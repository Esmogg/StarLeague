<?php
header('Content-Type: application/json; charset=utf-8');

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => "No se recibió un objeto JSON válido."]);
    exit();
}

class GestorTorneos {
    private $bd;

    public function __construct(PDO $conexionBD) {
        $this->bd = $conexionBD;
    }

    public function resolverDeporte(string $nombreDeporte): int {
        $sql = "SELECT id_deporte FROM deporte WHERE nombre = :nombre LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':nombre' => $nombreDeporte]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new Exception("La disciplina '$nombreDeporte' no existe en la tabla deporte.");
        }

        return (int) $fila['id_deporte'];
    }

    public function resolverFormato(string $nombreFormato): int {
        $sql = "SELECT id_formato FROM formato WHERE nombre = :nombre LIMIT 1";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':nombre' => $nombreFormato]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new Exception("El formato '$nombreFormato' no existe en la tabla formato.");
        }

        return (int) $fila['id_formato'];
    }

    public function guardarImagen(?string $dataUrl, string $prefijo, string $carpetaDestino): ?string {
        if (empty($dataUrl) || !str_contains($dataUrl, 'base64,')) {
            return null;
        }

        [$cabecera, $contenido] = explode('base64,', $dataUrl, 2);
        $binario = base64_decode($contenido);

        if ($binario === false) {
            return null;
        }

        $extension = 'png';
        if (str_contains($cabecera, 'jpeg')) $extension = 'jpg';
        if (str_contains($cabecera, 'webp')) $extension = 'webp';

        $carpeta = __DIR__ . '/../uploads/' . $carpetaDestino . '/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $nombreArchivo = strtolower($prefijo) . '_' . uniqid() . '.' . $extension;
        file_put_contents($carpeta . $nombreArchivo, $binario);

        return 'uploads/' . $carpetaDestino . '/' . $nombreArchivo;
    }

    public function crearTorneo(array $datos): int {
        if (empty($datos['nombre']) || empty($datos['fecha_inicio']) || empty($datos['fecha_fin']) || empty($datos['deporte']) || empty($datos['formato'])) {
            throw new Exception("Faltan datos obligatorios para crear el torneo.");
        }

        $idDeporte  = $this->resolverDeporte($datos['deporte']);
        $idFormato  = $this->resolverFormato($datos['formato']);
        
        // Simulamos un id_creador (puedes adaptarlo con la sesión del usuario logueado, ej: $_SESSION['id_usuario'])
        $idCreador  = $datos['id_creador'] ?? 1; 

        $logoPath   = $this->guardarImagen($datos['logo'] ?? null, 'logo', 'torneos');
        $bannerPath = $this->guardarImagen($datos['banner'] ?? null, 'banner', 'torneos');

        $sql = "INSERT INTO torneo (nombre, fecha_inicio, fecha_fin, logo_path, banner_path, id_creador, id_deporte, id_formato)
                VALUES (:nombre, :fecha_inicio, :fecha_fin, :logo_path, :banner_path, :id_creador, :id_deporte, :id_formato)";
        
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':nombre'       => $datos['nombre'],
            ':fecha_inicio' => $datos['fecha_inicio'],
            ':fecha_fin'    => $datos['fecha_fin'],
            ':logo_path'    => $logoPath,
            ':banner_path'  => $bannerPath,
            ':id_creador'   => $idCreador,
            ':id_deporte'   => $idDeporte,
            ':id_formato'   => $idFormato,
        ]);

        $idTorneo = (int) $this->bd->lastInsertId();

        // Guardar configuración específica según el deporte
        $this->guardarConfiguracionEspecifica($idTorneo, $datos['deporte'], $datos['config'] ?? []);

        return $idTorneo;
    }

    private function guardarConfiguracionEspecifica(int $idTorneo, string $deporte, array $config): void {
        $deporteLower = strtolower($deporte);

        if (str_contains($deporteLower, 'league of legends') || str_contains($deporteLower, 'lol')) {
            $sql = "INSERT INTO config_lol (id_torneo, mejor_de, seleccion_lado) VALUES (:id_torneo, :mejor_de, :seleccion_lado)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([
                ':id_torneo'      => $idTorneo,
                ':mejor_de'       => $config['mejor_de'] ?? 1,
                ':seleccion_lado' => $config['seleccion_lado'] ?? 'aleatorio'
            ]);
        } elseif (str_contains($deporteLower, 'valorant')) {
            $sql = "INSERT INTO config_valorant (id_torneo, mejor_de, seleccion_mapas) VALUES (:id_torneo, :mejor_de, :seleccion_mapas)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([
                ':id_torneo'       => $idTorneo,
                ':mejor_de'        => $config['mejor_de'] ?? 1,
                ':seleccion_mapas' => $config['seleccion_mapas'] ?? 'aleatorio'
            ]);
        } elseif (str_contains($deporteLower, 'ajedrez')) {
            $sql = "INSERT INTO config_ajedrez (id_torneo, ritmo_juego, control_tiempo) VALUES (:id_torneo, :ritmo_juego, :control_tiempo)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([
                ':id_torneo'     => $idTorneo,
                ':ritmo_juego'   => $config['ritmo_juego'] ?? 'clasico',
                ':control_tiempo'=> $config['control_tiempo'] ?? null
            ]);
        } elseif (str_contains($deporteLower, 'tenis')) {
            $sql = "INSERT INTO config_tenis (id_torneo, sets_para_ganar, usar_tiebreak) VALUES (:id_torneo, :sets_para_ganar, :usar_tiebreak)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([
                ':id_torneo'       => $idTorneo,
                ':sets_para_ganar' => $config['sets_para_ganar'] ?? 2,
                ':usar_tiebreak'   => $config['usar_tiebreak'] ?? 1
            ]);
        }
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=starleague;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $gestor    = new GestorTorneos($pdo);
    $idTorneo  = $gestor->crearTorneo($datos);

    echo json_encode([
        "ok"      => true,
        "mensaje" => "¡Torneo creado con éxito!",
        "data"    => ["id_torneo" => $idTorneo]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "Error en la base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => $e->getMessage()]);
}
?>