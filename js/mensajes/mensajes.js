/* MENSAJES: los participantes escriben a los organizadores; el staff atiende su bandeja */

const formMensaje = document.getElementById("formMensaje");
const msgTipo = document.getElementById("msgTipo");
const msgTorneo = document.getElementById("msgTorneo");
const msgTorneoEtiqueta = document.getElementById("msgTorneoEtiqueta");
const msgAsunto = document.getElementById("msgAsunto");
const msgTexto = document.getElementById("msgTexto");
const msgContador = document.getElementById("msgContador");
const msgEnviar = document.getElementById("msgEnviar");
const msgMensaje = document.getElementById("msgMensaje");

const bandejaStaff = document.getElementById("bandejaStaff");
const listaRecibidos = document.getElementById("listaRecibidos");
const vacioRecibidos = document.getElementById("vacioRecibidos");
const filtroTipo = document.getElementById("filtroTipo");
const filtroEstado = document.getElementById("filtroEstado");
const listaEnviados = document.getElementById("listaEnviados");
const vacioEnviados = document.getElementById("vacioEnviados");

const ETIQUETA_TIPO = {
  solicitud: "Solicitud de inscripción",
  consulta: "Consulta",
  reporte: "Reporte de conducta",
};

let recibidos = [];

function avisar(elemento, texto, ok) {
  elemento.textContent = texto;
  elemento.classList.toggle("exito", ok === true);
  elemento.classList.toggle("error", ok === false);
}

function formatearFecha(texto) {
  const fecha = new Date(String(texto).replace(" ", "T"));
  return Number.isNaN(fecha.getTime())
    ? ""
    : fecha.toLocaleString("es-UY", { dateStyle: "short", timeStyle: "short" });
}

async function llamar(url, cuerpo) {
  const respuesta = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(cuerpo),
  });
  return respuesta.json();
}

/* TARJETA DE UN MENSAJE (todo con textContent: nada de lo escrito se interpreta como HTML) */

function crearTarjeta(mensaje, comoStaff) {
  const tarjeta = document.createElement("article");
  tarjeta.className = `mensaje-tarjeta ${mensaje.estado}`;

  const cabecera = document.createElement("div");
  cabecera.className = "mensaje-cabecera";
  const etiqueta = document.createElement("span");
  etiqueta.className = `mensaje-tipo ${mensaje.tipo}`;
  etiqueta.textContent = ETIQUETA_TIPO[mensaje.tipo] || mensaje.tipo;
  const estado = document.createElement("span");
  estado.className = "mensaje-estado-texto";
  estado.textContent = mensaje.estado === "nuevo" ? "Pendiente" : "Atendido";
  cabecera.append(etiqueta, estado);

  const asunto = document.createElement("h4");
  asunto.textContent = mensaje.asunto;

  const meta = document.createElement("p");
  meta.className = "mensaje-meta";
  const partes = [formatearFecha(mensaje.fecha)];
  if (comoStaff) partes.unshift(`De: ${mensaje.remitente.nombre}`);
  if (mensaje.torneo) partes.push(`Torneo: ${mensaje.torneo.nombre}`);
  meta.textContent = partes.filter(Boolean).join(" · ");

  const texto = document.createElement("p");
  texto.className = "mensaje-texto";
  texto.textContent = mensaje.texto;

  tarjeta.append(cabecera, asunto, meta, texto);

  if (comoStaff) {
    const acciones = document.createElement("div");
    acciones.className = "mensaje-acciones";

    if (mensaje.tipo === "solicitud" && mensaje.torneo) {
      if (mensaje.torneo.por_equipo) {
        const enlace = document.createElement("a");
        enlace.href = "../torneos/inscribir_torneos.html";
        enlace.className = "mensajes-boton";
        enlace.textContent = "Ir a inscribir equipos";
        acciones.appendChild(enlace);
      } else {
        const inscribir = document.createElement("button");
        inscribir.type = "button";
        inscribir.className = "mensajes-boton";
        inscribir.textContent = `Inscribir a ${mensaje.remitente.nombre}`;
        inscribir.addEventListener("click", () => inscribirSolicitante(mensaje, inscribir));
        acciones.appendChild(inscribir);
      }
    }

    const alternar = document.createElement("button");
    alternar.type = "button";
    alternar.className = "mensajes-boton secundario";
    alternar.textContent = mensaje.estado === "nuevo" ? "Marcar atendido" : "Marcar pendiente";
    alternar.addEventListener("click", () => cambiarEstado(mensaje, alternar));

    const eliminar = document.createElement("button");
    eliminar.type = "button";
    eliminar.className = "mensajes-boton peligro";
    eliminar.textContent = "Eliminar";
    eliminar.addEventListener("click", () => eliminarMensaje(mensaje, eliminar));

    acciones.append(alternar, eliminar);
    tarjeta.appendChild(acciones);
  }

  return tarjeta;
}

/* BANDEJA DEL STAFF */

function mostrarRecibidos() {
  const lista = recibidos.filter(
    (m) =>
      (!filtroTipo.value || m.tipo === filtroTipo.value) &&
      (!filtroEstado.value || m.estado === filtroEstado.value),
  );
  listaRecibidos.replaceChildren(...lista.map((m) => crearTarjeta(m, true)));
  vacioRecibidos.hidden = lista.length > 0;
}

async function cargarRecibidos() {
  try {
    const respuesta = await fetch("../../../php/mensajes/listar.php?bandeja=recibidos");
    if (respuesta.status === 403) return; /* un jugador no ve la bandeja */
    const datos = await respuesta.json();
    if (!datos.ok) return;
    recibidos = datos.mensajes;
    bandejaStaff.hidden = false;
    mostrarRecibidos();
  } catch (error) {
    console.error("No se pudo cargar la bandeja:", error);
  }
}

