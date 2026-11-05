<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderPricingService::class)]
final class OrderPricingServiceTest extends TestCase
{
    public static function tiposDePaciente(): array
    {
        return [
            'CP-SRV-01 obra social' => ['obra_social', 1000.0, 700.0],
            'CP-SRV-02 jubilado'    => ['jubilado', 1000.0, 500.0],
            'CP-SRV-03 particular'  => ['particular', 1000.0, 1000.0],
        ];
    }

    #[DataProvider('tiposDePaciente')]
    public function testElTotalDependeDelTipoDePaciente(string $tipo, float $monto, float $esperado): void
    {
        $total = (new OrderPricingService())->totalFor($monto, $tipo);

        $this->assertEqualsWithDelta($esperado, $total, 0.001);
    }

    public static function tiposDesconocidos(): array
    {
        return [
            'CP-SRV-04 tipo inexistente' => ['vip'],
            'CP-SRV-05 tipo vacio'       => [''],
            'CP-SRV-06 mayusculas'       => ['OBRA_SOCIAL'],
        ];
    }

    #[DataProvider('tiposDesconocidos')]
    public function testUnTipoDesconocidoSeTrataComoParticularSinDescuento(string $tipo): void
    {
        $total = (new OrderPricingService())->totalFor(1000.0, $tipo);

        $this->assertEqualsWithDelta(1000.0, $total, 0.001);
    }
}
