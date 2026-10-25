<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Genera la ronda siguiente de un torneo de Sistema Suizo, emparejando según las posiciones.
 * Requiere que la ronda anterior tenga todos sus resultados. Solo quien creó el torneo o un administrador.
 */
function responder(int $codigo, bool $ok, string $mensaje, array $extra = []): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje] + $extra, $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$idTorneo = (int) (leerCuerpo()['id_torneo'] ?? 0);
if ($idTorneo <= 0) {
    responder(400, false, 'Torneo inválido.');
}

try {
    $torneo = (new TorneoRepository())->detalle($idTorneo);

    if ($torneo === null) {
        responder(404, false, 'El torneo no existe.');
    }
    if ((int) $torneo['id_usuario'] !== $idSesion && !(new UsuarioRepository())->esAdmin($idSesion)) {
        registrarEvento('ronda_suiza_sin_permiso id=' . $idTorneo, 'id' . $idSesion);
        responder(403, false, 'No tienes permiso para generar rondas de este torneo.');
    }
    if (Torneo::normalizarFormato($torneo['formato']) !== 'Sistema Suizo') {
        responder(422, false, 'Solo los torneos de Sistema Suizo se generan ronda por ronda.');
    }

    $repo   = new EncuentroRepository();
    $rondas = $repo->listarLlave($idTorneo);
    if ($rondas === []) {
        responder(422, false, 'Primero hay que sortear la primera ronda.');
    }

    $participantes = Suizo::participantesDe($rondas);
    $total         = Suizo::rondas(count($participantes));
    $actual        = count($rondas);

    if ($actual >= $total) {
        responder(422, false, 'Ya se jugaron todas las rondas del torneo.');
    }
    if (!Suizo::rondaCompleta($rondas[$actual - 1]['partidos'])) {
        responder(422, false, "Faltan resultados en la ronda $actual.");
    }

    $siguiente = Suizo::siguienteRonda($participantes, Suizo::resueltos($rondas));
    $fechas    = Calendario::fechas($total, $torneo['fecha_inicio'], $torneo['fecha_fin']);
    $repo->agregarRonda($idTorneo, $actual + 1, $fechas[$actual], $siguiente);

    registrarEvento('ronda_suiza_generada id=' . $idTorneo . ' ronda=' . ($actual + 1), 'id' . $idSesion);
    responder(200, true, 'Ronda generada.', ['ronda' => $actual + 1, 'total' => $total]);
} catch (InvalidArgumentException $e) {
    responder(422, false, $e->getMessage());
} catch (Throwable $e) {
    error_log('[StarLeague] generar_ronda_suiza: ' . $e->getMessage());
    responder(500, false, 'No se pudo generar la ronda.');
}
