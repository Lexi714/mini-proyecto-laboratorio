<?php
/**
 * Notificacion por SMS.
 */
final class SmsNotification implements Notification
{
    public function __construct(private string $to)
    {
    }

    public function send(string $message): void
    {
        echo "[SMS] a {$this->to}: {$message}<br>";
    }
}
