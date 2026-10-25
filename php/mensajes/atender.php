<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Marca un mensaje como atendido o nuevo. Solo organizador (de ese torneo o mensaje general) o administrador. */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos = leerCuerpo();
$id    = (int) ($datos['id_mensaje'] ?? 0);
$estado = $datos['estado'] ?? '';
if (!is_string($estado) || !in_array($estado, ['nuevo', 'atendido'], true)) {
    responder(400, false, 'Estado inválido.');
}

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

    $mensajes->cambiarEstado($id, $estado);
    registrarEvento("mensaje_$estado id=$id", 'id' . $idSesion);
    responder(200, true, 'Mensaje actualizado.');
} catch (Throwable $e) {
    error_log('[StarLeague] mensajes/atender: ' . $e->getMessage());
    responder(500, false, 'No se pudo completar la acción.');
}
