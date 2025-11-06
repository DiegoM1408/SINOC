
<?php
// modules/comparacion_bases/compararbasesP_backend.php

// Establecer collation a nivel de conexión
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}

/**
 * Normaliza fechas de diferentes formatos a YYYY-MM-DD HH:ii:ss
 */
function normalizarFecha($fecha, $tipo) {
    if (empty($fecha) || $fecha === 'N/A' || $fecha === '0000-00-00 00:00:00') {
        return null;
    }
    
    $fecha = trim($fecha);
    
    // Para incidentes_etl: formato dd/mm/yy HH:ii:ss
    if ($tipo === 'incidentes') {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $fecha, $matches)) {
            $dia = $matches[1];
            $mes = $matches[2];
            $ano = '20' . $matches[3];
            $hora = $matches[4];
            $min = $matches[5];
            $seg = $matches[6];
            
            if (checkdate($mes, $dia, $ano)) {
                return "$ano-$mes-$dia $hora:$min:$seg";
            }
        }
    }
    
    // Para reportes: puede tener dos formatos
    if ($tipo === 'reportes') {
        // Formato 1: "YYYY-MM-DD HH:ii:ss"
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $fecha, $matches)) {
            $ano = $matches[1];
            $mes = $matches[2];
            $dia = $matches[3];
            
            if (checkdate($mes, $dia, $ano)) {
                return $fecha;
            }
        }
        
        // Formato 2: "dd/mm/YYYY HH:ii:ss"
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/', $fecha, $matches)) {
            $dia = $matches[1];
            $mes = $matches[2];
            $ano = $matches[3];
            $hora = $matches[4];
            $min = $matches[5];
            $seg = $matches[6];
            
            if (checkdate($mes, $dia, $ano)) {
                return "$ano-$mes-$dia $hora:$min:$seg";
            }
        }
        
        // Formato 3: "d/m/YYYY H:i" (sin segundos)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $fecha, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $ano = $matches[3];
            $hora = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            $min = $matches[5];
            
            if (checkdate($mes, $dia, $ano)) {
                return "$ano-$mes-$dia $hora:$min:00";
            }
        }
    }
    
    return null;
}

/**
 * Normaliza motivos eliminando espacios extra y estandarizando
 */
function normalizarMotivo($motivo) {
    if (empty($motivo)) {
        return $motivo;
    }
    
    // Convertir a string y trim
    $motivo = trim(strval($motivo));
    
    // Reemplazar múltiples espacios por uno solo
    $motivo = preg_replace('/\s+/', ' ', $motivo);
    
    // Estandarizar guiones con espacios
    $motivo = preg_replace('/\s*-\s*/', ' - ', $motivo);
    
    // Quitar espacios extra alrededor de guiones
    $motivo = preg_replace('/\s+\-\s+/', ' - ', $motivo);
    
    return $motivo;
}

/**
 * TABLA 1: Comparar casos (numero_ticket vs ID de incidente)
 * SOLO debe mostrar casos que NO existen en ambas tablas
 */
