const catalogos = {
  /*CATEGORIAS*/

  categorias: [
    {
      id: "esports",
      nombre: "eSports",
    },
    {
      id: "tradicional",
      nombre: "Deporte Tradicional",
    },
  ],

  /*DEPORTES*/

  deportes: [
  {
    id: "lol",
    nombre: "League of Legends",
    categoria: "esports",
    tipoParticipante: "equipos",

    configuracionEquipo: {
      titulares: 5,
      suplentes: 5,
      roles: [
        "Top",
        "Jungla",
        "Mid",
        "ADC",
        "Support"
      ]
    }
  },

  {
    id: "valorant",
    nombre: "Valorant",
    categoria: "esports",
    tipoParticipante: "equipos",

    configuracionEquipo: {
      titulares: 5,
      suplentes: 2,
      roles: [
        "Duelista",
        "Iniciador",
        "Controlador",
        "Centinela",
        "Flex"
      ]
    }
  },

  {
    id: "ajedrez",
    nombre: "Ajedrez",
    categoria: "tradicional",
    tipoParticipante: "individual",

    configuracionEquipo: {
      titulares: 1,
      suplentes: 0,
      roles: [
        "Jugador"
      ]
    }
  },

  {
    id: "tenis",
    nombre: "Tenis",
    categoria: "tradicional",
    tipoParticipante: "individual",

    configuracionEquipo: {
      titulares: 1,
      suplentes: 0,
      roles: [
        "Jugador"
      ]
    }
  }
],

  /*FORMATOS DE TORNEO*/

  formatos: [
    {
      id: "liga",
      nombre: "Liga",
    },
    {
      id: "eliminacion",
      nombre: "Eliminación Directa",
    },
    {
      id: "suizo",
      nombre: "Sistema Suizo",
    },
  ],

  /*CONFIGURACIONES ESPECIFICAS DE CADA DEPORTE*/

  configuracionesDeporte: {
    /*LEAGUE OF LEGENDS*/

    lol: `

            <div class="campoFormulario">

                <label for="box">
                    Mejor de X:
                </label>

                <input
                    placeholder="Ej: Mejor de 4"
                    type="number"
                    id="box"
                    name="box"
                    min="1"
                >

            </div>


            <div class="campoFormulario">

                <label for="ladoLol">
                    Selección de lado con first pick:
                </label>

                <select
                    id="ladoLol"
                    name="ladoLol"
                >

                    <option value="aleatorio">
                        Aleatorio
                    </option>

                    <option value="azul">
                        Azul
                    </option>

                    <option value="rojo">
                        Rojo
                    </option>

                </select>

            </div>

        `,

    /*VALORANT*/

    valorant: `

            <div class="campoFormulario">

                <label for="boxValorant">
                    Mejor de X:
                </label>

                <input
                    placeholder="Ej: Mejor de 4"
                    type="number"
                    id="boxValorant"
                    name="boxValorant"
                    min="1"
                >

            </div>


            <div class="campoFormulario">

                <label for="seleccionMapa">
                    Selección de mapas:
                </label>

                <select
                    id="seleccionMapa"
                    name="seleccionMapa"
                >

                    <option value="aleatorio">
                        Aleatorio
                    </option>

                    <option value="organizador">
                        Organizador
                    </option>

                </select>

            </div>

        `,

    /*AJEDREZ*/

    ajedrez: `

            <div class="campoFormulario">

                <label for="ritmoAjedrez">
                    Ritmo de juego:
                </label>

                <select
                    id="ritmoAjedrez"
                    name="ritmoAjedrez"
                >

                    <option value="clasico">
                        Clásico
                    </option>

                    <option value="rapido">
                        Rápido
                    </option>

                    <option value="blitz">
                        Blitz
                    </option>

                </select>

            </div>


            <div class="campoFormulario">

                <label for="controlTiempo">
                    Control de tiempo:
                </label>

                <input
                    placeholder="Ej: 10+5"
                    type="text"
                    id="controlTiempo"
                    name="controlTiempo"
                >

            </div>

        `,

    /*TENIS*/

    tenis: `

            <div class="campoFormulario">

                <label for="sets">
                    Sets para ganar:
                </label>

                <select
                    id="sets"
                    name="sets"
                >

                    <option value="2">
                        2 de 3
                    </option>

                    <option value="3">
                        3 de 5
                    </option>

                </select>

            </div>


            <div class="campoFormulario campoCheckbox">

                <label>
                    Usar Tie-break
                </label>

                    <input
                        type="checkbox"
                        id="tieBreak"
                        name="tieBreak"
                        checked
                    >
            </div>

        `,
  },
};

