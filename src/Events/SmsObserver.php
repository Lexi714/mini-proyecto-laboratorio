<?php
/**
 * Avisa por SMS que se creo un pedido.
 */
final class SmsObserver implements OrderObserver
{
    public function update(Order $order): void
    {
        NotificationFactory::create('sms', '3704000000')->send("Pedido {$order->id} creado");
    }
}
