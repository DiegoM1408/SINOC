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
                -- Excluir específicamente el motivo de Fuera de Horario
                AND r.motivo_parada != 'Continuidad servicio  Instalaciones no disponibles  Fuera de horario'
                AND r.motivo_parada NOT LIKE '%Energía%'
                AND r.motivo_parada NOT LIKE '%Energia%'
                AND r.motivo_parada NOT LIKE '%ENERGÍA%'
                AND r.motivo_parada NOT LIKE '%ENERGIA%'
                AND r.motivo_parada NOT LIKE '%energía%'
                AND r.motivo_parada NOT LIKE '%energia%'
            ORDER BY r.fecha_apertura DESC
            LIMIT 500",

        'corregir_fechas_fdh' => "SELECT 
                r.numero_ticket,
                r.fecha_apertura,
                r.fecha_cierre,
                r.motivo_parada,
                r.tipificacion_falla
            FROM reportes r
            WHERE r.bandera = 'P' 
                AND r.prioridad IN ('1', '2')
                -- Incluir específicamente el motivo exacto con los espacios
                AND r.motivo_parada = 'Continuidad servicio  Instalaciones no disponibles  Fuera de horario'
                AND r.motivo_parada NOT LIKE '%Energía%'
                AND r.motivo_parada NOT LIKE '%Energia%'
                AND r.motivo_parada NOT LIKE '%ENERGÍA%'
                AND r.motivo_parada NOT LIKE '%ENERGIA%'
                AND r.motivo_parada NOT LIKE '%energía%'
                AND r.motivo_parada NOT LIKE '%energia%'
            ORDER BY r.fecha_apertura DESC
            LIMIT 500"
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
            // Caso 1: No tiene motivo FDH pero SÍ está en horario FDH
            if (debeAplicarParaFDH($row, $festivos_colombia)) {
                $rows[] = $row;
            }
        } else if ($tableId === 'corregir_fechas_fdh') {
            // Caso 2: Tiene motivo FDH pero NO está en horario FDH
            if (esErrorFDH($row, $festivos_colombia)) {
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

// Función para CASO 1: Aplica PDR Fuera Horario
// Traer casos donde NO tiene motivo "Fuera de Horario" pero SÍ está en horario FDH
function debeAplicarParaFDH($row, $festivos) {
    // Verificar si la fecha de apertura está en horario FDH
    return esFueraDeHorario($row['fecha_apertura'], $festivos);
}

// Función para CASO 2: Corregir Fechas FDH  
// Traer casos donde SÍ tiene motivo "Fuera de Horario" pero NO está en horario FDH
function esErrorFDH($row, $festivos) {
    // Verificar si la fecha de apertura NO está en horario FDH
    return !esFueraDeHorario($row['fecha_apertura'], $festivos);
}

// Función para verificar si una fecha está en horario FDH
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
    
    // Lunes a Viernes (1-5): FDH = 9pm - 7am
    if ($dia_semana >= 1 && $dia_semana <= 5) {
        return ($hora >= '21:00:00' || $hora < '07:00:00');
    }
    
    // Sábado (6): FDH = desde 12pm hasta 7am del lunes
    if ($dia_semana == 6) {
        return ($hora >= '12:00:00');
    }
    
    // Domingo (7): Todo el día es FDH
    if ($dia_semana == 7) {
        return true;
    }
    
    return false;
}
?>