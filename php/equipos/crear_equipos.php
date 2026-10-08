<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responder(int $codigo, bool $ok, string $mensaje, array $advertencias = []): void {
    http_response_code($codigo);
    echo json_encode([
        'ok'      => $ok,
        'mensaje' => $mensaje,
        'data'    => ['advertencias' => $advertencias],
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un equipo.');
}

/*
 * crear-equipos.js envía los datos como JSON (fetch).
 * Si llegara un formulario clásico, se leen de $_POST.
 */
$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    $datos = $_POST;
}

$id_usuario = (int) $_SESSION['id_usuario'];
$nombre     = trim((string) ($datos['nombre'] ?? ''));
$disciplina = trim((string) ($datos['deporte'] ?? ''));
$integrantes = (isset($datos['integrantes']) && is_array($datos['integrantes']))
    ? $datos['integrantes'] : [];

$disciplinas = ['Ajedrez', 'Valorant', 'Tenis', 'League of Legends'];

if ($nombre === '' || $disciplina === '') {
    responder(400, false, 'Por favor, completa todos los campos (incluyendo la disciplina).');
}
if (mb_strlen($nombre) > 50) {
    responder(400, false, 'El nombre del equipo no puede superar los 50 caracteres.');
}
if (!in_array($disciplina, $disciplinas, true)) {
    responder(400, false, 'Disciplina inválida.');
}

require_once '../conexion.php';

try {
    $conexion->set_charset('utf8mb4');
    $conexion->begin_transaction();

    // 1. El equipo
    $stmt = $conexion->prepare("INSERT INTO Equipo (nombre, disciplina) VALUES (?, ?)");
    $stmt->bind_param('ss', $nombre, $disciplina);
    $stmt->execute();
    $id_equipo = $conexion->insert_id;
    $stmt->close();

    // 2. Quién lo creó
    $stmt = $conexion->prepare("INSERT INTO CrearEquipo (id_equipo, id_usuario) VALUES (?, ?)");
    $stmt->bind_param('ii', $id_equipo, $id_usuario);
    $stmt->execute();
    $stmt->close();

    // 3. El creador también es integrante
    $stmt = $conexion->prepare("INSERT IGNORE INTO UnirseEquipo (id_equipo, id_usuario) VALUES (?, ?)");
    $stmt->bind_param('ii', $id_equipo, $id_usuario);
    $stmt->execute();

    // 4. Los demás integrantes (solo si el usuario existe en la base)
    $advertencias = [];
    $verificar = $conexion->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ?");

    foreach ($integrantes as $integrante) {
        $id_jugador = (int) ($integrante['jugadorId'] ?? 0);
        $etiqueta   = (string) ($integrante['jugador'] ?? $id_jugador);

        if ($id_jugador <= 0) {
            continue;
        }

        $verificar->bind_param('i', $id_jugador);
        $verificar->execute();

        if ($verificar->get_result()->num_rows === 0) {
            $advertencias[] = "El jugador \"$etiqueta\" no existe y no se agregó al equipo.";
            continue;
        }

        $stmt->bind_param('ii', $id_equipo, $id_jugador);
        $stmt->execute();
    }

    $verificar->close();
    $stmt->close();

    $conexion->commit();
    $conexion->close();

    responder(200, true, '¡Equipo creado exitosamente!', $advertencias);

} catch (mysqli_sql_exception $e) {
    $conexion->rollback();

    if ($e->getCode() === 1062) {
        responder(409, false, 'El nombre del equipo ya está en uso. Por favor, elige otro.');
    }
    error_log('crear_equipos.php: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el equipo en el servidor.');
}
