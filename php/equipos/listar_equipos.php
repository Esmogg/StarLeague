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

    // Equipos con quién los creó
    $sql = "
        SELECT e.id_equipo, e.nombre, e.disciplina,
               ce.id_usuario AS id_creador, uc.nombre AS creador
        FROM Equipo e
        LEFT JOIN CrearEquipo ce ON ce.id_equipo = e.id_equipo
        LEFT JOIN usuario uc ON uc.id_usuario = ce.id_usuario
        ORDER BY e.nombre ASC
    ";
    $resultado = $conexion->query($sql);

    $equipos = [];
    while ($f = $resultado->fetch_assoc()) {
        $id = (int) $f['id_equipo'];

        // Si por algún motivo hubiera dos creadores, se queda con el primero
        if (isset($equipos[$id])) {
            continue;
        }

        $equipos[$id] = [
            'id'             => $id,
            'id_equipo'      => $id,
            'nombre'         => $f['nombre'],
            'deporte'        => $f['disciplina'],
            'creador'        => $f['creador'],
            'integrantes'    => [],
            'puede_eliminar' => $es_admin
                || ($id_sesion > 0 && (int) $f['id_creador'] === $id_sesion),
            '_id_creador'    => $f['id_creador'] !== null ? (int) $f['id_creador'] : 0,
        ];
    }

    // Integrantes de todos los equipos
    $sql = "
        SELECT ue.id_equipo, u.id_usuario, u.nombre
        FROM UnirseEquipo ue
        INNER JOIN usuario u ON u.id_usuario = ue.id_usuario
        ORDER BY ue.id_equipo ASC, u.nombre ASC
    ";
    $resultado = $conexion->query($sql);

    while ($f = $resultado->fetch_assoc()) {
        $id = (int) $f['id_equipo'];
        if (!isset($equipos[$id])) {
            continue;
        }

        $equipos[$id]['integrantes'][] = [
            'jugador' => $f['nombre'],
            'rol'     => ((int) $f['id_usuario'] === $equipos[$id]['_id_creador']) ? 'Creador' : 'Jugador',
        ];
    }

    // _id_creador era solo de uso interno
    foreach ($equipos as &$equipo) {
        unset($equipo['_id_creador']);
    }
    unset($equipo);

    $conexion->close();
    echo json_encode(['ok' => true, 'equipos' => array_values($equipos)], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('listar_equipos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar los equipos.'], JSON_UNESCAPED_UNICODE);
}
