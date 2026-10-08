<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

try {
    responderJson((new TorneoRepository())->listar());
} catch (Throwable $e) {
    error_log('[StarLeague] obtener_torneos: ' . $e->getMessage());
    responderJson([], 500);
}
