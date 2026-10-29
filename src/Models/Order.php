<?php
/**
 * ============================================================================
 *  MODELO Order
 * ============================================================================
 *
 *  Entidad de dominio: solo datos del pedido y sus invariantes.
 *  - Inmutable (readonly): no se puede dejar en un estado invalido despues de crearlo.
 *  - Valida en el constructor: no existe un Order con paciente vacio o monto negativo.
 *  - No conoce SQL (ver src/Repositories/OrderRepository.php).
 *  - No calcula precios (ver src/Pricing/PriceCalculator.php).
 * ============================================================================
 */

class Order
{
    public function __construct(
        public readonly int $id,
        public readonly string $patient,
        public readonly float $amount,
        public readonly string $patientType
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('El importe no puede ser negativo');
        }
        if (trim($patient) === '') {
            throw new InvalidArgumentException('El paciente es obligatorio');
        }
    }
}
