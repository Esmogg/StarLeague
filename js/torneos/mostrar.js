document.addEventListener("DOMContentLoaded", () => {
  const selectTorneo = document.getElementById("id_torneo");
  const selectParticipante = document.getElementById("id_participante");
  const labelParticipante = document.getElementById("label_participante");
  const inputTipo = document.getElementById("tipo_inscripcion");

  let listaTorneos = [];

  // Envío por fetch (el servidor responde JSON); el token CSRF lo agrega script.js
  const formInscripcion = document.querySelector("form[action$='procesar_inscripcion.php']");

  // Comprobante firmado: se muestra para copiarlo o descargarlo
  const bloqueComprobante = document.getElementById("comprobante");
  const textoComprobante = document.getElementById("textoComprobante");

  function mostrarComprobante(comprobante) {
    textoComprobante.value = comprobante;
    bloqueComprobante.hidden = false;
    bloqueComprobante.scrollIntoView({ behavior: "smooth" });
  }

  document.getElementById("copiarComprobante")?.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(textoComprobante.value);
    } catch (error) {
      textoComprobante.select();
      document.execCommand("copy");
    }
    alert("Comprobante copiado.");
  });

  document.getElementById("descargarComprobante")?.addEventListener("click", () => {
    const archivo = new Blob([textoComprobante.value], { type: "text/plain" });
    const enlace = document.createElement("a");
    enlace.href = URL.createObjectURL(archivo);
    enlace.download = "comprobante-starleague.txt";
    enlace.click();
    URL.revokeObjectURL(enlace.href);
  });

  if (formInscripcion) {
    formInscripcion.addEventListener("submit", async (evento) => {
      evento.preventDefault();

      try {
        const respuesta = await fetch("../../../php/torneos/procesar_inscripcion.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(Object.fromEntries(new FormData(formInscripcion))),
        });
        const resultado = await respuesta.json();

        alert(resultado.mensaje || "No se pudo completar la inscripción.");

        if (resultado.ok && resultado.comprobante) {
          mostrarComprobante(resultado.comprobante);
          formInscripcion.reset();
          selectParticipante.disabled = true;
          selectParticipante.innerHTML =
            '<option value="">Primero seleccione un torneo</option>';
        } else if (resultado.ok) {
          window.location.reload();
        }
      } catch (error) {
        console.error("Error al inscribir:", error);
        alert("Error al registrar inscripción.");
      }
    });
  }

  if (selectTorneo && selectParticipante) {
    // 1. Cargar torneos
    fetch("../../../php/torneos/obtener_torneos.php")
      .then((res) => res.json())
      .then((torneos) => {
        listaTorneos = torneos;
        selectTorneo.innerHTML =
          '<option value="">-- Seleccione un torneo --</option>';
        torneos.forEach((t) => {
          const opt = document.createElement("option");
          opt.value = t.id_torneo;
          opt.textContent = `${t.nombre} (${t.disciplina})`;
          selectTorneo.appendChild(opt);
        });
      });

    // 2. Al cambiar de torneo, cargar Usuarios o Equipos
    selectTorneo.addEventListener("change", (e) => {
      const torneoId = e.target.value;
      const torneo = listaTorneos.find((t) => t.id_torneo == torneoId);

      if (!torneo) {
        selectParticipante.disabled = true;
        selectParticipante.innerHTML =
          '<option value="">Primero seleccione un torneo</option>';
        return;
      }

      const esEquipo = torneo.es_equipo;
      const tipo = esEquipo ? "equipo" : "usuario";

      inputTipo.value = tipo;
      labelParticipante.textContent = esEquipo
        ? "Seleccionar Equipo a Inscribir:"
        : "Seleccionar Usuario a Inscribir:";
      selectParticipante.disabled = false;
      selectParticipante.innerHTML = '<option value="">Cargando...</option>';

      fetch(`../../../php/torneos/obtener_candidatos.php?tipo=${tipo}`)
        .then((res) => res.json())
        .then((data) => {
          selectParticipante.innerHTML = `<option value="">-- Seleccione ${tipo} --</option>`;
          data.forEach((item) => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nombre;
            selectParticipante.appendChild(opt);
          });
        });
    });
  }
});
