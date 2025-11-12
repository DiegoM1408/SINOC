<?php
// load_fuera_horarios_fixed.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('display_errors', 0);
ini_set('max_execution_time', 30);

function sendJsonResponse($success, $data = [], $error = '') {
    $response = [
        'success' => $success,
        'rows' => $data,
        'count' => count($data),
        'error' => $error
    ];
    
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Array de festivos (actualiza con tus fechas específicas)
$festivos = [
      // Festivos 2025
    '2025-01-01', '2025-01-06', '2025-03-24', '2025-04-17', '2025-04-18',
    '2025-05-01', '2025-06-02', '2025-06-23', '2025-06-30', '2025-07-20', 
    '2025-08-07', '2025-08-18', '2025-10-13', '2025-11-03','2025-11-17', 
    '2025-12-08', '2025-12-25', 
    // Festivos 2026
    '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03',
    '2026-05-01', '2026-05-25', '2026-06-15', '2026-06-22', '2026-06-29',
    '2026-07-20', '2026-08-07', '2026-08-17', '2026-10-12', '2026-11-02',
    '2026-11-16', '2026-12-08', '2026-12-25'
];

// Función para verificar si es fuera de horario (FDH)
function esFueraDeHorario($fecha, $festivos) {
    if (empty($fecha) || $fecha == 'NULL' || $fecha == '0000-00-00 00:00:00') return false;
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return false;
    
    $dia_semana = date('N', $timestamp); // 1=Lunes, 7=Domingo
    $hora = date('H:i:s', $timestamp);
    $fecha_str = date('Y-m-d', $timestamp);
    
    // Verificar si es festivo
    if (in_array($fecha_str, $festivos)) {
        return true;
    }
    
    // Lunes a Viernes: 9pm a 7am del día siguiente
    if ($dia_semana >= 1 && $dia_semana <= 5) {
        return ($hora >= '21:00:00' || $hora <= '07:00:00');
    }
    
    // Sábado
    if ($dia_semana == 6) {
        return ($hora < '07:00:01' || $hora >= '12:00:00');
    }
    
    // Domingo: todo el día es FDH
    if ($dia_semana == 7) {
        return true;
    }
    
    return false;
}

try {
    // 1. Verificar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['id_usuario'])) {
        sendJsonResponse(false, [], 'No autorizado');
    }

    // 2. Verificar parámetro
    $tableId = $_GET['table'] ?? '';
    if (!in_array($tableId, ['aplica_fdh', 'no_aplica_fdh'])) {
        sendJsonResponse(false, [], 'Tabla no válida');
    }

    // 3. Incluir archivo de conexión
    $db_path = __DIR__ . '/../../../includes/db.php';
    
    if (!file_exists($db_path)) {
        $alternative_paths = [
            __DIR__ . '/../../../../includes/db.php',
            __DIR__ . '/../../../../../includes/db.php',
            __DIR__ . '/includes/db.php'
        ];
        
        $found = false;
        foreach ($alternative_paths as $alt_path) {
            if (file_exists($alt_path)) {
                $db_path = $alt_path;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            sendJsonResponse(false, [], 'Archivo de conexión no encontrado');
        }
    }
    
    require_once $db_path;

    // 4. Verificar variables de conexión
    if (!isset($servername) || !isset($username) || !isset($password)) {
        sendJsonResponse(false, [], 'Configuración de BD incompleta');
    }

    // 5. Conectar a base de datos
    $conn = new mysqli($servername, $username, $password, "incidentes_csv");
    
    if ($conn->connect_error) {
        sendJsonResponse(false, [], 'Error conexión BD: ' . $conn->connect_error);
    }

    // 6. CONSULTA PRINCIPAL MODIFICADA - Solo primera parada (bandera = 'P' más antigua) y sin duplicados
    $sql = "SELECT 
        r1.numero_ticket, 
        r1.fecha_apertura, 
        r1.fecha_cierre, 
        r1.motivo_parada,
        r1.bandera
    FROM reportes r1
    INNER JOIN (
        SELECT 
            numero_ticket,
            MIN(fecha_apertura) as primera_fecha
        FROM reportes 
        WHERE bandera = 'P'
            AND motivo_parada IS NOT NULL 
            AND motivo_parada != '' 
            AND prioridad IN ('1', '2')
            AND LOWER(motivo_parada) NOT LIKE '%energ%'
        GROUP BY numero_ticket
    ) r2 ON r1.numero_ticket = r2.numero_ticket AND r1.fecha_apertura = r2.primera_fecha
    WHERE r1.bandera = 'P'
        AND r1.motivo_parada IS NOT NULL 
        AND r1.motivo_parada != '' 
        AND r1.prioridad IN ('1', '2')
        AND LOWER(r1.motivo_parada) NOT LIKE '%energ%'
    GROUP BY r1.numero_ticket, r1.fecha_apertura, r1.fecha_cierre, r1.motivo_parada
    ORDER BY r1.fecha_apertura DESC";

    $result = $conn->query($sql);
    
    if (!$result) {
        sendJsonResponse(false, [], 'Error en consulta: ' . $conn->error);
    }

    // 7. Procesar datos
    $allData = [];
    while ($row = $result->fetch_assoc()) {
        $allData[] = $row;
    }

    $conn->close();

    // 8. Eliminar duplicados basados en número_ticket (por si acaso)
    $uniqueData = [];
    $processedTickets = [];
    
    foreach ($allData as $row) {
        $ticket = $row['numero_ticket'];
        if (!in_array($ticket, $processedTickets)) {
            $uniqueData[] = $row;
            $processedTickets[] = $ticket;
        }
    }

    // 9. Aplicar lógica de FDH CORREGIDA
    $aplicaFDH = [];
    $noAplicaFDH = [];

    foreach ($uniqueData as $row) {
        $fecha_apertura = $row['fecha_apertura'];
        $fecha_cierre = $row['fecha_cierre'];
        $motivo_parada = strtolower($row['motivo_parada'] ?? '');
        
        // Verificar si ambas fechas están en FDH
        $aperturaFDH = esFueraDeHorario($fecha_apertura, $festivos);
        $cierreFDH = esFueraDeHorario($fecha_cierre, $festivos);
        
        // Verificar si el motivo indica FDH
        $tieneIndicadorFDH = (strpos($motivo_parada, 'fuera de horario') !== false || 
                              strpos($motivo_parada, 'fdh') !== false ||
                              strpos($motivo_parada, ' o ') !== false ||
                              strpos($motivo_parada, ' p ') !== false);

        // LÓGICA CORREGIDA:

        // Tabla "No Aplica para FDH": 
        // Casos que tienen motivo_parada = "Fuera de Horario" pero las horas NO son de FDH
        if ($tieneIndicadorFDH && (!$aperturaFDH || !$cierreFDH)) {
            $noAplicaFDH[] = [
                'numero_ticket' => $row['numero_ticket'],
                'fecha_apertura' => $fecha_apertura,
                'fecha_cierre' => $fecha_cierre,
                'motivo_parada' => $row['motivo_parada'],
                'apertura_es_fdh' => $aperturaFDH ? 'SÍ' : 'NO',
                'cierre_es_fdh' => $cierreFDH ? 'SÍ' : 'NO'
            ];
        }
        
        // Tabla "Aplica para FDH o Corrección de Horas FDH":
        // Casos que están en FDH pero no tienen indicador FDH, o necesitan corrección
        if ($aperturaFDH && $cierreFDH && !$tieneIndicadorFDH) {
            $aplicaFDH[] = [
                'numero_ticket' => $row['numero_ticket'],
                'fecha_apertura' => $fecha_apertura,
                'fecha_cierre' => $fecha_cierre,
                'motivo_parada' => $row['motivo_parada'],
                'bandera' => $row['bandera'] ?? '',
                'tipo' => 'Falta Indicador FDH'
            ];
        }
    }

    // 10. Eliminar duplicados finales en los resultados
    $aplicaFDH = array_values(array_unique($aplicaFDH, SORT_REGULAR));
    $noAplicaFDH = array_values(array_unique($noAplicaFDH, SORT_REGULAR));

    // 11. Enviar respuesta según la tabla solicitada
    if ($tableId === 'no_aplica_fdh') {
        sendJsonResponse(true, $noAplicaFDH);
    } else {
        sendJsonResponse(true, $aplicaFDH);
    }

} catch (Exception $e) {
    sendJsonResponse(false, [], 'Error: ' . $e->getMessage());
}
?>