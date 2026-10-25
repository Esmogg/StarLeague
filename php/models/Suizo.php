<?php
declare(strict_types=1);

/**
 * Sistema Suizo: cantidad fija de rondas, nadie queda eliminado y en cada ronda se enfrentan
 * participantes con puntaje parecido sin repetir rivales. Victoria = 1 punto, empate = 0,5 y
 * pase (el que sobra con cantidad impar) = 1 punto, y a nadie le toca más de un pase.
 * Lógica pura: no toca la base de datos ni la sesión, por eso se prueba sola.
 *
 * Cada partido es ['local' => string, 'visitante' => ?string, 'ganador' => ?string].
 * visitante null = pase. ganador null = sin jugar, '' = empate, o el nombre de quien ganó.
 */
final class Suizo
{
    public const MIN_PARTICIPANTES = 2;
    public const MAX_PARTICIPANTES = 64;

    /** Límite de intentos de la búsqueda de emparejamientos antes de aceptar un repetido. */
    private const LIMITE_BUSQUEDA = 50000;

    /** Cantidad de rondas: la menor que alcanza para tener un único líder (log2 de n, redondeado hacia arriba). */
    public static function rondas(int $n): int
    {
        $rondas = (int) ceil(log(max($n, 2), 2));
        return max(1, min($rondas, $n - 1));
    }

    /**
     * Primera ronda: la mitad mejor sembrada contra la otra mitad (1 vs n/2+1, 2 vs n/2+2...).
     * Con cantidad impar, el último de la lista tiene el pase.
     *
     * @param  string[] $participantes nombres distintos, en orden de sorteo
     * @return array<int,array{local:string,visitante:?string,ganador:?string}>
     * @throws InvalidArgumentException si hay menos de 2, más de 64 o nombres repetidos
     */
    public static function primeraRonda(array $participantes): array
    {
        $lista = array_values($participantes);
        $n     = count($lista);

        if ($n < self::MIN_PARTICIPANTES || $n > self::MAX_PARTICIPANTES) {
            throw new InvalidArgumentException(sprintf(
                'El sistema suizo necesita entre %d y %d participantes.',
                self::MIN_PARTICIPANTES,
                self::MAX_PARTICIPANTES
            ));
        }
        if (count(array_unique($lista)) !== $n) {
            throw new InvalidArgumentException('Los participantes no pueden repetirse.');
        }

        $pase = null;
        if ($n % 2 === 1) {
            $pase = array_pop($lista);
        }

        $mitad    = intdiv(count($lista), 2);
        $partidos = [];
        for ($i = 0; $i < $mitad; $i++) {
            $partidos[] = ['local' => $lista[$i], 'visitante' => $lista[$i + $mitad], 'ganador' => null];
        }
        if ($pase !== null) {
            $partidos[] = ['local' => $pase, 'visitante' => null, 'ganador' => $pase];
        }
        return $partidos;
    }

    /**
     * Tabla de posiciones con los partidos ya resueltos. Ordena por puntos, después por
     * desempate (suma de los puntos de los rivales enfrentados, "Buchholz"), después por
     * victorias y por último por el orden original de la lista.
     *
     * @param  string[]                                                         $participantes
     * @param  array<int,array{local:string,visitante:?string,ganador:?string}> $partidos
     * @return array<int,array{posicion:int,nombre:string,puntos:float,desempate:float,jugados:int,ganados:int,empatados:int,perdidos:int,pases:int}>
     */
    public static function clasificacion(array $participantes, array $partidos): array
    {
        $tabla = [];
        foreach (array_values($participantes) as $orden => $nombre) {
            $tabla[$nombre] = [
                'nombre' => $nombre, 'orden' => $orden, 'puntos' => 0.0, 'desempate' => 0.0, 'jugados' => 0,
                'ganados' => 0, 'empatados' => 0, 'perdidos' => 0, 'pases' => 0, 'rivales' => [],
            ];
        }

        foreach ($partidos as $p) {
            if ($p['ganador'] === null || !isset($tabla[$p['local']])) {
                continue;
            }
            $local = $p['local'];

            if ($p['visitante'] === null) {
                $tabla[$local]['puntos'] += 1.0;
                $tabla[$local]['pases']++;
                continue;
            }
            $visitante = $p['visitante'];
            if (!isset($tabla[$visitante])) {
                continue;
            }

            $tabla[$local]['jugados']++;
            $tabla[$visitante]['jugados']++;
            $tabla[$local]['rivales'][]     = $visitante;
            $tabla[$visitante]['rivales'][] = $local;

            if ($p['ganador'] === '') {
                $tabla[$local]['puntos']     += 0.5;
                $tabla[$visitante]['puntos'] += 0.5;
                $tabla[$local]['empatados']++;
                $tabla[$visitante]['empatados']++;
            } elseif ($p['ganador'] === $local) {
                $tabla[$local]['puntos'] += 1.0;
                $tabla[$local]['ganados']++;
                $tabla[$visitante]['perdidos']++;
            } else {
                $tabla[$visitante]['puntos'] += 1.0;
                $tabla[$visitante]['ganados']++;
                $tabla[$local]['perdidos']++;
            }
        }

        foreach ($tabla as $nombre => $fila) {
            foreach ($fila['rivales'] as $rival) {
                $tabla[$nombre]['desempate'] += $tabla[$rival]['puntos'];
            }
        }

        $filas = array_values($tabla);
        usort($filas, static fn (array $a, array $b): int =>
            [$b['puntos'], $b['desempate'], $b['ganados'], $a['orden']]
            <=> [$a['puntos'], $a['desempate'], $a['ganados'], $b['orden']]);

        $resultado = [];
        foreach ($filas as $i => $f) {
            $resultado[] = [
                'posicion'  => $i + 1,
                'nombre'    => $f['nombre'],
                'puntos'    => $f['puntos'],
                'desempate' => $f['desempate'],
                'jugados'   => $f['jugados'],
                'ganados'   => $f['ganados'],
                'empatados' => $f['empatados'],
                'perdidos'  => $f['perdidos'],
                'pases'     => $f['pases'],
            ];
        }
        return $resultado;
    }

