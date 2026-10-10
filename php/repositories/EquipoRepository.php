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
    public function crear(Equipo $equipo, int $idCreador, array $integrantes = []): int
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('INSERT INTO Equipo (nombre, disciplina) VALUES (:nombre, :disciplina)');
            $stmt->execute([':nombre' => $equipo->nombre, ':disciplina' => $equipo->disciplina]);
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
            'SELECT e.id_equipo, e.nombre, e.disciplina,
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
                'integrantes'    => [],
                'puede_eliminar' => $esAdmin || ($idSesion > 0 && $idCreador === $idSesion),
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
            $equipos[$id]['integrantes'][] = [
                'jugador' => $m['nombre'],
                'rol'     => ((int) $m['id_usuario'] === $creadores[$id]) ? 'Creador' : 'Jugador',
            ];
        }

        return array_values($equipos);
    }
}
