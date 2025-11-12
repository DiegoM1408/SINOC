<?php
session_start();
include("../../../includes/db.php");

if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['table'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tabla no especificada']);
    exit;
}

$tableId = $_GET['table'];
header('Content-Type: application/json');

try {
    $conn_incidentes = new mysqli($servername, $username, $password, "incidentes_csv");
    
    if ($conn_incidentes->connect_error) {
        throw new Exception("Conexión fallida: " . $conn_incidentes->connect_error);
    }

    $queries = [
        'interrupcion_mayor_apertura' => "SELECT `ID de incidente`, `Inicio de la interrupción de servicio`, `Fecha/hora de apertura` 
                                        FROM incidentes 
                                        WHERE `Grupo de asignación` = 'EYN - NOCMINTIC' 
                                        AND `ID Conocimiento` != 'KM3201'
                                        AND Prioridad IN ('1 - Crítica', '2 - Alta')
                                        AND `Inicio de la interrupción de servicio` > `Fecha/hora de apertura`
                                        AND NOT (
                                            DATE(`Fecha/hora de apertura`) = DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                            AND TIME(`Fecha/hora de apertura`) BETWEEN '00:00:00' AND '01:00:00'
                                            AND DATE(`Inicio de la interrupción de servicio`) = DATE(`Fecha/hora de apertura`)
                                            AND TIME(`Inicio de la interrupción de servicio`) = '01:00:00'
                                        )
                                        ORDER BY `Fecha/hora de apertura` DESC
                                        LIMIT 100",
        
        'mes_diferente' => "SELECT `ID de incidente`, `Inicio de la interrupción de servicio`, `Fecha/hora de apertura` 
                           FROM incidentes 
                           WHERE `Grupo de asignación` = 'EYN - NOCMINTIC' 
                           AND Prioridad IN ('1 - Crítica', '2 - Alta')
                           AND `ID Conocimiento` != 'KM3201'
                           AND MONTH(`Inicio de la interrupción de servicio`) != MONTH(`Fecha/hora de apertura`)
                           ORDER BY `Fecha/hora de apertura` DESC
                           LIMIT 100",
        
        'fechas_blanco_p' => "SELECT numero_ticket, fecha_apertura, fecha_cierre 
                             FROM reportes 
                             WHERE bandera = 'P' 
                             AND acciones_correctivas != 'KM3201'
                             AND estado IN ('Closed','Reopen', 'Resolved')
                             AND prioridad IN ('1', '2')
                             AND (fecha_apertura IS NULL OR fecha_apertura = '' OR fecha_cierre IS NULL OR fecha_cierre = '')
                             ORDER BY fecha_apertura DESC
                             LIMIT 100",
        
        'fechas_blanco_o' => "SELECT numero_ticket, fecha_apertura, fecha_cierre 
                             FROM reportes 
                             WHERE bandera = 'O' 
                             AND prioridad IN ('1', '2')
                             AND acciones_correctivas != 'KM3201'
                             AND estado IN ('Closed','Reopen', 'Resolved')
                             AND (fecha_apertura IS NULL OR fecha_apertura = '' OR fecha_cierre IS NULL OR fecha_cierre = '')
                             ORDER BY fecha_apertura DESC
                             LIMIT 100",
        
        'tiempos_muertos' => "SELECT 
                                r_o.numero_ticket,
                                r_o.fecha_apertura as fecha_apertura_caso,
                                r_o.fecha_cierre as fecha_cierre_caso,
                                r_p.motivo_parada
                            FROM reportes r_o
                            LEFT JOIN reportes r_p ON r_o.numero_ticket = r_p.numero_ticket AND r_p.bandera = 'P'
                            WHERE r_o.bandera = 'O'
                                AND r_o.acciones_correctivas != 'KM3201'
                                AND r_o.prioridad IN ('1', '2')
                                AND r_o.fecha_apertura IS NOT NULL
                                AND r_o.fecha_apertura != ''
                            GROUP BY r_o.numero_ticket, r_o.fecha_apertura, r_o.fecha_cierre, r_p.motivo_parada
                            ORDER BY r_o.numero_ticket
                            LIMIT 100"
    ];

    if (!isset($queries[$tableId])) {
        throw new Exception('Tabla no válida');
    }

    $sql = $queries[$tableId];
    $result = $conn_incidentes->query($sql);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . $conn_incidentes->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Para tiempos muertos, procesamos los datos
        if ($tableId === 'tiempos_muertos') {
            $row = procesarTiemposMuertos($row, $conn_incidentes);
            if ($row && !empty($row['tiempos_muertos'])) {
                $rows[] = $row;
            }
        } else {
            $rows[] = $row;
        }
    }

    $conn_incidentes->close();
    
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'count' => count($rows)
    ]);
    
} catch (Exception $e) {
    if (isset($conn_incidentes)) {
        $conn_incidentes->close();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function procesarTiemposMuertos($row, $conn) {
    $numero_ticket = $row['numero_ticket'];
    
    // Obtener todas las paradas para este ticket
    $sql_paradas = "SELECT fecha_apertura, fecha_cierre 
                   FROM reportes 
                   WHERE numero_ticket = ? 
                   AND bandera = 'P'
                   AND fecha_apertura IS NOT NULL 
                   AND fecha_apertura != ''
                   ORDER BY fecha_apertura";
    
    $stmt = $conn->prepare($sql_paradas);
    $stmt->bind_param("s", $numero_ticket);
    $stmt->execute();
    $result_paradas = $stmt->get_result();
    
    $eventos = [];
    $tiempos_muertos = [];
    
    // Agregar apertura del caso
    if (!empty($row['fecha_apertura_caso'])) {
        $eventos[] = ['tipo' => 'apertura_caso', 'fecha' => $row['fecha_apertura_caso'], 'es_inicio' => true];
    }
    
    // Procesar paradas
    while ($parada = $result_paradas->fetch_assoc()) {
        if (!empty($parada['fecha_apertura'])) {
            $eventos[] = ['tipo' => 'apertura_parada', 'fecha' => $parada['fecha_apertura'], 'es_inicio' => true];
        }
        if (!empty($parada['fecha_cierre']) && $parada['fecha_cierre'] != 'NULL') {
            $eventos[] = ['tipo' => 'cierre_parada', 'fecha' => $parada['fecha_cierre'], 'es_inicio' => false];
        }
    }
    
    // Agregar cierre del caso
    if (!empty($row['fecha_cierre_caso']) && $row['fecha_cierre_caso'] != 'NULL') {
        $eventos[] = ['tipo' => 'cierre_caso', 'fecha' => $row['fecha_cierre_caso'], 'es_inicio' => false];
    }
    
    // Ordenar eventos por fecha
    usort($eventos, function($a, $b) {
        return strtotime($a['fecha']) - strtotime($b['fecha']);
    });
    
    // Detectar tiempos muertos
    for ($i = 0; $i < count($eventos) - 1; $i++) {
        $evento_actual = $eventos[$i];
        $evento_siguiente = $eventos[$i + 1];
        
        if (!$evento_actual['es_inicio'] && $evento_siguiente['es_inicio']) {
            $diferencia = strtotime($evento_siguiente['fecha']) - strtotime($evento_actual['fecha']);
            
            if ($diferencia > 60) { // Más de 1 minuto
                $tiempos_muertos[] = [
                    'desde' => $evento_actual['fecha'],
                    'hasta' => $evento_siguiente['fecha'],
                    'minutos_muertos' => round($diferencia / 60, 2)
                ];
            }
        }
    }
    
    $stmt->close();
    
    if (!empty($tiempos_muertos)) {
        $row['tiempos_muertos'] = $tiempos_muertos;
        return $row;
    }
    
    return null;
}
?>