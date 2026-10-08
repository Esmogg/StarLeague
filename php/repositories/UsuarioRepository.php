<?php
declare(strict_types=1);

final class UsuarioRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function existe(string $nombre, string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario FROM usuario WHERE nombre = :nombre OR email = :email LIMIT 1'
        );
        $stmt->execute([':nombre' => $nombre, ':email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function registrar(string $nombre, string $email, string $hash): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuario (nombre, email, contrasena) VALUES (:nombre, :email, :hash)'
        );
        return $stmt->execute([':nombre' => $nombre, ':email' => $email, ':hash' => $hash]);
    }

    public function buscarPorIdentificador(string $identificador): ?Usuario
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre, email, contrasena, rol, fecha_creacion
             FROM usuario WHERE nombre = :id1 OR email = :id2 LIMIT 1'
        );
        $stmt->execute([':id1' => $identificador, ':id2' => $identificador]);
        $fila = $stmt->fetch();
        return $fila ? Usuario::desdeFila($fila) : null;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre, email, rol, fecha_creacion FROM usuario WHERE id_usuario = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? Usuario::desdeFila($fila) : null;
    }

    public function esAdmin(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT rol FROM usuario WHERE id_usuario = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() === 'admin';
    }

    /** @return array<int,array{id:int,nombre:string}> */
    public function listarCandidatos(): array
    {
        return $this->db
            ->query('SELECT id_usuario AS id, nombre FROM usuario ORDER BY nombre ASC')
            ->fetchAll();
    }

    /** @param int[] $ids @return int[] los ids que existen realmente */
    public function filtrarExistentes(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuario WHERE id_usuario IN ($marcas)");
        $stmt->execute(array_values($ids));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
