<?php
/**
 * Contrato de un reporte. El reporte base y todos los decoradores lo cumplen,
 * por eso se pueden encadenar en cualquier combinacion.
 */
interface Report
{
    public function generate(): string;
}
