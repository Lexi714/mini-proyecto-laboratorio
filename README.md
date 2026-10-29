# laboratorio-pedidos

Proyecto de **Metodología de Sistemas II** — Tecnicatura Universitaria en Programación,
UTN FRRe (sede Formosa).

## Índice

1. [Qué es este proyecto](#1-qué-es-este-proyecto)
2. [Qué problemas había y cómo se solucionaron](#2-qué-problemas-había-y-cómo-se-solucionaron)
3. [Errores y limitaciones que siguen existiendo](#3-errores-y-limitaciones-que-siguen-existiendo)
4. [Tutorial paso a paso: clonar y hacer andar el proyecto](#4-tutorial-paso-a-paso-clonar-y-hacer-andar-el-proyecto)
5. [Problemas comunes al levantarlo](#5-problemas-comunes-al-levantarlo)
6. [Estructura del proyecto](#6-estructura-del-proyecto)
7. [Cómo agregar un tipo de paciente nuevo](#7-cómo-agregar-un-tipo-de-paciente-nuevo)
8. [Flujo de trabajo del equipo](#8-flujo-de-trabajo-del-equipo)

---

## 1. Qué es este proyecto

`laboratorio-pedidos` es un sistema, hecho en **PHP vanilla (sin frameworks y sin Composer)**,
para gestionar los **pedidos de un laboratorio de análisis clínicos**. Permite:

- **Crear un pedido** de un paciente, calculando el total según su tipo
  (`particular`, `obra_social` con 30 % de descuento o `jubilado` con 50 %).
  Al crearlo, se valida, se guarda y se avisa al paciente por email.
- **Listar** los pedidos.
- **Generar un reporte** del día con firma digital, PDF y marca de agua.

El proyecto **partió con deuda técnica sembrada a propósito**: el sistema funcionaba, pero
cada archivo tenía código difícil de cambiar (por ejemplo, el descuento de obra social estaba
escrito en cinco archivos distintos). El objetivo del trabajo **no fue que funcione mejor, sino
que pueda cambiarse sin miedo**: se refactorizó aplicando patrones de diseño, sin alterar lo
que el sistema hace.

Documentación relacionada:

| Archivo | Contenido |
|---|---|
| [`docs/CONSIGNA-TP.md`](docs/CONSIGNA-TP.md) | Consigna del trabajo práctico |
| [`docs/MAPA-DE-DEUDAS.md`](docs/MAPA-DE-DEUDAS.md) | Los 11 archivos con deuda, con su patrón, rama y PR |
| [`docs/DEUDA-TECNICA.md`](docs/DEUDA-TECNICA.md) | Cada deuda con su evidencia (archivo:línea) y el costo de la solución |

---

## 2. Qué problemas había y cómo se solucionaron

Cada fila es un archivo del mapa de deudas. Cada patrón se aplicó en **una rama propia con un
Pull Request** que explica la deuda, el patrón elegido y su consecuencia negativa.

| Problema original | Solución | Patrón | Rama · PR |
|---|---|---|---|
| `PriceCalculator`: un `switch` con todos los algoritmos de precio y el `0.7` repetido | Una clase por tipo de paciente detrás de la interfaz `PricingStrategy` | **Strategy** | `feat/patron-strategy` · #1 |
| `Order`: el modelo se guardaba solo (SQL armado a mano), se podía modificar y calculaba precios | `Order` inmutable y validado; la persistencia pasó a `OrderRepository` | **Repository** (+ Strategy) | `feat/patron-repository` · #2 |
| `Connection`: una conexión nueva en cada consulta y una excepción tragada en silencio | Una sola instancia (constructor privado) y los errores se propagan | **Singleton** | `feat/patron-singleton` · #4 |
| `NotificationSender`: el mismo `if` por tipo repetido en 3 archivos | Una única `NotificationFactory` que crea objetos `Notification` | **Factory** | `feat/patron-factory` · #5 |
| `LegacyNotifier`: clase de terceros modificada a mano y copiada | `LegacyNotifierAdapter`; la clase del proveedor quedó intacta | **Adapter** | `feat/patron-adapter` · #6 |
| `ReportGenerator`: banderas booleanas (`generate(true, false, true)`) | Decoradores encadenables sobre un `BasicReport` | **Decorator** | `feat/patron-decorator` · #7 |
| `OrderEvents`: avisos llamados uno por uno a clases concretas | `OrderSubject` con observadores suscriptos | **Observer** | `feat/patron-observer` · incluido en #7 |
| `index.php`: `require` manuales, credenciales en el código y ruteo con `if` | Autoload, configuración externa y tabla de rutas | **Autoload + config + rutas** | `feat/patron-autoload-rutas` · #9 |
| `OrderService`: un método que hacía todo (validar, calcular, guardar, avisar, imprimir) | `OrderFacade` que solo orquesta servicios con una responsabilidad cada uno | **Facade** | `feat/patron-facade` · #10 |
| `OrderController` y `views/orders.php`: SQL, reglas de negocio y HTML mezclados, salida sin escapar | Controlador delgado y vistas que solo muestran datos escapados | **MVC** + SRP | `feat/patron-mvc` · #11 |

**La medida de la mejora.** El descuento de obra social (`0.7`) estaba en **5 archivos**
(`Order.php`, `PriceCalculator.php` dos veces, `OrderService.php`, `OrderController.php` y
`views/orders.php`). Hoy vive en **un solo lugar**: `src/Pricing/InsuranceStrategy.php`.
Agregar un tipo de paciente pasó de **5 archivos a 2**.

**Cambios de comportamiento intencionales** (todo lo demás se mantiene igual):

- El email de la acción `crear` ahora dice `Pedido creado: Pedido 1 por $ 10500`
  (antes `Pedido del laboratorio: Pedido 1 creado`).
- Si falta el paciente o el monto es menor o igual a 0, se muestra un error en lugar de crear el pedido.
- Los jubilados ahora reciben su 50 % de descuento también al crear desde la web.
- Toda la salida se escapa: un nombre como `<script>` se muestra como texto y no se ejecuta.
- Un tipo de notificación desconocido lanza una excepción en lugar de ignorarse.

---

## 3. Errores y limitaciones que siguen existiendo

Ningún patrón es gratis. Esto es lo que **no** se resolvió o que el refactor dejó como costo:

**Del sistema**

- **La persistencia es simulada en memoria.** Nada se guarda entre una petición y la siguiente,
  y no hay base de datos real. (Está explícito en la consigna: no era parte de la deuda.)
- **Las notificaciones son simuladas**: se imprimen en pantalla, no se envía ningún email ni SMS.
  El destinatario (`paciente@mail.com`) está fijo en el controlador.
- **La inyección SQL está mitigada, no resuelta.** `OrderRepository` duplica las comillas simples,
  pero no usa consultas preparadas porque `Connection` recibe un `string` y no expone PDO.
- **Los datos del listado son de ejemplo** (`OrderRepository::findAll()` devuelve 2 pedidos fijos).
- **Sin pruebas automáticas.** El comportamiento se verificó a mano comparando la salida antes y después.
- Una acción inexistente responde `Accion no encontrada` con código HTTP 200 (no 404).

**Del diseño**

- **Observer sin usar en la web.** Los observadores existen, pero `index.php` crea el `OrderSubject`
  sin suscribir ninguno, para no cambiar lo que ve el usuario. Si un observador falla, los siguientes no se ejecutan.
- **El Adapter no está conectado** a `NotificationFactory`: nada en el sistema usa hoy el notificador heredado.
- **Agregar un tipo de paciente o de notificación todavía obliga a tocar un `match`**
  (`OrderPricingService`, `NotificationFactory`): el sistema no es 100 % abierto a extensión.
- **Singleton = estado global.** `Connection::obtener()` sigue llamándose desde `index.php`, y es difícil de reemplazar en pruebas.
- **El autoload usa `glob()`** por cada clase y no hay namespaces: dos clases con el mismo nombre
  en carpetas distintas de `src/` chocarían.
- **Facade y MVC suman archivos.** Entender "crear un pedido" ahora exige seguir controlador,
  fachada, servicios, repositorio y vista. La complejidad se movió; no desapareció.
- Si falta `config/database.php`, se usa la plantilla sin avisar, lo que podría ocultar un error de configuración con una base real.

El detalle de cada uno, con su archivo y línea, está en [`docs/DEUDA-TECNICA.md`](docs/DEUDA-TECNICA.md).

---

## 4. Tutorial paso a paso: clonar y hacer andar el proyecto

Esta guía es para Windows. **Seguí los pasos en orden y no te saltees ninguno**: cada uno
termina con una verificación para confirmar que salió bien antes de pasar al siguiente.

### Paso 1 — Instalar Git

1. Descargá Git desde <https://git-scm.com/download/win> e instalalo con las opciones por defecto.
2. Abrí una terminal (Símbolo del sistema o PowerShell) y ejecutá:

   ```
   git --version
   ```

   ✅ **Debe mostrar** algo como `git version 2.45.0.windows.1`. Si dice que no reconoce el comando,
   cerrá y volvé a abrir la terminal; si sigue igual, reinstalá Git.

### Paso 2 — Instalar Laragon (incluye Apache y PHP)

1. Descargá **Laragon Full** desde <https://laragon.org/download/> e instalalo con las opciones por defecto.
   Se instala en `C:\laragon`.
2. Abrí Laragon. Clic derecho en la ventana → **Terminal** (abre una terminal con PHP disponible).
3. Verificá la versión de PHP:

   ```
   php -v
   ```

   ✅ **Debe mostrar PHP 8.1 o superior** (por ejemplo `PHP 8.3.x`). Si es menor, en Laragon
   usá el menú de Laragon **PHP → Versión** y elegí una 8.1 o más nueva (si no tenés ninguna,
   ese mismo menú permite agregar versiones nuevas; la ubicación exacta puede variar según la versión de Laragon).

> Usás XAMPP en vez de Laragon: instalalo, copiá el proyecto en `C:\xampp\htdocs\` y
> salteá a la nota del paso 5. Necesitás de todos modos PHP 8.1 o superior.

### Paso 3 — Clonar el repositorio

En la terminal de Laragon (o en cualquier terminal), ejecutá **una línea por vez**:

```
cd C:\laragon\www
git clone https://github.com/Lexi714/mini-proyecto-laboratorio.git
cd mini-proyecto-laboratorio
```

Verificá que se clonó bien:

```
git log --oneline -3
dir
```

✅ **Debe mostrar** los últimos commits del proyecto y las carpetas `config`, `docs`, `public`,
`src` y `views` junto a `README.md`. Si `git clone` pide usuario y contraseña, revisá que la
URL esté bien escrita: el repositorio es público y no necesita credenciales para clonarse.

### Paso 4 — Iniciar el servidor

1. En Laragon, presioná **Start All**.
2. ✅ **Debe verse** Apache en verde (*Running*). MySQL **no** hace falta, aunque se inicie igual.

### Paso 5 — Abrir el proyecto en el navegador

Abrí esta dirección:

```
http://localhost/mini-proyecto-laboratorio/public/index.php
```

✅ **Debe verse** una línea que empieza con `[EMAIL] para paciente@mail.com | Pedido creado: Pedido 1 por $ 10500`,
y debajo el título **Pedido creado**, `Paciente: Juan Perez` y `Total: $ 10500`.

> **Alternativa sin Laragon ni XAMPP** (sirve para comprobar rápido que el código anda):
> desde la carpeta del proyecto ejecutá `php -S localhost:8000 -t public` y abrí
> `http://localhost:8000/index.php`. Dejá esa terminal abierta mientras lo usás.

### Paso 6 — Probar las cuatro acciones

Agregá `?accion=...` al final de la dirección (si usás el servidor de la nota anterior,
cambiá `http://localhost/mini-proyecto-laboratorio/public/index.php` por `http://localhost:8000/index.php`):

| Dirección | ✅ Resultado esperado |
|---|---|
| `...index.php?accion=crear` | El mismo resultado del paso 5 |
| `...index.php?accion=listar` | Título **Pedidos** y una tabla con 2 filas: `1 · Juan Perez · 10500` y `2 · Ana Gomez · 22000` |
| `...index.php?accion=reporte` | `Reporte: Pedidos del dia + firma digital + PDF + marca de agua` |
| `...index.php?accion=otra` | `Accion no encontrada` |

Probá también estos casos para ver las validaciones y el escapado:

| Dirección | ✅ Resultado esperado |
|---|---|
| `...index.php?accion=crear&tipo=jubilado&monto=1000` | `Total: $ 500` |
| `...index.php?accion=crear&monto=0` | **Pedido no creado** · `Monto invalido` |
| `...index.php?accion=crear&paciente=` | **Pedido no creado** · `Falta el paciente` |
| `...index.php?accion=crear&paciente=<b>hola</b>` | Se ve el texto `<b>hola</b>` literal (no se pone en negrita) |

Si las seis primeras filas dan lo esperado, **el proyecto quedó funcionando**.

### Paso 7 — Configuración (opcional)

El proyecto arranca sin configurar nada. Si querés usar valores propios de base de datos:

1. Copiá `config\database.example.php` como `config\database.php`.
2. Completá los valores (`host`, `name`, `user`, `pass`).

`config/database.php` está en `.gitignore`: **nunca se sube al repositorio**.

### Paso 8 — Para trabajar en el proyecto (equipo)

1. Nadie escribe directamente en `main`. Creá una rama: `git checkout -b feat/patron-<nombre>`.
2. Hacé tus cambios y commiteá con la convención (`feat:`, `fix:`, `refactor:` o `docs:`):
   `git commit -m "refactor(pricing): reemplazar switch por PricingStrategy"`.
3. Subí la rama: `git push -u origin feat/patron-<nombre>`.
4. Abrí un Pull Request en GitHub y pedí la revisión a otra persona del equipo.

---

## 5. Problemas comunes al levantarlo

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| `git` no se reconoce como comando | Git no está instalado o la terminal se abrió antes de instalarlo | Reinstalá Git y abrí una terminal nueva |
| `php` no se reconoce como comando | Estás en una terminal común, sin PHP en el PATH | Usá la **Terminal de Laragon** (clic derecho → Terminal) |
| El navegador dice *No se puede acceder a este sitio* | Apache no está iniciado | En Laragon presioná **Start All** |
| Apache no inicia (puerto 80 ocupado) | Otro programa usa el puerto (Skype, IIS, otro XAMPP) | Cerralo, o cambiá el puerto de Apache en las preferencias de Laragon (por ejemplo a 8080; la dirección pasa a ser `http://localhost:8080/...`) |
| Error 404 *Not Found* | Falta `/public/index.php` o la carpeta tiene otro nombre | La carpeta debe llamarse `mini-proyecto-laboratorio` y la dirección debe terminar en `/public/index.php` |
| `Parse error` o `syntax error ... readonly` / `match` | Tu PHP es menor a 8.1 | Cambiá a PHP 8.1 o más nuevo (paso 2) |
| `Class "X" not found` | El archivo de la clase no está en una subcarpeta de `src/` o su nombre no coincide con la clase | Cada clase va en `src/<Carpeta>/NombreDeLaClase.php` |
| Página en blanco | Errores de PHP ocultos | Mirá el log de PHP de Laragon o corré `php -S localhost:8000 -t public` y leé la terminal |

---

## 6. Estructura del proyecto

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

**Convención de autoload:** cada clase vive en un archivo con su mismo nombre, dentro de una
subcarpeta de `src/`. Una clase nueva se carga sola, sin editar `index.php`.

---

## 7. Cómo agregar un tipo de paciente nuevo

Ejemplo: `prepaga` con 20 % de descuento. Son **2 archivos**:

1. Crear `src/Pricing/PrepaidStrategy.php` con una clase que implemente `PricingStrategy`.
2. Sumar `'prepaga' => new PrepaidStrategy(),` al `match` de `src/Services/OrderPricingService.php`.

---

## 8. Flujo de trabajo del equipo

- Nadie escribe directamente en `main`: una rama por patrón y un Pull Request por rama.
- Nombre de las ramas de patrón: `feat/patron-<nombre>`.
- Cada PR responde: qué deuda se encontró (con la línea exacta), qué patrón se aplicó y por qué ese, y qué consecuencia negativa tiene.
- Cada PR lo revisa otra persona, nunca su autor.
- Commits con convención: `feat:`, `fix:`, `refactor:`, `docs:`.
- El código viejo se borra: conservarlo "por las dudas" es deuda nueva.
