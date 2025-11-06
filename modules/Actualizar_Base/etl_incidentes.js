// etl_incidentes.js
// Construye la tabla incidentes_etl desde incidentes (MySQL/MariaDB)
// Uso: node etl_incidentes.js
// Requiere: npm i mysql2 dotenv

require('dotenv').config();
const mysql = require('mysql2/promise');

const DB = process.env.DB_NAME || 'incidentes_csv';
const CONFIG = {
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: DB,
  port: Number(process.env.DB_PORT || 3306),
  dateStrings: true,            // trae DATETIME como 'YYYY-MM-DD HH:mm:ss'
  multipleStatements: false,
};

const SRC_TABLE  = 'incidentes';
const DEST_TABLE = 'incidentes_etl';

function splitMotivos(raw) {
  if (!raw) return [];
  let t = String(raw);

  // separadores por espacios dobles/más y patrones frecuentes
  t = t.replace(/\s{2,}/g, ' | ');
  const patterns = [
    /Atribuible\s+Terceros\s*-/gi,
    /Continuidad\s+servicio\s*-/gi,
    /Fuera\s+de\s+horario/gi,
    /Falla\s+Energ/gi,
    /Fen[óo]meno\s+Atmosf[ée]rico/gi,
    /Mantenimiento\s+(correctivo|preventivo)/gi,
    /Intermitencia/gi,
    /Corte\s+programado/gi
  ];
  for (const p of patterns) t = t.replace(p, (m) => ` | ${m.trim()}`);

  const tokens = t
    .split(/[|;/\n]+/g)
    .map(s => s.replace(/\s{2,}/g, ' ').trim())
    .filter(Boolean);

  // únicos y límite seguro para índices utf8mb4
  return Array.from(new Set(tokens)).map(s => s.slice(0, 190));
}

function partDate(dtStr) {
  if (!dtStr || typeof dtStr !== 'string' || dtStr.length < 10) {
    return { fecha: null, hora: null };
  }
  const fecha = dtStr.slice(0, 10);
  const hora  = dtStr.length >= 19 ? dtStr.slice(11, 19) : null;
  return { fecha, hora };
}

async function runETL() {
  const conn = await mysql.createConnection(CONFIG);

  // Garantiza BD y usa el schema
  await conn.execute(`CREATE DATABASE IF NOT EXISTS \`${DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`);
  await conn.execute(`USE \`${DB}\`;`);

  const TMP = `${DEST_TABLE}_tmp`;
  await conn.execute(`DROP TABLE IF EXISTS \`${TMP}\`;`);

  // Esquema destino (PK surrogate para permitir motivo NULL; unique opcional)
  await conn.execute(`
    CREATE TABLE \`${TMP}\` (
      id_etl BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      id INT NOT NULL,
      id_incidente VARCHAR(100),
      id_beneficiario VARCHAR(100),
      inicio_dt DATETIME NULL,

      fin_dt DATETIME NULL,
      fin_fecha DATE NULL,
      fin_hora TIME NULL,

      cierre_dt DATETIME NULL,
      cierre_fecha DATE NULL,
      cierre_hora TIME NULL,

      motivo_crudo TEXT NULL,
      motivo_normalizado VARCHAR(190) NULL,

      PRIMARY KEY (id_etl),
      KEY idx_id (id),
      KEY idx_incidente (id_incidente),
      KEY idx_fin_fecha (fin_fecha),
      KEY idx_cierre_fecha (cierre_fecha),
      UNIQUE KEY uq_id_motivo (id, motivo_normalizado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `);

  const PAGE = 4000;
  let off = 0;
  let total = 0;

  while (true) {
    const [rows] = await conn.execute(
      `
      SELECT
        i.id AS id,
        i.\`ID de incidente\` AS id_incidente,
        i.\`ID Beneficiario\` AS id_beneficiario,
        i.\`Inicio de la interrupción de servicio\` AS inicio_dt,
        i.\`Fin de la interrupción de servicio\` AS fin_dt,
        i.\`Fecha/hora de cierre\` AS cierre_dt,
        i.\`Motivo parada de reloj\` AS motivo_crudo
      FROM \`${SRC_TABLE}\` i
      ORDER BY i.id
      LIMIT ? OFFSET ?;`,
      [PAGE, off]
    );

    if (!rows.length) break;

    const batch = [];
    for (const r of rows) {
      const motivos = splitMotivos(r.motivo_crudo);
      const fin     = partDate(r.fin_dt);
      const cierre  = partDate(r.cierre_dt);

      const base = [
        r.id,
        r.id_incidente,
        r.id_beneficiario,
        r.inicio_dt || null,
        r.fin_dt || null,
        fin.fecha,
        fin.hora,
        r.cierre_dt || null,
        cierre.fecha,
        cierre.hora,
        r.motivo_crudo || null
      ];

      if (motivos.length === 0) {
        // conserva el incidente aunque no haya motivo
        batch.push([...base, null]);
      } else {
        for (const m of motivos) batch.push([...base, m]);
      }
    }

    if (batch.length) {
      await conn.query(
        `
        INSERT INTO \`${TMP}\`
        (id, id_incidente, id_beneficiario, inicio_dt,
         fin_dt, fin_fecha, fin_hora,
         cierre_dt, cierre_fecha, cierre_hora,
         motivo_crudo, motivo_normalizado)
        VALUES ?;
        `,
        [batch]
      );
      total += batch.length;
    }

    off += rows.length;
    console.log(`ETL: insertadas ${total} filas en ${TMP} (offset=${off})`);
  }

  // Swap atómico
  await conn.beginTransaction();
  try {
    const [[existe]] = await conn.query(
      `SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?`,
      [DB, DEST_TABLE]
    );

    if (existe.n > 0) {
      await conn.query(`RENAME TABLE \`${DEST_TABLE}\` TO \`${DEST_TABLE}_old\`, \`${TMP}\` TO \`${DEST_TABLE}\`;`);
    } else {
      await conn.query(`RENAME TABLE \`${TMP}\` TO \`${DEST_TABLE}\`;`);
    }
    await conn.commit();

    // Limpieza fuera de la transacción
    if (existe.n > 0) await conn.query(`DROP TABLE IF EXISTS \`${DEST_TABLE}_old\`;`);
  } catch (e) {
    await conn.rollback();
    throw e;
  }

  await conn.end();
  console.log(`✅ ETL finalizada. Tabla lista: ${DEST_TABLE}`);
}

if (require.main === module) {
  runETL().catch(err => {
    console.error('💥 ERROR ETL:', err);
    process.exit(1);
  });
}
module.exports = { runETL };
