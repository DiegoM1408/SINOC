<?php
// modules/comparacion_bases/compararbasesP.php
session_start();
include("../../../includes/db.php");

// Proteger ruta
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
    exit;
}

// Inicializar modo oscuro si no existe
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

// Datos de sesión seguros
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombreRol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');

// Incluir backend
include("compararbasesP_backend.php");

// Obtener datos directamente
$discrepanciasApertura = compararFechasAperturaParadas($conn);
$discrepanciasCierre = compararFechasCierreParadas($conn);
$discrepanciasMotivos = compararMotivosParada($conn);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Bases - Paradas (P) - MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ESTILOS IDÉNTICOS A LOS MÓDULOS ANTERIORES */
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

        /* ===== BARRA LATERAL CON ESTILOS IDÉNTICOS ===== */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: var(--text-light);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
        }

        .close-sidebar {
            position: absolute;
            right: 1rem;
            top: 1.5rem;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
        }

        .logo-primary {
            color: var(--primary-color);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .nav-item {
            margin: 0.5rem 1rem;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        /* Efecto rojo SOLO al hacer hover */
        .nav-item a:hover {
            background: var(--primary-color) !important;
        }

        /* ESTILO PARA MARCADOR DE PÁGINA ACTUAL */
        .nav-item.current-section a {
            background: var(--primary-color) !important;
        }

        .nav-item.active a {
            background: transparent !important;
        }

        /* ===== FIX DEFINITIVO ESPACIADO Y ALINEACIÓN ICONO–TEXTO ===== */
        .nav-item .nav-row {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            width: 100%;
            gap: 0 !important;
            text-align: left !important;
        }

        .nav-item .nav-row .nav-left {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            min-width: 0;
        }

        .nav-item .nav-row i {
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
            min-width: 0 !important;
        }

        .nav-item .nav-row .nav-left > i:first-child {
            margin: 0 !important;
        }

        .nav-item .nav-row .nav-left .nav-label {
            margin: 0 !important;
            padding: 0 !important;
            letter-spacing: 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-item .nav-row .dropdown-arrow {
            margin-left: auto !important;
            transition: transform .2s ease;
        }

        .nav-item.dropdown.active .nav-row .dropdown-arrow {
            transform: rotate(180deg);
        }

        .nav-item i { 
            margin-right: 0 !important; 
        }

        /* ===== CORRECCIÓN ESPECÍFICA PARA SUBMENÚS - ALINEACIÓN VERTICAL ===== */
        .nav-item.dropdown {
            position: relative;
        }

        .dropdown-menu {
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: var(--sidebar-hover);
            margin: 0 0.5rem;
            border-radius: 0 0 8px 8px;
        }

        .nav-item.dropdown.active .dropdown-menu {
            max-height: 300px;
        }

        .dropdown-menu li {
            margin: 0;
        }

        .dropdown-menu a {
            display: flex !important;
            align-items: center;
            padding: 0.75rem 1.5rem 0.75rem 2.5rem !important;
            font-size: 0.9rem;
            border-radius: 0;
            border-left: 3px solid transparent;
            background: transparent !important;
            text-decoration: none;
            color: var(--text-light);
            min-height: 44px;
        }

        .dropdown-menu a .nav-left {
            display: flex !important;
            align-items: center !important;
            width: 100%;
            gap: 8px !important;
        }

        .dropdown-menu a .nav-icon {
            margin-right: 12px !important;
            font-size: 1.2rem !important;
            width: 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            line-height: 1 !important;
        }

        .dropdown-menu a .nav-label {
            margin: 0 !important;
            padding: 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.9rem;
            line-height: 1.2 !important;
            vertical-align: middle !important;
        }

        .dropdown-menu a:hover {
            background: var(--primary-color) !important;
            border-left-color: transparent;
        }

        /* ESTILO PARA ENLACE ACTIVO EN SUBMENÚ */
        .dropdown-menu .active a {
            background: var(--primary-color) !important;
            border-left-color: var(--accent-color);
        }

        .nav-item.dropdown.active .dropdown-toggle {
            background: transparent !important;
        }

        .nav-item.dropdown .dropdown-toggle:hover {
            background: var(--primary-color) !important;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--text-gray);
            font-family: 'Poppins', sans-serif;
        }

        .sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            line-height: 1.25;
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-btn i {
            font-size: 1.10rem;
            width: 1.25rem;
            margin-right: .60rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-btn span {
            font: inherit;
            font-size: 0.95rem;
            line-height: 1.25;
        }

        /* Overlay para móvil */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
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

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(155, 89, 182, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .welcome-content h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 2;
        }

        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            max-width: 800px;
            position: relative;
            z-index: 2;
        }

        .badges {
            display: flex;
            gap: 1rem;
            position: relative;
            z-index: 2;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            font-size: 0.95rem;
        }

        .badge i {
            font-size: 1.2rem;
        }

        /* Agregar un botón de volver */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Estilos para la tabla de resultados */
        .results-container {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            margin-top: 2rem;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .results-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #9b59b6;
            font-family: 'Poppins', sans-serif;
        }

        .results-count {
            background: #9b59b6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table-container {
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .table-scroll {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            min-width: 600px;
        }

        .comparison-table th {
            background: #9b59b6;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            position: sticky;
            top: 0;
            cursor: pointer;
            user-select: none;
        }

        .comparison-table th:hover {
            background: #8e44ad;
        }

        .comparison-table th i {
            margin-left: 5px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .comparison-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .comparison-table tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.02);
        }

        .dark-mode .comparison-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        .comparison-table tr:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .dark-mode .comparison-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
            font-style: italic;
        }

        .refresh-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #9b59b6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .refresh-btn:hover {
            background: #8e44ad;
            transform: translateY(-2px);
        }

        /* ===== ESTILOS RESPONSIVE IDÉNTICOS ===== */
        @media (max-width: 1023px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }

            .sidebar.active {
                left: 0;
                box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3);
            }

            .close-sidebar {
                display: block;
            }

            .sidebar-toggle {
                display: flex;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .content-area {
                padding: 1.5rem;
            }
            
            .welcome-card {
                padding: 1.75rem;
            }
            
            .welcome-content h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .welcome-content h2 {
                font-size: 1.5rem;
            }
            
            .welcome-content p {
                font-size: 1rem;
            }
            
            .results-container {
                padding: 1rem;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .comparison-table {
                font-size: 0.8rem;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Desktop Styles */
        @media (min-width: 1024px) {
            .sidebar {
                left: 0;
            }
            
            .close-sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar - IDÉNTICA A LOS MÓDULOS ANTERIORES -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-text">
                <span class="logo-primary">CLARO</span>
                <span>NOC</span>
            </div>
            <button class="close-sidebar" id="closeSidebar">
                <i class='bx bx-x'></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="../../../index.php" class="nav-row">
                        <span class="nav-left">
                            <i class='bx bx-home nav-icon'></i>
                            <span class="nav-label">Inicio</span>
                        </span>
                    </a>
                </li>
                
                <!-- Menú desplegable MinTIC 7K - CONFIGURADO PARA COMPARACIÓN DE BASES -->
                <li class="nav-item dropdown current-section">
                    <a href="#" class="dropdown-toggle nav-row">
                        <span class="nav-left">
                            <i class='bx bx-network-chart nav-icon'></i>
                            <span class="nav-label">MinTIC 7K</span>
                        </span>
                        <i class='bx bx-chevron-down dropdown-arrow' aria-hidden="true"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../auditoria/auditoria_7k.php">
                                <span class="nav-left">
                                    <i class='bx bx-search-alt nav-icon'></i>
                                    <span class="nav-label">Auditorías</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="../PDRs_Fechas/pdr_fechas_7k.php">
                                <span class="nav-left">
                                    <i class='bx bx-calendar nav-icon'></i>
                                    <span class="nav-label">PDRs y Fechas</span>
                                </span>
                            </a>
                        </li>
                        <li class="active">
                            <a href="comparacion_bases_index.php">
                                <span class="nav-left">
                                    <i class='bx bx-transfer-alt nav-icon'></i>
                                    <span class="nav-label">Comparación Bases</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- MinTIC ODH 5G deshabilitado -->
                <li class="nav-item">
                    <a href="#" class="disabled-link nav-row">
                        <span class="nav-left">
                            <i class='bx bx-folder-open nav-icon'></i>
                            <span class="nav-label">MinTIC ODH 5G</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../../configuracion/configuracion.php" class="nav-row">
                        <span class="nav-left">
                            <i class='bx bx-cog nav-icon'></i>
                            <span class="nav-label">Configuración</span>
                        </span>
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 1): // Solo Admin ?>
                <li class="nav-item">
                    <a href="../gestion_usuario/gestion_usuarios.php" class="nav-row">
                        <span class="nav-left">
                            <i class='bx bx-user-plus nav-icon'></i>
                            <span class="nav-label">Gestión de Usuarios</span>
                        </span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class='bx bx-user'></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $nombre; ?></span>
                    <span class="user-role"><?php echo $nombreRol; ?></span>
                </div>
            </div>
            
            <div class="sidebar-actions">
                <button class="sidebar-btn" id="darkModeToggle">
                    <i class='bx <?php echo $_SESSION['dark_mode'] ? 'bx-sun' : 'bx-moon'; ?>'></i>
                    <span><?php echo $_SESSION['dark_mode'] ? 'Modo Claro' : 'Modo Oscuro'; ?></span>
                </button>
                
                <a href="../../../logout.php" class="sidebar-btn">
                    <i class='bx bx-log-out'></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Comparación de Bases - Paradas (P) - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="comparacion_bases_index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver a Comparación de Bases
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Comparación de Paradas (P)</h2>
                    <p>Herramientas especializadas para comparar y validar la consistencia de las paradas con bandera 'P' entre diferentes bases de datos del proyecto MinTIC 7K. Detecta discrepancias en fechas y motivos de parada.</p>
                    <div class="badges">
                        <div class="badge">
                            <i class='bx bx-time-five'></i>
                            <span>Fechas de Parada</span>
                        </div>
                        <div class="badge">
                            <i class='bx bx-transfer-alt'></i>
                            <span>Comparación Cruzada</span>
                        </div>
                        <div class="badge">
                            <i class='bx bx-check-double'></i>
                            <span>Detección de Discrepancias</span>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Resultados de Comparación - Fechas Apertura Paradas -->
            <div class="results-container">
                <div class="results-header">
                    <h2 class="results-title">Fecha de Apertura Paradas</h2>
                    <div class="results-count">
                        <?php 
                        if (isset($discrepanciasApertura['error'])) {
                            echo "Error";
                        } else {
                            echo count($discrepanciasApertura) . " Discrepancias";
                        }
                        ?>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll">
                        <table class="comparison-table" id="tableApertura">
                            <thead>
                                <tr>
                                    <th>ID de incidente <i class='bx bx-sort'></i></th>
                                    <th>fecha_apertura <i class='bx bx-sort'></i></th>
                                    <th>Fecha inicio parada de reloj <i class='bx bx-sort'></i></th>
                                    <th>Diferencia (segundos) <i class='bx bx-sort'></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($discrepanciasApertura['error'])): ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            Error: <?php echo $discrepanciasApertura['error']; ?>
                                        </td>
                                    </tr>
                                <?php elseif (count($discrepanciasApertura) > 0): ?>
                                    <?php foreach ($discrepanciasApertura as $fila): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['fecha_apertura'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['Fecha inicio parada de reloj'] ?? 'N/A'); ?></td>
                                            <td style="color: <?php echo ($fila['_debug']['diferencia_segundos'] ?? 0) > 5 ? 'red' : 'orange'; ?>">
                                                <?php echo $fila['_debug']['diferencia_segundos'] ?? 'N/A'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            No se encontraron discrepancias en las fechas de apertura de paradas
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class='bx bx-refresh'></i>
                        Actualizar Resultados
                    </button>
                </div>
            </div>

            <!-- Resultados de Comparación - Fechas Cierre Paradas -->
            <div class="results-container" style="margin-top: 3rem;">
                <div class="results-header">
                    <h2 class="results-title">Fecha de Cierre Paradas</h2>
                    <div class="results-count">
                        <?php 
                        if (isset($discrepanciasCierre['error'])) {
                            echo "Error";
                        } else {
                            echo count($discrepanciasCierre) . " Discrepancias";
                        }
                        ?>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll">
                        <table class="comparison-table" id="tableCierre">
                            <thead>
                                <tr>
                                    <th>ID de incidente <i class='bx bx-sort'></i></th>
                                    <th>fecha_cierre <i class='bx bx-sort'></i></th>
                                    <th>Fecha fin parada de reloj <i class='bx bx-sort'></i></th>
                                    <th>Diferencia (segundos) <i class='bx bx-sort'></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($discrepanciasCierre['error'])): ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            Error: <?php echo $discrepanciasCierre['error']; ?>
                                        </td>
                                    </tr>
                                <?php elseif (count($discrepanciasCierre) > 0): ?>
                                    <?php foreach ($discrepanciasCierre as $fila): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['fecha_cierre'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['Fecha fin parada de reloj'] ?? 'N/A'); ?></td>
                                            <td style="color: <?php echo ($fila['_debug']['diferencia_segundos'] ?? 0) > 5 ? 'red' : 'orange'; ?>">
                                                <?php echo $fila['_debug']['diferencia_segundos'] ?? 'N/A'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            No se encontraron discrepancias en las fechas de cierre de paradas
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class='bx bx-refresh'></i>
                        Actualizar Resultados
                    </button>
                </div>
            </div>

            <!-- Resultados de Comparación - Motivos Parada -->
            <div class="results-container" style="margin-top: 3rem;">
                <div class="results-header">
                    <h2 class="results-title">Comparación Motivo de Parada</h2>
                    <div class="results-count">
                        <?php 
                        if (isset($discrepanciasMotivos['error'])) {
                            echo "Error";
                        } else {
                            echo count($discrepanciasMotivos) . " Discrepancias";
                        }
                        ?>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll">
                        <table class="comparison-table" id="tableMotivos">
                            <thead>
                                <tr>
                                    <th>ID de incidente <i class='bx bx-sort'></i></th>
                                    <th>motivo_parada <i class='bx bx-sort'></i></th>
                                    <th>Motivo parada de reloj <i class='bx bx-sort'></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($discrepanciasMotivos['error'])): ?>
                                    <tr>
                                        <td colspan="3" class="no-results">
                                            Error al cargar los datos: <?php echo $discrepanciasMotivos['error']; ?>
                                        </td>
                                    </tr>
                                <?php elseif (count($discrepanciasMotivos) > 0): ?>
                                    <?php foreach ($discrepanciasMotivos as $fila): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['motivo_parada'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($fila['Motivo parada de reloj'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="no-results">
                                            No se encontraron discrepancias en los motivos de parada
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class='bx bx-refresh'></i>
                        Actualizar Resultados
                    </button>
                </div>
            </div>


                <div style="margin-top: 1.5rem; text-align: right;">
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class='bx bx-refresh'></i>
                        Actualizar Resultados
                    </button>
                </div>
            </div>

            
            
        </div>
    </div>

    <script>
        // ===== JAVASCRIPT PARA MANEJO DE SUBMENÚ (IGUAL QUE MÓDULOS ANTERIORES) =====
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos DOM para sidebar y modo oscuro
            const body = document.getElementById('body');
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const darkModeToggle = document.getElementById('darkModeToggle');

            // Elementos específicos para el menú MinTIC 7K
            const mintic7kDropdown = document.querySelector('.nav-item.dropdown.current-section');
            const mintic7kToggle = mintic7kDropdown?.querySelector('.dropdown-toggle');
            const comparacionBasesLink = document.querySelector('.dropdown-menu .active a');

            // Función para actualizar el estado visual del menú
            function updateMenuState() {
                const isDropdownOpen = mintic7kDropdown.classList.contains('active');
                
                if (isDropdownOpen) {
                    // Cuando el submenú está abierto: quitar rojo del elemento principal, mostrar rojo en Comparación Bases
                    mintic7kDropdown.classList.remove('current-section');
                    if (comparacionBasesLink) {
                        comparacionBasesLink.closest('li').classList.add('active');
                    }
                } else {
                    // Cuando el submenú está cerrado: mostrar rojo en el elemento principal
                    mintic7kDropdown.classList.add('current-section');
                    if (comparacionBasesLink) {
                        comparacionBasesLink.closest('li').classList.remove('active');
                    }
                }
            }

            // Toggle sidebar
            function toggleSidebar() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                }
            }

            // Toggle dark mode con AJAX
            function toggleDarkMode() {
                const isDarkMode = body.classList.toggle('dark-mode');
                
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
                fetch('../../includes/toggle_dark_mode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'toggle_dark_mode=true'
                }).catch(err => console.error('Error al cambiar modo oscuro:', err));
            }

            // Manejo del menú desplegable
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.parentElement;
                    const isCurrentSection = dropdown.classList.contains('current-section');
                    
                    dropdown.classList.toggle('active');
                    
                    // Actualizar estado visual del menú
                    updateMenuState();
                    
                    // Cerrar otros dropdowns abiertos
                    document.querySelectorAll('.nav-item.dropdown').forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('active');
                            otherDropdown.classList.remove('current-section');
                        }
                    });
                });
            });

            // Cerrar dropdowns al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-item.dropdown')) {
                    document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                        // Si es el menú MinTIC 7K, restaurar el estado de sección actual
                        if (dropdown.classList.contains('current-section')) {
                            updateMenuState();
                        }
                    });
                }
            });

            // Cerrar dropdowns al redimensionar (en móvil)
            window.addEventListener('resize', function() {
                if (window.innerWidth < 1024) {
                    document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                        updateMenuState();
                    });
                }
            });

            // Event listeners
            sidebarToggle.addEventListener('click', toggleSidebar);
            closeSidebar.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
            darkModeToggle.addEventListener('click', toggleDarkMode);

            // Cerrar sidebar al hacer clic en enlaces (mobile)
            document.querySelectorAll('.nav-item a').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });

            // Cerrar menús desplegables al hacer clic en un enlace (móvil)
            document.querySelectorAll('.dropdown-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        this.closest('.nav-item.dropdown').classList.remove('active');
                        updateMenuState();
                    }
                });
            });

            // Prevenir clic en enlaces deshabilitados
            document.querySelectorAll('.disabled-link').forEach(disabledElement => {
                disabledElement.addEventListener('click', function(e) {
                    e.preventDefault();
                });
            });

            // Ajustar al redimensionar
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                updateMenuState();
            });

            // Inicializar estado del menú
            updateMenuState();
        });

        // ===== FUNCIONALIDAD PARA ORDENAR TABLAS =====
        document.addEventListener('DOMContentLoaded', function() {
            // Función para ordenar tablas
            function sortTable(table, column, direction) {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isNumeric = column === 1 || column === 2; // Asumiendo que las columnas 1 y 2 son fechas
                
                rows.sort((a, b) => {
                    const aText = a.cells[column].textContent.trim();
                    const bText = b.cells[column].textContent.trim();
                    
                    if (isNumeric) {
                        // Para fechas, convertimos a timestamp para comparar
                        const aDate = new Date(aText);
                        const bDate = new Date(bText);
                        return direction === 'asc' ? aDate - bDate : bDate - aDate;
                    } else {
                        // Para texto, comparamos alfabéticamente
                        return direction === 'asc' 
                            ? aText.localeCompare(bText) 
                            : bText.localeCompare(aText);
                    }
                });
                
                // Remover todas las filas
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }
                
                // Agregar filas ordenadas
                rows.forEach(row => tbody.appendChild(row));
            }
            
            // Agregar event listeners a los encabezados de las tablas
            document.querySelectorAll('.comparison-table th').forEach(header => {
                header.addEventListener('click', function() {
                    const table = this.closest('table');
                    const columnIndex = Array.from(this.parentNode.cells).indexOf(this);
                    const currentDirection = this.getAttribute('data-sort') || 'asc';
                    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                    
                    // Remover indicadores de ordenamiento de todos los encabezados
                    table.querySelectorAll('th').forEach(th => {
                        th.removeAttribute('data-sort');
                        const icon = th.querySelector('i');
                        if (icon) {
                            icon.className = 'bx bx-sort';
                        }
                    });
                    
                    // Establecer nuevo ordenamiento
                    this.setAttribute('data-sort', newDirection);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = newDirection === 'asc' ? 'bx bx-sort-up' : 'bx bx-sort-down';
                    }
                    
                    // Ordenar la tabla
                    sortTable(table, columnIndex, newDirection);
                });
            });  
        });
    </script>
</body>
</html>