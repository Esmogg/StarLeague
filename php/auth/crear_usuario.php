<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Registro de usuario: el objeto llega como JSON por fetch(). */
$datos = leerJson();

$nombre = trim((string) ($datos['new_user']  ?? ''));
$email  = trim((string) ($datos['mail']      ?? ''));
$pass   = (string) ($datos['new_pass']  ?? '');
$pass2  = (string) ($datos['new_pass2'] ?? '');

if ($nombre === '' || $pass === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderJson(['ok' => false, 'mensaje' => 'Completa todos los campos con datos válidos.']);
}
if ($pass !== $pass2) {
    responderJson(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
}

try {
    $usuarios = new UsuarioRepository();

    if ($usuarios->existe($nombre, $email)) {
        responderJson(['ok' => false, 'mensaje' => 'El nombre de usuario o el correo electrónico ya están registrados.']);
    }

    $usuarios->registrar($nombre, $email, password_hash($pass, PASSWORD_DEFAULT));
    responderJson(['ok' => true, 'mensaje' => '¡Usuario registrado con éxito!']);
} catch (Throwable $e) {
    error_log('[StarLeague] crear_usuario: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'Hubo un error al registrar el usuario.'], 500);
}
