<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responder(int $codigo, bool $ok, string $mensaje): void {
    http_response_code($codigo);
    echo json_encode(['ok' => $ok, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Debes iniciar sesión para eliminar un equipo.');
}

$datos = json_decode(file_get_contents('php://input'), true);
$id_equipo = is_array($datos) ? (int) ($datos['id_equipo'] ?? 0) : 0;

if ($id_equipo <= 0) {
    responder(400, false, 'Equipo inválido.');
}

require_once '../conexion.php';

try {
    $id_sesion = (int) $_SESSION['id_usuario'];

    // Rol de quien pide la eliminación
    $stmt = $conexion->prepare("SELECT rol FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_sesion);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ¿Existe el equipo y quién lo creó?
    $stmt = $conexion->prepare("
        SELECT e.id_equipo, ce.id_usuario AS id_creador
        FROM Equipo e
        LEFT JOIN CrearEquipo ce ON ce.id_equipo = e.id_equipo
        WHERE e.id_equipo = ?
    ");
    $stmt->bind_param("i", $id_equipo);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$filas) {
        responder(404, false, 'El equipo no existe.');
    }

    $es_admin = ($usuario && $usuario['rol'] === 'admin');
    $es_creador = false;
    foreach ($filas as $fila) {
        if ((int) $fila['id_creador'] === $id_sesion) {
            $es_creador = true;
        }
    }

    if (!$es_admin && !$es_creador) {
        responder(403, false, 'No tienes permiso para eliminar este equipo.');
    }

    // Integrantes, creador e inscripciones se borran solos (ON DELETE CASCADE)
    $stmt = $conexion->prepare("DELETE FROM Equipo WHERE id_equipo = ?");
    $stmt->bind_param("i", $id_equipo);
    $stmt->execute();
    $stmt->close();
    $conexion->close();

    responder(200, true, 'Equipo eliminado.');

} catch (Throwable $e) {
    error_log('eliminar_equipo.php: ' . $e->getMessage());
    responder(500, false, 'No se pudo eliminar el equipo.');
}
