<?php
/**
 * Avisa por email que se creo un pedido.
 */
final class EmailObserver implements OrderObserver
{
    public function update(Order $order): void
    {
        NotificationFactory::create('email', 'paciente@mail.com', 'Pedido creado')->send("Pedido {$order->id}");
    }
}
