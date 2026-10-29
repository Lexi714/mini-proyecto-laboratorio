# laboratorio-pedidos

Sistema de gestión de pedidos de un laboratorio de análisis clínicos.
**PHP vanilla, sin frameworks y sin Composer.** Metodología de Sistemas II — UTN FRRe (sede Formosa).

El proyecto partió con deuda técnica sembrada a propósito y fue refactorizado
aplicando patrones de diseño (ver [Estado del refactor](#estado-del-refactor)).
El detalle de cada deuda y de su costo está en [`docs/DEUDA-TECNICA.md`](docs/DEUDA-TECNICA.md).

---

## Requisitos

- **PHP 8.1 o superior** (se usan propiedades `readonly`, `match` y promoción de constructor).
- Un servidor web con PHP **o** el servidor embebido de PHP. MySQL **no** hace falta.

## Cómo levantarlo

Elegir **una** de las tres opciones.

### Opción A — Laragon

1. Copiar la carpeta del proyecto en `C:\laragon\www\` (queda `C:\laragon\www\mini-proyecto-laboratorio`).
2. Abrir Laragon y presionar **Start All**.
3. Abrir: `http://localhost/mini-proyecto-laboratorio/public/index.php`

### Opción B — XAMPP

1. Copiar la carpeta del proyecto en `C:\xampp\htdocs\`.
2. Iniciar **Apache** desde el panel de XAMPP.
3. Abrir: `http://localhost/mini-proyecto-laboratorio/public/index.php`

### Opción C — Servidor embebido de PHP (sin instalar nada más)

Desde la carpeta del proyecto:

```
php -S localhost:8000 -t public
```

Abrir: `http://localhost:8000/index.php`

## Configuración (opcional)

El proyecto arranca sin configurar nada. Para usar valores propios de base de datos:

1. Copiar `config/database.example.php` como `config/database.php`.
2. Completar los valores.

`config/database.php` está en `.gitignore`: **nunca se versiona**. Si no existe,
se usa la plantilla `config/database.example.php`.

La persistencia está simulada en memoria, por eso no se necesita MySQL.

## Acciones disponibles

Se eligen con el parámetro `accion` (si no se envía, es `crear`).

| URL | Qué hace | Resultado esperado |
|---|---|---|
| `public/index.php?accion=crear` | Crea un pedido de ejemplo | Aviso `[EMAIL] ...` y la página **Pedido creado**, con total `10500` |
| `public/index.php?accion=listar` | Lista pedidos | Tabla con 2 pedidos (totales `10500` y `22000`) |
| `public/index.php?accion=reporte` | Genera el reporte del día | `Reporte: Pedidos del dia + firma digital + PDF + marca de agua` |
| cualquier otra | — | `Accion no encontrada` |

`crear` acepta parámetros opcionales: `id`, `paciente`, `monto` y `tipo`
(`particular`, `obra_social` o `jubilado`). Ejemplo:

```
public/index.php?accion=crear&paciente=Ana+Gomez&tipo=jubilado&monto=2000
```

Si falta el paciente o el monto es menor o igual a 0, se muestra **Pedido no creado** con el motivo.

---

## Estructura

```
public/index.php      Punto de entrada: autoload, configuración, rutas y armado de dependencias
config/               Plantilla de configuración (database.example.php)
src/Controllers/      OrderController (MVC): recibe la entrada y elige la vista
src/Services/         OrderFacade y los servicios que orquesta (validación, precio, aviso, reporte)
src/Models/           Order: entidad inmutable y validada
src/Repositories/     OrderRepository: persistencia (simulada en memoria)
src/Pricing/          PricingStrategy y una estrategia por tipo de paciente
src/Notifications/    Notification y NotificationFactory (email, SMS)
src/Events/           OrderSubject y observadores
src/Legacy/           Sistema heredado de terceros y su adaptador
src/Reports/          Report y decoradores
src/Database/         Connection (Singleton)
views/                Vistas: solo muestran datos, con la salida escapada
docs/                 Consigna, mapa de deudas y deuda técnica
```

**Convención de autoload:** cada clase vive en un archivo con su mismo nombre,
dentro de una subcarpeta de `src/`. Una clase nueva se carga sola, sin editar `index.php`.

## Cómo agregar un tipo de paciente nuevo

Ejemplo: `prepaga` con 20 % de descuento. Son **2 archivos**:

1. Crear `src/Pricing/PrepaidStrategy.php` con una clase que implemente `PricingStrategy`.
2. Sumar `'prepaga' => new PrepaidStrategy(),` al `match` de `src/Services/OrderPricingService.php`.

---

## Estado del refactor

| Archivo original | Patrón aplicado | Rama | PR |
|---|---|---|---|
| `src/Pricing/PriceCalculator.php` | Strategy | `feat/patron-strategy` | #1 |
| `src/Models/Order.php` | Repository (+ Strategy) | `feat/patron-repository` | #2 |
| `src/Database/Connection.php` | Singleton | `feat/patron-singleton` | #4 |
| `src/Notifications/NotificationSender.php` | Factory | `feat/patron-factory` | #5 |
| `src/Legacy/LegacyNotifier.php` | Adapter | `feat/patron-adapter` | #6 |
| `src/Reports/ReportGenerator.php` | Decorator | `feat/patron-decorator` | #7 |
| `src/Events/OrderEvents.php` | Observer | `feat/patron-observer` | incluido en #7 |
| `public/index.php` | Autoload + config externa + tabla de rutas | `feat/patron-autoload-rutas` | #9 |
| `src/Services/OrderService.php` | Facade | `feat/patron-facade` | #10 |
| `src/Controllers/OrderController.php` y `views/orders.php` | MVC + SRP | `feat/patron-mvc` | #11 |

## La medida de la deuda

El descuento de obra social (`0.7`) estaba escrito en **5 archivos**
(`Order.php`, `PriceCalculator.php` dos veces, `OrderService.php`,
`OrderController.php` y `views/orders.php`). Hoy vive en **un solo lugar**:
`src/Pricing/InsuranceStrategy.php` (como descuento de `0.30`).

Agregar un tipo de paciente pasó de tocar 5 archivos a tocar 2.

---

## Flujo de trabajo del equipo

- Nadie escribe directamente en `main`: una rama por patrón y un Pull Request por rama.
- Cada PR responde: qué deuda se encontró (con la línea exacta), qué patrón se aplicó y por qué ese, y qué consecuencia negativa tiene.
- Cada PR lo revisa otra persona, nunca su autor.
- Commits con convención: `feat:`, `fix:`, `refactor:`, `docs:`.
- El código viejo se borra: conservarlo "por las dudas" es deuda nueva.
