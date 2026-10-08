/* ELEMENTOS */

const filtroTorneo = document.getElementById("filtroTorneo");
const buscarPartido = document.getElementById("buscarPartido");
const cuerpoResultados = document.getElementById("cuerpoResultados");
const sinResultados = document.getElementById("sinResultados");

let partidos = [];

/* AAAA-MM-DD -> DD/MM/AAAA */

function formatearFecha(fecha) {
  const partes = String(fecha).split("-");

  if (partes.length !== 3) {
    return fecha;
  }

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

/* DIBUJAR LA TABLA */

function mostrarPartidos() {
  const idTorneo = filtroTorneo.value;
  const texto = buscarPartido.value.trim().toLowerCase();

  const lista = partidos.filter((partido) => {
    if (idTorneo && String(partido.id_torneo) !== idTorneo) {
      return false;
    }

    if (!texto) {
      return true;
    }

    return (
      partido.torneo.toLowerCase().includes(texto) ||
      partido.resultado.toLowerCase().includes(texto)
    );
  });

  cuerpoResultados.innerHTML = "";

  sinResultados.hidden = lista.length > 0;

  lista.forEach((partido) => {
    const fila = document.createElement("tr");

    [
      formatearFecha(partido.fecha),
      partido.torneo,
      partido.disciplina,
      partido.resultado,
    ].forEach((valor) => {
      const celda = document.createElement("td");

      /* textContent evita que un dato con HTML se ejecute en la página */
      celda.textContent = valor;

      fila.appendChild(celda);
    });

    cuerpoResultados.appendChild(fila);
  });
}

/* LLENAR EL SELECTOR DE TORNEOS */

function cargarFiltro() {
  const torneos = new Map();

  partidos.forEach((partido) => {
    torneos.set(partido.id_torneo, partido.torneo);
  });

  [...torneos.entries()]
    .sort((a, b) => a[1].localeCompare(b[1]))
    .forEach(([id, nombre]) => {
      const opcion = document.createElement("option");

      opcion.value = id;
      opcion.textContent = nombre;

      filtroTorneo.appendChild(opcion);
    });
}

/* CARGAR DESDE EL SERVIDOR */

async function cargarResultados() {
  try {
    const respuesta = await fetch(
      "../../../php/torneos/listar_resultados.php",
    );

    const datos = await respuesta.json();

    if (!datos.ok) {
      throw new Error(datos.mensaje || "Error al cargar los resultados.");
    }

    partidos = datos.partidos;
  } catch (error) {
    console.error("No se pudieron cargar los resultados:", error);

    partidos = [];
  }

  cargarFiltro();
  mostrarPartidos();
}

filtroTorneo.addEventListener("change", mostrarPartidos);
buscarPartido.addEventListener("input", mostrarPartidos);

cargarResultados();
