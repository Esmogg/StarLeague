/* ELEMENTOS */

const gridEquipos = document.getElementById("gridEquipos");

const buscarEquipo = document.getElementById("buscarEquipo");

const sinEquipos = document.getElementById("sinEquipos");

const overlay = document.getElementById("equipoOverlay");

const modalEquipo = document.getElementById("modalEquipo");

const contenidoModal = document.getElementById("contenidoModalEquipo");

const cerrarModal = document.getElementById("cerrarModalEquipo");

/* OBTENER EQUIPOS */

function obtenerEquipos() {
  try {
    const equipos = JSON.parse(localStorage.getItem("equipos")) || [];

    return equipos;
  } catch (error) {
    console.error("No se pudieron cargar los equipos:", error);

    return [];
  }
}

/* OBTENER VALOR */

function obtenerValor(equipo, nombres, valorPorDefecto = "") {
  for (const nombre of nombres) {
    if (
      equipo[nombre] !== undefined &&
      equipo[nombre] !== null &&
      equipo[nombre] !== ""
    ) {
      return equipo[nombre];
    }
  }

  return valorPorDefecto;
}

/* NOMBRE */

function obtenerNombreEquipo(equipo) {
  return obtenerValor(equipo, ["nombre", "nombreEquipo"], "Equipo sin nombre");
}

/* TAG */

function obtenerTagEquipo(equipo) {
  return obtenerValor(equipo, ["tag"], "SIN TAG");
}

/* LOGO */

function obtenerLogoEquipo(equipo) {
  return obtenerValor(equipo, ["logo", "imagenLogo", "logoEquipo"], "");
}

/* DISCIPLINA */

function obtenerNombreDeporteEquipo(equipo) {
  const deporteId = obtenerValor(equipo, ["deporteId", "deporte"], "");

  if (typeof catalogos !== "undefined" && Array.isArray(catalogos.deportes)) {
    const deporte = catalogos.deportes.find((item) => item.id === deporteId);

    if (deporte) {
      return deporte.nombre;
    }
  }

  return deporteId || "No especificada";
}

/* MOSTRAR EQUIPOS */

function mostrarEquipos(lista = obtenerEquipos()) {
  gridEquipos.innerHTML = "";

  if (!lista.length) {
    sinEquipos.style.display = "block";

    return;
  }

  sinEquipos.style.display = "none";

  lista.forEach((equipo) => {
    const tarjeta = document.createElement("article");

    tarjeta.className = "tarjeta-equipo";

    /* LOGO */

    const contenedorLogo = document.createElement("div");

    contenedorLogo.className = "tarjeta-equipo-logo";

    const logo = obtenerLogoEquipo(equipo);

    if (logo) {
      const imagen = document.createElement("img");

      imagen.src = logo;

      imagen.alt = `Logo de ${obtenerNombreEquipo(equipo)}`;

      contenedorLogo.appendChild(imagen);
    } else {
      const icono = document.createElement("i");

      icono.className = "fa-solid fa-users";

      contenedorLogo.appendChild(icono);
    }

    /* INFORMACIÓN */

    const informacion = document.createElement("div");

    informacion.className = "tarjeta-equipo-info";

    const nombre = document.createElement("h2");

    nombre.className = "tarjeta-equipo-nombre";

    nombre.textContent = obtenerNombreEquipo(equipo);

    const tag = document.createElement("p");

    tag.className = "tarjeta-equipo-tag";

    tag.textContent = `[${obtenerTagEquipo(equipo)}]`;

    const disciplina = document.createElement("p");

    disciplina.className = "tarjeta-equipo-disciplina";

    disciplina.textContent = obtenerNombreDeporteEquipo(equipo);

    informacion.appendChild(nombre);

    informacion.appendChild(tag);

    informacion.appendChild(disciplina);

    /* ARMAR TARJETA */

    tarjeta.appendChild(contenedorLogo);

    tarjeta.appendChild(informacion);

    /* CLICK */

    tarjeta.addEventListener("click", () => abrirModal(equipo));

    gridEquipos.appendChild(tarjeta);
  });
}

