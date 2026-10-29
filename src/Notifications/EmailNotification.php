<?php
/**
 * Notificacion por email.
 */
final class EmailNotification implements Notification
{
    public function __construct(private string $to, private string $subject)
    {
    }

    public function send(string $message): void
    {
        echo "[EMAIL] para {$this->to} | {$this->subject}: {$message}<br>";
    }
}
