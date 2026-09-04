/*
JUGADORES TEMPORALES
Más adelante vendrán desde la base de datos
*/

const jugadores = [
  {
    id: 1,
    nombre: "Esmo",
  },
  {
    id: 2,
    nombre: "Player123",
  },
  {
    id: 3,
    nombre: "GamerX",
  },
  {
    id: 4,
    nombre: "Shadow",
  },
  {
    id: 5,
    nombre: "Neko",
  },
  {
    id: 6,
    nombre: "ProPlayer",
  },
  {
    id: 7,
    nombre: "DarkWolf",
  },
  {
    id: 8,
    nombre: "Luna",
  },
  {
    id: 9,
    nombre: "Pixel",
  },
  {
    id: 10,
    nombre: "Ace",
  },
];

/* ELEMENTOS DEL HTML */

const formulario = document.getElementById("formCrearEquipo");

const selectDeporte = document.getElementById("deporte");

const listaIntegrantes = document.getElementById("listaIntegrantes");
const informacionIntegrantes = document.getElementById(
  "informacionIntegrantes",
);

const inputNombre = document.getElementById("nombre");
const inputTag = document.getElementById("tag");
const inputLogo = document.getElementById("logo");

const previewNombre = document.getElementById("previewNombre");
const previewTag = document.getElementById("previewTag");
const previewLogo = document.getElementById("previewLogo");

const mensaje = document.getElementById("mensaje");

/* INICIALIZACIÓN */

// Cargar las disciplinas disponibles
cargarDeportes();

// Detectar cambio de deporte
selectDeporte.addEventListener("change", actualizarIntegrantes);

// Detectar envío del formulario
formulario.addEventListener("submit", enviarFormulario);

// Detectar cambios en el nombre
inputNombre.addEventListener("input", actualizarPreviewNombre);

// Detectar cambios en el tag
inputTag.addEventListener("input", actualizarPreviewTag);

// Detectar click en el logo
previewLogo.addEventListener("click", () => {
  inputLogo.click();
});

// Detectar selección del logo
inputLogo.addEventListener("change", actualizarPreviewLogo);

/* FUNCIONES */

/* CARGAR DISCIPLINAS */

function cargarDeportes() {
  selectDeporte.innerHTML =
    '<option value="">Seleccione una disciplina:</option>';

  catalogos.deportes.forEach((deporte) => {
    const option = document.createElement("option");

    option.value = deporte.id;
    option.textContent = deporte.nombre;

    selectDeporte.appendChild(option);
  });
}

/* ACTUALIZAR INTEGRANTES SEGÚN EL DEPORTE */

function actualizarIntegrantes() {
  const deporteSeleccionado = selectDeporte.value;

  listaIntegrantes.innerHTML = "";
  informacionIntegrantes.innerHTML = "";

  if (!deporteSeleccionado) {
    informacionIntegrantes.innerHTML =
      "<p>Seleccione una disciplina para configurar los integrantes.</p>";

    return;
  }

  const deporte = catalogos.deportes.find((deporte) => {
    return deporte.id === deporteSeleccionado;
  });

  if (!deporte || !deporte.configuracionEquipo) {
    informacionIntegrantes.innerHTML =
      "<p>No existe una configuración para esta disciplina.</p>";

    return;
  }

  const configuracion = deporte.configuracionEquipo;

  informacionIntegrantes.innerHTML = ` <p> <strong>${deporte.nombre}</strong> </p>


<p>
  Titulares: ${configuracion.titulares}
  ${
    configuracion.suplentes > 0
      ? ` · Suplentes: ${configuracion.suplentes}`
      : ""
  }
</p>


`;

  crearListaIntegrantes(configuracion);
}

/* CREAR LISTA DE INTEGRANTES */

