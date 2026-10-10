<?php
declare(strict_types=1);

/**
 * Conexión PDO única (Singleton) para toda la aplicación.
 * Usa el usuario restringido app_starleague (ver database/dcl_usuarios.sql),
 * nunca root. Las credenciales salen de config.local.php o de variables de
 * entorno (Docker); no están escritas en el código.
 */
final class Database
{
    private static ?PDO $instancia = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instancia === null) {
            $c = self::cargarConfiguracion();

            $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";

            try {
                self::$instancia = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // Las fechas de creación se registran en horario de Uruguay.
                self::$instancia->exec("SET time_zone = '-03:00'");
            } catch (PDOException $e) {
                // El detalle va al log del servidor, no al usuario.
                error_log('[StarLeague] Fallo de conexión a la BD: ' . $e->getMessage());
                throw new RuntimeException('No se pudo conectar con la base de datos.');
            }
        }

        return self::$instancia;
    }

    private static function cargarConfiguracion(): array
    {
        $porDefecto = [
            'host'    => 'localhost',
            'name'    => 'starleague',
            'user'    => 'app_starleague',
            'pass'    => 'StarLeague2026!Pass',
            'charset' => 'utf8mb4',
        ];

        $archivo = __DIR__ . '/config.local.php';
        if (is_file($archivo)) {
            return array_merge($porDefecto, require $archivo);
        }

        // Docker define DB_HOST, DB_NAME, DB_USER y DB_PASS (ver docker-compose.yml);
        // STARLEAGUE_DB_* se acepta como alternativa en instalaciones sin Docker.
        $env = static fn(string $clave): string|false =>
            getenv("DB_$clave") !== false ? getenv("DB_$clave") : getenv("STARLEAGUE_DB_$clave");

        return [
            'host'    => $env('HOST') ?: $porDefecto['host'],
            'name'    => $env('NAME') ?: $porDefecto['name'],
            'user'    => $env('USER') ?: $porDefecto['user'],
            'pass'    => $env('PASS') !== false ? $env('PASS') : $porDefecto['pass'],
            'charset' => $porDefecto['charset'],
        ];
    }
}
