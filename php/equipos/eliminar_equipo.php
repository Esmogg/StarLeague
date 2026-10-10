<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Elimina un equipo: solo el administrador o quien lo creó. */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Debes iniciar sesión para eliminar un equipo.');
}

$idSesion = (int) $_SESSION['id_usuario'];
$idEquipo = (int) (leerCuerpo()['id_equipo'] ?? 0);
if ($idEquipo <= 0) {
    responder(400, false, 'Equipo inválido.');
}

try {
    $equipos = new EquipoRepository();

    if (!$equipos->existe($idEquipo)) {
        responder(404, false, 'El equipo no existe.');
    }
    if (!$equipos->esCreador($idEquipo, $idSesion) && !(new UsuarioRepository())->esAdmin($idSesion)) {
        responder(403, false, 'No tienes permiso para eliminar este equipo.');
    }

    $equipos->eliminar($idEquipo);
    responder(200, true, 'Equipo eliminado.');
} catch (Throwable $e) {
    error_log('[StarLeague] eliminar_equipo: ' . $e->getMessage());
    responder(500, false, 'No se pudo eliminar el equipo.');
}
