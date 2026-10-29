<?php
/**
 * ============================================================================
 *  CONEXION A LA BASE DE DATOS
 *  Patron aplicado: SINGLETON (creacional)
 * ============================================================================
 *
 *  Una unica instancia de Connection por request:
 *  - Constructor privado: nadie puede hacer new Connection() desde afuera.
 *  - obtener() devuelve siempre la misma instancia.
 *  - Si algo falla al ejecutar, el error se propaga (no se traga en silencio).
 *
 *  Singleton solo para recursos compartidos como la conexion. NO para guardar
 *  el pedido actual ni el usuario logueado: ahi seria una variable global.
 * ============================================================================
 */

class Connection
{
    private static ?Connection $instance = null;

    /** Almacen en memoria para poder correr la demo sin MySQL levantado. */
    private static array $tablaEnMemoria = [];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function obtener(): Connection
    {
        if (self::$instance === null) {
            self::$instance = new Connection();
        }

        return self::$instance;
    }

    public function ejecutar(string $sql): void
    {
        self::$tablaEnMemoria[] = $sql;
    }

    public function consultas(): array
    {
        return self::$tablaEnMemoria;
    }
}
