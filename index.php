<?php
session_start();
include("includes/db.php");

require_once 'config.php';
require_once 'includes/sidebar_config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

date_default_timezone_set('America/Bogota');

// Obtener estadísticas principales
$totalReportesPortal = 0;
$totalReportesSM = 0;
$totalReportesPortalODH = 0;
$totalReportesSMODH = 0;

if (isset($conn_ic) && $conn_ic && !$conn_ic->connect_error) {
    // Reportes Portal 7K
    $q = $conn_ic->query("SELECT COUNT(*) AS total FROM reportes");
    if ($q) { 
        $row = $q->fetch_assoc(); 
        $totalReportesPortal = (int)($row['total'] ?? 0); 
    }
    
    // Incidentes Service Manager 7K
    $q = $conn_ic->query("SELECT COUNT(*) AS total FROM incidentes");
    if ($q) { 
        $row = $q->fetch_assoc(); 
        $totalReportesSM = (int)($row['total'] ?? 0); 
    }
    
    // Reportes Portal ODH
    $q = $conn_ic->query("SELECT COUNT(*) AS total FROM reportes_odh");
    if ($q) { 
        $row = $q->fetch_assoc(); 
        $totalReportesPortalODH = (int)($row['total'] ?? 0); 
    }
    
    // Incidentes Service Manager ODH
    $q = $conn_ic->query("SELECT COUNT(*) AS total FROM incidentes_odh");
    if ($q) { 
        $row = $q->fetch_assoc(); 
        $totalReportesSMODH = (int)($row['total'] ?? 0); 
    }
}

