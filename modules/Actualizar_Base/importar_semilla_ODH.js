// importar_semilla_ODH.js
// ELT Excel → MySQL (Semilla ODH)
// - Detecta el Excel más reciente en el directorio actual (el que crea upload.php)
// - Usa la hoja "INVENTARIO" por defecto
// - Crea la tabla si no existe y, si existe, SINCRONIZA columnas (ADD/MODIFY)
// - Todas las columnas TEXT, excepto id_beneficiario como VARCHAR(25)
// - Inserción por lotes multi-VALUES con progreso

const fs = require('fs');
const path = require('path');
const XLSX = require('xlsx');
const mysql = require('mysql2/promise');

// ========= CLI =========
const args = process.argv.slice(2);
const getArg = (flag, def = null) => {
  const hit = args.find(a => a.startsWith(`--${flag}`));
  if (!hit) return def;
  const eq = hit.indexOf('=');
  return eq >= 0 ? hit.slice(eq + 1).trim() : true;
};

const TABLE_NAME = getArg('tabla', 'inventario_semilla_mintic_odh');
const SHEET_NAME = getArg('sheet', 'INVENTARIO'); // cambia si tu hoja se llama distinto
const APPEND = !!getArg('append', false);
const ID_COL = (getArg('idcol', 'id_beneficiario') || '').toString().trim().toLowerCase();
const ID_LEN = Math.max(1, parseInt(getArg('idlen', '25'), 10) || 25);

// ========= DB =========
// Ajusta si aplica. Tus PHP apuntan a esta misma BD (incidentes_csv) en config.php
const DB = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'incidentes_csv',
  charset: 'utf8mb4',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

// ========= Utils =========
const removeDiacritics = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

function sanitizeName(name, used = new Set()) {
  let s = String(name ?? '').trim();
  if (!s) s = 'columna';
  s = removeDiacritics(s)
    .toLowerCase()
    .replace(/[^a-z0-9_]+/g, '_')   // símbolos → _
    .replace(/^_+|_+$/g, '');       // recorta _
  if (!s) s = 'columna';
  if (/^[0-9]/.test(s)) s = `c_${s}`;
  const base = s;
  let i = 2;
  while (used.has(s)) s = `${base}_${i++}`;
  used.add(s);
  return s;
}

function findNewestExcel(cwd = process.cwd()) {
  const files = fs.readdirSync(cwd)
    .filter(f => /\.(xlsx|xls)$/i.test(f) && !/^~\$\S+/.test(f))
    .map(f => ({ file: path.join(cwd, f), mtime: fs.statSync(path.join(cwd, f)).mtimeMs }))
    .sort((a, b) => b.mtime - a.mtime);
  if (!files.length) throw new Error('No se encontró ningún .xlsx/.xls en la carpeta actual.');
  return files[0].file;
}

function pickSheetName(wb, desired) {
  // Coincidencia exacta, luego relajada (sin espacios/acentos)
  const norm = s => removeDiacritics(String(s)).toLowerCase().replace(/\s+/g, '');
  const exact = wb.SheetNames.find(n => n.toLowerCase() === String(desired).toLowerCase());
  if (exact) return exact;
  const relaxed = wb.SheetNames.find(n => norm(n) === norm(desired));
  if (relaxed) return relaxed;
  // Intentar 'inventario' por defecto
  const inv = wb.SheetNames.find(n => norm(n) === 'inventario');
  if (inv) return inv;
  throw new Error(`No se encontró la hoja "${desired}". Hojas: ${wb.SheetNames.join(', ')}`);
}

function normalizeText(raw) {
  if (raw === null || raw === undefined) return null;
  const s = String(raw).replace(/\uFFFD/g, '').trim();
  return s === '' ? null : s;
}

function normalizeId(raw) {
  if (raw === null || raw === undefined) return null;
  let s = typeof raw === 'number'
    ? (Number.isInteger(raw) ? String(raw) : String(Math.trunc(raw)))
    : String(raw);
  s = s.replace(/\s+/g, '').trim();
  if (!s || /^null$/i.test(s)) return null;
  if (s.length > ID_LEN) s = s.slice(0, ID_LEN);
  return s;
}

