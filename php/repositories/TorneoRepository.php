<?php
declare(strict_types=1);

final class TorneoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /** @throws DomainException si el nombre ya existe */
    public function crear(Torneo $t): int
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO Torneo
                    (id_usuario, nombre, descripcion, categoria, disciplina, formato,
                     cantParticipantes, fecha_inicio, fecha_fin)
                 VALUES (:u, :nombre, :descripcion, :categoria, :disciplina, :formato, :cant, :inicio, :fin)'
            );
            $stmt->execute([
                ':u'           => $t->idCreador,
                ':nombre'      => $t->nombre,
                ':descripcion' => $t->descripcion,
                ':categoria'   => $t->categoria,
                ':disciplina'  => $t->disciplina,
                ':formato'     => $t->formato,
                ':cant'        => $t->cantParticipantes,
                ':inicio'      => $t->fechaInicio,
                ':fin'         => $t->fechaFin,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate')) {
                throw new DomainException('El nombre del torneo ya está en uso. Por favor, elige otro.');
            }
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(): array
    {
        $filas = $this->db
            ->query('SELECT id_torneo, nombre, disciplina, cantParticipantes FROM Torneo ORDER BY fecha_inicio ASC')
            ->fetchAll();

        foreach ($filas as &$f) {
            $f['es_equipo'] = Torneo::esPorEquipo($f['disciplina']);
        }
        return $filas;
    }

    public function cupoMaximo(int $idTorneo): ?int
    {
        $stmt = $this->db->prepare('SELECT cantParticipantes FROM Torneo WHERE id_torneo = :id');
        $stmt->execute([':id' => $idTorneo]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    /** Torneos donde participa el usuario, solo o a través de un equipo suyo. */
    public function inscripcionesDeUsuario(int $idUsuario): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id_torneo, t.nombre, t.categoria, t.disciplina, t.formato, t.fecha_inicio, t.fecha_fin
               FROM Torneo t JOIN ParticipaIndv pi ON pi.id_torneo = t.id_torneo
              WHERE pi.id_usuario = :u1
             UNION
             SELECT t.id_torneo, t.nombre, t.categoria, t.disciplina, t.formato, t.fecha_inicio, t.fecha_fin
               FROM Torneo t
               JOIN ParticipaEquip pe ON pe.id_torneo = t.id_torneo
               JOIN UnirseEquipo ue   ON ue.id_equipo = pe.id_equipo
              WHERE ue.id_usuario = :u2
             ORDER BY nombre ASC'
        );
        $stmt->execute([':u1' => $idUsuario, ':u2' => $idUsuario]);
        return $stmt->fetchAll();
    }

    /** Id del usuario que creó el torneo, o null si no existe. */
    public function duenoDe(int $idTorneo): ?int
    {
        $stmt = $this->db->prepare('SELECT id_usuario FROM Torneo WHERE id_torneo = :id');
        $stmt->execute([':id' => $idTorneo]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    /** Las inscripciones y los partidos se borran solos (ON DELETE CASCADE). */
    public function eliminar(int $idTorneo): void
    {
        $stmt = $this->db->prepare('DELETE FROM Torneo WHERE id_torneo = :id');
        $stmt->execute([':id' => $idTorneo]);
    }

    /**
     * Listado completo para ver-torneos.js. `puede_eliminar` es verdadero para
     * el administrador y para quien creó el torneo.
     */
    public function listarDetallado(int $idSesion, bool $esAdmin): array
    {
        $filas = $this->db->query(
            'SELECT t.id_torneo, t.id_usuario, t.nombre, t.descripcion, t.categoria,
                    t.disciplina, t.formato, t.cantParticipantes, t.fecha_inicio, t.fecha_fin,
                    u.nombre AS organizador,
                    (SELECT COUNT(*) FROM ParticipaIndv  pi WHERE pi.id_torneo = t.id_torneo) AS insc_indv,
                    (SELECT COUNT(*) FROM ParticipaEquip pe WHERE pe.id_torneo = t.id_torneo) AS insc_equip
               FROM Torneo t
               JOIN usuario u ON u.id_usuario = t.id_usuario
              ORDER BY t.fecha_inicio ASC, t.id_torneo ASC'
        )->fetchAll();

        $torneos = [];
        foreach ($filas as $f) {
            $porEquipo = Torneo::esPorEquipo($f['disciplina']);
            $cupo      = (int) $f['cantParticipantes'];

            $t = [
                'id'               => (int) $f['id_torneo'],
                'id_torneo'        => (int) $f['id_torneo'],
                'nombre'           => $f['nombre'],
                'descripcion'      => $f['descripcion'],
                'categoria'        => $f['categoria'],
                'disciplina'       => $f['disciplina'],
                'formato'          => $f['formato'] === 'Eliminacion Directa' ? 'Eliminación Directa' : $f['formato'],
                'tipoParticipante' => $porEquipo ? 'Equipos' : 'Jugadores individuales',
                'inscriptos'       => (int) ($porEquipo ? $f['insc_equip'] : $f['insc_indv']),
                'fecha_inicio'     => $f['fecha_inicio'],
                'fecha_fin'        => $f['fecha_fin'],
                'organizador'      => $f['organizador'],
                'puede_eliminar'   => $esAdmin || ($idSesion > 0 && (int) $f['id_usuario'] === $idSesion),
            ];
            // ver-torneos.js muestra "N equipos" o "N jugadores" según la clave
            $t[$porEquipo ? 'cantidadEquipos' : 'cantidadJugadores'] = $cupo;

            $torneos[] = $t;
        }
        return $torneos;
    }
}
