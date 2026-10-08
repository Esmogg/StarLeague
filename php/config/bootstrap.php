<?php
declare(strict_types=1);

/**
 * Arranque común de los endpoints: errores al log (no a pantalla), sesión
 * con cookie segura, autoload de modelos/repositorios y helpers JSON.
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Montevideo');

spl_autoload_register(static function (string $clase): void {
    foreach (['models', 'repositories'] as $carpeta) {
        $archivo = __DIR__ . "/../{$carpeta}/{$clase}.php";
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

require_once __DIR__ . '/Database.php';

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function responderJson(array $cuerpo, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Devuelve el id del usuario en sesión o corta con 401. */
function exigirSesion(): int
{
    iniciarSesion();
    if (!isset($_SESSION['id_usuario'])) {
        responderJson(['ok' => false, 'mensaje' => 'Debes iniciar sesión.'], 401);
    }
    return (int) $_SESSION['id_usuario'];
}

/** Lee el cuerpo JSON de la petición; corta con 400 si no es válido. */
function leerJson(): array
{
    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($datos)) {
        responderJson(['ok' => false, 'mensaje' => 'No se recibió un objeto JSON válido.'], 400);
    }
    return $datos;
}

/** Largo en caracteres UTF-8 sin depender de la extensión mbstring. */
function longitud(string $texto): int
{
    return (int) preg_match_all('/./su', $texto);
}

/** Corta con 405 si el método HTTP no es el esperado. */
function exigirMetodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        responderJson(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
    }
}

/** Cuerpo JSON (fetch) y, si no hay, el formulario clásico ($_POST). */
function leerCuerpo(): array
{
    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($datos) ? $datos : $_POST;
}
