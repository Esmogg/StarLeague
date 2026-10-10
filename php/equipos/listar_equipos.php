<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Listado público de equipos para buscar-equipos.js (con permiso de eliminar según quién mira). */
iniciarSesion();
$idSesion = (int) ($_SESSION['id_usuario'] ?? 0);

try {
    $esAdmin = $idSesion > 0 && (new UsuarioRepository())->esAdmin($idSesion);

    responderJson([
        'ok'      => true,
        'equipos' => (new EquipoRepository())->listarDetallado($idSesion, $esAdmin),
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_equipos: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los equipos.'], 500);
}
