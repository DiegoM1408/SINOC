/**
 * importar.js — CSV -> incidentes / incidentes_etl (TEXT) + ETL de PDR alineada 1:1 (con repeticiones)
 * Requisitos: npm i mysql2 csv-parser
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
  connectionLimit: 10,
  charset: 'utf8mb4'
};

const CSV_FILE = process.argv[2] || path.resolve(__dirname, 'export_2.csv');
const SEP = ';'; // separador del CSV

// =================== COLUMNAS ===================
const TARGET_COLUMNS = [
  'ID Beneficiario','ID de incidente',
  'Inicio de la interrupción de servicio','Fecha/hora de apertura',
  'Nombre Asignatario','Prioridad','Estado del registro','Título',
  'Fin de la interrupción de servicio','Fecha/hora de cierre',
  'Justificacion parada de reloj','Fecha inicio parada de reloj',
  'Fecha fin parada de reloj','Cerrado por','Incidente Mayor',
  'Gestor de incidentes','Grupo de asignación','Abierto por',
  'INC Maximo','OT Maximo','OT Maximo Estado','ID Conocimiento',
  'CI Relacionados','Solución','Responsabilidad','Código de cierre',
  'CI afectado','Motivo parada de reloj','Descripción'
];

const DATE_FIELDS_P1 = new Set([
  'Inicio de la interrupción de servicio','Fecha/hora de apertura',
  'Fin de la interrupción de servicio','Fecha/hora de cierre',
  'Fecha inicio parada de reloj','Fecha fin parada de reloj'
]);

// =========== CATÁLOGO DE MOTIVOS (7K) ===========
const CAT_MOTIVOS = [
  'Atribuible Terceros - Falla Energía Comercial Zona',
  'Atribuible Terceros - Falla Energía Eléctrica en CD',
  'Atribuible Terceros - Falla Infraestructura física CD',
  'Atribuible Terceros - Imposibilidad accesos al CD',
  'Atribuible Terceros - Imposibilidad accesos BTS Terceros Bases Militares',
  'Atribuible Terceros - Imposibilidad programar actividad',
  'Atribuible Terceros - Sin contacto con CD',
  'Caso Fortuito - Atenuación señal FO condiciones externas',
  'Caso Fortuito - Daños cableado interno condiciones externas',
  'Caso Fortuito - Fibra Daños Animales y/o Humanos',
  'Caso Fortuito - Manipulación elementos por Terceros',
  'Caso Fortuito - Sitio temporizado por solicitud Terceros',
  'Continuidad servicio - Instalaciones no disponibles  Fuera de horario',
  'Continuidad Servicio - Trabajo Programado',
  'En proceso de reinstalación',
  'En proceso de reubicación',
  'En proceso de traslado',
  'Fuerza mayor - Energía Alternativa',
  'Fuerza Mayor - Fenómeno Atmosférico',
  'Fuerza Mayor - Fenómeno Natural',
  'Fuerza Mayor - Hurto',
  'Fuerza Mayor - Orden Público',
  'Fuerza Mayor - Vandalismo',
  'Fuerza mayor - Ausencia suministros'
].map(s => s.trim());

// =============== HELPERS =================
const normHeader = (h) =>
  String(h || '')
    .replace(/^\uFEFF/,'')
    .replace(/\u00A0/g,' ')
    .replace(/\s+/g,' ')
    .trim();

function toNullish(v){
  if(v==null) return null;
  const s=String(v).replace(/\u00A0/g,' ').trim();
  if(!s || /^null$/i.test(s) || /^n\/a$/i.test(s)) return null;
  return s;
}

// P1: convierte fechas a DATETIME para 'incidentes'
function toMySQLDate(val){
  if(val==null) return null;
  let s=toNullish(val); if(!s) return null;
  s=s.replace(/a\.?\s*m\.?/ig,'AM').replace(/p\.?\s*m\.?/ig,'PM').replace(/\s+/g,' ');
  const m=s.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*(AM|PM))?)?$/i);
  if(!m) return s; // deja texto si no coincide (no perdemos datos)
  let d=+m[1], mo=+m[2], y=+m[3]; if(y<100) y+=2000;
  let hh=+(m[4]||0), mi=+(m[5]||0), ss=+(m[6]||0);
  const ap=(m[7]||'').toUpperCase();
  if(ap==='PM'&&hh<12) hh+=12;
  if(ap==='AM'&&hh===12) hh=0;
  const pad=(n)=>String(n).padStart(2,'0');
  return `${y}-${pad(mo)}-${pad(d)} ${pad(hh)}:${pad(mi)}:${pad(ss)}`;
}

function leerCSV(file, asText=false){
  return new Promise((resolve,reject)=>{
    const rows=[];
    fs.createReadStream(file)
      .pipe(csv({separator:SEP, mapHeaders:({header})=>normHeader(header)}))
      .on('data',(raw)=>{
        const row={};
        for(const k of Object.keys(raw)){
          const key=normHeader(k);
          const val = asText ? toNullish(raw[k])
                             : (DATE_FIELDS_P1.has(key)? toMySQLDate(raw[k]) : toNullish(raw[k]));
          row[key]=val;
        }
        rows.push(row);
      })
      .on('end',()=>resolve(rows))
      .on('error',reject);
  });
}

// ============= PATRONES DE FECHA/HORA (para ETL) =============
// Captura 05/10/25 07:00[:ss], 16/10/2025 07:00, 05-10-25 07:00, etc.
const DT_REGEX = /(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(\d{1,2}:\d{2}(?::\d{2})?)/ig;

function extractDateTokens(text){
  const out=[]; if(!text) return out;
  const s=String(text).replace(/\s+/g,' ');
  for(const m of s.matchAll(DT_REGEX)){ out.push(`${m[1]} ${m[2]}`.trim()); }
  return out;
}

// === Detecta TODAS las ocurrencias (incluye repeticiones) del catálogo dentro del texto
function matchCatalogInText(text){
  if (!text) return [];
  const t = String(text).toLowerCase().replace(/\u00A0/g, ' ');
  const hits = [];
  for (const mot of CAT_MOTIVOS) {
    const needle = mot.toLowerCase().replace(/\u00A0/g, ' ');
    let idx = t.indexOf(needle);
    while (idx !== -1) {              // captura repeticiones de la misma frase
      hits.push({ idx, mot });
      idx = t.indexOf(needle, idx + needle.length);
    }
  }
  hits.sort((a,b) => a.idx - b.idx);
  return hits.map(h => h.mot);
}

// === Split de motivos con prioridad: catálogo en "Motivo", separadores, catálogo/sep en "Justificación", o valor tal cual
function splitMotivos(rawMotivo, rawJustificacion){
  const bySep = (str)=> String(str||'').split(/[\|\n;]+/g).map(x=>x.trim()).filter(Boolean);

  // 1) Motivos (con duplicados) en el propio campo
  const hitsMotivo = matchCatalogInText(rawMotivo);
  if (hitsMotivo.length > 0) return hitsMotivo;

  // 2) Si vienen explícitos por separador
  const sepMotivo = bySep(rawMotivo);
  if (sepMotivo.length) return sepMotivo;

  // 3) Buscar en la justificación
  const hitsJust = matchCatalogInText(rawJustificacion);
  if (hitsJust.length > 0) return hitsJust;

  const sepJust = bySep(rawJustificacion);
  if (sepJust.length) return sepJust;

  // 4) fallback: único valor/tal cual o null
  const uno = toNullish(rawMotivo);
  return uno ? [uno] : [];
}

// ============= PARTE 1: CSV -> incidentes =============
async function cargarAIncidentes(conn, csvPath){
  console.log('🔄 [1/3] TRUNCATE incidentes...');
  await conn.query('TRUNCATE TABLE `incidentes`');

  const rows=await leerCSV(csvPath,false);
  const cols=TARGET_COLUMNS.map(c=>`\`${c}\``).join(', ');
  const ph=TARGET_COLUMNS.map(()=>'?').join(', ');
  const sql=`INSERT INTO \`incidentes\` (${cols}) VALUES (${ph})`;

  await conn.beginTransaction();
  let ok=0;
  for(const r of rows){
    await conn.query(sql, TARGET_COLUMNS.map(c=>r[c]??null));
    ok++;
  }
  await conn.commit();
  console.log(`✅ incidentes cargados: ${ok}`);
}

// ============= PARTE 2: CSV -> incidentes_etl (TEXT) =============
async function ensureTextTable(conn, table){
  const colsDDL=TARGET_COLUMNS.map(c=>`\`${c}\` TEXT NULL`).join(',\n  ');
  await conn.query(`CREATE TABLE IF NOT EXISTS \`${table}\` (${colsDDL}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`);
}

async function cargarAIncidentesETL(conn, csvPath){
  console.log('🔄 [2/3] Crear/Truncar/Cargar incidentes_etl (TEXT)...');
  await ensureTextTable(conn,'incidentes_etl');
  await conn.query('TRUNCATE TABLE `incidentes_etl`');

  const rows=await leerCSV(csvPath,true);
  const cols=TARGET_COLUMNS.map(c=>`\`${c}\``).join(', ');
  const ph=TARGET_COLUMNS.map(()=>'?').join(', ');
  const sql=`INSERT INTO \`incidentes_etl\` (${cols}) VALUES (${ph})`;

  await conn.beginTransaction();
  let ok=0;
  for(const r of rows){
    await conn.query(sql, TARGET_COLUMNS.map(c=>r[c]??null));
    ok++;
  }
  await conn.commit();
  console.log(`✅ incidentes_etl base cargada: ${ok}`);
}

// ============= PARTE 3: ETL — Explota PDR con alineación 1:1 (y repeticiones) =============
function explodeRowByPDR(row){
  const motivos = splitMotivos(row['Motivo parada de reloj'], row['Justificacion parada de reloj']); // mantiene duplicados
  const inicios = extractDateTokens(row['Fecha inicio parada de reloj']);
  const fines   = extractDateTokens(row['Fecha fin parada de reloj']);

  // tamaño final = max(#motivos, #inicios, #fines, 1)
  const n = Math.max(motivos.length || 0, inicios.length || 0, fines.length || 0, 1);

  const out=[];
  for(let i=0;i<n;i++){
    const copy={};
    for(const c of TARGET_COLUMNS) copy[c]=toNullish(row[c]);

    // Alineación por índice; si solo hay 1 motivo/fecha, se repite; si falta, queda null
    copy['Motivo parada de reloj']       = (motivos[i] ?? (motivos.length===1 ? motivos[0] : null)) || null;
    copy['Fecha inicio parada de reloj'] = inicios[i] ?? (inicios.length===1 ? inicios[0] : null);
    copy['Fecha fin parada de reloj']    = fines[i]   ?? (fines.length===1   ? fines[0]   : null);

    out.push(copy);
  }
  return out;
}

async function ejecutarETLSeparacion(conn){
  console.log('🔧 [3/3] ETL separación (PDR 1:1) sobre incidentes_etl...');
  const [rows]=await conn.query('SELECT * FROM `incidentes_etl`');
  const exploded=[];
  for(const r of rows){ exploded.push(...explodeRowByPDR(r)); }

  console.log(`📊 Filas base: ${rows.length} → Filas separadas: ${exploded.length}`);

  await conn.query('TRUNCATE TABLE `incidentes_etl`');

  const cols=TARGET_COLUMNS.map(c=>`\`${c}\``).join(', ');
  const ph=TARGET_COLUMNS.map(()=>'?').join(', ');
  const sql=`INSERT INTO \`incidentes_etl\` (${cols}) VALUES (${ph})`;

  await conn.beginTransaction();
  let ok=0;
  for(const r of exploded){
    await conn.query(sql, TARGET_COLUMNS.map(c=>r[c]??null));
    ok++;
  }
  await conn.commit();
  console.log(`✅ incidentes_etl reescrita con filas separadas: ${ok}`);
}

// =================== MAIN ===================
(async()=>{
  const pool = await mysql.createPool(configDB);
  const conn = await pool.getConnection();
  try{
    console.log(`🚀 CSV: ${CSV_FILE}`);
    await cargarAIncidentes(conn, CSV_FILE);     // 1) original
    await cargarAIncidentesETL(conn, CSV_FILE);  // 2) texto
    await ejecutarETLSeparacion(conn);           // 3) PDR alineado 1:1 con repeticiones
    console.log('🎯 Pipeline completado.');
  }catch(e){
    console.error('💥 Error general:', e.message);
    process.exitCode=1;
  }finally{
    conn.release();
    await pool.end();
  }
})();
