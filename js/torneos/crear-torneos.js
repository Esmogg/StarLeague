const formulario = document.getElementById("formCrearTorneo");

const selectCategoria = document.getElementById("categoria");
const selectDeporte = document.getElementById("deporte");
const selectFormato = document.getElementById("formato");

const labelCantidad = document.getElementById("labelCantidad");
const inputCantidad = document.getElementById("cantidadParticipantes");

const mensaje = document.getElementById("mensaje");

const configuracionDeporte = document.getElementById("configuracionDeporte");

const inputLogo = document.getElementById("logo");
const inputBanner = document.getElementById("banner");

const previewLogo = document.getElementById("previewLogo");
const previewBanner = document.getElementById("previewBanner");

const previewNombre = document.getElementById("previewNombre");

/*INICIALIZACIÓN*/

// Detectar cuando cambia la disciplina
selectDeporte.addEventListener("change", actualizarTipoParticipante);

// Cargar los formatos disponibles
cargarFormatos();

// Detectar cambios de categoría
selectCategoria.addEventListener("change", actualizarDeportes);

// Detectar envío del formulario
formulario.addEventListener("submit", enviarFormulario);

// Detectar cambio de deporte
selectDeporte.addEventListener("change", actualizarConfiguracionDeporte);

/* FUNCIONES*/

/* SELECCIÓN DE EQUIPOS */

const overlayInfoEquipo = document.getElementById("overlayInfoEquipo");

const modalInfoEquipo = document.getElementById("modalInfoEquipo");

const contenidoInfoEquipo = document.getElementById("contenidoInfoEquipo");

const cerrarInfoEquipo = document.getElementById("cerrarInfoEquipo");

cerrarInfoEquipo.addEventListener("click", cerrarModalInfoEquipo);

overlayInfoEquipo.addEventListener("click", (event) => {
  if (event.target === overlayInfoEquipo) {
    cerrarModalInfoEquipo();
  }
});

const botonAgregarEquipo = document.querySelector(".btnAgregarEquipo");

const overlaySeleccionEquipos = document.getElementById(
  "overlaySeleccionEquipos",
);

const modalSeleccionEquipos = document.getElementById("modalSeleccionEquipos");

const listaSeleccionEquipos = document.getElementById("listaSeleccionEquipos");

const cerrarSeleccionEquipos = document.getElementById(
  "cerrarSeleccionEquipos",
);

const cancelarSeleccionEquipos = document.getElementById(
  "cancelarSeleccionEquipos",
);

const confirmarSeleccionEquipos = document.getElementById(
  "confirmarSeleccionEquipos",
);

const listaEquipos = document.getElementById("listaEquipos");

/*
 * Equipos que actualmente están agregados al torneo.
 */
let equiposSeleccionados = [];

botonAgregarEquipo.addEventListener("click", abrirSeleccionEquipos);

cerrarSeleccionEquipos.addEventListener("click", cerrarSeleccionEquiposModal);

cancelarSeleccionEquipos.addEventListener("click", cerrarSeleccionEquiposModal);

confirmarSeleccionEquipos.addEventListener("click", confirmarEquipos);

overlaySeleccionEquipos.addEventListener("click", (event) => {
  if (event.target === overlaySeleccionEquipos) {
    cerrarSeleccionEquiposModal();
  }
});

/* SELECCIÓN DE EQUIPOS */

/* Abrir ventana de selección */

function abrirSeleccionEquipos() {
  cargarEquiposDisponibles();

  overlaySeleccionEquipos.classList.add("activo");
  modalSeleccionEquipos.classList.add("activo");

  document.body.classList.add("modal-abierto");
  document.documentElement.classList.add("modal-abierto");
}

/* Cerrar ventana de selección */

function cerrarSeleccionEquiposModal() {
  overlaySeleccionEquipos.classList.remove("activo");
  modalSeleccionEquipos.classList.remove("activo");

  document.body.classList.remove("modal-abierto");
  document.documentElement.classList.remove("modal-abierto");
}

/* Cargar equipos creados */

