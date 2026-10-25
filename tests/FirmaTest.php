<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FirmaTest extends TestCase
{
    private string $claveOriginal;

    private const DATOS = [
        'emisor'          => 'StarLeague',
        'id'              => 'a1b2c3d4e5f6',
        'id_torneo'       => 9,
        'torneo'          => 'Copa Ñandú',
        'tipo'            => 'usuario',
        'id_participante' => 1,
        'participante'    => 'admin',
        'fecha'           => '2026-10-12 06:02:17',
    ];

    protected function setUp(): void
    {
        $this->claveOriginal = (string) getenv('FIRMA_CLAVE_PRIVADA');
    }

    protected function tearDown(): void
    {
        putenv('FIRMA_CLAVE_PRIVADA=' . $this->claveOriginal);
    }

    private static function dividir(string $comprobante): array
    {
        return explode('.', $comprobante, 2);
    }

    private static function cambiarUnaLetra(string $texto): string
    {
        $i = intdiv(strlen($texto), 2);
        $nuevo = $texto[$i] === 'A' ? 'B' : 'A';
        return substr($texto, 0, $i) . $nuevo . substr($texto, $i + 1);
    }

    public function testLaClaveConfiguradaEstaDisponible(): void
    {
        $this->assertTrue(Firma::disponible());
        $this->assertNotNull(Firma::clavePublica());
    }

    public function testEmiteComprobanteConFormatoPayloadPuntoFirma(): void
    {
        $comprobante = Firma::emitir(self::DATOS);
        $this->assertNotNull($comprobante);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $comprobante);
    }

    public function testComprobanteValidoDevuelveLosDatosOriginales(): void
    {
        $datos = Firma::verificar(Firma::emitir(self::DATOS));
        $this->assertNotNull($datos);
        $this->assertSame(1, $datos['v']);
        $this->assertSame('Copa Ñandú', $datos['torneo']);
        $this->assertSame(9, $datos['id_torneo']);
        $this->assertSame('admin', $datos['participante']);
    }

    public function testToleraEspaciosAlrededorDelComprobante(): void
    {
        $this->assertNotNull(Firma::verificar("  \n" . Firma::emitir(self::DATOS) . " \t"));
    }

    public function testPayloadAlteradoSeRechaza(): void
    {
        [$payload, $firma] = self::dividir(Firma::emitir(self::DATOS));
        $this->assertNull(Firma::verificar(self::cambiarUnaLetra($payload) . '.' . $firma));
    }

    public function testFirmaAlteradaSeRechaza(): void
    {
        [$payload, $firma] = self::dividir(Firma::emitir(self::DATOS));
        $this->assertNull(Firma::verificar($payload . '.' . self::cambiarUnaLetra($firma)));
    }

    public function testCambiarElTorneoEnElPayloadSeRechaza(): void
    {
        [$payload, $firma] = self::dividir(Firma::emitir(self::DATOS));
        $json = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $json['id_torneo'] = 10;
        $falso = rtrim(strtr(base64_encode(json_encode($json)), '+/', '-_'), '=');
        $this->assertNull(Firma::verificar($falso . '.' . $firma));
    }

    public function testFirmaDeOtroComprobanteNoSirve(): void
    {
        [$payloadA]  = self::dividir(Firma::emitir(self::DATOS));
        [, $firmaB]  = self::dividir(Firma::emitir(['id' => 'otro'] + self::DATOS));
        $this->assertNull(Firma::verificar($payloadA . '.' . $firmaB));
    }

    public function testComprobanteFirmadoConOtraClaveSeRechaza(): void
    {
        $comprobante = Firma::emitir(self::DATOS);
        putenv('FIRMA_CLAVE_PRIVADA=' . base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())));
        $this->assertNull(Firma::verificar($comprobante));
    }

    #[DataProvider('textosInvalidos')]
    public function testTextosInvalidosSeRechazan(string $texto): void
    {
        $this->assertNull(Firma::verificar($texto));
    }

    public static function textosInvalidos(): array
    {
        return [
            'vacío'              => [''],
            'solo espacios'      => ['   '],
            'texto basura'       => ['esto no es un comprobante'],
            'sin punto'          => ['abcdef'],
            'tres partes'        => ['a.b.c'],
            'firma vacía'        => ['abc.'],
            'payload vacío'      => ['.abc'],
            'caracteres raros'   => ['<script>alert(1)</script>.@@@@'],
            'base64 estándar'    => ['ab+/cd==.ab+/cd=='],
            'firma muy corta'    => ['eyJ2IjoxfQ.AAAA'],
        ];
    }

    public function testSinClaveNoSeEmiteNiSeVerifica(): void
    {
        $comprobante = Firma::emitir(self::DATOS);
        putenv('FIRMA_CLAVE_PRIVADA=');
        $this->assertFalse(Firma::disponible());
        $this->assertNull(Firma::emitir(self::DATOS));
        $this->assertNull(Firma::verificar($comprobante));
        $this->assertNull(Firma::clavePublica());
    }

    public function testClaveConLargoIncorrectoSeConsideraNoConfigurada(): void
    {
        putenv('FIRMA_CLAVE_PRIVADA=' . base64_encode('clave-demasiado-corta'));
        $this->assertFalse(Firma::disponible());
        $this->assertNull(Firma::emitir(self::DATOS));
    }

    public function testDosEmisionesDelMismoContenidoSonVerificables(): void
    {
        $a = Firma::emitir(self::DATOS);
        $b = Firma::emitir(self::DATOS);
        $this->assertNotNull(Firma::verificar($a));
        $this->assertNotNull(Firma::verificar($b));
    }
}
