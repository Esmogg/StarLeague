<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Resultados de partidos para resultados.js: los cargados a mano más los de los calendarios, llaves y rondas. */
try {
    $partidos = array_merge(
        (new PartidoRepository())->listarConTorneo(),
        (new EncuentroRepository())->resultadosJugados()
    );
    usort($partidos, static fn (array $a, array $b): int => strcmp((string) $b['fecha'], (string) $a['fecha']));

    responderJson(['ok' => true, 'partidos' => $partidos]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_resultados: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los resultados.'], 500);
}
