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

    /** Nombres del torneo y del participante para el comprobante firmado. */
    public function datosComprobante(string $tipo, int $idParticipante, int $idTorneo): ?array
    {
        $origen = $tipo === 'equipo'
            ? ['tabla' => 'Equipo',  'id' => 'id_equipo']
            : ['tabla' => 'usuario', 'id' => 'id_usuario'];

        $stmt = $this->db->prepare(
            "SELECT t.nombre AS torneo, p.nombre AS participante
               FROM Torneo t, {$origen['tabla']} p
              WHERE t.id_torneo = :t AND p.{$origen['id']} = :p"
        );
        $stmt->execute([':t' => $idTorneo, ':p' => $idParticipante]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function inscribir(string $tipo, int $idParticipante, int $idTorneo): bool
    {
        ['tabla' => $t, 'columna' => $c] = self::TABLAS[$tipo];
        $stmt = $this->db->prepare("INSERT INTO $t ($c, id_torneo) VALUES (:p, :t)");
        return $stmt->execute([':p' => $idParticipante, ':t' => $idTorneo]);
    }

    /** Nombres de los inscriptos al torneo (usuarios o equipos, según el tipo), en orden alfabético. */
    public function nombres(string $tipo, int $idTorneo): array
    {
        $origen = $tipo === 'equipo'
            ? 'SELECT e.nombre FROM ParticipaEquip pe JOIN Equipo e ON e.id_equipo = pe.id_equipo WHERE pe.id_torneo = :t'
            : 'SELECT u.nombre FROM ParticipaIndv pi JOIN usuario u ON u.id_usuario = pi.id_usuario WHERE pi.id_torneo = :t';

        $stmt = $this->db->prepare($origen . ' ORDER BY 1 ASC');
        $stmt->execute([':t' => $idTorneo]);
        return array_column($stmt->fetchAll(), 'nombre');
    }
}
