<?php
declare(strict_types=1);

/** Entidad `usuario` del modelo relacional. */
final class Usuario
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $email,
        public readonly string $contrasenaHash = '',
        public readonly string $rol = 'jugador',
        public readonly ?string $fechaCreacion = null,
    ) {}

    public static function desdeFila(array $f): self
    {
        return new self(
            (int) $f['id_usuario'],
            $f['nombre'],
            $f['email'] ?? '',
            $f['contrasena'] ?? '',
            $f['rol'] ?? 'jugador',
            $f['fecha_creacion'] ?? null,
        );
    }
}
