<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Prueba las reglas del backend con los mismos casos que tests/validaciones.test.js (frontend). */
final class ValidadorTest extends TestCase
{
    #[DataProvider('casos')]
    public function testReglaDelServidor(string $fn, array $args, ?string $esperado): void
    {
        $this->assertSame($esperado, Validador::$fn(...$args));
    }

    public static function casos(): array
    {
        $json = json_decode((string) file_get_contents(__DIR__ . '/casos_validacion.json'), true);
        $casos = [];
        foreach ($json as $c) {
            $casos[$c['fn'] . ': ' . $c['caso']] = [$c['fn'], $c['args'], $c['esperado']];
        }
        return $casos;
    }
}
