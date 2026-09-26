<?php
session_start();
include("../conexion.php");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    die("Acceso denegado. Debes iniciar sesión para crear un equipo.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    // CORREGIDO: Se usa 'deporte' que es el name que viene del formulario HTML
    $disciplina = trim($_POST['deporte'] ?? '');
    $id_usuario = $_SESSION['id_usuario'];

    if (!empty($nombre) && !empty($disciplina)) {
        // 1. Insertar el equipo con su nombre y disciplina
        $sqlEquipo = "INSERT INTO Equipo (nombre, disciplina) VALUES (?, ?)";
        
        if ($stmt = $conexion->prepare($sqlEquipo)) {
            $stmt->bind_param("ss", $nombre, $disciplina);
            
            try {
                if ($stmt->execute()) {
                    $id_equipo = $conexion->insert_id;
                    $stmt->close();

                    // 2. Registrar en CrearEquipo
                    $sqlCrear = "INSERT INTO CrearEquipo (id_equipo, id_usuario) VALUES (?, ?)";
                    if ($stmtCrear = $conexion->prepare($sqlCrear)) {
                        $stmtCrear->bind_param("ii", $id_equipo, $id_usuario);
                        $stmtCrear->execute();
                        $stmtCrear->close();
                    }

                    // 3. Registrar en UnirseEquipo
                    $sqlUnirse = "INSERT INTO UnirseEquipo (id_equipo, id_usuario) VALUES (?, ?)";
                    if ($stmtUnirse = $conexion->prepare($sqlUnirse)) {
                        $stmtUnirse->bind_param("ii", $id_equipo, $id_usuario);
                        $stmtUnirse->execute();
                        $stmtUnirse->close();
                    }

                    echo "¡Equipo creado exitosamente!";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    echo "El nombre del equipo ya está en uso. Por favor, elige otro.";
                } else {
                    echo "Error al registrar el equipo: " . $e->getMessage();
                }
            }
        }
    } else {
        echo "Por favor, completa todos los campos (incluyendo la disciplina).";
    }

    $conexion->close();
}
?>