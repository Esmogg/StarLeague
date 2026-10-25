<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Elimina un mensaje de la bandeja. Solo organizador (de ese torneo o mensaje general) o administrador. */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos = leerCuerpo();
$id    = (int) ($datos['id_mensaje'] ?? 0);


try {
    $rol = (new UsuarioRepository())->rolDe($idSesion);
    if (!in_array($rol, ['admin', 'organizador'], true)) {
        registrarEvento('mensajes_gestion_sin_permiso', 'id' . $idSesion);
        responder(403, false, 'Solo los organizadores y administradores pueden gestionar mensajes.');
    }
    if ($id <= 0) {
        responder(400, false, 'Mensaje inválido.');
    }

    $mensajes = new MensajeRepository();
    $mensaje  = $mensajes->obtener($id);
    if ($mensaje === null) {
        responder(404, false, 'El mensaje no existe.');
    }
    if (!MensajeRepository::puedeGestionar($mensaje, $idSesion, $rol === 'admin')) {
        registrarEvento('mensaje_ajeno', 'id' . $idSesion);
        responder(403, false, 'Ese mensaje no es de uno de tus torneos.');
    }

    $mensajes->eliminar($id);
    registrarEvento("mensaje_eliminado id=$id", 'id' . $idSesion);
    responder(200, true, 'Mensaje eliminado.');
} catch (Throwable $e) {
    error_log('[StarLeague] mensajes/eliminar: ' . $e->getMessage());
    responder(500, false, 'No se pudo completar la acción.');
}
