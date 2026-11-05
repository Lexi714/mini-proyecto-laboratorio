<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Prueba el patron MVC de punta a punta: el controlador lee la entrada,
 * llama a la fachada y renderiza la vista (con las dependencias reales).
 */
#[CoversClass(OrderController::class)]
final class OrderControllerTest extends TestCase
{
    private array $getOriginal = [];

    protected function setUp(): void
    {
        $this->getOriginal = $_GET;
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = $this->getOriginal;
    }

    private function controlador(): OrderController
    {
        return new OrderController(
            new OrderFacade(
                new OrderValidator(),
                new OrderPricingService(),
                new OrderRepository(Connection::obtener()),
                new OrderNotifier(),
                new OrderSubject()
            ),
            new ReportService()
        );
    }

    private function capturar(callable $accion): string
    {
        ob_start();
        try {
            $accion();
        } finally {
            $salida = (string) ob_get_clean();
        }

        return $salida;
    }

    /** CP-CTL-01: valores por defecto cuando no llega nada */
    public function testCrearUsaLosValoresPorDefecto(): void
    {
        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringContainsString('<h1>Pedido creado</h1>', $salida);
        $this->assertStringContainsString('Paciente: Juan Perez', $salida);
        $this->assertStringContainsString('Total: $ 10500', $salida);
    }

    /** CP-CTL-02: datos recibidos por GET, con descuento de jubilado */
    public function testCrearUsaLosDatosRecibidos(): void
    {
        $_GET = ['paciente' => 'Ana Gomez', 'monto' => '2000', 'tipo' => 'jubilado'];

        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringContainsString('Paciente: Ana Gomez', $salida);
        $this->assertStringContainsString('Total: $ 1000', $salida);
    }

    /** CP-CTL-03 (negativo): validacion, se muestra la vista de error */
    public function testCrearConPacienteVacioMuestraElError(): void
    {
        $_GET = ['paciente' => ''];

        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringContainsString('<h1>Pedido no creado</h1>', $salida);
        $this->assertStringContainsString('Falta el paciente', $salida);
        $this->assertStringNotContainsString('Pedido creado</h1>', $salida);
    }

    /** CP-CTL-04 (negativo) */
    public function testCrearConMontoCeroMuestraElError(): void
    {
        $_GET = ['monto' => '0'];

        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringContainsString('Monto invalido', $salida);
    }

    /** CP-CTL-05 (negativo): el HTML del paciente se escapa, no se ejecuta */
    public function testLaSalidaSeEscapaContraXss(): void
    {
        $_GET = ['paciente' => '<script>alert(1)</script>'];

        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringNotContainsString('<script>', $salida);
        $this->assertStringContainsString('&lt;script&gt;', $salida);
    }

    /** CP-CTL-06 (negativo): un parametro en forma de arreglo se ignora */
    public function testUnParametroArregloUsaElValorPorDefecto(): void
    {
        $_GET = ['paciente' => ['x']];

        $salida = $this->capturar(fn() => $this->controlador()->create());

        $this->assertStringContainsString('Paciente: Juan Perez', $salida);
    }

    /** CP-CTL-07 */
    public function testListarMuestraLaTablaDePedidos(): void
    {
        $salida = $this->capturar(fn() => $this->controlador()->index());

        $this->assertStringContainsString('<h1>Pedidos</h1>', $salida);
        $this->assertStringContainsString('Juan Perez', $salida);
        $this->assertStringContainsString('22000', $salida);
    }

    /** CP-CTL-08 */
    public function testReporteMuestraLaCadenaDeDecoradores(): void
    {
        $salida = $this->capturar(fn() => $this->controlador()->report());

        $this->assertStringContainsString('Reporte: Pedidos del dia + firma digital + PDF + marca de agua', $salida);
    }
}
