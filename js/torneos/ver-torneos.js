/*ELEMENTOS*/

const gridTorneos = document.getElementById("gridTorneos");

const buscarTorneo = document.getElementById("buscarTorneo");

const sinTorneos = document.getElementById("sinTorneos");

const overlay = document.getElementById("torneoOverlay");

const modalTorneo = document.getElementById("modalTorneo");

const contenidoModal = document.getElementById("contenidoModalTorneo");

const cerrarModal = document.getElementById("cerrarModalTorneo");

/*OBTENER TORNEOS*/

function obtenerTorneos() {
  try {
    return JSON.parse(localStorage.getItem("torneos")) || [];
  } catch (error) {
    console.error("No se pudieron cargar los torneos:", error);

    return [];
  }
}

/*OBTENER UN VALOR DEL TORNEO*/

/*
   Como todavía estamos trabajando sin base de datos,
   esta función permite encontrar los datos aunque
   algunos campos tengan nombres diferentes.
*/

function obtenerValor(torneo, nombres, valorPorDefecto = "") {
  for (const nombre of nombres) {
    if (
      torneo[nombre] !== undefined &&
      torneo[nombre] !== null &&
      torneo[nombre] !== ""
    ) {
      return torneo[nombre];
    }
  }

  return valorPorDefecto;
}

/* =====================================================
   CONVERTIR IDS DE CATÁLOGOS A NOMBRES
===================================================== */

function obtenerNombreDeporte(id) {
  if (!id) {
    return "No especificado";
  }

  if (typeof catalogos !== "undefined" && Array.isArray(catalogos.deportes)) {
    const deporte = catalogos.deportes.find((item) => item.id === id);

    if (deporte) {
      return deporte.nombre;
    }
  }

  return id;
}

function obtenerNombreFormato(id) {
  if (!id) {
    return "No especificado";
  }

  if (typeof catalogos !== "undefined" && Array.isArray(catalogos.formatos)) {
    const formato = catalogos.formatos.find((item) => item.id === id);

    if (formato) {
      return formato.nombre;
    }
  }

  return id;
}

function obtenerNombreCategoria(id) {
  if (!id) {
    return "No especificada";
  }

  /*
     Si existe un catálogo de categorías,
     usamos su nombre.
  */

  if (typeof catalogos !== "undefined" && Array.isArray(catalogos.categorias)) {
    const categoria = catalogos.categorias.find((item) => item.id === id);

    if (categoria) {
      return categoria.nombre;
    }
  }

  /*
     Mientras no exista un catálogo de categorías,
     hacemos una presentación más amigable.
  */

  return id
    .replace(/[-_]/g, " ")
    .replace(/\b\w/g, (letra) => letra.toUpperCase());
}

/*OBTENER NOMBRE*/

function obtenerNombreTorneo(torneo) {
  return obtenerValor(
    torneo,
    ["nombre", "nombreTorneo", "titulo", "tituloTorneo"],
    "Torneo sin nombre",
  );
}

/*OBTENER BANNER*/

function obtenerBannerTorneo(torneo) {
  return obtenerValor(torneo, ["banner", "imagenBanner", "bannerTorneo"], "");
}

/*OBTENER LOGO*/

function obtenerLogoTorneo(torneo) {
  return obtenerValor(torneo, ["logo", "imagenLogo", "logoTorneo"], "");
}

/*MOSTRAR TORNEOS*/

function mostrarTorneos(lista = obtenerTorneos()) {
  gridTorneos.innerHTML = "";

  if (!lista.length) {
    sinTorneos.style.display = "block";

    return;
  }

  sinTorneos.style.display = "none";

  lista.forEach((torneo) => {
    const tarjeta = document.createElement("article");

    tarjeta.className = "tarjeta-torneo";

    /*BANNER*/

    const banner = obtenerBannerTorneo(torneo);

    if (banner) {
      tarjeta.style.backgroundImage = `url("${banner}")`;
    }

    /*OVERLAY*/

    const capa = document.createElement("div");
    capa.className = "tarjeta-overlay";

    /*CONTENIDO*/

    const contenido = document.createElement("div");

    contenido.className = "tarjeta-contenido";

    /*LOGO*/

    const contenedorLogo = document.createElement("div");

    contenedorLogo.className = "torneo-logo";

    const logo = obtenerLogoTorneo(torneo);

    if (logo) {
      const imagenLogo = document.createElement("img");

      imagenLogo.src = logo;

      imagenLogo.alt = `Logo de ${obtenerNombreTorneo(torneo)}`;

      contenedorLogo.appendChild(imagenLogo);
    } else {
      const icono = document.createElement("i");

      icono.className = "fa-solid fa-trophy";

      contenedorLogo.appendChild(icono);
    }

    /*INFORMACIÓN*/

    const informacion = document.createElement("div");

    informacion.className = "torneo-info";

    const nombre = document.createElement("h2");

    nombre.className = "torneo-nombre";

    nombre.textContent = obtenerNombreTorneo(torneo);

    const deporteId = obtenerValor(torneo, ["deporte", "disciplina"], "");

    const categoriaId = obtenerValor(torneo, ["categoria", "categoría"], "");

    const deporte = obtenerNombreDeporte(deporteId);

    const categoria = obtenerNombreCategoria(categoriaId);

    const subtitulo = document.createElement("p");

    subtitulo.className = "torneo-subtitulo";

    if (deporte && categoria) {
      subtitulo.textContent = `${deporte} · ${categoria}`;
    } else if (deporte) {
      subtitulo.textContent = deporte;
    } else if (categoria) {
      subtitulo.textContent = categoria;
    }

    informacion.appendChild(nombre);

    if (subtitulo.textContent) {
      informacion.appendChild(subtitulo);
    }

    /*ARMAR TARJETA*/

    contenido.appendChild(contenedorLogo);

    contenido.appendChild(informacion);

    capa.appendChild(contenido);

    tarjeta.appendChild(capa);

    /*CLICK*/

    tarjeta.addEventListener("click", () => abrirModal(torneo));

    gridTorneos.appendChild(tarjeta);
  });
}

