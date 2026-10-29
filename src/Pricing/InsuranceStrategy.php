<?php
/**
 * Obra social: 30% de descuento. Es el unico lugar donde vive ese 0.7.
 */
final class InsuranceStrategy implements PricingStrategy
{
    public function __construct(private float $discount = 0.30)
    {
    }

    public function calculate(float $amount): float
    {
        return $amount * (1 - $this->discount);
    }
}
