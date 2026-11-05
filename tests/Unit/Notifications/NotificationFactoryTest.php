<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotificationFactory::class)]
#[CoversClass(EmailNotification::class)]
#[CoversClass(SmsNotification::class)]
final class NotificationFactoryTest extends TestCase
{
    /** CP-NOT-01 */
    public function testCreaUnaNotificacionDeEmail(): void
    {
        $notification = NotificationFactory::create('email', 'paciente@mail.com', 'Hola');

        $this->assertInstanceOf(EmailNotification::class, $notification);
    }

    /** CP-NOT-02 */
    public function testCreaUnaNotificacionDeSms(): void
    {
        $notification = NotificationFactory::create('sms', '3704000000');

        $this->assertInstanceOf(SmsNotification::class, $notification);
    }

    /** CP-NOT-03: el email incluye destinatario, asunto y mensaje */
    public function testElEmailSeEnviaConElFormatoEsperado(): void
    {
        $this->expectOutputString('[EMAIL] para paciente@mail.com | Hola: Pedido 1<br>');

        NotificationFactory::create('email', 'paciente@mail.com', 'Hola')->send('Pedido 1');
    }

    /** CP-NOT-04 */
    public function testElSmsSeEnviaConElFormatoEsperado(): void
    {
        $this->expectOutputString('[SMS] a 3704000000: Pedido 1 creado<br>');

        NotificationFactory::create('sms', '3704000000')->send('Pedido 1 creado');
    }

    /** CP-NOT-05: el asunto por defecto */
    public function testElEmailUsaElAsuntoPorDefecto(): void
    {
        $this->expectOutputString('[EMAIL] para a@b.com | Pedido creado: x<br>');

        NotificationFactory::create('email', 'a@b.com')->send('x');
    }

    /** CP-NOT-06 */
    public function testUnTipoNoSoportadoLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de notificacion no soportado: whatsapp');

        NotificationFactory::create('whatsapp', '3704000000');
    }

    /** CP-NOT-07 */
    public function testUnTipoVacioLanzaExcepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationFactory::create('', 'x');
    }

    /** CP-NOT-08: el tipo distingue mayusculas, "EMAIL" no es "email" */
    public function testElTipoDistingueMayusculas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationFactory::create('EMAIL', 'a@b.com');
    }
}
