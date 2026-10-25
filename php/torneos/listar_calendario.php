<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Calendario público de un torneo: jornadas si es Liga, rondas de la llave si es Eliminación
 * Directa. Si quien mira creó el torneo o es administrador (y el formato tiene calendario
 * automático), `puede_generar` es verdadero.
 */
exigirMetodo('GET');

$idTorneo = filter_var($_GET['id_torneo'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idTorneo === false) {
    responderJson(['ok' => false, 'mensaje' => 'Torneo inválido.'], 400);
}

iniciarSesion();
$idSesion = (int) ($_SESSION['id_usuario'] ?? 0);

try {
    $torneos = new TorneoRepository();
    $torneo  = $torneos->detalle($idTorneo);

    if ($torneo === null) {
        responderJson(['ok' => false, 'mensaje' => 'El torneo no existe.'], 404);
    }

    $formato     = Torneo::normalizarFormato($torneo['formato']);
    $esLiga      = $formato === 'Liga';
    $esLlave     = $formato === 'Eliminacion Directa';
    $esSuizo     = $formato === 'Sistema Suizo';
    $esDueno     = $idSesion > 0 && (int) $torneo['id_usuario'] === $idSesion;
    $puedeGestor = $idSesion > 0 && ($esDueno || (new UsuarioRepository())->esAdmin($idSesion));
    $tipo        = Torneo::esPorEquipo($torneo['disciplina']) ? 'equipo' : 'usuario';

    $rondas = ($esLlave || $esSuizo) ? (new EncuentroRepository())->listarLlave($idTorneo) : [];

    // Liga: jornadas con resultados y tabla de posiciones.
    $jornadas = $esLiga ? (new EncuentroRepository())->listarPorTorneo($idTorneo) : [];
    $posiciones = null;
    if ($esLiga && $jornadas !== []) {
        $nombres = [];
        $jugados = [];
        foreach ($jornadas as $j) {
            foreach ($j['encuentros'] as $e) {
                $nombres[$e['local']] = true;
                if ($e['visitante'] !== null) {
                    $nombres[$e['visitante']] = true;
                }
                $jugados[] = $e;
            }
        }
        $posiciones = Calendario::clasificacion(array_map('strval', array_keys($nombres)), $jugados);
    }

    // Sistema Suizo: posiciones, cuántas rondas hay y si ya se puede pasar a la siguiente.
    $suizo = null;
    if ($esSuizo && $rondas !== []) {
        $participantes = Suizo::participantesDe($rondas);
        $totalRondas   = Suizo::rondas(count($participantes));
        $ultima        = $rondas[array_key_last($rondas)];
        $completa      = Suizo::rondaCompleta($ultima['partidos']);

        $suizo = [
            'total_rondas'    => $totalRondas,
            'ronda_actual'    => count($rondas),
            'terminado'       => count($rondas) >= $totalRondas && $completa,
            'puede_siguiente' => $puedeGestor && $completa && count($rondas) < $totalRondas,
            'clasificacion'   => Suizo::clasificacion($participantes, Suizo::resueltos($rondas)),
        ];
    }

    responderJson([
        'ok'     => true,
        'torneo' => [
            'id_torneo'     => (int) $torneo['id_torneo'],
            'nombre'        => $torneo['nombre'],
            'disciplina'    => $torneo['disciplina'],
            'formato'       => $esLlave ? 'Eliminación Directa' : $formato,
            'es_liga'       => $esLiga,
            'es_llave'      => $esLlave,
            'es_suizo'      => $esSuizo,
            'fecha_inicio'  => $torneo['fecha_inicio'],
            'fecha_fin'     => $torneo['fecha_fin'],
            'inscriptos'    => (new InscripcionRepository())->contar($tipo, $idTorneo),
            'tipo'          => $tipo === 'equipo' ? 'equipos' : 'jugadores',
            'puede_generar' => ($esLiga || $esLlave || $esSuizo) && $puedeGestor,
        ],
        'jornadas' => $jornadas,
        'posiciones' => $posiciones,
        'rondas'   => $rondas,
        'suizo'    => $suizo,
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_calendario: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudo cargar el calendario.'], 500);
}
