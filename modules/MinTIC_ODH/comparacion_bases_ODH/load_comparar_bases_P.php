<?php
// modules/comparacion_bases/compararbasesP_backend.php

// Función para comparar fechas de apertura con búsqueda de la más cercana
function compararFechasAperturaParadas($conn) {
    // CONSULTA MEJORADA - Busca la fecha más cercana dentro de un rango
    $query = "
    SELECT 
        r.numero_ticket AS 'ID_de_incidente',
        r.sem_id_beneficiario,
        r.fecha_apertura,
        i.`Fecha inicio parada de reloj`,
        TIMESTAMPDIFF(SECOND, r.fecha_apertura, i.`Fecha inicio parada de reloj`) AS diferencia_segundos
    FROM 
        incidentes_csv.reportes r
    INNER JOIN 
        incidentes_csv.incidentes i ON r.numero_ticket = i.`ID de incidente`
        AND r.sem_id_beneficiario = i.`ID Beneficiario`
    WHERE 
        r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'P'
        AND i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.fecha_apertura IS NOT NULL
        AND i.`Fecha inicio parada de reloj` IS NOT NULL
        AND DATE(r.fecha_apertura) = DATE(i.`Fecha inicio parada de reloj`)
        AND ABS(TIMESTAMPDIFF(SECOND, r.fecha_apertura, i.`Fecha inicio parada de reloj`)) > 60
    ORDER BY 
        ABS(TIMESTAMPDIFF(SECOND, r.fecha_apertura, i.`Fecha inicio parada de reloj`))
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta de apertura: " . $conn->error];
    }
    
    $discrepancias = [];
    $procesados = [];
    
    while ($row = $result->fetch_assoc()) {
        $clave = $row['ID_de_incidente'] . '_' . $row['sem_id_beneficiario'];
        
        // Solo tomar la primera ocurrencia (la más cercana) para cada incidente+beneficiario
        if (!isset($procesados[$clave])) {
            $procesados[$clave] = true;
            $discrepancias[] = [
                'ID de incidente' => $row['ID_de_incidente'],
                'sem_id_beneficiario' => $row['sem_id_beneficiario'],
                'fecha_apertura' => $row['fecha_apertura'],
                'Fecha inicio parada de reloj' => $row['Fecha inicio parada de reloj'],
                '_debug' => [
                    'diferencia_segundos' => abs($row['diferencia_segundos'])
                ]
            ];
        }
    }
    
    return $discrepancias;
}

// Función para comparar fechas de cierre con búsqueda de la más cercana
function compararFechasCierreParadas($conn) {
    // CONSULTA MEJORADA - Busca la fecha más cercana dentro de un rango
    $query = "
    SELECT 
        r.numero_ticket AS 'ID_de_incidente',
        r.sem_id_beneficiario,
        r.fecha_cierre,
        i.`Fecha fin parada de reloj`,
        TIMESTAMPDIFF(SECOND, r.fecha_cierre, i.`Fecha fin parada de reloj`) AS diferencia_segundos
    FROM 
        incidentes_csv.reportes r
    INNER JOIN 
        incidentes_csv.incidentes i ON r.numero_ticket = i.`ID de incidente`
        AND r.sem_id_beneficiario = i.`ID Beneficiario`
    WHERE 
        r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'P'
        AND i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.fecha_cierre IS NOT NULL
        AND i.`Fecha fin parada de reloj` IS NOT NULL
        AND DATE(r.fecha_cierre) = DATE(i.`Fecha fin parada de reloj`)
        AND ABS(TIMESTAMPDIFF(SECOND, r.fecha_cierre, i.`Fecha fin parada de reloj`)) > 60
    ORDER BY 
        ABS(TIMESTAMPDIFF(SECOND, r.fecha_cierre, i.`Fecha fin parada de reloj`))
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta de cierre: " . $conn->error];
    }
    
    $discrepancias = [];
    $procesados = [];
    
    while ($row = $result->fetch_assoc()) {
        $clave = $row['ID_de_incidente'] . '_' . $row['sem_id_beneficiario'];
        
        // Solo tomar la primera ocurrencia (la más cercana) para cada incidente+beneficiario
        if (!isset($procesados[$clave])) {
            $procesados[$clave] = true;
            $discrepancias[] = [
                'ID de incidente' => $row['ID_de_incidente'],
                'sem_id_beneficiario' => $row['sem_id_beneficiario'],
                'fecha_cierre' => $row['fecha_cierre'],
                'Fecha fin parada de reloj' => $row['Fecha fin parada de reloj'],
                '_debug' => [
                    'diferencia_segundos' => abs($row['diferencia_segundos'])
                ]
            ];
        }
    }
    
    return $discrepancias;
}

// Función para comparar motivos de parada
function compararMotivosParada($conn) {
    $query = "
    SELECT 
        r.numero_ticket AS 'ID_de_incidente',
        r.sem_id_beneficiario,
        r.motivo_parada,
        i.`Motivo parada de reloj`
    FROM 
        incidentes_csv.reportes r
    INNER JOIN 
        incidentes_csv.incidentes i ON r.numero_ticket = i.`ID de incidente`
        AND r.sem_id_beneficiario = i.`ID Beneficiario`
    WHERE 
        r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'P'
        AND i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.motivo_parada IS NOT NULL
        AND i.`Motivo parada de reloj` IS NOT NULL
        AND TRIM(r.motivo_parada) != TRIM(i.`Motivo parada de reloj`)
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta de motivos: " . $conn->error];
    }
    
    $discrepancias = [];
    
    while ($row = $result->fetch_assoc()) {
        $discrepancias[] = [
            'ID de incidente' => $row['ID_de_incidente'],
            'sem_id_beneficiario' => $row['sem_id_beneficiario'],
            'motivo_parada' => $row['motivo_parada'],
            'Motivo parada de reloj' => $row['Motivo parada de reloj']
        ];
    }
    
    return $discrepancias;
}

// Obtener datos si se solicita via AJAX
if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_fechas_apertura_paradas' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararFechasAperturaParadas($conn));
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_fechas_cierre_paradas' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararFechasCierreParadas($conn));
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_motivos_parada' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararMotivosParada($conn));
    exit;
}
?>