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

    /** El inicio de sesión exige el nombre Y el correo de la misma cuenta. */
    public function buscarPorNombreYCorreo(string $nombre, string $correo): ?Usuario
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre, email, contrasena, rol, fecha_creacion
             FROM usuario WHERE nombre = :nombre AND email = :email LIMIT 1'
        );
        $stmt->execute([':nombre' => $nombre, ':email' => $correo]);
        $fila = $stmt->fetch();
        return $fila ? Usuario::desdeFila($fila) : null;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre, email, rol, fecha_creacion, foto_perfil, banner_perfil FROM usuario WHERE id_usuario = :id'
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

    public function rolDe(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT rol FROM usuario WHERE id_usuario = :id');
        $stmt->execute([':id' => $id]);
        $rol = $stmt->fetchColumn();
        return $rol === false ? null : (string) $rol;
    }

    public const ROLES = ['jugador', 'organizador', 'admin'];

    /** Todos los usuarios con su rol, para la pantalla de administración (nunca incluye la contraseña). */
    public function listarConRol(): array
    {
        $filas = $this->db->query(
            'SELECT id_usuario, nombre, email, rol, bloqueado FROM usuario ORDER BY nombre ASC'
        )->fetchAll();

        return array_map(static fn (array $f): array => [
            'id'     => (int) $f['id_usuario'],
            'nombre' => $f['nombre'],
            'email'  => $f['email'],
            'rol'    => $f['rol'],
            'bloqueado' => (int) $f['bloqueado'] === 1,
        ], $filas);
    }

    /** ¿La cuenta está bloqueada por un administrador? (cuenta inexistente = no) */
    public function estaBloqueado(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT bloqueado FROM usuario WHERE id_usuario = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() === 1;
    }

    public function cambiarBloqueo(int $id, bool $bloqueado): void
    {
        $this->db->prepare('UPDATE usuario SET bloqueado = :b WHERE id_usuario = :id')
            ->execute([':b' => $bloqueado ? 1 : 0, ':id' => $id]);
    }

    /** Cantidad de torneos creados por el usuario (borrar al usuario los borraría en cascada). */
    public function contarTorneosCreados(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM Torneo WHERE id_usuario = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare('DELETE FROM usuario WHERE id_usuario = :id')->execute([':id' => $id]);
    }

    public function contarAdmins(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM usuario WHERE rol = 'admin'")->fetchColumn();
    }

    public function cambiarRol(int $id, string $rol): void
    {
        if (!in_array($rol, self::ROLES, true)) {
            throw new InvalidArgumentException('Rol inválido.');
        }
        $this->db->prepare('UPDATE usuario SET rol = :rol WHERE id_usuario = :id')
            ->execute([':rol' => $rol, ':id' => $id]);
    }


    /**
     * Búsqueda pública por nombre (nunca devuelve correo ni contraseña). Máximo $limite resultados.
     *
     * @return array<int,array{id:int,nombre:string,rol:string,foto_perfil:?string}>
     */
    public function buscarPublicos(string $texto, int $limite = 30): array
    {
        // Los comodines del usuario (% y _) se escapan con "!" para que se busquen como texto común.
        $patron = '%' . strtr($texto, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';

        $stmt = $this->db->prepare(
            "SELECT id_usuario, nombre, rol, foto_perfil
               FROM usuario
              WHERE nombre LIKE :patron ESCAPE '!' AND bloqueado = 0
              ORDER BY nombre ASC
              LIMIT " . max(1, min($limite, 100))
        );
        $stmt->execute([':patron' => $patron]);

        return array_map(static fn (array $f): array => [
            'id'          => (int) $f['id_usuario'],
            'nombre'      => $f['nombre'],
            'rol'         => $f['rol'],
            'foto_perfil' => $f['foto_perfil'],
        ], $stmt->fetchAll());
    }

    /**
     * Datos públicos de un usuario: nunca incluye correo ni contraseña.
     *
     * @return array{id:int,nombre:string,rol:string,fecha_creacion:?string,foto_perfil:?string,banner_perfil:?string}|null
     */
    public function perfilPublico(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre, rol, fecha_creacion, foto_perfil, banner_perfil FROM usuario WHERE id_usuario = :id AND bloqueado = 0'
        );
        $stmt->execute([':id' => $id]);
        $f = $stmt->fetch();

        return $f === false ? null : [
            'id'             => (int) $f['id_usuario'],
            'nombre'         => $f['nombre'],
            'rol'            => $f['rol'],
            'fecha_creacion' => $f['fecha_creacion'],
            'foto_perfil'    => $f['foto_perfil'],
            'banner_perfil'  => $f['banner_perfil'],
        ];
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
