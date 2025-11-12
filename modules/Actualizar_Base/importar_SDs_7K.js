/**
 * importar_SD_7K.js - VERSIÓN CORREGIDA
 * Importa el CSV de SD 7K a MySQL
 */

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
  multipleStatements: true,
  connectionLimit: 10
};

const TABLE_NAME = 'incidentes_SD_7K';
// Obtener archivo CSV desde parámetro
const CSV_FILE = process.argv[2] ? path.resolve(process.argv[2]) : null;

// Validar que se proporcionó archivo
if (!CSV_FILE) {
  console.error('❌ Error: No se proporcionó archivo CSV');
  process.exit(1);
}

// Columnas
const TARGET_COLUMNS = [
  'Fecha/hora de apertura',
  'ID de la interacción',
  'ID del Beneficiario',
  'Destinatario del servicio',
  'Servicio afectado',
  'CI afectado',
  'Estado',
  'Proxima Cita',
  'Título',
  'Fecha/hora de cierre',
  'Clr Txt Responsabilidad',
  'Solución',
  'Grupo de asignación',
  'Abierto por',
  'CCC Numero',
  'Prioridad'
];

const DATE_FIELDS = new Set([
  'Fecha/hora de apertura',
  'Fecha/hora de cierre'
]);

// ============ FUNCIONES MEJORADAS ============

function normHeader(h) {
  return String(h || '')
    .replace(/^\uFEFF/, '')
    .replace(/\u00A0/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function toNullish(v) {
  if (v === null || v === undefined) return null;
  const s = String(v).trim();
  if (s === '' || /^null$/i.test(s) || /^n\/a$/i.test(s)) return null;
  return s;
}

function toMySQLDate(val) {
  if (!val) return null;
  let s = String(val).replace(/\u00A0/g, ' ').trim();

  // Limpiar y estandarizar formato
  s = s.replace(/a\.?\s*m\.?/ig, 'AM').replace(/p\.?\s*m\.?/ig, 'PM');
  s = s.replace(/\s+/g, ' ');

  // Probar múltiples formatos de fecha
  let m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*(AM|PM))?)?$/i);
  
  if (!m) {
    // Intentar otro formato común
    m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/i);
    if (!m) return null;
    
    const d = parseInt(m[3], 10);
    const mo = parseInt(m[2], 10);
    const y = parseInt(m[1], 10);
    const hh = parseInt(m[4] || '0', 10);
    const mi = parseInt(m[5] || '0', 10);
    const ss = parseInt(m[6] || '0', 10);

    const pad = (n) => String(n).padStart(2, '0');
    return `${y}-${pad(mo)}-${pad(d)} ${pad(hh)}:${pad(mi)}:${pad(ss)}`;
  }

  let d = parseInt(m[1], 10);
  let mo = parseInt(m[2], 10);
  let y = parseInt(m[3], 10);
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
    if (DATE_FIELDS.has(k)) {
      v = toMySQLDate(v);
    } else {
      v = toNullish(v);
    }
    out[k] = v;
  }
  return out;
}

// ============ LECTURA CSV MEJORADA ============

function leerCSV(file) {
  return new Promise((resolve, reject) => {
    console.log(`📁 Leyendo archivo: ${file}`);
    
    // Verificar que el archivo existe
    if (!fs.existsSync(file)) {
      reject(new Error(`El archivo no existe: ${file}`));
      return;
    }

    const rows = [];
    let headersDetected = false;
    
    fs.createReadStream(file)
      .on('error', (err) => {
        console.error('❌ Error leyendo archivo:', err.message);
        reject(err);
      })
      .pipe(csv({
        separator: ';',
        mapHeaders: ({ header, index }) => {
          if (!headersDetected) {
            headersDetected = true;
            console.log('🔍 Procesando encabezados...');
          }
          return normHeader(header);
        },
        mapValues: ({ value }) => value,
        skipEmptyLines: true
      }))
      .on('headers', (headers) => {
        console.log('🧾 Encabezados detectados:', headers.join(' | '));
        const missing = TARGET_COLUMNS.filter(tc => !headers.includes(tc));
        if (missing.length) {
          console.warn('⚠️ Columnas faltantes en CSV:', missing.join(' | '));
        }
      })
      .on('data', (raw) => {
        rows.push(raw);
      })
      .on('end', () => {
        console.log(`✅ Lectura completada: ${rows.length} filas procesadas`);
        resolve(rows);
      })
      .on('error', (err) => {
        console.error('❌ Error en parser CSV:', err.message);
        reject(err);
      });
  });
}

// ============ CREACIÓN DE BASE Y TABLA ============

