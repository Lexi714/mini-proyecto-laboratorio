<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BasicReport::class)]
#[CoversClass(DigitalSignatureDecorator::class)]
#[CoversClass(PdfReportDecorator::class)]
#[CoversClass(WatermarkDecorator::class)]
#[CoversClass(ReportService::class)]
final class ReportDecoratorTest extends TestCase
{
    /** CP-DEC-01 */
    public function testElReporteBasicoMuestraSuContenido(): void
    {
        $this->assertSame('Reporte: Pedidos del dia', (new BasicReport('Pedidos del dia'))->generate());
    }

    /** CP-DEC-02 */
    public function testCadaDecoradorAgregaSuParte(): void
    {
        $base = new BasicReport('x');

        $this->assertSame('Reporte: x + firma digital', (new DigitalSignatureDecorator($base))->generate());
        $this->assertSame('Reporte: x + PDF', (new PdfReportDecorator($base))->generate());
        $this->assertSame('Reporte: x + marca de agua', (new WatermarkDecorator($base))->generate());
    }

    /** CP-DEC-03: los decoradores se pueden apilar */
    public function testLosDecoradoresSeApilan(): void
    {
        $reporte = new PdfReportDecorator(new DigitalSignatureDecorator(new BasicReport('x')));

        $this->assertSame('Reporte: x + firma digital + PDF', $reporte->generate());
    }

    /** CP-DEC-04: el orden de los decoradores importa */
    public function testElOrdenDeLosDecoradoresCambiaElResultado(): void
    {
        $a = new PdfReportDecorator(new WatermarkDecorator(new BasicReport('x')));
        $b = new WatermarkDecorator(new PdfReportDecorator(new BasicReport('x')));

        $this->assertNotSame($a->generate(), $b->generate());
    }

    /** CP-DEC-05: todo decorador es tambien un Report */
    public function testLosDecoradoresCumplenLaInterfazReport(): void
    {
        $base = new BasicReport('x');

        $this->assertInstanceOf(Report::class, new DigitalSignatureDecorator($base));
        $this->assertInstanceOf(Report::class, new PdfReportDecorator($base));
        $this->assertInstanceOf(Report::class, new WatermarkDecorator($base));
    }

    /** CP-DEC-06 */
    public function testElReporteDiarioTieneLaCadenaCompleta(): void
    {
        $this->assertSame(
            'Reporte: Pedidos del dia + firma digital + PDF + marca de agua',
            (new ReportService())->dailyReport()
        );
    }
}
