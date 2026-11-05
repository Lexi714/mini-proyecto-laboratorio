<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Order::class)]
final class OrderTest extends TestCase
{
    /** CP-ORD-01 */
    public function testGuardaLosDatosRecibidos(): void
    {
        $order = new Order(7, 'Juan Perez', 700.0, 'obra_social');

        $this->assertSame(7, $order->id);
        $this->assertSame('Juan Perez', $order->patient);
        $this->assertSame(700.0, $order->amount);
        $this->assertSame('obra_social', $order->patientType);
    }

    /** CP-ORD-02 */
    public function testAceptaImporteCero(): void
    {
        $this->assertSame(0.0, (new Order(1, 'Ana', 0.0, 'particular'))->amount);
    }

    /** CP-ORD-03 */
    public function testRechazaImporteNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El importe no puede ser negativo');

        new Order(1, 'Ana', -1.0, 'particular');
    }

    /** CP-ORD-04 */
    public function testRechazaPacienteVacio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El paciente es obligatorio');

        new Order(1, '  ', 100.0, 'particular');
    }

    /** CP-ORD-05: las propiedades son readonly, el pedido no cambia despues de creado */
    public function testElPedidoEsInmutable(): void
    {
        $order = new Order(1, 'Ana', 100.0, 'particular');

        $this->expectException(Error::class);

        $order->amount = 1.0; // @phpstan-ignore-line
    }
}
