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
require_once __DIR__ . '/Firma.php';
require_once __DIR__ . '/Validador.php';

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS'])
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        ]);
        session_start();

        // Si un administrador bloqueó la cuenta, la sesión abierta deja de valer en la siguiente petición.
        if (isset($_SESSION['id_usuario'])) {
            try {
                if ((new UsuarioRepository())->estaBloqueado((int) $_SESSION['id_usuario'])) {
                    $_SESSION = [];
                    session_destroy();
                    session_start();
                }
            } catch (Throwable $e) {
                error_log('[StarLeague] comprobación de bloqueo: ' . $e->getMessage());
            }
        }
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

/** Corta el texto a un máximo de caracteres UTF-8 (sin depender de mbstring). */
function recortar(string $texto, int $max): string
{
    return preg_match('/^.{0,' . $max . '}/su', $texto, $m) ? $m[0] : '';
}

/**
 * Ruta de una imagen de perfil segura para mostrar en público: solo archivos de uploads/perfiles/
 * con nombre simple. Cualquier otra cosa (otra carpeta, una URL, ../) se descarta y se usa la imagen por defecto.
 */
function rutaImagenPublica(?string $ruta): ?string
{
    if ($ruta === null) {
        return null;
    }
    return preg_match('#^uploads/(perfiles|torneos|equipos)/[A-Za-z0-9._-]+$#', $ruta) === 1 && !str_contains($ruta, '..')
        ? $ruta
        : null;
}

/**
 * Guarda una imagen enviada como data URL (data:image/png;base64,...) en uploads/torneos/ (o uploads/equipos/).
 * Se valida en el servidor: tamaño, tipo real (finfo + getimagesize, no el que declara el cliente),
 * dimensiones; el nombre es aleatorio. Devuelve la ruta pública relativa, o null si no se envió imagen.
 * Lanza InvalidArgumentException con un mensaje para el usuario si la imagen no es válida.
 */
function guardarImagenTorneo(mixed $dataUrl, string $etiqueta, int $maxBytes, string $carpeta = 'torneos'): ?string
{
    if ($dataUrl === null || $dataUrl === '') {
        return null;
    }
    if (!is_string($dataUrl) || !preg_match('#^data:image/(?:png|jpeg|jpg);base64,([A-Za-z0-9+/=\s]+)$#', $dataUrl, $m)) {
        throw new InvalidArgumentException("$etiqueta: solo se permiten imágenes PNG o JPG.");
    }
    $bytes = base64_decode($m[1], true);
    if ($bytes === false || $bytes === '') {
        throw new InvalidArgumentException("$etiqueta: la imagen no es válida.");
    }
    if (strlen($bytes) > $maxBytes) {
        throw new InvalidArgumentException("$etiqueta: la imagen supera el tamaño máximo de " . intdiv($maxBytes, 1048576) . ' MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $info = @getimagesizefromstring($bytes);
    if (!isset($extensiones[$mime]) || $info === false || ($info['mime'] ?? '') !== $mime) {
        throw new InvalidArgumentException("$etiqueta: el archivo no es una imagen PNG o JPG válida.");
    }
    if ($info[0] > 6000 || $info[1] > 6000) {
        throw new InvalidArgumentException("$etiqueta: la imagen no puede superar 6000 × 6000 píxeles.");
    }
    if (!in_array($carpeta, ['torneos', 'equipos'], true)) {
        throw new RuntimeException('Carpeta de imágenes inválida.');
    }
    $dir = dirname(__DIR__, 2) . '/uploads/' . $carpeta;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de imágenes.');
    }
    $nombre = strtolower($etiqueta) . '_' . bin2hex(random_bytes(12)) . '.' . $extensiones[$mime];
    if (file_put_contents($dir . '/' . $nombre, $bytes) === false) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return 'uploads/' . $carpeta . '/' . $nombre;
}

/** Borra un archivo de uploads/torneos/ o uploads/equipos/ (nunca fuera de esas carpetas). */
function borrarImagenTorneo(?string $ruta): void
{
    if ($ruta === null || preg_match('#^uploads/(torneos|equipos)/[A-Za-z0-9._-]+$#', $ruta) !== 1) {
        return;
    }
    $f = dirname(__DIR__, 2) . '/' . $ruta;
    if (is_file($f)) {
        @unlink($f);
    }
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

/** IP del cliente. Detrás del proxy toma la última entrada de X-Forwarded-For (la agrega Apache). */
function ipCliente(): string
{
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '-';
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xff !== '' && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $partes = array_map('trim', explode(',', $xff));
        $ultima = end($partes);
        if (filter_var($ultima, FILTER_VALIDATE_IP)) {
            return $ultima;
        }
    }
    return $ip;
}

/** Registra un evento de seguridad en el log (docker compose logs web). */
function registrarEvento(string $evento, string $usuario = '-'): void
{
    $usuario = preg_replace('/[^\p{L}\p{N}@._-]/u', '_', $usuario) ?? '-';
    error_log(sprintf('[StarLeague][seguridad] %s usuario=%s ip=%s', $evento, $usuario, ipCliente()));
}

/** Token CSRF de la sesión (se crea la primera vez que se pide). */
function tokenCsrf(): string
{
    iniciarSesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** ¿La petición trae el token correcto? Cabecera X-CSRF-Token (fetch) o campo _csrf (form nativo). */
function csrfValido(): bool
{
    iniciarSesion();
    $esperado = (string) ($_SESSION['csrf'] ?? '');
    $enviado  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    return $esperado !== '' && is_string($enviado) && hash_equals($esperado, $enviado);
}

/** Corta con 403 si falta o no coincide el token CSRF. */
function exigirCsrf(): void
{
    if (!csrfValido()) {
        registrarEvento('csrf_invalido');
        responderJson(['ok' => false, 'mensaje' => 'La sesión de seguridad venció. Recarga la página e inténtalo de nuevo.'], 403);
    }
}
