<?php
declare(strict_types=1);

/**
 * Reglas de validación del servidor.
 *
 * Cada método devuelve null si el valor es válido o el mensaje de error si no.
 * Las MISMAS reglas y los MISMOS mensajes están en js/validaciones.js (frontend).
 * El frontend solo avisa antes de enviar; esta clase es la que realmente protege,
 * porque una petición hecha con curl o Postman no pasa por el JavaScript.
 * tests/casos_validacion.json contiene los casos que prueban que ambos lados
 * responden igual (tests/ValidadorTest.php y tests/validaciones.test.js).
 */
final class Validador
{
    public const USUARIO_MIN = 3;
    public const USUARIO_MAX = 30;
    public const EMAIL_MAX = 150;
    public const PASS_MIN = 8;
    public const PASS_MAX_BYTES = 72;   // bcrypt solo usa los primeros 72 bytes
    public const NOMBRE_MAX = 50;
    public const DESCRIPCION_MAX = 500;
    public const PARTICIPANTES_MIN = 2;
    public const PARTICIPANTES_MAX = 1024;

    private const RE_USUARIO = '/^[A-Za-z0-9._-]+$/D';
    private const RE_NOMBRE = '/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 ]+$/uD';
    private const RE_DESCRIPCION = '/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 .,!?¿¡:;()\-\/]+$/uD';
    private const RE_EMAIL = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/D';

    public static function usuario(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 'El nombre de usuario es obligatorio.';
        }
        $n = longitud($valor);
        if ($n < self::USUARIO_MIN || $n > self::USUARIO_MAX) {
            return 'El nombre de usuario debe tener entre 3 y 30 caracteres.';
        }
        if (!preg_match(self::RE_USUARIO, $valor)) {
            return 'El nombre de usuario solo puede tener letras, números, punto, guion y guion bajo.';
        }
        return null;
    }

    public static function email(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 'El correo electrónico es obligatorio.';
        }
        if (longitud($valor) > self::EMAIL_MAX) {
            return 'El correo electrónico no puede superar los 150 caracteres.';
        }
        // Mismo patrón que el frontend, más la validación estricta de PHP
        if (!preg_match(self::RE_EMAIL, $valor) || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }
        return null;
    }

    /**
     * Política de contraseñas: 8 a 72 bytes, sin espacios, con una mayúscula, un número
     * y un carácter especial, y sin contener el nombre de usuario.
     * $usuario es opcional (el login no lo usa; solo el registro).
     */
    public static function password(string $valor, string $usuario = ''): ?string
    {
        if (longitud($valor) < self::PASS_MIN) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (strlen($valor) > self::PASS_MAX_BYTES) {
            return 'La contraseña no puede superar los 72 bytes (las tildes y símbolos ocupan más de uno).';
        }
        if (preg_match('/\s/u', $valor)) {
            return 'La contraseña no puede contener espacios.';
        }
        if (!preg_match('/\p{Lu}/u', $valor)) {
            return 'La contraseña debe incluir al menos una letra mayúscula.';
        }
        if (!preg_match('/[0-9]/', $valor)) {
            return 'La contraseña debe incluir al menos un número.';
        }
        if (!preg_match('/[^\p{L}\p{N}\s]/u', $valor)) {
            return 'La contraseña debe incluir al menos un carácter especial (por ejemplo * ! $ #).';
        }
        $usuario = trim($usuario);
        if (longitud($usuario) >= self::USUARIO_MIN && preg_match('/' . preg_quote($usuario, '/') . '/iu', $valor) === 1) {
            return 'La contraseña no puede contener el nombre de usuario.';
        }
        return null;
    }

    /** $que: 'equipo' o 'torneo' (solo cambia el texto del mensaje). */
    public static function nombre(string $valor, string $que): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return "El nombre del $que es obligatorio.";
        }
        if (longitud($valor) > self::NOMBRE_MAX) {
            return "El nombre del $que no puede superar los 50 caracteres.";
        }
        if (!preg_match(self::RE_NOMBRE, $valor)) {
            return "El nombre del $que contiene caracteres no permitidos.";
        }
        return null;
    }

    public static function descripcion(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 'La descripción es obligatoria.';
        }
        if (longitud($valor) > self::DESCRIPCION_MAX) {
            return 'La descripción no puede superar los 500 caracteres.';
        }
        if (!preg_match(self::RE_DESCRIPCION, $valor)) {
            return 'La descripción contiene caracteres no permitidos.';
        }
        return null;
    }

    /** Acepta un entero o un texto con solo dígitos ("8"); rechaza "abc", "5.5" o "-3". */
    public static function participantes(mixed $valor): ?string
    {
        $mensaje = 'La cantidad de participantes debe ser un número entero entre 2 y 1024.';
        if (is_string($valor)) {
            $valor = trim($valor);
            if (!preg_match('/^[0-9]{1,6}$/D', $valor)) {
                return $mensaje;
            }
            $valor = (int) $valor;
        }
        if (!is_int($valor) || $valor < self::PARTICIPANTES_MIN || $valor > self::PARTICIPANTES_MAX) {
            return $mensaje;
        }
        return null;
    }

    public static function fechas(string $inicio, string $fin): ?string
    {
        $inicio = trim($inicio);
        $fin = trim($fin);
        if (!self::fechaValida($inicio) || !self::fechaValida($fin)) {
            return 'Debes indicar una fecha de inicio y una de fin válidas.';
        }
        if ($fin < $inicio) {
            return 'La fecha de fin no puede ser anterior a la de inicio.';
        }
        return null;
    }

    private static function fechaValida(string $f): bool
    {
        $d = DateTime::createFromFormat('!Y-m-d', $f);
        return $d !== false && $d->format('Y-m-d') === $f;
    }
}
