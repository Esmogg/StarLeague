/* Modo oscuro: control circular compartido por todas las páginas. */
const themeToggleButton = document.getElementById("themeToggleButton");
const themeToggleIcon = themeToggleButton ? themeToggleButton.querySelector("i") : null;

function aplicarTemaOscuro(oscuro) {
  document.body.classList.toggle("dark-mode", oscuro);
  document.documentElement.classList.toggle("dark-mode", oscuro);
  localStorage.setItem("darkMode", oscuro ? "true" : "false");

  if (themeToggleButton) {
    themeToggleButton.setAttribute("aria-pressed", oscuro ? "true" : "false");
    themeToggleButton.setAttribute("aria-label", oscuro ? "Activar modo claro" : "Activar modo oscuro");
    themeToggleButton.setAttribute("title", oscuro ? "Activar modo claro" : "Activar modo oscuro");
  }
  if (themeToggleIcon) {
    themeToggleIcon.classList.toggle("fa-moon", !oscuro);
    themeToggleIcon.classList.toggle("fa-sun", oscuro);
  }
}

// Aplicar la preferencia guardada en cada página.
const temaOscuroGuardado = localStorage.getItem("darkMode") === "true";
document.body.classList.toggle("dark-mode", temaOscuroGuardado);
document.documentElement.classList.toggle("dark-mode", temaOscuroGuardado);
if (themeToggleButton) {
  aplicarTemaOscuro(temaOscuroGuardado);
  themeToggleButton.addEventListener("click", () => {
    aplicarTemaOscuro(!document.body.classList.contains("dark-mode"));
  });
}

/* Menu Lateral */
/* Cada referencia se valida antes de usarse: si una pagina no tiene
   el boton de menu, la barra lateral o el overlay (como pasaba en
   login.html y register.html), ya no se rompe el script completo. */
const menuBtn = document.getElementById("menuBtn");
const aside = document.querySelector(".barra-lateral");
const menuOverlay = document.getElementById("menuOverlay");

function abrirCerrarMenu() {
  if (aside) aside.classList.toggle("abierto");
  if (menuOverlay) menuOverlay.classList.toggle("activo");
  document.body.classList.toggle("menu-abierto");
  document.documentElement.classList.toggle("menu-abierto");
}

if (menuBtn) {
  menuBtn.addEventListener("click", abrirCerrarMenu);
}

if (menuOverlay) {
  menuOverlay.addEventListener("click", () => {
    if (aside) aside.classList.remove("abierto");
    menuOverlay.classList.remove("activo");
    document.body.classList.remove("menu-abierto");
    document.documentElement.classList.remove("menu-abierto");
  });
}

/* Boton de subida de la pagina */
const btnSubir = document.getElementById("btnSubir");

if (btnSubir) {
  window.addEventListener("scroll", () => {
    if (window.scrollY > 65) {
      btnSubir.classList.add("visible");
    } else {
      btnSubir.classList.remove("visible");
    }
  });

  btnSubir.addEventListener("click", () => {
    window.scrollTo({ top: 0 });
  });
}

/* Confirmacion de cierre de sesion */
/* El item "Cerrar sesión" del menu lateral (data-abrir-cerrar-sesion) abre la
   ventana de confirmacion. "No", el fondo oscuro o Escape la cierran. */
const overlayCerrarSesion = document.getElementById("overlayCerrarSesion");
const btnCerrarNo = document.getElementById("btnCerrarNo");

function cerrarVentanaCerrarSesion() {
  if (overlayCerrarSesion) overlayCerrarSesion.hidden = true;
}

if (overlayCerrarSesion) {
  document.querySelectorAll("[data-abrir-cerrar-sesion]").forEach((enlace) => {
    enlace.addEventListener("click", (evento) => {
      evento.preventDefault();
      overlayCerrarSesion.hidden = false;
      if (btnCerrarNo) btnCerrarNo.focus();
    });
  });

  if (btnCerrarNo) btnCerrarNo.addEventListener("click", cerrarVentanaCerrarSesion);

  overlayCerrarSesion.addEventListener("click", (evento) => {
    if (evento.target === overlayCerrarSesion) cerrarVentanaCerrarSesion();
  });

  document.addEventListener("keydown", (evento) => {
    if (evento.key === "Escape" && !overlayCerrarSesion.hidden) {
      cerrarVentanaCerrarSesion();
    }
  });
}

