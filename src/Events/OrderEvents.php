<?php
/**
 * ============================================================================
 *  AVISOS AL CREARSE UN PEDIDO
 *  Patron esperado: OBSERVER (comportamiento)
 * ============================================================================
 *
 *  ❌ DEUDA SEMBRADA
 *     Quien crea el pedido llama a mano a cada interesado, uno por uno.
 *     Sumar un aviso nuevo (auditoria, WhatsApp, dashboard) obliga a modificar
 *     esta clase Y todos los lugares que la usan.
 *     Si un aviso falla, se corta la cadena y los siguientes no se ejecutan.
 *
 *  ✅ FORMA CORRECTA
 *     Un sujeto que mantiene una coleccion de observadores y les avisa.
 *     El sujeto conoce la interfaz, no las clases concretas.
 * ============================================================================
 */

class OrderEvents
{
    /**
     * ❌ METODO MAL APLICADO: pedidoCreado()
     *    Acoplamiento fuerte con TRES clases concretas.
     *    Viola OCP y ademas viola Inversion de Dependencias:
     *    la clase de alto nivel depende de detalles de bajo nivel.
     */
    public function pedidoCreado(Order $order): void
    {
        // ❌ llamadas directas y encadenadas
        NotificationFactory::create('email', 'paciente@mail.com', 'Pedido creado')->send("Pedido {$order->id}");
        NotificationFactory::create('sms', '3704000000')->send("Pedido {$order->id} creado");

        // ❌ Si esta linea lanza una excepcion, el mail ya salio pero el
        //    dashboard nunca se entera. Estado inconsistente y silencioso.
        echo "[DASHBOARD] actualizado para el pedido {$order->id}<br>";
    }
}

/*
 * ============================================================================
 *  ✅ FORMA CORRECTA — OBSERVER
 * ============================================================================
 *
 * interface OrderObserver
 * {
 *     public function update(Order $order): void;
 * }
 *
 * final class EmailObserver implements OrderObserver
 * {
 *     public function update(Order $order): void
 *     {
 *         echo "Email enviado para el pedido {$order->id}";
 *     }
 * }
 *
 * final class OrderSubject
 * {
 *     private array $observers = [];   // coleccion de INTERFACES, no de clases
 *
 *     public function subscribe(OrderObserver $observer): void
 *     {
 *         $this->observers[] = $observer;
 *     }
 *
 *     public function notify(Order $order): void
 *     {
 *         foreach ($this->observers as $observer) {
 *             $observer->update($order);   // POLIMORFISMO
 *         }
 *     }
 * }
 *
 * Uso:
 *   $subject = new OrderSubject();
 *   $subject->subscribe(new EmailObserver());
 *   $subject->subscribe(new DashboardObserver());
 *   $subject->notify($order);
 *
 * ----------------------------------------------------------------------------
 * EJERCICIO 5 (TP): agregar SmsObserver sin tocar OrderSubject.
 *
 * CONSECUENCIA A JUSTIFICAR EN EL PR: con Observer, leyendo el codigo del
 * pedido ya NO se ve quien se entera del evento. Ganamos desacoplamiento y
 * perdimos trazabilidad. Como lo compensan? (log, nombres explicitos, tests)
 * ============================================================================
 */
