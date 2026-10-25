<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Creación de torneo: JSON por fetch() desde crear-torneos.js (o formulario clásico). */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un torneo.');
}

// Primero se comprueba el permiso y recién después los datos: quien no puede crear torneos no recibe detalles de validación.
try {
    $rol = (new UsuarioRepository())->rolDe((int) $_SESSION['id_usuario']);
} catch (Throwable $e) {
    error_log('[StarLeague] crear_torneos rol: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el torneo en el servidor.');
}
if (!in_array($rol, ['admin', 'organizador'], true)) {
    responder(403, false, 'No tienes permiso para crear torneos.');
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

if ($categoria === '' || $disciplina === '' || $formato === '') {
    responder(400, false, 'Por favor, completa todos los campos obligatorios.');
}
// Mismas reglas que js/validaciones.js; aquí se vuelven a comprobar porque el cliente no es confiable.
$error = Validador::nombre($nombre, 'torneo')
    ?? Validador::descripcion($descripcion)
    ?? Validador::participantes($datos['cantidadParticipantes'] ?? '')
    ?? Validador::fechas($inicio, $fin);
if ($error !== null) {
    responder(400, false, $error);
}
if (
    !in_array($categoria, Torneo::CATEGORIAS, true)
    || !in_array($disciplina, Torneo::DISCIPLINAS, true)
    || !in_array($formato, Torneo::FORMATOS, true)
) {
    responder(400, false, 'Categoría, disciplina o formato inválido.');
}

$rutaLogo = $rutaBanner = null;
try {
    $rutaLogo   = guardarImagenTorneo($datos['logo'] ?? null, 'Logo', 2 * 1024 * 1024);
    $rutaBanner = guardarImagenTorneo($datos['banner'] ?? null, 'Banner', 5 * 1024 * 1024);
    (new TorneoRepository())->crear(
        new Torneo(null, $idCreador, $nombre, $descripcion, $categoria, $disciplina, $formato, $cant, $inicio, $fin),
        $rutaLogo,
        $rutaBanner
    );
    registrarEvento('torneo_creado', 'id' . $idCreador);
    responder(200, true, '¡Torneo creado exitosamente!');
} catch (InvalidArgumentException $e) {
    borrarImagenTorneo($rutaLogo);
    responder(400, false, $e->getMessage());
} catch (DomainException $e) {
    borrarImagenTorneo($rutaLogo);
    borrarImagenTorneo($rutaBanner);
    responder(409, false, $e->getMessage());
} catch (Throwable $e) {
    borrarImagenTorneo($rutaLogo);
    borrarImagenTorneo($rutaBanner);
    error_log('[StarLeague] crear_torneos: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el torneo en el servidor.');
}
