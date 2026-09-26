<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['id_usuario'])) {
    die("Acceso denegado. Debes iniciar sesión para crear un torneo.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturamos y limpiamos los datos enviados desde el formulario HTML
    $id_usuario = $_SESSION['id_usuario'];
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? ''); // <--- Verifica que el name en HTML sea 'categoria'
    $disciplina = trim($_POST['deporte'] ?? '');   // <--- Aquí usas 'deporte' del form para la columna disciplina
    $formato = trim($_POST['formato'] ?? '');
    $cantParticipantes = trim($_POST['cantidadParticipantes'] ?? '');
    $fecha_inicio = !empty($_POST['fechaInicio']) ? trim($_POST['fechaInicio']) : null;
    $fecha_fin = !empty($_POST['fechaFin']) ? trim($_POST['fechaFin']) : null;

    // Validamos que los campos obligatorios no estén vacíos
    if (!empty($nombre) && !empty($categoria) && !empty($disciplina) && !empty($formato) && !empty($cantParticipantes)) {
        
        $sqlTorneo = "INSERT INTO Torneo (id_usuario, nombre, descripcion, categoria, disciplina, formato, cantParticipantes, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conexion->prepare($sqlTorneo)) {
            // Tipos: 
            // 1. id_usuario (i)
            // 2. nombre (s)
            // 3. descripcion (s)
            // 4. categoria (s)
            // 5. disciplina (s)
            // 6. formato (s)
            // 7. cantParticipantes (i - si en la BD es numérico) o (s) si es texto. Usaremos 'i' asumiendo entero.
            // 8. fecha_inicio (s)
            // 9. fecha_fin (s)
            $stmt->bind_param("isssssiss", $id_usuario, $nombre, $descripcion, $categoria, $disciplina, $formato, $cantParticipantes, $fecha_inicio, $fecha_fin);
            
            try {
                if ($stmt->execute()) {
                    echo "¡Torneo creado exitosamente!";
                }
            } catch (mysqli_sql_exception $e) {
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
        echo "Por favor, completa todos los campos obligatorios. (Asegúrate de que la categoría no esté vacía).";
    }

    $conexion->close();
}
?>