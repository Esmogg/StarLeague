<?php
declare(strict_types=1);

/** Entidad `Equipo` del modelo relacional. */
final class Equipo
{
    public const DISCIPLINAS = ['Ajedrez', 'Valorant', 'Tenis', 'League of Legends'];

    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $disciplina,
    ) {}
}
