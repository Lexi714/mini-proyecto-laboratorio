<?php
/**
 * Valida los datos de un pedido antes de crearlo.
 * Lanza DomainException con el mensaje para el usuario; no imprime nada.
 */
final class OrderValidator
{
    public function validate(string $patient, float $amount): void
    {
        if (trim($patient) === '') {
            throw new DomainException('Falta el paciente');
        }

        if ($amount <= 0) {
            throw new DomainException('Monto invalido');
        }
    }
}
