const formulario = document.getElementById("formCrearTorneo");

const selectCategoria = document.getElementById("categoria");
const selectDeporte = document.getElementById("deporte");
const selectFormato = document.getElementById("formato");

const labelCantidad = document.getElementById("labelCantidad");
const inputCantidad = document.getElementById("cantidadParticipantes");

const mensajeExito = document.getElementById("mensajeExito");

const configuracionDeporte = document.getElementById("configuracionDeporte");

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

/* 
   Carga los formatos del torneo
 */

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

/*PROCESAR ENVÍO DEL FORMULARIO*/

async function enviarFormulario(event) {
  event.preventDefault();

  try {
    /*Obtener datos del formulario*/

    const datos = Object.fromEntries(new FormData(formulario));

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

    /*Guardar torneo*/

    const torneos = JSON.parse(localStorage.getItem("torneos")) || [];

    torneos.push(datos);

    localStorage.setItem("torneos", JSON.stringify(torneos));

    /*Mostrar datos en consola*/

    console.log("Torneo creado:", datos);

    /*Mostrar mensaje de éxito*/

    mensajeExito.textContent = "¡Torneo creado con éxito!";

    mensajeExito.style.display = "block";
  } catch (error) {
    /*Mostrar error*/

    mensajeExito.textContent = error;

    mensajeExito.style.display = "block";
  }
}
