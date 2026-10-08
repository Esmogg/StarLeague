<?php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once '../conexion.php';

try {
    $conexion->set_charset('utf8mb4');

    $sql = "
        SELECT p.id_partido, p.id_torneo, p.fecha, p.resultado,
               t.nombre AS torneo, t.disciplina
        FROM Partido p
        INNER JOIN Torneo t ON t.id_torneo = p.id_torneo
        ORDER BY p.fecha DESC, p.id_partido DESC
    ";

    $resultado = $conexion->query($sql);
    $partidos = [];

    while ($f = $resultado->fetch_assoc()) {
        $partidos[] = [
            'id_partido' => (int) $f['id_partido'],
            'id_torneo'  => (int) $f['id_torneo'],
            'torneo'     => $f['torneo'],
            'disciplina' => $f['disciplina'],
            'fecha'      => $f['fecha'],
            'resultado'  => $f['resultado'],
        ];
    }

    $conexion->close();
    echo json_encode(['ok' => true, 'partidos' => $partidos], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('listar_resultados.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar los resultados.'], JSON_UNESCAPED_UNICODE);
}
