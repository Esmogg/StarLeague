<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Inscripción a un torneo: recibe JSON por fetch y responde JSON ({ok, mensaje}). */
function avisar(string $texto, bool $ok = false, int $codigo = 200): never
{
    responderJson(['ok' => $ok, 'mensaje' => $texto], $codigo);
}

exigirMetodo('POST');
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    avisar('Debes iniciar sesión para inscribirte.', false, 401);
}
exigirCsrf();
$idSesion = (int) $_SESSION['id_usuario'];

$datos    = leerCuerpo();
$idTorneo = (int) ($datos['id_torneo'] ?? 0);
$tipo     = ($datos['tipo_inscripcion'] ?? '') === 'equipo' ? 'equipo' : 'usuario';
$idParticipante = (int) ($datos['id_participante'] ?? 0);

if ($idTorneo <= 0 || $idParticipante <= 0) {
    avisar('Faltan datos obligatorios.', false, 400);
}

try {
    // Un participante no puede inscribirse (ni inscribir a nadie): se lo pide al organizador.
    // Primero el permiso y recién después los datos del torneo.
    $usuarios = new UsuarioRepository();
    $rol      = $usuarios->rolDe($idSesion);
    if (!in_array($rol, ['admin', 'organizador'], true)) {
        registrarEvento('inscripcion_sin_permiso', 'id' . $idSesion);
        avisar('Solo un organizador o administrador puede inscribir participantes. Envía un mensaje al organizador desde "Mensajes".', false, 403);
    }

    $torneos = new TorneoRepository();
    $cupo    = $torneos->cupoMaximo($idTorneo);
    if ($cupo === null) {
        avisar('El torneo no existe.', false, 404);
    }

    // El organizador solo inscribe en los torneos que él administra; el administrador, en todos.
    if ($rol !== 'admin' && $torneos->duenoDe($idTorneo) !== $idSesion) {
        registrarEvento('inscripcion_torneo_ajeno', 'id' . $idSesion);
        avisar('Solo puedes inscribir participantes en los torneos que organizas.', false, 403);
    }

    // El tipo de participante tiene que coincidir con el del torneo (equipos o jugadores individuales).
    $detalle = $torneos->detalle($idTorneo);
    if ($detalle !== null && isset($detalle['disciplina'])) {
        $esperado = Torneo::esPorEquipo((string) $detalle['disciplina']) ? 'equipo' : 'usuario';
        if ($tipo !== $esperado) {
            avisar($esperado === 'equipo'
                ? 'Este torneo es por equipos: hay que inscribir un equipo.'
                : 'Este torneo es individual: hay que inscribir a un jugador.');
        }
    }

    if ($tipo === 'equipo') {
        if (!(new EquipoRepository())->existe($idParticipante)) {
            avisar('El equipo no existe.', false, 404);
        }
    } else {
        $jugador = $usuarios->buscarPorId($idParticipante);
        if ($jugador === null) {
            avisar('El jugador no existe.', false, 404);
        }
        if ($usuarios->estaBloqueado($idParticipante)) {
            avisar('No se puede inscribir a un usuario bloqueado.');
        }
    }

    if ((new EncuentroRepository())->tieneCalendario($idTorneo)) {
        avisar('El calendario de este torneo ya fue generado: no se aceptan más inscripciones.');
    }

    $inscripciones = new InscripcionRepository();

    if ($inscripciones->contar($tipo, $idTorneo) >= $cupo) {
        avisar("El torneo ya alcanzó el límite máximo de participantes ($cupo).");
    }
    if ($inscripciones->yaInscrito($tipo, $idParticipante, $idTorneo)) {
        avisar('Ya se encuentra inscrito en este torneo.');
    }

    $inscripciones->inscribir($tipo, $idParticipante, $idTorneo);
    registrarEvento("inscripcion tipo=$tipo torneo=$idTorneo participante=$idParticipante", 'id' . $idSesion);

    // Comprobante firmado: si falla, la inscripción ya quedó registrada y se responde sin comprobante.
    $comprobante = null;
    try {
        $nombres = $inscripciones->datosComprobante($tipo, $idParticipante, $idTorneo);
        if ($nombres !== null) {
            $comprobante = Firma::emitir([
                'emisor'         => 'StarLeague',
                'id'             => bin2hex(random_bytes(6)),
                'id_torneo'      => $idTorneo,
                'torneo'         => $nombres['torneo'],
                'tipo'           => $tipo,
                'id_participante' => $idParticipante,
                'participante'   => $nombres['participante'],
                'fecha'          => date('c'),
            ]);
        }
        if ($comprobante === null) {
            error_log('[StarLeague] procesar_inscripcion: comprobante no emitido (¿falta FIRMA_CLAVE_PRIVADA?)');
        }
    } catch (Throwable $e) {
        error_log('[StarLeague] procesar_inscripcion comprobante: ' . $e->getMessage());
    }

    responderJson([
        'ok'          => true,
        'mensaje'     => 'Inscripción registrada exitosamente.',
        'comprobante' => $comprobante,
    ]);
} catch (Throwable $e) {
    error_log('[StarLeague] procesar_inscripcion: ' . $e->getMessage());
    avisar('Error al registrar inscripción.');
}
