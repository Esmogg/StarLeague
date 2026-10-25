<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

try {
    $tipo = $_GET['tipo'] ?? '';
    // La lista de usuarios no es pública: hay que haber iniciado sesión (la de equipos ya es pública).
    if ($tipo !== 'equipo') {
        exigirSesion();
    }
    responderJson($tipo === 'equipo'
        ? (new EquipoRepository())->listarCandidatos()
        : (new UsuarioRepository())->listarCandidatos());
} catch (Throwable $e) {
    error_log('[StarLeague] obtener_candidatos: ' . $e->getMessage());
    responderJson([], 500);
}
