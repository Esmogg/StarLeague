<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Registra (o deshace) el resultado de un partido. Liga: gana uno o hay empate, en cualquier
 * jornada. Eliminación Directa: el ganador pasa a la
 * ronda siguiente. Sistema Suizo: gana uno de los dos o hay empate. Solo quien creó el torneo
 * o un administrador.
 */
function responder(int $codigo, bool $ok, string $mensaje): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos      = leerCuerpo();
$idTorneo   = (int) ($datos['id_torneo'] ?? 0);
$ronda      = (int) ($datos['ronda'] ?? 0);
$posicion   = filter_var($datos['posicion'] ?? 0, FILTER_VALIDATE_INT);
$idEncuentro = (int) ($datos['id_encuentro'] ?? 0);
$lado       = $datos['lado'] ?? '';

if ($idTorneo <= 0 || $posicion === false || $posicion < 0
    || !in_array($lado, ['local', 'visitante', 'empate', 'ninguno'], true)) {
    responder(400, false, 'Datos inválidos.');
}

try {
    $torneo = (new TorneoRepository())->detalle($idTorneo);

    if ($torneo === null) {
        responder(404, false, 'El torneo no existe.');
    }
    if ((int) $torneo['id_usuario'] !== $idSesion && !(new UsuarioRepository())->esAdmin($idSesion)) {
        registrarEvento('ganador_sin_permiso id=' . $idTorneo, 'id' . $idSesion);
        responder(403, false, 'No tienes permiso para registrar resultados de este torneo.');
    }
    $formato = Torneo::normalizarFormato($torneo['formato']);
    $repo    = new EncuentroRepository();

    if ($formato === 'Liga') {
        if ($idEncuentro <= 0) {
            responder(400, false, 'Datos inválidos.');
        }
        $repo->registrarResultadoLiga($idTorneo, $idEncuentro, $lado);
    } elseif ($formato === 'Eliminacion Directa' || $formato === 'Sistema Suizo') {
        if ($ronda <= 0) {
            responder(400, false, 'Datos inválidos.');
        }
        if ($formato === 'Eliminacion Directa') {
            $repo->registrarGanador($idTorneo, $ronda, $posicion, $lado);
        } else {
            $repo->registrarResultadoSuizo($idTorneo, $ronda, $posicion, $lado);
        }
    } else {
        responder(422, false, 'Este formato de torneo no tiene resultados por partido.');
    }

    registrarEvento("resultado_registrado id=$idTorneo ronda=$ronda pos=$posicion enc=$idEncuentro lado=$lado", 'id' . $idSesion);
    responder(200, true, $lado === 'ninguno' ? 'Resultado deshecho.' : 'Resultado registrado.');
} catch (OutOfBoundsException $e) {
    responder(404, false, $e->getMessage());
} catch (InvalidArgumentException $e) {
    responder(422, false, $e->getMessage());
} catch (DomainException $e) {
    responder(409, false, $e->getMessage());
} catch (Throwable $e) {
    error_log('[StarLeague] registrar_ganador: ' . $e->getMessage());
    responder(500, false, 'No se pudo registrar el resultado.');
}
