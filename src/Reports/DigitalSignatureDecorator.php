<?php
/**
 * Agrega la firma digital al reporte que envuelve.
 */
final class DigitalSignatureDecorator implements Report
{
    public function __construct(private Report $report)
    {
    }

    public function generate(): string
    {
        return $this->report->generate() . ' + firma digital';
    }
}