// ========= Schema helpers =========
async function tableExists(conn, dbName, table) {
  const [rows] = await conn.query(
    'SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema=? AND table_name=?',
    [dbName, table]
  );
  return rows[0].n > 0;
}

async function getExistingColumns(conn, table) {
  // Devuelve set de nombres en minúscula
  const [rows] = await conn.query(`SHOW COLUMNS FROM \`${table}\``);
  return rows
    .map(r => String(r.Field))
    .filter(c => c.toLowerCase() !== 'id') // excluir pk autoincrement
    .reduce((acc, c) => (acc.add(c.toLowerCase()), acc), new Set());
}

async function getColumnTypes(conn, table) {
  const [rows] = await conn.query(`SHOW COLUMNS FROM \`${table}\``);
  const map = {};
  for (const r of rows) {
    map[String(r.Field).toLowerCase()] = String(r.Type).toLowerCase();
  }
  return map;
}

async function ensureTableAndColumns(conn, dbName, table, colMap, typeMap) {
  // Crea si no existe; si existe, agrega columnas faltantes y ajusta idcol a VARCHAR(ID_LEN)
  const exists = await tableExists(conn, dbName, table);
  if (!exists) {
    const colsDDL = colMap.map(({ san }) => `\`${san}\` ${typeMap[san]}`).join(',\n  ');
    const createSQL = `
      CREATE TABLE \`${table}\` (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ${colsDDL}
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;
    await conn.query(createSQL);
    console.log(`✅ Tabla creada: ${table}`);
    return;
  }

  // Si existe: añadir columnas faltantes
  const existingCols = await getExistingColumns(conn, table);
  const missing = colMap
    .map(c => c.san)
    .filter(san => !existingCols.has(san.toLowerCase()));

  for (const san of missing) {
    const ddl = `ALTER TABLE \`${table}\` ADD COLUMN \`${san}\` ${typeMap[san]}`;
    await conn.query(ddl);
    console.log(`🔧 ADD COLUMN: ${san} (${typeMap[san]})`);
  }

  // Ajustar tipo de la columna ID (si existe)
  if (ID_COL) {
    const types = await getColumnTypes(conn, table);
    const t = types[ID_COL];
    const target = `varchar(${ID_LEN})`;
    if (!t || !t.startsWith(target)) {
      try {
        await conn.query(`ALTER TABLE \`${table}\` MODIFY COLUMN \`${ID_COL}\` VARCHAR(${ID_LEN}) NULL`);
        console.log(`🔧 MODIFY COLUMN: ${ID_COL} → VARCHAR(${ID_LEN})`);
      } catch (e) {
        // Si la columna no existe aún (porque el Excel no la trae), la agregamos
        if (e && /unknown column/i.test(e.message)) {
          await conn.query(`ALTER TABLE \`${table}\` ADD COLUMN \`${ID_COL}\` VARCHAR(${ID_LEN}) NULL`);
          console.log(`🔧 ADD COLUMN (id): ${ID_COL} → VARCHAR(${ID_LEN})`);
        } else {
          console.log(`⚠️ No se pudo ajustar ${ID_COL}: ${e.message}`);
        }
      }
    }
  }

  console.log(`✅ Tabla sincronizada: ${table}`);
}

