/* CALENDARIO DE UN TORNEO DE LIGA */

const calTitulo = document.getElementById("calTitulo");
const calMeta = document.getElementById("calMeta");
const calAcciones = document.getElementById("calAcciones");
const calIdaVuelta = document.getElementById("calIdaVuelta");
const calGenerar = document.getElementById("calGenerar");
const calMensaje = document.getElementById("calMensaje");
const calJornadas = document.getElementById("calJornadas");
const calLlave = document.getElementById("calLlave");
const calPosiciones = document.getElementById("calPosiciones");
const calSuizo = document.getElementById("calSuizo");
const calSiguiente = document.getElementById("calSiguiente");
const calOpcionIdaVuelta = document.getElementById("calOpcionIdaVuelta");

/* El id del torneo viene en la dirección (?id_torneo=3). Si no es un entero positivo se descarta. */
const idTorneo = Number.parseInt(
  new URLSearchParams(window.location.search).get("id_torneo") ?? "",
  10,
);

let torneo = null;
let hayCalendario = false;

/* AAAA-MM-DD -> DD/MM/AAAA */

function formatearFecha(fecha) {
  const partes = String(fecha).split("-");

  if (partes.length !== 3) {
    return fecha;
  }

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function mostrarMensaje(texto, tipo = "") {
  calMensaje.textContent = texto;
  calMensaje.className = `calendario-mensaje ${tipo}`.trim();
}

/* DIBUJAR LAS JORNADAS */

function dibujarJornadas(jornadas) {
  calJornadas.innerHTML = "";

  jornadas.forEach((jornada) => {
    const bloque = document.createElement("section");
    bloque.className = "calendario-jornada";

    const titulo = document.createElement("h3");
    /* textContent: un nombre con HTML se muestra como texto y no se ejecuta */
    titulo.textContent = `Jornada ${jornada.jornada} · ${formatearFecha(jornada.fecha)}`;
    bloque.appendChild(titulo);

    const lista = document.createElement("ul");
    lista.className = "calendario-encuentros";

    jornada.encuentros.forEach((encuentro) => {
      const fila = document.createElement("li");

      const local = document.createElement("span");
      local.className = "calendario-local";
      local.textContent = encuentro.local;

      const contra = document.createElement("span");
      contra.className = "calendario-vs";
      contra.textContent = encuentro.visitante === null ? "" : "vs";

      const visitante = document.createElement("span");
      visitante.className = "calendario-visitante";

      if (encuentro.visitante === null) {
        visitante.textContent = "Descansa";
        visitante.classList.add("calendario-descansa");
      } else {
        visitante.textContent = encuentro.visitante;

        if (encuentro.ganador === "") {
          local.classList.add("suizo-empate");
          visitante.classList.add("suizo-empate");
        } else if (encuentro.ganador !== null) {
          const gano = encuentro.ganador === encuentro.local;
          (gano ? local : visitante).classList.add("llave-ganador");
          (gano ? visitante : local).classList.add("llave-perdedor");
        }
      }

      fila.append(local, contra, visitante);

      /* Resultado: el organizador lo carga o lo corrige en cualquier jornada */
      if (encuentro.visitante !== null) {
        fila.appendChild(
          crearAccionesResultado(encuentro, torneo.puede_generar, (lado) =>
            registrarResultadoLiga(encuentro.id, lado),
          ),
        );
      }

      lista.appendChild(fila);
    });

    bloque.appendChild(lista);
    calJornadas.appendChild(bloque);
  });
}

/* TABLA DE POSICIONES DE LA LIGA */

function dibujarPosicionesLiga(posiciones) {
  calPosiciones.innerHTML = "";

  if (!posiciones || posiciones.length === 0) {
    return;
  }

  const titulo = document.createElement("h3");
  titulo.className = "suizo-titulo-tabla";
  titulo.textContent = "Tabla de posiciones";

  calPosiciones.append(titulo, crearTablaPosiciones(posiciones, COLUMNAS_LIGA));
}

/* DIBUJAR LA LLAVE (ELIMINACIÓN DIRECTA) */

function nombreDeRonda(ronda, total) {
  const faltan = total - ronda + 1;

  if (faltan === 1) return "Final";
  if (faltan === 2) return "Semifinales";
  if (faltan === 3) return "Cuartos de final";
  if (faltan === 4) return "Octavos de final";

  return `Ronda ${ronda}`;
}

function crearLado(partido, ronda, lado, esPase) {
  const nombre = partido[lado];
  const fila = document.createElement("div");
  fila.className = "llave-lado";

  const texto = document.createElement("span");
  texto.className = "llave-nombre";

  if (nombre === null) {
    /* En la primera ronda, un visitante vacío es un pase directo; después, es un lugar por definir */
    texto.textContent = esPase ? "Pase directo" : "Por definir";
    texto.classList.add("llave-vacio");
  } else {
    texto.textContent = nombre;
  }

  fila.appendChild(texto);

  if (partido.ganador !== null && nombre !== null) {
    fila.classList.add(partido.ganador === nombre ? "llave-ganador" : "llave-perdedor");
  }

  /* El organizador marca al ganador cuando los dos participantes ya están definidos */
  const definido = partido.local !== null && partido.visitante !== null;

  if (torneo.puede_generar && definido && partido.ganador === null) {
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "llave-gana";
    boton.textContent = "Gana";
    boton.setAttribute("aria-label", `Marcar como ganador a ${nombre}`);
    boton.addEventListener("click", () =>
      registrarGanador(ronda, partido.posicion, lado),
    );
    fila.appendChild(boton);
  }

  return fila;
}

function dibujarLlave(rondas) {
  calLlave.innerHTML = "";

  if (rondas.length === 0) {
    return;
  }

  /* Campeón: el ganador del único partido de la última ronda */
  const final = rondas[rondas.length - 1].partidos[0];

  if (final && final.ganador !== null) {
    const campeon = document.createElement("p");
    campeon.className = "llave-campeon";
    campeon.textContent = `🏆 Campeón: ${final.ganador}`;
    calLlave.appendChild(campeon);
  }

  const tablero = document.createElement("div");
  tablero.className = "llave-rondas";

  rondas.forEach((ronda) => {
    const columna = document.createElement("section");
    columna.className = "llave-ronda";

    const titulo = document.createElement("h3");
    titulo.textContent = `${nombreDeRonda(ronda.ronda, rondas.length)} · ${formatearFecha(ronda.fecha)}`;
    columna.appendChild(titulo);

    const lista = document.createElement("div");
    lista.className = "llave-partidos";

    ronda.partidos.forEach((partido) => {
      const tarjeta = document.createElement("div");
      tarjeta.className = "llave-partido";

      const esPase = ronda.ronda === 1 && partido.visitante === null;

      tarjeta.appendChild(crearLado(partido, ronda.ronda, "local", false));
      tarjeta.appendChild(crearLado(partido, ronda.ronda, "visitante", esPase));

      /* Deshacer: solo el organizador y solo en partidos jugados (no en pases directos) */
      if (torneo.puede_generar && partido.ganador !== null && !esPase) {
        const deshacer = document.createElement("button");
        deshacer.type = "button";
        deshacer.className = "llave-deshacer";
        deshacer.textContent = "Deshacer resultado";
        deshacer.addEventListener("click", () =>
          registrarGanador(ronda.ronda, partido.posicion, "ninguno"),
        );
        tarjeta.appendChild(deshacer);
      }

      lista.appendChild(tarjeta);
    });

    columna.appendChild(lista);
    tablero.appendChild(columna);
  });

  calLlave.appendChild(tablero);
}

/* TABLA DE POSICIONES (Liga y Sistema Suizo) */

function textoPuntos(valor) {
  /* 1 -> "1", 0.5 -> "0,5", 2.5 -> "2,5" */
  return String(valor).replace(".", ",");
}

/* columnas: [[texto del encabezado, ayuda, campo, esNombre?], ...] */
function crearTablaPosiciones(filas, columnas) {
  const envoltorio = document.createElement("div");
  envoltorio.className = "suizo-tabla-envoltorio";

  const tabla = document.createElement("table");
  tabla.className = "suizo-tabla";

  const cabecera = document.createElement("thead");
  const filaCabecera = document.createElement("tr");

  columnas.forEach(([texto, ayuda]) => {
    const th = document.createElement("th");
    th.scope = "col";
    th.textContent = texto;
    th.title = ayuda;
    filaCabecera.appendChild(th);
  });

  cabecera.appendChild(filaCabecera);
  tabla.appendChild(cabecera);

  const cuerpo = document.createElement("tbody");

  filas.forEach((fila) => {
    const tr = document.createElement("tr");

    columnas.forEach(([, , campo, esNombre]) => {
      const td = document.createElement("td");
      const valor = fila[campo];

      /* textContent: un nombre con HTML se muestra como texto y no se ejecuta */
      td.textContent = typeof valor === "number" && !Number.isInteger(valor)
        ? textoPuntos(valor)
        : String(valor);

      if (esNombre) td.className = "suizo-nombre";
      tr.appendChild(td);
    });

    cuerpo.appendChild(tr);
  });

  tabla.appendChild(cuerpo);
  envoltorio.appendChild(tabla);
  return envoltorio;
}

const COLUMNAS_LIGA = [
  ["#", "Posición", "posicion"],
  ["Participante", "Participante", "nombre", true],
  ["Pts", "Puntos (victoria 3, empate 1)", "puntos"],
  ["PJ", "Partidos jugados", "jugados"],
  ["G", "Ganados", "ganados"],
  ["E", "Empatados", "empatados"],
  ["P", "Perdidos", "perdidos"],
];

const COLUMNAS_SUIZO = [
  ["#", "Posición", "posicion"],
  ["Participante", "Participante", "nombre", true],
  ["Pts", "Puntos", "puntos"],
  ["Desempate", "Desempate: suma de los puntos de los rivales enfrentados", "desempate"],
  ["PJ", "Partidos jugados", "jugados"],
  ["G", "Ganados", "ganados"],
  ["E", "Empatados", "empatados"],
  ["P", "Perdidos", "perdidos"],
  ["Pase", "Pases", "pases"],
];

/* Botones Gana local / Empate / Gana visitante y, ya jugado, el resultado con Deshacer.
   registrar(lado) envía el resultado; puedeEditar dice si este usuario puede tocarlo. */

function crearBotonResultado(texto, descripcion, accion) {
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "llave-gana";
  boton.textContent = texto;
  boton.setAttribute("aria-label", descripcion);
  boton.addEventListener("click", accion);
  return boton;
}

function crearAccionesResultado(partido, puedeEditar, registrar) {
  const acciones = document.createElement("div");
  acciones.className = "suizo-acciones";

  if (partido.ganador === null) {
    if (puedeEditar) {
      acciones.append(
        crearBotonResultado("Gana local", `Marcar como ganador a ${partido.local}`, () => registrar("local")),
        crearBotonResultado("Empate", `Marcar empate entre ${partido.local} y ${partido.visitante}`, () => registrar("empate")),
        crearBotonResultado("Gana visitante", `Marcar como ganador a ${partido.visitante}`, () => registrar("visitante")),
      );
    } else {
      const pendiente = document.createElement("span");
      pendiente.className = "llave-vacio";
      pendiente.textContent = "Sin jugar";
      acciones.appendChild(pendiente);
    }

    return acciones;
  }

  const resultado = document.createElement("span");
  resultado.className = "suizo-resultado";
  resultado.textContent = partido.ganador === "" ? "Empate" : `Ganó ${partido.ganador}`;
  acciones.appendChild(resultado);

  if (puedeEditar) {
    const deshacer = document.createElement("button");
    deshacer.type = "button";
    deshacer.className = "llave-deshacer";
    deshacer.textContent = "Deshacer";
    deshacer.addEventListener("click", () => registrar("ninguno"));
    acciones.appendChild(deshacer);
  }

  return acciones;
}

/* DIBUJAR EL SISTEMA SUIZO */

function dibujarSuizo(rondas, suizo) {
  calSuizo.innerHTML = "";

  if (rondas.length === 0 || suizo === null) {
    return;
  }

  const lider = suizo.clasificacion[0];

  if (suizo.terminado && lider) {
    const campeon = document.createElement("p");
    campeon.className = "llave-campeon";
    campeon.textContent = `🏆 Líder final: ${lider.nombre} (${textoPuntos(lider.puntos)} puntos)`;
    calSuizo.appendChild(campeon);
  }

  const estado = document.createElement("p");
  estado.className = "calendario-meta";
  estado.textContent = suizo.terminado
    ? `Torneo terminado: ${suizo.total_rondas} rondas jugadas.`
    : `Ronda ${suizo.ronda_actual} de ${suizo.total_rondas}.`;
  calSuizo.appendChild(estado);

  /* Rondas: la más nueva primero, que es la que se está jugando */
  const contenedor = document.createElement("div");
  contenedor.className = "calendario-jornadas";

  [...rondas].reverse().forEach((ronda) => {
    const bloque = document.createElement("section");
    bloque.className = "calendario-jornada";

    const titulo = document.createElement("h3");
    titulo.textContent = `Ronda ${ronda.ronda} · ${formatearFecha(ronda.fecha)}`;
    bloque.appendChild(titulo);

    const lista = document.createElement("ul");
    lista.className = "calendario-encuentros suizo-partidos";

    const esUltima = ronda.ronda === suizo.ronda_actual;

    ronda.partidos.forEach((partido) => {
      const fila = document.createElement("li");
      fila.className = "suizo-partido";

      const local = document.createElement("span");
      local.className = "calendario-local";
      local.textContent = partido.local;

      const centro = document.createElement("span");
      centro.className = "calendario-vs";

      const visitante = document.createElement("span");
      visitante.className = "calendario-visitante";

      if (partido.visitante === null) {
        centro.textContent = "";
        visitante.textContent = "Pase (1 punto)";
        visitante.classList.add("calendario-descansa");
      } else {
        centro.textContent = "vs";
        visitante.textContent = partido.visitante;

        if (partido.ganador === "") {
          local.classList.add("suizo-empate");
          visitante.classList.add("suizo-empate");
        } else if (partido.ganador !== null) {
          const perdedor = partido.ganador === partido.local ? visitante : local;
          const ganador = partido.ganador === partido.local ? local : visitante;
          ganador.classList.add("llave-ganador");
          perdedor.classList.add("llave-perdedor");
        }
      }

      fila.append(local, centro, visitante);

      /* Resultado: solo el organizador y solo en la última ronda generada */
      if (partido.visitante !== null) {
        fila.appendChild(
          crearAccionesResultado(partido, torneo.puede_generar && esUltima, (lado) =>
            registrarGanador(ronda.ronda, partido.posicion, lado),
          ),
        );
      }

      lista.appendChild(fila);
    });

    bloque.appendChild(lista);
    contenedor.appendChild(bloque);
  });

  calSuizo.appendChild(contenedor);

  /* Tabla de posiciones */
  const tituloTabla = document.createElement("h3");
  tituloTabla.className = "suizo-titulo-tabla";
  tituloTabla.textContent = "Clasificación";
  calSuizo.appendChild(tituloTabla);

  calSuizo.appendChild(crearTablaPosiciones(suizo.clasificacion, COLUMNAS_SUIZO));
}

/* MOSTRAR LA RESPUESTA DEL SERVIDOR */

function mostrarTorneo(datos) {
  torneo = datos.torneo;

  calTitulo.textContent = torneo.nombre;

  calMeta.textContent =
    `${torneo.formato} · ${torneo.disciplina} · ` +
    `${formatearFecha(torneo.fecha_inicio)} al ${formatearFecha(torneo.fecha_fin)} · ` +
    `${torneo.inscriptos} ${torneo.tipo} inscriptos`;

  hayCalendario = torneo.es_liga
    ? datos.jornadas.length > 0
    : datos.rondas.length > 0;

  calAcciones.hidden = !torneo.puede_generar;
  /* "Ida y vuelta" solo tiene sentido en una Liga */
  calOpcionIdaVuelta.hidden = !torneo.es_liga;
  calJornadas.hidden = !torneo.es_liga;
  calPosiciones.hidden = !torneo.es_liga;
  calLlave.hidden = !torneo.es_llave;
  calSuizo.hidden = !torneo.es_suizo;

  const siguiente = datos.suizo && datos.suizo.puede_siguiente;
  calSiguiente.hidden = !siguiente;

  if (siguiente) {
    calSiguiente.querySelector("span").textContent =
      `Generar ronda ${datos.suizo.ronda_actual + 1}`;
  }

  /* Cómo se llama lo que se genera según el formato */
  const cosa = torneo.es_llave ? "llave" : torneo.es_suizo ? "sorteo" : "calendario";
  const textoBoton = calGenerar.querySelector("span");

  if (torneo.es_suizo) {
    textoBoton.textContent = hayCalendario ? "Reiniciar sorteo" : "Sortear primera ronda";
  } else {
    textoBoton.textContent = `${hayCalendario ? "Regenerar" : "Generar"} ${cosa}`;
  }

  if (!torneo.es_liga && !torneo.es_llave && !torneo.es_suizo) {
    mostrarMensaje(
      "El calendario automático está disponible para los formatos Liga, Eliminación Directa y Sistema Suizo.",
      "aviso",
    );
  } else if (!hayCalendario) {
    mostrarMensaje(
      torneo.puede_generar
        ? "Todavía no hay nada generado. Cuando estén todos los inscriptos, genéralo con el botón."
        : "El organizador todavía no generó el calendario de este torneo.",
    );
  } else if (siguiente) {
    mostrarMensaje("La ronda está completa. Ya puedes generar la siguiente.");
  } else {
    mostrarMensaje("");
  }

  dibujarJornadas(datos.jornadas);
  dibujarPosicionesLiga(torneo.es_liga ? datos.posiciones : null);
  dibujarLlave(torneo.es_llave ? datos.rondas : []);
  dibujarSuizo(torneo.es_suizo ? datos.rondas : [], datos.suizo);
}

async function cargarCalendario() {
  if (!Number.isInteger(idTorneo) || idTorneo < 1) {
    calTitulo.textContent = "Calendario";
    mostrarMensaje(
      "Torneo inválido. Entra desde la lista de torneos.",
      "error",
    );
    return;
  }

  try {
    const respuesta = await fetch(
      `../../../php/torneos/listar_calendario.php?id_torneo=${idTorneo}`,
    );
    const datos = await respuesta.json();

    if (!datos.ok) {
      mostrarMensaje(datos.mensaje || "No se pudo cargar el calendario.", "error");
      return;
    }

    mostrarTorneo(datos);
  } catch (error) {
    console.error("No se pudo cargar el calendario:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
  }
}

/* GENERAR (O REGENERAR) EL CALENDARIO */

async function generarCalendario() {
  if (hayCalendario) {
    const confirmar = confirm(
      torneo.es_liga
        ? "Ya hay un calendario. Si lo regeneras, el anterior se reemplaza y se pierden los resultados cargados.\n\n¿Continuar?"
        : "Ya hay un sorteo. Si lo repites, se vuelve a sortear y se pierden todos los resultados cargados.\n\n¿Continuar?",
    );

    if (!confirmar) {
      return;
    }
  }

  calGenerar.disabled = true;
  mostrarMensaje("Generando calendario...");

  try {
    const respuesta = await fetch("../../../php/torneos/generar_calendario.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id_torneo: idTorneo,
        ida_y_vuelta: calIdaVuelta.checked,
      }),
    });
    const datos = await respuesta.json();

    if (!datos.ok) {
      mostrarMensaje(datos.mensaje || "No se pudo generar el calendario.", "error");
      return;
    }

    await cargarCalendario();
    mostrarMensaje(
      torneo.es_suizo
        ? `Primera ronda sorteada: ${datos.encuentros} partidos. El torneo tiene ${datos.rondas} rondas.`
        : datos.rondas !== undefined
          ? `Llave generada: ${datos.rondas} rondas y ${datos.encuentros} partidos.`
          : `Calendario generado: ${datos.jornadas} jornadas y ${datos.encuentros} partidos.`,
      "exito",
    );
  } catch (error) {
    console.error("No se pudo generar el calendario:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
  } finally {
    calGenerar.disabled = false;
  }
}

