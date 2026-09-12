<?php
header('Content-Type: application/json; charset=utf-8');

/*
 * Antes crear-equipos.js guardaba el objeto "datos" directo en localStorage.
 * Ahora ese mismo objeto llega acá vía fetch() como JSON en el body,
 * en vez de venir por $_POST de un <form> (como login.php/register.php).
 */
$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => "No se recibió un objeto JSON válido."]);
    exit();
}

class GestorEquipos {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    // catalogos.js manda el nombre completo del deporte (ej: "League of Legends"),
    // no el id numérico de la tabla `deporte`. Lo resolvemos acá.
    public function resolverDeporte(string $nombreDeporte): int {
        $sql = "SELECT id_deporte FROM deporte WHERE nombre = :nombre LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':nombre' => $nombreDeporte]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new Exception("La disciplina '$nombreDeporte' no existe en la tabla deporte (¿corriste el seed del patch_equipos_fetch.sql?).");
        }

        return (int) $fila['id_deporte'];
    }

    // El logo llega como dataURL base64 (lo arma convertirImagenBase64() en el JS).
    // Siguiendo el mismo criterio que ya se documentó para logo/banner de torneo,
    // NO lo guardamos en la BD: lo escribimos a disco y guardamos la ruta.
    public function guardarLogo(?string $dataUrl, string $tag): ?string {
        if (empty($dataUrl) || !str_contains($dataUrl, 'base64,')) {
            return null;
        }

        [$cabecera, $contenido] = explode('base64,', $dataUrl, 2);
        $binario = base64_decode($contenido);

        if ($binario === false) {
            throw new Exception("El logo recibido no es una imagen válida.");
        }

        $extension = 'png';
        if (str_contains($cabecera, 'jpeg')) $extension = 'jpg';
        if (str_contains($cabecera, 'webp')) $extension = 'webp';

        $carpeta = __DIR__ . '/../uploads/equipos/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $nombreArchivo = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $tag)) . '_' . uniqid() . '.' . $extension;
        file_put_contents($carpeta . $nombreArchivo, $binario);

        return 'uploads/equipos/' . $nombreArchivo;
    }

    public function crearEquipo(array $datos): array {
        if (empty($datos['nombre']) || empty($datos['tag']) || empty($datos['deporte'])) {
            throw new Exception("Faltan datos obligatorios (nombre, tag o deporte).");
        }

        $idDeporte = $this->resolverDeporte($datos['deporte']);
        $logoPath  = $this->guardarLogo($datos['logo'] ?? null, $datos['tag']);

        $sql = "INSERT INTO equipo (nombre, tag, descripcion, pais, logo_path, id_deporte)
                VALUES (:nombre, :tag, :descripcion, :pais, :logo_path, :id_deporte)";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':tag'         => $datos['tag'],
            ':descripcion' => $datos['descripcion'] ?? null,
            ':pais'        => $datos['pais'] ?? null,
            ':logo_path'   => $logoPath,
            ':id_deporte'  => $idDeporte,
        ]);

        $idEquipo = (int) $this->bd->lastInsertId();

        $advertencias = $this->vincularIntegrantes($idEquipo, $datos['integrantes'] ?? []);

        return [
            'id_equipo'    => $idEquipo,
            'advertencias' => $advertencias,
        ];
    }

    // OJO: los "jugadores" que arma crear-equipos.js todavía son datos de
    // prueba hardcodeados (ids 1-10), no usuarios reales de la BD. Si el id
    // no existe como usuario, no tiramos abajo la creación del equipo entero:
    // lo salteamos y devolvemos una advertencia para que se note en el front.
    private function vincularIntegrantes(int $idEquipo, array $integrantes): array {
        $advertencias = [];

        $sql = "INSERT INTO equipo_miembro (id_equipo, id_usuario, rol) VALUES (:id_equipo, :id_usuario, :rol)";
        $stmt = $this->bd->prepare($sql);

        foreach ($integrantes as $integrante) {
            $idUsuario = $integrante['jugadorId'] ?? null;
            $rol       = $integrante['rol'] ?? null;

            if (!$idUsuario) {
                continue;
            }

            try {
                $stmt->execute([
                    ':id_equipo'  => $idEquipo,
                    ':id_usuario' => $idUsuario,
                    ':rol'        => $rol,
                ]);
            } catch (PDOException $e) {
                $nombreJugador = $integrante['jugador'] ?? "id $idUsuario";
                $advertencias[] = "No se pudo vincular a '$nombreJugador': todavía no existe como usuario real en la base de datos.";
            }
        }

        return $advertencias;
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $gestor    = new GestorEquipos($pdo);
    $resultado = $gestor->crearEquipo($datos);

    echo json_encode([
        "ok"      => true,
        "mensaje" => "¡Equipo creado con éxito!",
        "data"    => $resultado,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "Error de conexión: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => $e->getMessage()]);
}
