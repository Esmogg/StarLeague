<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Rol de quien mira la página, para que el menú muestre solo lo que le corresponde.
 * Es solo comodidad de la interfaz: cada acción vuelve a comprobar el permiso en el servidor.
 */
exigirMetodo('GET');
header('Cache-Control: no-store');

iniciarSesion();
$idSesion = (int) ($_SESSION['id_usuario'] ?? 0);

try {
    $rol = $idSesion > 0 ? (new UsuarioRepository())->rolDe($idSesion) : null;
    responderJson(['ok' => true, 'logueado' => $rol !== null, 'rol' => $rol]);
} catch (Throwable $e) {
    error_log('[StarLeague] rol: ' . $e->getMessage());
    responderJson(['ok' => false, 'logueado' => false, 'rol' => null], 500);
}
