<?php
declare(strict_types=1);

// Las pruebas no tocan la base de datos ni el .env real: usan una clave de firma
// propia, generada en cada ejecución, y una sesión que no envía cabeceras.
require __DIR__ . '/../vendor/autoload.php';

// La sesión se abre aquí, antes de que PHPUnit escriba nada en pantalla, y sin cookies
// ni cabeceras. Así iniciarSesion() la encuentra activa y no intenta abrirla de nuevo.
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
session_save_path(sys_get_temp_dir());
session_start();

putenv('FIRMA_CLAVE_PRIVADA=' . base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())));

require __DIR__ . '/../php/config/bootstrap.php';