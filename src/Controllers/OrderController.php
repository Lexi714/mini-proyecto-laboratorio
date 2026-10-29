<?php
/**
 * ============================================================================
 *  CONTROLADOR DE PEDIDOS
 *  Patron aplicado: MVC + SRP
 * ============================================================================
 *
 *  El controlador solo: recibe la entrada, llama a UN servicio o fachada y
 *  elige que vista renderizar. No arma SQL, no tiene reglas de negocio y no
 *  imprime HTML (eso es de las vistas en /views).
 * ============================================================================
 */

final class OrderController
{
    private const NOTIFICATION_TYPE = 'email';
    private const DESTINATION       = 'paciente@mail.com';

    public function __construct(
        private OrderFacade $orders,
        private ReportService $reports
    ) {
    }

    public function create(): void
    {
        try {
            $this->render('order_created', ['order' => $this->orders->createOrder(...$this->input())]);
        } catch (DomainException $e) {
            $this->render('order_error', ['mensaje' => $e->getMessage()]);
        }
    }

    public function index(): void
    {
        $this->render('orders', ['pedidos' => $this->orders->listOrders()]);
    }

    public function report(): void
    {
        $this->render('report', ['reporte' => $this->reports->dailyReport()]);
    }

    /** Lee y normaliza la entrada; no valida reglas de negocio. */
    private function input(): array
    {
        return [
            (int) $this->param('id', '1'),
            $this->param('paciente', 'Juan Perez'),
            (float) $this->param('monto', '15000'),
            $this->param('tipo', 'obra_social'),
            self::NOTIFICATION_TYPE,
            self::DESTINATION,
        ];
    }

    private function param(string $key, string $default): string
    {
        $value = $_GET[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private function render(string $view, array $data): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../../views/' . $view . '.php';
    }
}
