<?php
/**
 * Actualiza el dashboard cuando se crea un pedido.
 */
final class DashboardObserver implements OrderObserver
{
    public function update(Order $order): void
    {
        echo "[DASHBOARD] actualizado para el pedido {$order->id}<br>";
    }
}
