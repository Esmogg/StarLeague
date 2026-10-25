document.addEventListener("DOMContentLoaded", () => {
  const nombreUsuario = document.getElementById("nombreUsuario");
  const fechaCreacion = document.getElementById("fechaCreacion");
  const contenidoInscripciones = document.getElementById(
    "contenidoInscripciones",
  );
  const fotoPerfil = document.getElementById("logoUsuario");
  const bannerPerfil = document.getElementById("bannerUsuario");
  const inputFotoPerfil = document.getElementById("inputFotoPerfil");
  const inputBanner = document.getElementById("inputBanner");
  const mensajePerfil = document.getElementById("mensajePerfil");
  let temporizadorMensaje = null;

  function mostrarMensajePerfil(texto, tipo = "error") {
    if (!mensajePerfil) {
      console.error(texto);
      return;
    }
    if (temporizadorMensaje) {
      clearTimeout(temporizadorMensaje);
      temporizadorMensaje = null;
    }
    if (!texto) {
      mensajePerfil.textContent = "";
      mensajePerfil.hidden = true;
      mensajePerfil.classList.remove("mensaje-perfil-error", "mensaje-perfil-exito");
      return;
    }
    mensajePerfil.textContent = texto;
    mensajePerfil.classList.toggle("mensaje-perfil-error", tipo !== "exito");
    mensajePerfil.classList.toggle("mensaje-perfil-exito", tipo === "exito");
    mensajePerfil.hidden = false;
    temporizadorMensaje = window.setTimeout(() => {
      mensajePerfil.textContent = "";
      mensajePerfil.hidden = true;
      mensajePerfil.classList.remove("mensaje-perfil-error", "mensaje-perfil-exito");
      temporizadorMensaje = null;
    }, tipo === "exito" ? 5000 : 7000);
  }

  const ENDPOINT = "../../../php/auth/perfil.php";
  const ENDPOINT_SUBIR_IMAGEN = "../../../php/auth/subir_imagen_perfil.php";
  const MAX_BYTES = 10 * 1024 * 1024;
  const TIPOS_PERMITIDOS = ["image/png", "image/jpeg"];

  function escaparTexto(texto) {
    return String(texto ?? "");
  }

  // Convierte la ruta guardada en MySQL en una URL absoluta dentro del proyecto.
  // Así no depende de la ruta relativa desde la que se abrió el HTML.
  function resolverRutaImagen(ruta) {
    const rutaLimpia = String(ruta ?? "")
      .trim()
      .replace(/^(?:\.\.\/)+/, "")
      .replace(/^\/+/, "");
    const rutaProyecto = window.location.pathname.replace(
      /\/html\/login\/usuario\/[^/]*$/,
      "",
    );
    return `${rutaProyecto}/${rutaLimpia}`.replace(/\/{2,}/g, "/");
  }

  function aplicarImagenGuardada(imagen, ruta, textoAlternativo) {
    const imagenPorDefecto = imagen.getAttribute("src");
    if (!ruta) {
      imagen.onerror = null;
      imagen.src = imagenPorDefecto;
      imagen.alt = textoAlternativo;
      return;
    }

    imagen.onerror = () => {
      console.error("No se pudo cargar la imagen guardada:", imagen.src);
      imagen.onerror = null;
      imagen.src = imagenPorDefecto;
      mostrarMensajePerfil(
        "La imagen está registrada, pero no se encontró el archivo en el servidor. Verificá la carpeta uploads/perfiles.",
        "error",
      );
    };
    imagen.src = resolverRutaImagen(ruta);
    imagen.alt = textoAlternativo;
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

  let perfilCargando = false;

  async function cargarPerfil() {
    if (perfilCargando) return;
    perfilCargando = true;
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

      aplicarImagenGuardada(
        fotoPerfil,
        datos.usuario.foto_perfil,
        "Foto de perfil del usuario",
      );
      aplicarImagenGuardada(
        bannerPerfil,
        datos.usuario.banner_perfil,
        "Banner del usuario",
      );

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
    } finally {
      perfilCargando = false;
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

  // No se consulta la API continuamente: evita peticiones simultáneas y que
  // una respuesta antigua sobrescriba el estado visual recién actualizado.

  // Abrir los selectores desde el clic del lápiz o desde cualquier parte del área.
  // showPicker() funciona en navegadores compatibles; click() queda como alternativa.
  const editorFotoPerfil = document.getElementById("editorFotoPerfil");
  const editorBanner = document.getElementById("editorBanner");
  const botonEditarFoto = editorFotoPerfil?.querySelector(".boton-editar-imagen");
  const botonEditarBanner = editorBanner?.querySelector(".boton-editar-imagen");

  function abrirSelectorImagen(evento, input, boton) {
    evento.preventDefault();
    evento.stopPropagation();
    if (!input) {
      console.error("No se encontró el input de selección de imagen.");
      return;
    }
    try {
      if (typeof input.showPicker === "function") {
        input.showPicker();
      } else {
        input.click();
      }
    } catch (error) {
      // Algunos navegadores bloquean showPicker; intentar con click().
      try {
        input.click();
      } catch (errorClick) {
        console.error("No se pudo abrir el selector de imágenes:", errorClick);
        mostrarMensajePerfil("No se pudo abrir el selector de imágenes. Recargá la página e intentá nuevamente.", "error");
      }
    }
    // Evita que :focus-within deje el overlay pegado al cerrar el selector.
    if (boton) boton.blur();
  }

  if (editorFotoPerfil && botonEditarFoto) {
    editorFotoPerfil.addEventListener("click", (evento) => {
      if (evento.target.closest(".boton-editar-imagen, input")) return;
      abrirSelectorImagen(evento, inputFotoPerfil, botonEditarFoto);
    });
    botonEditarFoto.addEventListener("click", (evento) =>
      abrirSelectorImagen(evento, inputFotoPerfil, botonEditarFoto)
    );
  } else {
    console.error("No se encontró el área para cambiar la foto de perfil.");
  }

  if (editorBanner && botonEditarBanner) {
    editorBanner.addEventListener("click", (evento) => {
      if (evento.target.closest(".boton-editar-imagen, input")) return;
      abrirSelectorImagen(evento, inputBanner, botonEditarBanner);
    });
    botonEditarBanner.addEventListener("click", (evento) =>
      abrirSelectorImagen(evento, inputBanner, botonEditarBanner)
    );
  } else {
    console.error("No se encontró el área para cambiar el banner.");
  }

  async function procesarImagen(archivo, tipo, input) {
    if (!archivo) return;
    if (!TIPOS_PERMITIDOS.includes(archivo.type)) {
      mostrarMensajePerfil("Solo se permiten imágenes PNG, JPG o JPEG.", "error");
      input.value = "";
      return;
    }
    if (archivo.size > MAX_BYTES) {
      mostrarMensajePerfil("La imagen debe pesar como máximo 10 MB.", "error");
      input.value = "";
      return;
    }

    try {
      const dimensiones = await leerDimensionesImagen(archivo);
      const minimo = tipo === "foto_perfil" ? [200, 200] : [1110, 170];
      if (dimensiones.ancho < minimo[0] || dimensiones.alto < minimo[1]) {
        mostrarMensajePerfil(tipo === "foto_perfil"
          ? "La foto de perfil debe medir al menos 200 × 200 píxeles."
          : "El banner debe medir al menos 1110 × 170 píxeles.", "error");
        input.value = "";
        return;
      }

      const formulario = new FormData();
      formulario.append("tipo", tipo);
      formulario.append("imagen", archivo);
      mostrarMensajePerfil("Subiendo imagen…", "exito");
      const respuesta = await fetch(ENDPOINT_SUBIR_IMAGEN, {
        method: "POST",
        body: formulario,
        credentials: "same-origin",
      });
      let datos;
      try {
        datos = await respuesta.json();
      } catch {
        throw new Error("El servidor devolvió una respuesta inesperada. Revisá los registros de PHP.");
      }
      if (!respuesta.ok || !datos.ok) {
        throw new Error(datos.mensaje || "No se pudo actualizar la imagen.");
      }

      const imagen = tipo === "foto_perfil" ? fotoPerfil : bannerPerfil;
      imagen.onerror = null;
      imagen.src = `${resolverRutaImagen(datos.ruta)}?t=${Date.now()}`;
      imagen.alt = tipo === "foto_perfil" ? "Foto de perfil del usuario" : "Banner del usuario";
      mostrarMensajePerfil(datos.mensaje || "Perfil actualizado correctamente.", "exito");
    } catch (error) {
      console.error("Error al subir imagen del perfil:", error);
      mostrarMensajePerfil(error.message || "No se pudo subir la imagen. Intentá nuevamente.", "error");
    } finally {
      input.value = "";
    }
  }

  function leerDimensionesImagen(archivo) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(archivo);
      const imagen = new Image();
      imagen.onload = () => {
        const dimensiones = { ancho: imagen.naturalWidth, alto: imagen.naturalHeight };
        URL.revokeObjectURL(url);
        resolve(dimensiones);
      };
      imagen.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error("El archivo seleccionado no es una imagen válida."));
      };
      imagen.src = url;
    });
  }

  inputFotoPerfil.addEventListener("change", () => {
    procesarImagen(inputFotoPerfil.files[0], "foto_perfil", inputFotoPerfil);
  });
  inputBanner.addEventListener("change", () => {
    procesarImagen(inputBanner.files[0], "banner_perfil", inputBanner);
  });
});
