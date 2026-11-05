# Casos de prueba — laboratorio-pedidos

Documento de casos de prueba del TP de **Metodología de Sistemas II** (IE3, segundo parcial). Cubre el código refactorizado en el TP N.º 2.1 (patrones de diseño) y se ejecuta de forma automática con **PHPUnit**.

## 1. Alcance y enfoque

- **Qué se prueba:** el comportamiento de cada clase refactorizada (estrategias de precio, validación, modelo, repositorio, conexión, notificaciones, adaptador, observadores, decoradores, fachada, controlador y punto de entrada).
- **Qué no se prueba:** conexión real a una base de datos (el proyecto usa una conexión en memoria) ni el diseño visual de las vistas.
- **Niveles de prueba (pirámide de testing):** muchas pruebas *unitarias* (rápidas, una clase por vez, en `tests/Unit/`) y pocas pruebas de *integración* (varias clases juntas y el punto de entrada, en `tests/Integration/`).
- **Criterio de cobertura de casos:** cada funcionalidad tiene al menos un caso **positivo** (camino feliz) y al menos uno **negativo** (datos inválidos, errores, valores no permitidos), más valores límite cuando corresponde (monto 0, monto mínimo, etc.).
- **Testing temprano y repetible:** las mismas pruebas corren en cada `push` y en cada Pull Request mediante GitHub Actions (`.github/workflows/ci.yml`).

## 2. Ambiente de prueba

| Elemento | Valor |
|---|---|
| Lenguaje | PHP 8.1 o superior (el CI prueba 8.1, 8.2 y 8.3) |
| Framework de pruebas | PHPUnit 10.5 |
| Gestor de dependencias | Composer |
| Datos de prueba | Valores fijos escritos en cada prueba; el repositorio devuelve pedidos de ejemplo (id 1 y 2) y la conexión guarda las consultas en memoria |
| Base de datos | No se usa una real |

## 3. Cómo ejecutar las pruebas

```bash
composer install        # instala PHPUnit y las herramientas de lint
composer test           # corre todas las pruebas (equivale a vendor/bin/phpunit)
composer lint           # revisa sintaxis y estilo (PSR-12)
vendor/bin/phpunit --testsuite unit          # solo las unitarias
vendor/bin/phpunit --testsuite integration   # solo las de integración
```

## 4. Convención de identificadores

`CP-<ÁREA>-<NN>`: **CP** = caso de prueba; el área identifica la funcionalidad y `NN` es el número dentro del área.

| Área | Funcionalidad | Patrón / tema |
|---|---|---|
| `PRE` | Cálculo de precios | Strategy + PriceCalculator |
| `SRV` | Selección del tipo de paciente | Strategy elegida en `OrderPricingService` |
| `VAL` | Validación de pedidos | Validación separada (SRP) |
| `ORD` | Modelo de pedido | Modelo `Order` |
| `CON` | Conexión a la base de datos | Singleton |
| `REP` | Repositorio de pedidos | Repository |
| `NOT` | Notificaciones | Factory (+ Email y SMS) |
| `ADA` | Integración con el sistema viejo | Adapter |
| `OBS` | Eventos de pedido | Observer |
| `DEC` | Reportes | Decorator |
| `FAC` | Creación de pedidos | Facade |
| `CTL` | Controlador y vistas | MVC |
| `RUT` | Punto de entrada, autoload y rutas | Autoload + configuración + tabla de rutas |

## 5. Resumen

- **Casos documentados:** 81 (50 positivos y 31 negativos).
- **Funcionalidades:** 13, todas con al menos un caso positivo y uno negativo.
- Cada caso está automatizado: la sección 7 indica en qué archivo y método de `tests/` está.

## 6. Casos de prueba

Columnas: **ID**, **Descripción**, **Precondiciones**, **Datos de entrada**, **Pasos**, **Resultado esperado** y **Tipo** (positivo o negativo).

### 6.1 Cálculo de precios — Strategy + PriceCalculator

