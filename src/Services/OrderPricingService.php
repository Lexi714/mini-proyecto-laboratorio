<?php
/**
 * Calcula el total de un pedido segun el tipo de paciente.
 * Elige la estrategia de precio y delega el calculo en PriceCalculator.
 */
final class OrderPricingService
{
    public function totalFor(float $amount, string $patientType): float
    {
        $strategy = match ($patientType) {
            'obra_social' => new InsuranceStrategy(),
            'jubilado'    => new RetiredStrategy(),
            default       => new PrivatePatientStrategy(),
        };

        return (new PriceCalculator($strategy))->calculate($amount);
    }
}