/*BUSCADOR*/

if (buscarTorneo) {
  buscarTorneo.addEventListener("input", () => {
    const texto = buscarTorneo.value.trim().toLowerCase();

    const torneos = obtenerTorneos();

    if (!texto) {
      mostrarTorneos(torneos);

      return;
    }

    const resultados = torneos.filter((torneo) => {
      const nombre = obtenerNombreTorneo(torneo).toLowerCase();

      return nombre.includes(texto);
    });

    mostrarTorneos(resultados);
  });
}

/*MODAL*/

function abrirModal(torneo) {
  const nombre = obtenerNombreTorneo(torneo);

  const logo = obtenerLogoTorneo(torneo);

  const banner = obtenerBannerTorneo(torneo);

  const categoriaId = obtenerValor(torneo, ["categoria", "categoría"], "");

  const deporteId = obtenerValor(torneo, ["deporte", "disciplina"], "");

  const formatoId = obtenerValor(torneo, ["formato"], "");

  const categoria = obtenerNombreCategoria(categoriaId);

  const deporte = obtenerNombreDeporte(deporteId);

  const formato = obtenerNombreFormato(formatoId);

  const descripcion = obtenerValor(
    torneo,
    ["descripcion", "descripción"],
    "Sin descripción",
  );

  const tipoParticipante = obtenerValor(torneo, ["tipoParticipante"], "");

  let cantidad = "No especificada";

  if (torneo.cantidadEquipos !== undefined) {
    cantidad = `${torneo.cantidadEquipos} equipos`;
  } else if (torneo.cantidadJugadores !== undefined) {
    cantidad = `${torneo.cantidadJugadores} jugadores`;
  }

  /*LIMPIAR MODAL*/

  contenidoModal.innerHTML = "";

  /*BANNER*/

  if (banner) {
    const bannerModal = document.createElement("div");

    bannerModal.className = "modal-banner";

    bannerModal.style.backgroundImage = `url("${banner}")`;

    contenidoModal.appendChild(bannerModal);
  }

  /*ENCABEZADO*/

  const encabezado = document.createElement("div");

  encabezado.className = "modal-header";

  /*LOGO*/

  if (logo) {
    const logoModal = document.createElement("img");

    logoModal.className = "modal-logo";

    logoModal.src = logo;

    logoModal.alt = `Logo de ${nombre}`;

    encabezado.appendChild(logoModal);
  }

  const titulo = document.createElement("h2");

  titulo.textContent = nombre;

  encabezado.appendChild(titulo);

  contenidoModal.appendChild(encabezado);

  /*INFORMACIÓN GENERAL*/

  const seccionGeneral = document.createElement("div");

  seccionGeneral.className = "modal-seccion";

  const tituloGeneral = document.createElement("h3");

  tituloGeneral.textContent = "Información general";

  seccionGeneral.appendChild(tituloGeneral);

  agregarDatoModal(seccionGeneral, "Categoría", categoria);

  agregarDatoModal(seccionGeneral, "Disciplina", deporte);

  agregarDatoModal(seccionGeneral, "Formato", formato);

  agregarDatoModal(seccionGeneral, "Participantes", cantidad);

  if (tipoParticipante) {
    agregarDatoModal(seccionGeneral, "Tipo de participante", tipoParticipante);
  }

  contenidoModal.appendChild(seccionGeneral);

  /*DESCRIPCION*/

  const seccionDescripcion = document.createElement("div");

  seccionDescripcion.className = "modal-seccion";

  const tituloDescripcion = document.createElement("h3");

  tituloDescripcion.textContent = "Descripción";

  const textoDescripcion = document.createElement("p");

  textoDescripcion.textContent = descripcion;

  seccionDescripcion.appendChild(tituloDescripcion);

  seccionDescripcion.appendChild(textoDescripcion);

  contenidoModal.appendChild(seccionDescripcion);

  /*MOSTRAR*/

  overlay.classList.add("activo");

  modalTorneo.classList.add("activo");

  document.body.style.overflow = "hidden";
}

/*AGREGAR DATO AL MODAL*/

function agregarDatoModal(contenedor, etiqueta, valor) {
  const fila = document.createElement("div");

  fila.className = "modal-dato";

  const nombre = document.createElement("strong");

  nombre.textContent = etiqueta;

  const contenido = document.createElement("span");

  contenido.textContent = valor;

  fila.appendChild(nombre);

  fila.appendChild(contenido);

  contenedor.appendChild(fila);
}

/*CERRAR MODAL*/

function cerrarVentana() {
  overlay.classList.remove("activo");

  modalTorneo.classList.remove("activo");

  document.body.style.overflow = "";
}

if (cerrarModal) {
  cerrarModal.addEventListener("click", cerrarVentana);
}

if (overlay) {
  overlay.addEventListener("click", cerrarVentana);
}

/*CARGAR TORNEOS*/
mostrarTorneos();
