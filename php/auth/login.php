<?php
session_start(); // Inicia la sesión para almacenar el ID del usuario

$user_input = $_POST["user"] ?? '';
$mail_input = $_POST["mail"] ?? '';
$pass = $_POST["pass"] ?? '';

class LoginManager {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    public function autenticar(string $user, string $mail, string $password): ?array {
        $identificador = !empty($user) ? $user : $mail;

        if (empty($identificador)) {
            return null;
        }

        // Se incluye id_usuario en la consulta para poder rescatarlo
        $sql = "SELECT id_usuario, nombre, email, contrasena FROM usuario WHERE nombre = :id OR email = :id LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':id' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            return $usuario; 
        }
        
        return null; 
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $auth = new LoginManager($pdo);
    $resultado = $auth->autenticar($user_input, $mail_input, $pass);

    if ($resultado) {
        // Se guardan los datos clave en las variables de sesión
        $_SESSION['id_usuario'] = $resultado['id_usuario'];
        $_SESSION['nombre'] = $resultado['nombre'];

        // Redirección exitosa solicitada
        header("Location: ../../html/login/");
        exit();
    } else {
        echo "Usuario, correo o contraseña incorrectos.";
        exit();
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>