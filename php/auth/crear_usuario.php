<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Registro de usuario: el objeto llega como JSON por fetch(). */
exigirCsrf();
$datos = leerJson();

$nombre = trim((string) ($datos['new_user']  ?? ''));
$email  = trim((string) ($datos['mail']      ?? ''));
$pass   = (string) ($datos['new_pass']  ?? '');
$pass2  = (string) ($datos['new_pass2'] ?? '');

// Mismas reglas que js/validaciones.js; aquí se vuelven a comprobar porque el cliente no es confiable.
$error = Validador::usuario($nombre) ?? Validador::email($email) ?? Validador::password($pass, $nombre);
if ($error !== null) {
    responderJson(['ok' => false, 'mensaje' => $error], 400);
}
if ($pass !== $pass2) {
    responderJson(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.'], 400);
}

try {
    $usuarios = new UsuarioRepository();

    if ($usuarios->existe($nombre, $email)) {
        responderJson(['ok' => false, 'mensaje' => 'El nombre de usuario o el correo electrónico ya están registrados.']);
    }

    $usuarios->registrar($nombre, $email, password_hash($pass, PASSWORD_DEFAULT));
    registrarEvento('usuario_registrado', $nombre);
    responderJson(['ok' => true, 'mensaje' => '¡Usuario registrado con éxito!']);
} catch (Throwable $e) {
    error_log('[StarLeague] crear_usuario: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'Hubo un error al registrar el usuario.'], 500);
}
