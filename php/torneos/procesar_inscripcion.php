<?php
require_once '../conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['id_torneo']) && !empty($_POST['id_participante'])) {
    
    $id_torneo = intval($_POST['id_torneo']);
    $id_participante = intval($_POST['id_participante']);
    $tipo = $_POST['tipo_inscripcion']; // 'equipo' o 'usuario'

    // 1. Obtener cupo máximo del torneo
    $sql_torneo = "SELECT cantParticipantes FROM Torneo WHERE id_torneo = ?";
    $stmt_torneo = $conexion->prepare($sql_torneo);
    $stmt_torneo->bind_param("i", $id_torneo);
    $stmt_torneo->execute();
    $max_cupos = $stmt_torneo->get_result()->fetch_assoc()['cantParticipantes'];
    $stmt_torneo->close();

    // Configurar tabla y columna según tipo de inscripción
    if ($tipo === 'equipo') {
        $tabla = "ParticipaEquip";
        $columna = "id_equipo";
    } else {
        $tabla = "ParticipaIndv";
        $columna = "id_usuario";
    }

    // 2. Verificar cupos actuales ocupados
    $sql_count = "SELECT COUNT(*) as total FROM $tabla WHERE id_torneo = ?";
    $stmt_count = $conexion->prepare($sql_count);
    $stmt_count->bind_param("i", $id_torneo);
    $stmt_count->execute();
    $inscritos = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();

    if ($inscritos >= $max_cupos) {
        echo "<script>alert('El torneo ya alcanzó el límite máximo de participantes ($max_cupos).'); window.history.back();</script>";
        exit;
    }

    // 3. Verificar si ya está inscrito
    $sql_check = "SELECT * FROM $tabla WHERE $columna = ? AND id_torneo = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_participante, $id_torneo);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        echo "<script>alert('Ya se encuentra inscrito en este torneo.'); window.history.back();</script>";
    } else {
        // 4. Insertar la inscripción
        $sql_insert = "INSERT INTO $tabla ($columna, id_torneo) VALUES (?, ?)";
        $stmt_insert = $conexion->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $id_participante, $id_torneo);

        if ($stmt_insert->execute()) {
            echo "<script>alert('Inscripción registrada exitosamente.'); window.history.back();</script>";
        } else {
            echo "<script>alert('Error al registrar inscripción.'); window.history.back();</script>";
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
} else {
    echo "<script>alert('Faltan datos obligatorios.'); window.history.back();</script>";
}

$conexion->close();
?>