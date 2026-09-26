<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php'; 

$tipo = $_GET['tipo'] ?? ''; // 'equipo' o 'usuario'

if ($tipo === 'equipo') {
    $sql = "SELECT id_equipo AS id, nombre FROM Equipo ORDER BY nombre ASC";
} else {
    $sql = "SELECT id_usuario AS id, nombre FROM usuario ORDER BY nombre ASC";
}

$resultado = $conexion->query($sql);
$data = array();

if ($resultado && $resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
}

$conexion->close();
echo json_encode($data);
?>