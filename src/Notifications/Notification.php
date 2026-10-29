<?php
/**
 * Contrato comun de toda notificacion saliente.
 * El resto del sistema depende de esta interfaz, no de las clases concretas.
 */
interface Notification
{
    public function send(string $message): void;
}
