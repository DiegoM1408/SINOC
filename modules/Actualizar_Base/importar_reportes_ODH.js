/**
 * importar_reportes_ODH.js - MODIFICADO PARA SISTEMA WEB
 * Importa datos de reportes ODH desde CSV a MySQL/MariaDB
 * Ahora acepta ruta del archivo como parámetro
 */

const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
const csv = require('csv-parser');

// === Config BD ===
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
const CSV_FILE = process.argv[2] || path.join(__dirname, 'Reporte Service Manager 2025_10_ODH.csv');
const SEP = '|'; // Separador del CSV
const TABLE_NAME = 'reportes_odh';
const BATCH_SIZE = 100; // Insertar en lotes de 100 registros

// Convierte '' -> null y maneja caracteres especiales
const toNull = (v) => {
  if (v === undefined || v === null || String(v).trim() === '') {
    return null;
  }
  return String(v).trim().replace(/\uFFFD/g, ''); // Remover caracteres de reemplazo
};

// Función para convertir fechas
function toMySQLDate(val) {
  if (!val) return null;
  let s = String(val).trim();
  
  // ISO directo
  let m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
  if (m) {
    const hh = m[4] || '00', mi = m[5] || '00', ss = m[6] || '00';
    return `${m[1]}-${m[2]}-${m[3]} ${hh}:${mi}:${ss}`;
  }

  // AM/PM en español
  s = s.replace(/a\.?\s*m\.?/ig, 'AM').replace(/p\.?\s*m\.?/ig, 'PM').replace(/\s+/g, ' ');
  
  // d/m/Y [hh:mm[:ss] [AM|PM]]
  m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*(AM|PM))?)?$/i);
  if (!m) return null;
  
  let d = parseInt(m[1]), mo = parseInt(m[2]), y = parseInt(m[3]);
  if (y < 100) y += y >= 70 ? 1900 : 2000;
  
  let hh = parseInt(m[4] || '0'), mi = parseInt(m[5] || '0'), ss = parseInt(m[6] || '0');
  const ap = (m[7] || '').toUpperCase();
  
  if (ap === 'PM' && hh < 12) hh += 12;
  if (ap === 'AM' && hh === 12) hh = 0;
  
  const pad = (n) => String(n).padStart(2, '0');
  return `${y}-${pad(mo)}-${pad(d)} ${pad(hh)}:${pad(mi)}:${pad(ss)}`;
}

