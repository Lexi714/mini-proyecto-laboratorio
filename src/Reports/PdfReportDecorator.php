<?php
/**
 * Agrega la salida en PDF al reporte que envuelve.
 */
final class PdfReportDecorator implements Report
{
    public function __construct(private Report $report)
    {
    }

    public function generate(): string
    {
        return $this->report->generate() . ' + PDF';
    }
}
