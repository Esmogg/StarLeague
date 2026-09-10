<?php
$user = $_POST["new_user"];
$mail = $_POST["mail"]; // 1. Esto ya estaba, captura el email del formulario
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

    // 2. MODIFICADO: Ahora verifica si ya existe el usuario O el email
    public function existeUsuario(string $user, string $mail): bool {
        $sql = "SELECT id FROM usuarios WHERE username = :user OR email = :mail LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':user' => $user,
            ':mail' => $mail
        ]);
        
        return $stmt->fetch() !== false;
    }

    // 3. MODIFICADO: Añadimos 'email' en la sentencia INSERT y en los parámetros
    public function registrar(string $user, string $mail, string $hash): bool {
        $sql = "INSERT INTO usuarios (username, email, password_hash) VALUES (:user, :mail, :pass)";
        $stmt = $this->bd->prepare($sql);
        
        return $stmt->execute([
            ':user' => $user,
            ':mail' => $mail,
            ':pass' => $hash
        ]);
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $gestionUsuarios = new Usuarios($pdo);

    // 4. MODIFICADO: Pasamos también la variable $mail a la función de verificación
    if ($gestionUsuarios->existeUsuario($user, $mail)) {
        echo "El nombre de usuario o el correo electrónico ya están registrados.";
    } else {
        // 5. MODIFICADO: Pasamos el $mail a la función registrar
        if ($gestionUsuarios->registrar($user, $mail, $hash)) {
            echo "¡Usuario registrado con éxito!";
        } else {
            echo "Hubo un error al registrar el usuario.";
        }
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
