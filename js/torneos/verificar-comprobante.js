/* Verificación pública de un comprobante de inscripción firmado */

const formulario = document.getElementById("formVerificar");
const resultado = document.getElementById("resultadoVerificacion");

const ETIQUETAS = [
  ["torneo", "Torneo"],
  ["tipo", "Tipo de inscripción"],
  ["participante", "Participante"],
  ["fecha", "Fecha de emisión"],
  ["id", "Código del comprobante"],
];

function mostrar(clase, texto, datos) {
  resultado.className = clase;
  resultado.hidden = false;
  resultado.textContent = texto;

  if (datos) {
    const lista = document.createElement("dl");
    ETIQUETAS.forEach(([clave, etiqueta]) => {
      const dt = document.createElement("dt");
      const dd = document.createElement("dd");
      dt.textContent = etiqueta;
      dd.textContent = String(datos[clave] ?? "");
      lista.append(dt, dd);
    });
    resultado.appendChild(lista);
  }
}

formulario.addEventListener("submit", async (evento) => {
  evento.preventDefault();

  try {
    const respuesta = await fetch("../../../php/torneos/verificar_comprobante.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ comprobante: formulario.comprobante.value }),
    });
    const datos = await respuesta.json();

    if (!datos.ok) {
      mostrar("invalido", datos.mensaje || "No se pudo verificar el comprobante.");
    } else if (datos.valido) {
      mostrar("valido", "✔ " + datos.mensaje, datos.datos);
    } else {
      mostrar("invalido", "✘ " + datos.mensaje);
    }
  } catch (error) {
    console.error("Error al verificar:", error);
    mostrar("invalido", "No se pudo verificar el comprobante. Inténtalo de nuevo.");
  }
});
