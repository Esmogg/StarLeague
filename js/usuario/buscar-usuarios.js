/* BUSCAR USUARIOS: búsqueda pública y perfil con foto, banner, equipos e inscripciones */

const buscarUsuario = document.getElementById("buscarUsuario");
const gridUsuarios = document.getElementById("gridUsuarios");
const estadoUsuarios = document.getElementById("estadoUsuarios");
const modalPerfil = document.getElementById("modalPerfil");
const contenidoPerfil = document.getElementById("contenidoPerfil");
const cerrarPerfil = document.getElementById("cerrarPerfil");

const FOTO_POR_DEFECTO = "images/user/blue_user-pfp-400x400.jpeg";
const BANNER_POR_DEFECTO = "images/user/blue_user-banner-default.jpeg";

const ETIQUETA_ROL = {
  jugador: "Jugador",
  organizador: "Organizador",
  admin: "Administrador",
};

/* Carpeta del proyecto: la página está en /html/login/usuario/ o /html/nologin/usuario/ */
const raizProyecto = window.location.pathname.replace(
  /\/html\/(?:login|nologin)\/usuario\/[^/]*$/,
  "",
);

function urlImagen(ruta, porDefecto) {
  return `${raizProyecto}/${ruta || porDefecto}`.replace(/\/{2,}/g, "/");
}

function crearImagen(ruta, porDefecto, alt, clase) {
  const imagen = document.createElement("img");
  imagen.className = clase;
  imagen.alt = alt;
  imagen.loading = "lazy";
  imagen.src = urlImagen(ruta, porDefecto);
  /* Si el archivo ya no existe, se usa la imagen por defecto */
  imagen.addEventListener(
    "error",
    () => {
      imagen.src = urlImagen(null, porDefecto);
    },
    { once: true },
  );
  return imagen;
}

function formatearFecha(fecha) {
  const partes = String(fecha ?? "").split("-");
  return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : "";
}

function crearInsignia(rol) {
  const insignia = document.createElement("span");
  insignia.className = `usuario-rol usuario-rol-${rol}`;
  insignia.textContent = ETIQUETA_ROL[rol] ?? rol;
  return insignia;
}

/* LISTA DE RESULTADOS */

function dibujarUsuarios(usuarios, hayMas) {
  gridUsuarios.innerHTML = "";

  if (usuarios.length === 0) {
    estadoUsuarios.textContent = "No se encontraron usuarios con ese nombre.";
    estadoUsuarios.hidden = false;
    return;
  }

  estadoUsuarios.hidden = !hayMas;
  estadoUsuarios.textContent = hayMas
    ? "Se muestran los primeros 30. Escribe más letras para afinar la búsqueda."
    : "";

  usuarios.forEach((usuario) => {
    const tarjeta = document.createElement("button");
    tarjeta.type = "button";
    tarjeta.className = "usuario-tarjeta";
    tarjeta.setAttribute("aria-label", `Ver el perfil de ${usuario.nombre}`);

    const nombre = document.createElement("span");
    nombre.className = "usuario-nombre";
    /* textContent: un nombre con HTML se muestra como texto y no se ejecuta */
    nombre.textContent = usuario.nombre;

    tarjeta.append(
      crearImagen(usuario.foto_perfil, FOTO_POR_DEFECTO, "", "usuario-foto"),
      nombre,
      crearInsignia(usuario.rol),
    );
    tarjeta.addEventListener("click", () => abrirPerfil(usuario.id));
    gridUsuarios.appendChild(tarjeta);
  });
}

let temporizador = null;
let busquedaActual = 0;

async function buscar() {
  const numero = ++busquedaActual;

  try {
    const respuesta = await fetch(
      `../../../php/usuarios/buscar_usuarios.php?q=${encodeURIComponent(buscarUsuario.value.trim())}`,
    );
    const datos = await respuesta.json();

    /* Si mientras tanto se escribió otra cosa, esta respuesta ya no sirve */
    if (numero !== busquedaActual) return;

    if (!datos.ok) {
      gridUsuarios.innerHTML = "";
      estadoUsuarios.textContent = datos.mensaje || "No se pudo hacer la búsqueda.";
      estadoUsuarios.hidden = false;
      return;
    }

    dibujarUsuarios(datos.usuarios, datos.hay_mas);
  } catch (error) {
    console.error("No se pudo buscar usuarios:", error);
    estadoUsuarios.textContent = "No se pudo conectar con el servidor.";
    estadoUsuarios.hidden = false;
  }
}

buscarUsuario.addEventListener("input", () => {
  clearTimeout(temporizador);
  temporizador = setTimeout(buscar, 250);
});

/* PERFIL PÚBLICO */

function crearSeccion(titulo, vacio, elementos) {
  const seccion = document.createElement("section");
  seccion.className = "perfil-publico-seccion";

  const encabezado = document.createElement("h3");
  encabezado.textContent = titulo;
  seccion.appendChild(encabezado);

  if (elementos.length === 0) {
    const texto = document.createElement("p");
    texto.className = "perfil-publico-vacio";
    texto.textContent = vacio;
    seccion.appendChild(texto);
    return seccion;
  }

  const lista = document.createElement("ul");
  lista.className = "perfil-publico-lista";
  elementos.forEach((elemento) => lista.appendChild(elemento));
  seccion.appendChild(lista);
  return seccion;
}

