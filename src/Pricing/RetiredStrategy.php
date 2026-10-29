<?php
/**
 * Jubilado: 50% de descuento.
 */
final class RetiredStrategy implements PricingStrategy
{
    public function __construct(private float $discount = 0.50)
    {
    }

    public function calculate(float $amount): float
    {
        return $amount * (1 - $this->discount);
    }
}
