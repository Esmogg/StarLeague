<?php
declare(strict_types=1);

/**
 * Llave (bracket) de un torneo de Eliminación Directa.
 * Lógica pura: no toca la base de datos ni la sesión, por eso se prueba sola.
 */
final class Bracket
{
    public const MIN_PARTICIPANTES = 2;
    public const MAX_PARTICIPANTES = 64;

    /**
     * Arma todas las rondas de la llave. La primera ronda se completa hasta la potencia de 2
     * más cercana con "pases directos" (bye): quien tiene el pase pasa solo a la ronda siguiente.
     * Las semillas (1 = primer nombre de la lista) se ubican de modo que las mejores se
     * encuentren lo más tarde posible, y los pases van a las primeras semillas.
     *
     * Cada partido es ['local' => ?string, 'visitante' => ?string, 'ganador' => ?string].
     * En la primera ronda, visitante null es un pase directo (ganador ya definido).
     * En las demás, null significa "por definir".
     *
     * @param  string[] $participantes nombres distintos, en orden de semilla
     * @return array<int,array<int,array{local:?string,visitante:?string,ganador:?string}>> rondas, de la primera a la final
     * @throws InvalidArgumentException si hay menos de 2, más de 64 o nombres repetidos
     */
    public static function generar(array $participantes): array
    {
        $lista = array_values($participantes);
        $n     = count($lista);

        if ($n < self::MIN_PARTICIPANTES || $n > self::MAX_PARTICIPANTES) {
            throw new InvalidArgumentException(sprintf(
                'La llave necesita entre %d y %d participantes.',
                self::MIN_PARTICIPANTES,
                self::MAX_PARTICIPANTES
            ));
        }
        if (count(array_unique($lista)) !== $n) {
            throw new InvalidArgumentException('Los participantes no pueden repetirse.');
        }

        $tamano = self::tamano($n);
        $orden  = self::ordenSemillas($tamano);

        // Primera ronda: se emparejan las semillas de a dos; una semilla mayor a n es un pase directo.
        $primera = [];
        for ($i = 0; $i < $tamano; $i += 2) {
            $local     = $lista[$orden[$i] - 1];
            $visitante = $lista[$orden[$i + 1] - 1] ?? null;
            $primera[] = [
                'local'     => $local,
                'visitante' => $visitante,
                'ganador'   => $visitante === null ? $local : null,
            ];
        }

        $rondas = [$primera];
        for ($partidos = intdiv($tamano, 4); $partidos >= 1; $partidos = intdiv($partidos, 2)) {
            $rondas[] = array_fill(0, $partidos, ['local' => null, 'visitante' => null, 'ganador' => null]);
        }

        // Quien tiene pase directo ya ocupa su lugar en la segunda ronda.
        foreach ($primera as $posicion => $partido) {
            if ($partido['ganador'] !== null && isset($rondas[1])) {
                $destino = self::destino(1, $posicion);
                $rondas[1][$destino['posicion']][$destino['lado']] = $partido['ganador'];
            }
        }

        return $rondas;
    }

    /** Potencia de 2 igual o mayor a la cantidad de participantes. */
    public static function tamano(int $n): int
    {
        $tamano = 2;
        while ($tamano < $n) {
            $tamano *= 2;
        }
        return $tamano;
    }

    /** Cantidad de rondas que tiene la llave para $n participantes. */
    public static function cantidadDeRondas(int $n): int
    {
        return (int) log(self::tamano($n), 2);
    }

    /**
     * Orden de las semillas en la primera ronda: [1, 8, 4, 5, 2, 7, 3, 6] para 8,
     * es decir 1-8, 4-5, 2-7 y 3-6. Cada vez que se duplica el tamaño, a cada semilla se le
     * agrega su complemento (la suma de cada pareja siempre es tamaño + 1).
     *
     * @return int[]
     */
    public static function ordenSemillas(int $tamano): array
    {
        $orden = [1, 2];
        while (count($orden) < $tamano) {
            $siguiente = [];
            $suma      = count($orden) * 2 + 1;
            foreach ($orden as $semilla) {
                $siguiente[] = $semilla;
                $siguiente[] = $suma - $semilla;
            }
            $orden = $siguiente;
        }
        return $orden;
    }

    /**
     * A dónde va el ganador de un partido: en la ronda siguiente, el partido de la mitad
     * de su posición; del lado local si su posición es par y visitante si es impar.
     *
     * @param  int $ronda    número de ronda (1 = primera)
     * @param  int $posicion posición del partido dentro de la ronda (0 = primero)
     * @return array{ronda:int,posicion:int,lado:string}
     */
    public static function destino(int $ronda, int $posicion): array
    {
        return [
            'ronda'    => $ronda + 1,
            'posicion' => intdiv($posicion, 2),
            'lado'     => $posicion % 2 === 0 ? 'local' : 'visitante',
        ];
    }
}
