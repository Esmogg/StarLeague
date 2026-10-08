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
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un torneo.');
}

/*
 * crear-torneos.js envía los datos como JSON (fetch).
 * Si llegara un formulario clásico, se leen de $_POST.
 */
$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    $datos = $_POST;
}

$id_usuario        = (int) $_SESSION['id_usuario'];
$nombre            = trim((string) ($datos['nombre'] ?? ''));
$descripcion       = trim((string) ($datos['descripcion'] ?? ''));
$categoria         = trim((string) ($datos['categoria'] ?? ''));
$disciplina        = trim((string) ($datos['deporte'] ?? ''));
$formato           = trim((string) ($datos['formato'] ?? ''));
$cantParticipantes = (int) ($datos['cantidadParticipantes'] ?? 0);
$fecha_inicio      = trim((string) ($datos['fechaInicio'] ?? ''));
$fecha_fin         = trim((string) ($datos['fechaFin'] ?? ''));

// El catálogo del frontend usa tilde; la base de datos no
if ($formato === 'Eliminación Directa') {
    $formato = 'Eliminacion Directa';
}

$categorias  = ['E-Sports', 'Tradicional'];
$disciplinas = ['Ajedrez', 'Valorant', 'Tenis', 'League of Legends'];
$formatos    = ['Liga', 'Eliminacion Directa', 'Sistema Suizo'];

function fecha_valida(string $f): bool {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

if ($nombre === '' || $categoria === '' || $disciplina === '' || $formato === '') {
    responder(400, false, 'Por favor, completa todos los campos obligatorios.');
}
if (mb_strlen($nombre) > 50) {
    responder(400, false, 'El nombre del torneo no puede superar los 50 caracteres.');
}
if (mb_strlen($descripcion) > 500) {
    responder(400, false, 'La descripción no puede superar los 500 caracteres.');
}
if (!in_array($categoria, $categorias, true)
    || !in_array($disciplina, $disciplinas, true)
    || !in_array($formato, $formatos, true)) {
    responder(400, false, 'Categoría, disciplina o formato inválido.');
}
if ($cantParticipantes < 2) {
    responder(400, false, 'La cantidad de participantes debe ser al menos 2.');
}
if (!fecha_valida($fecha_inicio) || !fecha_valida($fecha_fin)) {
    responder(400, false, 'Debes indicar una fecha de inicio y una de fin válidas.');
}
if ($fecha_fin < $fecha_inicio) {
    responder(400, false, 'La fecha de fin no puede ser anterior a la de inicio.');
}

require_once '../conexion.php';

try {
    $conexion->set_charset('utf8mb4');

    $sql = "INSERT INTO Torneo
            (id_usuario, nombre, descripcion, categoria, disciplina, formato,
             cantParticipantes, fecha_inicio, fecha_fin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        'isssssiss',
        $id_usuario, $nombre, $descripcion, $categoria, $disciplina,
        $formato, $cantParticipantes, $fecha_inicio, $fecha_fin
    );
    $stmt->execute();
    $stmt->close();
    $conexion->close();

    responder(200, true, '¡Torneo creado exitosamente!');

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        responder(409, false, 'El nombre del torneo ya está en uso. Por favor, elige otro.');
    }
    error_log('crear_torneos.php: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el torneo en el servidor.');
}