/* Cerrar sesion */
/* Cualquier elemento con data-cerrar-sesion (el boton "Si" de la ventana de
   confirmacion) pide al servidor que destruya la sesion y vuelve
   a la pagina de inicio sin login. Las rutas se calculan a partir de la
   ubicacion de este script, asi funcionan desde cualquier carpeta. */
const rutaScript = document.currentScript ? document.currentScript.src : "";

document.querySelectorAll("[data-cerrar-sesion]").forEach((boton) => {
  boton.addEventListener("click", async (evento) => {
    evento.preventDefault();

    if (!rutaScript) {
      return;
    }

    try {
      const respuesta = await fetch(
        new URL("../php/auth/logout.php", rutaScript),
        { method: "POST", credentials: "same-origin" },
      );

      const datos = await respuesta.json();

      if (!datos.ok) {
        throw new Error(datos.mensaje || "No se pudo cerrar la sesión.");
      }

      window.location.href = new URL("../index.html", rutaScript).href;
    } catch (error) {
      console.error("Error al cerrar sesión:", error);
      alert("No se pudo cerrar la sesión. Inténtalo de nuevo.");
    }
  });
});

/* Proteccion CSRF */
/* Pide al servidor el token de la sesion y lo agrega como cabecera
   X-CSRF-Token a todo fetch que modifica datos (POST, PUT, DELETE...)
   hacia el mismo sitio. Los formularios nativos marcados con data-csrf
   reciben el token en un campo oculto _csrf. */
const fetchOriginal = window.fetch.bind(window);
let tokenCsrf = null;

async function obtenerTokenCsrf() {
  if (!tokenCsrf) {
    const respuesta = await fetchOriginal(
      new URL("../php/auth/csrf.php", rutaScript),
      { credentials: "same-origin" },
    );
    const datos = await respuesta.json();
    tokenCsrf = datos.token;
  }
  return tokenCsrf;
}

window.fetch = async (entrada, opciones = {}) => {
  const esRequest = entrada instanceof Request;
  const metodo = (opciones.method || (esRequest ? entrada.method : "GET")).toUpperCase();
  const url = new URL(esRequest ? entrada.url : entrada, window.location.href);

  if (!["GET", "HEAD", "OPTIONS"].includes(metodo) && url.origin === window.location.origin) {
    const cabeceras = new Headers(opciones.headers || (esRequest ? entrada.headers : undefined));
    cabeceras.set("X-CSRF-Token", await obtenerTokenCsrf());
    opciones = { ...opciones, headers: cabeceras };
  }

  return fetchOriginal(entrada, opciones);
};

document.querySelectorAll("form[data-csrf]").forEach((formulario) => {
  const campo = document.createElement("input");
  campo.type = "hidden";
  campo.name = "_csrf";
  formulario.appendChild(campo);

  obtenerTokenCsrf()
    .then((token) => { campo.value = token; })
    .catch((error) => console.error("No se pudo obtener el token CSRF:", error));

  formulario.addEventListener("submit", (evento) => {
    if (!campo.value) {
      evento.preventDefault();
    }
  });
});


/* Avisos dentro de la página: reemplaza las ventanas alert() del navegador. */
(() => {
  let temporizadorAviso;
  function mostrarAvisoPagina(texto) {
    let aviso = document.getElementById("avisoPagina");
    if (!aviso) {
      aviso = document.createElement("div");
      aviso.id = "avisoPagina";
      aviso.className = "aviso-pagina";
      aviso.setAttribute("role", "status");
      aviso.setAttribute("aria-live", "polite");
      document.body.appendChild(aviso);
    }
    const contenido = String(texto ?? "");
    aviso.textContent = contenido;
    const pareceExito = /éxito|exito|correctamente|copiado|sesión iniciada|inscripci[oó]n realizada|se ha unido|guardado/i.test(contenido);
    aviso.classList.toggle("exito", pareceExito);
    aviso.classList.toggle("error", !pareceExito);
    aviso.classList.add("visible");
    window.clearTimeout(temporizadorAviso);
    temporizadorAviso = window.setTimeout(() => {
      aviso.classList.remove("visible");
    }, 4500);
  }

  // Permite que el resto de los scripts sigan usando alert(), pero sin
  // mostrar el cuadro nativo "(nombre de la página) dice".
  window.alert = mostrarAvisoPagina;
})();

