# Mapa de deudas — laboratorio-pedidos

Planilla de trabajo para la clase práctica. Cada grupo completa **una fila**
durante la ronda de refactor y la usa como descripción de su Pull Request.

| Archivo | Síntoma sembrado | Principio violado | Patrón que lo repara | Grupo | Rama | PR |
|---|---|---|---|---|---|---|
| `public/index.php` | Requires manuales, credenciales, ruteo con `if` | OCP + configuración en código | Autoload + tabla de rutas | Persona C | `feat/patron-autoload-rutas` | #9 |
| `src/Database/Connection.php` | Conexión nueva por consulta, excepción silenciada | Gestión de recursos | Singleton | Persona A | `feat/patron-singleton` | #4 |
| `src/Models/Order.php` | El modelo persiste y calcula precios | SRP | Repository + Strategy | Persona A | `feat/patron-repository` | #2 |
| `src/Pricing/PriceCalculator.php` | `switch` con todos los algoritmos, duplicado | OCP + DRY | Strategy | Persona A | `feat/patron-strategy` | #1 |
| `src/Notifications/NotificationSender.php` | `if` por tipo repetido en 3 archivos | OCP + DIP | Factory | Persona B | `feat/patron-factory` | #5 |
| `src/Legacy/LegacyNotifier.php` | Clase de terceros modificada + copia y pega | DRY + límites del sistema | Adapter | Persona B | `feat/patron-adapter` | #6 |
| `src/Reports/ReportGenerator.php` | Banderas booleanas (`boolean trap`) | OCP | Decorator | Persona B | `feat/patron-decorator` | #7 |
| `src/Events/OrderEvents.php` | Avisos encadenados a clases concretas | DIP | Observer | Persona B | `feat/patron-observer` | incluido en #7 |
| `src/Services/OrderService.php` | Método que hace de todo | SRP | Facade | Persona C | `feat/patron-facade` | #10 |
| `src/Controllers/OrderController.php` | SQL + negocio + HTML juntos | SRP + MVC | MVC | Persona C | `feat/patron-mvc` | #11 |
| `views/orders.php` | Consulta, calcula y no escapa | MVC + seguridad | MVC | Persona C | `feat/patron-mvc` | #11 |

## Referencias

- **SRP** — una clase, un motivo de cambio.
- **OCP** — abierto a extensión, cerrado a modificación.
- **DIP** — depender de abstracciones, no de implementaciones concretas.
- **DRY** — el mismo conocimiento no se escribe dos veces.

## Métrica del proyecto

El descuento de obra social (`0.7`) aparecía en **5 archivos**.
Después del refactor vive en **1 solo lugar**: `src/Pricing/InsuranceStrategy.php`
(como descuento de `0.30`). Agregar un tipo de paciente pasó de tocar 5 archivos a tocar 2.