function compararCasos($conn) {
    // Obtener IDs de incidentes_etl (sin límite)
    $query_incidentes = "
    SELECT DISTINCT
        `ID de incidente`
    FROM 
        incidentes_csv.incidentes_etl
    WHERE 
        `Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND `ID Conocimiento` != 'KM3201'
        AND `Fecha inicio parada de reloj` IS NOT NULL
        AND `Fecha inicio parada de reloj` != ''
        AND `ID de incidente` IS NOT NULL
        AND `ID de incidente` != ''
    ";
    
    $result_incidentes = $conn->query($query_incidentes);
    if (!$result_incidentes) {
        return ["error" => "Error en incidentes: " . $conn->error];
    }
    
    // Obtener IDs de reportes (sin límite)
    $query_reportes = "
    SELECT DISTINCT
        numero_ticket
    FROM 
        incidentes_csv.reportes
    WHERE 
        prioridad IN ('1', '2')
        AND acciones_correctivas != 'KM3201'
        AND bandera = 'P'
        AND fecha_apertura IS NOT NULL
        AND fecha_apertura != ''
        AND numero_ticket IS NOT NULL
        AND numero_ticket != ''
    ";
    
    $result_reportes = $conn->query($query_reportes);
    if (!$result_reportes) {
        return ["error" => "Error en reportes: " . $conn->error];
    }
    
    // Construir sets de IDs
    $incidentes_ids = [];
    while ($row = $result_incidentes->fetch_assoc()) {
        $id = trim($row['ID de incidente']);
        if (!empty($id)) {
            $incidentes_ids[$id] = true;
        }
    }
    
    $reportes_ids = [];
    while ($row = $result_reportes->fetch_assoc()) {
        $id = trim($row['numero_ticket']);
        if (!empty($id)) {
            $reportes_ids[$id] = true;
        }
    }
    
    // Encontrar discrepancias - SOLO casos que faltan en una de las tablas
    $discrepancias = [];
    
    foreach ($incidentes_ids as $id => $val) {
        if (!isset($reportes_ids[$id])) {
            $discrepancias[] = [
                'numero_ticket' => $id,
                'en_incidentes_etl' => 'Sí',
                'en_reportes' => 'No',
                'observacion' => 'Falta en reportes'
            ];
        }
    }
    
    foreach ($reportes_ids as $id => $val) {
        if (!isset($incidentes_ids[$id])) {
            $discrepancias[] = [
                'numero_ticket' => $id,
                'en_incidentes_etl' => 'No',
                'en_reportes' => 'Sí',
                'observacion' => 'Falta en incidentes_etl'
            ];
        }
    }
    
    return $discrepancias;
}

/**
 * TABLA 2: Comparar fechas de inicio
 * SOLO para casos que existen en AMBAS tablas
 */
function compararFechasInicio($conn) {
    // Obtener datos de incidentes_etl (sin límite)
    $query_incidentes = "
    SELECT 
        `ID de incidente`,
        `Fecha inicio parada de reloj`
    FROM 
        incidentes_csv.incidentes_etl
    WHERE 
        `Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND `ID Conocimiento` != 'KM3201'
        AND `Fecha inicio parada de reloj` IS NOT NULL
        AND `Fecha inicio parada de reloj` != ''
        AND `ID de incidente` IS NOT NULL
        AND `ID de incidente` != ''
    ORDER BY `ID de incidente`
    ";
    
    $result_incidentes = $conn->query($query_incidentes);
    if (!$result_incidentes) {
        return ["error" => "Error en incidentes: " . $conn->error];
    }
    
    // Obtener datos de reportes (sin límite)
    $query_reportes = "
    SELECT 
        numero_ticket,
        fecha_apertura
    FROM 
        incidentes_csv.reportes
    WHERE 
        prioridad IN ('1', '2')
        AND acciones_correctivas != 'KM3201'
        AND bandera = 'P'
        AND fecha_apertura IS NOT NULL
        AND fecha_apertura != ''
        AND numero_ticket IS NOT NULL
        AND numero_ticket != ''
    ORDER BY numero_ticket
    ";
    
    $result_reportes = $conn->query($query_reportes);
    if (!$result_reportes) {
        return ["error" => "Error en reportes: " . $conn->error];
    }
    
    // Procesar datos de incidentes_etl
    $incidentes_data = [];
    while ($row = $result_incidentes->fetch_assoc()) {
        $id = trim($row['ID de incidente']);
        $fecha_original = $row['Fecha inicio parada de reloj'];
        $fecha_normalizada = normalizarFecha($fecha_original, 'incidentes');
        
        if ($fecha_normalizada && $id) {
            // Para casos duplicados, tomar la fecha más temprana
            if (!isset($incidentes_data[$id]) || $fecha_normalizada < $incidentes_data[$id]['fecha_norm']) {
                $incidentes_data[$id] = [
                    'fecha_original' => $fecha_original,
                    'fecha_norm' => $fecha_normalizada
                ];
            }
        }
    }
    
    // Procesar datos de reportes
    $reportes_data = [];
    $casos_duplicados = [];
    
    while ($row = $result_reportes->fetch_assoc()) {
        $id = trim($row['numero_ticket']);
        $fecha_original = $row['fecha_apertura'];
        $fecha_normalizada = normalizarFecha($fecha_original, 'reportes');
        
        if ($fecha_normalizada && $id) {
            // Detectar casos duplicados
            if (isset($reportes_data[$id])) {
                $casos_duplicados[$id] = true;
            }
            
            // Para casos duplicados, tomar la fecha más temprana
            if (!isset($reportes_data[$id]) || $fecha_normalizada < $reportes_data[$id]['fecha_norm']) {
                $reportes_data[$id] = [
                    'fecha_original' => $fecha_original,
                    'fecha_norm' => $fecha_normalizada
                ];
            }
        }
    }
    
    // Comparar SOLO casos que existen en AMBAS tablas
    $discrepancias = [];
    
    foreach ($incidentes_data as $id => $datos_inc) {
        if (isset($reportes_data[$id])) {
            $datos_rep = $reportes_data[$id];
            $fecha_inc_norm = $datos_inc['fecha_norm'];
            $fecha_rep_norm = $datos_rep['fecha_norm'];
            
            // Comparar fechas normalizadas (con margen de 1 minuto para diferencias de redondeo)
            $diferencia = abs(strtotime($fecha_inc_norm) - strtotime($fecha_rep_norm));
            
            if ($diferencia > 60) { // Más de 1 minuto de diferencia
                $observacion = '';
                if (isset($casos_duplicados[$id])) {
                    $observacion .= 'Caso duplicado - ';
                }
                if (substr($id, 0, 2) === 'IM') {
                    $observacion .= 'Caso IM - ';
                }
                $observacion .= 'Fechas diferentes';
                
                $discrepancias[] = [
                    'numero_ticket' => $id,
                    'fecha_apertura' => $datos_rep['fecha_original'],
                    'Fecha inicio parada de reloj' => $datos_inc['fecha_original'],
                    'observacion' => trim($observacion)
                ];
            }
        }
    }
    
    return $discrepancias;
}