function cargarEquiposDisponibles() {
  listaSeleccionEquipos.innerHTML = "";

  const equipos = JSON.parse(localStorage.getItem("equipos")) || [];

  if (equipos.length === 0) {
    listaSeleccionEquipos.innerHTML = `
      <div class="estado-vacio-equipos">
        <i class="fa-solid fa-users"></i>
        <p>No hay equipos creados.</p>
      </div>
    `;
    return;
  }

  equipos.forEach((equipo) => {
    const yaAgregado = equiposSeleccionados.some(
      (equipoSeleccionado) => equipoSeleccionado.id === equipo.id,
    );

    const opcion = document.createElement("label");

    opcion.className = "opcion-equipo";

    if (yaAgregado) {
      opcion.classList.add("no-disponible");
    }

    const logo = equipo.logo
      ? `
        <img
          src="${equipo.logo}"
          alt="Logo de ${equipo.nombre}"
        >
      `
      : `
        <i class="fa-solid fa-users"></i>
      `;

    opcion.innerHTML = `
      <input
        type="checkbox"
        value="${equipo.id}"
        ${yaAgregado ? "checked disabled" : ""}
      >

      <div class="opcion-equipo-logo">
        ${logo}
      </div>

      <div class="opcion-equipo-info">
        <p class="opcion-equipo-nombre">
          ${equipo.nombre}
        </p>

        <p class="opcion-equipo-tag">
          ${equipo.tag || ""}
        </p>
      </div>
    `;

    const checkbox = opcion.querySelector('input[type="checkbox"]');

    if (!yaAgregado) {
      checkbox.addEventListener("change", () => {
        opcion.classList.toggle("seleccionado", checkbox.checked);
      });
    }

    listaSeleccionEquipos.appendChild(opcion);
  });
}

/* Confirmar selección */

function confirmarEquipos() {
  const equipos = JSON.parse(localStorage.getItem("equipos")) || [];

  const checkboxes = listaSeleccionEquipos.querySelectorAll(
    'input[type="checkbox"]:checked:not(:disabled)',
  );

  checkboxes.forEach((checkbox) => {
    const equipo = equipos.find((equipo) => equipo.id === checkbox.value);

    if (!equipo) {
      return;
    }

    const yaExiste = equiposSeleccionados.some(
      (equipoSeleccionado) => equipoSeleccionado.id === equipo.id,
    );

    if (!yaExiste) {
      equiposSeleccionados.push(equipo);
    }
  });

  actualizarListaEquipos();

  cerrarSeleccionEquiposModal();
}

/* Mostrar equipos agregados */

function actualizarListaEquipos() {
  listaEquipos.innerHTML = "";

  const hayEquipos = equiposSeleccionados.length > 0;

  listaEquipos.classList.toggle("grid-equipos-agregados", hayEquipos);

  if (!hayEquipos) {
    listaEquipos.innerHTML = `
      <div class="estado-vacio-equipos">
        <i class="fa-solid fa-users"></i>
        <p>No hay equipos agregados.</p>
      </div>
    `;
    return;
  }

  /*
   * Convertir la lista en un grid.
   */
  listaEquipos.classList.add("grid-equipos-agregados");

  equiposSeleccionados.forEach((equipo) => {
    const tarjeta = document.createElement("div");

    tarjeta.className = "tarjeta-equipo-agregado";

    tarjeta.dataset.id = equipo.id;

    const logo = equipo.logo
      ? `
        <img
          src="${equipo.logo}"
          alt="Logo de ${equipo.nombre}"
        >
      `
      : `
        <i class="fa-solid fa-users"></i>
      `;

    tarjeta.innerHTML = `
      <div class="tarjeta-equipo-agregado-logo">
        ${logo}
      </div>

      <div class="tarjeta-equipo-agregado-info">
        <p class="tarjeta-equipo-agregado-nombre">
          ${equipo.nombre}
        </p>

        <p class="tarjeta-equipo-agregado-tag">
          ${equipo.tag ? equipo.tag : ""}
        </p>

        <p class="tarjeta-equipo-agregado-disciplina">
          ${equipo.deporte ? equipo.deporte : ""}
        </p>
      </div>

      <button
        type="button"
        class="btnEliminarEquipo"
        data-id="${equipo.id}"
        title="Quitar equipo del torneo"
      >
        <i class="fa-solid fa-xmark"></i>
      </button>
    `;

    /*
     * Click sobre la tarjeta:
     * abrir información del equipo.
     */
    tarjeta.addEventListener("click", (event) => {
      /*
       * Si se hizo click en el botón X,
       * no abrimos el modal.
       */
      if (event.target.closest(".btnEliminarEquipo")) {
        return;
      }

      abrirModalEquipo(equipo);
    });

    /*
     * Botón para quitar el equipo.
     */
    const botonEliminar = tarjeta.querySelector(".btnEliminarEquipo");

    botonEliminar.addEventListener("click", (event) => {
      event.stopPropagation();

      eliminarEquipo(equipo.id);
    });

    listaEquipos.appendChild(tarjeta);
  });
}

