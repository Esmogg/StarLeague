<?php
declare(strict_types=1);

/**
 * Firma digital Ed25519 (libsodium) del comprobante de inscripción.
 *
 * La clave secreta (64 bytes, en base64) sale de la variable de entorno
 * FIRMA_CLAVE_PRIVADA; la clave pública se deriva de ella. El comprobante es
 * un texto "payload.firma", ambos en base64url: el payload es un JSON con los
 * datos de la inscripción y la firma cubre exactamente esos bytes. Si alguien
 * cambia una sola letra del payload, la verificación falla.
 */
final class Firma
{
    private const VERSION = 1;

    private static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private static function deB64url(string $texto): ?string
    {
        if ($texto === '' || preg_match('/^[A-Za-z0-9_-]+$/', $texto) !== 1) {
            return null;
        }
        $bin = base64_decode(strtr($texto, '-_', '+/'), true);
        return $bin === false ? null : $bin;
    }

    /** Clave secreta Ed25519 o null si no está configurada o es inválida. */
    private static function claveSecreta(): ?string
    {
        $b64 = getenv('FIRMA_CLAVE_PRIVADA');
        if (!is_string($b64) || $b64 === '') {
            return null;
        }
        $bin = base64_decode($b64, true);
        return ($bin !== false && strlen($bin) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) ? $bin : null;
    }

    public static function disponible(): bool
    {
        return self::claveSecreta() !== null;
    }

    /** Clave pública en base64 (para publicarla o verificar fuera del sistema). */
    public static function clavePublica(): ?string
    {
        $sk = self::claveSecreta();
        return $sk === null ? null : base64_encode(sodium_crypto_sign_publickey_from_secretkey($sk));
    }

    /** Firma los datos y devuelve el comprobante "payload.firma"; null si no hay clave. */
    public static function emitir(array $datos): ?string
    {
        $sk = self::claveSecreta();
        if ($sk === null) {
            return null;
        }
        $payload = json_encode(['v' => self::VERSION] + $datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return null;
        }
        $firma = sodium_crypto_sign_detached($payload, $sk);
        return self::b64url($payload) . '.' . self::b64url($firma);
    }

    /** Devuelve los datos si el comprobante es auténtico; null si fue alterado o es inválido. */
    public static function verificar(string $comprobante): ?array
    {
        $sk = self::claveSecreta();
        $partes = explode('.', trim($comprobante));
        if ($sk === null || count($partes) !== 2) {
            return null;
        }
        $payload = self::deB64url($partes[0]);
        $firma   = self::deB64url($partes[1]);
        if ($payload === null || $firma === null || strlen($firma) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }
        $pk = sodium_crypto_sign_publickey_from_secretkey($sk);
        if (!sodium_crypto_sign_verify_detached($firma, $payload, $pk)) {
            return null;
        }
        $datos = json_decode($payload, true);
        return (is_array($datos) && ($datos['v'] ?? null) === self::VERSION) ? $datos : null;
    }
}
