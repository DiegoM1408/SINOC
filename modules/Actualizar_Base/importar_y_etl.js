// importar_y_etl.js
// Orquestador: 1) importar CSV -> tabla `incidentes` (tu script actual)
//              2) ejecutar ETL -> tabla `incidentes_etl`
// Uso: node importar_y_etl.js C:\ruta\al\archivo.csv
//
// NOTA: este archivo NO depende de que tu importador exporte funciones;
//       lo invoca como proceso hijo (node <script> <csv>).

require('dotenv').config();
const { spawn } = require('child_process');
const path = require('path');
const { runETL } = require('./etl_incidentes');

// ====== CONFIGURA AQUÍ EL SCRIPT IMPORTADOR QUE YA USAS ======
// Ejemplos: './importar.js'  o  './importarSM-ODH.js'
const IMPORT_SCRIPT = process.env.IMPORT_SCRIPT_PATH || './importar.js';
// Si tu importador NO recibe la ruta al CSV por argumento, deja IMPORT_ARGS
// devolviendo []. Si sí la recibe, pásala como [csvPath].
const IMPORT_ARGS = (csvPath) => [csvPath]; // ajusta si tu importador usa flags

// =============================================================

function runImporter(importScript, csvPath) {
  return new Promise((resolve, reject) => {
    const absScript = path.resolve(importScript);
    const args = [absScript, ...IMPORT_ARGS(csvPath)];
    const child = spawn(process.execPath, args, { stdio: 'pipe' });

    let out = '';
    let err = '';
    child.stdout.on('data', (d) => { out += d.toString(); process.stdout.write(d); });
    child.stderr.on('data', (d) => { err += d.toString(); process.stderr.write(d); });

    child.on('close', (code) => {
      if (code === 0) {
        resolve(out.trim());
      } else {
        reject(new Error(`Importador terminó con código ${code}\n${err || out}`));
      }
    });
  });
}

(async () => {
  try {
    const csvPath = process.argv[2];
    if (!csvPath) {
      console.error('Uso: node importar_y_etl.js <ruta_al_csv>');
      process.exit(1);
    }

    console.log('🟡 [1/2] Ejecutando importador (carga CSV -> tabla `incidentes`)…');
    await runImporter(IMPORT_SCRIPT, csvPath);
    console.log('✅ Importación completada.');

    console.log('🟡 [2/2] Ejecutando ETL de incidentes (creando `incidentes_etl`)…');
    await runETL();
    console.log('✅ ETL completada.');

    console.log('🎉 Proceso finalizado: importar CSV + ETL de separación');
    process.exit(0);
  } catch (e) {
    console.error('💥 Error en orquestación:', e.message || e);
    process.exit(1);
  }
})();
