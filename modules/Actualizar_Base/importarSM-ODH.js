const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
const csv = require('csv-parser');

// =================== CONFIG ===================
const configDB = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'incidentes_csv',
  connectionLimit: 10,
  acquireTimeout: 60000,
  timeout: 60000,
  charset: 'utf8mb4'
};

// Obtener ruta del archivo desde argumentos de línea de comandos
const CSV_FILE = process.argv[2] || path.resolve(__dirname, 'export_ODH.csv');
const SEP = ';'; // Separador del CSV
const TABLE_NAME = 'incidentes_odh';

// Columnas exactas del CSV
const TARGET_COLUMNS = [
  'ID Beneficiario',
  'ID de incidente',
  'Inicio de la interrupción de servicio',
  'Fecha/hora de apertura',
  'Nombre Asignatario',
  'Prioridad',
  'Estado del registro',
  'Título',
  'Fin de la interrupción de servicio',
  'Fecha/hora de cierre',
  'Justificacion parada de reloj',
  'Fecha inicio parada de reloj',
  'Fecha fin parada de reloj',
  'Cerrado por',
  'Incidente Mayor',
  'Gestor de incidentes',
  'Grupo de asignación',
  'Abierto por',
  'INC Maximo',
  'OT Maximo',
  'OT Maximo Estado',
  'ID Conocimiento',
  'CI Relacionados',
  'Solución',
  'Responsabilidad',
  'Código de cierre',
  'CI afectado',
  'Motivo parada de reloj',
  'Descripción'
];

// Campos que son fecha/hora
const DATE_FIELDS = new Set([
  'Inicio de la interrupción de servicio',
  'Fecha/hora de apertura',
  'Fin de la interrupción de servicio',
  'Fecha/hora de cierre',
  'Fecha inicio parada de reloj',
  'Fecha fin parada de reloj'
]);

// ===============================================

// ---------- Utilidades ----------
const normHeader = (h) =>
  String(h || '')
    .replace(/^\uFEFF/, '')   // quita BOM si existe
    .replace(/\u00A0/g, ' ')  // NBSP → espacio normal
    .replace(/\s+/g, ' ')     // colapsa espacios múltiples
    .trim();

function toNullish(v) {
  if (v === null || v === undefined) return null;
  let s = String(v)
    .replace(/\u00A0/g, ' ')
    .trim();
  if (
    s === '' ||
    /^null$/i.test(s) ||
    /^na$/i.test(s) ||
    /^n\/a$/i.test(s) ||
    s === '0000-00-00 00:00:00'
  ) return null;
  return s;
}

function toMySQLDate(val) {
  if (val == null) return null;
  let s = toNullish(val);
  if (s == null) return null;

  // ISO ya correcto
  let m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
  if (m) {
    const hh = m[4] || '00', mi = m[5] || '00', ss = m[6] || '00';
    return `${m[1]}-${m[2]}-${m[3]} ${hh}:${mi}:${ss}`;
  }

  // AM/PM en español → inglés
  s = s.replace(/a\.?\s*m\.?/ig, 'AM').replace(/p\.?\s*m\.?/ig, 'PM');
  s = s.replace(/\s+/g, ' ');

  // d/m/Y [hh:mm[:ss] [AM|PM]]
  m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*(AM|PM))?)?$/i);
  if (!m) return null;

  let d  = parseInt(m[1], 10);
  let mo = parseInt(m[2], 10);
  let y  = parseInt(m[3], 10);
  if (y < 100) y += (y >= 70 ? 1900 : 2000);

  let hh = parseInt(m[4] || '0', 10);
  let mi = parseInt(m[5] || '0', 10);
  let ss = parseInt(m[6] || '0', 10);
  const ap = (m[7] || '').toUpperCase();

  if (ap === 'PM' && hh < 12) hh += 12;
  if (ap === 'AM' && hh === 12) hh = 0;

  const pad = (n) => String(n).padStart(2, '0');
  return `${y}-${pad(mo)}-${pad(d)} ${pad(hh)}:${pad(mi)}:${pad(ss)}`;
}

function normalizeRow(raw) {
  const out = {};
  for (const key of Object.keys(raw)) {
    const k = normHeader(key);
    let v = raw[key];

    // convierte fechas cuando corresponda
    if (DATE_FIELDS.has(k)) v = toMySQLDate(v);
    else v = toNullish(v);

    // Limpieza especial para ID Beneficiario
    if (k === 'ID Beneficiario' && v) {
      v = v.replace(/\./g, '').trim();
    }

    out[k] = v;
  }
  return out;
}

