<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Listado público de torneos para ver-torneos.js (con permiso de eliminar según quién mira). */
iniciarSesion();
$idSesion = (int) ($_SESSION['id_usuario'] ?? 0);

try {
    $esAdmin = $idSesion > 0 && (new UsuarioRepository())->esAdmin($idSesion);

    responderJson([
        'ok'      => true,
        'torneos' => (new TorneoRepository())->listarDetallado($idSesion, $esAdmin),
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_torneos: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los torneos.'], 500);
}