// ========= Main =========
(async () => {
  let pool, conn;
  try {
    console.log('🔎 Buscando Excel más reciente en la carpeta actual...');
    const excelPath = findNewestExcel();
    console.log(`📄 Archivo: ${path.basename(excelPath)}`);

    const wb = XLSX.readFile(excelPath, { cellDates: true, raw: false, cellText: true, dateNF: 'yyyy-mm-dd hh:mm:ss' });
    const sheetName = pickSheetName(wb, SHEET_NAME);
    const ws = wb.Sheets[sheetName];
    console.log(`📑 Hoja: ${sheetName}`);

    const rows = XLSX.utils.sheet_to_json(ws, { defval: null, raw: false, dateNF: 'yyyy-mm-dd hh:mm:ss' });
    console.log(`📊 Registros detectados: ${rows.length}`);
    if (!rows.length) throw new Error('La hoja está vacía.');

    // Encabezados → saneo + mapa
    const headersOriginal = Object.keys(rows[0]);
    const used = new Set();
    const headersSanitized = headersOriginal.map(h => sanitizeName(h, used));
    const colMap = headersSanitized.map((san, i) => ({ san, orig: headersOriginal[i] }));

    // Tipos: todo TEXT; id_beneficiario como VARCHAR(ID_LEN)
    const typeMap = {};
    for (const { san } of colMap) typeMap[san] = 'TEXT NULL';
    if (ID_COL && colMap.some(c => c.san === ID_COL)) typeMap[ID_COL] = `VARCHAR(${ID_LEN}) NULL`;

    // Log de mapeo (primeras 12 columnas para depurar)
    console.log('🧭 Mapeo (orig → san):', colMap.slice(0, 12).map(m => `${m.orig}→${m.san}`).join(' | '));

    // Conexión BD
    pool = await mysql.createPool(DB);
    conn = await pool.getConnection();
    await conn.query(`SET NAMES utf8mb4`);
    console.log('✅ Conectado a MySQL');

    // Crear/sincronizar tabla y columnas
    await ensureTableAndColumns(conn, DB.database, TABLE_NAME, colMap, typeMap);

    // TRUNCATE salvo que se pida append
    if (!APPEND) {
      await conn.query(`TRUNCATE TABLE \`${TABLE_NAME}\``);
      console.log('🧹 TRUNCATE aplicado (modo reemplazo).');
    } else {
      console.log('➕ Modo append (no se trunca la tabla).');
    }

    // Por si la tabla ya tenía columnas extra, volvemos a consultar columnas existentes
    const existingCols = await getExistingColumns(conn, TABLE_NAME);

    // Lista final de columnas a insertar = intersección (sanitizadas ∩ existentes)
    const insertCols = colMap
      .map(c => c.san)
      .filter(san => existingCols.has(san.toLowerCase()));

    if (!insertCols.length) {
      throw new Error('No hay columnas coincidentes entre el Excel y la tabla destino.');
    }

    const colsQuoted = insertCols.map(c => `\`${c}\``).join(', ');
    const rowPlaceholders = `(${insertCols.map(() => '?').join(',')})`;

    const BATCH_SIZE = 300;
    let buffer = [];
    let inserted = 0;

    const flush = async () => {
      if (!buffer.length) return;
      const placeholders = Array.from({ length: buffer.length }, () => rowPlaceholders).join(',');
      const sql = `INSERT INTO \`${TABLE_NAME}\` (${colsQuoted}) VALUES ${placeholders}`;
      const flat = buffer.flat();
      await conn.query(sql, flat);
      inserted += buffer.length;
      buffer = [];
      const pct = Math.round((inserted / rows.length) * 100);
      console.log(`📦 Progreso: ${inserted}/${rows.length} (${pct}%)`);
    };

    for (const r of rows) {
      const rowVals = insertCols.map(san => {
        const orig = colMap.find(m => m.san === san)?.orig;
        const raw = orig ? r[orig] : null;

        if (ID_COL && san === ID_COL) return normalizeId(raw); // id como VARCHAR
        return normalizeText(raw); // el resto texto normalizado
      });

      buffer.push(rowVals);
      if (buffer.length >= BATCH_SIZE) await flush();
    }
    await flush();

    console.log('\n✅ CARGA COMPLETADA');
    console.log(`   Registros insertados: ${inserted}`);
  } catch (err) {
    console.error('💥 Error:', err.message);
    process.exitCode = 1;
  } finally {
    try { if (conn) conn.release(); } catch {}
    try { if (pool) await pool.end(); } catch {}
    console.log('🔚 Conexión cerrada');
  }
})();
