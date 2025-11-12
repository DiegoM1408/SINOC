<?php
// modules/auditoria/load_table_data.php
session_start();
include("../../../includes/db.php");

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar parámetro
if (!isset($_GET['table'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tabla no especificada']);
    exit;
}

$tableId = $_GET['table'];
header('Content-Type: application/json');

// OPTIMIZACIÓN: Verificar conexión
if (!isset($conn_ic) || $conn_ic->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Consultas optimizadas (exactamente las mismas que en auditoria.php original)
$queries = [
    'km3201' => "SELECT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes_odh
                WHERE bandera = 'O'
                AND acciones_correctivas = 'KM24807'
                AND (tipificacion_falla = 'Cancelled' OR tipificacion_falla = 'Resolved Successfully' OR tipificacion_falla IS NULL OR tipificacion_falla = '')
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    'tabla2' => "SELECT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes_odh
                WHERE bandera = 'O'
                AND (acciones_correctivas != 'KM24807' AND acciones_correctivas IS NOT NULL AND acciones_correctivas != '')
                AND tipificacion_falla = 'Request Rejected'
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    'tabla3' => "SELECT numero_ticket, sem_id_beneficiario, sem_cod_servicio
                FROM reportes_odh
                WHERE bandera = 'O' 
                AND (sem_id_beneficiario IS NULL OR sem_id_beneficiario = '')
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",



    'prioridad_p1' => "SELECT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - NOCMINTIC'
                AND Prioridad = '1 - Crítica'
                AND Título NOT LIKE 'CAÍDA TOTAL CENTRO%' 
                AND Título NOT LIKE 'FALLA MASIVA CENTROS%' 
                AND Título NOT LIKE 'MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%' 
                AND Título NOT LIKE 'MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%'
                AND Título NOT LIKE 'BMC%'
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'prioridad_p2' => "SELECT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - NOCMINTIC'
                AND Prioridad = '2 - Alta'
                AND (Título NOT LIKE 'CAÍDA PARCIAL%' AND Título NOT LIKE '% CAÍDA PARCIAL%')
                AND (Título NOT LIKE 'SERVICIO INTERMITENTE%' AND Título NOT LIKE '% SERVICIO INTERMITENTE%')
                AND (Título NOT LIKE 'SIN TRAFICO EN AP%' AND Título NOT LIKE '% SIN TRAFICO EN AP%')
                AND Título NOT LIKE 'MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%' AND Título NOT LIKE '% MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%'
                AND Título NOT LIKE 'MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%' AND Título NOT LIKE '% MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%'
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'prioridad_p3' => "SELECT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - NOCMINTIC'
                AND Prioridad = '3 - Media'
                AND (
                    Título LIKE 'CAÍDA PARCIAL%' OR Título LIKE '% CAÍDA PARCIAL%'
                    OR Título LIKE 'SERVICIO INTERMITENTE%' OR Título LIKE '% SERVICIO INTERMITENTE%'
                    OR Título LIKE 'SIN TRAFICO EN AP%' OR Título LIKE '% SIN TRAFICO EN AP%'
                    OR Título LIKE 'CAÍDA TOTAL%' OR Título LIKE '% CAÍDA TOTAL%'
                    OR Título LIKE 'FALLA MASIVA CENTROS%' OR Título LIKE '% FALLA MASIVA CENTROS%'
                    OR Título LIKE 'MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%' OR Título LIKE '% MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%'
                    OR Título LIKE 'MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%' OR Título LIKE '% MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%'
                )
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'incidente_mayor' => "SELECT `ID de incidente`, Título, `Incidente Mayor`
                FROM incidentes_odh
                WHERE `Incidente Mayor` != 'TRUE'
                AND (Título LIKE 'FALLA MASIVA%' OR Título LIKE '% FALLA MASIVA%')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'fallas_masivas_un_ci' => "SELECT `ID de incidente`, Título, `CI Relacionados`
                FROM incidentes_odh
                WHERE (Título LIKE 'FALLA MASIVA%' OR Título LIKE '% FALLA MASIVA%' OR Título LIKE 'FALLA MASIVA DEGRADACIÓN%' OR Título LIKE 'FALLA MASIVA DEGRADACION%')
                AND (`CI Relacionados` IS NOT NULL AND `CI Relacionados` != '')
                AND (`CI Relacionados` NOT LIKE '% %')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

//Auditoria de Responsabilidad en KM's --- Si la responsabilidad es de Claro o del Cliente
    'responsabilidad_km' => "SELECT DISTINCT 
                    i.`ID de incidente` as numero_ticket, 
                    i.`Responsabilidad` as responsabilidad_por_ticket, 
                    i.`ID Conocimiento` as acciones_correctivas,
                    i.`Código de cierre` as tipificacion_falla,
                    rk.causa_principal,
                    rk.responsabilidad as responsabilidad_correcta
                FROM incidentes_odh i
                INNER JOIN responsabilidad_kms_odh_5g rk ON i.`ID Conocimiento` = rk.no_km
                WHERE i.Prioridad IN ('1 - Crítica', '2 - Alta')
                    AND NOT (i.`Responsabilidad` <=> rk.responsabilidad)
                ORDER BY i.`Inicio de la interrupción de servicio` DESC
                LIMIT 100",

//Auditoría de KM's NO correspondientes al proyecto --- Aquelos KM's que se colocan de manera erronea, o no exiten
    'kms_no_correspondientes' => "SELECT 
                    r.numero_ticket, 
                    r.acciones_correctivas, 
                    r.tipificacion_falla
                FROM reportes_odh r
                LEFT JOIN responsabilidad_kms_odh_5g rk ON r.acciones_correctivas = rk.no_km
                WHERE r.bandera = 'O'
                AND r.prioridad IN ('1', '2')
                AND rk.no_km IS NULL
                AND r.acciones_correctivas IS NOT NULL
                AND r.acciones_correctivas != ''
                ORDER BY r.fecha_apertura DESC
                LIMIT 100",

    'sitios_no_encontrados' => "SELECT 
                    r.numero_ticket, 
                    r.sem_id_beneficiario,
                    r.sem_cod_servicio,
                    MAX(bc_id.`ID BENEFICIARIO`) as id_beneficiario_por_id,
                    MAX(bc_cod.`ID BENEFICIARIO`) as id_beneficiario_por_codigo,
                    MAX(bc_cod.`COD SERV`) as cod_servicio_base_cis,
                    CASE 
                        WHEN MAX(bc_id.`ID BENEFICIARIO`) IS NOT NULL THEN 'Encontrado por ID'
                        WHEN MAX(bc_cod.`ID BENEFICIARIO`) IS NOT NULL THEN 'Comparte código con ID saliente'
                        ELSE 'No encontrado'
                    END as observacion
                FROM reportes_odh r
                LEFT JOIN base_cis_odh_5g bc_id ON r.sem_id_beneficiario = bc_id.`ID BENEFICIARIO`
                LEFT JOIN base_cis_odh_5g bc_cod ON r.sem_cod_servicio = bc_cod.`COD SERV`
                WHERE r.bandera = 'O'
                AND r.acciones_correctivas != 'KM24807'
                AND bc_id.`ID BENEFICIARIO` IS NULL
                AND (r.sem_id_beneficiario IS NOT NULL AND r.sem_id_beneficiario != '')
                GROUP BY r.numero_ticket, r.sem_id_beneficiario, r.sem_cod_servicio
                ORDER BY r.fecha_apertura DESC
                LIMIT 100"
];

// Verificar si la tabla existe en las consultas
if (!isset($queries[$tableId])) {
    echo json_encode(['success' => false, 'error' => 'Tabla no válida']);
    exit;
}

try {
    $sql = $queries[$tableId];
    $stmt = $conn_ic->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conn_ic->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    $conn_ic->close();
    
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'count' => count($rows)
    ]);
    
} catch (Exception $e) {
    if (isset($conn_ic)) {
        $conn_ic->close();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>