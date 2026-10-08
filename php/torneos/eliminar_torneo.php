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
    responder(401, false, 'Debes iniciar sesión para eliminar un torneo.');
}

$datos = json_decode(file_get_contents('php://input'), true);
$id_torneo = is_array($datos) ? (int) ($datos['id_torneo'] ?? 0) : 0;

if ($id_torneo <= 0) {
    responder(400, false, 'Torneo inválido.');
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

    // Dueño del torneo
    $stmt = $conexion->prepare("SELECT id_usuario FROM Torneo WHERE id_torneo = ?");
    $stmt->bind_param("i", $id_torneo);
    $stmt->execute();
    $torneo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$torneo) {
        responder(404, false, 'El torneo no existe.');
    }

    $es_admin = ($usuario && $usuario['rol'] === 'admin');
    $es_dueno = ((int) $torneo['id_usuario'] === $id_sesion);

    if (!$es_admin && !$es_dueno) {
        responder(403, false, 'No tienes permiso para eliminar este torneo.');
    }

    // Las inscripciones y los partidos se borran solos (ON DELETE CASCADE)
    $stmt = $conexion->prepare("DELETE FROM Torneo WHERE id_torneo = ?");
    $stmt->bind_param("i", $id_torneo);
    $stmt->execute();
    $stmt->close();
    $conexion->close();

    responder(200, true, 'Torneo eliminado.');

} catch (Throwable $e) {
    error_log('eliminar_torneo.php: ' . $e->getMessage());
    responder(500, false, 'No se pudo eliminar el torneo.');
}
