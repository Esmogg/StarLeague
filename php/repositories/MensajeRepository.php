<?php
declare(strict_types=1);

/**
 * Buzón de mensajes: los participantes escriben a los organizadores (solicitudes de
 * inscripción, consultas y reportes). Todas las consultas son preparadas.
 */
final class MensajeRepository
{
    public const TIPOS = ['solicitud', 'consulta', 'reporte'];
    public const ASUNTO_MIN = 3;
    public const ASUNTO_MAX = 100;
    public const TEXTO_MIN = 10;
    public const TEXTO_MAX = 1000;
    public const MAX_POR_DIA = 5;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function crear(int $remitente, string $tipo, ?int $idTorneo, string $asunto, string $texto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Mensaje (id_remitente, tipo, id_torneo, asunto, texto, estado, fecha)
             VALUES (:r, :tipo, :t, :asunto, :texto, \'nuevo\', :fecha)'
        );
        // La fecha la pone PHP (misma zona horaria que el límite diario) y no el motor de la base.
        $stmt->execute([
            ':r' => $remitente, ':tipo' => $tipo, ':t' => $idTorneo,
            ':asunto' => $asunto, ':texto' => $texto, ':fecha' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Mensajes que mandó el usuario en las últimas 24 horas (tope contra el spam). */
    public function contarRecientes(int $remitente): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM Mensaje WHERE id_remitente = :r AND fecha >= :desde');
        $stmt->execute([':r' => $remitente, ':desde' => date('Y-m-d H:i:s', time() - 86400)]);

        return (int) $stmt->fetchColumn();
    }

    private const SELECT = 'SELECT m.id_mensaje, m.tipo, m.asunto, m.texto, m.estado, m.fecha,
                                   m.id_remitente, u.nombre AS remitente,
                                   m.id_torneo, t.nombre AS torneo, t.disciplina, t.id_usuario AS dueno
                              FROM Mensaje m
                              JOIN usuario u ON u.id_usuario = m.id_remitente
                              LEFT JOIN Torneo t ON t.id_torneo = m.id_torneo';

    private function formatear(array $f): array
    {
        return [
            'id'        => (int) $f['id_mensaje'],
            'tipo'      => $f['tipo'],
            'asunto'    => $f['asunto'],
            'texto'     => $f['texto'],
            'estado'    => $f['estado'],
            'fecha'     => $f['fecha'],
            'remitente' => ['id' => (int) $f['id_remitente'], 'nombre' => $f['remitente']],
            'torneo'    => $f['id_torneo'] === null ? null : [
                'id'        => (int) $f['id_torneo'],
                'nombre'    => $f['torneo'],
                'por_equipo' => Torneo::esPorEquipo((string) $f['disciplina']),
            ],
        ];
    }

    /** Lo que envió un usuario (para que vea el estado de sus mensajes). */
    public function enviadosPor(int $idUsuario): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE m.id_remitente = :u ORDER BY m.fecha DESC, m.id_mensaje DESC LIMIT 100');
        $stmt->execute([':u' => $idUsuario]);

        return array_map([$this, 'formatear'], $stmt->fetchAll());
    }

    /**
     * Bandeja del staff: el administrador ve todo; el organizador, lo de sus torneos y los
     * mensajes generales (sin torneo).
     */
    public function recibidos(int $idSesion, bool $esAdmin): array
    {
        if ($esAdmin) {
            $stmt = $this->db->query(self::SELECT . ' ORDER BY m.fecha DESC, m.id_mensaje DESC LIMIT 200');
        } else {
            $stmt = $this->db->prepare(
                self::SELECT . ' WHERE m.id_torneo IS NULL OR t.id_usuario = :u ORDER BY m.fecha DESC, m.id_mensaje DESC LIMIT 200'
            );
            $stmt->execute([':u' => $idSesion]);
        }

        return array_map([$this, 'formatear'], $stmt->fetchAll());
    }

    /** Un mensaje con el dueño de su torneo (null = mensaje general), o null si no existe. */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE m.id_mensaje = :id');
        $stmt->execute([':id' => $id]);
        $f = $stmt->fetch();

        return $f === false ? null : $f + ['_dueno' => $f['dueno'] === null ? null : (int) $f['dueno']];
    }

    /** ¿Puede este usuario del staff leer, atender o eliminar el mensaje? */
    public static function puedeGestionar(array $mensaje, int $idSesion, bool $esAdmin): bool
    {
        return $esAdmin || $mensaje['_dueno'] === null || $mensaje['_dueno'] === $idSesion;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare('UPDATE Mensaje SET estado = :e WHERE id_mensaje = :id')
            ->execute([':e' => $estado, ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare('DELETE FROM Mensaje WHERE id_mensaje = :id')->execute([':id' => $id]);
    }
}
