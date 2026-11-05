<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderSubject::class)]
#[CoversClass(EmailObserver::class)]
#[CoversClass(SmsObserver::class)]
#[CoversClass(DashboardObserver::class)]
final class OrderSubjectTest extends TestCase
{
    private function observadorEspia(array &$registro, string $nombre): OrderObserver
    {
        return new class ($registro, $nombre) implements OrderObserver {
            public function __construct(private array &$registro, private string $nombre)
            {
            }

            public function update(Order $order): void
            {
                $this->registro[] = $this->nombre . ':' . $order->id;
            }
        };
    }

    /** CP-OBS-01 */
    public function testNotificaATodosLosSuscriptoresEnOrden(): void
    {
        $registro = [];
        $subject = new OrderSubject();
        $subject->subscribe($this->observadorEspia($registro, 'a'));
        $subject->subscribe($this->observadorEspia($registro, 'b'));

        $subject->notify(new Order(9, 'Ana', 100.0, 'particular'));

        $this->assertSame(['a:9', 'b:9'], $registro);
    }

    /** CP-OBS-02 (negativo): sin suscriptores no pasa nada */
    public function testNotificarSinSuscriptoresNoHaceNada(): void
    {
        $subject = new OrderSubject();

        $subject->notify(new Order(1, 'Ana', 100.0, 'particular'));

        $this->expectOutputString('');
        $this->addToAssertionCount(1);
    }

    /** CP-OBS-03 */
    public function testCadaNotificacionLlegaUnaSolaVezPorSuscriptor(): void
    {
        $registro = [];
        $subject = new OrderSubject();
        $subject->subscribe($this->observadorEspia($registro, 'a'));

        $subject->notify(new Order(1, 'Ana', 100.0, 'particular'));
        $subject->notify(new Order(2, 'Ana', 100.0, 'particular'));

        $this->assertSame(['a:1', 'a:2'], $registro);
    }

    /** CP-OBS-04 */
    public function testEmailObserverEnviaUnEmail(): void
    {
        $this->expectOutputString('[EMAIL] para paciente@mail.com | Pedido creado: Pedido 7<br>');

        (new EmailObserver())->update(new Order(7, 'Ana', 100.0, 'particular'));
    }

    /** CP-OBS-05 */
    public function testSmsObserverEnviaUnSms(): void
    {
        $this->expectOutputString('[SMS] a 3704000000: Pedido 7 creado<br>');

        (new SmsObserver())->update(new Order(7, 'Ana', 100.0, 'particular'));
    }

    /** CP-OBS-06 */
    public function testDashboardObserverActualizaElTablero(): void
    {
        $this->expectOutputString('[DASHBOARD] actualizado para el pedido 7<br>');

        (new DashboardObserver())->update(new Order(7, 'Ana', 100.0, 'particular'));
    }

    /** CP-OBS-07: se pueden combinar los observadores reales */
    public function testLosTresObservadoresRealesSeEjecutanEnElOrdenSuscripto(): void
    {
        $this->expectOutputString(
            '[EMAIL] para paciente@mail.com | Pedido creado: Pedido 3<br>'
            . '[SMS] a 3704000000: Pedido 3 creado<br>'
            . '[DASHBOARD] actualizado para el pedido 3<br>'
        );
        $subject = new OrderSubject();
        $subject->subscribe(new EmailObserver());
        $subject->subscribe(new SmsObserver());
        $subject->subscribe(new DashboardObserver());

        $subject->notify(new Order(3, 'Ana', 100.0, 'particular'));
    }
}
