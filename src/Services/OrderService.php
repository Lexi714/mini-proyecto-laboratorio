<?php
/**
 * ============================================================================
 *  SERVICIO DE PEDIDOS
 *  Patron esperado: FACADE (estructural) + separacion en servicios cohesivos
 * ============================================================================
 *
 *  ❌ DEUDA SEMBRADA
 *     Un unico metodo procesarPedidoCompleto() que hace de todo:
 *     valida, calcula, cobra, guarda, notifica, reporta y ademas imprime HTML.
 *     Es la clase que nadie quiere tocar: cualquier cambio puede romper otra cosa.
 *     Ademas repite el if por tipo de notificacion que ya existe en NotificationSender.
 *
 *  ✅ FORMA CORRECTA
 *     1. Cada paso en su propio servicio cohesivo (validacion, precio, pago...).
 *     2. Una fachada que ORQUESTA esos servicios en un metodo corto y legible.
 *     3. Las dependencias se inyectan por constructor, no se crean con new adentro.
 *
 *  ⚠️ Una fachada COORDINA. Si empieza a DECIDIR reglas de negocio, se convierte
 *     en la nueva clase que hace todo y volvimos al punto de partida.
 * ============================================================================
 */

class OrderService
{
    /**
     * ❌ METODO MAL APLICADO: procesarPedidoCompleto()
     *    Sintomas medibles:
     *      - mas de 6 responsabilidades en un solo metodo
     *      - 4 niveles de anidamiento
     *      - imposible de probar sin base de datos ni servidor de mail
     *      - imprime HTML desde la capa de servicio (rompe MVC)
     */
    public function procesarPedidoCompleto(
        int $id,
        string $paciente,
        float $monto,
        string $tipoPaciente,
        string $tipoNotificacion,
        string $destino
    ): void {
        // ---- 1. Validacion (deberia estar en su propio validador) ----
        if (trim($paciente) === '') {
            echo 'Falta el paciente<br>';   // ❌ la capa de servicio imprime HTML
            return;
        }

        if ($monto <= 0) {
            echo 'Monto invalido<br>';
            return;
        }

        // ---- 2. Calculo de precio (deberia ser una Strategy) ----
        $total = $monto;

        if ($tipoPaciente === 'obra_social') {
            $total = $monto * 0.7;          // ❌ tercera copia del 0.7 en el proyecto
        } elseif ($tipoPaciente === 'jubilado') {
            $total = $monto * 0.5;
        }

        // ---- 3. Persistencia (deberia ser un Repository) ----
        // ❌ new adentro del metodo: no se puede reemplazar por un doble en pruebas
        $order = new Order($id, $paciente, $total, $tipoPaciente);
        $order->guardar();

        // ---- 4. Notificacion ----
        NotificationFactory::create($tipoNotificacion, $destino)->send("Pedido {$id} por $ {$total}");

        // ---- 5. Avisos internos ----
        $eventos = new OrderSubject();
        $eventos->subscribe(new EmailObserver());
        $eventos->subscribe(new SmsObserver());
        $eventos->subscribe(new DashboardObserver());
        $eventos->notify($order);

        // ---- 6. Reporte ----
        $reporte = new PdfReportDecorator(new DigitalSignatureDecorator(new BasicReport("Pedido {$id}")));
        echo $reporte->generate() . '<br>';

        // ---- 7. Presentacion (deberia ser una View) ----
        echo "<p>Pedido {$id} procesado. Total: $ {$total}</p>";
    }
}

/*
 * ============================================================================
 *  ✅ FORMA CORRECTA — FACADE SOBRE SERVICIOS COHESIVOS
 * ============================================================================
 *
 * final class OrderFacade
 * {
 *     public function __construct(
 *         private OrderValidator $validator,
 *         private PriceCalculator $calculator,
 *         private OrderRepository $repository,
 *         private OrderSubject $events
 *     ) {}
 *
 *     public function createOrder(Order $order): void
 *     {
 *         $this->validator->validate($order);
 *         $total = $this->calculator->calculate($order->amount);
 *         $this->repository->save($order);
 *         $this->events->notify($order);
 *     }
 * }
 *
 * El controlador queda en una linea:
 *   $facade->createOrder($order);
 *
 * Estructura:
 *   Controller -> OrderFacade -> Validator | Pricing | Repository | Events
 *
 * ----------------------------------------------------------------------------
 * EJERCICIO 6 (TP): dejar procesarPedidoCompleto() en menos de 10 lineas
 * sin que el sistema pierda ninguna funcionalidad.
 *
 * PREGUNTA PARA EL PR: Facade elimina la complejidad o la mueve de lugar?
 * ============================================================================
 */