/* Menús por rol */
/* Los elementos con data-roles="organizador admin" empiezan ocultos y solo se muestran si el
   usuario tiene alguno de esos roles. Es comodidad de la interfaz: cada acción del servidor
   vuelve a comprobar el permiso, así que ocultar el menú no es lo que protege. Si algo falla,
   el menú queda oculto. */
const elementosPorRol = document.querySelectorAll("[data-roles]");

if (elementosPorRol.length > 0 && rutaScript) {
  fetchOriginal(new URL("../php/auth/rol.php", rutaScript), { credentials: "same-origin" })
    .then((respuesta) => respuesta.json())
    .then((datos) => {
      const rol = datos && datos.ok ? datos.rol : null;

      elementosPorRol.forEach((elemento) => {
        const permitidos = (elemento.dataset.roles || "").split(/\s+/);
        elemento.hidden = !(rol && permitidos.includes(rol));
      });
    })
    .catch((error) => console.error("No se pudo obtener el rol:", error));
}

/* Nombres largos: al pasar el mouse por la tarjeta, el nombre se desliza de izquierda a derecha
   y vuelve, mostrando el texto completo mientras el mouse esté encima. Sin hover, se ve cortado con "..." */
(() => {
  const TARJETAS = {
    ".tarjeta-equipo": ".tarjeta-equipo-nombre",
    ".tarjeta-torneo": ".torneo-nombre",
    ".usuario-tarjeta": ".usuario-nombre",
  };
  const SELECTOR_TARJETAS = Object.keys(TARJETAS).join(",");
  const PIXELES_POR_SEGUNDO = 90;
  const MARGEN = 4; /* margen extra por la sombra del texto */

  const sinAnimaciones = window.matchMedia("(prefers-reduced-motion: reduce)");
  let nombreActivo = null;

  function detenerDeslizamiento() {
    if (!nombreActivo) return;
    nombreActivo.classList.remove("nombre-deslizante");
    nombreActivo.style.removeProperty("--desplazamiento");
    nombreActivo.style.removeProperty("--duracion");
    nombreActivo = null;
  }

  function iniciarDeslizamiento(tarjeta) {
    if (sinAnimaciones.matches) return;

    const selectorNombre = Object.entries(TARJETAS).find(([selector]) => tarjeta.matches(selector))?.[1];
    const nombre = selectorNombre ? tarjeta.querySelector(selectorNombre) : null;
    if (!nombre) return;

    /* Solo se mueve si el nombre no entra completo (si está cortado con "...") */
    const sobrante = nombre.scrollWidth - nombre.clientWidth;
    if (sobrante <= 0) return;

    const recorrido = sobrante + MARGEN;
    /* El movimiento ocupa el 85% de cada tramo; el resto son pausas breves en los extremos */
    const duracion = Math.max(1.5, recorrido / PIXELES_POR_SEGUNDO / 0.85);

    nombre.style.setProperty("--desplazamiento", `${recorrido}px`);
    nombre.style.setProperty("--duracion", `${duracion.toFixed(2)}s`);
    nombre.classList.add("nombre-deslizante");
    nombreActivo = nombre;
  }

  document.addEventListener("mouseover", (evento) => {
    const tarjeta = evento.target.closest?.(SELECTOR_TARJETAS);
    if (!tarjeta || tarjeta.contains(evento.relatedTarget)) return;
    detenerDeslizamiento();
    iniciarDeslizamiento(tarjeta);
  });

  document.addEventListener("mouseout", (evento) => {
    const tarjeta = evento.target.closest?.(SELECTOR_TARJETAS);
    if (!tarjeta || tarjeta.contains(evento.relatedTarget)) return;
    detenerDeslizamiento();
  });
})();
