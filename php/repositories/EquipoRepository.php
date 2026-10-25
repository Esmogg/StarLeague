<?php
declare(strict_types=1);

final class EquipoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Crea el equipo y registra al creador (CrearEquipo) y a todos los
     * integrantes (UnirseEquipo) en una sola transacción.
     *
     * @param int[] $integrantes ids de usuario ya validados (el creador se agrega solo)
     * @throws DomainException si el nombre ya existe
     */
    public function crear(Equipo $equipo, int $idCreador, array $integrantes = [], ?string $logo = null, ?string $banner = null): int
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('INSERT INTO Equipo (nombre, disciplina, logo, banner) VALUES (:nombre, :disciplina, :logo, :banner)');
            $stmt->execute([':nombre' => $equipo->nombre, ':disciplina' => $equipo->disciplina, ':logo' => $logo, ':banner' => $banner]);
            $idEquipo = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare('INSERT INTO CrearEquipo (id_equipo, id_usuario) VALUES (:e, :u)');
            $stmt->execute([':e' => $idEquipo, ':u' => $idCreador]);

            $unirse = $this->db->prepare('INSERT IGNORE INTO UnirseEquipo (id_equipo, id_usuario) VALUES (:e, :u)');
            foreach (array_unique(array_merge([$idCreador], $integrantes)) as $idUsuario) {
                $unirse->execute([':e' => $idEquipo, ':u' => $idUsuario]);
            }

            $this->db->commit();
            return $idEquipo;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate')) {
                throw new DomainException('El nombre del equipo ya está en uso. Por favor, elige otro.');
            }
            throw $e;
        }
    }

    /** @return array<int,array{id:int,nombre:string}> */
    public function listarCandidatos(): array
    {
        return $this->db
            ->query('SELECT id_equipo AS id, nombre FROM Equipo ORDER BY nombre ASC')
            ->fetchAll();
    }

    public function esCreador(int $idEquipo, int $idUsuario): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM CrearEquipo WHERE id_equipo = :e AND id_usuario = :u');
        $stmt->execute([':e' => $idEquipo, ':u' => $idUsuario]);
        return $stmt->fetch() !== false;
    }

    /**
     * Agrega al usuario como integrante del equipo (UnirseEquipo).
     *
     * @throws DomainException si el usuario ya es integrante
     */
    public function unirse(int $idEquipo, int $idUsuario): void
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO UnirseEquipo (id_equipo, id_usuario) VALUES (:e, :u)');
            $stmt->execute([':e' => $idEquipo, ':u' => $idUsuario]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate')) {
                throw new DomainException('Ya eres integrante de este equipo.');
            }
            throw $e;
        }
    }

    /** @return array{logo:?string,banner:?string} */
    public function imagenesDe(int $idEquipo): array
    {
        $stmt = $this->db->prepare('SELECT logo, banner FROM Equipo WHERE id_equipo = :e');
        $stmt->execute([':e' => $idEquipo]);
        $f = $stmt->fetch();
        return ['logo' => $f['logo'] ?? null, 'banner' => $f['banner'] ?? null];
    }

    public function existe(int $idEquipo): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM Equipo WHERE id_equipo = :e');
        $stmt->execute([':e' => $idEquipo]);
        return $stmt->fetch() !== false;
    }

    /** Los integrantes, el creador y las inscripciones se borran solos (ON DELETE CASCADE). */
    public function eliminar(int $idEquipo): void
    {
        $stmt = $this->db->prepare('DELETE FROM Equipo WHERE id_equipo = :e');
        $stmt->execute([':e' => $idEquipo]);
    }

    /**
     * Listado para buscar-equipos.js: cada equipo con su creador e integrantes.
     * `puede_eliminar` es verdadero para el administrador y para quien creó el equipo.
     */
    public function listarDetallado(int $idSesion, bool $esAdmin): array
    {
        $filas = $this->db->query(
            'SELECT e.id_equipo, e.nombre, e.disciplina, e.logo, e.banner,
                    ce.id_usuario AS id_creador, uc.nombre AS creador
             FROM Equipo e
             LEFT JOIN CrearEquipo ce ON ce.id_equipo = e.id_equipo
             LEFT JOIN usuario uc     ON uc.id_usuario = ce.id_usuario
             ORDER BY e.nombre ASC'
        )->fetchAll();

        $equipos   = [];
        $creadores = [];
        foreach ($filas as $f) {
            $id = (int) $f['id_equipo'];
            if (isset($equipos[$id])) {
                continue; // si hubiera dos creadores, se queda con el primero
            }
            $idCreador     = $f['id_creador'] !== null ? (int) $f['id_creador'] : 0;
            $creadores[$id] = $idCreador;
            $equipos[$id]  = [
                'id'             => $id,
                'id_equipo'      => $id,
                'nombre'         => $f['nombre'],
                'deporte'        => $f['disciplina'],
                'creador'        => $f['creador'],
                'logo'           => ($l = rutaImagenPublica($f['logo'])) ? '../../../' . $l : '',
                'banner'         => ($bn = rutaImagenPublica($f['banner'])) ? '../../../' . $bn : '',
                'integrantes'    => [],
                'puede_eliminar' => $esAdmin || ($idSesion > 0 && $idCreador === $idSesion),
                'puede_unirse'   => false, // un participante no se une solo: se lo pide al organizador (Mensajes)
            ];
        }

        $miembros = $this->db->query(
            'SELECT ue.id_equipo, u.id_usuario, u.nombre
             FROM UnirseEquipo ue
             INNER JOIN usuario u ON u.id_usuario = ue.id_usuario
             ORDER BY ue.id_equipo ASC, u.nombre ASC'
        )->fetchAll();

        foreach ($miembros as $m) {
            $id = (int) $m['id_equipo'];
            if (!isset($equipos[$id])) {
                continue;
            }
            if ($idSesion > 0 && (int) $m['id_usuario'] === $idSesion) {
                $equipos[$id]['puede_unirse'] = false;
            }
            $equipos[$id]['integrantes'][] = [
                'jugador' => $m['nombre'],
                'rol'     => ((int) $m['id_usuario'] === $creadores[$id]) ? 'Creador' : 'Jugador',
            ];
        }

        return array_values($equipos);
    }

    /**
     * Equipos de los que un usuario forma parte (como integrante o creador), para su perfil público.
     *
     * @return array<int,array{id:int,nombre:string,disciplina:string}>
     */
    public function deUsuario(int $idUsuario): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id_equipo AS id, e.nombre, e.disciplina
               FROM Equipo e JOIN UnirseEquipo ue ON ue.id_equipo = e.id_equipo
              WHERE ue.id_usuario = :u1
             UNION
             SELECT e.id_equipo AS id, e.nombre, e.disciplina
               FROM Equipo e JOIN CrearEquipo ce ON ce.id_equipo = e.id_equipo
              WHERE ce.id_usuario = :u2
             ORDER BY nombre ASC'
        );
        $stmt->execute([':u1' => $idUsuario, ':u2' => $idUsuario]);

        return array_map(static fn (array $f): array => [
            'id' => (int) $f['id'], 'nombre' => $f['nombre'], 'disciplina' => $f['disciplina'],
        ], $stmt->fetchAll());
    }
}
