<?php


$server = "localhost";
$user = "root";
$pass = "";
$database = "starleague";

$conexion= new mysqli($server, $user, $pass, $database);
if ($conexion->connect_error) {
    die("Connection failed: " . $conexion->connect_error);
    }else {
    echo "Conectado";
    }
?>