Clases: `PrivatePatientStrategy`, `InsuranceStrategy`, `RetiredStrategy`, `PriceCalculator`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-PRE-01** | Paciente particular: no se aplica descuento | Ninguna | Monto 1000 | 1. Crear `PrivatePatientStrategy`. 2. Llamar `calculate(1000)`. | Devuelve 1000 | Positivo |
| **CP-PRE-02** | Obra social: se aplica 30% de descuento | Ninguna | Monto 1000 | 1. Crear `InsuranceStrategy` (descuento por defecto). 2. Llamar `calculate(1000)`. | Devuelve 700 | Positivo |
| **CP-PRE-03** | Jubilado: se aplica 50% de descuento | Ninguna | Monto 1000 | 1. Crear `RetiredStrategy`. 2. Llamar `calculate(1000)`. | Devuelve 500 | Positivo |
| **CP-PRE-04** | El porcentaje de descuento se puede configurar | Ninguna | Descuento 0,10; monto 1000 | 1. Crear `InsuranceStrategy(0.10)`. 2. Llamar `calculate(1000)`. | Devuelve 900 | Positivo |
| **CP-PRE-05** | Monto cero (valor límite) | Ninguna | Monto 0 | 1. Llamar `calculate(0)` en las tres estrategias. | Las tres devuelven 0 | Positivo |
| **CP-PRE-06** | El calculador delega en la estrategia elegida | Ninguna | Estrategia jubilado; monto 1000 | 1. Crear `PriceCalculator(new RetiredStrategy())`. 2. Llamar `calculate(1000)`. | Devuelve 500 | Positivo |
| **CP-PRE-07** | El IVA (21%) se agrega después del descuento | Ninguna | Estrategia obra social; monto 1000 | 1. Crear `PriceCalculator(new InsuranceStrategy())`. 2. Llamar `calculateWithVat(1000)`. | Devuelve 847 (700 × 1,21) | Positivo |
| **CP-PRE-08** | El calculador acepta cualquier implementación de `PricingStrategy` (OCP) | Ninguna | Estrategia anónima que divide por 2; monto 100 | 1. Crear una clase anónima que implemente `PricingStrategy`. 2. Pasarla a `PriceCalculator`. 3. Llamar `calculate(100)`. | Devuelve 50, sin modificar `PriceCalculator` | Positivo |
| **CP-PRE-09** | El calculador rechaza algo que no es una estrategia de precio | Ninguna | Un objeto `stdClass` | 1. Crear `new PriceCalculator(new stdClass())`. | Lanza `TypeError` (el constructor exige `PricingStrategy`) | Negativo |

### 6.2 Selección del tipo de paciente — Strategy elegida en `OrderPricingService`

Clase: `OrderPricingService`. Elige la estrategia según el tipo de paciente.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-SRV-01** | Tipo `obra_social` usa el descuento de obra social | Ninguna | Monto 1000; tipo `obra_social` | 1. Crear `OrderPricingService`. 2. Llamar `totalFor(1000, 'obra_social')`. | Devuelve 700 | Positivo |
| **CP-SRV-02** | Tipo `jubilado` usa el descuento de jubilado | Ninguna | Monto 1000; tipo `jubilado` | 1. Llamar `totalFor(1000, 'jubilado')`. | Devuelve 500 | Positivo |
| **CP-SRV-03** | Tipo `particular` no tiene descuento | Ninguna | Monto 1000; tipo `particular` | 1. Llamar `totalFor(1000, 'particular')`. | Devuelve 1000 | Positivo |
| **CP-SRV-04** | Tipo inexistente se trata como particular (sin descuento) | Ninguna | Monto 1000; tipo `vip` | 1. Llamar `totalFor(1000, 'vip')`. | Devuelve 1000; no lanza excepción | Negativo |
| **CP-SRV-05** | Tipo vacío se trata como particular | Ninguna | Monto 1000; tipo `''` | 1. Llamar `totalFor(1000, '')`. | Devuelve 1000 | Negativo |
| **CP-SRV-06** | El tipo distingue mayúsculas: `OBRA_SOCIAL` no es `obra_social` | Ninguna | Monto 1000; tipo `OBRA_SOCIAL` | 1. Llamar `totalFor(1000, 'OBRA_SOCIAL')`. | Devuelve 1000 (no aplica descuento) | Negativo |

### 6.3 Validación de pedidos — Validación separada (SRP)

Clase: `OrderValidator`. Lanza `DomainException` ante datos inválidos.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-VAL-01** | Pedido válido | Ninguna | Paciente `Juan Perez`; monto 15000 | 1. Llamar `validate('Juan Perez', 15000)`. | No lanza excepción | Positivo |
| **CP-VAL-02** | Monto mínimo positivo (valor límite) | Ninguna | Paciente `Ana`; monto 0,01 | 1. Llamar `validate('Ana', 0.01)`. | No lanza excepción | Positivo |
| **CP-VAL-03** | Paciente vacío | Ninguna | Paciente `''`; monto 100 | 1. Llamar `validate('', 100)`. | Lanza `DomainException` con mensaje `Falta el paciente` | Negativo |
| **CP-VAL-04** | Paciente solo con espacios | Ninguna | Paciente `'   '`; monto 100 | 1. Llamar `validate('   ', 100)`. | Lanza `DomainException` con mensaje `Falta el paciente` | Negativo |
| **CP-VAL-05** | Monto cero | Ninguna | Paciente `Juan Perez`; monto 0 | 1. Llamar `validate('Juan Perez', 0)`. | Lanza `DomainException` con mensaje `Monto invalido` | Negativo |
| **CP-VAL-06** | Monto negativo | Ninguna | Paciente `Juan Perez`; monto -50 | 1. Llamar `validate('Juan Perez', -50)`. | Lanza `DomainException` con mensaje `Monto invalido` | Negativo |

