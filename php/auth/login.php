<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Inicio de sesión: recibe JSON por fetch y responde JSON; el cliente redirige al panel. */
final class LoginManager
{
    public function __construct(private UsuarioRepository $usuarios) {}

    public function autenticar(string $user, string $mail, string $password): ?Usuario
    {
        // Nombre, correo y contraseña son obligatorios, y el nombre y el correo tienen que ser de la misma cuenta.
        if ($user === '' || $mail === '' || $password === '') {
            return null;
        }

        $usuario = $this->usuarios->buscarPorNombreYCorreo($user, $mail);

        return ($usuario && password_verify($password, $usuario->contrasenaHash)) ? $usuario : null;
    }

    public function estaBloqueado(Usuario $usuario): bool
    {
        return $this->usuarios->estaBloqueado($usuario->id);
    }
}

exigirMetodo('POST');
exigirCsrf();

try {
    $datos   = leerCuerpo();
    $user    = trim((string) ($datos['user'] ?? ''));
    $mail    = trim((string) ($datos['mail'] ?? ''));
    $clave   = (string) ($datos['pass'] ?? '');
    // Topes de tamaño para no gastar CPU con entradas absurdas (bcrypt tarda lo mismo con cualquier largo).
    if (longitud($user) > Validador::EMAIL_MAX || longitud($mail) > Validador::EMAIL_MAX || strlen($clave) > 1024) {
        registrarEvento('login_fallido', 'entrada_demasiado_larga');
        responderJson(['ok' => false, 'mensaje' => 'Usuario, correo o contraseña incorrectos.'], 401);
    }
    $auth    = new LoginManager(new UsuarioRepository());
    $usuario = $auth->autenticar($user, $mail, $clave);

    // Cuenta bloqueada por un administrador: se avisa solo a quien puso las credenciales correctas.
    if ($usuario && $auth->estaBloqueado($usuario)) {
        registrarEvento('login_bloqueado', $usuario->nombre);
        responderJson(['ok' => false, 'mensaje' => 'Tu cuenta está bloqueada. Contacta a un administrador.'], 403);
    }

    if ($usuario) {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['id_usuario'] = $usuario->id;
        $_SESSION['nombre']     = $usuario->nombre;
        registrarEvento('login_ok', $usuario->nombre);

        responderJson(['ok' => true, 'mensaje' => 'Sesión iniciada.']);
    }

    registrarEvento('login_fallido', $user !== '' ? $user : $mail);
    // (mensaje genérico: no se revela si falló el nombre, el correo o la contraseña)
    responderJson(['ok' => false, 'mensaje' => 'Usuario, correo o contraseña incorrectos.'], 401);
} catch (Throwable $e) {
    error_log('[StarLeague] login: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo iniciar sesión. Inténtalo más tarde.'], 500);
}