async function cambiarEstado(mensaje, boton) {
  boton.disabled = true;
  const nuevo = mensaje.estado === "nuevo" ? "atendido" : "nuevo";
  const datos = await llamar("../../../php/mensajes/atender.php", {
    id_mensaje: mensaje.id,
    estado: nuevo,
  });
  if (!datos.ok) alert(datos.mensaje || "No se pudo actualizar el mensaje.");
  await cargarRecibidos();
}

async function eliminarMensaje(mensaje, boton) {
  if (!confirm("¿Eliminar este mensaje? No se puede deshacer.")) return;
  boton.disabled = true;
  const datos = await llamar("../../../php/mensajes/eliminar.php", { id_mensaje: mensaje.id });
  if (!datos.ok) alert(datos.mensaje || "No se pudo eliminar el mensaje.");
  await cargarRecibidos();
}

/* Inscribir al solicitante en el torneo (individual) y marcar la solicitud como atendida */
async function inscribirSolicitante(mensaje, boton) {
  if (!confirm(`¿Inscribir a ${mensaje.remitente.nombre} en ${mensaje.torneo.nombre}?`)) return;
  boton.disabled = true;
  try {
    const datos = await llamar("../../../php/torneos/procesar_inscripcion.php", {
      id_torneo: mensaje.torneo.id,
      tipo_inscripcion: "usuario",
      id_participante: mensaje.remitente.id,
    });
    alert(datos.mensaje || "No se pudo completar la inscripción.");
    if (datos.ok) {
      await llamar("../../../php/mensajes/atender.php", { id_mensaje: mensaje.id, estado: "atendido" });
    }
  } catch (error) {
    console.error("Error al inscribir:", error);
    alert("No se pudo conectar con el servidor.");
  }
  await cargarRecibidos();
}

/* MIS MENSAJES */

async function cargarEnviados() {
  try {
    const respuesta = await fetch("../../../php/mensajes/listar.php?bandeja=enviados");
    const datos = await respuesta.json();
    if (!datos.ok) return;
    listaEnviados.replaceChildren(...datos.mensajes.map((m) => crearTarjeta(m, false)));
    vacioEnviados.hidden = datos.mensajes.length > 0;
  } catch (error) {
    console.error("No se pudieron cargar tus mensajes:", error);
  }
}

/* FORMULARIO */

async function cargarTorneos() {
  try {
    const respuesta = await fetch("../../../php/torneos/listar_torneos.php");
    const datos = await respuesta.json();
    if (!datos.ok) return;
    datos.torneos.forEach((t) => {
      const opcion = document.createElement("option");
      opcion.value = t.id_torneo;
      opcion.textContent = t.nombre;
      msgTorneo.appendChild(opcion);
    });
  } catch (error) {
    console.error("No se pudieron cargar los torneos:", error);
  }
}

function ajustarTorneo() {
  const obligatorio = msgTipo.value === "solicitud";
  msgTorneoEtiqueta.textContent = obligatorio ? "Torneo (obligatorio)" : "Torneo (opcional)";
  const primera = msgTorneo.querySelector("option[value='']");
  if (primera) primera.hidden = obligatorio;
  if (obligatorio && msgTorneo.value === "") msgTorneo.selectedIndex = msgTorneo.options.length > 1 ? 1 : 0;
}

function validar() {
  const asunto = msgAsunto.value.trim();
  const texto = msgTexto.value.trim();
  if (msgTipo.value === "solicitud" && !msgTorneo.value) return "Elige el torneo al que quieres que te inscriban.";
  if (asunto.length < 3 || asunto.length > 100) return "El asunto debe tener entre 3 y 100 caracteres.";
  if (texto.length < 10 || texto.length > 1000) return "El mensaje debe tener entre 10 y 1000 caracteres.";
  return null;
}

formMensaje.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  const error = validar();
  if (error) return avisar(msgMensaje, error, false);

  msgEnviar.disabled = true;
  avisar(msgMensaje, "Enviando...", null);
  try {
    const datos = await llamar("../../../php/mensajes/enviar.php", {
      tipo: msgTipo.value,
      id_torneo: msgTorneo.value === "" ? null : Number(msgTorneo.value),
      asunto: msgAsunto.value.trim(),
      texto: msgTexto.value.trim(),
    });
    avisar(msgMensaje, datos.mensaje || "No se pudo enviar el mensaje.", datos.ok);
    if (datos.ok) {
      msgAsunto.value = "";
      msgTexto.value = "";
      msgContador.textContent = "0 / 1000";
      cargarEnviados();
      cargarRecibidos();
    }
  } catch (e) {
    console.error("Error al enviar el mensaje:", e);
    avisar(msgMensaje, "No se pudo conectar con el servidor.", false);
  }
  msgEnviar.disabled = false;
});

msgTipo.addEventListener("change", ajustarTorneo);
msgTexto.addEventListener("input", () => {
  msgContador.textContent = `${msgTexto.value.length} / 1000`;
});
filtroTipo.addEventListener("change", mostrarRecibidos);
filtroEstado.addEventListener("change", mostrarRecibidos);

(async () => {
  const vacia = document.createElement("option");
  vacia.value = "";
  vacia.textContent = "(ninguno / general)";
  msgTorneo.appendChild(vacia);
  await cargarTorneos();
  ajustarTorneo();
  cargarEnviados();
  cargarRecibidos();
})();
