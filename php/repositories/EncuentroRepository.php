<?php
declare(strict_types=1);

/**
 * Partidos de los torneos: tabla Encuentro (un registro por partido programado).
 * En una Liga, `jornada` es la jornada; en Eliminación Directa es la ronda y `posicion` el lugar
 * del partido dentro de ella.
 */
final class EncuentroRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Guarda el calendario del torneo y borra el anterior, todo en una sola transacción:
     * si algo falla no queda un calendario a medias.
     *
     * @param  array<int,array<int,array{local:string,visitante:?string}>> $jornadas
     * @param  string[]                                                    $fechas una por jornada
     * @return int cantidad de partidos guardados (los descansos no cuentan)
     */
    public function reemplazar(int $idTorneo, array $jornadas, array $fechas): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM Encuentro WHERE id_torneo = :t')
                ->execute([':t' => $idTorneo]);

            $insertar = $this->db->prepare(
                'INSERT INTO Encuentro (id_torneo, jornada, fecha, participante_local, participante_visitante)
                 VALUES (:t, :j, :f, :l, :v)'
            );

            $total = 0;
            foreach ($jornadas as $indice => $encuentros) {
                foreach ($encuentros as $e) {
                    $insertar->execute([
                        ':t' => $idTorneo,
                        ':j' => $indice + 1,
                        ':f' => $fechas[$indice],
                        ':l' => $e['local'],
                        ':v' => $e['visitante'],
                    ]);
                    if ($e['visitante'] !== null) {
                        $total++;
                    }
                }
            }

            $this->db->commit();
            return $total;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Calendario del torneo agrupado por jornada.
     *
     * @return array<int,array{jornada:int,fecha:string,encuentros:array<int,array{id:int,local:string,visitante:?string,ganador:?string}>}>
     */
    public function listarPorTorneo(int $idTorneo): array
    {
        $stmt = $this->db->prepare(
            'SELECT id_encuentro, jornada, fecha, participante_local, participante_visitante, ganador
               FROM Encuentro
              WHERE id_torneo = :t
              ORDER BY jornada ASC, id_encuentro ASC'
        );
        $stmt->execute([':t' => $idTorneo]);

        $jornadas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $numero = (int) $fila['jornada'];
            $jornadas[$numero] ??= ['jornada' => $numero, 'fecha' => $fila['fecha'], 'encuentros' => []];
            $jornadas[$numero]['encuentros'][] = [
                'id'        => (int) $fila['id_encuentro'],
                'local'     => $fila['participante_local'],
                'visitante' => $fila['participante_visitante'],
                'ganador'   => $fila['ganador'],
            ];
        }
        return array_values($jornadas);
    }

    /** ¿El torneo ya tiene calendario o llave generados? Desde ahí no se aceptan más inscripciones. */
    public function tieneCalendario(int $idTorneo): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM Encuentro WHERE id_torneo = :t LIMIT 1');
        $stmt->execute([':t' => $idTorneo]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Resultado de un encuentro de Liga. $lado: 'local', 'visitante', 'empate' (se guarda como
     * ganador '') o 'ninguno' (deshacer). En una Liga se puede cargar o cambiar en cualquier jornada.
     *
     * @throws OutOfBoundsException     el encuentro no existe en ese torneo
     * @throws InvalidArgumentException es un descanso (no hay partido)
     */
    public function registrarResultadoLiga(int $idTorneo, int $idEncuentro, string $lado): void
    {
        $stmt = $this->db->prepare(
            'SELECT participante_local, participante_visitante FROM Encuentro
              WHERE id_encuentro = :id AND id_torneo = :t'
        );
        $stmt->execute([':id' => $idEncuentro, ':t' => $idTorneo]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            throw new OutOfBoundsException('El partido no existe.');
        }
        if ($fila['participante_visitante'] === null) {
            throw new InvalidArgumentException('Ese participante descansa: no hay partido que jugar.');
        }

        $ganador = match ($lado) {
            'local'     => $fila['participante_local'],
            'visitante' => $fila['participante_visitante'],
            'empate'    => '',
            default     => null,
        };

        $this->db->prepare('UPDATE Encuentro SET ganador = :g WHERE id_encuentro = :id AND id_torneo = :t')
            ->execute([':g' => $ganador, ':id' => $idEncuentro, ':t' => $idTorneo]);
    }

    /**
     * Partidos ya jugados de todos los torneos, para la página Resultados (más recientes primero).
     * Los descansos y pases no cuentan.
     *
     * @return array<int,array{id_torneo:int,fecha:string,resultado:string,torneo:string,disciplina:string}>
     */
    public function resultadosJugados(): array
    {
        $filas = $this->db->query(
            "SELECT e.id_torneo, e.jornada, e.fecha, e.participante_local, e.participante_visitante, e.ganador,
                    t.nombre AS torneo, t.disciplina, t.formato
               FROM Encuentro e
               JOIN Torneo t ON t.id_torneo = e.id_torneo
              WHERE e.ganador IS NOT NULL AND e.participante_visitante IS NOT NULL
              ORDER BY e.fecha DESC, e.id_encuentro DESC"
        )->fetchAll();

        $lista = [];
        foreach ($filas as $f) {
            $etapa = Torneo::normalizarFormato($f['formato']) === 'Liga' ? 'Jornada' : 'Ronda';
            $quien = $f['ganador'] === '' ? 'empate' : 'ganó ' . $f['ganador'];
            $lista[] = [
                'id_torneo'  => (int) $f['id_torneo'],
                'fecha'      => $f['fecha'],
                'resultado'  => "{$etapa} {$f['jornada']}: {$f['participante_local']} vs {$f['participante_visitante']} - {$quien}",
                'torneo'     => $f['torneo'],
                'disciplina' => $f['disciplina'],
            ];
        }
        return $lista;
    }

    /**
     * Guarda la llave de un torneo de Eliminación Directa y borra lo anterior (una sola transacción).
     *
     * @param  array<int,array<int,array{local:?string,visitante:?string,ganador:?string}>> $rondas
     * @param  string[]                                                                     $fechas una por ronda
     * @return int cantidad de partidos reales guardados (los pases directos no cuentan)
     */
    public function reemplazarLlave(int $idTorneo, array $rondas, array $fechas): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM Encuentro WHERE id_torneo = :t')
                ->execute([':t' => $idTorneo]);

            $insertar = $this->db->prepare(
                'INSERT INTO Encuentro (id_torneo, jornada, posicion, fecha, participante_local, participante_visitante, ganador)
                 VALUES (:t, :r, :p, :f, :l, :v, :g)'
            );

            $total = 0;
            foreach ($rondas as $indice => $partidos) {
                foreach ($partidos as $posicion => $partido) {
                    $insertar->execute([
                        ':t' => $idTorneo,
                        ':r' => $indice + 1,
                        ':p' => $posicion,
                        ':f' => $fechas[$indice],
                        ':l' => $partido['local'],
                        ':v' => $partido['visitante'],
                        ':g' => $partido['ganador'],
                    ]);
                    if ($indice > 0 || $partido['visitante'] !== null) {
                        $total++;
                    }
                }
            }

            $this->db->commit();
            return $total;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Llave del torneo agrupada por ronda.
     *
     * @return array<int,array{ronda:int,fecha:string,partidos:array<int,array{posicion:int,local:?string,visitante:?string,ganador:?string}>}>
     */
    public function listarLlave(int $idTorneo): array
    {
        $stmt = $this->db->prepare(
            'SELECT jornada, posicion, fecha, participante_local, participante_visitante, ganador
               FROM Encuentro
              WHERE id_torneo = :t
              ORDER BY jornada ASC, posicion ASC'
        );
        $stmt->execute([':t' => $idTorneo]);

        $rondas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $numero = (int) $fila['jornada'];
            $rondas[$numero] ??= ['ronda' => $numero, 'fecha' => $fila['fecha'], 'partidos' => []];
            $rondas[$numero]['partidos'][] = [
                'posicion'  => (int) $fila['posicion'],
                'local'     => $fila['participante_local'],
                'visitante' => $fila['participante_visitante'],
                'ganador'   => $fila['ganador'],
            ];
        }
        return array_values($rondas);
    }

    /**
     * Las rondas de todos los torneos que tienen partidos, con la misma forma que listarLlave
     * (para una Liga, "ronda" es la jornada).
     *
     * @return array<int,array<int,array{ronda:int,fecha:string,partidos:array}>> por id de torneo
     */
    public function listarTodas(): array
    {
        $filas = $this->db->query(
            'SELECT id_torneo, jornada, posicion, fecha, participante_local, participante_visitante, ganador
               FROM Encuentro
              ORDER BY id_torneo ASC, jornada ASC, posicion ASC, id_encuentro ASC'
        )->fetchAll();

        $torneos = [];
        foreach ($filas as $fila) {
            $idTorneo = (int) $fila['id_torneo'];
            $numero   = (int) $fila['jornada'];
            $torneos[$idTorneo][$numero] ??= ['ronda' => $numero, 'fecha' => $fila['fecha'], 'partidos' => []];
            $torneos[$idTorneo][$numero]['partidos'][] = [
                'posicion'  => (int) ($fila['posicion'] ?? 0),
                'local'     => $fila['participante_local'],
                'visitante' => $fila['participante_visitante'],
                'ganador'   => $fila['ganador'],
            ];
        }
        return array_map('array_values', $torneos);
    }

    /**
     * Marca quién ganó un partido de la llave y lo pasa a la ronda siguiente.
     * $lado es 'local', 'visitante' o 'ninguno' (deshacer). Todo en una transacción.
     *
     * @throws OutOfBoundsException     el partido no existe
     * @throws InvalidArgumentException el partido todavía no tiene a sus dos participantes
     * @throws DomainException          el partido siguiente ya tiene ganador (primero hay que deshacerlo)
     */
    public function registrarGanador(int $idTorneo, int $ronda, int $posicion, string $lado): void
    {
        $this->db->beginTransaction();
        try {
            $partido = $this->partido($idTorneo, $ronda, $posicion);
            if ($partido === null) {
                throw new OutOfBoundsException('El partido no existe.');
            }

            if ($lado === 'empate') {
                throw new InvalidArgumentException('En eliminación directa no hay empates.');
            }

            $esPase = $ronda === 1 && $partido['participante_visitante'] === null;
            if ($esPase) {
                throw new InvalidArgumentException('Ese participante pasa directo: no hay partido que jugar.');
            }

            if ($lado === 'ninguno') {
                $ganador = null;
            } else {
                $ganador = $lado === 'local' ? $partido['participante_local'] : $partido['participante_visitante'];
                if ($partido['participante_local'] === null || $partido['participante_visitante'] === null || $ganador === null) {
                    throw new InvalidArgumentException('Todavía no están definidos los dos participantes de este partido.');
                }
            }

            if ($ganador !== $partido['ganador']) {
                $destino   = Bracket::destino($ronda, $posicion);
                $siguiente = $this->partido($idTorneo, $destino['ronda'], $destino['posicion']);

                if ($siguiente !== null) {
                    if ($siguiente['ganador'] !== null) {
                        throw new DomainException(
                            'El partido siguiente ya tiene ganador. Primero deshaz ese resultado.'
                        );
                    }
                    // La columna sale de una lista fija, nunca de lo que mande el usuario.
                    $columna = $destino['lado'] === 'local' ? 'participante_local' : 'participante_visitante';
                    $this->db->prepare("UPDATE Encuentro SET $columna = :p WHERE id_encuentro = :id")
                        ->execute([':p' => $ganador, ':id' => $siguiente['id_encuentro']]);
                }

                $this->db->prepare('UPDATE Encuentro SET ganador = :g WHERE id_encuentro = :id')
                    ->execute([':g' => $ganador, ':id' => $partido['id_encuentro']]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Agrega una ronda nueva al torneo de Sistema Suizo (la 1 la crea reemplazarLlave).
     *
     * @param array<int,array{local:string,visitante:?string,ganador:?string}> $partidos
     */
    public function agregarRonda(int $idTorneo, int $ronda, string $fecha, array $partidos): void
    {
        $this->db->beginTransaction();
        try {
            $insertar = $this->db->prepare(
                'INSERT INTO Encuentro (id_torneo, jornada, posicion, fecha, participante_local, participante_visitante, ganador)
                 VALUES (:t, :r, :p, :f, :l, :v, :g)'
            );
            foreach (array_values($partidos) as $posicion => $partido) {
                $insertar->execute([
                    ':t' => $idTorneo, ':r' => $ronda, ':p' => $posicion, ':f' => $fecha,
                    ':l' => $partido['local'], ':v' => $partido['visitante'], ':g' => $partido['ganador'],
                ]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Resultado de un partido de Sistema Suizo. $lado: 'local', 'visitante', 'empate' (se guarda
     * como ganador '') o 'ninguno' (deshacer). Solo se puede tocar la última ronda generada,
     * porque las siguientes se emparejan con estos resultados.
     *
     * @throws OutOfBoundsException     el partido no existe
     * @throws InvalidArgumentException es un pase (no hay partido)
     * @throws DomainException          no es la última ronda
     */
    public function registrarResultadoSuizo(int $idTorneo, int $ronda, int $posicion, string $lado): void
    {
        $this->db->beginTransaction();
        try {
            $partido = $this->partido($idTorneo, $ronda, $posicion);
            if ($partido === null) {
                throw new OutOfBoundsException('El partido no existe.');
            }
            if ($partido['participante_visitante'] === null) {
                throw new InvalidArgumentException('Ese participante tiene pase: no hay partido que jugar.');
            }

            $stmt = $this->db->prepare('SELECT MAX(jornada) AS ultima FROM Encuentro WHERE id_torneo = :t');
            $stmt->execute([':t' => $idTorneo]);
            if ($ronda !== (int) $stmt->fetch()['ultima']) {
                throw new DomainException('Solo se puede cambiar el resultado de la última ronda generada.');
            }

            $ganador = match ($lado) {
                'local'     => $partido['participante_local'],
                'visitante' => $partido['participante_visitante'],
                'empate'    => '',
                default     => null,
            };

            $this->db->prepare('UPDATE Encuentro SET ganador = :g WHERE id_encuentro = :id')
                ->execute([':g' => $ganador, ':id' => $partido['id_encuentro']]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    private function partido(int $idTorneo, int $ronda, int $posicion): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id_encuentro, participante_local, participante_visitante, ganador
               FROM Encuentro
              WHERE id_torneo = :t AND jornada = :r AND posicion = :p'
        );
        $stmt->execute([':t' => $idTorneo, ':r' => $ronda, ':p' => $posicion]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }
}
