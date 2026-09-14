<?php

$server = "localhost";
$user = "root";
$pass = "";
$database = "starleague";

try{
$conexion= new PDO("mysql:host=10.0.0.129;dbname=$database;charset=utf8", $user, $pass);
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "Conectado";
}catch (PDOException $e) {
die("Connection failed: " . $e->getMessage()
?>
