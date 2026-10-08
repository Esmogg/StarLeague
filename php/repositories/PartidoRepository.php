<?php
declare(strict_types=1);

final class PartidoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /** Todos los partidos con el nombre y la disciplina de su torneo (más recientes primero). */
    public function listarConTorneo(): array
    {
        $filas = $this->db->query(
            'SELECT p.id_partido, p.id_torneo, p.fecha, p.resultado,
                    t.nombre AS torneo, t.disciplina
               FROM Partido p
               JOIN Torneo t ON t.id_torneo = p.id_torneo
              ORDER BY p.fecha DESC, p.id_partido DESC'
        )->fetchAll();

        foreach ($filas as &$f) {
            $f['id_partido'] = (int) $f['id_partido'];
            $f['id_torneo']  = (int) $f['id_torneo'];
        }
        return $filas;
    }
}