function crearItemTorneo(torneo, detalleExtra) {
  const item = document.createElement("li");

  const enlace = document.createElement("a");
  enlace.href = `../torneos/calendario.html?id_torneo=${encodeURIComponent(torneo.id_torneo)}`;
  enlace.textContent = torneo.nombre;

  const detalle = document.createElement("span");
  detalle.className = "perfil-publico-detalle";
  detalle.textContent =
    `${torneo.disciplina} · ${torneo.formato.replace("Eliminacion", "Eliminación")} · ` +
    `${formatearFecha(torneo.fecha_inicio)} al ${formatearFecha(torneo.fecha_fin)}` +
    (detalleExtra ? ` · ${detalleExtra}` : "");

  item.append(enlace, detalle);
  return item;
}

function dibujarPerfil(datos) {
  const { usuario, equipos, inscripciones, organizados } = datos;
  contenidoPerfil.innerHTML = "";

  const cabecera = document.createElement("div");
  cabecera.className = "perfil-publico-cabecera";

  cabecera.appendChild(
    crearImagen(usuario.banner_perfil, BANNER_POR_DEFECTO, "", "perfil-publico-banner"),
  );

  const identidad = document.createElement("div");
  identidad.className = "perfil-publico-identidad";

  const nombre = document.createElement("h2");
  nombre.id = "tituloPerfil";
  nombre.textContent = usuario.nombre;

  const desde = document.createElement("p");
  desde.className = "perfil-publico-desde";
  desde.textContent = usuario.fecha_creacion
    ? `Miembro desde el ${formatearFecha(usuario.fecha_creacion)}`
    : "";

  const textos = document.createElement("div");
  textos.append(nombre, crearInsignia(usuario.rol), desde);

  identidad.append(
    crearImagen(usuario.foto_perfil, FOTO_POR_DEFECTO, `Foto de ${usuario.nombre}`, "perfil-publico-foto"),
    textos,
  );
  cabecera.appendChild(identidad);
  contenidoPerfil.appendChild(cabecera);

  contenidoPerfil.appendChild(
    crearSeccion(
      "Equipos",
      "No forma parte de ningún equipo.",
      equipos.map((equipo) => {
        const item = document.createElement("li");
        const texto = document.createElement("span");
        texto.textContent = equipo.nombre;
        const detalle = document.createElement("span");
        detalle.className = "perfil-publico-detalle";
        detalle.textContent = equipo.disciplina;
        item.append(texto, detalle);
        return item;
      }),
    ),
  );

  contenidoPerfil.appendChild(
    crearSeccion(
      "Inscripciones",
      "No está inscripto en ningún torneo.",
      inscripciones.map((t) =>
        crearItemTorneo(t, t.via === "equipo" ? `con el equipo ${t.equipo}` : "individual"),
      ),
    ),
  );

  if (organizados.length > 0) {
    contenidoPerfil.appendChild(
      crearSeccion("Torneos organizados", "", organizados.map((t) => crearItemTorneo(t, ""))),
    );
  }
}

let elementoPrevio = null;

async function abrirPerfil(id) {
  elementoPrevio = document.activeElement;
  contenidoPerfil.innerHTML = "";
  const cargando = document.createElement("p");
  cargando.className = "perfil-publico-vacio";
  cargando.textContent = "Cargando perfil...";
  contenidoPerfil.appendChild(cargando);

  modalPerfil.hidden = false;
  document.body.classList.add("modal-abierto");
  document.documentElement.classList.add("modal-abierto");
  cerrarPerfil.focus();

  try {
    const respuesta = await fetch(
      `../../../php/usuarios/perfil_publico.php?id=${encodeURIComponent(id)}`,
    );
    const datos = await respuesta.json();

    if (!datos.ok) {
      cargando.textContent = datos.mensaje || "No se pudo cargar el perfil.";
      return;
    }

    dibujarPerfil(datos);
  } catch (error) {
    console.error("No se pudo cargar el perfil:", error);
    cargando.textContent = "No se pudo conectar con el servidor.";
  }
}

function cerrarModalPerfil() {
  modalPerfil.hidden = true;
  document.body.classList.remove("modal-abierto");
  document.documentElement.classList.remove("modal-abierto");

  if (elementoPrevio && typeof elementoPrevio.focus === "function") {
    elementoPrevio.focus();
  }
}

cerrarPerfil.addEventListener("click", cerrarModalPerfil);
modalPerfil.addEventListener("click", (evento) => {
  if (evento.target === modalPerfil) cerrarModalPerfil();
});
document.addEventListener("keydown", (evento) => {
  if (evento.key === "Escape" && !modalPerfil.hidden) cerrarModalPerfil();
});

/* INICIO: abre un perfil directo si la dirección trae ?id=N, y muestra la lista */

buscar();

const idDirecto = Number.parseInt(
  new URLSearchParams(window.location.search).get("id") ?? "",
  10,
);

if (Number.isInteger(idDirecto) && idDirecto > 0) {
  abrirPerfil(idDirecto);
}
