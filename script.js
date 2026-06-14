const menuBtn = document.getElementById("menuBtn");
const aside = document.querySelector("aside");
const menuOverlay = document.getElementById("menuOverlay");

function abrirCerrarMenu() {
    aside.classList.toggle("abierto");
    menuOverlay.classList.toggle("activo");
    document.body.classList.toggle("menu-abierto");
	document.documentElement.classList.toggle("menu-abierto");
}

menuBtn.addEventListener("click", abrirCerrarMenu);

menuOverlay.addEventListener("click", () => {
    aside.classList.remove("abierto");
    menuOverlay.classList.remove("activo");
    document.body.classList.remove("menu-abierto");
	document.documentElement.classList.remove("menu-abierto");
});

const darkModeToggle = document.getElementById("darkModeToggle");

/* Cargar preferencia guardada */
if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark-mode");
	document.documentElement.classList.add("dark-mode");

    if (darkModeToggle) {
        darkModeToggle.checked = true;
    }
}

/* Cambiar tema */
if (darkModeToggle) {
    darkModeToggle.addEventListener("change", () => {

        if (darkModeToggle.checked) {
            document.body.classList.add("dark-mode");
			document.documentElement.classList.add("dark-mode");
			
            localStorage.setItem("darkMode", "true");
        } else {
            document.body.classList.remove("dark-mode");
			document.documentElement.classList.remove("dark-mode");
			
            localStorage.setItem("darkMode", "false");
        }

    });
}