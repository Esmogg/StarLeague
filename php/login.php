<<<<<<< HEAD
<?php
$user = $_POST["user"];
$mail = $_POST["mail"];
$pass = $_POST["pass"];
$pruebaUser ="admin";
$pruebaPass="admin"; //quitar mas tarde
$hash=password_hash($pruebaPass, PASSWORD_DEFAULT); //debe recolectar datos de la bd
if ($user == $pruebaUser && password_verify($pass, $hash)){
    echo "Bienvenido";
    header("Location: ../html/usuario/login.html");
    exit();
}else{
    echo "Usuario Incorrecto";
    header("Location: ../html/usuario/register.html");
    exit();
}
=======
<?php
$user = $_POST["user"];
$mail = $_POST["mail"];
$pass = $_POST["pass"];
$pruebaUser ="admin";
$pruebaPass="admin"; //quitar mas tarde
$hash=password_hash($pruebaPass, PASSWORD_DEFAULT); //debe recolectar datos de la bd
if ($user == $pruebaUser && password_verify($pass, $hash)){
    echo "Bienvenido";
    header("Location: ../html/usuario/login.html");
    exit();
}else{
    echo "Usuario Incorrecto";
    header("Location: ../html/usuario/register.html");
    exit();
}
>>>>>>> f75b60736b5e44e563ad81bb053e4e7ef354fa68
?>