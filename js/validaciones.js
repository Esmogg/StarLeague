/*
 * Reglas de validación del frontend.
 *
 * Son las MISMAS reglas y los MISMOS mensajes que php/config/Validador.php.
 * Aquí solo sirven para avisar al usuario antes de enviar el formulario;
 * la protección real está en el servidor, que vuelve a validar todo.
 * tests/casos_validacion.json prueba que ambos lados responden igual.
 *
 * Cada función devuelve null si el valor es válido o el mensaje de error.
 */
const Validaciones = (() => {
  const USUARIO_MIN = 3;
  const USUARIO_MAX = 30;
  const EMAIL_MAX = 150;
  const PASS_MIN = 8;
  const PASS_MAX_BYTES = 72;
  const NOMBRE_MAX = 50;
  const DESCRIPCION_MAX = 500;
  const PARTICIPANTES_MIN = 2;
  const PARTICIPANTES_MAX = 1024;

  const RE_USUARIO = /^[A-Za-z0-9._-]+$/;
  const RE_NOMBRE = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 ]+$/;
  const RE_DESCRIPCION = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 .,!?¿¡:;()\-/]+$/;
  const RE_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /* Largo en caracteres (un emoji cuenta como 1, igual que en PHP con /u) */
  const largo = (texto) => Array.from(texto).length;
  /* Largo en bytes UTF-8 (bcrypt solo usa los primeros 72) */
  const bytes = (texto) => new TextEncoder().encode(texto).length;

  function usuario(valor) {
    valor = String(valor ?? "").trim();
    if (valor === "") return "El nombre de usuario es obligatorio.";
    const n = largo(valor);
    if (n < USUARIO_MIN || n > USUARIO_MAX) {
      return "El nombre de usuario debe tener entre 3 y 30 caracteres.";
    }
    if (!RE_USUARIO.test(valor)) {
      return "El nombre de usuario solo puede tener letras, números, punto, guion y guion bajo.";
    }
    return null;
  }

  function email(valor) {
    valor = String(valor ?? "").trim();
    if (valor === "") return "El correo electrónico es obligatorio.";
    if (largo(valor) > EMAIL_MAX) {
      return "El correo electrónico no puede superar los 150 caracteres.";
    }
    if (!RE_EMAIL.test(valor)) return "El correo electrónico no es válido.";
    return null;
  }

  /*
   * Política de contraseñas: 8 a 72 bytes, sin espacios, con una mayúscula, un número
   * y un carácter especial, y sin contener el nombre de usuario.
   * usuario es opcional (el login no lo usa; solo el registro).
   */
  function password(valor, usuario = "") {
    valor = String(valor ?? "");
    if (largo(valor) < PASS_MIN) {
      return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (bytes(valor) > PASS_MAX_BYTES) {
      return "La contraseña no puede superar los 72 bytes (las tildes y símbolos ocupan más de uno).";
    }
    if (/\s/u.test(valor)) {
      return "La contraseña no puede contener espacios.";
    }
    if (!/\p{Lu}/u.test(valor)) {
      return "La contraseña debe incluir al menos una letra mayúscula.";
    }
    if (!/[0-9]/.test(valor)) {
      return "La contraseña debe incluir al menos un número.";
    }
    if (!/[^\p{L}\p{N}\s]/u.test(valor)) {
      return "La contraseña debe incluir al menos un carácter especial (por ejemplo * ! $ #).";
    }
    usuario = String(usuario ?? "").trim();
    if (largo(usuario) >= USUARIO_MIN && valor.toLowerCase().includes(usuario.toLowerCase())) {
      return "La contraseña no puede contener el nombre de usuario.";
    }
    return null;
  }

  /* que: "equipo" o "torneo" (solo cambia el texto del mensaje) */
  function nombre(valor, que) {
    valor = String(valor ?? "").trim();
    if (valor === "") return `El nombre del ${que} es obligatorio.`;
    if (largo(valor) > NOMBRE_MAX) {
      return `El nombre del ${que} no puede superar los 50 caracteres.`;
    }
    if (!RE_NOMBRE.test(valor)) {
      return `El nombre del ${que} contiene caracteres no permitidos.`;
    }
    return null;
  }

  function descripcion(valor) {
    valor = String(valor ?? "").trim();
    if (valor === "") return "La descripción es obligatoria.";
    if (largo(valor) > DESCRIPCION_MAX) {
      return "La descripción no puede superar los 500 caracteres.";
    }
    if (!RE_DESCRIPCION.test(valor)) {
      return "La descripción contiene caracteres no permitidos.";
    }
    return null;
  }

  /* Acepta un entero o un texto con solo dígitos ("8"); rechaza "abc", "5.5" o "-3" */
  function participantes(valor) {
    const mensaje =
      "La cantidad de participantes debe ser un número entero entre 2 y 1024.";
    if (typeof valor === "string") {
      valor = valor.trim();
      if (!/^[0-9]{1,6}$/.test(valor)) return mensaje;
      valor = Number(valor);
    }
    if (
      !Number.isInteger(valor) ||
      valor < PARTICIPANTES_MIN ||
      valor > PARTICIPANTES_MAX
    ) {
      return mensaje;
    }
    return null;
  }

  function fechaValida(f) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(f)) return false;
    const [a, m, d] = f.split("-").map(Number);
    const fecha = new Date(Date.UTC(a, m - 1, d));
    return (
      fecha.getUTCFullYear() === a &&
      fecha.getUTCMonth() === m - 1 &&
      fecha.getUTCDate() === d
    );
  }

  function fechas(inicio, fin) {
    inicio = String(inicio ?? "").trim();
    fin = String(fin ?? "").trim();
    if (!fechaValida(inicio) || !fechaValida(fin)) {
      return "Debes indicar una fecha de inicio y una de fin válidas.";
    }
    if (fin < inicio) return "La fecha de fin no puede ser anterior a la de inicio.";
    return null;
  }

  return { usuario, email, password, nombre, descripcion, participantes, fechas };
})();

/* Permite probar este archivo con Node (tests/validaciones.test.js) */
if (typeof module !== "undefined" && module.exports) {
  module.exports = Validaciones;
}
