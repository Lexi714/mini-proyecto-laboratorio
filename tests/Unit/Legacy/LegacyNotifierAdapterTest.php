<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyNotifierAdapter::class)]
final class LegacyNotifierAdapterTest extends TestCase
{
    /** CP-ADA-01: el adaptador cumple la interfaz Notification */
    public function testElAdaptadorEsUnaNotification(): void
    {
        $this->assertInstanceOf(Notification::class, new LegacyNotifierAdapter(new LegacyNotifier()));
    }

    /** CP-ADA-02: traduce send() a sendMessage() del sistema viejo */
    public function testSendLlamaASendMessageDelSistemaViejo(): void
    {
        $legacy = $this->createMock(LegacyNotifier::class);
        $legacy->expects($this->once())->method('sendMessage')->with('hola');

        (new LegacyNotifierAdapter($legacy))->send('hola');
    }

    /** CP-ADA-03: se puede usar como cualquier otra notificacion */
    public function testLaSalidaEsLaDelSistemaViejo(): void
    {
        $this->expectOutputString('[LEGACY] hola<br>');

        (new LegacyNotifierAdapter(new LegacyNotifier()))->send('hola');
    }

    /** CP-ADA-04 (negativo): sin adaptador el sistema viejo NO sirve como Notification */
    public function testElSistemaViejoPorSiSoloNoEsUnaNotification(): void
    {
        $this->assertNotInstanceOf(Notification::class, new LegacyNotifier());
    }
}
