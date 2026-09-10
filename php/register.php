<?php
$user = $_POST["new_user"];
$mail = $_POST["mail"];
$pass = $_POST["new_pass"];
$pass2 = $_POST["new_pass2"];

if ($pass !== $pass2){
    echo "Las contraseñas no coinciden";
    exit;
} else {
    echo "Las contraseñas coinciden. ";
    $hash = password_hash($pass, PASSWORD_DEFAULT);
}

class Usuarios {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    // 1. Buscamos usando el nombre de columna real: username
    public function existeUsuario(string $user): bool {
        $sql = "SELECT id FROM usuarios WHERE username = :user LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':user' => $user]);
        
        return $stmt->fetch() !== false;
    }

    // 2. Insertamos usando exactamente las columnas que tiene tu tabla
    public function registrar(string $user, string $hash): bool {
        $sql = "INSERT INTO usuarios (username, password_hash) VALUES (:user, :pass)";
        $stmt = $this->bd->prepare($sql);
        
        return $stmt->execute([
            ':user' => $user,
            ':pass' => $hash
        ]);
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $gestionUsuarios = new Usuarios($pdo);

    if ($gestionUsuarios->existeUsuario($user)) {
        echo "El nombre de usuario ya está registrado.";
    } else {
        if ($gestionUsuarios->registrar($user, $hash)) {
            echo "¡Usuario registrado con éxito!";
        } else {
            echo "Hubo un error al registrar el usuario.";
        }
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>