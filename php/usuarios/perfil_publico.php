<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Perfil público de un usuario: foto, banner, rol, desde cuándo está, equipos, inscripciones y,
 * si organizó torneos, cuáles. No incluye correo ni ningún dato privado.
 */
exigirMetodo('GET');
header('Cache-Control: no-store');

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    responderJson(['ok' => false, 'mensaje' => 'Usuario inválido.'], 400);
}

try {
    $usuario = (new UsuarioRepository())->perfilPublico($id);
    if ($usuario === null) {
        responderJson(['ok' => false, 'mensaje' => 'El usuario no existe.'], 404);
    }

    $usuario['foto_perfil']   = rutaImagenPublica($usuario['foto_perfil']);
    $usuario['banner_perfil'] = rutaImagenPublica($usuario['banner_perfil']);
    $usuario['fecha_creacion'] = $usuario['fecha_creacion'] !== null
        ? substr((string) $usuario['fecha_creacion'], 0, 10)
        : null;

    $torneos = new TorneoRepository();

    responderJson([
        'ok'            => true,
        'usuario'       => $usuario,
        'equipos'       => (new EquipoRepository())->deUsuario($id),
        'inscripciones' => $torneos->inscripcionesPublicas($id),
        'organizados'   => $usuario['rol'] === 'jugador' ? [] : $torneos->organizadosPor($id),
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] perfil_publico: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo cargar el perfil.'], 500);
}
