<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Montevideo');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "mensaje" => "No hay una sesión de usuario iniciada."
    ]);
    exit();
}

require_once '../conexion.php';

$id_usuario = (int) $_SESSION['id_usuario'];

try {
    /*
     * Datos de la cuenta.
     * fecha_creacion se guarda como DATETIME en la tabla usuario.
     */
    $sqlUsuario = "
        SELECT id_usuario, nombre, fecha_creacion
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ";

    $stmtUsuario = $conexion->prepare($sqlUsuario);
    $stmtUsuario->bind_param("i", $id_usuario);
    $stmtUsuario->execute();

    $resultadoUsuario = $stmtUsuario->get_result();
    $usuario = $resultadoUsuario->fetch_assoc();

    $stmtUsuario->close();

    if (!$usuario) {
        session_unset();
        session_destroy();

        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se encontró la cuenta."
        ]);
        exit();
    }

    $fechaCreacion = new DateTime($usuario['fecha_creacion']);
    $usuario['fecha_creacion'] =
        $fechaCreacion->format('d/m/Y \a \l\a\s H:i');

    /*
     * Se contemplan las dos formas en las que un jugador puede
     * aparecer en un torneo:
     * 1. Inscripción individual (ParticipaIndv).
     * 2. Pertenecer a un equipo que está inscrito (ParticipaEquip).
     */
    $sqlTorneos = "
        SELECT DISTINCT
            t.id_torneo,
            t.nombre,
            t.categoria,
            t.disciplina,
            t.formato,
            t.fecha_inicio,
            t.fecha_fin
        FROM Torneo t
        INNER JOIN ParticipaIndv pi
            ON pi.id_torneo = t.id_torneo
        WHERE pi.id_usuario = ?

        UNION

        SELECT DISTINCT
            t.id_torneo,
            t.nombre,
            t.categoria,
            t.disciplina,
            t.formato,
            t.fecha_inicio,
            t.fecha_fin
        FROM Torneo t
        INNER JOIN ParticipaEquip pe
            ON pe.id_torneo = t.id_torneo
        INNER JOIN UnirseEquipo ue
            ON ue.id_equipo = pe.id_equipo
        WHERE ue.id_usuario = ?

        ORDER BY nombre ASC
    ";

    $stmtTorneos = $conexion->prepare($sqlTorneos);
    $stmtTorneos->bind_param("ii", $id_usuario, $id_usuario);
    $stmtTorneos->execute();

    $resultadoTorneos = $stmtTorneos->get_result();

    $inscripciones = [];

    while ($torneo = $resultadoTorneos->fetch_assoc()) {
        $inscripciones[] = $torneo;
    }

    $stmtTorneos->close();
    $conexion->close();

    echo json_encode([
        "ok" => true,
        "usuario" => $usuario,
        "inscripciones" => $inscripciones
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($conexion) {
        $conexion->close();
    }

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo cargar el perfil."
    ], JSON_UNESCAPED_UNICODE);
}
?>