/* Eliminar un equipo de la selección */

function eliminarEquipo(id) {
  equiposSeleccionados = equiposSeleccionados.filter(
    (equipo) => equipo.id !== id,
  );

  actualizarListaEquipos();
}

/* Actualiza el tipo de participante según el deporte */

function actualizarTipoParticipante() {
  const deporteSeleccionado = catalogos.deportes.find((deporte) => {
    return deporte.id === selectDeporte.value;
  });

  if (!deporteSeleccionado) {
    labelCantidad.textContent = "Cantidad";

    inputCantidad.placeholder = "";

    return;
  }

  if (deporteSeleccionado.tipoParticipante === "equipos") {
    labelCantidad.textContent = "Cantidad de equipos";
  } else {
    labelCantidad.textContent = "Cantidad de jugadores";
  }
}

/* Carga los formatos del torneo */

function cargarFormatos() {
  selectFormato.innerHTML = '<option value="">Seleccione un formato:</option>';

  catalogos.formatos.forEach((formato) => {
    const option = document.createElement("option");

    option.value = formato.id;

    option.textContent = formato.nombre;

    selectFormato.appendChild(option);
  });
}

/*Actualiza las disciplinas según la categoría*/

function actualizarDeportes() {
  selectDeporte.innerHTML =
    '<option value="">Seleccione una disciplina:</option>';

  const categoriaSeleccionada = selectCategoria.value;

  const deportes = catalogos.deportes.filter((deporte) => {
    return deporte.categoria === categoriaSeleccionada;
  });

  deportes.forEach((deporte) => {
    const option = document.createElement("option");

    option.value = deporte.id;

    option.textContent = deporte.nombre;

    selectDeporte.appendChild(option);
  });
}

/*Actualiza la configuración específica del deporte*/

function actualizarConfiguracionDeporte() {
  const deporteSeleccionado = selectDeporte.value;

  if (!deporteSeleccionado) {
    configuracionDeporte.innerHTML = "<p>Seleccione una disciplina.</p>";

    return;
  }

  const configuracion = catalogos.configuracionesDeporte[deporteSeleccionado];

  if (!configuracion) {
    configuracionDeporte.innerHTML =
      "<p>No hay configuración disponible para esta disciplina.</p>";

    return;
  }

  configuracionDeporte.innerHTML = configuracion;
}

