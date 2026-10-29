<?php
/**
 * ============================================================================
 *  FACHADA DE PEDIDOS
 *  Patron aplicado: FACADE (estructural)
 * ============================================================================
 *
 *  Una unica entrada para crear un pedido: ORQUESTA servicios cohesivos y no
 *  decide ninguna regla de negocio ni imprime nada.
 *
 *    Controller -> OrderFacade -> Validator | Pricing | Repository
 *                                 | Notifier | Events (Observer)
 *
 *  - Validacion fallida: lanza DomainException (el mensaje es para el usuario).
 *  - Devuelve el Order creado; presentar el resultado es tarea de la vista.
 *  - Las dependencias llegan por constructor (se pueden reemplazar en pruebas).
 *
 *  Si esta clase empieza a DECIDIR reglas, se convierte en la nueva clase que
 *  hace todo: cada regla nueva va a su servicio, no aca.
 * ============================================================================
 */

final class OrderFacade
{
    public function __construct(
        private OrderValidator $validator,
        private OrderPricingService $pricing,
        private OrderRepository $repository,
        private OrderNotifier $notifier,
        private OrderSubject $events
    ) {
    }

    public function createOrder(
        int $id,
        string $patient,
        float $amount,
        string $patientType,
        string $notificationType,
        string $destination
    ): Order {
        $this->validator->validate($patient, $amount);

        $order = new Order($id, $patient, $this->pricing->totalFor($amount, $patientType), $patientType);

        $this->repository->save($order);
        $this->notifier->notify($order, $notificationType, $destination);
        $this->events->notify($order);

        return $order;
    }

    /** @return Order[] */
    public function listOrders(): array
    {
        return $this->repository->findAll();
    }
}
