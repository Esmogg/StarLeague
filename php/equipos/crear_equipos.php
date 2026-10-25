<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Creación de equipo: JSON por fetch() desde crear-equipos.js (o formulario clásico). */
function responder(int $codigo, bool $ok, string $mensaje, array $advertencias = []): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje, 'data' => ['advertencias' => $advertencias]], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un equipo.');
}

// Como en crear_torneos: primero el permiso y recién después los datos.
try {
    $rol = (new UsuarioRepository())->rolDe((int) $_SESSION['id_usuario']);
} catch (Throwable $e) {
    error_log('[StarLeague] crear_equipos rol: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el equipo en el servidor.');
}
if (!in_array($rol, ['admin', 'organizador'], true)) {
    responder(403, false, 'No tienes permiso para crear equipos.');
}

$datos      = leerCuerpo();
$idCreador  = (int) $_SESSION['id_usuario'];
$nombre     = trim((string) ($datos['nombre'] ?? ''));
$disciplina = trim((string) ($datos['deporte'] ?? ''));

if ($disciplina === '') {
    responder(400, false, 'Por favor, completa todos los campos (incluyendo la disciplina).');
}
// Mismas reglas que js/validaciones.js (obligatorio, hasta 50 caracteres, solo letras, números y espacios)
if (($error = Validador::nombre($nombre, 'equipo')) !== null) {
    responder(400, false, $error);
}
if (!in_array($disciplina, Equipo::DISCIPLINAS, true)) {
    responder(400, false, 'Disciplina inválida.');
}

// integrantes: [{jugadorId, jugador, rol}] -> ids válidos + etiqueta para las advertencias
$pedidos = [];
foreach ((array) ($datos['integrantes'] ?? []) as $i) {
    $id = (int) ($i['jugadorId'] ?? 0);
    if ($id > 0) {
        $pedidos[$id] = recortar((string) ($i['jugador'] ?? $id), 50);
    }
}

$rutaLogo = $rutaBanner = null;
try {
    $existentes   = (new UsuarioRepository())->filtrarExistentes(array_keys($pedidos));
    $advertencias = [];
    foreach ($pedidos as $id => $etiqueta) {
        if (!in_array($id, $existentes, true)) {
            $advertencias[] = "El jugador \"$etiqueta\" no existe y no se agregó al equipo.";
        }
    }

    $rutaLogo   = guardarImagenTorneo($datos['logo'] ?? null, 'Logo', 2 * 1024 * 1024, 'equipos');
    $rutaBanner = guardarImagenTorneo($datos['banner'] ?? null, 'Banner', 5 * 1024 * 1024, 'equipos');

    (new EquipoRepository())->crear(new Equipo(null, $nombre, $disciplina), $idCreador, $existentes, $rutaLogo, $rutaBanner);

    registrarEvento('equipo_creado', 'id' . $idCreador);
    responder(200, true, '¡Equipo creado exitosamente!', $advertencias);
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
    error_log('[StarLeague] crear_equipos: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el equipo en el servidor.');
}
