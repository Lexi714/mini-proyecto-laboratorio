<?php
/**
 * ============================================================================
 *  AVISOS AL CREARSE UN PEDIDO
 *  Patron aplicado: OBSERVER (comportamiento)
 * ============================================================================
 *
 *  El sujeto mantiene una coleccion de INTERFACES (OrderObserver) y les avisa
 *  en el orden en que se suscribieron. No conoce ninguna clase concreta:
 *  agregar un aviso nuevo es crear un observador y suscribirlo.
 *
 *  Uso:
 *    $subject = new OrderSubject();
 *    $subject->subscribe(new EmailObserver());
 *    $subject->subscribe(new DashboardObserver());
 *    $subject->notify($order);
 *
 *  Restricciones a tener presentes:
 *  - El orden de suscripcion es el orden de ejecucion.
 *  - Si un observador lanza una excepcion, los siguientes no se ejecutan.
 *  - Leyendo el codigo que crea el pedido ya no se ve quien se entera: para
 *    saberlo hay que mirar donde se suscribe (ver OrderService).
 * ============================================================================
 */

final class OrderSubject
{
    /** @var OrderObserver[] */
    private array $observers = [];

    public function subscribe(OrderObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(Order $order): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($order);
        }
    }
}
