<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Búsqueda pública de usuarios por nombre. Solo devuelve datos públicos (nombre, rol y foto):
 * nunca el correo. Máximo 30 resultados; sin texto, los primeros por orden alfabético.
 */
exigirMetodo('GET');
header('Cache-Control: no-store');

$texto = trim((string) ($_GET['q'] ?? ''));
if (longitud($texto) > 50) {
    responderJson(['ok' => false, 'mensaje' => 'La búsqueda es demasiado larga.'], 400);
}

try {
    $limite   = 30;
    $usuarios = (new UsuarioRepository())->buscarPublicos($texto, $limite + 1);
    $hayMas   = count($usuarios) > $limite;

    foreach ($usuarios as &$u) {
        $u['foto_perfil'] = rutaImagenPublica($u['foto_perfil']);
    }
    unset($u);

    responderJson(['ok' => true, 'usuarios' => array_slice($usuarios, 0, $limite), 'hay_mas' => $hayMas]);
} catch (Throwable $e) {
    error_log('[StarLeague] buscar_usuarios: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo hacer la búsqueda.'], 500);
}
