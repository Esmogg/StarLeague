/* ROLES DE USUARIOS (solo administradores) */

const rolesMensaje = document.getElementById("rolesMensaje");
const rolesContenido = document.getElementById("rolesContenido");
const rolesBuscar = document.getElementById("rolesBuscar");
const rolesCuerpo = document.getElementById("rolesCuerpo");
const rolesVacio = document.getElementById("rolesVacio");

const ETIQUETAS_ROL = {
  jugador: "Jugador",
  organizador: "Organizador",
  admin: "Administrador",
};

let usuarios = [];
let idPropio = 0;

function mostrarMensaje(texto, tipo = "") {
  rolesMensaje.textContent = texto;
  rolesMensaje.className = `roles-mensaje ${tipo}`.trim();
}

/* DIBUJAR LA TABLA */

function mostrarUsuarios() {
  const texto = rolesBuscar.value.trim().toLowerCase();

  const lista = usuarios.filter(
    (u) =>
      !texto ||
      u.nombre.toLowerCase().includes(texto) ||
      u.email.toLowerCase().includes(texto),
  );

  rolesCuerpo.innerHTML = "";
  rolesVacio.hidden = lista.length > 0;

  lista.forEach((usuario) => {
    const fila = document.createElement("tr");

    const celdaNombre = document.createElement("td");
    /* textContent: un nombre con HTML se muestra como texto y no se ejecuta */
    celdaNombre.textContent = usuario.nombre;
    celdaNombre.className = "roles-nombre";

    const celdaEmail = document.createElement("td");
    celdaEmail.textContent = usuario.email;

    const celdaRol = document.createElement("td");
    const selector = document.createElement("select");
    selector.setAttribute("aria-label", `Rol de ${usuario.nombre}`);

    Object.entries(ETIQUETAS_ROL).forEach(([valor, etiqueta]) => {
      const opcion = document.createElement("option");
      opcion.value = valor;
      opcion.textContent = etiqueta;
      opcion.selected = valor === usuario.rol;
      selector.appendChild(opcion);
    });

    const celdaEstado = document.createElement("td");
    celdaEstado.textContent = usuario.bloqueado ? "Bloqueado" : "Activo";
    celdaEstado.className = usuario.bloqueado
      ? "roles-estado bloqueado"
      : "roles-estado";

    const celdaAccion = document.createElement("td");
    celdaAccion.className = "roles-acciones";
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "roles-guardar";
    boton.textContent = "Guardar";
    boton.disabled = true;

    if (usuario.id === idPropio) {
      /* Nadie puede cambiarse el rol a sí mismo: así un administrador nunca se queda sin acceso */
      selector.disabled = true;
      celdaNombre.textContent = `${usuario.nombre} (tú)`;
      boton.hidden = true;
    } else {
      selector.addEventListener("change", () => {
        boton.disabled = selector.value === usuario.rol;
      });
      boton.addEventListener("click", () =>
        guardarRol(usuario, selector.value, boton),
      );
    }

    celdaAccion.appendChild(boton);

    /* Bloquear y eliminar: no sobre uno mismo ni sobre otro administrador
       (el servidor también lo rechaza, esto solo evita botones inútiles) */
    if (usuario.id !== idPropio && usuario.rol !== "admin") {
      const botonBloqueo = document.createElement("button");
      botonBloqueo.type = "button";
      botonBloqueo.className = "roles-guardar";
      botonBloqueo.textContent = usuario.bloqueado ? "Desbloquear" : "Bloquear";
      botonBloqueo.addEventListener("click", () =>
        cambiarBloqueo(usuario, !usuario.bloqueado, botonBloqueo),
      );

      const botonEliminar = document.createElement("button");
      botonEliminar.type = "button";
      botonEliminar.className = "roles-guardar roles-peligro";
      botonEliminar.textContent = "Eliminar";
      botonEliminar.addEventListener("click", () =>
        eliminarUsuario(usuario, botonEliminar),
      );

      celdaAccion.append(botonBloqueo, botonEliminar);
    }

    celdaRol.appendChild(selector);
    fila.append(celdaNombre, celdaEmail, celdaRol, celdaEstado, celdaAccion);
    rolesCuerpo.appendChild(fila);
  });
}