    /**
     * Emparejamientos de la ronda siguiente: se ordena por posición, y cada uno se empareja con
     * el mejor ubicado que todavía no enfrentó. Con cantidad impar, el pase es para el peor
     * ubicado que no tuvo uno. Si es imposible no repetir rivales, se acepta un repetido.
     * El local es quien menos veces lo fue hasta ahora.
     *
     * @param  string[]                                                         $participantes
     * @param  array<int,array{local:string,visitante:?string,ganador:?string}> $partidos todos los jugados hasta ahora
     * @return array<int,array{local:string,visitante:?string,ganador:?string}>
     */
    public static function siguienteRonda(array $participantes, array $partidos): array
    {
        $orden = array_column(self::clasificacion($participantes, $partidos), 'nombre');

        $jugados  = [];
        $conPase  = [];
        $locales  = array_fill_keys($orden, 0);
        foreach ($partidos as $p) {
            if ($p['visitante'] === null) {
                $conPase[$p['local']] = true;
                continue;
            }
            $jugados[$p['local']][$p['visitante']] = true;
            $jugados[$p['visitante']][$p['local']] = true;
            $locales[$p['local']]++;
        }

        $parejas = null;
        $pase    = null;

        foreach ([false, true] as $permitirRepetidos) {
            if (count($orden) % 2 === 0) {
                $parejas = self::emparejar($orden, $jugados, $permitirRepetidos);
            } else {
                // Pase: primero a quien no tuvo uno, del peor ubicado hacia arriba; si no se puede, a cualquiera.
                foreach ([true, false] as $soloSinPase) {
                    foreach (array_reverse($orden) as $candidato) {
                        if ($soloSinPase && isset($conPase[$candidato])) {
                            continue;
                        }
                        $resto    = array_values(array_diff($orden, [$candidato]));
                        $emparejo = self::emparejar($resto, $jugados, $permitirRepetidos);
                        if ($emparejo !== null) {
                            $parejas = $emparejo;
                            $pase    = $candidato;
                            break 2;
                        }
                    }
                }
            }
            if ($parejas !== null) {
                break;
            }
        }

        $ronda = [];
        foreach ($parejas ?? [] as [$a, $b]) {
            // $a está mejor ubicado: es local salvo que ya lo haya sido más veces que $b.
            [$local, $visitante] = $locales[$a] > $locales[$b] ? [$b, $a] : [$a, $b];
            $ronda[] = ['local' => $local, 'visitante' => $visitante, 'ganador' => null];
        }
        if ($pase !== null) {
            $ronda[] = ['local' => $pase, 'visitante' => null, 'ganador' => $pase];
        }
        return $ronda;
    }

    /**
     * Nombres de los participantes (los de la primera ronda).
     *
     * @param  array<int,array{partidos:array<int,array{local:?string,visitante:?string}>}> $rondas
     * @return string[]
     */
    public static function participantesDe(array $rondas): array
    {
        $nombres = [];
        foreach ($rondas[0]['partidos'] ?? [] as $p) {
            foreach (['local', 'visitante'] as $lado) {
                if ($p[$lado] !== null) {
                    $nombres[] = $p[$lado];
                }
            }
        }
        return $nombres;
    }

    /**
     * Todos los partidos con resultado de las rondas dadas.
     *
     * @param  array<int,array{partidos:array<int,array{local:?string,visitante:?string,ganador:?string}>}> $rondas
     * @return array<int,array{local:string,visitante:?string,ganador:string}>
     */
    public static function resueltos(array $rondas): array
    {
        $lista = [];
        foreach ($rondas as $ronda) {
            foreach ($ronda['partidos'] as $p) {
                if ($p['ganador'] !== null && $p['local'] !== null) {
                    $lista[] = ['local' => $p['local'], 'visitante' => $p['visitante'], 'ganador' => $p['ganador']];
                }
            }
        }
        return $lista;
    }

    /** Verdadero si todos los partidos de la ronda (menos los pases) tienen resultado. */
    public static function rondaCompleta(array $partidos): bool
    {
        foreach ($partidos as $p) {
            if ($p['visitante'] !== null && $p['ganador'] === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Búsqueda con vuelta atrás de parejas que no se hayan enfrentado.
     *
     * @param  string[] $lista ordenados de mejor a peor
     * @return array<int,array{0:string,1:string}>|null null si no hay forma de no repetir
     */
    private static function emparejar(array $lista, array $jugados, bool $permitirRepetidos): ?array
    {
        $intentos = 0;
        return self::buscar($lista, $jugados, $permitirRepetidos, $intentos);
    }

    private static function buscar(array $lista, array $jugados, bool $permitirRepetidos, int &$intentos): ?array
    {
        if ($lista === []) {
            return [];
        }
        $primero = array_shift($lista);

        foreach ($lista as $i => $otro) {
            if (!$permitirRepetidos && isset($jugados[$primero][$otro])) {
                continue;
            }
            if (++$intentos > self::LIMITE_BUSQUEDA) {
                return null;
            }
            $resto = $lista;
            unset($resto[$i]);
            $sigue = self::buscar(array_values($resto), $jugados, $permitirRepetidos, $intentos);
            if ($sigue !== null) {
                return array_merge([[$primero, $otro]], $sigue);
            }
        }
        return null;
    }
}
