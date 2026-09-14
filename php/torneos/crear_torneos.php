<?php
// NUEVO: Iniciamos sesión para poder obtener el id_usuario del usuario autenticado
session_start();
include("../conexion.php");

// NUEVO: Verificamos que el usuario haya iniciado sesión antes de permitirle crear un torneo
if (!isset($_SESSION['id_usuario'])) {
    die("Acceso denegado. Debes iniciar sesión para crear un torneo.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturamos y limpiamos los datos enviados desde el formulario HTML
    $id_usuario = $_SESSION['id_usuario'];
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $disciplina = trim($_POST['deporte'] ?? '');
    $formato = trim($_POST['formato'] ?? '');
    $cantParticipantes = trim($_POST['cantidadParticipantes'] ?? '');
    $fecha_inicio = trim($_POST['fechaInicio'] ?? null);
    $fecha_fin = trim($_POST['fechaFin'] ?? null);

    // Validamos que los campos obligatorios no estén vacíos
    if (!empty($nombre) && !empty($categoria) && !empty($disciplina) && !empty($formato) && !empty($cantParticipantes)) {
        
        // Consulta SQL para insertar el torneo vinculándolo al usuario logueado
        $sqlTorneo = "INSERT INTO Torneo (id_usuario, nombre, descripcion, categoria, disciplina, formato, cantParticipantes, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conexion->prepare($sqlTorneo)) {
            // "isssssiss" representa los tipos de datos: i (integer), s (string)
            $stmt->bind_param("isssssiss", $id_usuario, $nombre, $descripcion, $categoria, $disciplina, $formato, $cantParticipantes, $fecha_inicio, $fecha_fin);
            
            try {
                if ($stmt->execute()) {
                    echo "¡Torneo creado exitosamente!";
                }
            } catch (mysqli_sql_exception $e) {
                // Capturamos error si el nombre del torneo ya está repetido (Código 1062)
                if ($e->getCode() == 1062) {
                    echo "El nombre del torneo ya está en uso. Por favor, elige otro.";
                } else {
                    echo "Error al registrar el torneo: " . $e->getMessage();
                }
            }
            $stmt->close();
        } else {
            echo "Error en la preparación de la consulta: " . $conexion->error;
        }
    } else {
        echo "Por favor, completa todos los campos obligatorios.";
    }

    $conexion->close();
}
?>