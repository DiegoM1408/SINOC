<?php
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

require_once '../../../config.php';

// Definir la página actual para la navegación
$current_page = 'tiempos_ODH.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiempos - MinTIC ODH - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* DISEÑO MODERNO ODH AZUL - MATCHING paradas_reloj_ODH.php */
        :root {
            --primary-blue: #0EA5E9;
            --secondary-blue: #0284C7;
            --accent-cyan: #06B6D4;
            --purple: #8B5CF6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --text-light: #ffffff;
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
        }

        body.dark-mode {
            --bg-main: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #f1f5f9;
            --text-gray: #94a3b8;
            --border-color: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-main);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* === OVERRIDE PARA OCULTAR SIDEBAR EN ESTA PÁGINA === */
        /* Ocultar sidebar por defecto en todos los tamaños */
        .sidebar {
            left: -280px !important;
        }

        /* Mostrar sidebar solo cuando tenga la clase active */
        .sidebar.active {
            left: 0 !important;
        }

        /* Main content ocupa todo el ancho por defecto */
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }

        /* Mostrar el botón X en desktop también */
        @media (min-width: 1024px) {
            .close-sidebar {
                display: flex !important;
            }
        }
        /* === FIN OVERRIDE === */

        /* Main Content */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: 100%;
        }

        /* HEADER MINIMALISTA ODH */
        .top-header {
            background: var(--card-bg);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
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
        }

        .sidebar-toggle:hover {
            background: var(--border-color);
        }

        .top-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
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

        /* HERO SECTION ODH */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, var(--purple) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            font-family: 'Poppins', sans-serif;
        }

        .hero-content p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 900px;
            line-height: 1.7;
            font-family: 'Poppins', sans-serif;
        }

        .badges {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
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
            font-family: 'Poppins', sans-serif;
        }

        .badge i {
            font-size: 1.2rem;
        }

        /* Botón de volver ODH */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .back-button:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        /* STATS CARDS ODH */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.8rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .stat-icon.blue { 
            background: linear-gradient(135deg, #0EA5E9, #0284C7); 
            color: white;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }
        .stat-icon.purple { 
            background: linear-gradient(135deg, #8B5CF6, #7C3AED); 
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .stat-icon.cyan { 
            background: linear-gradient(135deg, #06B6D4, #0891B2); 
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        .stat-icon.green { 
            background: linear-gradient(135deg, #10B981, #059669); 
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .stat-icon.orange { 
            background: linear-gradient(135deg, #F59E0B, #D97706); 
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .stat-info h3 {
            font-size: 0.85rem;
            color: var(--text-gray);
            font-weight: 500;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Poppins', sans-serif;
        }

        .stat-info .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
        }

        /* SECTION HEADER */
        .section-header {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-family: 'Poppins', sans-serif;
        }

        .section-title i {
            color: var(--primary-blue);
            font-size: 1.8rem;
        }

        .section-subtitle {
            color: var(--text-gray);
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
        }

        /* Estilos para las tablas ODH */
        .table-container {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .table-container:hover {
            box-shadow: var(--shadow-md);
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .table-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Poppins', sans-serif;
        }

        .count-badge {
            background: var(--primary-blue);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        /* Tabla con scroll vertical */
        .table-scroll-vertical {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .table-scroll-vertical .lazy-table {
            margin-bottom: 0;
        }

        .table-scroll-vertical .lazy-table thead th {
            position: sticky;
            top: 0;
            background: var(--bg-main);
            z-index: 10;
            box-shadow: 0 1px 0 var(--border-color);
        }

        .lazy-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .lazy-table th {
            background: var(--bg-main);
            color: var(--text-dark);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            font-family: 'Poppins', sans-serif;
        }

        .lazy-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
        }

        .lazy-table tr:hover {
            background: rgba(0, 0, 0, 0.02);
        }

        body.dark-mode .lazy-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .table-placeholder {
            text-align: center;
            padding: 3rem;
            color: var(--text-gray);
            font-size: 1.1rem;
            font-family: 'Poppins', sans-serif;
        }

        .table-placeholder i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .table-error {
            text-align: center;
            padding: 2rem;
            color: var(--danger);
            font-weight: 500;
            background: rgba(239, 68, 68, 0.05);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .no-data {
            text-align: center;
            color: var(--text-gray);
            font-style: italic;
            padding: 2rem;
            font-family: 'Poppins', sans-serif;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #b45309;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        body.dark-mode .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        /* Grid para tablas pequeñas lado a lado */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scrollbar personalizado ODH */
        .table-scroll-vertical::-webkit-scrollbar {
            width: 8px;
        }

        .table-scroll-vertical::-webkit-scrollbar-track {
            background: var(--bg-main);
            border-radius: 0 8px 8px 0;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb {
            background: var(--primary-blue);
            border-radius: 4px;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-blue);
        }

        /* Indicador de caché */
        .cache-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
            font-size: 0.7rem;
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }

        /* Responsive ODH */
        @media (max-width: 1023px) {
            .content-area {
                padding: 1.5rem;
            }
            
            .hero-section {
                padding: 2rem 1.5rem;
            }
            
            .hero-content h1 {
                font-size: 1.8rem;
            }
            
            .badges {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .hero-section {
                padding: 1.5rem 1rem;
            }
            
            .hero-content h1 {
                font-size: 1.5rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .table-container {
                padding: 1.5rem;
            }
            
            .table-scroll-vertical {
                max-height: 400px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 1.2rem;
            }

            .stat-icon {
                width: 55px;
                height: 55px;
                font-size: 1.6rem;
            }
        }

        /* ANIMACIONES ODH */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            animation: fadeInUp 0.6s ease forwards;
        }

        .table-container:nth-child(1) { animation-delay: 0.1s; }
        .table-container:nth-child(2) { animation-delay: 0.2s; }

        .stat-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    
    <?php 
    // Incluir la sidebar centralizada
    require_once '../../../includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Tiempos - MinTIC ODH</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="PDRs_Fechas_index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver a MinTIC ODH
            </a>

            <!-- Hero Section ODH -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1>⏰ Tiempos - MinTIC ODH 5G</h1>
                    <p>Auditoría completa de tiempos y fechas en incidentes del proyecto MinTIC ODH. Detecta inconsistencias en fechas de interrupción, apertura, cierre y tiempos muertos en los registros.</p>
                    <div class="badges">
                        <span class="badge"><i class='bx bx-time'></i> Auditoría de Tiempos</span>
                        <span class="badge"><i class='bx bx-calendar-check'></i> Validación de Fechas</span>
                        <span class="badge"><i class='bx bx-error-alt'></i> Detección de Inconsistencias</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class='bx bx-time'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Interrupción Mayor a Apertura</h3>
                        <div class="stat-value" id="count-interrupcion_mayor_apertura">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class='bx bx-calendar'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Mes Diferente</h3>
                        <div class="stat-value" id="count-mes_diferente">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cyan">
                        <i class='bx bx-calendar-x'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Fechas en Blanco</h3>
                        <div class="stat-value" id="count-fechas_blanco_total">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class='bx bx-timer'></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tiempos Negativos</h3>
                        <div class="stat-value" id="count-tiempos_muertos">0</div>
                    </div>
                </div>
            </div>

            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">
                    <i class='bx bx-shield-alt-2'></i>
                    Resultados de Auditoría
                </h2>
                <p class="section-subtitle">Validación de coherencia temporal en fechas y tiempos de incidentes</p>
            </div>

            <!-- Tabla 1: Interrupción mayor a apertura - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-time'></i> Fecha de Interrupción Mayor a Fecha de Apertura
                        <span class="count-badge" id="count-interrupcion_mayor_apertura_badge">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-interrupcion_mayor_apertura" data-table="interrupcion_mayor_apertura">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla 2: Mes diferente - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-calendar'></i> Mes de Interrupción Diferente al Mes de Apertura
                        <span class="count-badge" id="count-mes_diferente_badge">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-mes_diferente" data-table="mes_diferente">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid para tablas pequeñas de fechas en blanco -->
            <div class="tables-grid">
                <!-- Tabla 3: Fechas en blanco (Bandera P) -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class='bx bx-calendar-x'></i> Fechas en Blanco (Bandera P)
                            <span class="count-badge" id="count-fechas_blanco_p_badge">0</span>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <div class="lazy-table-container" id="table-fechas_blanco_p" data-table="fechas_blanco_p">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla 4: Fechas en blanco (Bandera O) -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class='bx bx-calendar-x'></i> Fechas en Blanco (Bandera O)
                            <span class="count-badge" id="count-fechas_blanco_o_badge">0</span>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <div class="lazy-table-container" id="table-fechas_blanco_o" data-table="fechas_blanco_o">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla 5: Tiempos muertos - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-timer'></i> Tiempos Negativos
                        <span class="count-badge" id="count-tiempos_muertos_badge">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-tiempos_muertos" data-table="tiempos_muertos">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración para las tablas de tiempos
        const tableConfig = {
            'interrupcion_mayor_apertura': {
                columns: ['ID de incidente', 'Inicio de la interrupción de servicio', 'Fecha/hora de apertura'],
                headers: ['ID de Incidente', 'Inicio Interrupción', 'Fecha/Hora Apertura']
            },
            'mes_diferente': {
                columns: ['ID de incidente', 'Inicio de la interrupción de servicio', 'Fecha/hora de apertura'],
                headers: ['ID de Incidente', 'Inicio Interrupción', 'Fecha/Hora Apertura']
            },
            'fechas_blanco_p': {
                columns: ['numero_ticket', 'fecha_apertura', 'fecha_cierre'],
                headers: ['Número de Ticket', 'Fecha Apertura', 'Fecha Cierre']
            },
            'fechas_blanco_o': {
                columns: ['numero_ticket', 'fecha_apertura', 'fecha_cierre'],
                headers: ['Número de Ticket', 'Fecha Apertura', 'Fecha Cierre']
            },
            'tiempos_muertos': {
                columns: ['numero_ticket', 'motivo_parada', 'tiempos_muertos'],
                headers: ['Número de Ticket', 'Motivo de Parada', 'Tiempos Muertos Detectados']
            }
        };

        // Sistema de caché
        const CACHE_DURATION = 5 * 60 * 1000; // 5 minutos en milisegundos
        const tableCache = new Map();

        // Función para obtener datos del caché
        function getCachedData(tableId) {
            const cached = localStorage.getItem(`cache_tiempos_${tableId}`);
            if (!cached) return null;

            const { data, timestamp } = JSON.parse(cached);
            const now = Date.now();

            // Verificar si el caché ha expirado
            if (now - timestamp > CACHE_DURATION) {
                localStorage.removeItem(`cache_tiempos_${tableId}`);
                return null;
            }

            return data;
        }

        // Función para guardar datos en caché
        function setCachedData(tableId, data) {
            const cacheItem = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem(`cache_tiempos_${tableId}`, JSON.stringify(cacheItem));
            tableCache.set(tableId, data);
        }

        // Función para escapar HTML
        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return unsafe
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Función para formatear fechas
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('es-ES');
        }

        // Función para renderizar una tabla
        function renderTable(tableId, data, fromCache = false) {
            const container = document.getElementById(`table-${tableId}`);
            const countBadge = document.getElementById(`count-${tableId}_badge`);
            const statValue = document.getElementById(`count-${tableId}`);
            
            if (!container) return;
            
            if (!data.success) {
                container.innerHTML = `<div class="table-error">Error: ${escapeHtml(data.error)}</div>`;
                return;
            }
            
            const config = tableConfig[tableId];
            if (!config) {
                container.innerHTML = `<div class="table-error">Configuración no encontrada para la tabla</div>`;
                return;
            }
            
            const count = data.count || data.rows.length;
            
            // Actualizar contadores
            if (countBadge) {
                countBadge.textContent = count;
            }
            if (statValue) {
                statValue.textContent = count;
            }
            
            // Actualizar total de fechas en blanco
            if (tableId === 'fechas_blanco_p' || tableId === 'fechas_blanco_o') {
                updateFechasBlancoTotal();
            }
            
            // Crear tabla
            let html = `<table class="lazy-table">`;
            html += `<thead><tr>`;
            config.headers.forEach(header => {
                html += `<th>${escapeHtml(header)}</th>`;
            });
            html += `</tr></thead>`;
            html += `<tbody>`;
            
            if (data.rows.length === 0) {
                html += `<tr><td colspan="${config.headers.length}" class="no-data">No se encontraron registros</td></tr>`;
            } else {
                data.rows.forEach(row => {
                    html += `<tr>`;
                    
                    if (tableId === 'tiempos_muertos') {
                        // Renderizado especial para tiempos muertos
                        html += `<td>${escapeHtml(row.numero_ticket)}</td>`;
                        html += `<td>${escapeHtml(row.motivo_parada || 'Sin motivo')}</td>`;
                        html += `<td>`;
                        if (row.tiempos_muertos && row.tiempos_muertos.length > 0) {
                            row.tiempos_muertos.forEach((tiempo, index) => {
                                if (index === 0) {
                                    html += `${escapeHtml(tiempo.desde + ' → ' + tiempo.hasta)}<br>`;
                                    html += `<span class="badge-warning">${tiempo.minutos_muertos} min</span>`;
                                }
                            });
                            if (row.tiempos_muertos.length > 1) {
                                html += `<br><small style="color: var(--text-gray);">(+ ${row.tiempos_muertos.length - 1} tiempo(s) más)</small>`;
                            }
                        } else {
                            html += `No hay tiempos muertos`;
                        }
                        html += `</td>`;
                    } else {
                        // Renderizado normal para otras tablas
                        config.columns.forEach(column => {
                            let value = row[column];
                            // Formatear fechas si es necesario
                            if (column.includes('fecha') || column.includes('Fecha')) {
                                value = formatDate(value);
                            }
                            // Resaltar campos vacíos o nulos
                            if (value === null || value === '' || value === 'NULL') {
                                html += `<td><span class="badge-warning">VACÍO</span></td>`;
                            } else {
                                html += `<td title="${escapeHtml(value)}">${escapeHtml(value)}</td>`;
                            }
                        });
                    }
                    
                    html += `</tr>`;
                });
            }
            
            html += `</tbody>`;
            html += `</table>`;
            
            container.innerHTML = html;
        }

        // Función para actualizar el total de fechas en blanco
        function updateFechasBlancoTotal() {
            const countP = parseInt(document.getElementById('count-fechas_blanco_p')?.textContent || 0);
            const countO = parseInt(document.getElementById('count-fechas_blanco_o')?.textContent || 0);
            const totalElement = document.getElementById('count-fechas_blanco_total');
            
            if (totalElement) {
                totalElement.textContent = countP + countO;
            }
        }

        // Función para cargar una tabla
        async function loadTable(tableId) {
            const container = document.getElementById(`table-${tableId}`);
            
            // Verificar caché en memoria primero
            if (tableCache.has(tableId)) {
                renderTable(tableId, tableCache.get(tableId), true);
                return;
            }
            
            // Verificar caché en localStorage
            const cachedData = getCachedData(tableId);
            if (cachedData) {
                renderTable(tableId, cachedData, true);
                tableCache.set(tableId, cachedData);
                return;
            }
            
            // Si no hay caché, cargar desde el servidor
            try {
                const response = await fetch(`load_tiempos_ODH.php?table=${tableId}`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    // Guardar en caché
                    setCachedData(tableId, data);
                    renderTable(tableId, data, false);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                console.error(`Error cargando tabla ${tableId}:`, error);
                if (container) {
                    container.innerHTML = `<div class="table-error"><i class='bx bx-error-circle'></i> Error: ${error.message}</div>`;
                }
            }
        }

        // Cargar todas las tablas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainers = document.querySelectorAll('.lazy-table-container');
            
            tableContainers.forEach(container => {
                const tableId = container.getAttribute('data-table');
                loadTable(tableId);
            });

            // === OVERRIDE DEL COMPORTAMIENTO DE LA SIDEBAR PARA ESTA PÁGINA ===
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebarCustom() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            // Remover los event listeners existentes y agregar los nuevos
            if (sidebarToggle) {
                const newToggle = sidebarToggle.cloneNode(true);
                sidebarToggle.parentNode.replaceChild(newToggle, sidebarToggle);
                newToggle.addEventListener('click', toggleSidebarCustom);
            }

            if (closeSidebar) {
                const newClose = closeSidebar.cloneNode(true);
                closeSidebar.parentNode.replaceChild(newClose, closeSidebar);
                newClose.addEventListener('click', toggleSidebarCustom);
            }

            if (overlay) {
                const newOverlay = overlay.cloneNode(true);
                overlay.parentNode.replaceChild(newOverlay, overlay);
                newOverlay.addEventListener('click', toggleSidebarCustom);
            }

            // Cerrar sidebar al hacer clic en cualquier enlace
            document.querySelectorAll('.nav-item a:not(.dropdown-toggle)').forEach(item => {
                item.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
            // === FIN OVERRIDE ===

            // Manejo del modo oscuro ODH
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

            // Animación de entrada progresiva
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.table-container, .stat-card').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
