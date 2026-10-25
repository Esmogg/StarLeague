<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/** Quién puede leer, atender o eliminar un mensaje del buzón. */
final class MensajeTest extends TestCase
{
    public function testAdminGestionaTodo(): void
    {
        $this->assertTrue(MensajeRepository::puedeGestionar(['_dueno' => 7], 1, true));
        $this->assertTrue(MensajeRepository::puedeGestionar(['_dueno' => null], 1, true));
    }

    public function testOrganizadorGestionaLosDeSusTorneosYLosGenerales(): void
    {
        $this->assertTrue(MensajeRepository::puedeGestionar(['_dueno' => 2], 2, false));
        $this->assertTrue(MensajeRepository::puedeGestionar(['_dueno' => null], 2, false));
    }

    public function testOrganizadorNoGestionaTorneosAjenos(): void
    {
        $this->assertFalse(MensajeRepository::puedeGestionar(['_dueno' => 3], 2, false));
    }

    public function testLosTiposYLosLimitesSonLosEsperados(): void
    {
        $this->assertSame(['solicitud', 'consulta', 'reporte'], MensajeRepository::TIPOS);
        $this->assertSame(5, MensajeRepository::MAX_POR_DIA);
    }
}