function crearListaIntegrantes(configuracion) {
  listaIntegrantes.innerHTML = "";

  /* TITULARES */

  const tituloTitulares = document.createElement("h3");

  tituloTitulares.textContent = "Titulares";

  listaIntegrantes.appendChild(tituloTitulares);

  for (let i = 0; i < configuracion.titulares; i++) {
    const rol = configuracion.roles[i] || "Jugador";

    const campo = crearSelectorJugador(rol, `titular-${i}`);

    listaIntegrantes.appendChild(campo);
  }

  /* SUPLENTES */

  if (configuracion.suplentes > 0) {
    const tituloSuplentes = document.createElement("h3");

    tituloSuplentes.textContent = "Suplentes";

    listaIntegrantes.appendChild(tituloSuplentes);

    for (let i = 0; i < configuracion.suplentes; i++) {
      const campo = crearSelectorJugador(`Suplente ${i + 1}`, `suplente-${i}`);

      listaIntegrantes.appendChild(campo);
    }
  }
}

/* CREAR SELECTOR DE JUGADOR */

function crearSelectorJugador(rol, id) {
  const contenedor = document.createElement("div");

  contenedor.classList.add("campoFormulario", "integrante");

  const label = document.createElement("label");

  label.htmlFor = id;
  label.textContent = `${rol}:`;

  const select = document.createElement("select");

  select.id = id;
  select.name = id;

  select.innerHTML = '<option value="">Seleccione un jugador:</option>';

  jugadores.forEach((jugador) => {
    const option = document.createElement("option");

    option.value = jugador.id;
    option.textContent = jugador.nombre;

    select.appendChild(option);
  });

  select.addEventListener("change", actualizarJugadoresDisponibles);

  contenedor.appendChild(label);
  contenedor.appendChild(select);

  return contenedor;
}

/* EVITAR JUGADORES REPETIDOS */

function actualizarJugadoresDisponibles() {
  const selects = listaIntegrantes.querySelectorAll("select");

  const seleccionados = new Set();

  selects.forEach((select) => {
    if (select.value) {
      seleccionados.add(select.value);
    }
  });

  selects.forEach((select) => {
    const valorActual = select.value;

    Array.from(select.options).forEach((option) => {
      if (!option.value) {
        return;
      }

      option.disabled =
        seleccionados.has(option.value) && option.value !== valorActual;
    });
  });
}

/* VALIDAR IMAGEN */

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

    /* Comprobar formato */

    const formatosPermitidos = ["image/png", "image/jpeg", "image/webp"];

    if (!formatosPermitidos.includes(archivo.type)) {
      reject(`${nombreCampo}: solo se permiten imágenes PNG, JPG o WEBP.`);

      return;
    }

    /* Comprobar tamaño */

    if (archivo.size > tamanioMaximo) {
      const tamanioMB = tamanioMaximo / (1024 * 1024);

      reject(`${nombreCampo}: la imagen no puede superar los ${tamanioMB} MB.`);

      return;
    }

    /* Comprobar dimensiones */

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

/* CONVERTIR IMAGEN A BASE64 */

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

/* VALIDAR TAG */

function validarTag(tag) {
  const regex = /^[A-Za-z0-9]+$/;

  return regex.test(tag);
}

/* VALIDAR DESCRIPCIÓN */

function validarDescripcion(descripcion) {
  const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9 .,!?¿¡:;()-/]+$/;

  return regex.test(descripcion);
}

/* PREVISUALIZACIÓN DEL NOMBRE */

function actualizarPreviewNombre() {
  const nombre = inputNombre.value.trim();

  previewNombre.textContent = nombre || "[NOMBRE DEL EQUIPO]";
}

/* PREVISUALIZACIÓN DEL TAG */

function actualizarPreviewTag() {
  const tag = inputTag.value.trim();

  previewTag.textContent = tag || "[TAG]";
}

/* PREVISUALIZACIÓN DEL LOGO */

async function actualizarPreviewLogo() {
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
}

/* PROCESAR ENVÍO DEL FORMULARIO */

