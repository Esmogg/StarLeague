<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once '../conexion.php';

try {
    $conexion->set_charset('utf8mb4');

    // Quién está mirando (para decidir si puede eliminar)
    $id_sesion = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    $es_admin = false;

    if ($id_sesion > 0) {
        $stmt = $conexion->prepare("SELECT rol FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_sesion);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $es_admin = ($fila && $fila['rol'] === 'admin');
    }

    $sql = "
        SELECT t.id_torneo, t.id_usuario, t.nombre, t.descripcion, t.categoria,
               t.disciplina, t.formato, t.cantParticipantes,
               t.fecha_inicio, t.fecha_fin,
               u.nombre AS organizador,
               (SELECT COUNT(*) FROM ParticipaIndv pind WHERE pind.id_torneo = t.id_torneo) AS insc_indv,
               (SELECT COUNT(*) FROM ParticipaEquip peq  WHERE peq.id_torneo  = t.id_torneo) AS insc_equip
        FROM Torneo t
        INNER JOIN usuario u ON u.id_usuario = t.id_usuario
        ORDER BY t.fecha_inicio ASC, t.id_torneo ASC
    ";

    $resultado = $conexion->query($sql);
    $torneos = [];

    while ($f = $resultado->fetch_assoc()) {
        $es_equipo = in_array($f['disciplina'], ['Valorant', 'League of Legends'], true);
        $cupo = (int) $f['cantParticipantes'];

        $formato = $f['formato'];
        if ($formato === 'Eliminacion Directa') {
            $formato = 'Eliminación Directa';
        }

        $torneo = [
            'id'               => (int) $f['id_torneo'],
            'id_torneo'        => (int) $f['id_torneo'],
            'nombre'           => $f['nombre'],
            'descripcion'      => $f['descripcion'],
            'categoria'        => $f['categoria'],
            'disciplina'       => $f['disciplina'],
            'formato'          => $formato,
            'tipoParticipante' => $es_equipo ? 'Equipos' : 'Jugadores individuales',
            'inscriptos'       => (int) ($es_equipo ? $f['insc_equip'] : $f['insc_indv']),
            'fecha_inicio'     => $f['fecha_inicio'],
            'fecha_fin'        => $f['fecha_fin'],
            'organizador'      => $f['organizador'],
            'puede_eliminar'   => $es_admin || ($id_sesion > 0 && (int) $f['id_usuario'] === $id_sesion),
        ];

        // ver-torneos.js muestra "N equipos" o "N jugadores" según la clave
        if ($es_equipo) {
            $torneo['cantidadEquipos'] = $cupo;
        } else {
            $torneo['cantidadJugadores'] = $cupo;
        }

        $torneos[] = $torneo;
    }

    $conexion->close();
    echo json_encode(['ok' => true, 'torneos' => $torneos], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('listar_torneos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar los torneos.'], JSON_UNESCAPED_UNICODE);
}
