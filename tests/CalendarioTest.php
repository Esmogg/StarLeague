<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalendarioTest extends TestCase
{
    /** @return string[] */
    private static function nombres(int $n): array
    {
        return array_map(static fn (int $i): string => "Equipo $i", range(1, $n));
    }

    public static function cantidades(): array
    {
        $casos = [];
        foreach ([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 15, 16, 31, 32, 63, 64] as $n) {
            $casos["$n participantes"] = [$n];
        }
        return $casos;
    }

    #[DataProvider('cantidades')]
    public function testTodosSeEnfrentanUnaSolaVez(int $n): void
    {
        $jornadas = Calendario::todosContraTodos(self::nombres($n));

        $vistos = [];
        foreach ($jornadas as $jornada) {
            foreach ($jornada as $e) {
                if ($e['visitante'] === null) {
                    continue;
                }
                $par = [$e['local'], $e['visitante']];
                sort($par);
                $clave = implode('|', $par);
                $this->assertArrayNotHasKey($clave, $vistos, "se repite $clave");
                $vistos[$clave] = true;
            }
        }
        $this->assertCount($n * ($n - 1) / 2, $vistos);
    }

    #[DataProvider('cantidades')]
    public function testNadieJuegaDosVecesEnLaMismaJornada(int $n): void
    {
        foreach (Calendario::todosContraTodos(self::nombres($n)) as $numero => $jornada) {
            $aparecen = [];
            foreach ($jornada as $e) {
                $aparecen[] = $e['local'];
                if ($e['visitante'] !== null) {
                    $aparecen[] = $e['visitante'];
                }
            }
            $this->assertSame(
                $aparecen,
                array_values(array_unique($aparecen)),
                "alguien repetido en la jornada " . ($numero + 1)
            );
        }
    }

    #[DataProvider('cantidades')]
    public function testCantidadDeJornadasYEncuentros(int $n): void
    {
        $jornadas = Calendario::todosContraTodos(self::nombres($n));

        $esperadas = $n % 2 === 0 ? $n - 1 : $n;
        $this->assertCount($esperadas, $jornadas);

        foreach ($jornadas as $jornada) {
            // Con impares, uno descansa: el "encuentro" con visitante null cuenta como renglón.
            $this->assertCount((int) ceil($n / 2), $jornada);
        }
    }

    public static function impares(): array
    {
        $casos = [];
        foreach ([3, 5, 7, 9, 11, 15, 31, 63] as $n) {
            $casos["$n participantes"] = [$n];
        }
        return $casos;
    }

    #[DataProvider('impares')]
    public function testConImparesCadaUnoDescansaUnaVez(int $n): void
    {
        $descansos = [];
        foreach (Calendario::todosContraTodos(self::nombres($n)) as $jornada) {
            foreach ($jornada as $e) {
                if ($e['visitante'] === null) {
                    $descansos[] = $e['local'];
                }
            }
        }
        sort($descansos);
        $esperados = self::nombres($n);
        sort($esperados);
        $this->assertSame($esperados, $descansos);
    }

    #[DataProvider('cantidades')]
    public function testLaLocaliaEstaRepartida(int $n): void
    {
        $locales    = array_fill_keys(self::nombres($n), 0);
        $visitantes = array_fill_keys(self::nombres($n), 0);

        foreach (Calendario::todosContraTodos(self::nombres($n)) as $jornada) {
            foreach ($jornada as $e) {
                if ($e['visitante'] === null) {
                    continue;
                }
                $locales[$e['local']]++;
                $visitantes[$e['visitante']]++;
            }
        }

        foreach ($locales as $nombre => $comoLocal) {
            $this->assertLessThanOrEqual(
                1,
                abs($comoLocal - $visitantes[$nombre]),
                "$nombre juega demasiadas veces del mismo lado"
            );
        }
    }

    public function testIdaYVueltaDuplicaYInvierteLaLocalia(): void
    {
        $ida   = Calendario::todosContraTodos(self::nombres(6));
        $total = Calendario::todosContraTodos(self::nombres(6), true);

        $this->assertCount(10, $total);
        $this->assertSame($ida, array_slice($total, 0, 5));

        foreach (array_slice($total, 5) as $i => $jornada) {
            foreach ($jornada as $j => $e) {
                $this->assertSame($ida[$i][$j]['local'], $e['visitante']);
                $this->assertSame($ida[$i][$j]['visitante'], $e['local']);
            }
        }
    }

    public function testIdaYVueltaConImparesConservaLosDescansos(): void
    {
        $total = Calendario::todosContraTodos(self::nombres(5), true);

        $this->assertCount(10, $total);
        $descansan = 0;
        foreach ($total as $jornada) {
            foreach ($jornada as $e) {
                if ($e['visitante'] === null) {
                    $descansan++;
                }
            }
        }
        $this->assertSame(10, $descansan); // uno por jornada
    }

    public function testEjemploFijoDeCuatroParticipantes(): void
    {
        $this->assertSame(
            [
                [['local' => 'A', 'visitante' => 'D'], ['local' => 'B', 'visitante' => 'C']],
                [['local' => 'C', 'visitante' => 'A'], ['local' => 'D', 'visitante' => 'B']],
                [['local' => 'A', 'visitante' => 'B'], ['local' => 'C', 'visitante' => 'D']],
            ],
            Calendario::todosContraTodos(['A', 'B', 'C', 'D'])
        );
    }

    public function testEjemploFijoDeTresParticipantesConDescanso(): void
    {
        $this->assertSame(
            [
                [['local' => 'A', 'visitante' => null], ['local' => 'B', 'visitante' => 'C']],
                [['local' => 'C', 'visitante' => 'A'], ['local' => 'B', 'visitante' => null]],
                [['local' => 'A', 'visitante' => 'B'], ['local' => 'C', 'visitante' => null]],
            ],
            Calendario::todosContraTodos(['A', 'B', 'C'])
        );
    }

    public function testDosParticipantesJueganUnSoloPartido(): void
    {
        $this->assertSame(
            [[['local' => 'A', 'visitante' => 'B']]],
            Calendario::todosContraTodos(['A', 'B'])
        );
    }

    public static function participantesInvalidos(): array
    {
        return [
            'ninguno'  => [[]],
            'uno solo' => [['A']],
            'repetido' => [['A', 'B', 'A']],
            'demasiados' => [array_map(static fn (int $i): string => "P$i", range(1, 65))],
        ];
    }

    #[DataProvider('participantesInvalidos')]
    public function testRechazaParticipantesInvalidos(array $participantes): void
    {
        $this->expectException(InvalidArgumentException::class);
        Calendario::todosContraTodos($participantes);
    }

    public function testLasFechasSeRepartenEntreInicioYFin(): void
    {
        $this->assertSame(
            ['2026-06-01', '2026-06-11', '2026-06-21', '2026-07-01'],
            Calendario::fechas(4, '2026-06-01', '2026-07-01')
        );
    }

    public function testUnaSolaJornadaCaeElDiaDeInicio(): void
    {
        $this->assertSame(['2026-06-05'], Calendario::fechas(1, '2026-06-05', '2026-07-02'));
    }

    public function testMasJornadasQueDiasRepiteFechasPeroNuncaSePasaDelFin(): void
    {
        $fechas = Calendario::fechas(5, '2026-06-05', '2026-06-06');

        $this->assertCount(5, $fechas);
        $this->assertSame('2026-06-05', $fechas[0]);
        $this->assertSame('2026-06-06', $fechas[4]);
        $this->assertSame($fechas, array_values($fechas));
        foreach ($fechas as $f) {
            $this->assertGreaterThanOrEqual('2026-06-05', $f);
            $this->assertLessThanOrEqual('2026-06-06', $f);
        }
    }

    public function testLasFechasSonNoDecrecientes(): void
    {
        $fechas = Calendario::fechas(31, '2026-06-13', '2026-07-13');
        $orden  = $fechas;
        sort($orden);
        $this->assertSame($orden, $fechas);
        $this->assertSame('2026-07-13', $fechas[30]);
    }

    public static function fechasInvalidas(): array
    {
        return [
            'fin antes del inicio' => ['2026-07-02', '2026-06-05'],
            '30 de febrero'        => ['2026-02-30', '2026-03-05'],
            'formato dd/mm/aaaa'   => ['05/06/2026', '2026-07-02'],
            'vacía'                => ['', '2026-07-02'],
        ];
    }

    #[DataProvider('fechasInvalidas')]
    public function testRechazaFechasInvalidas(string $inicio, string $fin): void
    {
        $this->expectException(InvalidArgumentException::class);
        Calendario::fechas(3, $inicio, $fin);
    }

    public function testClasificacionDeLigaPuntosYOrden(): void
    {
        $encuentros = [
            ['local' => 'A', 'visitante' => 'B', 'ganador' => 'A'],
            ['local' => 'C', 'visitante' => 'D', 'ganador' => ''],
            ['local' => 'A', 'visitante' => 'C', 'ganador' => 'C'],
            ['local' => 'B', 'visitante' => 'D', 'ganador' => null],   // sin jugar: no cuenta
            ['local' => 'E', 'visitante' => null, 'ganador' => null],  // descanso: no cuenta
        ];
        $tabla = Calendario::clasificacion(['A', 'B', 'C', 'D', 'E'], $encuentros);
        $por   = array_column($tabla, null, 'nombre');

        $this->assertSame(3, $por['A']['puntos']);
        $this->assertSame(4, $por['C']['puntos']);
        $this->assertSame(1, $por['D']['puntos']);
        $this->assertSame(0, $por['B']['puntos']);
        $this->assertSame(0, $por['E']['jugados']);
        $this->assertSame(1, $por['C']['empatados']);
        $this->assertSame(['C', 'A', 'D', 'B', 'E'], array_column($tabla, 'nombre'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($tabla, 'posicion'));
    }

    public function testClasificacionDeLigaSinResultadosMantieneElOrdenDeLaLista(): void
    {
        $tabla = Calendario::clasificacion(['Z', 'Y', 'X'], [['local' => 'Z', 'visitante' => 'Y', 'ganador' => null]]);
        $this->assertSame(['Z', 'Y', 'X'], array_column($tabla, 'nombre'));
        $this->assertSame(0, $tabla[0]['puntos']);
    }

    public function testClasificacionDeLigaCompletaReparteLosPuntosEsperados(): void
    {
        $nombres  = self::nombres(6);
        $jornadas = Calendario::todosContraTodos($nombres);
        $resueltos = [];
        foreach ($jornadas as $jornada) {
            foreach ($jornada as $e) {
                $resueltos[] = $e + ['ganador' => $e['local']]; // siempre gana el local
            }
        }
        $tabla  = Calendario::clasificacion($nombres, $resueltos);
        $puntos = array_sum(array_column($tabla, 'puntos'));

        $this->assertSame(15 * 3, $puntos); // 15 partidos, 3 puntos cada uno
        $this->assertSame(array_fill(0, 6, 5), array_column($tabla, 'jugados'));
    }
}
