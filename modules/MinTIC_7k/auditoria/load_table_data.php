<?php
// modules/MinTIC_7k/auditoria/load_table_data.php
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

// Consultas optimizadas
$queries = [
    'km3201' => "SELECT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes
                WHERE bandera = 'O'
                AND acciones_correctivas = 'KM3201'
                AND (tipificacion_falla = 'Cancelled' OR tipificacion_falla = 'Resolved Successfully' OR tipificacion_falla IS NULL OR tipificacion_falla = '')
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    'tabla2' => "SELECT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes
                WHERE bandera = 'O'
                AND (acciones_correctivas != 'KM3201' AND acciones_correctivas IS NOT NULL AND acciones_correctivas != '')
                AND tipificacion_falla = 'Request Rejected'
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    'tabla3' => "SELECT numero_ticket, sem_id_beneficiario, sem_cod_servicio
                FROM reportes
                WHERE bandera = 'O' 
                AND acciones_correctivas != 'KM3201'
                AND (sem_id_beneficiario IS NULL OR sem_id_beneficiario = '')
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",

    'prioridad_p1' => "SELECT `ID de incidente`, Prioridad, Título
                FROM incidentes
                WHERE `Grupo de asignación` = 'EYN - NOCMINTIC'
                AND Prioridad = '1 - Crítica'
                AND Título NOT LIKE 'CAÍDA TOTAL CENTRO%' 
                AND Título NOT LIKE 'FALLA MASIVA CENTROS%' 
                AND Título NOT LIKE 'MEDICIÓN VELOCIDAD AFECTACIÓN DE AP%' 
                AND Título NOT LIKE 'MEDICIÓN DIRECTA DE VELOCIDAD EFECTIVA DE TRANSMISIÓN DE DATOS%'
                AND Título NOT LIKE 'BMC%'
                AND Título NOT LIKE 'Network Outage%' 
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'prioridad_p2' => "SELECT `ID de incidente`, Prioridad, Título
                FROM incidentes
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
                FROM incidentes
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
                FROM incidentes
                WHERE `Incidente Mayor` != 'TRUE'
                AND (Título LIKE 'FALLA MASIVA%' OR Título LIKE '% FALLA MASIVA%')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'fallas_masivas_un_ci' => "SELECT `ID de incidente`, Título, `CI Relacionados`
                FROM incidentes
                WHERE (Título LIKE 'FALLA MASIVA%' OR Título LIKE '% FALLA MASIVA%' OR Título LIKE 'FALLA MASIVA DEGRADACIÓN%' OR Título LIKE 'FALLA MASIVA DEGRADACION%')
                AND (`CI Relacionados` IS NOT NULL AND `CI Relacionados` != '')
                AND (`CI Relacionados` NOT LIKE '% %')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'responsabilidad_km' => "SELECT DISTINCT
                    r.numero_ticket, 
                    r.responsabilidad_por_ticket, 
                    r.acciones_correctivas, 
                    r.tipificacion_falla,
                    rk.causa_principal, 
                    rk.responsabilidad as responsabilidad_correcta
                FROM reportes r
                INNER JOIN responsabilidad_kms rk ON r.acciones_correctivas = rk.no_km
                WHERE r.bandera = 'O'
                AND r.prioridad IN ('1', '2')
                AND numero_ticket NOT LIKE 'SD%'
                AND NOT (r.responsabilidad_por_ticket <=> rk.responsabilidad)
                ORDER BY r.fecha_apertura DESC
                LIMIT 500",

    'kms_no_correspondientes' => "SELECT 
                    r.numero_ticket, 
                    r.acciones_correctivas, 
                    r.tipificacion_falla
                FROM reportes r
                LEFT JOIN responsabilidad_kms rk ON r.acciones_correctivas = rk.no_km
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
                FROM reportes r
                LEFT JOIN base_cis bc_id ON r.sem_id_beneficiario = bc_id.`ID BENEFICIARIO`
                LEFT JOIN base_cis bc_cod ON r.sem_cod_servicio = bc_cod.`COD SERV`
                WHERE r.bandera = 'O'
                AND r.acciones_correctivas != 'KM3201'
                AND bc_id.`ID BENEFICIARIO` IS NULL
                AND (r.sem_id_beneficiario IS NOT NULL AND r.sem_id_beneficiario != '')
                GROUP BY r.numero_ticket, r.sem_id_beneficiario, r.sem_cod_servicio
                ORDER BY r.fecha_apertura DESC
                LIMIT 100",

    'comparacion_id_titulo' => "SELECT 'carga_personalizada' as tipo",

    'ids_descripcion_no_encontrados' => "SELECT 'carga_personalizada' as tipo"
];

// Verificar si la tabla existe en las consultas
if (!isset($queries[$tableId])) {
    echo json_encode(['success' => false, 'error' => 'Tabla no válida']);
    exit;
}

