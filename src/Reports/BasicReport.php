<?php
/**
 * Reporte base: solo el contenido, sin agregados.
 */
final class BasicReport implements Report
{
    public function __construct(private string $contenido)
    {
    }

    public function generate(): string
    {
        return "Reporte: {$this->contenido}";
    }
}