// ---------- Proceso principal ----------
async function importarCSV(csvFile = CSV_FILE) {
  console.log(`🔄 Iniciando proceso de migración ODH desde: ${csvFile}`);
  
  const pool = await mysql.createPool(configDB);
  const conn = await pool.getConnection();

  try {
    // Verificar que el archivo existe
    if (!fs.existsSync(csvFile)) {
      throw new Error(`El archivo CSV no existe en: ${csvFile}`);
    }

    const fileStats = fs.statSync(csvFile);
    console.log(`📁 Archivo encontrado: ${csvFile}`);
    console.log(`📊 Tamaño del archivo: ${(fileStats.size / 1024 / 1024).toFixed(2)} MB`);

    // Crear tabla si no existe
    const cols = TARGET_COLUMNS.map((c) => `\`${c}\` TEXT NULL`).join(',\n  ');
    const createSQL = `
      CREATE TABLE IF NOT EXISTS \`${TABLE_NAME}\` (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ${cols}
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `;
    await conn.query(createSQL);
    console.log(`✅ Tabla verificada: ${TABLE_NAME}`);

    // Limpiar tabla existente antes de insertar nuevos datos
    console.log('🔄 Limpiando datos anteriores...');
    await conn.query(`TRUNCATE TABLE \`${TABLE_NAME}\``);
    console.log('✅ Datos anteriores eliminados');

    const rows = await leerCSV(csvFile);

    console.log(`📊 Total de registros a procesar: ${rows.length}`);
    if (rows.length === 0) {
      throw new Error('El archivo CSV está vacío o no se pudo leer');
    }

    // Prepara INSERT
    const colsInsert = TARGET_COLUMNS.map(c => `\`${c}\``).join(', ');
    const placeholders = TARGET_COLUMNS.map(() => '?').join(', ');
    const sql = `INSERT INTO \`${TABLE_NAME}\` (${colsInsert}) VALUES (${placeholders})`;

    await conn.beginTransaction();

    let ok = 0, fail = 0;
    const totalRows = rows.length;
    
    for (const r of rows) {
      const vals = TARGET_COLUMNS.map(c =>
        DATE_FIELDS.has(c) ? toMySQLDate(r[c]) : toNullish(r[c])
      );

      try {
        await conn.query(sql, vals);
        ok++;
        
        // Mostrar progreso cada 100 registros
        if (ok % 100 === 0 || ok === totalRows) {
          const porcentaje = Math.round((ok/totalRows)*100);
          console.log(`📦 Progreso: ${ok}/${totalRows} registros (${porcentaje}%)`);
        }
      } catch (err) {
        fail++;
        console.error(`❌ Error en fila ${ok + fail}: ${err.code || err.message}`);
      }
    }

    await conn.commit();
    console.log(`\n✅ ===== MIGRACIÓN ODH COMPLETADA =====`);
    console.log(`✅ Registros insertados: ${ok}`);
    console.log(`❌ Registros fallidos: ${fail}`);
    console.log(`📊 Total procesado: ${totalRows}`);
    console.log(`🎯 Tasa de éxito: ${((ok/totalRows)*100).toFixed(2)}%`);
    
    return { inserted: ok, failed: fail, total: totalRows };
    
  } catch (e) {
    try { await conn.rollback(); } catch {}
    console.error('💥 Error durante la migración ODH:', e.message);
    throw e;
  } finally {
    conn.release();
    await pool.end();
    console.log('🔚 Conexión a BD cerrada');
  }
}

function leerCSV(file) {
  return new Promise((resolve, reject) => {
    const rows = [];
    fs.createReadStream(file)
      .pipe(csv({
        separator: SEP,
        mapHeaders: ({ header }) => normHeader(header),
        mapValues: ({ value }) => value
      }))
      .on('headers', (h) => {
        const preview = h.map(normHeader);
        console.log('📋 Headers detectados:', preview.join(' | '));
        const missing = TARGET_COLUMNS.filter(tc => !preview.includes(tc));
        if (missing.length) {
          console.warn('⚠️ Columnas faltantes en CSV:', missing.join(' | '));
        }
      })
      .on('data', (raw) => rows.push(normalizeRow(raw)))
      .on('end', () => {
        console.log(`✅ CSV leído correctamente: ${rows.length} filas procesadas`);
        resolve(rows);
      })
      .on('error', (err) => {
        console.error('❌ Error leyendo CSV:', err);
        reject(err);
      });
  });
}

// Ejecutar si es llamado directamente
if (require.main === module) {
  const csvFile = process.argv[2];
  importarCSV(csvFile).catch(err => {
    console.error('💥 Fallo inesperado:', err);
    process.exit(1);
  });
}

module.exports = { importarCSV };