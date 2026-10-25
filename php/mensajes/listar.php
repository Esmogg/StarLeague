<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * GET ?bandeja=enviados  -> lo que mandó el usuario (cualquier usuario con sesión)
 * GET ?bandeja=recibidos -> bandeja del staff (organizador o administrador)
 */
exigirMetodo('GET');
header('Cache-Control: no-store');
$idSesion = exigirSesion();
$bandeja  = $_GET['bandeja'] ?? 'enviados';

try {
    $mensajes = new MensajeRepository();

    if ($bandeja === 'recibidos') {
        $rol = (new UsuarioRepository())->rolDe($idSesion);
        if (!in_array($rol, ['admin', 'organizador'], true)) {
            registrarEvento('mensajes_bandeja_sin_permiso', 'id' . $idSesion);
            responderJson(['ok' => false, 'mensaje' => 'Solo los organizadores y administradores ven la bandeja.'], 403);
        }
        responderJson(['ok' => true, 'mensajes' => $mensajes->recibidos($idSesion, $rol === 'admin')]);
    }

    responderJson(['ok' => true, 'mensajes' => $mensajes->enviadosPor($idSesion)]);
} catch (Throwable $e) {
    error_log('[StarLeague] mensajes/listar: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los mensajes.'], 500);
}
