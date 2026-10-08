<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Inicio de sesión: el formulario HTML envía por POST nativo y se redirige al panel. */
final class LoginManager
{
    public function __construct(private UsuarioRepository $usuarios) {}

    public function autenticar(string $user, string $mail, string $password): ?Usuario
    {
        $identificador = $user !== '' ? $user : $mail;
        if ($identificador === '') {
            return null;
        }

        $usuario = $this->usuarios->buscarPorIdentificador($identificador);

        return ($usuario && password_verify($password, $usuario->contrasenaHash)) ? $usuario : null;
    }
}

iniciarSesion();

try {
    $auth    = new LoginManager(new UsuarioRepository());
    $usuario = $auth->autenticar(
        trim((string) ($_POST['user'] ?? '')),
        trim((string) ($_POST['mail'] ?? '')),
        (string) ($_POST['pass'] ?? '')
    );

    if ($usuario) {
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $usuario->id;
        $_SESSION['nombre']     = $usuario->nombre;

        header('Location: ../../html/login/');
        exit;
    }

    echo 'Usuario, correo o contraseña incorrectos.';
} catch (Throwable $e) {
    error_log('[StarLeague] login: ' . $e->getMessage());
    echo 'No se pudo iniciar sesión. Inténtalo más tarde.';
}
