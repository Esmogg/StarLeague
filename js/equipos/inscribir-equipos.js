document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formInscribirEquipo");
  const selectEquipo = document.getElementById("id_equipo");
  const selectJugador = document.getElementById("id_jugador");
  const mensaje = document.getElementById("mensajeInscripcion");

  if (!form || !selectEquipo || !selectJugador) {
    return;
  }

  function llenarSelect(select, textoInicial, items) {
    select.innerHTML = "";

    const inicial = document.createElement("option");
    inicial.value = "";
    inicial.textContent = textoInicial;
    select.appendChild(inicial);

    items.forEach((item) => {
      const opcion = document.createElement("option");
      opcion.value = item.id;
      opcion.textContent = item.nombre;
      select.appendChild(opcion);
    });
  }

  function mostrar(texto, ok) {
    mensaje.textContent = texto;
    mensaje.classList.remove("error", "exito");
    mensaje.classList.add(ok ? "exito" : "error");
    mensaje.style.display = "block";
  }

  /* 1. Equipos (listar_equipos.php) */
  fetch("../../../php/equipos/listar_equipos.php")
    .then((res) => res.json())
    .then((datos) => {
      if (!datos.ok) {
        throw new Error(datos.mensaje);
      }
      llenarSelect(
        selectEquipo,
        "-- Seleccione un equipo --",
        datos.equipos.map((e) => ({
          id: e.id_equipo,
          nombre: `${e.nombre} (${e.disciplina || e.deporte || "—"})`,
        })),
      );
    })
    .catch(() => {
      llenarSelect(selectEquipo, "No se pudieron cargar los equipos", []);
    });

  /* 2. Jugadores (obtener_candidatos.php) */
  fetch("../../../php/torneos/obtener_candidatos.php?tipo=usuario")
    .then((res) => res.json())
    .then((jugadores) => {
      llenarSelect(selectJugador, "-- Seleccione un jugador --", jugadores);
    })
    .catch(() => {
      llenarSelect(selectJugador, "No se pudieron cargar los jugadores", []);
    });

  /* 3. Enviar */
  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();

    try {
      const respuesta = await fetch("../../../php/equipos/unirse_equipo.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id_equipo: Number(selectEquipo.value),
          id_usuario: Number(selectJugador.value),
        }),
      });

      const resultado = await respuesta.json();
      mostrar(resultado.mensaje || "No se pudo completar la inscripción.", resultado.ok);

      if (resultado.ok) {
        selectJugador.value = "";
      }
    } catch (error) {
      console.error("Error al inscribir en el equipo:", error);
      mostrar("No se pudo conectar con el servidor.", false);
    }
  });
});
