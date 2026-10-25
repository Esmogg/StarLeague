<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$idUsuario = exigirSesion();
exigirMetodo('POST');
exigirCsrf();

const MAX_BYTES = 10 * 1024 * 1024;
$tipo = $_POST['tipo'] ?? '';
if (!in_array($tipo, ['foto_perfil', 'banner_perfil'], true)) {
    responderJson(['ok' => false, 'mensaje' => 'Tipo de imagen no válido.'], 400);
}

if (!isset($_FILES['imagen']) || !is_array($_FILES['imagen'])) {
    responderJson(['ok' => false, 'mensaje' => 'Seleccioná una imagen para subir.'], 400);
}
$archivo = $_FILES['imagen'];
if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $mensaje = match ((int) $archivo['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo permitido de 10 MB.',
        UPLOAD_ERR_NO_FILE => 'Seleccioná una imagen para subir.',
        default => 'No se pudo recibir la imagen. Intentá nuevamente.',
    };
    responderJson(['ok' => false, 'mensaje' => $mensaje], 400);
}
if (!is_uploaded_file($archivo['tmp_name']) || (int) $archivo['size'] <= 0 || (int) $archivo['size'] > MAX_BYTES) {
    responderJson(['ok' => false, 'mensaje' => 'La imagen debe pesar como máximo 10 MB.'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($archivo['tmp_name']);
$extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
if (!isset($extensiones[$mime])) {
    responderJson(['ok' => false, 'mensaje' => 'Solo se permiten imágenes PNG, JPG o JPEG.'], 400);
}
$infoImagen = @getimagesize($archivo['tmp_name']);
if ($infoImagen === false || !isset($infoImagen[0], $infoImagen[1]) || ($infoImagen['mime'] ?? '') !== $mime) {
    responderJson(['ok' => false, 'mensaje' => 'El archivo seleccionado no es una imagen válida.'], 400);
}
$ancho = (int) $infoImagen[0];
$alto = (int) $infoImagen[1];
if ($ancho > 6000 || $alto > 6000) {
    responderJson(['ok' => false, 'mensaje' => 'La imagen no puede superar 6000 × 6000 píxeles.'], 400);
}
if ($tipo === 'foto_perfil' && ($ancho < 200 || $alto < 200)) {
    responderJson(['ok' => false, 'mensaje' => 'La foto de perfil debe medir al menos 200 × 200 píxeles.'], 400);
}
if ($tipo === 'banner_perfil' && ($ancho < 1110 || $alto < 170)) {
    responderJson(['ok' => false, 'mensaje' => 'El banner debe medir al menos 1110 × 170 píxeles.'], 400);
}

$directorio = dirname(__DIR__, 2) . '/uploads/perfiles';
if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
    responderJson(['ok' => false, 'mensaje' => 'No se pudo preparar el almacenamiento de imágenes.'], 500);
}

try {
    $db = Database::getConnection();
    $columna = $tipo;
    $stmt = $db->prepare("SELECT {$columna} FROM usuario WHERE id_usuario = :id");
    $stmt->execute([':id' => $idUsuario]);
    $rutaAnterior = $stmt->fetchColumn();
    if ($rutaAnterior === false) {
        responderJson(['ok' => false, 'mensaje' => 'No se encontró la cuenta.'], 404);
    }

    $nombreArchivo = $idUsuario . '_' . $tipo . '_' . bin2hex(random_bytes(12)) . '.' . $extensiones[$mime];
    $destino = $directorio . '/' . $nombreArchivo;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        responderJson(['ok' => false, 'mensaje' => 'No se pudo guardar la imagen.'], 500);
    }

    $rutaPublica = 'uploads/perfiles/' . $nombreArchivo;
    $actualizar = $db->prepare("UPDATE usuario SET {$columna} = :ruta WHERE id_usuario = :id");
    $actualizar->execute([':ruta' => $rutaPublica, ':id' => $idUsuario]);
    registrarEvento('imagen_perfil_subida tipo=' . $tipo, 'id' . $idUsuario);

    // Elimina únicamente archivos previos dentro de uploads/perfiles; las imágenes por defecto se conservan.
    if (is_string($rutaAnterior) && str_starts_with($rutaAnterior, 'uploads/perfiles/')) {
        $archivoAnterior = dirname(__DIR__, 2) . '/' . $rutaAnterior;
        if (is_file($archivoAnterior) && realpath(dirname($archivoAnterior)) === realpath($directorio)) {
            @unlink($archivoAnterior);
        }
    }

    responderJson([
        'ok' => true,
        'mensaje' => $tipo === 'foto_perfil' ? 'Foto de perfil actualizada.' : 'Banner actualizado.',
        'tipo' => $tipo,
        'ruta' => '../../../' . $rutaPublica,
    ]);
} catch (Throwable $e) {
    if (isset($destino) && is_file($destino)) {
        @unlink($destino);
    }
    error_log('[StarLeague] subir_imagen_perfil: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo actualizar la imagen.'], 500);
}
