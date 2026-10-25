<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        iniciarSesion();
        $_SESSION = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_POST = [];
    }

    public function testElTokenTiene64CaracteresHexadecimales(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', tokenCsrf());
    }

    public function testElTokenSeMantieneDentroDeLaSesion(): void
    {
        $this->assertSame(tokenCsrf(), tokenCsrf());
    }

    public function testTokenesDeSesionesDistintasSonDistintos(): void
    {
        $a = tokenCsrf();
        $_SESSION = [];
        $this->assertNotSame($a, tokenCsrf());
    }

    public function testSinTokenEnLaPeticionSeRechaza(): void
    {
        tokenCsrf();
        $this->assertFalse(csrfValido());
    }

    public function testSinTokenEnLaSesionSeRechazaAunqueLaPeticionTraigaUno(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'cualquiera';
        $this->assertFalse(csrfValido());
    }

    public function testSinTokenEnLaSesionSeRechazaConCabeceraVacia(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        $this->assertFalse(csrfValido());
    }

    public function testTokenIncorrectoEnLaCabeceraSeRechaza(): void
    {
        tokenCsrf();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('0', 64);
        $this->assertFalse(csrfValido());
    }

    public function testTokenCorrectoEnLaCabeceraSeAcepta(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = tokenCsrf();
        $this->assertTrue(csrfValido());
    }

    public function testTokenCorrectoEnElCampoDelFormularioSeAcepta(): void
    {
        $_POST['_csrf'] = tokenCsrf();
        $this->assertTrue(csrfValido());
    }

    public function testTokenConUnaLetraDeMasSeRechaza(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = tokenCsrf() . 'a';
        $this->assertFalse(csrfValido());
    }
}
