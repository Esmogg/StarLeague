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
}
