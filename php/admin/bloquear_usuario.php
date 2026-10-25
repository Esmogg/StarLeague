<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Bloquea o desbloquea una cuenta. Solo administradores. Un administrador no puede
 * bloquearse a sí mismo ni bloquear a otro administrador (primero hay que cambiarle el rol).
 * Una cuenta bloqueada no puede iniciar sesión y su sesión abierta deja de valer.
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
$bloquear  = $datos['bloqueado'] ?? null;

try {
    $usuarios = new UsuarioRepository();

    if (!$usuarios->esAdmin($idSesion)) {
        registrarEvento('bloquear_sin_permiso', 'id' . $idSesion);
        responder(403, false, 'Solo los administradores pueden bloquear usuarios.');
    }
    if ($idUsuario <= 0 || !is_bool($bloquear)) {
        responder(400, false, 'Datos inválidos.');
    }
    if ($idUsuario === $idSesion) {
        responder(409, false, 'No puedes bloquear tu propia cuenta.');
    }
    $rol = $usuarios->rolDe($idUsuario);
    if ($rol === null) {
        responder(404, false, 'El usuario no existe.');
    }
    if ($rol === 'admin') {
        responder(409, false, 'No se puede bloquear a un administrador: primero cámbiale el rol.');
    }

    $usuarios->cambiarBloqueo($idUsuario, $bloquear);

    registrarEvento(($bloquear ? 'usuario_bloqueado' : 'usuario_desbloqueado') . " objetivo=$idUsuario", 'id' . $idSesion);
    responder(200, true, $bloquear ? 'Usuario bloqueado.' : 'Usuario desbloqueado.');
} catch (Throwable $e) {
    error_log('[StarLeague] admin/bloquear_usuario: ' . $e->getMessage());
    responder(500, false, 'No se pudo cambiar el estado de la cuenta.');
}
