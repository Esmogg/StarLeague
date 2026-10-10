<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Resultados de partidos para resultados.js. */
try {
    responderJson(['ok' => true, 'partidos' => (new PartidoRepository())->listarConTorneo()]);
} catch (Throwable $e) {
    error_log('[StarLeague] listar_resultados: ' . $e->getMessage());
    responderJson(['ok' => false, 'mensaje' => 'No se pudieron cargar los resultados.'], 500);
}
