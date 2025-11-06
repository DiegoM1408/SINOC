<?php
// modules/MinTIC_7k/SDs/back_SDs_7K.php

function obtenerDatosSDs() {
    global $conn_ic;
    
    $tablas_data = [];
    
    // Verificar conexión
    if (!isset($conn_ic) || $conn_ic->connect_error) {
        return ['error' => "Error de conexión: " . ($conn_ic->connect_error ?? "Desconocido")];
    }
    
    // Definir consultas para SD con KM's
    $queries = [
        'sds_sin_km' => "SELECT numero_ticket, estado, acciones_correctivas
                        FROM reportes 
                        WHERE numero_ticket LIKE '%SD%'
                        AND estado = 'Closed'
                        AND (acciones_correctivas IS NULL OR acciones_correctivas = '')
                        ORDER BY numero_ticket DESC
                        LIMIT 100",
        
        'kms_no_correspondientes' => "SELECT r.numero_ticket, r.estado, r.acciones_correctivas
                        FROM reportes r
                        LEFT JOIN responsabilidad_kms rk ON r.acciones_correctivas = rk.no_km
                        WHERE r.numero_ticket LIKE '%SD%'
                        AND r.estado = 'Closed'
                        AND rk.no_km IS NULL
                        AND (r.acciones_correctivas IS NOT NULL AND r.acciones_correctivas != '')
                        ORDER BY r.numero_ticket DESC
                        LIMIT 100",
        
        'km3201_sin_cancelled' => "SELECT numero_ticket, estado, acciones_correctivas, tipificacion_falla
                        FROM reportes
                        WHERE numero_ticket LIKE '%SD%'
                        AND acciones_correctivas = 'KM3201'
                        AND estado = 'Closed'
                        AND (tipificacion_falla != 'Cancelled' OR tipificacion_falla IS NULL)
                        ORDER BY numero_ticket DESC
                        LIMIT 100",
        
        'otros_kms_sin_fulfilled' => "SELECT numero_ticket, estado, acciones_correctivas, tipificacion_falla
                        FROM reportes
                        WHERE numero_ticket LIKE '%SD%'
                        AND acciones_correctivas != 'KM3201'
                        AND estado = 'Closed'
                        AND (acciones_correctivas IS NOT NULL AND acciones_correctivas != '')
                        AND (tipificacion_falla != 'Fulfilled' OR tipificacion_falla IS NULL)
                        ORDER BY numero_ticket DESC
                        LIMIT 100",
        
        // Tomamos incidentes_sd_7k como base y verificamos qué existe en reportes
        'sds_no_aplica_portal' => "SELECT 
                            i.`ID de la interacción` as id_incidente,
                            i.Prioridad as prioridad,
                            i.Estado as estado,
                            r.numero_ticket as ticket_en_reportes
                        FROM incidentes_sd_7k i
                        INNER JOIN reportes r ON i.`ID de la interacción` = r.numero_ticket
                        WHERE i.Prioridad IN ('1 - Crítica', '2 - Alta')
                        AND i.Estado = 'En curso'
                        ORDER BY i.`ID de la interacción` DESC
                        LIMIT 100"
    ];
    
    // Ejecutar consultas
    foreach ($queries as $tabla_id => $sql) {
        $tablas_data[$tabla_id] = ['success' => false, 'rows' => [], 'error' => '', 'count' => 0];
        
        try {
            $stmt = $conn_ic->prepare($sql);
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $tablas_data[$tabla_id] = [
                    'success' => true,
                    'rows' => $rows,
                    'count' => count($rows)
                ];
                $stmt->close();
            } else {
                $tablas_data[$tabla_id]['error'] = "Error preparando consulta: " . $conn_ic->error;
            }
        } catch (Exception $e) {
            $tablas_data[$tabla_id]['error'] = $e->getMessage();
        }
    }
    
    return $tablas_data;
}

// Si se llama directamente, devolver JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    header('Content-Type: application/json');
    echo json_encode(obtenerDatosSDs());
    exit;
}
?>