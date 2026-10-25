<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Verifica un comprobante de inscripción firmado. Es público (cualquiera puede
 * comprobar un comprobante) y no modifica nada, por eso no exige sesión ni CSRF.
 */
exigirMetodo('POST');

$comprobante = trim((string) (leerCuerpo()['comprobante'] ?? ''));
if ($comprobante === '' || strlen($comprobante) > 4000) {
    responderJson(['ok' => false, 'mensaje' => 'Pega un comprobante válido.'], 400);
}
if (!Firma::disponible()) {
    responderJson(['ok' => false, 'mensaje' => 'La verificación no está disponible en este servidor.'], 503);
}

$datos = Firma::verificar($comprobante);
registrarEvento($datos === null ? 'comprobante_invalido' : 'comprobante_valido');

if ($datos === null) {
    responderJson(['ok' => true, 'valido' => false, 'mensaje' => 'El comprobante no es auténtico o fue modificado.']);
}
responderJson(['ok' => true, 'valido' => true, 'mensaje' => 'Comprobante auténtico.', 'datos' => $datos]);
