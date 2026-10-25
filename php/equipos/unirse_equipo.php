<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Inscribe a un usuario en un equipo (UnirseEquipo). JSON por fetch(): {id_equipo, id_usuario?}
 * Un participante no puede unirse a un equipo por su cuenta: solo el creador del equipo
 * o un administrador agrega jugadores (el jugador se lo pide al organizador).
 */
function responder(int $codigo, bool $ok, string $mensaje, array $data = []): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje, 'data' => $data], $codigo);
}

exigirMetodo('POST');
exigirCsrf();
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Debes iniciar sesión para inscribirte en un equipo.');
}

$idSesion = (int) $_SESSION['id_usuario'];
$datos    = leerCuerpo();
$idEquipo = (int) ($datos['id_equipo'] ?? 0);
$idJugador = (int) ($datos['id_usuario'] ?? $idSesion);

if ($idEquipo <= 0 || $idJugador <= 0) {
    responder(400, false, 'Equipo o jugador inválido.');
}

try {
    $equipos  = new EquipoRepository();
    $usuarios = new UsuarioRepository();

    if (!$equipos->existe($idEquipo)) {
        responder(404, false, 'El equipo no existe.');
    }

    if (!$equipos->esCreador($idEquipo, $idSesion) && !$usuarios->esAdmin($idSesion)) {
        registrarEvento('unirse_equipo_sin_permiso', 'id' . $idSesion);
        responder(403, false, 'Solo el creador del equipo o un administrador puede agregar jugadores. Envía un mensaje al organizador desde "Mensajes".');
    }
    $jugador = $usuarios->buscarPorId($idJugador);
    if (!$jugador) {
        responder(404, false, 'El jugador no existe.');
    }
    if ($usuarios->estaBloqueado($idJugador)) {
        responder(409, false, 'No se puede agregar a un usuario bloqueado.');
    }
    $nombre = $jugador->nombre;

    $equipos->unirse($idEquipo, $idJugador);
    responder(200, true, 'Jugador inscrito en el equipo.', ['jugador' => $nombre]);
} catch (DomainException $e) {
    responder(409, false, 'Ese jugador ya es integrante del equipo.');
} catch (Throwable $e) {
    error_log('[StarLeague] unirse_equipo: ' . $e->getMessage());
    responder(500, false, 'No se pudo completar la acción.');
}
