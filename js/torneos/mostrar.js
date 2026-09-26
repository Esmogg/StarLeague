document.addEventListener("DOMContentLoaded", () => {
    const selectTorneo = document.getElementById("id_torneo");
    const selectParticipante = document.getElementById("id_participante");
    const labelParticipante = document.getElementById("label_participante");
    const inputTipo = document.getElementById("tipo_inscripcion");

    let listaTorneos = [];

    if (selectTorneo && selectParticipante) {
        // 1. Cargar torneos
        fetch('../../../php/torneos/obtener_torneos.php')
            .then(res => res.json())
            .then(torneos => {
                listaTorneos = torneos;
                selectTorneo.innerHTML = '<option value="">-- Seleccione un torneo --</option>';
                torneos.forEach(t => {
                    const opt = document.createElement("option");
                    opt.value = t.id_torneo;
                    opt.textContent = `${t.nombre} (${t.disciplina})`;
                    selectTorneo.appendChild(opt);
                });
            });

        // 2. Al cambiar de torneo, cargar Usuarios o Equipos
        selectTorneo.addEventListener("change", (e) => {
            const torneoId = e.target.value;
            const torneo = listaTorneos.find(t => t.id_torneo == torneoId);

            if (!torneo) {
                selectParticipante.disabled = true;
                selectParticipante.innerHTML = '<option value="">Primero seleccione un torneo</option>';
                return;
            }

            const esEquipo = torneo.es_equipo;
            const tipo = esEquipo ? 'equipo' : 'usuario';
            
            inputTipo.value = tipo;
            labelParticipante.textContent = esEquipo ? "Seleccionar Equipo a Inscribir:" : "Seleccionar Usuario a Inscribir:";
            selectParticipante.disabled = false;
            selectParticipante.innerHTML = '<option value="">Cargando...</option>';

            fetch(`../../../php/torneos/obtener_candidatos.php?tipo=${tipo}`)
                .then(res => res.json())
                .then(data => {
                    selectParticipante.innerHTML = `<option value="">-- Seleccione ${tipo} --</option>`;
                    data.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item.id;
                        opt.textContent = item.nombre;
                        selectParticipante.appendChild(opt);
                    });
                });
        });
    }
});