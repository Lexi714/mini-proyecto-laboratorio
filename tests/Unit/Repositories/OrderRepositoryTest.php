<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderRepository::class)]
final class OrderRepositoryTest extends TestCase
{
    private function ultimaConsulta(): string
    {
        $consultas = Connection::obtener()->consultas();

        return (string) end($consultas);
    }

    /** CP-REP-01 */
    public function testGuardarEnviaUnInsertAlaConexion(): void
    {
        $repo = new OrderRepository(Connection::obtener());

        $repo->save(new Order(5, 'Juan Perez', 700.0, 'obra_social'));

        $this->assertSame(
            "INSERT INTO orders (id, patient, amount) VALUES (5, 'Juan Perez', 700)",
            $this->ultimaConsulta()
        );
    }

    /** CP-REP-02: una comilla en el nombre no rompe el SQL */
    public function testGuardarEscapaLasComillasSimples(): void
    {
        $repo = new OrderRepository(Connection::obtener());

        $repo->save(new Order(6, "O'Brien", 100.0, 'particular'));

        $this->assertStringContainsString("'O''Brien'", $this->ultimaConsulta());
    }

    /** CP-REP-03 */
    public function testBuscarTodosDevuelvePedidosDeEjemplo(): void
    {
        $pedidos = (new OrderRepository(Connection::obtener()))->findAll();

        $this->assertCount(2, $pedidos);
        $this->assertContainsOnlyInstancesOf(Order::class, $pedidos);
        $this->assertSame('Juan Perez', $pedidos[0]->patient);
        $this->assertSame(22000.0, $pedidos[1]->amount);
    }

    /** CP-REP-04: leer no escribe en la base */
    public function testBuscarTodosNoRegistraConsultas(): void
    {
        $antes = count(Connection::obtener()->consultas());

        (new OrderRepository(Connection::obtener()))->findAll();

        $this->assertCount($antes, Connection::obtener()->consultas());
    }
}
