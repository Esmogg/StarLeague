<?php
$user_input = $_POST["user"] ?? '';
$mail_input = $_POST["mail"] ?? '';
$pass = $_POST["pass"] ?? '';

class LoginManager {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    // Método POO adaptado para recibir tanto usuario como correo del formulario
    public function autenticar(string $user, string $mail, string $password): ?array {
        // Determinamos cuál de los dos campos llenó el usuario
        $identificador = !empty($user) ? $user : $mail;

        if (empty($identificador)) {
            return null;
        }

        // Buscamos en la BD si coincide con el username o con el email
        $sql = "SELECT username, email, password_hash FROM usuarios WHERE username = :id OR email = :id LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':id' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificamos el hash de la contraseña
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            return $usuario; 
        }
        
        return null; 
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $auth = new LoginManager($pdo);
    // Pasamos las variables correspondientes al método
    $resultado = $auth->autenticar($user_input, $mail_input, $pass);

    if ($resultado) {
        echo "¡Bienvenido, " . htmlspecialchars($resultado['username']) . "!";
        // header("Location: ../html/usuario/dashboard.html");
        exit();
    } else {
        echo "Usuario, correo o contraseña incorrectos.";
        // header("Location: ../html/usuario/login.html");
        exit();
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
