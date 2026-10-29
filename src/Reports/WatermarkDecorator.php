<?php
/**
 * Agrega la marca de agua al reporte que envuelve.
 */
final class WatermarkDecorator implements Report
{
    public function __construct(private Report $report)
    {
    }

    public function generate(): string
    {
        return $this->report->generate() . ' + marca de agua';
    }
}
