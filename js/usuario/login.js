/* ELEMENTOS DEL HTML */

const formulario = document.getElementById("formLogin");
const mensaje = document.getElementById("mensaje");

/* INICIALIZACIÓN */

formulario.addEventListener("submit", enviarLogin);

/* MOSTRAR MENSAJE (misma animación que en register.js) */

function mostrarMensaje(texto, tipo) {
  mensaje.textContent = texto;

  mensaje.classList.remove("error", "exito");
  mensaje.style.display = "none";

  void mensaje.offsetWidth;

  mensaje.classList.add(tipo);
  mensaje.style.display = "block";
}

/* PROCESAR ENVÍO DEL FORMULARIO */

async function enviarLogin(event) {
  event.preventDefault();

  try {
    const datos = Object.fromEntries(new FormData(formulario));

    /* Solo se exige lo mínimo; las reglas de formato son del registro, no del login */

    if (!datos.user.trim()) {
      throw "Ingresa tu nombre de usuario.";
    }

    if (!datos.mail.trim()) {
      throw "Ingresa tu correo electrónico: es obligatorio para iniciar sesión.";
    }

    if (datos.pass === "") {
      throw "Ingresa tu contraseña.";
    }

    const respuesta = await fetch("../../../php/auth/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });

    const resultado = await respuesta.json();

    if (!resultado.ok) {
      throw resultado.mensaje;
    }

    /* Mostrar confirmación en la propia página y luego entrar al panel. */
    mostrarMensaje(resultado.mensaje || "Sesión iniciada correctamente.", "exito");
    formulario.querySelector('input[type="submit"]').disabled = true;
    window.setTimeout(() => {
      window.location.href = "../../login/";
    }, 1200);
  } catch (error) {
    mostrarMensaje(
      typeof error === "string" ? error : "No se pudo iniciar sesión. Inténtalo más tarde.",
      "error",
    );
  }
}
