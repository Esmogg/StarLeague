<?php
declare(strict_types=1);

/** Entidad `Torneo` del modelo relacional. */
final class Torneo
{
    public const CATEGORIAS  = ['E-Sports', 'Tradicional'];
    public const DISCIPLINAS = ['Ajedrez', 'Valorant', 'Tenis', 'League of Legends'];
    public const FORMATOS    = ['Liga', 'Eliminacion Directa', 'Sistema Suizo'];
    /** Disciplinas que se inscriben por equipo (el resto es individual). */
    public const POR_EQUIPO  = ['Valorant', 'League of Legends'];

    public function __construct(
        public readonly ?int $id,
        public readonly int $idCreador,
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly string $categoria,
        public readonly string $disciplina,
        public readonly string $formato,
        public readonly int $cantParticipantes,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
    ) {}

    /** El catálogo del frontend usa "Eliminación Directa"; el ENUM, sin tilde. */
    public static function normalizarFormato(string $formato): string
    {
        return str_ireplace('ó', 'o', $formato);
    }

    public static function esPorEquipo(string $disciplina): bool
    {
        return in_array($disciplina, self::POR_EQUIPO, true);
    }
}
