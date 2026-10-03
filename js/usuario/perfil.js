document.addEventListener("DOMContentLoaded", () => {
  const nombreUsuario = document.getElementById("nombreUsuario");
  const fechaCreacion = document.getElementById("fechaCreacion");
  const contenidoInscripciones = document.getElementById(
    "contenidoInscripciones",
  );

  const ENDPOINT = "../../../php/auth/perfil.php";

  function escaparTexto(texto) {
    return String(texto ?? "");
  }

  function crearTarjetaInscripcion(torneo) {
    const tarjeta = document.createElement("article");
    tarjeta.className = "tarjeta-inscripcion";
    tarjeta.title = torneo.nombre;

    const logo = document.createElement("div");
    logo.className = "logo-inscripcion";

    const icono = document.createElement("i");
    icono.className = "fa-solid fa-trophy";
    logo.appendChild(icono);

    const informacion = document.createElement("div");
    informacion.className = "info-inscripcion";

    const titulo = document.createElement("h3");
    titulo.textContent = escaparTexto(torneo.nombre);

    const detalles = document.createElement("p");

    const disciplina = torneo.disciplina || "Disciplina no especificada";
    const categoria = torneo.categoria || "";

    detalles.textContent = categoria
      ? `${disciplina} · ${categoria}`
      : disciplina;

    informacion.appendChild(titulo);
    informacion.appendChild(detalles);

    tarjeta.appendChild(logo);
    tarjeta.appendChild(informacion);

    return tarjeta;
  }

  function mostrarInscripciones(torneos) {
    contenidoInscripciones.innerHTML = "";

    if (!Array.isArray(torneos) || torneos.length === 0) {
      const estado = document.createElement("p");
      estado.className = "estado-inscripciones";
      estado.textContent = "No estás inscrito en ningún torneo.";
      contenidoInscripciones.appendChild(estado);
      return;
    }

    torneos.forEach((torneo) => {
      contenidoInscripciones.appendChild(crearTarjetaInscripcion(torneo));
    });
  }

  async function cargarPerfil() {
    try {
      const respuesta = await fetch(ENDPOINT, {
        method: "GET",
        cache: "no-store",
        credentials: "same-origin",
      });

      const datos = await respuesta.json();

      if (!respuesta.ok || !datos.ok) {
        throw new Error(datos.mensaje || "No se pudo cargar el perfil.");
      }

      nombreUsuario.textContent = datos.usuario.nombre;
      fechaCreacion.textContent =
        `Fecha de Creacion: ${datos.usuario.fecha_creacion}`;

      mostrarInscripciones(datos.inscripciones);
    } catch (error) {
      console.error("Error al cargar el perfil:", error);

      nombreUsuario.textContent = "Usuario";
      fechaCreacion.textContent = "Fecha de Creacion: No disponible";

      contenidoInscripciones.innerHTML = "";

      const estado = document.createElement("p");
      estado.className = "estado-inscripciones";
      estado.textContent =
        "No se pudieron cargar las inscripciones del usuario.";
      contenidoInscripciones.appendChild(estado);
    }
  }

  cargarPerfil();

  // Actualiza el perfil al volver a la pestaña o ventana.
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
      cargarPerfil();
    }
  });

  window.addEventListener("focus", cargarPerfil);

  // También detecta cambios hechos desde otra sesión/pestaña.
  setInterval(cargarPerfil, 5000);
});
