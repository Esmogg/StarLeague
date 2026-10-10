<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Creación de torneo: JSON por fetch() desde crear-torneos.js (o formulario clásico). */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

function fechaValida(string $f): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

exigirMetodo('POST');
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un torneo.');
}

$datos       = leerCuerpo();
$idCreador   = (int) $_SESSION['id_usuario'];
$nombre      = trim((string) ($datos['nombre'] ?? ''));
$descripcion = trim((string) ($datos['descripcion'] ?? ''));
$categoria   = trim((string) ($datos['categoria'] ?? ''));
$disciplina  = trim((string) ($datos['deporte'] ?? ''));
$formato     = Torneo::normalizarFormato(trim((string) ($datos['formato'] ?? '')));
$cant        = (int) ($datos['cantidadParticipantes'] ?? 0);
$inicio      = trim((string) ($datos['fechaInicio'] ?? ''));
$fin         = trim((string) ($datos['fechaFin'] ?? ''));

if ($nombre === '' || $categoria === '' || $disciplina === '' || $formato === '') {
    responder(400, false, 'Por favor, completa todos los campos obligatorios.');
}
if (longitud($nombre) > 50) {
    responder(400, false, 'El nombre del torneo no puede superar los 50 caracteres.');
}
if (longitud($descripcion) > 500) {
    responder(400, false, 'La descripción no puede superar los 500 caracteres.');
}
if (
    !in_array($categoria, Torneo::CATEGORIAS, true)
    || !in_array($disciplina, Torneo::DISCIPLINAS, true)
    || !in_array($formato, Torneo::FORMATOS, true)
) {
    responder(400, false, 'Categoría, disciplina o formato inválido.');
}
if ($cant < 2) {
    responder(400, false, 'La cantidad de participantes debe ser al menos 2.');
}
if (!fechaValida($inicio) || !fechaValida($fin)) {
    responder(400, false, 'Debes indicar una fecha de inicio y una de fin válidas.');
}
if ($fin < $inicio) {
    responder(400, false, 'La fecha de fin no puede ser anterior a la de inicio.');
}

try {
    (new TorneoRepository())->crear(
        new Torneo(null, $idCreador, $nombre, $descripcion, $categoria, $disciplina, $formato, $cant, $inicio, $fin)
    );
    responder(200, true, '¡Torneo creado exitosamente!');
} catch (DomainException $e) {
    responder(409, false, $e->getMessage());
} catch (Throwable $e) {
    error_log('[StarLeague] crear_torneos: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el torneo en el servidor.');
}
