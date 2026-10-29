# Deuda técnica — laboratorio-pedidos

Cada fila enlaza un síntoma observado con su evidencia en el código, el patrón
aplicado y el costo que el equipo asume por esa solución. Las líneas se refieren
al código original (rama `main` antes de los refactors).

| Síntoma observado | Evidencia (archivo:línea) | Tipo de deuda | Patrón aplicado | Consecuencia asumida |
|---|---|---|---|---|
| `switch` con todos los algoritmos de precio; el descuento de obra social (`0.7`) está escrito a mano y repetido dentro del mismo archivo | `src/Pricing/PriceCalculator.php:34`, `src/Pricing/PriceCalculator.php:53` | Diseño | Strategy (`feat/patron-strategy`) | Un `switch` de ~10 líneas pasa a 5 archivos. Sobreingeniería si los tipos de paciente no crecen; además alguien debe elegir la estrategia según el tipo de paciente |
| El modelo muta libremente, nace sin validar, conoce SQL (armado por concatenación) y calcula precios | `src/Models/Order.php:24-27`, `src/Models/Order.php:62-67`, `src/Models/Order.php:96-107` | Diseño | Repository + Strategy (`feat/patron-repository`) | Una clase más para una sola entidad; la inyección SQL queda mitigada y no resuelta, porque `Connection::ejecutar()` recibe un `string` y no admite consultas preparadas |
| Una conexión nueva en cada llamada y excepción tragada en silencio: el pedido "se guarda" aunque falle | `src/Database/Connection.php:36`, `src/Database/Connection.php:64-70` | Diseño | Singleton (`feat/patron-singleton`) | Estado global difícil de reemplazar por un doble en pruebas; el acoplamiento a `Connection::obtener()` sigue en controlador, vista y servicio |