async function importarReportesODH(csvFile = CSV_FILE) {
  let pool;
  let conn;

  try {
    console.log('🔄 Iniciando proceso de migración Reportes ODH...');
    
    // Verificar que el archivo existe
    if (!fs.existsSync(csvFile)) {
      throw new Error(`El archivo CSV no existe en: ${csvFile}`);
    }

    const fileStats = fs.statSync(csvFile);
    console.log(`📁 Archivo encontrado: ${csvFile}`);
    console.log(`📊 Tamaño del archivo: ${(fileStats.size / 1024 / 1024).toFixed(2)} MB`);
    
    pool = await mysql.createPool(configDB);
    conn = await pool.getConnection();

    console.log('✅ Conectado a la base de datos');

    // Crear tabla si no existe
    const createTableSQL = `
      CREATE TABLE IF NOT EXISTS \`${TABLE_NAME}\` (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        \`fecha_apertura\`                VARCHAR(50)  NULL,
        \`numero_ticket\`                 VARCHAR(100) NULL,
        \`canal_solicitud\`               VARCHAR(255) NULL,
        \`sem_id_beneficiario\`           VARCHAR(100) NULL,
        \`codigo_ticket_ccc\`             VARCHAR(100) NULL,
        \`departamento\`                  VARCHAR(100) NULL,
        \`municipio\`                     VARCHAR(100) NULL,
        \`centro_poblado\`                VARCHAR(255) NULL,
        \`tipo_cd\`                       VARCHAR(100) NULL,
        \`nombre_cd\`                     VARCHAR(255) NULL,
        \`dda\`                           VARCHAR(100) NULL,
        \`fase_instalacion\`              VARCHAR(100) NULL,
        \`bandera\`                       VARCHAR(100) NULL,
        \`fecha_cierre\`                  VARCHAR(50)  NULL,
        \`prioridad\`                     VARCHAR(100) NULL,
        \`responsabilidad_por_ticket\`    VARCHAR(255) NULL,
        \`descripcion_apertura_o_parada\` TEXT         NULL,
        \`motivo_parada\`                 VARCHAR(255) NULL,
        \`tiempo_total_parada_reloj\`     VARCHAR(100) NULL,
        \`tiempo_final_falla\`            VARCHAR(100) NULL,
        \`tipo_solicitud\`                VARCHAR(100) NULL,
        \`categoria\`                     VARCHAR(255) NULL,
        \`subcategoria\`                  VARCHAR(255) NULL,
        \`energia\`                       VARCHAR(100) NULL,
        \`tipificacion_falla\`            VARCHAR(255) NULL,
        \`acciones_correctivas\`          TEXT         NULL,
        \`estado\`                        VARCHAR(100) NULL,
        \`dispositivo_afectado\`          VARCHAR(255) NULL,
        \`solucion\`                      TEXT         NULL,
        \`masivo\`                        VARCHAR(50)  NULL,
        \`sem_cod_servicio\`              VARCHAR(100) NULL,
        \`clasificacion\`                 VARCHAR(100) NULL,
        KEY \`idx_ticket\` (\`numero_ticket\`),
        KEY \`idx_beneficiario\` (\`sem_id_beneficiario\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`;
    
    await conn.query(createTableSQL);
    console.log('✅ Tabla verificada/creada');

    // Limpiar tabla existente antes de insertar nuevos datos
    console.log('🔄 Limpiando datos anteriores...');
    await conn.query(`TRUNCATE TABLE \`${TABLE_NAME}\``);
    console.log('✅ Datos anteriores eliminados');

    const insertSQL = `
      INSERT INTO \`${TABLE_NAME}\` SET
        \`fecha_apertura\`=?,
        \`numero_ticket\`=?,
        \`canal_solicitud\`=?,
        \`sem_id_beneficiario\`=?,
        \`codigo_ticket_ccc\`=?,
        \`departamento\`=?,
        \`municipio\`=?,
        \`centro_poblado\`=?,
        \`tipo_cd\`=?,
        \`nombre_cd\`=?,
        \`dda\`=?,
        \`fase_instalacion\`=?,
        \`bandera\`=?,
        \`fecha_cierre\`=?,
        \`prioridad\`=?,
        \`responsabilidad_por_ticket\`=?,
        \`descripcion_apertura_o_parada\`=?,
        \`motivo_parada\`=?,
        \`tiempo_total_parada_reloj\`=?,
        \`tiempo_final_falla\`=?,
        \`tipo_solicitud\`=?,
        \`categoria\`=?,
        \`subcategoria\`=?,
        \`energia\`=?,
        \`tipificacion_falla\`=?,
        \`acciones_correctivas\`=?,
        \`estado\`=?,
        \`dispositivo_afectado\`=?,
        \`solucion\`=?,
        \`masivo\`=?,
        \`sem_cod_servicio\`=?,
        \`clasificacion\`=?
    `;

    let inserted = 0, failed = 0;
    let batch = [];
    let totalRows = 0;

    // Contar filas primero para progreso
    const rowCount = await new Promise((resolve) => {
      let count = 0;
      fs.createReadStream(csvFile)
        .pipe(csv({ 
          separator: SEP, 
          mapHeaders: ({ header }) => header.replace(/^\uFEFF/, '').trim()
        }))
        .on('data', () => count++)
        .on('end', () => resolve(count))
        .on('error', (err) => {
          console.error('❌ Error contando filas:', err);
          resolve(0);
        });
    });

    console.log(`📊 Total de registros a procesar: ${rowCount}`);

    if (rowCount === 0) {
      throw new Error('El archivo CSV está vacío o no se pudo leer');
    }

    await new Promise((resolve, reject) => {
      const stream = fs.createReadStream(csvFile)
        .pipe(csv({
          separator: SEP,
          mapHeaders: ({ header }) => header.replace(/^\uFEFF/, '').trim(),
          encoding: 'utf8'
        }))
        .on('data', async (row) => {
          stream.pause();
          totalRows++;

          try {
            const values = [
              toMySQLDate(toNull(row['fecha_apertura'])),
              toNull(row['numero_ticket']),
              toNull(row['canal_solicitud']),
              toNull(row['sem_id_beneficiario']),
              toNull(row['codigo_ticket_ccc']),
              toNull(row['departamento']),
              toNull(row['municipio']),
              toNull(row['centro_poblado']),
              toNull(row['tipo_cd']),
              toNull(row['nombre_cd']),
              toNull(row['dda']),
              toNull(row['fase_instalacion']),
              toNull(row['bandera']),
              toMySQLDate(toNull(row['fecha_cierre'])),
              toNull(row['prioridad']),
              toNull(row['responsabilidad_por_ticket']),
              toNull(row['descripcion_apertura_o_parada']),
              toNull(row['motivo_parada']),
              toNull(row['tiempo_total_parada_reloj']),
              toNull(row['tiempo_final_falla']),
              toNull(row['tipo_solicitud']),
              toNull(row['categoria']),
              toNull(row['subcategoria']),
              toNull(row['energia']),
              toNull(row['tipificacion_falla']),
              toNull(row['acciones_correctivas']),
              toNull(row['estado']),
              toNull(row['dispositivo_afectado']),
              toNull(row['solucion']),
              toNull(row['masivo']),
              toNull(row['sem_cod_servicio']),
              toNull(row['clasificacion'])
            ];

            batch.push(values);

            // Procesar lote cuando alcance el tamaño definido
            if (batch.length >= BATCH_SIZE) {
              const currentBatch = [...batch];
              batch = [];
              
              try {
                for (const batchValues of currentBatch) {
                  await conn.query(insertSQL, batchValues);
                  inserted++;
                }
                const porcentaje = Math.round((inserted/rowCount)*100);
                console.log(`📦 Progreso: ${inserted}/${rowCount} registros (${porcentaje}%)`);
              } catch (e) {
                failed += currentBatch.length;
                console.error('⚠️ Error insertando lote:', e.message);
              } finally {
                stream.resume();
              }
            } else {
              stream.resume();
            }
          } catch (error) {
            console.error('⚠️ Error procesando fila:', error);
            failed++;
            stream.resume();
          }
        })
        .on('end', async () => {
          // Procesar último lote
          if (batch.length > 0) {
            try {
              for (const batchValues of batch) {
                await conn.query(insertSQL, batchValues);
                inserted++;
              }
              console.log(`📦 Último lote procesado: ${inserted}/${rowCount} registros`);
            } catch (e) {
              failed += batch.length;
              console.error('⚠️ Error en último lote:', e.message);
            }
          }
          resolve();
        })
        .on('error', (error) => {
          console.error('❌ Error leyendo el archivo CSV:', error);
          reject(error);
        });
    });

    console.log('\n✅ ===== MIGRACIÓN REPORTES ODH COMPLETADA =====');
    console.log(`✅ Registros insertados: ${inserted}`);
    console.log(`❌ Registros fallidos: ${failed}`);
    console.log(`📊 Total procesado: ${totalRows}`);
    console.log(`🎯 Tasa de éxito: ${((inserted/totalRows)*100).toFixed(2)}%`);
    
    return { inserted, failed, total: totalRows };
    
  } catch (err) {
    console.error('💥 Error fatal durante la migración:', err.message);
    throw err;
  } finally {
    if (conn) conn.release();
    if (pool) await pool.end();
    console.log('🔚 Conexión a BD cerrada');
  }
}

// Ejecutar si es llamado directamente
if (require.main === module) {
  const csvFile = process.argv[2];
  importarReportesODH(csvFile).catch(err => {
    console.error('💥 Error en el proceso:', err);
    process.exit(1);
  });
}

module.exports = { importarReportesODH };