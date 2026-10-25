<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Lista de usuarios con su rol. Solo administradores. */
exigirMetodo('GET');
header('Cache-Control: no-store');

iniciarSesion();
$idSesion = (int) ($_SESSION['id_usuario'] ?? 0);
if ($idSesion <= 0) {
    responderJson(['ok' => false, 'mensaje' => 'Debes iniciar sesión.'], 401);
}

try {
    $usuarios = new UsuarioRepository();

    if (!$usuarios->esAdmin($idSesion)) {
        registrarEvento('admin_listar_sin_permiso', 'id' . $idSesion);
        responderJson(['ok' => false, 'mensaje' => 'Solo los administradores pueden ver esta sección.'], 403);
    }

    responderJson(['ok' => true, 'yo' => $idSesion, 'usuarios' => $usuarios->listarConRol()]);
} catch (Throwable $e) {
    error_log('[StarLeague] admin/listar_usuarios: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo cargar la lista de usuarios.'], 500);
}
