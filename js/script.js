/* Modo oscuro */
/* Se ejecuta primero y de forma aislada para que, aunque falte algun
   elemento del menu en una pagina puntual, el tema guardado siempre
   se aplique. */
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