### 6.4 Modelo de pedido — Modelo `Order`

Clase: `Order`. Objeto inmutable (propiedades `readonly`) que valida sus datos.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-ORD-01** | El pedido guarda los datos recibidos | Ninguna | Id 7; paciente `Juan Perez`; monto 700; tipo `obra_social` | 1. Crear `new Order(7, 'Juan Perez', 700, 'obra_social')`. 2. Leer sus propiedades. | Cada propiedad devuelve el valor recibido | Positivo |
| **CP-ORD-02** | Importe cero es válido (valor límite) | Ninguna | Monto 0 | 1. Crear `new Order(1, 'Ana', 0, 'particular')`. | Se crea con monto 0 | Positivo |
| **CP-ORD-03** | Importe negativo | Ninguna | Monto -1 | 1. Crear `new Order(1, 'Ana', -1, 'particular')`. | Lanza `InvalidArgumentException`: `El importe no puede ser negativo` | Negativo |
| **CP-ORD-04** | Paciente vacío | Ninguna | Paciente `'  '` | 1. Crear `new Order(1, '  ', 100, 'particular')`. | Lanza `InvalidArgumentException`: `El paciente es obligatorio` | Negativo |
| **CP-ORD-05** | El pedido es inmutable | Pedido ya creado | Nuevo monto 1 | 1. Crear un pedido. 2. Intentar asignar `$order->amount = 1`. | Lanza `Error` (propiedad `readonly`) | Negativo |

### 6.5 Conexión a la base de datos — Singleton

Clase: `Connection`. Una única instancia compartida; guarda las consultas en memoria.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-CON-01** | Siempre se obtiene la misma instancia | Ninguna | — | 1. Llamar `Connection::obtener()` dos veces. 2. Comparar con `assertSame`. | Ambas llamadas devuelven el mismo objeto | Positivo |
| **CP-CON-02** | `ejecutar` registra la consulta | Ninguna | SQL `SELECT 1` | 1. Contar las consultas. 2. Llamar `ejecutar('SELECT 1')`. 3. Leer `consultas()`. | Hay una consulta más y la última es `SELECT 1` | Positivo |
| **CP-CON-03** | No se puede crear una segunda conexión con `new` | Ninguna | — | 1. Ejecutar `new Connection()` desde fuera de la clase. | Lanza `Error` (constructor privado) | Negativo |
| **CP-CON-04** | No se puede clonar la conexión | Ninguna | — | 1. Ejecutar `clone Connection::obtener()`. | Lanza `Error` (`__clone` privado) | Negativo |

### 6.6 Repositorio de pedidos — Repository

Clase: `OrderRepository`. Único lugar que construye SQL; recibe la `Connection` por constructor.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-REP-01** | Guardar envía un INSERT a la conexión | Conexión disponible | Pedido id 5, `Juan Perez`, monto 700 | 1. Crear `OrderRepository`. 2. Llamar `save()`. 3. Leer la última consulta. | `INSERT INTO orders (id, patient, amount) VALUES (5, 'Juan Perez', 700)` | Positivo |
| **CP-REP-02** | Una comilla simple en el nombre no rompe el SQL | Conexión disponible | Paciente `O'Brien` | 1. Llamar `save()` con ese pedido. 2. Leer la última consulta. | La consulta contiene `'O''Brien'` (comilla duplicada) | Negativo |
| **CP-REP-03** | `findAll` devuelve los pedidos de ejemplo | Ninguna | — | 1. Llamar `findAll()`. | 2 objetos `Order`; el primero es `Juan Perez`; el segundo tiene monto 22000 | Positivo |
| **CP-REP-04** | Leer no escribe en la base | Conexión disponible | — | 1. Contar consultas. 2. Llamar `findAll()`. 3. Volver a contar. | La cantidad de consultas no cambia | Negativo |

### 6.7 Notificaciones — Factory (+ Email y SMS)

