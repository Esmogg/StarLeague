const formulario = document.getElementById("formCrearTorneo");

const selectCategoria = document.getElementById("categoria");
const selectDeporte = document.getElementById("deporte");
const selectFormato = document.getElementById("formato");

const labelCantidad = document.getElementById("labelCantidad");
const inputCantidad = document.getElementById("cantidadParticipantes");


/*Inicialización*/

// Detectar cuando cambia la disciplina
selectDeporte.addEventListener("change", actualizarTipoParticipante);

// Cargar los formatos disponibles
cargarFormatos();

// Detectar cambios de categoría
selectCategoria.addEventListener("change", actualizarDeportes);

// Detectar envío del formulario
formulario.addEventListener("submit", enviarFormulario);


/*Funciones*/

/*Actualiza el tipo de participante según el deporte seleccionado*/
function actualizarTipoParticipante() {

    const deporteSeleccionado = catalogos.deportes.find((deporte) => {
        return deporte.id === selectDeporte.value;
    });

    if (!deporteSeleccionado) {
        labelCantidad.textContent = "Cantidad";
        inputCantidad.placeholder = "";
        return;
    }

    if (deporteSeleccionado.tipoParticipante === "equipos") {
        labelCantidad.textContent = "Cantidad de equipos";
    }
    else {
        labelCantidad.textContent = "Cantidad de jugadores";
    }
}

/*Carga los formatos del torneo*/
function cargarFormatos() {

    selectFormato.innerHTML = '<option value="">Seleccione un formato</option>';

    catalogos.formatos.forEach((formato) => {
        const option = document.createElement("option");
        option.value = formato.id;
        option.textContent = formato.nombre;
        selectFormato.appendChild(option);
    });
}

/*Actualiza las disciplinas según la categoría elegida*/
function actualizarDeportes() {

    selectDeporte.innerHTML = '<option value="">Seleccione una disciplina:</option>';

    const categoriaSeleccionada = selectCategoria.value;

    const deportes = catalogos.deportes.filter((deporte) => {
        return deporte.categoria === categoriaSeleccionada;
    });

    deportes.forEach((deporte) => {
        const option = document.createElement("option");
        option.value = deporte.id;
        option.textContent = deporte.nombre;
        selectDeporte.appendChild(option);
    });

}

/*Procesa el envío del formulario*/
function enviarFormulario(event) {

    event.preventDefault();
    const datos = Object.fromEntries(new FormData(formulario));
    const deporte = catalogos.deportes.find(
        deporte => deporte.id === datos.deporte
    );
    if (deporte) {
        datos.tipoParticipante = deporte.tipoParticipante;
        if (deporte.tipoParticipante === "equipos") {
            datos.cantidadEquipos = datos.cantidadParticipantes;
        } else {
            datos.cantidadJugadores = datos.cantidadParticipantes;
        }
        delete datos.cantidadParticipantes;
    }
    console.log(datos);
}