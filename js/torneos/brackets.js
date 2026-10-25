/* BRACKETS Y POSICIONES: lista pública de torneos y cómo va cada uno */

const filtroFormato = document.getElementById("filtroFormato");
const buscarCuadro = document.getElementById("buscarCuadro");
const listaCuadros = document.getElementById("listaCuadros");
const sinCuadros = document.getElementById("sinCuadros");

let torneos = [];

const ETIQUETA_ESTADO = {
  sin_generar: "Sin generar",
  calendario: "Calendario listo",
  en_curso: "En curso",
  terminado: "Terminado",
};

const PRIORIDAD_ESTADO = { en_curso: 0, terminado: 1, calendario: 2, sin_generar: 3 };

const TEXTO_ENLACE = {
  liga: "Ver calendario",
  llave: "Ver bracket",
  suizo: "Ver rondas y clasificación",
};

const ICONO_ENLACE = {
  liga: "fa-calendar-days",
  llave: "fa-sitemap",
  suizo: "fa-ranking-star",
};

function formatearFecha(fecha) {
  const partes = String(fecha).split("-");
  return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fecha;
}

function mostrarTorneos() {
  const formato = filtroFormato.value;
  const texto = buscarCuadro.value.trim().toLowerCase();

  const lista = torneos
    .filter((t) => (!formato || t.formato === formato))
    .filter(
      (t) =>
        !texto ||
        t.nombre.toLowerCase().includes(texto) ||
        t.disciplina.toLowerCase().includes(texto) ||
        t.detalle.toLowerCase().includes(texto),
    )
    .sort(
      (a, b) =>
        PRIORIDAD_ESTADO[a.estado] - PRIORIDAD_ESTADO[b.estado] ||
        a.nombre.localeCompare(b.nombre, "es"),
    );

  listaCuadros.innerHTML = "";
  sinCuadros.hidden = lista.length > 0;

  lista.forEach((t) => {
    const tarjeta = document.createElement("article");
    tarjeta.className = "cuadro-tarjeta";

    const encabezado = document.createElement("div");
    encabezado.className = "cuadro-encabezado";

    const titulo = document.createElement("h3");
    /* textContent: un nombre con HTML se muestra como texto y no se ejecuta */
    titulo.textContent = t.nombre;

    const estado = document.createElement("span");
    estado.className = `cuadro-estado cuadro-estado-${t.estado}`;
    estado.textContent = ETIQUETA_ESTADO[t.estado] ?? t.estado;

    encabezado.append(titulo, estado);

    const meta = document.createElement("p");
    meta.className = "cuadro-meta";
    meta.textContent = `${t.disciplina} · ${t.formato} · ${formatearFecha(t.fecha_inicio)} al ${formatearFecha(t.fecha_fin)}`;

    const detalle = document.createElement("p");
    detalle.className = "cuadro-detalle";
    detalle.textContent = t.detalle;

    const enlace = document.createElement("a");
    enlace.className = "cuadro-enlace";
    /* encodeURIComponent: el id nunca puede romper la dirección */
    enlace.href = `calendario.html?id_torneo=${encodeURIComponent(t.id_torneo)}`;

    const icono = document.createElement("i");
    icono.className = `fa-solid ${ICONO_ENLACE[t.vista] ?? "fa-calendar-days"}`;
    icono.setAttribute("aria-hidden", "true");

    const textoEnlace = document.createElement("span");
    textoEnlace.textContent = TEXTO_ENLACE[t.vista] ?? "Ver torneo";

    enlace.append(icono, textoEnlace);

    tarjeta.append(encabezado, meta, detalle, enlace);
    listaCuadros.appendChild(tarjeta);
  });
}

async function cargarTorneos() {
  try {
    const respuesta = await fetch("../../../php/torneos/listar_cuadros.php");
    const datos = await respuesta.json();

    if (!datos.ok) {
      sinCuadros.textContent = datos.mensaje || "No se pudieron cargar los torneos.";
      sinCuadros.hidden = false;
      return;
    }

    torneos = datos.torneos;
    mostrarTorneos();
  } catch (error) {
    console.error("No se pudieron cargar los torneos:", error);
    sinCuadros.textContent = "No se pudo conectar con el servidor.";
    sinCuadros.hidden = false;
  }
}

filtroFormato.addEventListener("change", mostrarTorneos);
buscarCuadro.addEventListener("input", mostrarTorneos);

cargarTorneos();
