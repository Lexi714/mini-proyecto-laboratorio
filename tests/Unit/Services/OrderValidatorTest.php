<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderValidator::class)]
final class OrderValidatorTest extends TestCase
{
    /** CP-VAL-01 */
    public function testAceptaUnPedidoValido(): void
    {
        (new OrderValidator())->validate('Juan Perez', 15000.0);

        $this->addToAssertionCount(1); // no lanzo ninguna excepcion
    }

    /** CP-VAL-02 */
    public function testAceptaUnMontoMinimoPositivo(): void
    {
        (new OrderValidator())->validate('Ana', 0.01);

        $this->addToAssertionCount(1);
    }

    public static function pacientesInvalidos(): array
    {
        return [
            'CP-VAL-03 vacio'    => [''],
            'CP-VAL-04 espacios' => ['   '],
        ];
    }

    #[DataProvider('pacientesInvalidos')]
    public function testRechazaUnPacienteSinNombre(string $paciente): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Falta el paciente');

        (new OrderValidator())->validate($paciente, 100.0);
    }

    public static function montosInvalidos(): array
    {
        return [
            'CP-VAL-05 cero'     => [0.0],
            'CP-VAL-06 negativo' => [-50.0],
        ];
    }

    #[DataProvider('montosInvalidos')]
    public function testRechazaUnMontoNoPositivo(float $monto): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Monto invalido');

        (new OrderValidator())->validate('Juan Perez', $monto);
    }
}
