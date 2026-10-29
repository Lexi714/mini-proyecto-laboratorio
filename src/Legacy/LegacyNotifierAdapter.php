<?php
/**
 * ============================================================================
 *  ADAPTER DEL SISTEMA HEREDADO
 *  Patron aplicado: ADAPTER (estructural)
 * ============================================================================
 *
 *  Hace que LegacyNotifier cumpla NUESTRO contrato (Notification).
 *  La incompatibilidad (sendMessage vs send) queda encerrada en este archivo.
 *
 *  COMPOSICION: el adapter TIENE el sistema viejo adentro.
 *
 *  Estructura:
 *    Nuestro sistema -> Notification <- LegacyNotifierAdapter -> LegacyNotifier
 *
 *  Uso:
 *    $notificacion = new LegacyNotifierAdapter(new LegacyNotifier());
 *    $notificacion->send('Pedido creado');
 * ============================================================================
 */

final class LegacyNotifierAdapter implements Notification
{
    public function __construct(private LegacyNotifier $legacy)
    {
    }

    public function send(string $message): void
    {
        $this->legacy->sendMessage($message);
    }
}
