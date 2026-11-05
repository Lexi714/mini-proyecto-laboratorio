<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrivatePatientStrategy::class)]
#[CoversClass(InsuranceStrategy::class)]
#[CoversClass(RetiredStrategy::class)]
#[CoversClass(PriceCalculator::class)]
final class PricingStrategyTest extends TestCase
{
    /** CP-PRE-01 / CP-PRE-02 / CP-PRE-03 */
    public static function strategiasPorDefecto(): array
    {
        return [
            'particular: sin descuento'   => [new PrivatePatientStrategy(), 1000.0, 1000.0],
            'obra social: 30% de descuento' => [new InsuranceStrategy(), 1000.0, 700.0],
            'jubilado: 50% de descuento'  => [new RetiredStrategy(), 1000.0, 500.0],
        ];
    }

    #[DataProvider('strategiasPorDefecto')]
    public function testCadaEstrategiaAplicaSuDescuento(PricingStrategy $strategy, float $monto, float $esperado): void
    {
        $this->assertEqualsWithDelta($esperado, $strategy->calculate($monto), 0.001);
    }

    /** CP-PRE-04 */
    public function testElDescuentoSePuedeConfigurar(): void
    {
        $this->assertEqualsWithDelta(900.0, (new InsuranceStrategy(0.10))->calculate(1000.0), 0.001);
    }

    /** CP-PRE-05 */
    public function testMontoCeroDaCeroEnTodasLasEstrategias(): void
    {
        $this->assertSame(0.0, (new PrivatePatientStrategy())->calculate(0.0));
        $this->assertSame(0.0, (new InsuranceStrategy())->calculate(0.0));
        $this->assertSame(0.0, (new RetiredStrategy())->calculate(0.0));
    }

    /** CP-PRE-06 */
    public function testElCalculadorDelegaEnLaEstrategiaElegida(): void
    {
        $calculator = new PriceCalculator(new RetiredStrategy());

        $this->assertEqualsWithDelta(500.0, $calculator->calculate(1000.0), 0.001);
    }

    /** CP-PRE-07 */
    public function testElCalculadorAgregaIvaDespuesDelDescuento(): void
    {
        $calculator = new PriceCalculator(new InsuranceStrategy());

        $this->assertEqualsWithDelta(847.0, $calculator->calculateWithVat(1000.0), 0.001);
    }

    /** CP-PRE-08: se puede cambiar de estrategia sin tocar el calculador (OCP) */
    public function testSeAceptaCualquierImplementacionDeLaInterfaz(): void
    {
        $mitad = new class implements PricingStrategy {
            public function calculate(float $amount): float
            {
                return $amount / 2;
            }
        };

        $this->assertEqualsWithDelta(50.0, (new PriceCalculator($mitad))->calculate(100.0), 0.001);
    }

    /** CP-PRE-09 (negativo): el calculador solo acepta estrategias de precio */
    public function testElCalculadorRechazaAlgoQueNoEsUnaEstrategia(): void
    {
        $this->expectException(TypeError::class);

        new PriceCalculator(new stdClass()); // @phpstan-ignore-line
    }
}