// Datos para gráfica de tendencia semanal
$datosSemanales = [];
if (isset($conn_ic) && $conn_ic && !$conn_ic->connect_error) {
    $querySemanal = $conn_ic->query("
        SELECT DAYNAME(fecha_apertura) as dia_semana, COUNT(*) as total 
        FROM reportes 
        WHERE fecha_apertura >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DAYNAME(fecha_apertura), DAYOFWEEK(fecha_apertura)
        ORDER BY DAYOFWEEK(fecha_apertura)
    ");
    
    if ($querySemanal) {
        while ($row = $querySemanal->fetch_assoc()) {
            $datosSemanales[$row['dia_semana']] = $row['total'];
        }
    }
}

// Lógica para calcular próxima y última actualización
$horasActualizacion = ['06:30', '12:30', '17:30', '22:30'];
$horaActual = new DateTime();

$proximaActualizacion = '';
$ultimaActualizacion = '';

// Convertir horas a minutos para facilitar comparación
$minutosActuales = ($horaActual->format('H') * 60) + $horaActual->format('i');

$horasEnMinutos = array_map(function($hora) {
    list($h, $m) = explode(':', $hora);
    return ($h * 60) + $m;
}, $horasActualizacion);

// Encontrar próxima actualización
foreach ($horasEnMinutos as $index => $minutos) {
    if ($minutosActuales < $minutos) {
        $proximaActualizacion = $horasActualizacion[$index];
        break;
    }
}

// Si no hay próxima hoy, es la primera de mañana
if (empty($proximaActualizacion)) {
    $proximaActualizacion = $horasActualizacion[0] . ' (mañana)';
}

// Encontrar última actualización
for ($i = count($horasEnMinutos) - 1; $i >= 0; $i--) {
    if ($minutosActuales >= $horasEnMinutos[$i]) {
        $ultimaActualizacion = $horasActualizacion[$i];
        break;
    }
}

// Si no hay última hoy, es la última de ayer
if (empty($ultimaActualizacion)) {
    $ultimaActualizacion = $horasActualizacion[count($horasActualizacion) - 1] . ' (ayer)';
}

// Convertir a formato 12 horas para mostrar
function convertirA12Horas($hora24) {
    if (strpos($hora24, '(') !== false) {
        list($hora, $dia) = explode(' ', $hora24);
        $dt = DateTime::createFromFormat('H:i', $hora);
        return $dt->format('g:i A') . ' ' . $dia;
    }
    $dt = DateTime::createFromFormat('H:i', $hora24);
    return $dt->format('g:i A');
}

$proximaActDisplay = convertirA12Horas($proximaActualizacion);
$ultimaActDisplay = convertirA12Horas($ultimaActualizacion);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Auditorías NOC - Claro</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #E10000;
            --secondary-color: #CC0000;
            --accent-color: #FF5252;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: #252b3d;
            --text-light: #ffffff;
            --text-dark: #1a1f2e;
            --text-gray: #64748b;
            --card-bg: #ffffff;
            --bg-light: #f8fafc;
            --bg-gradient-start: #fafbfc;
            --bg-gradient-end: #f1f5f9;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode {
            --bg-light: #0f1419;
            --bg-gradient-start: #0f1419;
            --bg-gradient-end: #1a1f2e;
            --card-bg: #1e2433;
            --text-dark: #f1f5f9;
            --text-gray: #94a3b8;
            --border-color: #2d3748;
            --sidebar-bg: #14181f;
            --sidebar-hover: #1e2433;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-dark);
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.5;
            min-height: 100vh;
            font-size: 14px;
        }

        /* ==================== SIDEBAR ==================== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--sidebar-bg);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.sidebar-hidden {
            transform: translateX(-280px);
        }

        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-280px);
            }
            
            .sidebar.sidebar-active {
                transform: translateX(0);
            }
        }

        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: 100%;
        }

        .main-content.sidebar-hidden {
            margin-left: 0 !important;
            width: 100% !important;
        }

        /* Estilos para ocultar sidebar */
        .sidebar.sidebar-hidden {
            transform: translateX(-280px);
        }

        /* ==================== HEADER ==================== */
        .top-header {
            background: var(--card-bg);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }

        body.dark-mode .top-header {
            background: rgba(30, 36, 51, 0.95);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            box-shadow: 0 2px 8px rgba(225, 0, 0, 0.3);
        }

        .sidebar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.4);
        }

        .top-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-right img {
            height: 32px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        /* ==================== CONTENT AREA ==================== */
        .content-area {
            padding: 1.5rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* ==================== USER GREETING ==================== */
        .user-greeting {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .greeting-text h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .greeting-text p {
            font-size: 0.85rem;
            color: var(--text-gray);
        }

        .greeting-info {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .info-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.8rem;
            color: var(--text-gray);
            box-shadow: var(--shadow-sm);
        }

        .info-badge i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        /* ==================== STATS GRID ==================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stat-subvalue {
            font-size: 0.85rem;
            color: var(--text-gray);
            margin-top: 0.25rem;
        }

        /* ==================== UPDATE CARDS ==================== */
        .updates-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .update-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .update-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .update-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .update-card.next .update-icon {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            color: #10b981;
        }

        .update-card.last .update-icon {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);
            color: #3b82f6;
        }

        .update-info {
            flex: 1;
        }

        .update-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .update-time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* ==================== CHARTS ==================== */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .chart-header {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }

        .chart-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-title i {
            color: var(--primary-color);
        }

        .chart-container {
            height: 240px;
            position: relative;
        }

        /* ==================== MODULES GRID ==================== */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }

        .section-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .module-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .module-card:hover::before {
            opacity: 1;
        }

        .module-card:hover .module-content * {
            color: white !important;
        }

        .module-content {
            position: relative;
            z-index: 2;
        }

        .module-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .module-card:hover .module-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .module-icon i {
            font-size: 1.5rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .module-card:hover .module-icon i {
            color: white;
        }

        .module-title {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .module-description {
            color: var(--text-gray);
            font-size: 0.8rem;
            line-height: 1.4;
            transition: all 0.3s ease;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }
            
            .charts-row,
            .updates-row {
                grid-template-columns: 1fr;
            }
            
            .user-greeting {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .greeting-info {
                flex-wrap: wrap;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .top-header h1 {
                font-size: 0.95rem;
            }
            
            .header-right img {
                height: 28px;
            }
            
            .greeting-text h2 {
                font-size: 1.2rem;
            }
            
            .greeting-text p {
                font-size: 0.75rem;
            }
            
            .info-badge {
                font-size: 0.75rem;
                padding: 0.4rem 0.75rem;
            }
            
            .greeting-info {
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">

    <!-- Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Dashboard Principal</h1>
            </div>
            <div class="header-right">
                <img src="assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <div class="content-area">
            <!-- User Greeting -->
            <div class="user-greeting">
                <div class="greeting-text">
                    <h2>¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?>!</h2>
                    <p>Sistema de Gestión Auditorías - SINOC - Claro Colombia</p>
                </div>
                <div class="greeting-info">
                    <div class="info-badge">
                        <i class='bx bx-user-circle'></i>
                        <span><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-badge">
                        <i class='bx bx-calendar'></i>
                        <span><?php echo date('d/m/Y'); ?></span>
                    </div>
                    <div class="info-badge">
                        <i class='bx bx-time'></i>
                        <span><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-data'></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Reportes Portal</div>
                        <div class="stat-value"><?php echo number_format($totalReportesPortal + $totalReportesPortalODH, 0, ',', '.'); ?></div>
                        <div class="stat-subvalue">
                            7K: <?php echo number_format($totalReportesPortal, 0, ',', '.'); ?> | 
                            ODH: <?php echo number_format($totalReportesPortalODH, 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-server'></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Service Manager</div>
                        <div class="stat-value"><?php echo number_format($totalReportesSM + $totalReportesSMODH, 0, ',', '.'); ?></div>
                        <div class="stat-subvalue">
                            7K: <?php echo number_format($totalReportesSM, 0, ',', '.'); ?> | 
                            ODH: <?php echo number_format($totalReportesSMODH, 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Updates Row -->
            <div class="updates-row">
                <div class="update-card next">
                    <div class="update-icon">
                        <i class='bx bx-refresh'></i>
                    </div>
                    <div class="update-info">
                        <div class="update-label">Próxima Actualización</div>
                        <div class="update-time"><?php echo $proximaActDisplay; ?></div>
                    </div>
                </div>

                <div class="update-card last">
                    <div class="update-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <div class="update-info">
                        <div class="update-label">Última Actualización</div>
                        <div class="update-time"><?php echo $ultimaActDisplay; ?></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class='bx bx-pie-chart-alt-2'></i>
                            Distribución de Reportes
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="reportsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class='bx bx-trending-up'></i>
                            Tendencia Semanal
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- MinTIC 7K Section -->
            <div class="section-header">
                <div class="section-icon">
                    <i class='bx bx-broadcast'></i>
                </div>
                <h2 class="section-title">MinTIC 7K</h2>
            </div>
            <div class="modules-grid">
                <a href="modules/MinTIC_7k/auditoria/auditoria_7k.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-search-alt-2'></i>
                        </div>
                        <h3 class="module-title">Auditorías</h3>
                        <p class="module-description">Ejecutar revisiones y validar campos críticos</p>
                    </div>
                </a>

                <a href="modules/MinTIC_7k/PDRs_Fechas/pdr_fechas_7k.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-calendar-event'></i>
                        </div>
                        <h3 class="module-title">PDRs y Fechas</h3>
                        <p class="module-description">Gestionar planes de remediación y seguimiento</p>
                    </div>
                </a>

                <a href="modules/MinTIC_7k/comparacion_bases/comparacion_bases_index.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-git-compare'></i>
                        </div>
                        <h3 class="module-title">Comparación Bases</h3>
                        <p class="module-description">Comparar y validar bases de datos</p>
                    </div>
                </a>

                <a href="modules/MinTIC_7k/SDs/SDs_7K.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-file'></i>
                        </div>
                        <h3 class="module-title">SDs 7K</h3>
                        <p class="module-description">Gestión de Solicitudes de Servicio 7K</p>
                    </div>
                </a>
            </div>

            <!-- MinTIC ODH Section -->
            <div class="section-header">
                <div class="section-icon">
                    <i class='bx bx-network-chart'></i>
                </div>
                <h2 class="section-title">MinTIC ODH 5G</h2>
            </div>
            <div class="modules-grid">
                <a href="modules/MinTIC_ODH/auditoria_ODH/auditoria_ODH.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-search-alt-2'></i>
                        </div>
                        <h3 class="module-title">Auditorías ODH</h3>
                        <p class="module-description">Revisiones y validaciones ODH 5G</p>
                    </div>
                </a>

                <a href="modules/MinTIC_ODH/PDRs_Fechas_ODH/PDRs_Fechas_index.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-calendar-event'></i>
                        </div>
                        <h3 class="module-title">PDRs y Fechas ODH</h3>
                        <p class="module-description">Gestión de planes ODH 5G</p>
                    </div>
                </a>

                <a href="modules/MinTIC_ODH/comparacion_bases_ODH/comparacion_bases_index.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-git-compare'></i>
                        </div>
                        <h3 class="module-title">Comparación Bases ODH</h3>
                        <p class="module-description">Validación de bases ODH 5G</p>
                    </div>
                </a>

                <a href="modules/MinTIC_ODH/SDs/SDs_ODH.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-file'></i>
                        </div>
                        <h3 class="module-title">SDs ODH</h3>
                        <p class="module-description">Gestión de Solicitudes de Servicio ODH</p>
                    </div>
                </a>
            </div>

            <!-- Sistema Section -->
            <div class="section-header">
                <div class="section-icon">
                    <i class='bx bx-cog'></i>
                </div>
                <h2 class="section-title">Configuración del Sistema</h2>
            </div>
            <div class="modules-grid">
                <a href="modules/configuracion/configuracion.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-slider-alt'></i>
                        </div>
                        <h3 class="module-title">Configuración</h3>
                        <p class="module-description">Personalizar ajustes y preferencias</p>
                    </div>
                </a>

                <?php if ($_SESSION['role'] == 1): ?>
                <a href="modules/gestion_usuario/gestion_usuarios.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-user-plus'></i>
                        </div>
                        <h3 class="module-title">Gestión de Usuarios</h3>
                        <p class="module-description">Administrar usuarios, roles y permisos</p>
                    </div>
                </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 1 || $_SESSION['role'] == 2): ?>
                <a href="modules/actualizacion_bases/actualizacion_bases.php" class="module-card">
                    <div class="module-content">
                        <div class="module-icon">
                            <i class='bx bx-refresh'></i>
                        </div>
                        <h3 class="module-title">Actualización Bases</h3>
                        <p class="module-description">Actualizar y sincronizar bases de datos</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const body = document.getElementById('body');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.toggle('sidebar-hidden');
                    mainContent.classList.toggle('sidebar-hidden');
                } else {
                    sidebar.classList.toggle('sidebar-active');
                }
            });

            document.addEventListener('click', function(e) {
                if (window.innerWidth < 1024) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('sidebar-active');
                    }
                }
            });
        }

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function() {
                const isDarkMode = body.classList.toggle('dark-mode');
                const icon = this.querySelector('i');
                const text = this.querySelector('span');
                
                icon.className = isDarkMode ? 'bx bx-sun' : 'bx bx-moon';
                text.textContent = isDarkMode ? 'Modo Claro' : 'Modo Oscuro';

                fetch('includes/toggle_dark_mode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'toggle_dark_mode=true'
                });
            });
        }

        // Gráficas principales
        document.addEventListener('DOMContentLoaded', function() {
            const chartColors = {
                primary: '#E10000',
                secondary: '#CC0000',
                blue: '#3b82f6',
                green: '#10b981',
                purple: '#8b5cf6',
                orange: '#f59e0b',
                teal: '#14b8a6',
                pink: '#ec4899',
                indigo: '#6366f1',
                yellow: '#eab308',
                cyan: '#06b6d4'
            };

            // Gráfica de distribución
            const reportsCtx = document.getElementById('reportsChart');
            if (reportsCtx) {
                new Chart(reportsCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Reportes Portal', 'Service Manager'],
                        datasets: [{
                            data: [<?php echo $totalReportesPortal + $totalReportesPortalODH; ?>, <?php echo $totalReportesSM + $totalReportesSMODH; ?>],
                            backgroundColor: [chartColors.primary, chartColors.blue],
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: 'Poppins', size: 11, weight: '500' },
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 10,
                                cornerRadius: 6,
                                titleFont: { family: 'Poppins', size: 12, weight: '600' },
                                bodyFont: { family: 'Poppins', size: 11 },
                                displayColors: false
                            }
                        },
                        cutout: '70%'
                    }
                });
            }

            // Gráfica de tendencia semanal
            const weeklyCtx = document.getElementById('weeklyChart');
            if (weeklyCtx) {
                const weeklyData = <?php echo json_encode($datosSemanales); ?>;
                const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const spanishDays = {
                    'Sunday': 'Domingo',
                    'Monday': 'Lunes', 
                    'Tuesday': 'Martes',
                    'Wednesday': 'Miércoles',
                    'Thursday': 'Jueves',
                    'Friday': 'Viernes',
                    'Saturday': 'Sábado'
                };
                
                const labels = daysOfWeek.map(day => spanishDays[day]);
                const data = daysOfWeek.map(day => weeklyData[day] || 0);

                const gradient = weeklyCtx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.8)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.1)');

                new Chart(weeklyCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reportes',
                            data: data,
                            backgroundColor: gradient,
                            borderColor: chartColors.green,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: chartColors.green,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 10,
                                cornerRadius: 6,
                                titleFont: { family: 'Poppins', size: 12, weight: '600' },
                                bodyFont: { family: 'Poppins', size: 11 },
                                displayColors: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { 
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { family: 'Poppins', size: 10 }
                                }
                            },
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: {
                                    font: { family: 'Poppins', size: 10 }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>