Clases: `NotificationFactory`, `EmailNotification`, `SmsNotification`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-NOT-01** | La fábrica crea una notificación de email | Ninguna | Tipo `email`; destino `paciente@mail.com` | 1. Llamar `NotificationFactory::create('email', ...)`. | Devuelve una `EmailNotification` | Positivo |
| **CP-NOT-02** | La fábrica crea una notificación de SMS | Ninguna | Tipo `sms`; destino `3704000000` | 1. Llamar `create('sms', ...)`. | Devuelve una `SmsNotification` | Positivo |
| **CP-NOT-03** | Formato del email | Ninguna | Destino `paciente@mail.com`; asunto `Hola`; mensaje `Pedido 1` | 1. Crear el email con la fábrica. 2. Llamar `send('Pedido 1')`. | Imprime `[EMAIL] para paciente@mail.com \| Hola: Pedido 1<br>` | Positivo |
| **CP-NOT-04** | Formato del SMS | Ninguna | Destino `3704000000`; mensaje `Pedido 1 creado` | 1. Crear el SMS con la fábrica. 2. Llamar `send()`. | Imprime `[SMS] a 3704000000: Pedido 1 creado<br>` | Positivo |
| **CP-NOT-05** | Asunto por defecto del email | Ninguna | Sin asunto | 1. Llamar `create('email', 'a@b.com')`. 2. Enviar `x`. | Imprime `[EMAIL] para a@b.com \| Pedido creado: x<br>` | Positivo |
| **CP-NOT-06** | Tipo no soportado | Ninguna | Tipo `whatsapp` | 1. Llamar `create('whatsapp', '3704000000')`. | Lanza `InvalidArgumentException`: `Tipo de notificacion no soportado: whatsapp` | Negativo |
| **CP-NOT-07** | Tipo vacío | Ninguna | Tipo `''` | 1. Llamar `create('', 'x')`. | Lanza `InvalidArgumentException` | Negativo |
| **CP-NOT-08** | El tipo distingue mayúsculas | Ninguna | Tipo `EMAIL` | 1. Llamar `create('EMAIL', 'a@b.com')`. | Lanza `InvalidArgumentException` | Negativo |

### 6.8 Integración con el sistema viejo — Adapter

Clases: `LegacyNotifier` (sistema viejo, no modificable) y `LegacyNotifierAdapter`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-ADA-01** | El adaptador cumple la interfaz `Notification` | Ninguna | — | 1. Crear `LegacyNotifierAdapter(new LegacyNotifier())`. | Es una instancia de `Notification` | Positivo |
| **CP-ADA-02** | `send()` se traduce en `sendMessage()` del sistema viejo | Doble de prueba (mock) de `LegacyNotifier` | Mensaje `hola` | 1. Crear un mock de `LegacyNotifier` que espere `sendMessage('hola')` una vez. 2. Llamar `send('hola')` en el adaptador. | El mock recibe exactamente una llamada con `hola` | Positivo |
| **CP-ADA-03** | La salida final es la del sistema viejo | Ninguna | Mensaje `hola` | 1. Crear el adaptador con un `LegacyNotifier` real. 2. Llamar `send('hola')`. | Imprime `[LEGACY] hola<br>` | Positivo |
| **CP-ADA-04** | Sin adaptador, el sistema viejo no sirve como `Notification` | Ninguna | — | 1. Crear `new LegacyNotifier()`. 2. Comprobar su tipo. | No es instancia de `Notification` (por eso hace falta el adaptador) | Negativo |

### 6.9 Eventos de pedido — Observer

Clases: `OrderSubject`, `EmailObserver`, `SmsObserver`, `DashboardObserver`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-OBS-01** | Se notifica a todos los suscriptores, en orden | Dos observadores espía suscriptos | Pedido id 9 | 1. Suscribir `a` y `b`. 2. Llamar `notify(pedido)`. | Registro `['a:9', 'b:9']` | Positivo |
| **CP-OBS-02** | Notificar sin suscriptores no hace nada | Sin suscriptores | Pedido id 1 | 1. Crear `OrderSubject` vacío. 2. Llamar `notify()`. | No lanza error ni imprime nada | Negativo |
| **CP-OBS-03** | Cada notificación llega una sola vez por suscriptor | Un observador espía suscripto | Pedidos 1 y 2 | 1. Llamar `notify()` dos veces con pedidos distintos. | Registro `['a:1', 'a:2']` | Positivo |
| **CP-OBS-04** | `EmailObserver` envía un email | Ninguna | Pedido id 7 | 1. Llamar `update(pedido)`. | Imprime `[EMAIL] para paciente@mail.com \| Pedido creado: Pedido 7<br>` | Positivo |
| **CP-OBS-05** | `SmsObserver` envía un SMS | Ninguna | Pedido id 7 | 1. Llamar `update(pedido)`. | Imprime `[SMS] a 3704000000: Pedido 7 creado<br>` | Positivo |
| **CP-OBS-06** | `DashboardObserver` actualiza el tablero | Ninguna | Pedido id 7 | 1. Llamar `update(pedido)`. | Imprime `[DASHBOARD] actualizado para el pedido 7<br>` | Positivo |
| **CP-OBS-07** | Los tres observadores reales se ejecutan en el orden de suscripción | Email, SMS y Dashboard suscriptos | Pedido id 3 | 1. Suscribir los tres. 2. Llamar `notify(pedido)`. | Se imprimen, en orden, las líneas de email, SMS y dashboard del pedido 3 | Positivo |

