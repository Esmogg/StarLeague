<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Un usuario escribe a los organizadores: solicitud de inscripción, consulta o reporte.
 * JSON: {tipo, id_torneo?, asunto, texto}. Máximo 5 mensajes por usuario cada 24 horas.
 */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos  = leerCuerpo();
$tipo   = $datos['tipo'] ?? '';
$asunto = trim((string) ($datos['asunto'] ?? ''));
$texto  = trim((string) ($datos['texto'] ?? ''));
$idTorneo = isset($datos['id_torneo']) && $datos['id_torneo'] !== '' && $datos['id_torneo'] !== null
    ? (int) $datos['id_torneo'] : null;

if (!is_string($tipo) || !in_array($tipo, MensajeRepository::TIPOS, true)) {
    responder(400, false, 'El tipo de mensaje no es válido.');
}
// Sin caracteres de control (salvo saltos de línea): el texto se muestra siempre como texto, nunca como HTML.
$limpio = static fn (string $v): bool => preg_match('/[^\P{C}\n\r\t]/u', $v) !== 1;
if (!$limpio($asunto) || !$limpio($texto)) {
    responder(400, false, 'El mensaje contiene caracteres no permitidos.');
}
if (longitud($asunto) < MensajeRepository::ASUNTO_MIN || longitud($asunto) > MensajeRepository::ASUNTO_MAX) {
    responder(400, false, 'El asunto debe tener entre 3 y 100 caracteres.');
}
if (longitud($texto) < MensajeRepository::TEXTO_MIN || longitud($texto) > MensajeRepository::TEXTO_MAX) {
    responder(400, false, 'El mensaje debe tener entre 10 y 1000 caracteres.');
}
if ($tipo === 'solicitud' && ($idTorneo === null || $idTorneo <= 0)) {
    responder(400, false, 'Elige el torneo al que quieres que te inscriban.');
}

try {
    if ($idTorneo !== null && (new TorneoRepository())->duenoDe($idTorneo) === null) {
        responder(404, false, 'El torneo no existe.');
    }

    $mensajes = new MensajeRepository();
    if ($mensajes->contarRecientes($idSesion) >= MensajeRepository::MAX_POR_DIA) {
        registrarEvento('mensaje_limite_diario', 'id' . $idSesion);
        responder(429, false, 'Llegaste al máximo de ' . MensajeRepository::MAX_POR_DIA . ' mensajes en 24 horas. Inténtalo más tarde.');
    }

    $id = $mensajes->crear($idSesion, $tipo, $idTorneo, $asunto, $texto);

    registrarEvento("mensaje_enviado id=$id tipo=$tipo", 'id' . $idSesion);
    responder(200, true, 'Mensaje enviado. Los organizadores lo van a ver.');
} catch (Throwable $e) {
    error_log('[StarLeague] mensajes/enviar: ' . $e->getMessage());
    responder(500, false, 'No se pudo enviar el mensaje.');
}
