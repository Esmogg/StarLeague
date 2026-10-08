<?php
declare(strict_types=1);

/** Entidad `Partido` del modelo relacional. */
final class Partido
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $idTorneo,
        public readonly string $fecha,
        public readonly string $resultado,
    ) {}
}
