<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$idUsuario = exigirSesion();

try {
    $usuario = (new UsuarioRepository())->buscarPorId($idUsuario);

    if (!$usuario) {
        session_unset();
        session_destroy();
        responderJson(['ok' => false, 'mensaje' => 'No se encontró la cuenta.'], 404);
    }

    $fecha = new DateTime($usuario->fechaCreacion);

    responderJson([
        'ok'            => true,
        'usuario'       => [
            'id_usuario'     => $usuario->id,
            'nombre'         => $usuario->nombre,
            'fecha_creacion' => $fecha->format('d/m/Y \a \l\a\s H:i'),
        ],
        'inscripciones' => (new TorneoRepository())->inscripcionesDeUsuario($idUsuario),
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] perfil: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo cargar el perfil.'], 500);
}
