<?php
/**
 * Paciente particular: paga el monto completo.
 */
final class PrivatePatientStrategy implements PricingStrategy
{
    public function calculate(float $amount): float
    {
        return $amount;
    }
}
