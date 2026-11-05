<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ejecuta public/index.php en un proceso aparte (como lo haria el servidor) y
 * comprueba la respuesta: autoload, configuracion externa y tabla de rutas.
 */
final class FrontControllerTest extends TestCase
{
    private function ejecutar(string $queryString): string
    {
        $index = var_export(dirname(__DIR__, 2) . '/public/index.php', true);
        $runner = tempnam(sys_get_temp_dir(), 'front');
        file_put_contents($runner, '<?php parse_str($argv[1] ?? "", $_GET); require ' . $index . ';');

        // Sin pasar por un shell: funciona igual en Windows, Linux y macOS.
        $proceso = proc_open(
            [PHP_BINARY, $runner, $queryString],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        $salida = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proceso);
        unlink($runner);

        return $salida;
    }

    public static function rutasValidas(): array
    {
        return [
            'CP-RUT-01 crear'   => ['accion=crear', 'Pedido creado'],
            'CP-RUT-02 listar'  => ['accion=listar', '<h1>Pedidos</h1>'],
            'CP-RUT-03 reporte' => ['accion=reporte', 'marca de agua'],
            'CP-RUT-04 sin accion (crear por defecto)' => ['', 'Pedido creado'],
        ];
    }

    #[DataProvider('rutasValidas')]
    public function testLasRutasConocidasResponden(string $query, string $esperado): void
    {
        $this->assertStringContainsString($esperado, $this->ejecutar($query));
    }

    public static function rutasInvalidas(): array
    {
        return [
            'CP-RUT-05 accion inexistente' => ['accion=borrar_todo'],
            'CP-RUT-06 accion como arreglo' => ['accion[]=listar'],
        ];
    }

    #[DataProvider('rutasInvalidas')]
    public function testUnaAccionInexistenteNoEncuentraRuta(string $query): void
    {
        $salida = $this->ejecutar($query);

        $this->assertStringContainsString('Accion no encontrada', $salida);
        $this->assertStringNotContainsString('Fatal error', $salida);
        $this->assertStringNotContainsString('Warning', $salida);
    }

    /** CP-RUT-07: no hay credenciales reales versionadas */
    public function testLaConfiguracionVersionadaNoTieneCredenciales(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/database.example.php';

        $this->assertArrayHasKey('host', $config);
        $this->assertSame('', $config['pass']);
    }
}