### 6.10 Reportes — Decorator

Clases: `BasicReport`, `DigitalSignatureDecorator`, `PdfReportDecorator`, `WatermarkDecorator`, `ReportService`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-DEC-01** | El reporte básico muestra su contenido | Ninguna | Contenido `Pedidos del dia` | 1. Crear `BasicReport`. 2. Llamar `generate()`. | `Reporte: Pedidos del dia` | Positivo |
| **CP-DEC-02** | Cada decorador agrega su parte | Ninguna | Contenido `x` | 1. Envolver un `BasicReport('x')` con cada decorador por separado. 2. Llamar `generate()`. | `... + firma digital`, `... + PDF` y `... + marca de agua` respectivamente | Positivo |
| **CP-DEC-03** | Los decoradores se pueden apilar | Ninguna | Contenido `x` | 1. Crear `Pdf(Firma(Basic('x')))`. 2. Llamar `generate()`. | `Reporte: x + firma digital + PDF` | Positivo |
| **CP-DEC-04** | El orden de los decoradores cambia el resultado | Ninguna | Contenido `x` | 1. Armar `Pdf(Marca(Basic))` y `Marca(Pdf(Basic))`. 2. Comparar las salidas. | Los dos textos son distintos | Negativo |
| **CP-DEC-05** | Todo decorador es también un `Report` | Ninguna | — | 1. Crear cada decorador. 2. Comprobar su tipo. | Los tres son instancias de `Report` | Positivo |
| **CP-DEC-06** | El reporte diario arma la cadena completa | Ninguna | — | 1. Crear `ReportService`. 2. Llamar `dailyReport()`. | `Reporte: Pedidos del dia + firma digital + PDF + marca de agua` | Positivo |

### 6.11 Creación de pedidos — Facade

Clase: `OrderFacade` (usa validador, precios, repositorio, notificador y observadores reales).

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-FAC-01** | Flujo completo con descuento de obra social | Fachada armada con las clases reales | Id 1; `Juan Perez`; monto 1000; `obra_social`; canal `email` | 1. Llamar `createOrder(...)`. | Devuelve un `Order` con monto 700 e imprime el email `Pedido 1 por $ 700` | Positivo |
| **CP-FAC-02** | Se guarda el pedido y se avisa a los observadores | Observador espía suscripto | Id 2; `Ana`; monto 500; `particular` | 1. Contar consultas. 2. Llamar `createOrder(...)`. | Se agrega 1 consulta y el observador recibe el pedido 2 | Positivo |
| **CP-FAC-03** | Se puede elegir SMS como canal | Fachada armada | Id 3; monto 1000; `jubilado`; canal `sms` | 1. Llamar `createOrder(...)`. | Imprime `[SMS] a 3704000000: Pedido 3 por $ 500<br>` | Positivo |
| **CP-FAC-04** | Paciente vacío: no se guarda ni se notifica | Observador espía suscripto | Paciente `''` | 1. Contar consultas. 2. Llamar `createOrder(...)`. | Lanza `DomainException` `Falta el paciente`; no hay consultas nuevas ni avisos | Negativo |
| **CP-FAC-05** | Monto inválido: no se guarda ni se notifica | Observador espía suscripto | Monto 0 | 1. Contar consultas. 2. Llamar `createOrder(...)`. | Lanza `DomainException` `Monto invalido`; no hay consultas nuevas ni avisos | Negativo |
| **CP-FAC-06** | Canal de notificación no soportado | Fachada armada | Canal `paloma` | 1. Llamar `createOrder(...)` con ese canal. | Lanza `InvalidArgumentException` | Negativo |
| **CP-FAC-07** | Listar devuelve los pedidos del repositorio | Fachada armada | — | 1. Llamar `listOrders()`. | Devuelve 2 pedidos | Positivo |

### 6.12 Controlador y vistas — MVC

