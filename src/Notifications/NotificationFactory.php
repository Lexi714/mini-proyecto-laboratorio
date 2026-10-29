<?php
/**
 * ============================================================================
 *  FABRICA DE NOTIFICACIONES
 *  Patron aplicado: FACTORY METHOD (creacional)
 * ============================================================================
 *
 *  Unico lugar del sistema que decide QUE clase concreta crear segun el tipo.
 *  Controlador, servicio y eventos piden una Notification y no conocen
 *  EmailNotification ni SmsNotification.
 *
 *  Agregar WhatsApp = crear WhatsAppNotification + una linea en el match de
 *  abajo. Un tipo desconocido falla en voz alta (no se ignora en silencio).
 *
 *  Uso:
 *    NotificationFactory::create('email', 'juan@mail.com')->send('Su pedido fue creado');
 * ============================================================================
 */

final class NotificationFactory
{
    public static function create(string $type, string $to, string $subject = 'Pedido creado'): Notification
    {
        return match ($type) {
            'email' => new EmailNotification($to, $subject),
            'sms'   => new SmsNotification($to),
            default => throw new InvalidArgumentException("Tipo de notificacion no soportado: {$type}"),
        };
    }
}
