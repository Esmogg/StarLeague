<<<<<<< HEAD
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
=======
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
>>>>>>> f75b60736b5e44e563ad81bb053e4e7ef354fa68
?>