Clase: `OrderController` con sus vistas (`views/`). Los datos llegan por `$_GET`.

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-CTL-01** | Crear con valores por defecto | `$_GET` vacío | — | 1. Llamar `create()` y capturar la salida. | Contiene `Pedido creado`, `Paciente: Juan Perez` y `Total: $ 10500` | Positivo |
| **CP-CTL-02** | Crear con datos recibidos (jubilado) | `$_GET` con datos | `paciente=Ana Gomez`, `monto=2000`, `tipo=jubilado` | 1. Cargar `$_GET`. 2. Llamar `create()`. | Contiene `Paciente: Ana Gomez` y `Total: $ 1000` | Positivo |
| **CP-CTL-03** | Paciente vacío muestra la vista de error | `$_GET['paciente']` vacío | `paciente=` | 1. Llamar `create()`. | Contiene `Pedido no creado` y `Falta el paciente`; no contiene `Pedido creado` | Negativo |
| **CP-CTL-04** | Monto cero muestra la vista de error | `$_GET['monto']` = 0 | `monto=0` | 1. Llamar `create()`. | Contiene `Monto invalido` | Negativo |
| **CP-CTL-05** | La salida se escapa (XSS) | `$_GET['paciente']` con HTML | `<script>alert(1)</script>` | 1. Llamar `create()`. | No contiene `<script>`; contiene `&lt;script&gt;` | Negativo |
| **CP-CTL-06** | Un parámetro con forma de arreglo se ignora | `$_GET['paciente']` es un arreglo | `paciente[]=x` | 1. Llamar `create()`. | Usa el valor por defecto `Juan Perez`, sin errores | Negativo |
| **CP-CTL-07** | Listar muestra la tabla de pedidos | Ninguna | — | 1. Llamar `index()`. | Contiene `<h1>Pedidos</h1>`, `Juan Perez` y `22000` | Positivo |
| **CP-CTL-08** | Reporte muestra la cadena de decoradores | Ninguna | — | 1. Llamar `report()`. | Contiene `Reporte: Pedidos del dia + firma digital + PDF + marca de agua` | Positivo |

### 6.13 Punto de entrada, autoload y rutas — Autoload + configuración + tabla de rutas

Archivo: `public/index.php`, ejecutado en un proceso PHP aparte (como lo hace el servidor).

| ID | Descripción | Precondiciones | Datos de entrada | Pasos | Resultado esperado | Tipo |
|---|---|---|---|---|---|---|
| **CP-RUT-01** | Ruta `crear` | Ninguna | `accion=crear` | 1. Ejecutar `public/index.php` con esa consulta. | La respuesta contiene `Pedido creado` (también prueba el autoload) | Positivo |
| **CP-RUT-02** | Ruta `listar` | Ninguna | `accion=listar` | 1. Ejecutar `public/index.php`. | La respuesta contiene `<h1>Pedidos</h1>` | Positivo |
| **CP-RUT-03** | Ruta `reporte` | Ninguna | `accion=reporte` | 1. Ejecutar `public/index.php`. | La respuesta contiene `marca de agua` | Positivo |
| **CP-RUT-04** | Sin acción se usa `crear` por defecto | Ninguna | Sin parámetros | 1. Ejecutar `public/index.php` sin consulta. | La respuesta contiene `Pedido creado` | Positivo |
| **CP-RUT-05** | Acción inexistente | Ninguna | `accion=borrar_todo` | 1. Ejecutar `public/index.php`. | Responde `Accion no encontrada`, sin `Fatal error` ni `Warning` | Negativo |
| **CP-RUT-06** | Acción con forma de arreglo | Ninguna | `accion[]=listar` | 1. Ejecutar `public/index.php`. | Responde `Accion no encontrada`, sin `Fatal error` ni `Warning` | Negativo |
| **CP-RUT-07** | La configuración versionada no trae credenciales | Archivo `config/database.example.php` | — | 1. Cargar el archivo. 2. Leer sus claves. | Tiene la clave `host` y la contraseña es una cadena vacía | Negativo |

## 7. Trazabilidad: caso de prueba → prueba automatizada