/**
 * TABLA 3: Comparar fechas de cierre
 * SOLO para casos que existen en AMBAS tablas
 */
function compararFechasCierre($conn) {
    // Obtener datos de incidentes_etl (sin límite)
    $query_incidentes = "
    SELECT 
        `ID de incidente`,
        `Fecha fin parada de reloj`
    FROM 
        incidentes_csv.incidentes_etl
    WHERE 
        `Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND `ID Conocimiento` != 'KM3201'
        AND `Fecha fin parada de reloj` IS NOT NULL
        AND `Fecha fin parada de reloj` != ''
        AND `ID de incidente` IS NOT NULL
        AND `ID de incidente` != ''
    ORDER BY `ID de incidente`
    ";
    
    $result_incidentes = $conn->query($query_incidentes);
    if (!$result_incidentes) {
        return ["error" => "Error en incidentes: " . $conn->error];
    }
    
    // Obtener datos de reportes (sin límite)
    $query_reportes = "
    SELECT 
        numero_ticket,
        fecha_cierre
    FROM 
        incidentes_csv.reportes
    WHERE 
        prioridad IN ('1', '2')
        AND acciones_correctivas != 'KM3201'
        AND bandera = 'P'
        AND fecha_cierre IS NOT NULL
        AND fecha_cierre != ''
        AND numero_ticket IS NOT NULL
        AND numero_ticket != ''
    ORDER BY numero_ticket
    ";
    
    $result_reportes = $conn->query($query_reportes);
    if (!$result_reportes) {
        return ["error" => "Error en reportes: " . $conn->error];
    }
    
    // Procesar datos de incidentes_etl
    $incidentes_data = [];
    while ($row = $result_incidentes->fetch_assoc()) {
        $id = trim($row['ID de incidente']);
        $fecha_original = $row['Fecha fin parada de reloj'];
        $fecha_normalizada = normalizarFecha($fecha_original, 'incidentes');
        
        if ($fecha_normalizada && $id) {
            // Para casos duplicados, tomar la fecha más tardía
            if (!isset($incidentes_data[$id]) || $fecha_normalizada > $incidentes_data[$id]['fecha_norm']) {
                $incidentes_data[$id] = [
                    'fecha_original' => $fecha_original,
                    'fecha_norm' => $fecha_normalizada
                ];
            }
        }
    }
    
    // Procesar datos de reportes
    $reportes_data = [];
    while ($row = $result_reportes->fetch_assoc()) {
        $id = trim($row['numero_ticket']);
        $fecha_original = $row['fecha_cierre'];
        $fecha_normalizada = normalizarFecha($fecha_original, 'reportes');
        
        if ($fecha_normalizada && $id) {
            // Para casos duplicados, tomar la fecha más tardía
            if (!isset($reportes_data[$id]) || $fecha_normalizada > $reportes_data[$id]['fecha_norm']) {
                $reportes_data[$id] = [
                    'fecha_original' => $fecha_original,
                    'fecha_norm' => $fecha_normalizada
                ];
            }
        }
    }
    
    // Comparar SOLO casos que existen en AMBAS tablas
    $discrepancias = [];
    
    foreach ($incidentes_data as $id => $datos_inc) {
        if (isset($reportes_data[$id])) {
            $datos_rep = $reportes_data[$id];
            $fecha_inc_norm = $datos_inc['fecha_norm'];
            $fecha_rep_norm = $datos_rep['fecha_norm'];
            
            // Comparar fechas normalizadas (con margen de 1 minuto para diferencias de redondeo)
            $diferencia = abs(strtotime($fecha_inc_norm) - strtotime($fecha_rep_norm));
            
            if ($diferencia > 60) { // Más de 1 minuto de diferencia
                $observacion = 'Fechas diferentes';
                
                $discrepancias[] = [
                    'numero_ticket' => $id,
                    'fecha_cierre' => $datos_rep['fecha_original'],
                    'Fecha fin parada de reloj' => $datos_inc['fecha_original'],
                    'observacion' => $observacion
                ];
            }
        }
    }
    
    return $discrepancias;
}

/**
 * TABLA 4: Comparar motivos de parada
 * SOLO para casos que existen en AMBAS tablas
 */
function compararMotivos($conn) {
    // Tabla de mapeo CORREGIDA - basada en tu tabla
    $mapeo_motivos = [
        'Atribuible Terceros Falla Energía Comercial Zona' => 'Atribuible Terceros - Falla Energía Comercial Zona',
        'Atribuible Terceros Falla Energía Eléctrica en CD' => 'Atribuible Terceros - Falla Energía Eléctrica en CD',
        'Atribuible Terceros Falla Infraestructura física CD' => 'Atribuible Terceros - Falla Infraestructura física CD',
        'Atribuible Terceros Imposibilidad accesos al CD' => 'Atribuible Terceros - Imposibilidad accesos al CD',
        'Atribuible Terceros Imposibilidad accesos BTS Site' => 'Atribuible Terceros - Imposibilidad accesos BTS Site',
        'Atribuible Terceros Imposibilidad programar actividad' => 'Atribuible Terceros - Imposibilidad programar actividad',
        'Atribuible Terceros Sin contacto con CD' => 'Atribuible Terceros - Sin contacto con CD',
        'Caso Fortuito Atenuación señal FO condiciones externas' => 'Caso Fortuito - Atenuación señal FO condiciones externas',
        'Caso Fortuito Daños cableado interno condiciones externas' => 'Caso Fortuito - Daños cableado interno condiciones externas',
        'Caso Fortuito Fibra Daños Animales y/o Humanos' => 'Caso Fortuito - Fibra Daños Animales y/o Humanos',
        'Caso Fortuito Manipulación elementos por Terceros' => 'Caso Fortuito - Manipulación elementos por Terceros',
        'Caso Fortuito Sitio temporizado por solicitud Terceros' => 'Caso Fortuito - Sitio temporizado por solicitud Terceros',
        'Continuidad servicio Instalaciones no disponibles Fuera de horario' => 'Continuidad servicio - Instalaciones no disponibles Fuera de horario',
        'Continuidad Servicio Trabajo Programado' => 'Continuidad Servicio - Trabajo Programado',
        'En proceso de reinstalación' => 'En proceso de reinstalación',
        'En proceso de reubicación' => 'En proceso de reubicación',
        'En proceso de traslado' => 'En proceso de traslado',
        'Fuerza mayor Energía Alternativa' => 'Fuerza mayor - Energía Alternativa',
        'Fuerza Mayor Fenómeno Atmosférico' => 'Fuerza Mayor - Fenómeno Atmosférico',
        'Fuerza Mayor Fenómeno Natural' => 'Fuerza Mayor - Fenómeno Natural',
        'Fuerza Mayor Hurto' => 'Fuerza Mayor - Hurto',
        'Fuerza Mayor Orden Público' => 'Fuerza Mayor - Orden Público',
        'Fuerza Mayor Vandalismo' => 'Fuerza Mayor - Vandalismo',
        'Fuerza mayor Ausencia suministros' => 'Fuerza mayor - Ausencia suministros'
    ];
    
    // Obtener datos de incidentes_etl (sin límite)
    $query_incidentes = "
    SELECT 
        `ID de incidente`,
        `Motivo parada de reloj`
    FROM 
        incidentes_csv.incidentes_etl
    WHERE 
        `Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND `ID Conocimiento` != 'KM3201'
        AND `Fecha inicio parada de reloj` IS NOT NULL
        AND `Fecha inicio parada de reloj` != ''
        AND `ID de incidente` IS NOT NULL
        AND `ID de incidente` != ''
    GROUP BY `ID de incidente`
    ";
    
    $result_incidentes = $conn->query($query_incidentes);
    if (!$result_incidentes) {
        return ["error" => "Error en incidentes: " . $conn->error];
    }
    
    // Obtener datos de reportes (sin límite)
    $query_reportes = "
    SELECT 
        numero_ticket,
        motivo_parada
    FROM 
        incidentes_csv.reportes
    WHERE 
        prioridad IN ('1', '2')
        AND acciones_correctivas != 'KM3201'
        AND bandera = 'P'
        AND fecha_apertura IS NOT NULL
        AND fecha_apertura != ''
        AND numero_ticket IS NOT NULL
        AND numero_ticket != ''
    GROUP BY numero_ticket
    ";
    
    $result_reportes = $conn->query($query_reportes);
    if (!$result_reportes) {
        return ["error" => "Error en reportes: " . $conn->error];
    }
    
    // Procesar datos
    $incidentes_data = [];
    while ($row = $result_incidentes->fetch_assoc()) {
        $id = trim($row['ID de incidente']);
        $motivo = normalizarMotivo($row['Motivo parada de reloj']);
        $incidentes_data[$id] = $motivo;
    }
    
    $reportes_data = [];
    while ($row = $result_reportes->fetch_assoc()) {
        $id = trim($row['numero_ticket']);
        $motivo = normalizarMotivo($row['motivo_parada']);
        $reportes_data[$id] = $motivo;
    }
    
    // Comparar SOLO casos que existen en AMBAS tablas
    $discrepancias = [];
    
    foreach ($incidentes_data as $id => $motivo_inc) {
        if (isset($reportes_data[$id])) {
            $motivo_rep = $reportes_data[$id];
            
            // Buscar el motivo mapeado correspondiente
            $motivo_mapeado = null;
            foreach ($mapeo_motivos as $clave_reporte => $valor_incidente) {
                $clave_normalizada = normalizarMotivo($clave_reporte);
                $valor_normalizado = normalizarMotivo($valor_incidente);
                
                // Si el motivo de reportes coincide con la clave del mapeo
                if ($motivo_rep === $clave_normalizada) {
                    $motivo_mapeado = $valor_normalizado;
                    break;
                }
            }
            
            // Si no se encontró mapeo, usar el mismo motivo
            if ($motivo_mapeado === null) {
                $motivo_mapeado = $motivo_rep;
            }
            
            // Verificar coincidencia (comparar ambos motivos normalizados)
            $coincide = ($motivo_inc === $motivo_mapeado);
            
            if (!$coincide) {
                $observacion = 'Motivo no coincide';
                
                $discrepancias[] = [
                    'numero_ticket' => $id,
                    'motivo_parada_reportes' => $motivo_rep,
                    'Motivo_parada_reloj_incidentes' => $motivo_inc,
                    'motivo_esperado' => $motivo_mapeado,
                    'observacion' => $observacion
                ];
            }
        }
    }
    
    return $discrepancias;
}

// Manejo de peticiones AJAX
if (isset($_GET['accion']) && isset($conn)) {
    header('Content-Type: application/json');
    
    switch ($_GET['accion']) {
        case 'comparar_casos':
            echo json_encode(compararCasos($conn));
            break;
        case 'comparar_fechas_inicio':
            echo json_encode(compararFechasInicio($conn));
            break;
        case 'comparar_fechas_cierre':
            echo json_encode(compararFechasCierre($conn));
            break;
        case 'comparar_motivos':
            echo json_encode(compararMotivos($conn));
            break;
        default:
            echo json_encode(["error" => "Acción no válida"]);
    }
    exit;
}
?>
