<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Entrega el token CSRF de la sesión; js/script.js lo agrega a cada petición que modifica datos. */
exigirMetodo('GET');
header('Cache-Control: no-store');
responderJson(['ok' => true, 'token' => tokenCsrf()]);