try {
    // Procesar tablas especiales
    switch ($tableId) {
        case 'comparacion_id_titulo':
            procesarComparacionIdTitulo($conn_ic);
            exit;
        case 'ids_descripcion_no_encontrados':
            procesarIdsDescripcionNoEncontrados($conn_ic);
            exit;
        default:
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
    }
    
} catch (Exception $e) {
    if (isset($conn_ic)) {
        $conn_ic->close();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Función para procesar comparación ID Beneficiario con Título - CORREGIDA
function procesarComparacionIdTitulo($conn_ic) {
    try {
        // Consulta para obtener incidentes con IDs en el título y sus reportes
        $sql = "SELECT 
                    i.`ID de incidente` as id_incidente,
                    i.Título as titulo,
                    r.sem_id_beneficiario
                FROM incidentes i
                INNER JOIN reportes r ON i.`ID de incidente` = r.numero_ticket
                WHERE i.Título LIKE '%ID BENEFICIARIO:%'
                AND r.sem_id_beneficiario IS NOT NULL 
                AND r.sem_id_beneficiario != ''";
                
        $stmt = $conn_ic->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $registros = [];
        
        while ($row = $result->fetch_assoc()) {
            $registros[] = $row;
        }
        $stmt->close();
        
        $resultados = [];
        
        foreach ($registros as $registro) {
            $titulo = $registro['titulo'];
            $id_beneficiario = $registro['sem_id_beneficiario'];
            $id_incidente = $registro['id_incidente'];
            
            // Extraer SOLO el primer ID del título después de "ID BENEFICIARIO:"
            $primer_id_en_titulo = extraerPrimerIdDeTitulo($titulo);
            
            // Solo mostrar si el primer ID del título NO coincide con el ID beneficiario
            if ($primer_id_en_titulo && $primer_id_en_titulo != $id_beneficiario) {
                $resultados[] = [
                    'id_incidente' => $id_incidente,
                    'titulo' => $titulo,
                    'sem_id_beneficiario' => $id_beneficiario,
                    'id_en_titulo' => $primer_id_en_titulo
                ];
            }
        }
        
        $conn_ic->close();
        
        echo json_encode([
            'success' => true,
            'rows' => $resultados,
            'count' => count($resultados)
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
}

// Función para extraer SOLO el primer ID del título
function extraerPrimerIdDeTitulo($titulo) {
    // Buscar el patrón "ID BENEFICIARIO: número" y capturar solo el primer número (antes de cualquier guión o espacio)
    if (preg_match('/ID BENEFICIARIO:\s*([0-9]+)/i', $titulo, $matches)) {
        return $matches[1]; // Retorna solo el primer ID encontrado (antes del primer guión)
    }
    return null;
}

// Función para procesar IDs en descripción no encontrados - CORREGIDA
function procesarIdsDescripcionNoEncontrados($conn_ic) {
    try {
        // Consulta usando la columna Descripción
        $sql = "SELECT 
                    `ID de incidente`,
                    `Descripción`
                FROM incidentes 
                WHERE (`Descripción` LIKE '%ID BENEFICIARIO:%')
                AND `Descripción` IS NOT NULL
                ORDER BY `Inicio de la interrupción de servicio` DESC";
                
        $stmt = $conn_ic->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $incidentes = [];
        
        while ($row = $result->fetch_assoc()) {
            $incidentes[] = $row;
        }
        $stmt->close();
        
        $resultados = [];
        
        foreach ($incidentes as $incidente) {
            $descripcion = $incidente['Descripción'];
            $id_incidente = $incidente['ID de incidente'];
            
            // Extraer TODOS los IDs de la descripción (incluyendo múltiples separados por "-")
            $ids_encontrados = extraerTodosIdsDeDescripcion($descripcion);
            
            if (!empty($ids_encontrados)) {
                $ids_no_encontrados = [];
                
                foreach ($ids_encontrados as $id) {
                    $existe_en_base = verificarIDEnBaseCIS($conn_ic, $id);
                    if (!$existe_en_base) {
                        $ids_no_encontrados[] = $id;
                    }
                }
                
                if (!empty($ids_no_encontrados)) {
                    $resultados[] = [
                        'id_beneficiario' => implode(', ', $ids_encontrados),
                        'descripcion' => $descripcion,
                        'id_no_encontrado' => implode(', ', $ids_no_encontrados),
                        'id_incidente' => $id_incidente
                    ];
                }
            }
        }
        
        $conn_ic->close();
        
        echo json_encode([
            'success' => true,
            'rows' => $resultados,
            'count' => count($resultados)
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
}

// Función para extraer TODOS los IDs de la descripción (incluyendo múltiples separados por "-")
function extraerTodosIdsDeDescripcion($descripcion) {
    $ids_extraidos = [];
    
    // Buscar el patrón "ID Beneficiario: número" (puede tener múltiples separados por "-")
    if (preg_match('/ID BENEFICIARIO:\s*([0-9\-]+)/i', $descripcion, $matches)) {
        $parte_ids = trim($matches[1]);
        
        // Separar por guiones si hay múltiples IDs
        if (strpos($parte_ids, '-') !== false) {
            $ids = explode('-', $parte_ids);
            foreach ($ids as $id) {
                $id_limpio = trim($id);
                if (preg_match('/^\d+$/', $id_limpio)) {
                    $ids_extraidos[] = $id_limpio;
                }
            }
        } else {
            // Solo un ID
            if (preg_match('/^\d+$/', $parte_ids)) {
                $ids_extraidos[] = $parte_ids;
            }
        }
    }
    
    return $ids_extraidos;
}

// Función para verificar en base_cis
function verificarIDEnBaseCIS($conn_ic, $id_beneficiario) {
    $sql = "SELECT COUNT(*) as count FROM base_cis WHERE `ID BENEFICIARIO` = ?";
    $stmt = $conn_ic->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('s', $id_beneficiario);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}
?>