/* CARGAR LOS USUARIOS */

async function cargarUsuarios() {
  try {
    const respuesta = await fetch("../../../php/admin/listar_usuarios.php");
    const datos = await respuesta.json();

    if (!datos.ok) {
      rolesContenido.hidden = true;
      mostrarMensaje(
        datos.mensaje || "No se pudo cargar la lista de usuarios.",
        "error",
      );
      return;
    }

    usuarios = datos.usuarios;
    idPropio = datos.yo;
    rolesContenido.hidden = false;
    mostrarUsuarios();
  } catch (error) {
    console.error("No se pudo cargar la lista de usuarios:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
  }
}

/* CAMBIAR UN ROL */

async function guardarRol(usuario, rolNuevo, boton) {
  const accion = rolNuevo === "jugador" ? "revocar los permisos de" : "darle el rol de " + ETIQUETAS_ROL[rolNuevo] + " a";

  if (!confirm(`¿Seguro que quieres ${accion} ${usuario.nombre}?`)) {
    return;
  }

  boton.disabled = true;
  mostrarMensaje("Guardando...");

  try {
    const respuesta = await fetch("../../../php/admin/cambiar_rol.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id_usuario: usuario.id, rol: rolNuevo }),
    });
    const datos = await respuesta.json();

    await cargarUsuarios();

    mostrarMensaje(
      datos.ok
        ? `${usuario.nombre} ahora es ${ETIQUETAS_ROL[rolNuevo]}.`
        : datos.mensaje || "No se pudo cambiar el rol.",
      datos.ok ? "exito" : "error",
    );
  } catch (error) {
    console.error("No se pudo cambiar el rol:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
    boton.disabled = false;
  }
}

/* BLOQUEAR / DESBLOQUEAR UNA CUENTA */

async function cambiarBloqueo(usuario, bloquear, boton) {
  const pregunta = bloquear
    ? `¿Bloquear a ${usuario.nombre}? No podrá iniciar sesión y su sesión abierta se cerrará.`
    : `¿Desbloquear a ${usuario.nombre}?`;
  if (!confirm(pregunta)) return;

  boton.disabled = true;
  mostrarMensaje("Guardando...");

  try {
    const respuesta = await fetch("../../../php/admin/bloquear_usuario.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id_usuario: usuario.id, bloqueado: bloquear }),
    });
    const datos = await respuesta.json();

    await cargarUsuarios();
    mostrarMensaje(
      datos.mensaje || "No se pudo cambiar el estado de la cuenta.",
      datos.ok ? "exito" : "error",
    );
  } catch (error) {
    console.error("No se pudo cambiar el bloqueo:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
    boton.disabled = false;
  }
}

/* ELIMINAR UNA CUENTA */

async function eliminarUsuario(usuario, boton) {
  if (
    !confirm(
      `¿Eliminar definitivamente la cuenta de ${usuario.nombre}? Esta acción no se puede deshacer.`,
    )
  ) {
    return;
  }

  boton.disabled = true;
  mostrarMensaje("Eliminando...");

  try {
    const respuesta = await fetch("../../../php/admin/eliminar_usuario.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id_usuario: usuario.id }),
    });
    const datos = await respuesta.json();

    await cargarUsuarios();
    mostrarMensaje(
      datos.mensaje || "No se pudo eliminar el usuario.",
      datos.ok ? "exito" : "error",
    );
  } catch (error) {
    console.error("No se pudo eliminar el usuario:", error);
    mostrarMensaje("No se pudo conectar con el servidor.", "error");
    boton.disabled = false;
  }
}

rolesBuscar.addEventListener("input", mostrarUsuarios);

cargarUsuarios();
