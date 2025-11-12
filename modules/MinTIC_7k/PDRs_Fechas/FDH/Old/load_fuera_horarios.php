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

// Festivos de Colombia 2025 y 2026
$festivos_colombia = [
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

try {
    $conn_incidentes = new mysqli($servername, $username, $password, "incidentes_csv");
    
    if ($conn_incidentes->connect_error) {
        throw new Exception("Conexión fallida: " . $conn_incidentes->connect_error);
    }

    $queries = [
        'aplica_pdr_fuera_horario' => "SELECT 
                r.numero_ticket,
                r.fecha_apertura,
                r.fecha_cierre,
                r.motivo_parada,
                r.tipificacion_falla
            FROM reportes r
            INNER JOIN (
                SELECT 
                    numero_ticket,
                    MIN(fecha_apertura) as primera_fecha_p
                FROM reportes 
                WHERE bandera = 'P'
                GROUP BY numero_ticket
            ) primera_p ON r.numero_ticket = primera_p.numero_ticket 
            WHERE r.bandera = 'P' 
                AND r.fecha_apertura = primera_p.primera_fecha_p
                AND r.prioridad IN ('1', '2')
                AND r.motivo_parada != 'Continuidad servicio Instalaciones no disponibles  Fuera de horario'
                AND r.motivo_parada != 'Continuidad servicio Instalaciones no disponibles Fuera de horario'
                AND r.motivo_parada != 'Continuidad servicio  Instalaciones no disponibles  Fuera de horario' 
                AND r.motivo_parada NOT LIKE '%Energía%'
                AND r.motivo_parada NOT LIKE '%Energia%'
                AND r.motivo_parada NOT LIKE '%ENERGÍA%'
                AND r.motivo_parada NOT LIKE '%ENERGIA%'
                AND r.motivo_parada NOT LIKE '%energía%'
                AND r.motivo_parada NOT LIKE '%energia%'
            ORDER BY r.fecha_apertura DESC
            LIMIT 100",

        'corregir_fechas_fdh' => "SELECT 
                r.numero_ticket,
                r.fecha_apertura,
                r.fecha_cierre,
                r.motivo_parada,
                r.tipificacion_falla
            FROM reportes r
            INNER JOIN (
                SELECT 
                    numero_ticket,
                    COUNT(*) as total_paradas
                FROM reportes 
                WHERE bandera = 'P'
                GROUP BY numero_ticket
                HAVING total_paradas = 1
            ) paradas_unicas ON r.numero_ticket = paradas_unicas.numero_ticket
            WHERE r.bandera = 'P' 
                AND r.prioridad IN ('1', '2')
                AND (r.motivo_parada = 'Continuidad servicio Instalaciones no disponibles  Fuera de horario'
                     OR r.motivo_parada = 'Continuidad servicio Instalaciones no disponibles Fuera de horario'
                     OR r.motivo_parada = 'Continuidad servicio  Instalaciones no disponibles  Fuera de horario')
                AND r.motivo_parada NOT LIKE '%Energía%'
                AND r.motivo_parada NOT LIKE '%Energia%'
                AND r.motivo_parada NOT LIKE '%ENERGÍA%'
                AND r.motivo_parada NOT LIKE '%ENERGIA%'
                AND r.motivo_parada NOT LIKE '%energía%'
                AND r.motivo_parada NOT LIKE '%energia%'
            ORDER BY r.fecha_apertura DESC
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
        if ($tableId === 'aplica_pdr_fuera_horario') {
            // Usar función estricta (hasta 7:00 sin segundos)
            if (esFueraDeHorarioEstricto($row['fecha_apertura'], $festivos_colombia)) {
                $rows[] = $row;
            }
        } else if ($tableId === 'corregir_fechas_fdh') {
            // Usar función incluyente (hasta 7:00:00 con segundos)
            $apertura_es_fdh = esFueraDeHorarioIncluyente($row['fecha_apertura'], $festivos_colombia);
            $cierre_es_fdh = !empty($row['fecha_cierre']) && $row['fecha_cierre'] != 'NULL' ? esFueraDeHorarioIncluyente($row['fecha_cierre'], $festivos_colombia) : true;
            
            // Si alguna de las fechas no es FDH, incluir el registro
            if (!$apertura_es_fdh || !$cierre_es_fdh) {
                $row['error_tipo'] = '';
                if (!$apertura_es_fdh) $row['error_tipo'] .= 'Apertura no FDH ';
                if (!$cierre_es_fdh) $row['error_tipo'] .= 'Cierre no FDH';
                $rows[] = $row;
            }
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

// Función para "Aplica para PDR Fuera de Horario" - Hasta 7:00 (sin segundos)
function esFueraDeHorarioEstricto($fecha, $festivos) {
    if (empty($fecha) || $fecha == 'NULL') return false;
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return false;
    
    $dia_semana = date('N', $timestamp); // 1=Lunes, 7=Domingo
    $hora = date('H:i', $timestamp);
    $fecha_str = date('Y-m-d', $timestamp);
    
    // Verificar si es festivo
    if (in_array($fecha_str, $festivos)) {
        return true;
    }
    
    // Lunes a Viernes: 9pm a 7am del día siguiente
    if ($dia_semana >= 1 && $dia_semana <= 5) {
        return ($hora >= '21:00' || $hora < '07:00');
    }
    
    // Sábado: 
    // - De 00:00 a 07:00 (continuación del viernes por la noche)
    // - De 12:01 en adelante (todo el sábado después del mediodía)
    if ($dia_semana == 6) {
        return ($hora < '07:00' || $hora >= '12:01');
    }
    
    // Domingo: todo el día es FDH
    if ($dia_semana == 7) {
        return true;
    }
    
    return false;
}

// Función para "Corregir Fechas de FDH" - Hasta 7:00:00 (con segundos)
function esFueraDeHorarioIncluyente($fecha, $festivos) {
    if (empty($fecha) || $fecha == 'NULL') return false;
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return false;
    
    $dia_semana = date('N', $timestamp); // 1=Lunes, 7=Domingo
    $hora = date('H:i:s', $timestamp); // Incluir segundos
    $fecha_str = date('Y-m-d', $timestamp);
    
    // Verificar si es festivo
    if (in_array($fecha_str, $festivos)) {
        return true;
    }
    
    // Lunes a Viernes: 9pm a 7am del día siguiente
    if ($dia_semana >= 1 && $dia_semana <= 5) {
        return ($hora >= '21:00:00' || $hora <= '07:00:00');
    }
    
    // Sábado: 
    // - De 00:00 a 07:00 (continuación del viernes por la noche)
    // - De 12:01 en adelante (todo el sábado después del mediodía)
    if ($dia_semana == 6) {
        return ($hora < '07:00:00' || $hora >= '12:01:00');
    }
    
    // Domingo: todo el día es FDH
    if ($dia_semana == 7) {
        return true;
    }
    
    return false;
}
?>