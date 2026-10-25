<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Asigna o revoca un rol (jugador, organizador, admin) a otro usuario. Solo administradores.
 * Un administrador no puede cambiar su propio rol (así nunca se queda sin acceso) y siempre
 * tiene que quedar al menos un administrador.
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
$rol       = $datos['rol'] ?? '';

try {
    $usuarios = new UsuarioRepository();

    // Primero el permiso y recién después los datos.
    if (!$usuarios->esAdmin($idSesion)) {
        registrarEvento('cambiar_rol_sin_permiso', 'id' . $idSesion);
        responder(403, false, 'Solo los administradores pueden cambiar roles.');
    }
    if ($idUsuario <= 0 || !is_string($rol) || !in_array($rol, UsuarioRepository::ROLES, true)) {
        responder(400, false, 'Datos inválidos.');
    }
    if ($idUsuario === $idSesion) {
        responder(409, false, 'No puedes cambiar tu propio rol.');
    }

    $actual = $usuarios->rolDe($idUsuario);
    if ($actual === null) {
        responder(404, false, 'El usuario no existe.');
    }
    if ($actual === $rol) {
        responder(200, true, 'El usuario ya tenía ese rol.');
    }
    if ($actual === 'admin' && $usuarios->contarAdmins() <= 1) {
        responder(409, false, 'Tiene que quedar al menos un administrador.');
    }

    $usuarios->cambiarRol($idUsuario, $rol);

    registrarEvento("rol_cambiado objetivo=$idUsuario de=$actual a=$rol", 'id' . $idSesion);
    responder(200, true, 'Rol actualizado.');
} catch (Throwable $e) {
    error_log('[StarLeague] admin/cambiar_rol: ' . $e->getMessage());
    responder(500, false, 'No se pudo cambiar el rol.');
}
