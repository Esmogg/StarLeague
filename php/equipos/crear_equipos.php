<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Creación de equipo: JSON por fetch() desde crear-equipos.js (o formulario clásico). */
function responder(int $codigo, bool $ok, string $mensaje, array $advertencias = []): never
{
    responderJson(['ok' => $ok, 'mensaje' => $mensaje, 'data' => ['advertencias' => $advertencias]], $codigo);
}

exigirMetodo('POST');
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    responder(401, false, 'Acceso denegado. Debes iniciar sesión para crear un equipo.');
}

$datos      = leerCuerpo();
$idCreador  = (int) $_SESSION['id_usuario'];
$nombre     = trim((string) ($datos['nombre'] ?? ''));
$disciplina = trim((string) ($datos['deporte'] ?? ''));

if ($nombre === '' || $disciplina === '') {
    responder(400, false, 'Por favor, completa todos los campos (incluyendo la disciplina).');
}
if (longitud($nombre) > 50) {
    responder(400, false, 'El nombre del equipo no puede superar los 50 caracteres.');
}
if (!in_array($disciplina, Equipo::DISCIPLINAS, true)) {
    responder(400, false, 'Disciplina inválida.');
}

// integrantes: [{jugadorId, jugador, rol}] -> ids válidos + etiqueta para las advertencias
$pedidos = [];
foreach ((array) ($datos['integrantes'] ?? []) as $i) {
    $id = (int) ($i['jugadorId'] ?? 0);
    if ($id > 0) {
        $pedidos[$id] = (string) ($i['jugador'] ?? $id);
    }
}

try {
    $existentes   = (new UsuarioRepository())->filtrarExistentes(array_keys($pedidos));
    $advertencias = [];
    foreach ($pedidos as $id => $etiqueta) {
        if (!in_array($id, $existentes, true)) {
            $advertencias[] = "El jugador \"$etiqueta\" no existe y no se agregó al equipo.";
        }
    }

    (new EquipoRepository())->crear(new Equipo(null, $nombre, $disciplina), $idCreador, $existentes);

    responder(200, true, '¡Equipo creado exitosamente!', $advertencias);
} catch (DomainException $e) {
    responder(409, false, $e->getMessage());
} catch (Throwable $e) {
    error_log('[StarLeague] crear_equipos: ' . $e->getMessage());
    responder(500, false, 'Error al registrar el equipo en el servidor.');
}
