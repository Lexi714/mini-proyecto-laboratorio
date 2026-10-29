<?php
/**
 * Contrato de un algoritmo de precio.
 * Cada tipo de paciente es una clase que implementa esta interfaz.
 */
interface PricingStrategy
{
    public function calculate(float $amount): float;
}
