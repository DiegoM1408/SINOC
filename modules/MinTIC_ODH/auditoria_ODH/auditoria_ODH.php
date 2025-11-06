<?php
// modules/auditoria/auditoria_ODH.php
session_start();
include("../../../includes/db.php");

// Proteger ruta
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../../login.php');
    exit;
}

// Inicializar modo oscuro si no existe
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

// Datos de sesión seguros
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombreRol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');

// OPTIMIZACIÓN: Verificar conexión primero
if (!isset($conn_ic) || $conn_ic->connect_error) {
    die("Error de conexión: " . ($conn_ic->connect_error ?? "Desconocido"));
}

// OPTIMIZACIÓN: Métrica rápida con consulta más eficiente
$totalReportes = 0;
$q = $conn_ic->query("SELECT COUNT(*) AS total FROM reportes WHERE bandera = 'O'");
if ($q) { 
    $row = $q->fetch_assoc(); 
    $totalReportes = (int)($row['total'] ?? 0); 
    $q->free();
}

require_once '../../../config.php';
require_once '../../../includes/sidebar_config.php';

// CONSULTAS DIRECTAS - SIN CACHE
$tablas_data = [];

// Definir todas las consultas
$queries = [
    'km3201' => "SELECT DISTINCT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes_odh
                WHERE bandera = 'O'
                AND acciones_correctivas = 'KM24807'
                AND (tipificacion_falla = 'Cancelled' OR tipificacion_falla = 'Resolved Successfully' OR tipificacion_falla IS NULL OR tipificacion_falla = '')
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    'tabla2' => "SELECT DISTINCT numero_ticket, acciones_correctivas, tipificacion_falla
                FROM reportes_odh
                WHERE bandera = 'O'
                AND (acciones_correctivas != 'KM24807' AND acciones_correctivas IS NOT NULL AND acciones_correctivas != '')
                AND tipificacion_falla = 'Request Rejected'
                AND numero_ticket NOT LIKE 'SD%'
                ORDER BY fecha_apertura DESC
                LIMIT 100",
    
    // CONSULTA ACTUALIZADA: IDs de Beneficiarios Vacíos - Versión mejorada
    'tabla3' => "SELECT DISTINCT
                    r.numero_ticket,
                    r.sem_id_beneficiario,
                    r.sem_cod_servicio
                FROM reportes_odh AS r
                WHERE
                    -- bandera exacta, ignorando espacios
                    TRIM(COALESCE(r.bandera, '')) = 'O'

                    -- sem_id_beneficiario nulo o \"vacío\" (espacios, tabs, NBSP, saltos de línea)
                    AND NULLIF(
                          TRIM(
                            REPLACE(
                              REPLACE(
                                REPLACE(
                                  REPLACE(COALESCE(r.sem_id_beneficiario, ''), CHAR(160), ''), -- NBSP
                                CHAR(9), ''),  -- TAB
                              CHAR(10), ''),   -- LF
                            CHAR(13), '')      -- CR
                          ),
                        ''
                        ) IS NULL

                    -- excluir SD, tolerando espacios y mayúsculas/minúsculas
                    AND TRIM(UPPER(COALESCE(r.numero_ticket, ''))) NOT LIKE 'SD%'

                    -- excluir KM24807, pero incluir NULL (si no quieres incluir NULL, cambia esta línea)
                    AND (r.acciones_correctivas IS NULL
                         OR TRIM(r.acciones_correctivas) <> 'KM24807')

                ORDER BY r.fecha_apertura DESC
                LIMIT 100",

    'prioridad_p1' => "SELECT DISTINCT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - ODHNOCMINTIC'
                AND Prioridad = '1 - Crítica'
                AND `ID Conocimiento` != 'KM24807'
                AND Título NOT LIKE '%CAÍDA TOTAL INSTITUCIONES %' 
                AND Título NOT LIKE ' CAÍDA TOTAL INSTITUCIONES %' 
                AND Título NOT LIKE '%FALLA MASIVA INSTITUCIONES%'
                AND Título NOT LIKE 'FALLA MASIVA  INSTITUCIONES%' 
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'prioridad_p2' => "SELECT DISTINCT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - ODHNOCMINTIC'
                AND Prioridad = '2 - Alta'
                AND `ID Conocimiento` != 'KM24807'
                AND (Título NOT LIKE 'CAÍDA PARCIAL%' AND Título NOT LIKE '% CAÍDA PARCIAL%')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'prioridad_p3' => "SELECT DISTINCT `ID de incidente`, Prioridad, Título
                FROM incidentes_odh
                WHERE `Grupo de asignación` = 'EYN - ODHNOCMINTIC'
                AND Prioridad = '3 - Media'
                AND `ID Conocimiento` != 'KM24807'
                AND (
                    Título LIKE 'CAÍDA PARCIAL%' OR Título LIKE '% CAÍDA PARCIAL%'
                    OR Título LIKE '%CAÍDA TOTAL INSTITUCIONES%' OR Título LIKE '% CAÍDA TOTAL INSTITUCIONES%'
                    OR Título LIKE '%FALLA MASIVA INSTITUCIONES%' OR Título LIKE '%FALLA MASIVA  INSTITUCIONES%'
                )
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    // CONSULTA ACTUALIZADA: Check de Fallas Masivas - Incidente Mayor - Versión mejorada
    'incidente_mayor' => "SELECT DISTINCT
                    `ID de incidente`,
                    `Título`,
                    `Incidente Mayor`
                FROM incidentes_odh
                WHERE
                    (
                        `Incidente Mayor` IS NULL
                        OR TRIM(`Incidente Mayor`) = ''
                        OR UPPER(TRIM(`Incidente Mayor`)) <> 'TRUE'
                    )
                    AND `Título` LIKE '%FALLA MASIVA%'
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    'fallas_masivas_un_ci' => "SELECT DISTINCT `ID de incidente`, Título, `CI Relacionados`
                FROM incidentes_odh
                WHERE (Título LIKE 'FALLA MASIVA%' OR Título LIKE '% FALLA MASIVA%' OR Título LIKE 'FALLA MASIVA DEGRADACIÓN%' OR Título LIKE 'FALLA MASIVA DEGRADACION%')
                AND `ID Conocimiento` != 'KM24807'
                AND (`CI Relacionados` IS NOT NULL AND `CI Relacionados` != '')
                AND (`CI Relacionados` NOT LIKE '% %')
                ORDER BY `Inicio de la interrupción de servicio` DESC
                LIMIT 100",

    // CONSULTA CORREGIDA: Responsabilidad KM - Versión corregida
    'responsabilidad_km' => "SELECT
                    i.`ID de incidente`  AS numero_ticket,
                    i.`Responsabilidad`  AS responsabilidad_por_ticket,
                    i.`ID Conocimiento`  AS acciones_correctivas,
                    i.`Cerrado por`      AS cerrado_por,
                    rk.`causa_principal` AS causa_principal,
                    rk.`responsabilidad` AS responsabilidad_correcta
                FROM incidentes_odh AS i
                INNER JOIN responsabilidad_kms_odh_5g AS rk
                  ON TRIM(i.`ID Conocimiento`) COLLATE utf8mb4_unicode_ci
                     = TRIM(rk.`no_km`) COLLATE utf8mb4_unicode_ci
                WHERE
                    i.`Responsabilidad` IS NOT NULL
                    AND rk.`responsabilidad` IS NOT NULL
                    AND TRIM(i.`Responsabilidad`) <> ''
                    AND TRIM(rk.`responsabilidad`) <> ''
                    AND NOT (
                        TRIM(i.`Responsabilidad`) COLLATE utf8mb4_unicode_ci
                        <=> TRIM(rk.`responsabilidad`) COLLATE utf8mb4_unicode_ci
                    )
                ORDER BY i.`ID de incidente`
                LIMIT 100",

    'kms_no_correspondientes' => "SELECT DISTINCT 
                    r.numero_ticket, 
                    r.acciones_correctivas, 
                    r.tipificacion_falla,
                    'KM no corresponde al proyecto' as causa_principal,
                    'Por validar' as responsabilidad_correcta
                FROM reportes_odh r
                LEFT JOIN responsabilidad_kms_odh_5g rk ON r.acciones_correctivas = rk.no_km
                WHERE r.bandera = 'O'
                AND r.prioridad IN ('1', '2')
                AND rk.no_km IS NULL
                AND r.acciones_correctivas IS NOT NULL
                AND r.acciones_correctivas != ''
                ORDER BY r.fecha_apertura DESC
                LIMIT 100",

    'sitios_no_encontrados' => "SELECT DISTINCT 
                    r.numero_ticket, 
                    r.sem_id_beneficiario,
                    r.sem_cod_servicio,
                    MAX(bc_id.`ID BENEFICIARIO`) as id_beneficiario_por_id,
                    MAX(bc_cod.`ID BENEFICIARIO`) as id_beneficiario_por_codigo,
                    MAX(bc_cod.`CODIGO SERVICIO`) as cod_servicio_base_cis,
                    CASE 
                        WHEN MAX(bc_id.`ID BENEFICIARIO`) IS NOT NULL THEN 'Encontrado por ID'
                        WHEN MAX(bc_cod.`ID BENEFICIARIO`) IS NOT NULL THEN 'Comparte código con ID saliente'
                        ELSE 'No encontrado'
                    END as observacion
                FROM reportes_odh r
                LEFT JOIN base_cis_odh_5g bc_id ON r.sem_id_beneficiario = bc_id.`ID BENEFICIARIO`
                LEFT JOIN base_cis_odh_5g bc_cod ON r.sem_cod_servicio = bc_cod.`CODIGO SERVICIO`
                WHERE r.bandera = 'O'
                AND r.acciones_correctivas != 'KM24807'
                AND bc_id.`ID BENEFICIARIO` IS NULL
                AND (r.sem_id_beneficiario IS NOT NULL AND r.sem_id_beneficiario != '')
                GROUP BY r.numero_ticket, r.sem_id_beneficiario, r.sem_cod_servicio
                ORDER BY r.fecha_apertura DESC
                LIMIT 100",

    // CONSULTA CORREGIDA: Prioridad vs Categorización con DISTINCT
    'prioridad_categorizacion' => "SELECT DISTINCT 
                    r.numero_ticket,
                    r.tipo_solicitud,
                    r.prioridad,
                    GROUP_CONCAT(DISTINCT c.prioridad ORDER BY c.prioridad) as prioridades_permitidas,
                    CASE 
                        WHEN c.categorizacion IS NULL THEN 'Categorización no encontrada'
                        WHEN FIND_IN_SET(r.prioridad, GROUP_CONCAT(DISTINCT c.prioridad)) = 0 THEN 'Prioridad no coincide con las permitidas'
                        ELSE 'Validar manualmente'
                    END as observacion
                FROM reportes_odh r
                LEFT JOIN categorizacion_odh c ON r.tipo_solicitud = c.categorizacion
                WHERE r.bandera = 'O'
                AND r.numero_ticket NOT LIKE 'SD%'
                GROUP BY r.numero_ticket, r.tipo_solicitud, r.prioridad
                HAVING observacion != 'Validar manualmente'
                ORDER BY r.fecha_apertura DESC
                LIMIT 100"
];

