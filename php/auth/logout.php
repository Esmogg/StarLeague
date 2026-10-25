<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Cierra la sesión: vacía $_SESSION, borra la cookie y destruye la sesión en el servidor. */
exigirMetodo('POST');
exigirCsrf();
iniciarSesion();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?: 'Lax',
    ]);
}

session_destroy();

responderJson(['ok' => true, 'mensaje' => 'Sesión cerrada.']);
