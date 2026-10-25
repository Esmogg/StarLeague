<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Elimina una cuenta. Solo administradores. No se puede eliminar la propia cuenta ni a otro
 * administrador, ni a quien tenga torneos creados (se borrarían junto con la cuenta: en ese
 * caso conviene bloquearla o eliminar antes los torneos).
 */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos     = leerCuerpo();
$idUsuario = (int) ($datos['id_usuario'] ?? 0);

try {
    $usuarios = new UsuarioRepository();

    if (!$usuarios->esAdmin($idSesion)) {
        registrarEvento('eliminar_usuario_sin_permiso', 'id' . $idSesion);
        responder(403, false, 'Solo los administradores pueden eliminar usuarios.');
    }
    if ($idUsuario <= 0) {
        responder(400, false, 'Datos inválidos.');
    }
    if ($idUsuario === $idSesion) {
        responder(409, false, 'No puedes eliminar tu propia cuenta.');
    }
    $rol = $usuarios->rolDe($idUsuario);
    if ($rol === null) {
        responder(404, false, 'El usuario no existe.');
    }
    if ($rol === 'admin') {
        responder(409, false, 'No se puede eliminar a un administrador: primero cámbiale el rol.');
    }
    $torneos = $usuarios->contarTorneosCreados($idUsuario);
    if ($torneos > 0) {
        responder(409, false, "El usuario organiza $torneos torneo(s) y se borrarían con la cuenta. Bloquéalo, o elimina antes esos torneos.");
    }

    $usuarios->eliminar($idUsuario);

    registrarEvento("usuario_eliminado objetivo=$idUsuario", 'id' . $idSesion);
    responder(200, true, 'Usuario eliminado.');
} catch (Throwable $e) {
    error_log('[StarLeague] admin/eliminar_usuario: ' . $e->getMessage());
    responder(500, false, 'No se pudo eliminar el usuario.');
}
