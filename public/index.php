<?php
/**
 * ============================================================================
 *  laboratorio-pedidos — PUNTO DE ENTRADA
 *  Metodología de Sistemas II — UTN FRRe (sede Formosa)
 * ============================================================================
 *
 *  1. Autoload: las clases se cargan solas. Convencion del proyecto: el archivo
 *     se llama igual que la clase y vive en una subcarpeta de src/.
 *     Agregar una clase nueva NO obliga a editar este archivo.
 *  2. Configuracion: se lee de config/database.php (ignorado por git). Si no
 *     existe, se usa la plantilla config/database.example.php para que el
 *     proyecto arranque sin configurar nada.
 *  3. Ruteo: tabla accion => callable. Agregar una pantalla es agregar una
 *     entrada al mapa, no modificar un bloque de if.
 * ============================================================================
 */

spl_autoload_register(function (string $class): void {
    if (str_contains($class, '\\')) {
        return;
    }

    foreach (glob(__DIR__ . '/../src/*/' . $class . '.php') as $file) {
        require_once $file;
        return;
    }
});

$archivoConfig = is_file(__DIR__ . '/../config/database.php')
    ? __DIR__ . '/../config/database.php'
    : __DIR__ . '/../config/database.example.php';

$config = require $archivoConfig;

define('DB_HOST', $config['host']);
define('DB_NAME', $config['name']);
define('DB_USER', $config['user']);
define('DB_PASS', $config['pass']);

// Raiz de composicion: aca se arman las dependencias. OrderSubject va sin
// observadores suscritos, igual que el flujo web anterior.
$controller = new OrderController(
    new OrderFacade(
        new OrderValidator(),
        new OrderPricingService(),
        new OrderRepository(Connection::obtener()),
        new OrderNotifier(),
        new OrderSubject()
    ),
    new ReportService()
);

$rutas = [
    'crear'   => fn() => $controller->create(),
    'listar'  => fn() => $controller->index(),
    'reporte' => fn() => $controller->report(),
];

$accion = $_GET['accion'] ?? 'crear';
$ruta   = is_string($accion) ? ($rutas[$accion] ?? null) : null;

if ($ruta === null) {
    echo 'Accion no encontrada';
} else {
    $ruta();
}
