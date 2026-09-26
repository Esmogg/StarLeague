<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php'; 

// Eliminamos la restricción de fecha para traer todos los torneos registrados
$sql = "SELECT id_torneo, nombre, disciplina, cantParticipantes FROM Torneo ORDER BY fecha_inicio ASC";
$resultado = $conexion->query($sql);

$torneos = array();
if ($resultado && $resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        // Definimos qué disciplinas se juegan en equipo
        $disciplinas_equipo = ['Valorant', 'League of Legends'];
        $fila['es_equipo'] = in_array($fila['disciplina'], $disciplinas_equipo);
        $torneos[] = $fila;
    }
}

$conexion->close();
echo json_encode($torneos);
?>