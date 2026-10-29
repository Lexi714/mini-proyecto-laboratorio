<?php
/**
 * ============================================================================
 *  REPOSITORIO DE PEDIDOS
 *  Patron aplicado: REPOSITORY
 * ============================================================================
 *
 *  Unico lugar que conoce como se persiste un Order. Si cambia el motor de
 *  base de datos, cambia esta clase y no el modelo.
 *  La conexion se inyecta por constructor (no se crea con new adentro).
 * ============================================================================
 */

class OrderRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(Order $order): void
    {
        $patient = str_replace("'", "''", $order->patient);

        $this->connection->ejecutar(
            "INSERT INTO orders (id, patient, amount) VALUES ({$order->id}, '{$patient}', {$order->amount})"
        );
    }

    /**
     * Pedidos guardados (importe = total ya calculado). Datos de ejemplo:
     * la persistencia sigue simulada en memoria.
     *
     * @return Order[]
     */
    public function findAll(): array
    {
        return [
            new Order(1, 'Juan Perez', 10500, 'obra_social'),
            new Order(2, 'Ana Gomez', 22000, 'particular'),
        ];
    }
}
