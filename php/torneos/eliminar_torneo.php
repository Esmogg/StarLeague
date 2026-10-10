<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Elimina un torneo: solo el administrador o quien lo creó. */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Debes iniciar sesión para eliminar un torneo.');
}

$idSesion = (int) $_SESSION['id_usuario'];
$idTorneo = (int) (leerCuerpo()['id_torneo'] ?? 0);
if ($idTorneo <= 0) {
    responder(400, false, 'Torneo inválido.');
}

try {
    $torneos = new TorneoRepository();
    $dueno   = $torneos->duenoDe($idTorneo);

    if ($dueno === null) {
        responder(404, false, 'El torneo no existe.');
    }
    if ($dueno !== $idSesion && !(new UsuarioRepository())->esAdmin($idSesion)) {
        responder(403, false, 'No tienes permiso para eliminar este torneo.');
    }

    $torneos->eliminar($idTorneo);
    responder(200, true, 'Torneo eliminado.');
} catch (Throwable $e) {
    error_log('[StarLeague] eliminar_torneo: ' . $e->getMessage());
    responder(500, false, 'No se pudo eliminar el torneo.');
}