// Ejecutar todas las consultas
foreach ($queries as $tabla_id => $sql) {
    $tablas_data[$tabla_id] = ['success' => false, 'rows' => [], 'error' => ''];
    
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

// OPTIMIZACIÓN: Cerrar conexión temprano
$conn_ic->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría MinTIC ODH 5G - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* MANTENER TODO EL CSS ORIGINAL DEL AUDITORIA_7K.PHP */
        :root {
            --primary-color: #E10000;
            --secondary-color: #CC0000;
            --accent-color: #FF5252;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --text-light: #ffffff;
            --text-dark: #2c3e50;
            --text-gray: #7f8c8d;
            --card-bg: #ffffff;
            --bg-light: #f8f9fa;
            --border-color: #e9ecef;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        /* Modo Oscuro */
        body.dark-mode {
            --bg-light: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-dark: #ffffff;
            --text-gray: #b3b3b3;
            --border-color: #404040;
            --sidebar-bg: #1e2a38;
            --sidebar-hover: #2c3e50;
            --success-color: #3dcc70;
            --warning-color: #ffd76e;
            --info-color: #5bc0de;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Main Content */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 280px);
            margin-left: 280px;
        }

        .top-header {
            background: var(--card-bg);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-dark);
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            display: none;
        }

        .sidebar-toggle:hover {
            background: var(--bg-light);
            transform: scale(1.05);
        }

        .top-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.5px;
            font-family: 'Poppins', sans-serif;
        }

        .header-right {
            height: 40px;
            object-fit: contain;
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Welcome Card Reducida y sin badges */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(225, 0, 0, 0.2);
        }

        .welcome-content h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Internal Navigation */
        .internal-nav {
            background: var(--card-bg);
            padding: 0 2rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            gap: 1rem;
            overflow-x: auto;
        }

        .nav-tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--text-gray);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tab:hover {
            color: var(--primary-color);
        }

        .nav-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .nav-tab i {
            font-size: 1.2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-icon.red { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: white; 
        }
        .stat-icon.purple { 
            background: linear-gradient(135deg, #8B5CF6, #7C3AED); 
            color: white; 
        }
        .stat-icon.cyan { 
            background: linear-gradient(135deg, #06B6D4, #0891B2); 
            color: white; 
        }
        .stat-icon.orange { 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: white; 
        }

        .stat-info h3 {
            font-size: 0.85rem;
            color: var(--text-gray);
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .stat-info .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Section Header */
        .section-header {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-subtitle {
            color: var(--text-gray);
            font-size: 0.95rem;
        }

        /* Audit Grid - Distribución mejorada */
        .audit-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Para la tercera tarjeta que ocupa todo el ancho */
        .full-width-row {
            grid-column: 1 / -1;
        }

        /* Audit Cards */
        .audit-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .audit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #0EA5E9, #0284C7);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-badge {
            background: rgba(255,255,255,0.3);
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .card-body {
            padding: 0;
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
            max-height: 400px;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .modern-table thead {
            background: var(--bg-light);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modern-table th {
            padding: 0.8rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.85rem;
        }

        .modern-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background: var(--bg-light);
        }

        .full-width-card {
            grid-column: 1 / -1;
        }

        /* Filters */
        .filter-bar {
            background: var(--bg-light);
            padding: 1rem 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--card-bg);
            color: var(--text-dark);
            min-width: 180px;
        }

        .filter-button {
            padding: 0.6rem 1.2rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-button:hover {
            background: var(--secondary-color);
        }

        /* Accordion */
        .accordion-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .accordion-header {
            padding: 1.2rem 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            background: var(--card-bg);
        }

        .accordion-header:hover {
            background: var(--bg-light);
        }

        .accordion-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .priority-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .priority-badge.p1 { background: #fee2e2; color: #dc2626; }
        .priority-badge.p2 { background: #fed7aa; color: #ea580c; }
        .priority-badge.p3 { background: #fef3c7; color: #d97706; }

        .accordion-count {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .accordion-icon {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .accordion-item.active .accordion-icon {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-item.active .accordion-content {
            max-height: 800px;
        }

        .accordion-body {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        /* Estilos para texto en tablas - Color azul sin subrayado */
        .text-blue {
            color: #0EA5E9;
            font-weight: 500;
        }

        .text-green {
            color: #28a745;
            font-weight: 500;
        }

        .text-orange {
            color: #ff9800;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Back Button */
        .back-button-header {
            background: var(--primary-color);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-button-header:hover {
            background: var(--secondary-color);
            transform: translateX(-2px);
        }

        .back-button-header i {
            font-size: 1.1rem;
        }

        /* RESPONSIVE */
        @media (max-width: 1023px) {
            .sidebar-toggle { display: flex; }
            .main-content { margin-left: 0; width: 100%; }
            .audit-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .back-button-header span { display: none; }
        }

        @media (max-width: 640px) {
            .content-area { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-input { min-width: 100%; }
            .top-header h1 { font-size: 1.2rem; }
        }

        @media (min-width: 1024px) {
            .main-content { margin-left: 280px; width: calc(100% - 280px); }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    
    <?php include '../../../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <a href="../../../index.php" class="back-button-header">
                    <i class='bx bx-arrow-back'></i>
                    <span>Volver</span>
                </a>
                <h1>Auditoría ODH 5G</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Navegación por tabs -->
        <nav class="internal-nav">
            <div class="nav-tab active" data-tab="validaciones">
                <i class='bx bx-check-shield'></i>
                Validaciones KM
            </div>
            <div class="nav-tab" data-tab="prioridades">
                <i class='bx bx-error'></i>
                Incidentes por Prioridad
            </div>
            <div class="nav-tab" data-tab="fallas">
                <i class='bx bx-bug'></i>
                Fallas Masivas
            </div>
            <div class="nav-tab" data-tab="sitios">
                <i class='bx bx-map'></i>
                Validación Sitios
            </div>
            <!-- NUEVO TAB: Prioridad vs Categorización -->
            <div class="nav-tab" data-tab="categorizacion">
                <i class='bx bx-category'></i>
                Categorización
            </div>
        </nav>

        <div class="content-area">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class='bx bx-file'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Hallazgos</h3>
                        <div class="stat-value"><?php 
                            $total = array_sum(array_column($tablas_data, 'count'));
                            echo $total;
                        ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class='bx bx-error-circle'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Validaciones KM</h3>
                        <div class="stat-value"><?php echo ($tablas_data['km3201']['count'] ?? 0) + ($tablas_data['tabla2']['count'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cyan">
                        <i class='bx bx-map-pin'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Sitios Vacíos</h3>
                        <div class="stat-value"><?php echo $tablas_data['tabla3']['count'] ?? 0; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class='bx bx-shield-x'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Fallas Masivas</h3>
                        <div class="stat-value"><?php echo ($tablas_data['incidente_mayor']['count'] ?? 0) + ($tablas_data['fallas_masivas_un_ci']['count'] ?? 0) + ($tablas_data['prioridad_categorizacion']['count'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <!-- TAB 1: Validaciones KM -->
            <div class="tab-content active" id="tab-validaciones">
                <!-- Welcome Card Reducida y sin badges -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h2>Auditoría ODH 5G</h2>
                        <p>Desde aquí podrás ejecutar revisiones, validar campos críticos y generar hallazgos para mantener la calidad de los datos del proyecto ODH 5G.</p>
                    </div>
                </div>

                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-check-shield'></i>
                        Validaciones de Acciones Correctivas (KM)
                    </h2>
                    <p class="section-subtitle">Tickets que requieren revisión en sus acciones correctivas y tipificaciones</p>
                </div>

                <div class="audit-grid">
                    <!-- KM24807 -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-bookmark'></i>
                                KM24807 sin Requerimiento Rechazado
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['km3201']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('km3201', $tablas_data['km3201']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Otros KMs -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-bookmarks'></i>
                                Otros KM's sin Resuelto Satisfactoriamente
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['tabla2']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('tabla2', $tablas_data['tabla2']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- KMs no correspondientes - OCUPA TODA LA FILA -->
                    <div class="audit-card full-width-row">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-x-circle'></i>
                                KMs No Correspondientes ODH
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['kms_no_correspondientes']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('kms_no_correspondientes', $tablas_data['kms_no_correspondientes']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla grande de Responsabilidad KM -->
                <div class="audit-card full-width-card">
                    <div class="card-header">
                        <span class="card-title">
                            <i class='bx bx-user-check'></i>
                            Auditoría de Responsabilidad por KM
                        </span>
                        <span class="card-badge" id="count-responsabilidad_km"><?php echo $tablas_data['responsabilidad_km']['count'] ?? 0; ?></span>
                    </div>
                    <?php if (!empty($tablas_data['responsabilidad_km']['rows'])): ?>
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label class="filter-label">Ticket</label>
                            <input type="text" class="filter-input" id="filter-ticket" placeholder="Buscar ticket...">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Responsabilidad Asignada</label>
                            <select class="filter-input" id="filter-responsabilidad">
                                <option value="">Todas</option>
                                <?php
                                $responsabilidades = array_unique(array_column($tablas_data['responsabilidad_km']['rows'], 'responsabilidad_por_ticket'));
                                foreach ($responsabilidades as $resp) {
                                    if (!empty($resp)) {
                                        echo "<option value=\"" . htmlspecialchars($resp) . "\">" . htmlspecialchars($resp) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Cerrado por</label>
                            <select class="filter-input" id="filter-cerrado_por">
                                <option value="">Todos</option>
                                <?php
                                $cerrado_por = array_unique(array_column($tablas_data['responsabilidad_km']['rows'], 'cerrado_por'));
                                foreach ($cerrado_por as $cp) {
                                    if (!empty($cp)) {
                                        echo "<option value=\"" . htmlspecialchars($cp) . "\">" . htmlspecialchars($cp) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <button class="filter-button" id="reset-filters">
                            <i class='bx bx-reset'></i> Resetear
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="table-wrapper" style="max-height: 500px;">
                            <div id="table-responsabilidad_km">
                                <?php echo renderTableModern('responsabilidad_km', $tablas_data['responsabilidad_km']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Prioridades -->
            <div class="tab-content" id="tab-prioridades">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-error'></i>
                        Incidentes por Nivel de Prioridad
                    </h2>
                    <p class="section-subtitle">Clasificación de incidentes según su criticidad</p>
                </div>

                <!-- Accordion para prioridades -->
                <div class="accordion-item active">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <div class="accordion-title">
                            <span class="priority-badge p1">P1 - CRÍTICA</span>
                            <span>Incidentes de Prioridad 1</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="accordion-count"><?php echo $tablas_data['prioridad_p1']['count'] ?? 0; ?></span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('prioridad_p1', $tablas_data['prioridad_p1']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <div class="accordion-title">
                            <span class="priority-badge p2">P2 - ALTA</span>
                            <span>Incidentes de Prioridad 2</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="accordion-count"><?php echo $tablas_data['prioridad_p2']['count'] ?? 0; ?></span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('prioridad_p2', $tablas_data['prioridad_p2']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <div class="accordion-title">
                            <span class="priority-badge p3">P3 - MEDIA</span>
                            <span>Incidentes de Prioridad 3</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="accordion-count"><?php echo $tablas_data['prioridad_p3']['count'] ?? 0; ?></span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('prioridad_p3', $tablas_data['prioridad_p3']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Fallas Masivas -->
            <div class="tab-content" id="tab-fallas">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-bug'></i>
                        Validación de Fallas Masivas
                    </h2>
                    <p class="section-subtitle">Revisión de incidentes mayores y CIs relacionados</p>
                </div>

                <div class="audit-grid">
                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-error-alt'></i>
                                Check de Fallas Masivas - Incidente Mayor
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['incidente_mayor']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('incidente_mayor', $tablas_data['incidente_mayor']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-server'></i>
                                Fallas Masivas con un Solo CI
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['fallas_masivas_un_ci']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('fallas_masivas_un_ci', $tablas_data['fallas_masivas_un_ci']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Sitios -->
            <div class="tab-content" id="tab-sitios">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-map'></i>
                        Validación de Sitios e IDs de Beneficiario
                    </h2>
                    <p class="section-subtitle">Verificación de consistencia en la base de datos de sitios</p>
                </div>

                <div class="audit-grid">
                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-map-pin'></i>
                                IDs de Beneficiarios Vacíos
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['tabla3']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" style="max-height: 350px;">
                                <?php echo renderTableModern('tabla3', $tablas_data['tabla3']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-search-alt'></i>
                                Sitios No Encontrados en Base de Última Milla
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['sitios_no_encontrados']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('sitios_no_encontrados', $tablas_data['sitios_no_encontrados']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NUEVO TAB 5: Prioridad vs Categorización -->
            <div class="tab-content" id="tab-categorizacion">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-category'></i>
                        Prioridad vs Categorización
                    </h2>
                    <p class="section-subtitle">Comparación entre tipo de solicitud y prioridad con la categorización correcta</p>
                </div>

                <div class="audit-grid">
                    <div class="audit-card full-width-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-category'></i>
                                Incongruencias en Categorización vs Prioridad
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['prioridad_categorizacion']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableModern('prioridad_categorizacion', $tablas_data['prioridad_categorizacion']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navegación por tabs
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remover active de todos
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Activar el seleccionado
                this.classList.add('active');
                const tabId = this.dataset.tab;
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });

        // Accordion
        function toggleAccordion(element) {
            const item = element.closest('.accordion-item');
            const wasActive = item.classList.contains('active');
            
            // Cerrar todos
            document.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('active'));
            
            // Abrir el clickeado si estaba cerrado
            if (!wasActive) {
                item.classList.add('active');
            }
        }

        // Filtros para tabla de responsabilidad
        let responsabilidadKmData = <?php echo json_encode($tablas_data['responsabilidad_km']['rows'] ?? []); ?>;
        let filteredResponsabilidadKmData = [...responsabilidadKmData];

        function applyResponsabilidadKmFilters() {
            const ticketFilter = document.getElementById('filter-ticket').value.toLowerCase();
            const responsabilidadFilter = document.getElementById('filter-responsabilidad').value;
            const cerradoPorFilter = document.getElementById('filter-cerrado_por').value;

            filteredResponsabilidadKmData = responsabilidadKmData.filter(row => {
                const matchesTicket = !ticketFilter || 
                    (row.numero_ticket && row.numero_ticket.toLowerCase().includes(ticketFilter));
                const matchesResponsabilidad = !responsabilidadFilter || 
                    row.responsabilidad_por_ticket === responsabilidadFilter;
                const matchesCerradoPor = !cerradoPorFilter || 
                    row.cerrado_por === cerradoPorFilter;

                return matchesTicket && matchesResponsabilidad && matchesCerradoPor;
            });

            updateResponsabilidadKmCount();
            renderResponsabilidadKmTable();
        }

        function updateResponsabilidadKmCount() {
            const countElement = document.getElementById('count-responsabilidad_km');
            if (countElement) {
                countElement.textContent = filteredResponsabilidadKmData.length;
            }
        }

        function renderResponsabilidadKmTable() {
            const container = document.getElementById('table-responsabilidad_km');
            if (!container) return;

            const columns = ['numero_ticket', 'responsabilidad_por_ticket', 'acciones_correctivas', 'causa_principal', 'responsabilidad_correcta', 'cerrado_por'];
            const headers = ['Ticket', 'Responsabilidad Asignada', 'KM', 'Causa Principal', 'Responsabilidad Correcta', 'Cerrado por'];

            let html = `<table class="modern-table"><thead><tr>`;
            headers.forEach(header => {
                html += `<th>${header}</th>`;
            });
            html += `</tr></thead><tbody>`;

            if (filteredResponsabilidadKmData.length === 0) {
                html += `<tr><td colspan="${headers.length}" class="empty-state">
                    <i class='bx bx-search-alt'></i>
                    <p>No hay datos con los filtros aplicados</p>
                </td></tr>`;
            } else {
                filteredResponsabilidadKmData.forEach(row => {
                    html += `<tr>`;
                    columns.forEach(column => {
                        let value = row[column] ?? 'N/A';
                        if (value === null || value === '') value = '<em>Vacío</em>';
                        
                        // Aplicar estilos especiales
                        if (column === 'responsabilidad_correcta') {
                            value = `<span class="text-green">${escapeHtml(value)}</span>`;
                        } else if (column === 'cerrado_por' && !value.includes('Vacío')) {
                            value = `<span class="text-blue">${escapeHtml(value)}</span>`;
                        } else {
                            value = escapeHtml(value);
                        }
                        html += `<td>${value}</td>`;
                    });
                    html += `</tr>`;
                });
            }
            html += `</tbody></table>`;
            container.innerHTML = html;
        }

        function resetResponsabilidadKmFilters() {
            document.getElementById('filter-ticket').value = '';
            document.getElementById('filter-responsabilidad').value = '';
            document.getElementById('filter-cerrado_por').value = '';
            filteredResponsabilidadKmData = [...responsabilidadKmData];
            updateResponsabilidadKmCount();
            renderResponsabilidadKmTable();
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return 'N/A';
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            if (responsabilidadKmData.length > 0) {
                document.getElementById('filter-ticket')?.addEventListener('input', applyResponsabilidadKmFilters);
                document.getElementById('filter-responsabilidad')?.addEventListener('change', applyResponsabilidadKmFilters);
                document.getElementById('filter-cerrado_por')?.addEventListener('change', applyResponsabilidadKmFilters);
                document.getElementById('reset-filters')?.addEventListener('click', resetResponsabilidadKmFilters);
                updateResponsabilidadKmCount();
            }

            // Modo oscuro
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.getElementById('body');
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    body.classList.toggle('dark-mode');
                    const isDarkMode = body.classList.contains('dark-mode');
                    
                    // Actualizar icono y texto
                    const icon = darkModeToggle.querySelector('i');
                    const text = darkModeToggle.querySelector('span');
                    
                    if (isDarkMode) {
                        icon.className = 'bx bx-sun';
                        text.textContent = 'Modo Claro';
                    } else {
                        icon.className = 'bx bx-moon';
                        text.textContent = 'Modo Oscuro';
                    }

                    // Guardar preferencia via AJAX
                    fetch('../../../includes/toggle_dark_mode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'toggle_dark_mode=true'
                    });
                });
            }
        });
    </script>
</body>
</html>

<?php
function renderTableModern($tableId, $data) {
    $config = [
        'km3201' => [
            'columns' => ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla'],
            'headers' => ['Ticket', 'Acciones Correctivas', 'Tipificación']
        ],
        'tabla2' => [
            'columns' => ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla'],
            'headers' => ['Ticket', 'Acciones Correctivas', 'Tipificación']
        ],
        'tabla3' => [
            'columns' => ['numero_ticket', 'sem_id_beneficiario', 'sem_cod_servicio'],
            'headers' => ['Ticket', 'ID Beneficiario', 'Código Servicio']
        ],
        'prioridad_p1' => [
            'columns' => ['ID de incidente', 'Prioridad', 'Título'],
            'headers' => ['ID Incidente', 'Prioridad', 'Título']
        ],
        'prioridad_p2' => [
            'columns' => ['ID de incidente', 'Prioridad', 'Título'],
            'headers' => ['ID Incidente', 'Prioridad', 'Título']
        ],
        'prioridad_p3' => [
            'columns' => ['ID de incidente', 'Prioridad', 'Título'],
            'headers' => ['ID Incidente', 'Prioridad', 'Título']
        ],
        'incidente_mayor' => [
            'columns' => ['ID de incidente', 'Título', 'Incidente Mayor'],
            'headers' => ['ID Incidente', 'Título', 'Incidente Mayor']
        ],
        'fallas_masivas_un_ci' => [
            'columns' => ['ID de incidente', 'Título', 'CI Relacionados'],
            'headers' => ['ID Incidente', 'Título', 'CI Relacionados']
        ],
        // CONFIGURACIÓN MODIFICADA: Incluye todas las columnas necesarias
        'responsabilidad_km' => [
            'columns' => ['numero_ticket', 'responsabilidad_por_ticket', 'acciones_correctivas', 'causa_principal', 'responsabilidad_correcta', 'cerrado_por'],
            'headers' => ['Ticket', 'Responsabilidad Asignada', 'KM', 'Causa Principal', 'Responsabilidad Correcta', 'Cerrado por']
        ],
        'kms_no_correspondientes' => [
            'columns' => ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla', 'causa_principal', 'responsabilidad_correcta'],
            'headers' => ['Ticket', 'KM', 'Tipificación', 'Causa', 'Responsabilidad']
        ],
        'sitios_no_encontrados' => [
            'columns' => ['numero_ticket', 'sem_id_beneficiario', 'sem_cod_servicio', 'id_beneficiario_por_codigo', 'observacion'],
            'headers' => ['Ticket', 'ID Beneficiario', 'Cód. Servicio', 'ID Base UM', 'Observación']
        ],
        // NUEVA CONFIGURACIÓN: Prioridad vs Categorización
        'prioridad_categorizacion' => [
            'columns' => ['numero_ticket', 'tipo_solicitud', 'prioridad', 'prioridades_permitidas', 'observacion'],
            'headers' => ['Ticket', 'Tipo Solicitud', 'Prioridad', 'Prioridades Permitidas', 'Observación']
        ]
    ];

    if (!$data['success']) {
        return "<div class='empty-state'><i class='bx bx-error-circle'></i><p>Error: " . htmlspecialchars($data['error']) . "</p></div>";
    }

    if (empty($data['rows'])) {
        return "<div class='empty-state'><i class='bx bx-inbox'></i><p>No hay datos para mostrar</p></div>";
    }

    $tableConfig = $config[$tableId] ?? null;
    if (!$tableConfig) {
        return "<div class='empty-state'><i class='bx bx-error'></i><p>Configuración no encontrada</p></div>";
    }

    $html = "<table class='modern-table'><thead><tr>";
    foreach ($tableConfig['headers'] as $header) {
        $html .= "<th>" . htmlspecialchars($header) . "</th>";
    }
    $html .= "</tr></thead><tbody>";
    
    foreach ($data['rows'] as $row) {
        $html .= "<tr>";
        foreach ($tableConfig['columns'] as $column) {
            $value = $row[$column] ?? 'N/A';
            if ($value === null || $value === '') {
                $value = '<em>Vacío</em>';
            } else {
                // Aplicar estilos especiales para ciertas columnas
                if ($column === 'observacion') {
                    if ($value === 'Categorización no encontrada') {
                        $value = "<span class='text-orange'>" . htmlspecialchars($value) . "</span>";
                    } else if ($value === 'Prioridad no coincide con las permitidas') {
                        $value = "<span class='text-blue'>" . htmlspecialchars($value) . "</span>";
                    } else {
                        $value = "<span style='color: var(--warning-color);'>" . htmlspecialchars($value) . "</span>";
                    }
                } else if ($column === 'prioridades_permitidas') {
                    // Resaltar las prioridades permitidas
                    $value = "<span class='text-green'>" . htmlspecialchars($value) . "</span>";
                } else {
                    $value = htmlspecialchars($value);
                }
            }
            $html .= "<td>" . $value . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    
    return $html;
}
?>