function abrirModalEquipo(equipo) {
  const logo = equipo.logo
    ? `
      <img
        src="${equipo.logo}"
        alt="Logo de ${equipo.nombre}"
        class="info-equipo-logo"
      >
    `
    : `
      <div class="info-equipo-logo sin-logo">
        <i class="fa-solid fa-users"></i>
      </div>
    `;

  const integrantes = equipo.integrantes || [];

  let listaIntegrantes = "";

  if (integrantes.length === 0) {
    listaIntegrantes = `
      <p class="sin-integrantes">
        Este equipo no tiene integrantes registrados.
      </p>
    `;
  } else {
    listaIntegrantes = `
      <div class="lista-integrantes-info">
        ${integrantes
          .map(
            (integrante) => `
              <div class="integrante-info">
                <span class="integrante-info-nombre">
                  ${integrante.jugador || "Jugador"}
                </span>

                <span class="integrante-info-rol">
                  ${integrante.rol || ""}
                </span>
              </div>
            `,
          )
          .join("")}
      </div>
    `;
  }

  contenidoInfoEquipo.innerHTML = `
    <div class="info-equipo-header">

      ${logo}

      <div class="info-equipo-header-text">

        <h2 id="tituloInfoEquipo">
          ${equipo.nombre}
        </h2>

        <p>
          ${equipo.tag ? equipo.tag : "Sin tag"}
        </p>

      </div>

    </div>

    <section class="info-equipo-seccion">

      <h3>
        <i class="fa-solid fa-circle-info"></i>
        Información
      </h3>

      <div class="info-equipo-dato">
        <strong>Disciplina:</strong>
        <span>
          ${equipo.deporte || "No especificada"}
        </span>
      </div>

      <div class="info-equipo-dato">
        <strong>Tag:</strong>
        <span>
          ${equipo.tag || "Sin tag"}
        </span>
      </div>

    </section>

    <section class="info-equipo-seccion">

      <h3>
        <i class="fa-solid fa-align-left"></i>
        Descripción
      </h3>

      <p>
        ${equipo.descripcion || "Este equipo no tiene descripción."}
      </p>

    </section>

    <section class="info-equipo-seccion">

      <h3>
        <i class="fa-solid fa-users"></i>
        Integrantes
      </h3>

      ${listaIntegrantes}

    </section>
  `;

  overlayInfoEquipo.classList.add("activo");
  modalInfoEquipo.classList.add("activo");
}

function cerrarModalInfoEquipo() {
  overlayInfoEquipo.classList.remove("activo");
  modalInfoEquipo.classList.remove("activo");
}

/*VALIDACIÓN DE IMÁGENES*/

/*
   Comprueba que una imagen:

   - sea PNG, JPG o WEBP
   - no supere el tamaño máximo
   - tenga las dimensiones mínimas
*/

function validarImagen(
  archivo,
  nombreCampo,
  anchoMinimo,
  altoMinimo,
  tamanioMaximo,
) {
  return new Promise((resolve, reject) => {
    if (!archivo) {
      resolve(null);

      return;
    }

    /*Comprobar formato*/

    const formatosPermitidos = ["image/png", "image/jpeg", "image/webp"];

    if (!formatosPermitidos.includes(archivo.type)) {
      reject(`${nombreCampo}: solo se permiten imágenes PNG, JPG o WEBP.`);

      return;
    }

    /*Comprobar tamaño del archivo*/

    if (archivo.size > tamanioMaximo) {
      const tamanioMB = tamanioMaximo / (1024 * 1024);

      reject(`${nombreCampo}: la imagen no puede superar los ${tamanioMB} MB.`);

      return;
    }

    /*Comprobar dimensiones*/

    const imagen = new Image();

    imagen.onload = () => {
      if (imagen.width < anchoMinimo || imagen.height < altoMinimo) {
        reject(
          `${nombreCampo}: la imagen debe tener como mínimo ${anchoMinimo}×${altoMinimo} píxeles.`,
        );

        return;
      }

      resolve(archivo);
    };

    imagen.onerror = () => {
      reject(`${nombreCampo}: no se pudo leer la imagen.`);
    };

    imagen.src = URL.createObjectURL(archivo);
  });
}

/* CONVERTIR IMAGEN A BASE64*/

function convertirImagenBase64(archivo) {
  return new Promise((resolve, reject) => {
    if (!archivo) {
      resolve(null);

      return;
    }

    const lector = new FileReader();

    lector.onload = () => {
      resolve(lector.result);
    };

    lector.onerror = () => {
      reject("No se pudo procesar la imagen.");
    };

    lector.readAsDataURL(archivo);
  });
}

/* VALIDAR NOMBRE */

function validarNombre(nombre) {
  const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 ]+$/;

  return regex.test(nombre);
}

/* VALIDAR DESCRIPCION */

function validarDescripcion(descripcion) {
  const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 .,!?¿¡:;()\-\/]+$/;

  return regex.test(descripcion);
}

/*PROCESAR ENVÍO DEL FORMULARIO*/

