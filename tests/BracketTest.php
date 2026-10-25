<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BracketTest extends TestCase
{
    /** @return string[] */
    private static function nombres(int $n): array
    {
        return array_map(static fn (int $i): string => "Equipo $i", range(1, $n));
    }

    public static function cantidades(): array
    {
        $casos = [];
        foreach ([2, 3, 4, 5, 6, 7, 8, 9, 12, 15, 16, 17, 31, 32, 33, 63, 64] as $n) {
            $casos["$n participantes"] = [$n];
        }
        return $casos;
    }

    #[DataProvider('cantidades')]
    public function testCantidadDeRondasYDePartidos(int $n): void
    {
        $rondas = Bracket::generar(self::nombres($n));
        $tamano = Bracket::tamano($n);

        $this->assertCount(Bracket::cantidadDeRondas($n), $rondas);
        foreach ($rondas as $indice => $partidos) {
            $this->assertCount($tamano / (2 ** ($indice + 1)), $partidos);
        }
        $this->assertCount(1, $rondas[array_key_last($rondas)]); // una sola final
    }

    #[DataProvider('cantidades')]
    public function testTodosAparecenUnaSolaVezEnLaPrimeraRonda(int $n): void
    {
        $vistos = [];
        foreach (Bracket::generar(self::nombres($n))[0] as $p) {
            $vistos[] = $p['local'];
            if ($p['visitante'] !== null) {
                $vistos[] = $p['visitante'];
            }
        }
        sort($vistos);
        $esperados = self::nombres($n);
        sort($esperados);
        $this->assertSame($esperados, $vistos);
    }

    #[DataProvider('cantidades')]
    public function testLosPasesDirectosCompletanLaLlaveYNuncaSeEnfrentanEntreSi(int $n): void
    {
        $rondas = Bracket::generar(self::nombres($n));
        $pases  = 0;

        foreach ($rondas[0] as $posicion => $p) {
            $this->assertNotNull($p['local'], 'un partido sin nadie');
            if ($p['visitante'] === null) {
                $pases++;
                $this->assertSame($p['local'], $p['ganador'], 'el pase directo ya avanza');
                if (isset($rondas[1])) {
                    $d = Bracket::destino(1, $posicion);
                    $this->assertSame($p['local'], $rondas[1][$d['posicion']][$d['lado']]);
                }
            } else {
                $this->assertNull($p['ganador']);
            }
        }
        $this->assertSame(Bracket::tamano($n) - $n, $pases);
    }

    #[DataProvider('cantidades')]
    public function testLasRondasPosterioresSoloTienenLoQueYaAvanzo(int $n): void
    {
        $rondas   = Bracket::generar(self::nombres($n));
        $ocupados = 0;

        foreach (array_slice($rondas, 1) as $indice => $partidos) {
            foreach ($partidos as $p) {
                $this->assertNull($p['ganador']);
                if ($indice > 0) {
                    $this->assertNull($p['local']);
                    $this->assertNull($p['visitante']);
                } else {
                    $ocupados += ($p['local'] !== null ? 1 : 0) + ($p['visitante'] !== null ? 1 : 0);
                }
            }
        }
        $this->assertSame(Bracket::tamano($n) - $n, $ocupados);
    }

    public function testLasPrimerasSemillasRecibenLosPases(): void
    {
        $rondas = Bracket::generar(self::nombres(6)); // tamaño 8: dos pases
        $conPase = array_values(array_map(
            static fn (array $p): string => $p['local'],
            array_filter($rondas[0], static fn (array $p): bool => $p['visitante'] === null)
        ));
        sort($conPase);
        $this->assertSame(['Equipo 1', 'Equipo 2'], $conPase);
    }

    public function testOrdenDeSemillas(): void
    {
        $this->assertSame([1, 2], Bracket::ordenSemillas(2));
        $this->assertSame([1, 4, 2, 3], Bracket::ordenSemillas(4));
        $this->assertSame([1, 8, 4, 5, 2, 7, 3, 6], Bracket::ordenSemillas(8));
    }

    public function testEjemploFijoDeCuatro(): void
    {
        $this->assertSame(
            [
                [
                    ['local' => 'A', 'visitante' => 'D', 'ganador' => null],
                    ['local' => 'B', 'visitante' => 'C', 'ganador' => null],
                ],
                [['local' => null, 'visitante' => null, 'ganador' => null]],
            ],
            Bracket::generar(['A', 'B', 'C', 'D'])
        );
    }

    public function testEjemploFijoDeTresConPase(): void
    {
        $this->assertSame(
            [
                [
                    ['local' => 'A', 'visitante' => null, 'ganador' => 'A'],
                    ['local' => 'B', 'visitante' => 'C', 'ganador' => null],
                ],
                [['local' => 'A', 'visitante' => null, 'ganador' => null]],
            ],
            Bracket::generar(['A', 'B', 'C'])
        );
    }

    public function testDosParticipantesJueganSoloLaFinal(): void
    {
        $this->assertSame(
            [[['local' => 'A', 'visitante' => 'B', 'ganador' => null]]],
            Bracket::generar(['A', 'B'])
        );
    }

    public function testDestinoDelGanador(): void
    {
        $this->assertSame(['ronda' => 2, 'posicion' => 0, 'lado' => 'local'], Bracket::destino(1, 0));
        $this->assertSame(['ronda' => 2, 'posicion' => 0, 'lado' => 'visitante'], Bracket::destino(1, 1));
        $this->assertSame(['ronda' => 2, 'posicion' => 1, 'lado' => 'local'], Bracket::destino(1, 2));
        $this->assertSame(['ronda' => 3, 'posicion' => 1, 'lado' => 'visitante'], Bracket::destino(2, 3));
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
        Bracket::generar($participantes);
    }
}