| ID | Archivo | Método |
|---|---|---|
| CP-PRE-01 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testCadaEstrategiaAplicaSuDescuento` |
| CP-PRE-02 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testCadaEstrategiaAplicaSuDescuento` |
| CP-PRE-03 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testCadaEstrategiaAplicaSuDescuento` |
| CP-PRE-04 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testElDescuentoSePuedeConfigurar` |
| CP-PRE-05 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testMontoCeroDaCeroEnTodasLasEstrategias` |
| CP-PRE-06 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testElCalculadorDelegaEnLaEstrategiaElegida` |
| CP-PRE-07 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testElCalculadorAgregaIvaDespuesDelDescuento` |
| CP-PRE-08 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testSeAceptaCualquierImplementacionDeLaInterfaz` |
| CP-PRE-09 | `tests/Unit/Pricing/PricingStrategyTest.php` | `testElCalculadorRechazaAlgoQueNoEsUnaEstrategia` |
| CP-SRV-01 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testElTotalDependeDelTipoDePaciente` |
| CP-SRV-02 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testElTotalDependeDelTipoDePaciente` |
| CP-SRV-03 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testElTotalDependeDelTipoDePaciente` |
| CP-SRV-04 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testUnTipoDesconocidoSeTrataComoParticularSinDescuento` |
| CP-SRV-05 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testUnTipoDesconocidoSeTrataComoParticularSinDescuento` |
| CP-SRV-06 | `tests/Unit/Services/OrderPricingServiceTest.php` | `testUnTipoDesconocidoSeTrataComoParticularSinDescuento` |
| CP-VAL-01 | `tests/Unit/Services/OrderValidatorTest.php` | `testAceptaUnPedidoValido` |
| CP-VAL-02 | `tests/Unit/Services/OrderValidatorTest.php` | `testAceptaUnMontoMinimoPositivo` |
| CP-VAL-03 | `tests/Unit/Services/OrderValidatorTest.php` | `testRechazaUnPacienteSinNombre` |
| CP-VAL-04 | `tests/Unit/Services/OrderValidatorTest.php` | `testRechazaUnPacienteSinNombre` |
| CP-VAL-05 | `tests/Unit/Services/OrderValidatorTest.php` | `testRechazaUnMontoNoPositivo` |
| CP-VAL-06 | `tests/Unit/Services/OrderValidatorTest.php` | `testRechazaUnMontoNoPositivo` |
| CP-ORD-01 | `tests/Unit/Models/OrderTest.php` | `testGuardaLosDatosRecibidos` |
| CP-ORD-02 | `tests/Unit/Models/OrderTest.php` | `testAceptaImporteCero` |
| CP-ORD-03 | `tests/Unit/Models/OrderTest.php` | `testRechazaImporteNegativo` |
| CP-ORD-04 | `tests/Unit/Models/OrderTest.php` | `testRechazaPacienteVacio` |
| CP-ORD-05 | `tests/Unit/Models/OrderTest.php` | `testElPedidoEsInmutable` |
| CP-CON-01 | `tests/Unit/Database/ConnectionTest.php` | `testObtenerDevuelveSiempreLaMismaInstancia` |
| CP-CON-02 | `tests/Unit/Database/ConnectionTest.php` | `testEjecutarRegistraLaConsulta` |
| CP-CON-03 | `tests/Unit/Database/ConnectionTest.php` | `testElConstructorEsPrivado` |
| CP-CON-04 | `tests/Unit/Database/ConnectionTest.php` | `testNoSePuedeClonar` |
| CP-REP-01 | `tests/Unit/Repositories/OrderRepositoryTest.php` | `testGuardarEnviaUnInsertAlaConexion` |
| CP-REP-02 | `tests/Unit/Repositories/OrderRepositoryTest.php` | `testGuardarEscapaLasComillasSimples` |
| CP-REP-03 | `tests/Unit/Repositories/OrderRepositoryTest.php` | `testBuscarTodosDevuelvePedidosDeEjemplo` |
| CP-REP-04 | `tests/Unit/Repositories/OrderRepositoryTest.php` | `testBuscarTodosNoRegistraConsultas` |
| CP-NOT-01 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testCreaUnaNotificacionDeEmail` |
| CP-NOT-02 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testCreaUnaNotificacionDeSms` |
| CP-NOT-03 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testElEmailSeEnviaConElFormatoEsperado` |
| CP-NOT-04 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testElSmsSeEnviaConElFormatoEsperado` |
| CP-NOT-05 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testElEmailUsaElAsuntoPorDefecto` |
| CP-NOT-06 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testUnTipoNoSoportadoLanzaExcepcion` |
| CP-NOT-07 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testUnTipoVacioLanzaExcepcion` |
| CP-NOT-08 | `tests/Unit/Notifications/NotificationFactoryTest.php` | `testElTipoDistingueMayusculas` |
| CP-ADA-01 | `tests/Unit/Legacy/LegacyNotifierAdapterTest.php` | `testElAdaptadorEsUnaNotification` |
| CP-ADA-02 | `tests/Unit/Legacy/LegacyNotifierAdapterTest.php` | `testSendLlamaASendMessageDelSistemaViejo` |
| CP-ADA-03 | `tests/Unit/Legacy/LegacyNotifierAdapterTest.php` | `testLaSalidaEsLaDelSistemaViejo` |
| CP-ADA-04 | `tests/Unit/Legacy/LegacyNotifierAdapterTest.php` | `testElSistemaViejoPorSiSoloNoEsUnaNotification` |
| CP-OBS-01 | `tests/Unit/Events/OrderSubjectTest.php` | `testNotificaATodosLosSuscriptoresEnOrden` |
| CP-OBS-02 | `tests/Unit/Events/OrderSubjectTest.php` | `testNotificarSinSuscriptoresNoHaceNada` |
| CP-OBS-03 | `tests/Unit/Events/OrderSubjectTest.php` | `testCadaNotificacionLlegaUnaSolaVezPorSuscriptor` |
| CP-OBS-04 | `tests/Unit/Events/OrderSubjectTest.php` | `testEmailObserverEnviaUnEmail` |
| CP-OBS-05 | `tests/Unit/Events/OrderSubjectTest.php` | `testSmsObserverEnviaUnSms` |
| CP-OBS-06 | `tests/Unit/Events/OrderSubjectTest.php` | `testDashboardObserverActualizaElTablero` |
| CP-OBS-07 | `tests/Unit/Events/OrderSubjectTest.php` | `testLosTresObservadoresRealesSeEjecutanEnElOrdenSuscripto` |
| CP-DEC-01 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testElReporteBasicoMuestraSuContenido` |
| CP-DEC-02 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testCadaDecoradorAgregaSuParte` |
| CP-DEC-03 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testLosDecoradoresSeApilan` |
| CP-DEC-04 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testElOrdenDeLosDecoradoresCambiaElResultado` |
| CP-DEC-05 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testLosDecoradoresCumplenLaInterfazReport` |
| CP-DEC-06 | `tests/Unit/Reports/ReportDecoratorTest.php` | `testElReporteDiarioTieneLaCadenaCompleta` |
| CP-FAC-01 | `tests/Unit/Services/OrderFacadeTest.php` | `testCreaUnPedidoAplicandoElDescuento` |
| CP-FAC-02 | `tests/Unit/Services/OrderFacadeTest.php` | `testGuardaElPedidoYAvisaALosObservadores` |
| CP-FAC-03 | `tests/Unit/Services/OrderFacadeTest.php` | `testPermiteNotificarPorSms` |
| CP-FAC-04 | `tests/Unit/Services/OrderFacadeTest.php` | `testConPacienteVacioNoGuardaNiNotifica` |
| CP-FAC-05 | `tests/Unit/Services/OrderFacadeTest.php` | `testConMontoInvalidoNoGuardaNiNotifica` |
| CP-FAC-06 | `tests/Unit/Services/OrderFacadeTest.php` | `testUnCanalNoSoportadoLanzaExcepcion` |
| CP-FAC-07 | `tests/Unit/Services/OrderFacadeTest.php` | `testListarDevuelveLosPedidosDelRepositorio` |
| CP-CTL-01 | `tests/Integration/OrderControllerTest.php` | `testCrearUsaLosValoresPorDefecto` |
| CP-CTL-02 | `tests/Integration/OrderControllerTest.php` | `testCrearUsaLosDatosRecibidos` |
| CP-CTL-03 | `tests/Integration/OrderControllerTest.php` | `testCrearConPacienteVacioMuestraElError` |
| CP-CTL-04 | `tests/Integration/OrderControllerTest.php` | `testCrearConMontoCeroMuestraElError` |
| CP-CTL-05 | `tests/Integration/OrderControllerTest.php` | `testLaSalidaSeEscapaContraXss` |
| CP-CTL-06 | `tests/Integration/OrderControllerTest.php` | `testUnParametroArregloUsaElValorPorDefecto` |
| CP-CTL-07 | `tests/Integration/OrderControllerTest.php` | `testListarMuestraLaTablaDePedidos` |
| CP-CTL-08 | `tests/Integration/OrderControllerTest.php` | `testReporteMuestraLaCadenaDeDecoradores` |
| CP-RUT-01 | `tests/Integration/FrontControllerTest.php` | `testLasRutasConocidasResponden` |
| CP-RUT-02 | `tests/Integration/FrontControllerTest.php` | `testLasRutasConocidasResponden` |
| CP-RUT-03 | `tests/Integration/FrontControllerTest.php` | `testLasRutasConocidasResponden` |
| CP-RUT-04 | `tests/Integration/FrontControllerTest.php` | `testLasRutasConocidasResponden` |
| CP-RUT-05 | `tests/Integration/FrontControllerTest.php` | `testUnaAccionInexistenteNoEncuentraRuta` |
| CP-RUT-06 | `tests/Integration/FrontControllerTest.php` | `testUnaAccionInexistenteNoEncuentraRuta` |
| CP-RUT-07 | `tests/Integration/FrontControllerTest.php` | `testLaConfiguracionVersionadaNoTieneCredenciales` |

## 8. Hallazgos y limitaciones conocidas

- **Notificación fallida después de guardar:** en `OrderFacade::createOrder()` el pedido se guarda *antes* de notificar. Si el canal es inválido (CP-FAC-06) la excepción se lanza cuando el pedido ya fue guardado y los observadores no llegan a avisarse. Es una mejora posible (validar el canal antes de guardar); hoy el caso solo comprueba que se lanza la excepción.
- **Tipos de paciente desconocidos** (CP-SRV-04 a CP-SRV-06) no fallan: se cobran como particular, sin descuento. Es el comportamiento heredado del código original; se documenta para que un cambio futuro sea consciente.
- **Conexión compartida:** `Connection` es un Singleton y guarda estado entre pruebas; por eso los casos que miran las consultas comparan la cantidad *antes y después* en lugar de asumir una lista vacía.
- **Sin pruebas de base de datos real ni de interfaz:** quedan fuera del alcance del proyecto.