async function enviarFormulario(event) {
  event.preventDefault();

  try {
    /*Obtener datos del formulario*/

    const datos = Object.fromEntries(new FormData(formulario));

    if (!validarNombre(datos.nombre)) {
      throw "El nombre del torneo contiene caracteres no permitidos.";
    }

    if (!validarDescripcion(datos.descripcion)) {
      throw "La descripción contiene caracteres no permitidos.";
    }

    /*Obtener archivos*/

    const inputLogo = document.getElementById("logo");
    const inputBanner = document.getElementById("banner");
    const archivoLogo = inputLogo.files[0] || null;
    const archivoBanner = inputBanner.files[0] || null;

    /*Validar logo*/

    await validarImagen(archivoLogo, "Logo", 300, 300, 2 * 1024 * 1024);

    /*Validar banner*/

    await validarImagen(archivoBanner, "Banner", 800, 180, 5 * 1024 * 1024);

    /*Convertir imágenes*/

    datos.logo = await convertirImagenBase64(archivoLogo);

    datos.banner = await convertirImagenBase64(archivoBanner);

    /*Obtener información del deporte*/

    const deporte = catalogos.deportes.find((deporte) => {
      return deporte.id === datos.deporte;
    });

    if (deporte) {
      datos.tipoParticipante = deporte.tipoParticipante;

      if (deporte.tipoParticipante === "equipos") {
        datos.cantidadEquipos = datos.cantidadParticipantes;
      } else {
        datos.cantidadJugadores = datos.cantidadParticipantes;
      }

      delete datos.cantidadParticipantes;
    }

    /* EQUIPOS DEL TORNEO */

    datos.equipos = equiposSeleccionados.map((equipo) => ({
      id: equipo.id,
      nombre: equipo.nombre,
      tag: equipo.tag,
      logo: equipo.logo,
    }));

    /* GENERAR ID DEL TORNEO */

    datos.id = crypto.randomUUID();

    /*Guardar torneo*/

    const torneos = JSON.parse(localStorage.getItem("torneos")) || [];

    torneos.push(datos);

    localStorage.setItem("torneos", JSON.stringify(torneos));

    /*Mostrar datos en consola*/

    console.log("Torneo creado:", datos);

    /*Mostrar mensaje de éxito*/

    mensaje.textContent = "¡Torneo creado con éxito!";

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("exito");
    mensaje.style.display = "block";
  } catch (error) {
    /*Mostrar error*/

    mensaje.textContent = error;

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("error");
    mensaje.style.display = "block";
  }
}

/* PREVISUALIZACIÓN DE IMÁGENES */

/* Al hacer click en el logo */

previewLogo.addEventListener("click", (event) => {
  event.stopPropagation();

  inputLogo.click();
});

/* Al hacer click en el banner */

previewBanner.addEventListener("click", () => {
  inputBanner.click();
});

/* Cuando se selecciona un logo */

inputLogo.addEventListener("change", async () => {
  const archivo = inputLogo.files[0];

  if (!archivo) {
    return;
  }

  try {
    await validarImagen(archivo, "Logo", 300, 300, 2 * 1024 * 1024);

    const url = URL.createObjectURL(archivo);

    previewLogo.innerHTML = `
      <img
        src="${url}"
        alt="Previsualización del logo"
      >
    `;
  } catch (error) {
    alert(error);

    inputLogo.value = "";
  }
});

/* Cuando se selecciona un banner */

inputBanner.addEventListener("change", async () => {
  const archivo = inputBanner.files[0];

  if (!archivo) {
    return;
  }

  try {
    await validarImagen(archivo, "Banner", 800, 180, 5 * 1024 * 1024);

    const url = URL.createObjectURL(archivo);

    previewBanner.style.backgroundImage = `url("${url}")`;

    const textoBanner = previewBanner.querySelector(".preview-info p");

    textoBanner.style.opacity = "0";
  } catch (error) {
    alert(error);

    inputBanner.value = "";
  }
});

const inputNombre = document.getElementById("nombre");

inputNombre.addEventListener("input", () => {
  if (inputNombre.value.trim() === "") {
    previewNombre.textContent = "[TÍTULO AQUÍ]";
  } else {
    previewNombre.textContent = inputNombre.value;
  }
});
