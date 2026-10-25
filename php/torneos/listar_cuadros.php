<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Resumen público de los torneos para la página "Brackets y posiciones": cómo va cada uno
 * (sin generar, en curso, terminado) y quién lidera o ganó. No requiere sesión.
 */
exigirMetodo('GET');

/**
 * @param  array<int,array{ronda:int,fecha:string,partidos:array}> $rondas
 * @return array{estado:string,detalle:string}
 */
function resumirLiga(array $rondas): array
{
    if ($rondas === []) {
        return ['estado' => 'sin_generar', 'detalle' => 'Todavía no se generó el calendario.'];
    }
    return ['estado' => 'calendario', 'detalle' => count($rondas) . ' jornadas programadas.'];
}

function resumirLlave(array $rondas): array
{
    if ($rondas === []) {
        return ['estado' => 'sin_generar', 'detalle' => 'Todavía no se generó la llave.'];
    }

    $total = 0;
    $jugados = 0;
    foreach ($rondas as $indice => $ronda) {
        foreach ($ronda['partidos'] as $p) {
            if ($indice === 0 && $p['visitante'] === null) {
                continue; // pase directo: no es un partido
            }
            $total++;
            if ($p['ganador'] !== null) {
                $jugados++;
            }
        }
    }

    $final = $rondas[array_key_last($rondas)]['partidos'][0] ?? null;
    if ($final !== null && $final['ganador'] !== null) {
        return ['estado' => 'terminado', 'detalle' => 'Campeón: ' . $final['ganador']];
    }
    return ['estado' => 'en_curso', 'detalle' => "$jugados de $total partidos jugados."];
}

function resumirSuizo(array $rondas): array
{
    if ($rondas === []) {
        return ['estado' => 'sin_generar', 'detalle' => 'Todavía no se sorteó la primera ronda.'];
    }

    $participantes = Suizo::participantesDe($rondas);
    $total         = Suizo::rondas(count($participantes));
    $actual        = count($rondas);
    $tabla         = Suizo::clasificacion($participantes, Suizo::resueltos($rondas));
    $lider         = $tabla[0] ?? null;
    $terminado     = $actual >= $total && Suizo::rondaCompleta($rondas[$actual - 1]['partidos']);

    if ($terminado && $lider !== null) {
        return ['estado' => 'terminado', 'detalle' => 'Líder final: ' . $lider['nombre']];
    }
    $detalle = "Ronda $actual de $total";
    if ($lider !== null && $lider['puntos'] > 0) {
        $detalle .= ' · líder: ' . $lider['nombre'];
    }
    return ['estado' => 'en_curso', 'detalle' => $detalle . '.'];
}

try {
    $rondasPorTorneo = (new EncuentroRepository())->listarTodas();
    $lista = [];

    foreach ((new TorneoRepository())->listarBasico() as $t) {
        $formato = Torneo::normalizarFormato($t['formato']);
        $rondas  = $rondasPorTorneo[(int) $t['id_torneo']] ?? [];

        if ($formato === 'Liga') {
            $vista = 'liga';
            $res   = resumirLiga($rondas);
        } elseif ($formato === 'Eliminacion Directa') {
            $vista = 'llave';
            $res   = resumirLlave($rondas);
        } elseif ($formato === 'Sistema Suizo') {
            $vista = 'suizo';
            $res   = resumirSuizo($rondas);
        } else {
            continue;
        }

        $lista[] = [
            'id_torneo'    => (int) $t['id_torneo'],
            'nombre'       => $t['nombre'],
            'disciplina'   => $t['disciplina'],
            'formato'      => $vista === 'llave' ? 'Eliminación Directa' : $formato,
            'vista'        => $vista,
            'estado'       => $res['estado'],
            'detalle'      => $res['detalle'],
            'fecha_inicio' => $t['fecha_inicio'],
            'fecha_fin'    => $t['fecha_fin'],
        ];
    }

    responderJson(['ok' => true, 'torneos' => $lista]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_cuadros: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los torneos.'], 500);
}
