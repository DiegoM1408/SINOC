<?php
// modules/comparacion_bases/comparar_bases_backend.php

function compararFechasApertura($conn) {
    // CONSULTA DIRECTA Y SIMPLE - SIN TIPO REGISTRO
    $query = "
    -- 1. CASOS O de Fecha de Inicio de Interrupción
    SELECT 
        r.numero_ticket AS 'ID de incidente',
        i.`Inicio de la interrupción de servicio`,
        r.fecha_apertura
    FROM 
        incidentes_csv.incidentes i
    INNER JOIN 
        incidentes_csv.reportes r ON i.`ID de incidente` = r.numero_ticket
    WHERE 
        i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'O'
        AND TIME_FORMAT(i.`Inicio de la interrupción de servicio`, '%H:%i') 
            != TIME_FORMAT(r.fecha_apertura, '%H:%i')
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta: " . $conn->error];
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

function compararFechasCierre($conn) {
    // CONSULTA PARA FECHAS DE CIERRE - SIN TIPO REGISTRO
    $query = "
    -- 2. CASOS O de Fecha de Fin de Interrupción
    SELECT 
        r.numero_ticket AS 'ID de incidente',
        i.`Fin de la interrupción de servicio`,
        r.fecha_cierre
    FROM 
        incidentes_csv.incidentes i
    INNER JOIN 
        incidentes_csv.reportes r ON i.`ID de incidente` = r.numero_ticket
    WHERE 
        i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'O'
        AND TIME_FORMAT(i.`Fin de la interrupción de servicio`, '%H:%i') 
            != TIME_FORMAT(r.fecha_cierre, '%H:%i')
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta: " . $conn->error];
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

function compararEstados($conn) {
    // CONSULTA PARA COMPARAR ESTADOS - SIN TIPO REGISTRO
    $query = "
    -- 3. CASOS O de Comparación de Estados
    SELECT 
        r.numero_ticket AS 'ID de incidente',
        i.`Estado del registro` AS estado_incidentes,
        r.estado AS estado_reportes
    FROM 
        incidentes_csv.incidentes i
    INNER JOIN 
        incidentes_csv.reportes r ON i.`ID de incidente` = r.numero_ticket
    WHERE 
        i.`Prioridad` IN ('1 - Crítica', '2 - Alta')
        AND i.`ID Conocimiento` != 'KM3201'
        AND r.prioridad IN (1, 2)
        AND r.acciones_correctivas != 'KM3201'
        AND r.bandera = 'O'
        AND i.`Estado del registro` != r.estado
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => "Error en la consulta: " . $conn->error];
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Obtener datos si se solicita via AJAX
if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_fechas_apertura' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararFechasApertura($conn));
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_fechas_cierre' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararFechasCierre($conn));
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] == 'comparar_estados' && isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(compararEstados($conn));
    exit;
}
?>