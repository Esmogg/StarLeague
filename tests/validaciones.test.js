/*
 * Prueba las reglas del frontend (js/validaciones.js) con los mismos casos
 * que tests/ValidadorTest.php usa para el backend.
 * Ejecutar:  node tests/validaciones.test.js
 */
const fs = require("fs");
const path = require("path");
const V = require("../js/validaciones.js");

const casos = JSON.parse(
  fs.readFileSync(path.join(__dirname, "casos_validacion.json"), "utf8"),
);

let fallos = 0;
for (const c of casos) {
  const obtenido = V[c.fn](...c.args);
  const ok = obtenido === c.esperado;
  if (!ok) fallos++;
  console.log(`${ok ? "OK   " : "FALLO"} ${c.fn}: ${c.caso}`);
  if (!ok) console.log(`       esperado: ${c.esperado}\n       obtenido: ${obtenido}`);
}
console.log(`\n${casos.length - fallos}/${casos.length} casos correctos`);
process.exit(fallos ? 1 : 0);