async function enviarFormulario(event) {
  event.preventDefault();

  try {
    /* Obtener datos del formulario */

    const datos = Object.fromEntries(new FormData(formulario));

    /* Validar nombre */

    if (!validarNombre(datos.nombre)) {
      throw "El nombre del equipo contiene caracteres no permitidos.";
    }

    /* Validar tag */

    if (!validarTag(datos.tag)) {
      throw "El tag contiene caracteres no permitidos.";
    }

    /* Validar descripción */

    if (!validarDescripcion(datos.descripcion)) {
      throw "La descripción contiene caracteres no permitidos.";
    }

    /* Obtener archivo */

    const archivoLogo = inputLogo.files[0] || null;

    /* Validar logo */

    await validarImagen(archivoLogo, "Logo", 300, 300, 2 * 1024 * 1024);

    /* Convertir logo */

    datos.logo = await convertirImagenBase64(archivoLogo);

    /* Obtener información del deporte */

    const deporte = catalogos.deportes.find((deporte) => {
      return deporte.id === datos.deporte;
    });

    if (!deporte) {
      throw "Seleccione una disciplina.";
    }

    datos.deporte = deporte.nombre;
    datos.deporteId = deporte.id;

    /* Obtener integrantes */

    const integrantes = [];

    const selects = listaIntegrantes.querySelectorAll("select");

    selects.forEach((select) => {
      if (!select.value) {
        return;
      }

      const jugador = jugadores.find((jugador) => {
        return String(jugador.id) === select.value;
      });

      if (jugador) {
        integrantes.push({
          jugadorId: jugador.id,
          jugador: jugador.nombre,
          rol: select.previousElementSibling.textContent.replace(":", ""),
        });
      }
    });

    datos.integrantes = integrantes;

    /* Generar ID del equipo */

    datos.id = crypto.randomUUID();

    /* Guardar equipo */

    const equipos = JSON.parse(localStorage.getItem("equipos")) || [];

    equipos.push(datos);

    localStorage.setItem("equipos", JSON.stringify(equipos));

    /* Mostrar datos en consola */

    console.log("Equipo creado:", datos);

    /* Mostrar mensaje de éxito */

    mensaje.textContent = "¡Equipo creado con éxito!";

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("exito");
    mensaje.style.display = "block";
  } catch (error) {
    /* Mostrar error */

    mensaje.textContent = error;

    mensaje.classList.remove("error", "exito");
    mensaje.style.display = "none";

    void mensaje.offsetWidth;

    mensaje.classList.add("error");
    mensaje.style.display = "block";
  }
}

//Scroll de nombre
function configurarScrollNombre() {
  const tiempoEspera = 1000; //1000 = 1s, 2000 = 2s, etc...

  if (!previewNombre) return;

  previewNombre.addEventListener("mouseenter", () => {
    if (previewNombre.scrollWidth <= previewNombre.clientWidth) {
      return;
    }

    let posicion = 0;
    let direccion = 1;
    let esperando = false;

    previewNombre._scrollActivo = true;

    function desplazar() {
      if (!previewNombre._scrollActivo) return;

      if (esperando) {
        return;
      }

      posicion += direccion * 0.5;
      previewNombre.scrollLeft = posicion;

      /* Llegó al final */

      if (
        direccion === 1 &&
        previewNombre.scrollLeft >=
          previewNombre.scrollWidth - previewNombre.clientWidth
      ) {
        esperando = true;

        setTimeout(() => {
          if (!previewNombre._scrollActivo) return;

          direccion = -1;
          esperando = false;

          previewNombre._scrollFrame = requestAnimationFrame(desplazar);
        }, tiempoEspera);

        return;
      }

      /* Llegó al principio */

      if (direccion === -1 && previewNombre.scrollLeft <= 0) {
        esperando = true;

        setTimeout(() => {
          if (!previewNombre._scrollActivo) return;

          direccion = 1;
          esperando = false;

          previewNombre._scrollFrame = requestAnimationFrame(desplazar);
        }, tiempoEspera);

        return;
      }

      previewNombre._scrollFrame = requestAnimationFrame(desplazar);
    }

    previewNombre._scrollFrame = requestAnimationFrame(desplazar);
  });

  previewNombre.addEventListener("mouseleave", () => {
    previewNombre._scrollActivo = false;

    if (previewNombre._scrollFrame) {
      cancelAnimationFrame(previewNombre._scrollFrame);
    }

    previewNombre.scrollLeft = 0;
  });
}

document.addEventListener("DOMContentLoaded", () => {
  configurarScrollNombre();
});
