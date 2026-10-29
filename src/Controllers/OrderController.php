<?php
/**
 * ============================================================================
 *  CONTROLADOR DE PEDIDOS
 *  Patron esperado: MVC bien aplicado (arquitectonico) + SRP
 * ============================================================================
 *
 *  ❌ DEUDA SEMBRADA — el "God Controller"
 *     1. Arma SQL directamente.
 *     2. Contiene reglas de negocio (descuentos).
 *     3. Imprime HTML con echo.
 *     4. Crea con new todas sus dependencias concretas.
 *     5. Repite por TERCERA vez el if de notificaciones.
 *
 *  Tener las carpetas /Controllers /Models /views NO significa aplicar MVC.
 *  MVC es separacion de responsabilidades, no estructura de directorios.
 *
 *  ✅ FORMA CORRECTA
 *     El controlador solo: recibe la entrada, la valida como entrada,
 *     llama a UN servicio o fachada, y elige que vista renderizar.
 * ============================================================================
 */

class OrderController
{
    /**
     * ❌ METODO MAL APLICADO: create()
     *    Mezcla las tres capas de MVC dentro de un solo metodo.
     */
    public function create(): void
    {
        // ❌ 1. Entrada sin validar ni sanitizar
        $id       = (int) ($_GET['id'] ?? 1);
        $paciente = $_GET['paciente'] ?? 'Juan Perez';
        $monto    = (float) ($_GET['monto'] ?? 15000);
        $tipo     = $_GET['tipo'] ?? 'obra_social';

        // ❌ 2. Regla de negocio dentro del controlador (deberia ser Strategy)
        $total = $tipo === 'obra_social' ? $monto * 0.7 : $monto;

        // ❌ 3. SQL dentro del controlador (deberia ser Repository)
        $conexion = Connection::obtener();
        $conexion->ejecutar("INSERT INTO orders VALUES ({$id}, '{$paciente}', {$total})");

        // 4. Notificacion
        NotificationFactory::create('email', 'paciente@mail.com', 'Pedido del laboratorio')->send("Pedido {$id} creado");

        // ❌ 5. HTML impreso desde el controlador (deberia ser una View)
        echo "<h1>Pedido creado</h1>";
        echo "<p>Paciente: {$paciente}</p>";       // ❌ ademas, sin escapar: XSS
        echo "<p>Total: $ {$total}</p>";
    }

    /*
     * ✅ FORMA CORRECTA:
     *
     * public function create(): void
     * {
     *     $order = new Order(
     *         (int) $_POST['id'],
     *         (string) $_POST['paciente'],
     *         (float) $_POST['monto'],
     *         (string) $_POST['tipo']
     *     );
     *
     *     $this->facade->createOrder($order);          // toda la logica, una linea
     *
     *     require __DIR__ . '/../../views/orders.php'; // la vista solo presenta
     * }
     */

    /**
     * ❌ METODO MAL APLICADO: index()
     *    Delega en la vista la consulta a la base de datos (ver views/orders.php).
     */
    public function index(): void
    {
        require __DIR__ . '/../../views/orders.php';
    }

    /**
     * ❌ METODO MAL APLICADO: report()
     *    Usa banderas booleanas ilegibles (ver ReportGenerator).
     */
    public function report(): void
    {
        $generador = new ReportGenerator();
        echo $generador->generate('Pedidos del dia', true, true, true);
    }
}

/*
 * ----------------------------------------------------------------------------
 * EJERCICIO 7 (TP): dejar create() con un maximo de 5 lineas ejecutables,
 * sin SQL, sin reglas de negocio y sin echo.
 *
 * PREGUNTA PARA EL PR: cuales de los 5 problemas de este archivo son deuda
 * de DISEÑO (Unidad 2) y cuales son deuda de PROCESO (Unidad 1)?
 * ----------------------------------------------------------------------------
 */
