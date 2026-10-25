<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SuizoTest extends TestCase
{
    /** @return string[] */
    private static function nombres(int $n): array
    {
        return array_map(static fn (int $i): string => "J$i", range(1, $n));
    }

    public static function cantidades(): array
    {
        $casos = [];
        foreach ([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 15, 16, 17, 24, 31, 32, 33, 63, 64] as $n) {
            $casos["$n participantes"] = [$n];
        }
        return $casos;
    }

    /** Juega el torneo completo con resultados al azar (semilla fija) y devuelve todos los partidos. */
    private static function jugar(int $n, int $semilla): array
    {
        mt_srand($semilla);
        $nombres  = self::nombres($n);
        $partidos = [];
        $ronda    = Suizo::primeraRonda($nombres);

        for ($r = 1; $r <= Suizo::rondas($n); $r++) {
            foreach ($ronda as $i => $p) {
                if ($p['visitante'] !== null) {
                    $azar = mt_rand(0, 9);
                    $ronda[$i]['ganador'] = $azar === 0 ? '' : ($azar % 2 === 0 ? $p['local'] : $p['visitante']);
                }
            }
            $partidos = array_merge($partidos, $ronda);
            if ($r < Suizo::rondas($n)) {
                $ronda = Suizo::siguienteRonda($nombres, $partidos);
            }
        }
        return $partidos;
    }

    public function testCantidadDeRondas(): void
    {
        $this->assertSame(1, Suizo::rondas(2));
        $this->assertSame(2, Suizo::rondas(3));
        $this->assertSame(2, Suizo::rondas(4));
        $this->assertSame(3, Suizo::rondas(5));
        $this->assertSame(3, Suizo::rondas(8));
        $this->assertSame(4, Suizo::rondas(9));
        $this->assertSame(5, Suizo::rondas(32));
        $this->assertSame(6, Suizo::rondas(64));
    }

    #[DataProvider('cantidades')]
    public function testPrimeraRondaEmparejaMitadContraMitad(int $n): void
    {
        $ronda = Suizo::primeraRonda(self::nombres($n));

        $vistos = [];
        foreach ($ronda as $p) {
            $vistos[] = $p['local'];
            if ($p['visitante'] !== null) {
                $vistos[] = $p['visitante'];
            }
        }
        sort($vistos);
        $esperados = self::nombres($n);
        sort($esperados);
        $this->assertSame($esperados, $vistos);
        $this->assertCount((int) ceil($n / 2), $ronda);

        $pases = array_filter($ronda, static fn (array $p): bool => $p['visitante'] === null);
        $this->assertCount($n % 2, $pases);
    }

    public function testEjemploFijoDePrimeraRonda(): void
    {
        $this->assertSame(
            [
                ['local' => 'A', 'visitante' => 'C', 'ganador' => null],
                ['local' => 'B', 'visitante' => 'D', 'ganador' => null],
                ['local' => 'E', 'visitante' => null, 'ganador' => 'E'],
            ],
            Suizo::primeraRonda(['A', 'B', 'C', 'D', 'E'])
        );
    }

    #[DataProvider('cantidades')]
    public function testNadieRepiteRivalNiJuegaDosVecesPorRonda(int $n): void
    {
        foreach ([1, 2, 3] as $semilla) {
            $partidos = self::jugar($n, $semilla);
            $rondas   = Suizo::rondas($n);
            $porRonda = (int) ceil($n / 2);
            $this->assertCount($rondas * $porRonda, $partidos);

            $enfrentados = [];
            foreach (array_chunk($partidos, $porRonda) as $numero => $ronda) {
                $aparecen = [];
                foreach ($ronda as $p) {
                    $aparecen[] = $p['local'];
                    if ($p['visitante'] !== null) {
                        $aparecen[] = $p['visitante'];
                        $par = [$p['local'], $p['visitante']];
                        sort($par);
                        $clave = implode('|', $par);
                        $this->assertArrayNotHasKey($clave, $enfrentados, "revancha $clave en la ronda " . ($numero + 1));
                        $enfrentados[$clave] = true;
                    }
                }
                $this->assertCount($n, array_unique($aparecen), 'ronda ' . ($numero + 1));
            }
        }
    }

    #[DataProvider('cantidades')]
    public function testElPaseNuncaSeRepite(int $n): void
    {
        if ($n % 2 === 0) {
            $this->expectNotToPerformAssertions();
            return;
        }
        foreach ([1, 2, 3] as $semilla) {
            $conPase = [];
            foreach (self::jugar($n, $semilla) as $p) {
                if ($p['visitante'] === null) {
                    $this->assertArrayNotHasKey($p['local'], $conPase, "{$p['local']} tuvo dos pases");
                    $conPase[$p['local']] = true;
                }
            }
        }
    }

    public function testClasificacionPuntosYDesempate(): void
    {
        $partidos = [
            ['local' => 'A', 'visitante' => 'B', 'ganador' => 'A'],
            ['local' => 'C', 'visitante' => 'D', 'ganador' => ''],
            ['local' => 'E', 'visitante' => null, 'ganador' => 'E'],
            ['local' => 'A', 'visitante' => 'C', 'ganador' => 'A'],
            ['local' => 'D', 'visitante' => 'E', 'ganador' => 'E'],
            ['local' => 'B', 'visitante' => null, 'ganador' => 'B'],
        ];
        $tabla = Suizo::clasificacion(['A', 'B', 'C', 'D', 'E'], $partidos);
        $por   = array_column($tabla, null, 'nombre');

        $this->assertSame(2.0, $por['A']['puntos']);
        $this->assertSame(2.0, $por['E']['puntos']);
        $this->assertSame(1.0, $por['B']['puntos']);
        $this->assertSame(0.5, $por['C']['puntos']);
        $this->assertSame(0.5, $por['D']['puntos']);
        $this->assertSame(1, $por['E']['pases']);
        $this->assertSame(1, $por['C']['empatados']);

        // A y E empatan en 2 puntos: A enfrentó a B (1) y C (0,5) = 1,5; E solo a D (0,5) = 0,5.
        $this->assertSame('A', $tabla[0]['nombre']);
        $this->assertSame('E', $tabla[1]['nombre']);
        $this->assertSame([1, 2, 3, 4, 5], array_column($tabla, 'posicion'));
    }

    public function testClasificacionIgnoraPartidosSinResultado(): void
    {
        $tabla = Suizo::clasificacion(['A', 'B'], [['local' => 'A', 'visitante' => 'B', 'ganador' => null]]);
        $this->assertSame(0.0, $tabla[0]['puntos']);
        $this->assertSame(0, $tabla[0]['jugados']);
        $this->assertSame('A', $tabla[0]['nombre']); // sin diferencias, manda el orden de la lista
    }

    public function testLaSegundaRondaEnfrentaGanadoresConGanadores(): void
    {
        $nombres = ['A', 'B', 'C', 'D'];
        $r1      = Suizo::primeraRonda($nombres); // A-C, B-D
        $r1[0]['ganador'] = 'A';
        $r1[1]['ganador'] = 'D';

        $r2 = Suizo::siguienteRonda($nombres, $r1);

        $this->assertCount(2, $r2);
        $pares = array_map(static function (array $p): string {
            $par = [$p['local'], $p['visitante']];
            sort($par);
            return implode('', $par);
        }, $r2);
        sort($pares);
        $this->assertSame(['AD', 'BC'], $pares); // ganadores entre sí, perdedores entre sí
    }

    public function testElLocalSeAlternaSegunLasVecesQueLoFue(): void
    {
        $nombres = ['A', 'B', 'C', 'D'];
        $r1      = Suizo::primeraRonda($nombres); // A-C y B-D: A y B fueron locales
        $r1[0]['ganador'] = 'A';
        $r1[1]['ganador'] = 'D';
        $r2 = Suizo::siguienteRonda($nombres, $r1); // A-D y B-C

        $this->assertEqualsCanonicalizing(['C', 'D'], array_column($r2, 'local'));
    }

    public function testParticipantesYResueltosDesdeLasRondas(): void
    {
        $rondas = [
            ['partidos' => [
                ['local' => 'A', 'visitante' => 'B', 'ganador' => ''],
                ['local' => 'C', 'visitante' => null, 'ganador' => 'C'],
            ]],
            ['partidos' => [['local' => 'A', 'visitante' => 'C', 'ganador' => null]]],
        ];
        $this->assertSame(['A', 'B', 'C'], Suizo::participantesDe($rondas));
        $this->assertCount(2, Suizo::resueltos($rondas));
        $this->assertTrue(Suizo::rondaCompleta($rondas[0]['partidos']));
        $this->assertFalse(Suizo::rondaCompleta($rondas[1]['partidos']));
    }

    public static function participantesInvalidos(): array
    {
        return [
            'ninguno'    => [[]],
            'uno solo'   => [['A']],
            'repetido'   => [['A', 'B', 'A']],
            'demasiados' => [array_map(static fn (int $i): string => "P$i", range(1, 65))],
        ];
    }

    #[DataProvider('participantesInvalidos')]
    public function testRechazaParticipantesInvalidos(array $participantes): void
    {
        $this->expectException(InvalidArgumentException::class);
        Suizo::primeraRonda($participantes);
    }
}