/* REGISTRAR (O DESHACER) EL GANADOR DE UN PARTIDO DE LA LLAVE */

async function enviarResultado(datosResultado) {
  mostrarMensaje("Guardando resultado...");

  try {
    const respuesta = await fetch("../../../php/torneos/registrar_ganador.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id_torneo: idTorneo, ...datosResultado }),
    });
    const datos = await respuesta.json();

    await cargarCalendario();

    if (!datos.ok) {
      mostrarMensaje(datos.mensaje || "No se pudo guardar el resultado.", "error");
    }
  } catch (error) {
    console.error("No se pudo guardar el resultado:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
  }
}

function registrarGanador(ronda, posicion, lado) {
  return enviarResultado({ ronda, posicion, lado });
}

function registrarResultadoLiga(idEncuentro, lado) {
  return enviarResultado({ id_encuentro: idEncuentro, lado });
}

/* GENERAR LA SIGUIENTE RONDA (SISTEMA SUIZO) */

async function generarSiguienteRonda() {
  calSiguiente.disabled = true;
  mostrarMensaje("Generando ronda...");

  try {
    const respuesta = await fetch("../../../php/torneos/generar_ronda_suiza.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id_torneo: idTorneo }),
    });
    const datos = await respuesta.json();

    await cargarCalendario();

    if (!datos.ok) {
      mostrarMensaje(datos.mensaje || "No se pudo generar la ronda.", "error");
    } else {
      mostrarMensaje(`Ronda ${datos.ronda} de ${datos.total} generada.`, "exito");
    }
  } catch (error) {
    console.error("No se pudo generar la ronda:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
  } finally {
    calSiguiente.disabled = false;
  }
}

calGenerar.addEventListener("click", generarCalendario);
calSiguiente.addEventListener("click", generarSiguienteRonda);

cargarCalendario();
