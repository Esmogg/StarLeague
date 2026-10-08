<?php
declare(strict_types=1);

/** Inscripciones a torneos: ParticipaIndv (usuario) y ParticipaEquip (equipo). */
final class InscripcionRepository
{
    private const TABLAS = [
        'usuario' => ['tabla' => 'ParticipaIndv',  'columna' => 'id_usuario'],
        'equipo'  => ['tabla' => 'ParticipaEquip', 'columna' => 'id_equipo'],
    ];

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function contar(string $tipo, int $idTorneo): int
    {
        ['tabla' => $t] = self::TABLAS[$tipo];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM $t WHERE id_torneo = :id");
        $stmt->execute([':id' => $idTorneo]);
        return (int) $stmt->fetchColumn();
    }

    public function yaInscrito(string $tipo, int $idParticipante, int $idTorneo): bool
    {
        ['tabla' => $t, 'columna' => $c] = self::TABLAS[$tipo];
        $stmt = $this->db->prepare("SELECT 1 FROM $t WHERE $c = :p AND id_torneo = :t");
        $stmt->execute([':p' => $idParticipante, ':t' => $idTorneo]);
        return $stmt->fetch() !== false;
    }

    public function inscribir(string $tipo, int $idParticipante, int $idTorneo): bool
    {
        ['tabla' => $t, 'columna' => $c] = self::TABLAS[$tipo];
        $stmt = $this->db->prepare("INSERT INTO $t ($c, id_torneo) VALUES (:p, :t)");
        return $stmt->execute([':p' => $idParticipante, ':t' => $idTorneo]);
    }
}