async function ensureDatabaseAndTable(conn) {
  try {
    // Crear base de datos si no existe
    await conn.query(`CREATE DATABASE IF NOT EXISTS \`${configDB.database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`);
    await conn.query(`USE \`${configDB.database}\`;`);

    // Crear tabla
    await conn.query(`
      CREATE TABLE IF NOT EXISTS \`${TABLE_NAME}\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`Fecha/hora de apertura\` DATETIME NULL,
        \`ID de la interacción\` VARCHAR(255),
        \`ID del Beneficiario\` VARCHAR(255),
        \`Destinatario del servicio\` VARCHAR(255),
        \`Servicio afectado\` VARCHAR(255),
        \`CI afectado\` VARCHAR(255),
        \`Estado\` VARCHAR(255),
        \`Proxima Cita\` VARCHAR(255),
        \`Título\` TEXT,
        \`Fecha/hora de cierre\` DATETIME NULL,
        \`Clr Txt Responsabilidad\` TEXT,
        \`Solución\` TEXT,
        \`Grupo de asignación\` VARCHAR(255),
        \`Abierto por\` VARCHAR(255),
        \`CCC Numero\` VARCHAR(255),
        \`Prioridad\` VARCHAR(255),
        \`fecha_importacion\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // Truncar tabla
    await conn.query(`TRUNCATE TABLE \`${TABLE_NAME}\`;`);
    console.log(`🧹 Tabla ${TABLE_NAME} truncada y lista.`);
    
  } catch (error) {
    console.error('❌ Error creando tabla:', error.message);
    throw error;
  }
}

// ============ FUNCIÓN PRINCIPAL ============

async function importarCSV() {
  let pool;
  let conn;

  try {
    console.log('🔄 Iniciando proceso de importación SDs 7K...');
    console.log(`📊 Archivo: ${CSV_FILE}`);
    
    // Crear pool de conexiones
    pool = mysql.createPool(configDB);
    conn = await pool.getConnection();
    
    console.log('✅ Conectado a la base de datos');
    
    await ensureDatabaseAndTable(conn);

    const rows = await leerCSV(CSV_FILE);
    console.log(`📥 Total de filas a insertar: ${rows.length}`);

    if (rows.length === 0) {
      console.log('⚠️ No hay datos para insertar.');
      return;
    }

    const cols = TARGET_COLUMNS.map(c => `\`${c}\``).join(', ');
    const placeholders = TARGET_COLUMNS.map(() => '?').join(', ');
    const sql = `INSERT INTO \`${TABLE_NAME}\` (${cols}) VALUES (${placeholders})`;

    await conn.beginTransaction();
    console.log('🔄 Iniciando transacción...');

    let ok = 0, fail = 0;
    const totalRows = rows.length;
    
    // Progreso cada 100 registros o 2 segundos
    let lastProgress = Date.now();
    
    for (let i = 0; i < rows.length; i++) {
      const raw = rows[i];
      const r = normalizeRow(raw);
      const vals = TARGET_COLUMNS.map(c => r[c] || null);
      
      try {
        await conn.query(sql, vals);
        ok++;
        
        // Mostrar progreso periódicamente
        const now = Date.now();
        if (ok % 100 === 0 || now - lastProgress > 2000) {
          const progress = Math.round((ok + fail) / totalRows * 100);
          console.log(`🔄 Progreso: ${ok + fail}/${totalRows} (${progress}%) - ${ok} OK, ${fail} errores`);
          lastProgress = now;
        }
        
      } catch (err) {
        fail++;
        console.error(`❌ Error en fila ${i + 1}:`, err.message);
        // Continuar con siguiente fila en lugar de detener todo
      }
    }

    await conn.commit();
    console.log(`✅ Migración SDs 7K completada: ${ok} insertados | ❌ ${fail} errores`);
    
    if (fail > 0) {
      console.warn(`⚠️ Se produjeron ${fail} errores durante la importación`);
    }
    
  } catch (error) {
    console.error('💥 ERROR GENERAL:', error.message);
    
    if (conn) {
      try {
        await conn.rollback();
        console.log('🔁 Transacción revertida');
      } catch (rollbackError) {
        console.error('❌ Error en rollback:', rollbackError.message);
      }
    }
    
    throw error;
    
  } finally {
    if (conn) {
      conn.release();
    }
    if (pool) {
      await pool.end();
    }
  }
}

// ============ EJECUCIÓN ============

// Manejar errores no capturados
process.on('unhandledRejection', (error) => {
  console.error('💥 Error no manejado:', error);
  process.exit(1);
});

process.on('uncaughtException', (error) => {
  console.error('💥 Excepción no capturada:', error);
  process.exit(1);
});

// Ejecutar importación
importarCSV()
  .then(() => {
    console.log('🎉 Proceso de SDs 7K finalizado exitosamente');
    process.exit(0);
  })
  .catch(error => {
    console.error('💥 Fallo en el proceso:', error.message);
    process.exit(1);
  });