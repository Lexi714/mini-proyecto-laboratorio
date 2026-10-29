<?php
/**
 * Avisa al destinatario que su pedido fue creado, por el canal elegido.
 */
final class OrderNotifier
{
    public function notify(Order $order, string $notificationType, string $destination): void
    {
        NotificationFactory::create($notificationType, $destination)
            ->send("Pedido {$order->id} por $ {$order->amount}");
    }
}
