<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    private array $servidorOriginal;

    protected function setUp(): void
    {
        $this->servidorOriginal = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->servidorOriginal;
    }

    #[DataProvider('largos')]
    public function testLongitudCuentaCaracteresUtf8(string $texto, int $esperado): void
    {
        $this->assertSame($esperado, longitud($texto));
    }

    public static function largos(): array
    {
        return [
            'vacío'            => ['', 0],
            'ascii'            => ['hola', 4],
            'con ñ y tildes'   => ['Ñandú', 5],
            'solo espacios'    => ['   ', 3],
        ];
    }

    #[DataProvider('recortes')]
    public function testRecortarCortaPorCaracteresUtf8(string $texto, int $max, string $esperado): void
    {
        $this->assertSame($esperado, recortar($texto, $max));
    }

    public static function recortes(): array
    {
        return [
            'más corto que el máximo' => ['hola', 10, 'hola'],
            'justo en el máximo'      => ['hola', 4, 'hola'],
            'se corta'                => ['abcdef', 3, 'abc'],
            'no parte una ñ'          => ['ññññ', 2, 'ññ'],
            'vacío'                   => ['', 5, ''],
        ];
    }

    public function testIpDirectaSinProxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->assertSame('203.0.113.7', ipCliente());
    }

    public function testDetrasDelProxyTomaLaUltimaEntradaDeXForwardedFor(): void
    {
        $_SERVER['REMOTE_ADDR'] = '172.18.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.9.9.9, 198.51.100.4';
        $this->assertSame('198.51.100.4', ipCliente());
    }

    public function testXForwardedForConBasuraSeIgnora(): void
    {
        $_SERVER['REMOTE_ADDR'] = '172.18.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'no-es-una-ip';
        $this->assertSame('172.18.0.1', ipCliente());
    }

    public function testUnClienteExternoNoPuedeFalsearSuIpConLaCabecera(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $this->assertSame('203.0.113.7', ipCliente());
    }

    public function testRegistrarEventoLimpiaElNombreDeUsuario(): void
    {
        $archivo = tempnam(sys_get_temp_dir(), 'sl');
        $anterior = ini_set('error_log', $archivo);
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        registrarEvento('login_fallido', "admin\nFALSO evento=x");

        ini_set('error_log', (string) $anterior);
        $log = (string) file_get_contents($archivo);
        unlink($archivo);

        $this->assertStringContainsString('[StarLeague][seguridad] login_fallido', $log);
        $this->assertStringContainsString('ip=203.0.113.7', $log);
        $this->assertSame(1, substr_count($log, '[StarLeague]'), 'Una sola línea de log: no se puede inyectar otra');
    }
}
