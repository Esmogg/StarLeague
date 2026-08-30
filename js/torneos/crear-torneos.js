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

    await validarImagen(archivoBanner, "Banner", 800, 300, 5 * 1024 * 1024);

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

    mensaje.classList.remove("error");
    mensaje.classList.add("exito");

    mensaje.style.display = "block";
  } catch (error) {
    /*Mostrar error*/

    mensaje.textContent = error;

    mensaje.classList.remove("exito");
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
    await validarImagen(archivo, "Banner", 800, 300, 5 * 1024 * 1024);

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
