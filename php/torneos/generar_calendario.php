<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Genera (o vuelve a generar) el calendario de un torneo con sus inscriptos:
 * jornadas "todos contra todos" para Liga, o la llave de rondas para Eliminación Directa
 * (con los participantes sorteados) o la primera ronda de un Sistema Suizo. Solo quien creó el torneo o un administrador.
 * Lo anterior se reemplaza.
 */
function responder(int $codigo, bool $ok, string $mensaje, array $extra = []): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje] + $extra, $codigo);
}

exigirMetodo('POST');
exigirCsrf();
$idSesion = exigirSesion();

$datos      = leerCuerpo();
$idTorneo   = (int) ($datos['id_torneo'] ?? 0);
$idaYVuelta = ($datos['ida_y_vuelta'] ?? false) === true;

if ($idTorneo <= 0) {
    responder(400, false, 'Torneo inválido.');
}

try {
    $torneos = new TorneoRepository();
    $torneo  = $torneos->detalle($idTorneo);

    if ($torneo === null) {
        responder(404, false, 'El torneo no existe.');
    }
    if ((int) $torneo['id_usuario'] !== $idSesion && !(new UsuarioRepository())->esAdmin($idSesion)) {
        registrarEvento('calendario_sin_permiso id=' . $idTorneo, 'id' . $idSesion);
        responder(403, false, 'No tienes permiso para generar el calendario de este torneo.');
    }
    $formato = Torneo::normalizarFormato($torneo['formato']);
    if (!in_array($formato, ['Liga', 'Eliminacion Directa', 'Sistema Suizo'], true)) {
        responder(422, false, 'El calendario automático no está disponible para este formato.');
    }

    $tipo    = Torneo::esPorEquipo($torneo['disciplina']) ? 'equipo' : 'usuario';
    $nombres = (new InscripcionRepository())->nombres($tipo, $idTorneo);

    if (count($nombres) < Calendario::MIN_PARTICIPANTES) {
        responder(422, false, 'Hacen falta al menos 2 inscriptos para generar el calendario.');
    }
    if (count($nombres) > Calendario::MAX_PARTICIPANTES) {
        responder(422, false, 'El calendario automático admite hasta ' . Calendario::MAX_PARTICIPANTES . ' participantes.');
    }

    if ($formato === 'Liga') {
        $jornadas = Calendario::todosContraTodos($nombres, $idaYVuelta);
        $fechas   = Calendario::fechas(count($jornadas), $torneo['fecha_inicio'], $torneo['fecha_fin']);
        $total    = (new EncuentroRepository())->reemplazar($idTorneo, $jornadas, $fechas);

        registrarEvento('calendario_generado id=' . $idTorneo, 'id' . $idSesion);
        responder(200, true, 'Calendario generado.', [
            'jornadas'   => count($jornadas),
            'encuentros' => $total,
        ]);
    }

    if ($formato === 'Sistema Suizo') {
        // Sorteo y primera ronda; las siguientes se generan una por una con generar_ronda_suiza.php.
        shuffle($nombres);
        $total  = Suizo::rondas(count($nombres));
        $fechas = Calendario::fechas($total, $torneo['fecha_inicio'], $torneo['fecha_fin']);
        $ronda  = Suizo::primeraRonda($nombres);
        (new EncuentroRepository())->reemplazarLlave($idTorneo, [$ronda], [$fechas[0]]);

        registrarEvento('suizo_generado id=' . $idTorneo, 'id' . $idSesion);
        responder(200, true, 'Primera ronda generada.', [
            'rondas'     => $total,
            'encuentros' => count(array_filter($ronda, static fn (array $p): bool => $p['visitante'] !== null)),
        ]);
    }

    // Eliminación Directa: sorteo de los cruces y llave completa.
    shuffle($nombres);
    $rondas = Bracket::generar($nombres);
    $fechas = Calendario::fechas(count($rondas), $torneo['fecha_inicio'], $torneo['fecha_fin']);
    $total  = (new EncuentroRepository())->reemplazarLlave($idTorneo, $rondas, $fechas);

    registrarEvento('llave_generada id=' . $idTorneo, 'id' . $idSesion);
    responder(200, true, 'Llave generada.', [
        'rondas'     => count($rondas),
        'encuentros' => $total,
    ]);
} catch (InvalidArgumentException $e) {
    responder(422, false, $e->getMessage());
} catch (Throwable $e) {
    error_log('[StarLeague] generar_calendario: ' . $e->getMessage());
    responder(500, false, 'No se pudo generar el calendario.');
}
