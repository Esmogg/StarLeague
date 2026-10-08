<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/** Inscripción desde un <form> nativo: responde con un alert y vuelve atrás. */
function avisar(string $texto): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<script>alert(' . json_encode($texto, JSON_UNESCAPED_UNICODE) . '); window.history.back();</script>';
    exit;
}

iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    avisar('Debes iniciar sesión para inscribirte.');
}
$idSesion = (int) $_SESSION['id_usuario'];

$idTorneo = (int) ($_POST['id_torneo'] ?? 0);
$tipo     = ($_POST['tipo_inscripcion'] ?? '') === 'equipo' ? 'equipo' : 'usuario';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $idTorneo <= 0) {
    avisar('Faltan datos obligatorios.');
}

try {
    // Un usuario solo se inscribe a sí mismo; un equipo, solo si lo creó él.
    $idParticipante = $idSesion;
    if ($tipo === 'equipo') {
        $idParticipante = (int) ($_POST['id_participante'] ?? 0);
        if ($idParticipante <= 0 || !(new EquipoRepository())->esCreador($idParticipante, $idSesion)) {
            avisar('Solo puedes inscribir equipos que creaste.');
        }
    }

    $cupo = (new TorneoRepository())->cupoMaximo($idTorneo);
    if ($cupo === null) {
        avisar('El torneo no existe.');
    }

    $inscripciones = new InscripcionRepository();

    if ($inscripciones->contar($tipo, $idTorneo) >= $cupo) {
        avisar("El torneo ya alcanzó el límite máximo de participantes ($cupo).");
    }
    if ($inscripciones->yaInscrito($tipo, $idParticipante, $idTorneo)) {
        avisar('Ya se encuentra inscrito en este torneo.');
    }

    $inscripciones->inscribir($tipo, $idParticipante, $idTorneo);
    avisar('Inscripción registrada exitosamente.');
} catch (Throwable $e) {
    error_log('[StarLeague] procesar_inscripcion: ' . $e->getMessage());
    avisar('Error al registrar inscripción.');
}
