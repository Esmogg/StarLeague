<?php
header('Content-Type: application/json; charset=utf-8');

/*
 * Mismo trabajo que register.php (crear el usuario), pero el objeto llega
 * como JSON por fetch() en vez de $_POST de un <form> con submit nativo.
 */
$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => "No se recibió un objeto JSON válido."]);
    exit();
}

$user  = $datos['new_user']  ?? '';
$mail  = $datos['mail']      ?? '';
$pass  = $datos['new_pass']  ?? '';
$pass2 = $datos['new_pass2'] ?? '';

if ($pass !== $pass2) {
    echo json_encode(["ok" => false, "mensaje" => "Las contraseñas no coinciden."]);
    exit();
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

class GestorUsuarios {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    public function existeUsuario(string $user, string $mail): bool {
        $sql = "SELECT id_usuario FROM usuario WHERE nombre = :user OR email = :mail LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':user' => $user,
            ':mail' => $mail,
        ]);

        return $stmt->fetch() !== false;
    }

    public function registrar(string $user, string $mail, string $hash): bool {
        $sql = "INSERT INTO usuario (nombre, email, contrasena) VALUES (:user, :mail, :pass)";
        $stmt = $this->bd->prepare($sql);

        return $stmt->execute([
            ':user' => $user,
            ':mail' => $mail,
            ':pass' => $hash,
        ]);
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $gestor = new GestorUsuarios($pdo);

    if ($gestor->existeUsuario($user, $mail)) {
        echo json_encode(["ok" => false, "mensaje" => "El nombre de usuario o el correo electrónico ya están registrados."]);
    } elseif ($gestor->registrar($user, $mail, $hash)) {
        echo json_encode(["ok" => true, "mensaje" => "¡Usuario registrado con éxito!"]);
    } else {
        echo json_encode(["ok" => false, "mensaje" => "Hubo un error al registrar el usuario."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "Error de conexión: " . $e->getMessage()]);
}
