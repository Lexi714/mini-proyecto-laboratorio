<?php
/**
 * ============================================================================
 *  CALCULO DE PRECIOS
 *  Patron aplicado: STRATEGY (comportamiento)
 * ============================================================================
 *
 *  PriceCalculator TIENE una estrategia (composicion) y delega en ella.
 *  Agregar un tipo de paciente nuevo es AGREGAR una clase que implemente
 *  PricingStrategy; esta clase no se modifica.
 *
 *  Uso:
 *    $calculadora = new PriceCalculator(new InsuranceStrategy());
 *    echo $calculadora->calculate(10000);
 * ============================================================================
 */

final class PriceCalculator
{
    private const VAT = 1.21;

    public function __construct(private PricingStrategy $strategy)
    {
    }

    public function calculate(float $amount): float
    {
        return $this->strategy->calculate($amount);
    }

    public function calculateWithVat(float $amount): float
    {
        return $this->calculate($amount) * self::VAT;
    }
}
