<?php
declare(strict_types=1);

/**
 * Calendario de un torneo de Liga ("todos contra todos").
 * Lógica pura: no toca la base de datos ni la sesión, por eso se prueba sola.
 */
final class Calendario
{
    public const MIN_PARTICIPANTES = 2;
    public const MAX_PARTICIPANTES = 64;

    /**
     * Reparte los participantes en jornadas con el método del círculo: uno queda fijo
     * y los demás rotan, así todos se enfrentan una vez y nadie juega dos veces en la
     * misma jornada. Con cantidad impar, quien no tiene rival descansa esa jornada
     * (visitante = null) y cada uno descansa una sola vez. Con $idaYVuelta se agrega la segunda rueda con la localía invertida.
     *
     * @param  string[] $participantes nombres distintos, en el orden en que se quiera sortear
     * @return array<int,array<int,array{local:string,visitante:?string}>> jornadas, cada una con sus encuentros
     * @throws InvalidArgumentException si hay menos de 2, más de 64 o nombres repetidos
     */
    public static function todosContraTodos(array $participantes, bool $idaYVuelta = false): array
    {
        $lista = array_values($participantes);
        $n     = count($lista);

        if ($n < self::MIN_PARTICIPANTES || $n > self::MAX_PARTICIPANTES) {
            throw new InvalidArgumentException(sprintf(
                'El calendario necesita entre %d y %d participantes.',
                self::MIN_PARTICIPANTES,
                self::MAX_PARTICIPANTES
            ));
        }
        if (count(array_unique($lista)) !== $n) {
            throw new InvalidArgumentException('Los participantes no pueden repetirse.');
        }

        // Se trabaja con posiciones (0..n-1) y no con nombres; null es el "descanso" de las cantidades impares.
        $posiciones = range(0, $n - 1);
        if ($n % 2 === 1) {
            $posiciones[] = null;
        }
        $m = count($posiciones);

        $jornadas = [];
        for ($ronda = 0; $ronda < $m - 1; $ronda++) {
            $encuentros = [];
            for ($i = 0; $i < $m / 2; $i++) {
                $a = $posiciones[$i];
                $b = $posiciones[$m - 1 - $i];

                if ($a === null || $b === null) {
                    $encuentros[] = ['local' => $lista[$a ?? $b], 'visitante' => null];
                    continue;
                }

                // Localía: si la suma de posiciones es impar, local el de menor posición; si es par, el de mayor.
                // Con esta regla cada uno termina con la misma cantidad de partidos de local y de visitante (±1).
                [$local, $visitante] = ($a + $b) % 2 === 1
                    ? [min($a, $b), max($a, $b)]
                    : [max($a, $b), min($a, $b)];

                $encuentros[] = ['local' => $lista[$local], 'visitante' => $lista[$visitante]];
            }
            $jornadas[] = $encuentros;

            // Rotación: el último pasa al segundo lugar y el primero no se mueve.
            $ultimo = array_pop($posiciones);
            array_splice($posiciones, 1, 0, [$ultimo]);
        }

        if ($idaYVuelta) {
            $vuelta = array_map(
                static fn (array $jornada): array => array_map(
                    static fn (array $e): array => $e['visitante'] === null
                        ? $e
                        : ['local' => $e['visitante'], 'visitante' => $e['local']],
                    $jornada
                ),
                $jornadas
            );
            $jornadas = array_merge($jornadas, $vuelta);
        }

        return $jornadas;
    }

    /**
     * Fechas de las jornadas repartidas en forma pareja entre el inicio y el fin del torneo
     * (la primera cae el día de inicio y la última el día de fin).
     *
     * @return string[] fechas AAAA-MM-DD, una por jornada
     * @throws InvalidArgumentException si las fechas no son válidas o el fin es anterior al inicio
     */
    public static function fechas(int $cantidadJornadas, string $inicio, string $fin): array
    {
        $desde = DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
        $hasta = DateTimeImmutable::createFromFormat('!Y-m-d', $fin);

        if ($desde === false || $hasta === false
            || $desde->format('Y-m-d') !== $inicio || $hasta->format('Y-m-d') !== $fin) {
            throw new InvalidArgumentException('Las fechas del torneo no son válidas.');
        }
        if ($hasta < $desde) {
            throw new InvalidArgumentException('La fecha de fin es anterior a la de inicio.');
        }
        if ($cantidadJornadas < 1) {
            return [];
        }

        $dias   = (int) $desde->diff($hasta)->days;
        $fechas = [];
        for ($j = 0; $j < $cantidadJornadas; $j++) {
            $desplazamiento = $cantidadJornadas === 1 ? 0 : intdiv($j * $dias, $cantidadJornadas - 1);
            $fechas[]       = $desde->modify("+{$desplazamiento} days")->format('Y-m-d');
        }
        return $fechas;
    }

    /**
     * Tabla de posiciones de una Liga con los encuentros ya resueltos: victoria = 3 puntos,
     * empate = 1, derrota = 0. Ordena por puntos, después por victorias y por último por el
     * orden original de la lista. ganador null = sin jugar, '' = empate, o el nombre de quien ganó.
     *
     * @param  string[]                                                         $participantes
     * @param  array<int,array{local:string,visitante:?string,ganador:?string}> $encuentros
     * @return array<int,array{posicion:int,nombre:string,puntos:int,jugados:int,ganados:int,empatados:int,perdidos:int}>
     */
    public static function clasificacion(array $participantes, array $encuentros): array
    {
        $tabla = [];
        foreach (array_values($participantes) as $orden => $nombre) {
            $tabla[$nombre] = [
                'nombre' => $nombre, 'orden' => $orden, 'puntos' => 0,
                'jugados' => 0, 'ganados' => 0, 'empatados' => 0, 'perdidos' => 0,
            ];
        }

        foreach ($encuentros as $e) {
            if ($e['ganador'] === null || $e['visitante'] === null
                || !isset($tabla[$e['local']], $tabla[$e['visitante']])) {
                continue;
            }
            $l = $e['local'];
            $v = $e['visitante'];
            $tabla[$l]['jugados']++;
            $tabla[$v]['jugados']++;

            if ($e['ganador'] === '') {
                $tabla[$l]['empatados']++;
                $tabla[$v]['empatados']++;
                $tabla[$l]['puntos']++;
                $tabla[$v]['puntos']++;
            } else {
                $gana  = $e['ganador'] === $l ? $l : $v;
                $pierde = $gana === $l ? $v : $l;
                $tabla[$gana]['ganados']++;
                $tabla[$gana]['puntos'] += 3;
                $tabla[$pierde]['perdidos']++;
            }
        }

        $filas = array_values($tabla);
        usort($filas, static fn (array $a, array $b): int =>
            [$b['puntos'], $b['ganados'], $a['orden']] <=> [$a['puntos'], $a['ganados'], $b['orden']]);

        $resultado = [];
        foreach ($filas as $i => $f) {
            unset($f['orden']);
            $resultado[] = ['posicion' => $i + 1] + $f;
        }
        return $resultado;
    }
}
