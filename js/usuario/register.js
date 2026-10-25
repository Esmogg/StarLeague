/* ELEMENTOS DEL HTML */

const formulario = document.getElementById("formRegistro");
const mensaje = document.getElementById("mensaje");

/* INICIALIZACIÓN */

formulario.addEventListener("submit", enviarRegistro);

/* PROCESAR ENVÍO DEL FORMULARIO */

async function enviarRegistro(event) {
  event.preventDefault();

  try {
    /* Obtener datos del formulario como objeto (igual que en crear-equipos.js) */

    const datos = Object.fromEntries(new FormData(formulario));

    /* Validar antes de gastar un request (el servidor repite estas reglas) */

    const errorDatos =
      Validaciones.usuario(datos.new_user) ||
      Validaciones.email(datos.mail) ||
      Validaciones.password(datos.new_pass, datos.new_user);

    if (errorDatos) {
      throw errorDatos;
    }

    if (datos.new_pass !== datos.new_pass2) {
      throw "Las contraseñas no coinciden.";
    }

    /* Enviar al backend */

    const respuesta = await fetch("../../../php/auth/crear_usuario.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });

    const resultado = await respuesta.json();

    if (!resultado.ok) {
      throw resultado.mensaje;
    }

    /* Mostrar mensaje de éxito */

    mensaje.textContent = resultado.mensaje;

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("exito");
    mensaje.style.display = "block";

    formulario.reset();
  } catch (error) {
    /* Mostrar error */

    mensaje.textContent = typeof error === "string" ? error : "No se pudo completar el registro. Inténtalo más tarde.";

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("error");
    mensaje.style.display = "block";
  }
}
