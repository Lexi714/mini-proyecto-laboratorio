<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderFacade::class)]
#[CoversClass(OrderNotifier::class)]
final class OrderFacadeTest extends TestCase
{
    private OrderSubject $events;

    /** @var string[] */
    private array $avisos = [];

    protected function setUp(): void
    {
        $this->events = new OrderSubject();
        $this->avisos = [];

        $avisos = &$this->avisos;
        $this->events->subscribe(new class ($avisos) implements OrderObserver {
            public function __construct(private array &$avisos)
            {
            }

            public function update(Order $order): void
            {
                $this->avisos[] = 'observador:' . $order->id;
            }
        });
    }

    private function fachada(): OrderFacade
    {
        return new OrderFacade(
            new OrderValidator(),
            new OrderPricingService(),
            new OrderRepository(Connection::obtener()),
            new OrderNotifier(),
            $this->events
        );
    }

    private function consultasGuardadas(): int
    {
        return count(Connection::obtener()->consultas());
    }

    /** CP-FAC-01: flujo completo con descuento de obra social */
    public function testCreaUnPedidoAplicandoElDescuento(): void
    {
        $this->expectOutputString('[EMAIL] para paciente@mail.com | Pedido creado: Pedido 1 por $ 700<br>');

        $order = $this->fachada()->createOrder(1, 'Juan Perez', 1000.0, 'obra_social', 'email', 'paciente@mail.com');

        $this->assertSame(1, $order->id);
        $this->assertEqualsWithDelta(700.0, $order->amount, 0.001);
    }

    /** CP-FAC-02: el pedido se guarda y se avisa a los observadores */
    public function testGuardaElPedidoYAvisaALosObservadores(): void
    {
        $this->expectOutputRegex('/EMAIL/');
        $antes = $this->consultasGuardadas();

        $this->fachada()->createOrder(2, 'Ana', 500.0, 'particular', 'email', 'a@b.com');

        $this->assertSame($antes + 1, $this->consultasGuardadas());
        $this->assertSame(['observador:2'], $this->avisos);
    }

    /** CP-FAC-03: se puede elegir SMS como canal */
    public function testPermiteNotificarPorSms(): void
    {
        $this->expectOutputString('[SMS] a 3704000000: Pedido 3 por $ 500<br>');

        $this->fachada()->createOrder(3, 'Ana', 1000.0, 'jubilado', 'sms', '3704000000');
    }

    /** CP-FAC-04 (negativo): paciente vacio, no se guarda ni se notifica */
    public function testConPacienteVacioNoGuardaNiNotifica(): void
    {
        $antes = $this->consultasGuardadas();

        try {
            $this->fachada()->createOrder(4, '', 1000.0, 'particular', 'email', 'a@b.com');
            $this->fail('Debio lanzar DomainException');
        } catch (DomainException $e) {
            $this->assertSame('Falta el paciente', $e->getMessage());
        }

        $this->assertSame($antes, $this->consultasGuardadas());
        $this->assertSame([], $this->avisos);
    }

    /** CP-FAC-05 (negativo): monto invalido, no se guarda ni se notifica */
    public function testConMontoInvalidoNoGuardaNiNotifica(): void
    {
        $antes = $this->consultasGuardadas();

        try {
            $this->fachada()->createOrder(5, 'Ana', 0.0, 'particular', 'email', 'a@b.com');
            $this->fail('Debio lanzar DomainException');
        } catch (DomainException $e) {
            $this->assertSame('Monto invalido', $e->getMessage());
        }

        $this->assertSame($antes, $this->consultasGuardadas());
        $this->assertSame([], $this->avisos);
    }

    /** CP-FAC-06 (negativo): canal de notificacion no soportado */
    public function testUnCanalNoSoportadoLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->fachada()->createOrder(6, 'Ana', 100.0, 'particular', 'paloma', 'x');
    }

    /** CP-FAC-07 */
    public function testListarDevuelveLosPedidosDelRepositorio(): void
    {
        $this->assertCount(2, $this->fachada()->listOrders());
    }
}
