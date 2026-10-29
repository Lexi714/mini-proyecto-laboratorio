<?php
/**
 * Arma el reporte del dia encadenando los decoradores que corresponden.
 * El orden importa: firma -> PDF -> marca de agua.
 */
final class ReportService
{
    public function dailyReport(): string
    {
        return (new WatermarkDecorator(
            new PdfReportDecorator(new DigitalSignatureDecorator(new BasicReport('Pedidos del dia')))
        ))->generate();
    }
}
