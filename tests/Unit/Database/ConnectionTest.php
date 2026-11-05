<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Connection::class)]
final class ConnectionTest extends TestCase
{
    /** CP-CON-01: Singleton, siempre la misma instancia */
    public function testObtenerDevuelveSiempreLaMismaInstancia(): void
    {
        $this->assertSame(Connection::obtener(), Connection::obtener());
    }

    /** CP-CON-02 */
    public function testEjecutarRegistraLaConsulta(): void
    {
        $antes = count(Connection::obtener()->consultas());

        Connection::obtener()->ejecutar('SELECT 1');

        $consultas = Connection::obtener()->consultas();
        $this->assertCount($antes + 1, $consultas);
        $this->assertSame('SELECT 1', end($consultas));
    }

    /** CP-CON-03: no se puede crear una segunda conexion con new */
    public function testElConstructorEsPrivado(): void
    {
        $this->expectException(Error::class);

        new Connection();
    }

    /** CP-CON-04: tampoco se puede clonar */
    public function testNoSePuedeClonar(): void
    {
        $this->expectException(Error::class);

        clone Connection::obtener();
    }
}