/* BUSCADOR */

if (buscarEquipo) {
  buscarEquipo.addEventListener("input", () => {
    const texto = buscarEquipo.value.trim().toLowerCase();

    const equipos = obtenerEquipos();

    if (!texto) {
      mostrarEquipos(equipos);

      return;
    }

    const resultados = equipos.filter((equipo) => {
      const nombre = obtenerNombreEquipo(equipo).toLowerCase();

      const tag = obtenerTagEquipo(equipo).toLowerCase();

      return nombre.includes(texto) || tag.includes(texto);
    });

    mostrarEquipos(resultados);
  });
}

/* MODAL */

function abrirModal(equipo) {
  const nombre = obtenerNombreEquipo(equipo);

  const tag = obtenerTagEquipo(equipo);

  const logo = obtenerLogoEquipo(equipo);

  const deporte = obtenerNombreDeporteEquipo(equipo);

  const descripcion = obtenerValor(
    equipo,
    ["descripcion", "descripción"],
    "Sin descripción",
  );

  const pais = obtenerNombrePais(
    obtenerValor(equipo, ["pais", "region"], "No especificado"),
  );

  const integrantes = Array.isArray(equipo.integrantes)
    ? equipo.integrantes
    : [];

  /* Eliminar Equipo */
  function eliminarEquipo(equipo) {
    const nombre = obtenerNombreEquipo(equipo);

    const confirmar = confirm(
      `¿Estás seguro de que quieres eliminar el equipo "${nombre}"?\n\nEsta acción no se puede deshacer.`,
    );

    if (!confirmar) {
      return;
    }

    const equipos = obtenerEquipos();

    const equiposActualizados = equipos.filter((item) => {
      return item.id !== equipo.id;
    });

    localStorage.setItem("equipos", JSON.stringify(equiposActualizados));

    cerrarVentana();

    const textoBusqueda = buscarEquipo
      ? buscarEquipo.value.trim().toLowerCase()
      : "";

    if (!textoBusqueda) {
      mostrarEquipos(equiposActualizados);
      return;
    }

    const resultados = equiposActualizados.filter((item) => {
      const nombreEquipo = obtenerNombreEquipo(item).toLowerCase();
      const tag = obtenerTagEquipo(item).toLowerCase();

      return (
        nombreEquipo.includes(textoBusqueda) || tag.includes(textoBusqueda)
      );
    });

    mostrarEquipos(resultados);
  }

  /* LIMPIAR */

  contenidoModal.innerHTML = "";

  /* HEADER */

  const encabezado = document.createElement("div");

  encabezado.className = "modal-equipo-header";

  /* LOGO */

  if (logo) {
    const logoModal = document.createElement("img");

    logoModal.className = "modal-equipo-logo";

    logoModal.src = logo;

    logoModal.alt = `Logo de ${nombre}`;

    encabezado.appendChild(logoModal);
  } else {
    const logoModal = document.createElement("div");

    logoModal.className = "modal-equipo-logo";

    logoModal.style.display = "flex";
    logoModal.style.alignItems = "center";
    logoModal.style.justifyContent = "center";

    logoModal.innerHTML = '<i class="fa-solid fa-users"></i>';

    encabezado.appendChild(logoModal);
  }

  /* INFORMACIÓN DEL HEADER */

  function obtenerNombrePais(pais) {
    if (!pais) {
      return "No especificado";
    }

    return pais.charAt(0).toUpperCase() + pais.slice(1);
  }

  const headerInfo = document.createElement("div");

  headerInfo.className = "modal-equipo-header-info";

  const titulo = document.createElement("h2");

  titulo.textContent = nombre;

  const textoTag = document.createElement("p");

  textoTag.className = "modal-equipo-tag";

  textoTag.textContent = `[${tag}]`;

  headerInfo.appendChild(titulo);

  headerInfo.appendChild(textoTag);

  encabezado.appendChild(headerInfo);

  contenidoModal.appendChild(encabezado);

  /* INFORMACIÓN GENERAL */

  const seccionGeneral = document.createElement("div");

  seccionGeneral.className = "modal-equipo-seccion";

  const tituloGeneral = document.createElement("h3");

  tituloGeneral.textContent = "Información general";

  seccionGeneral.appendChild(tituloGeneral);

  agregarDatoModal(seccionGeneral, "Disciplina", deporte);

  agregarDatoModal(seccionGeneral, "País / región", pais);

  agregarDatoModal(
    seccionGeneral,
    "Integrantes",
    `${integrantes.length} jugador${integrantes.length === 1 ? "" : "es"}`,
  );

  contenidoModal.appendChild(seccionGeneral);

  /* DESCRIPCIÓN */

  const seccionDescripcion = document.createElement("div");

  seccionDescripcion.className = "modal-equipo-seccion";

  const tituloDescripcion = document.createElement("h3");

  tituloDescripcion.textContent = "Descripción";

  const textoDescripcion = document.createElement("p");

  textoDescripcion.textContent = descripcion;

  seccionDescripcion.appendChild(tituloDescripcion);

  seccionDescripcion.appendChild(textoDescripcion);

  contenidoModal.appendChild(seccionDescripcion);

  /* INTEGRANTES */

  const seccionIntegrantes = document.createElement("div");

  seccionIntegrantes.className = "modal-equipo-seccion";

  const tituloIntegrantes = document.createElement("h3");

  tituloIntegrantes.textContent = "Integrantes";

  seccionIntegrantes.appendChild(tituloIntegrantes);

  if (!integrantes.length) {
    const texto = document.createElement("p");

    texto.textContent = "Este equipo todavía no tiene integrantes registrados.";

    seccionIntegrantes.appendChild(texto);
  } else {
    const lista = document.createElement("div");

    lista.className = "lista-integrantes-modal";

    integrantes.forEach((integrante) => {
      const fila = document.createElement("div");

      fila.className = "integrante-modal";

      const nombreJugador = document.createElement("span");

      nombreJugador.className = "integrante-modal-nombre";

      nombreJugador.textContent = integrante.jugador || "Jugador sin nombre";

      const rol = document.createElement("span");

      rol.className = "integrante-modal-rol";

      rol.textContent = integrante.rol || "Jugador";

      fila.appendChild(nombreJugador);

      fila.appendChild(rol);

      lista.appendChild(fila);
    });

    seccionIntegrantes.appendChild(lista);
  }

  contenidoModal.appendChild(seccionIntegrantes);

  const botonEliminar = document.createElement("button");

  botonEliminar.className = "boton-eliminar-equipo";
  botonEliminar.innerHTML = '<i class="fa-solid fa-trash"></i><span>Eliminar</span>';

  botonEliminar.addEventListener("click", () => {
    eliminarEquipo(equipo);
  });

  contenidoModal.appendChild(botonEliminar);

  /* MOSTRAR */

  overlay.classList.add("activo");

  modalEquipo.classList.add("activo");

  document.body.style.overflow = "hidden";
}

/* AGREGAR DATO */

function agregarDatoModal(contenedor, etiqueta, valor) {
  const fila = document.createElement("div");

  fila.className = "modal-equipo-dato";

  const nombre = document.createElement("strong");

  nombre.textContent = etiqueta;

  const contenido = document.createElement("span");

  contenido.textContent = valor;

  fila.appendChild(nombre);

  fila.appendChild(contenido);

  contenedor.appendChild(fila);
}

/* CERRAR MODAL */

function cerrarVentana() {
  overlay.classList.remove("activo");

  modalEquipo.classList.remove("activo");

  document.body.style.overflow = "";
}

if (cerrarModal) {
  cerrarModal.addEventListener("click", cerrarVentana);
}

if (overlay) {
  overlay.addEventListener("click", cerrarVentana);
}

/* CARGAR EQUIPOS */

mostrarEquipos();
