<?php
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
        return ($hora >= '19:00:00' || $hora <= '07:00:00');
    }
    
    // Sábado
    if ($dia_semana == 6) {
        return ($hora < '07:00:01' || $hora >= '12:01:00');
    }
    
    // Domingo: todo el día es FDH
    if ($dia_semana == 7) {
        return true;
    }
    
    return false;
}

// Función para determinar el tipo de error en FDH
function determinarErrorFDH($fecha_apertura, $fecha_cierre, $festivos) {
    $aperturaFDH = esFueraDeHorario($fecha_apertura, $festivos);
    $cierreFDH = esFueraDeHorario($fecha_cierre, $festivos);
    
    if (!$aperturaFDH && !$cierreFDH) {
        return "Ambas fechas NO son FDH";
    } elseif (!$aperturaFDH) {
        return "Fecha apertura NO es FDH";
    } elseif (!$cierreFDH) {
        return "Fecha cierre NO es FDH";
    }
    
    return "Fechas FDH correctas";
}

// Función para eliminar duplicados basada en numero_ticket
function eliminarDuplicados($array) {
    $ticketsUnicos = [];
    $resultado = [];
    
    foreach ($array as $item) {
        $ticket = $item['numero_ticket'];
        if (!in_array($ticket, $ticketsUnicos)) {
            $ticketsUnicos[] = $ticket;
            $resultado[] = $item;
        }
    }
    
    return $resultado;
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
    if (!in_array($tableId, ['aplica_pdr_fuera_horario', 'corregir_fechas_fdh'])) {
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

    // 6. CONSULTA PRINCIPAL - Adaptada para MinTIC ODH con DISTINCT para evitar duplicados
    $sql = "SELECT DISTINCT
        numero_ticket, 
        fecha_apertura, 
        fecha_cierre, 
        motivo_parada,
        tipificacion_falla,
        bandera
    FROM reportes_odh 
    WHERE motivo_parada IS NOT NULL 
        AND motivo_parada != '' 
        AND prioridad IN ('1', '2')
        AND LOWER(motivo_parada) NOT LIKE '%energ%'
        AND LOWER(motivo_parada) NOT LIKE '%Fibra Daños Animales%'
        AND LOWER(motivo_parada) NOT LIKE '%Trabajo Programado%'
    ORDER BY fecha_apertura DESC";

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

    // 8. Aplicar lógica de FDH para MinTIC ODH
    $aplicaPDR = [];       // Aplica para PDR "Fuera de Horario"
    $corregirFDH = [];     // Corregir Fechas de FDH

    foreach ($allData as $row) {
        $fecha_apertura = $row['fecha_apertura'];
        $fecha_cierre = $row['fecha_cierre'];
        $motivo_parada = strtolower($row['motivo_parada'] ?? '');
        
        // Verificar si ambas fechas están en FDH
        $aperturaFDH = esFueraDeHorario($fecha_apertura, $festivos);
        $cierreFDH = esFueraDeHorario($fecha_cierre, $festivos);
        
        // Verificar si el motivo indica FDH
        $tieneIndicadorFDH = (strpos($motivo_parada, 'fuera de horario') !== false || 
                              strpos($motivo_parada, 'fdh') !== false);

        // LÓGICA PARA TABLA 1: Aplica para PDR "Fuera de Horario"
        // Casos que están en FDH pero no tienen indicador FDH en motivo_parada
        if ($aperturaFDH && $cierreFDH && !$tieneIndicadorFDH) {
            $aplicaPDR[] = [
                'numero_ticket' => $row['numero_ticket'],
                'fecha_apertura' => $fecha_apertura,
                'fecha_cierre' => $fecha_cierre,
                'motivo_parada' => $row['motivo_parada'],
                'tipificacion_falla' => $row['tipificacion_falla'] ?? ''
            ];
        }
        
        // LÓGICA PARA TABLA 2: Corregir Fechas de FDH
        // Casos que tienen indicador FDH pero las horas NO son de FDH
        if ($tieneIndicadorFDH && (!$aperturaFDH || !$cierreFDH)) {
            $errorTipo = determinarErrorFDH($fecha_apertura, $fecha_cierre, $festivos);
            
            $corregirFDH[] = [
                'numero_ticket' => $row['numero_ticket'],
                'fecha_apertura' => $fecha_apertura,
                'fecha_cierre' => $fecha_cierre,
                'motivo_parada' => $row['motivo_parada'],
                'tipificacion_falla' => $row['tipificacion_falla'] ?? '',
                'error_tipo' => $errorTipo
            ];
        }
    }

    // 9. ELIMINAR DUPLICADOS de ambos arrays
    $aplicaPDR = eliminarDuplicados($aplicaPDR);
    $corregirFDH = eliminarDuplicados($corregirFDH);

    // 10. Enviar respuesta según la tabla solicitada
    if ($tableId === 'aplica_pdr_fuera_horario') {
        sendJsonResponse(true, $aplicaPDR);
    } else {
        sendJsonResponse(true, $corregirFDH);
    }

} catch (Exception $e) {
    sendJsonResponse(false, [], 'Error: ' . $e->getMessage());
}
?>