<?php
// modules/MinTIC_7k/auditoria/auditoria_7k.php
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

require_once '../../../config.php';
require_once '../../../includes/sidebar_config.php';

// Cerrar conexión temprano ya que usaremos AJAX
if (isset($conn_ic)) {
    $conn_ic->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* COLORES PRINCIPALES MÁS AUTÉNTICOS - ROJO CLARO CARACTERÍSTICO */
        :root {
            --primary-color: #E10000;
            --secondary-color: #CC0000;
            --accent-color: #FF5252;
            --dark-red: #B30000;
            --light-red: #FF6B6B;
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

        /* Modo Oscuro con tonos rojos */
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
            --primary-color: #FF4444;
            --secondary-color: #E10000;
            --accent-color: #FF6B6B;
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
            box-shadow: 0 2px 10px rgba(225, 0, 0, 0.1);
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

        /* Welcome Card - Estilo más auténtico */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-red) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(225, 0, 0, 0.3);
            border-left: 4px solid var(--light-red);
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
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .welcome-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
            position: relative;
            z-index: 2;
        }

        /* Internal Navigation - Rojo más auténtico */
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
            position: relative;
        }

        .nav-tab:hover {
            color: var(--primary-color);
            background: rgba(225, 0, 0, 0.05);
        }

        .nav-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: rgba(225, 0, 0, 0.08);
        }

        .nav-tab i {
            font-size: 1.2rem;
        }

        /* Stats Cards - Colores rojos unificados - SOLO EN VALIDACIONES KM */
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
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(225, 0, 0, 0.15);
            border-left-color: var(--dark-red);
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
        .stat-icon.dark-red { 
            background: linear-gradient(135deg, var(--dark-red), #990000); 
            color: white; 
        }
        .stat-icon.light-red { 
            background: linear-gradient(135deg, var(--light-red), var(--primary-color)); 
            color: white; 
        }
        .stat-icon.orange { 
            background: linear-gradient(135deg, #FF6B35, #E64A19); 
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
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(225, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
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

        /* Audit Cards - Encabezados en rojo */
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
            box-shadow: 0 10px 25px rgba(225, 0, 0, 0.12);
        }

        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-red));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            backdrop-filter: blur(10px);
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
            background: var(--card-bg); /* FONDO OPACO - CORREGIDO */
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .modern-table th {
            padding: 0.8rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.85rem;
            background: var(--card-bg); /* FONDO OPACO - CORREGIDO */
        }

        .modern-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background: rgba(225, 0, 0, 0.03);
        }

        .full-width-card {
            grid-column: 1 / -1;
        }

        /* Filters - Estilo rojo */
        .filter-bar {
            background: rgba(225, 0, 0, 0.03);
            padding: 1rem 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-color);
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
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(225, 0, 0, 0.1);
            outline: none;
        }

        /* Botón Quitar Filtros - Más estético */
        .clear-filters-button {
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-red));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(225, 0, 0, 0.2);
            font-size: 0.9rem;
        }

        .clear-filters-button:hover {
            background: linear-gradient(135deg, var(--dark-red), #990000);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        /* Accordion - Estilo rojo */
        .accordion-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .accordion-item:hover {
            border-color: var(--light-red);
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
            background: rgba(225, 0, 0, 0.03);
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

        .priority-badge.p1 { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .priority-badge.p2 { background: #fed7aa; color: #ea580c; border: 1px solid #fdba74; }
        .priority-badge.p3 { background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; }

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
            color: var(--primary-color);
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

        /* Estilos para texto en tablas - Colores específicos */
        .text-red {
            color: var(--primary-color);
            font-weight: 600;
        }

        .text-green {
            color: var(--success-color);
            font-weight: 600;
        }

        .text-dark-red {
            color: var(--dark-red);
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
            color: var(--primary-color);
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
            background: var(--dark-red);
            transform: translateX(-2px);
        }

        .back-button-header i {
            font-size: 1.1rem;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
        }

        .loading i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            .internal-nav { padding: 0 1rem; }
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
                <h1>Auditoría MinTIC 7K</h1>
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
                Prioridad vs Título
            </div>
            <div class="nav-tab" data-tab="fallas">
                <i class='bx bx-bug'></i>
                Fallas Masivas
            </div>
            <div class="nav-tab" data-tab="sitios">
                <i class='bx bx-map'></i>
                Validación Sitios
            </div>
        </nav>

        <div class="content-area">
            <!-- TAB 1: Validaciones KM -->
            <div class="tab-content active" id="tab-validaciones">
                <!-- Welcome Card Reducida -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h2>Auditoría MinTIC 7K</h2>
                        <p>Desde aquí podrás ejecutar revisiones, validar campos críticos y generar hallazgos para mantener la calidad de los datos del proyecto MinTIC 7K.</p>
                    </div>
                </div>

                <!-- Stats Cards - SOLO EN VALIDACIONES KM -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class='bx bx-file'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Hallazgos</h3>
                            <div class="stat-value" id="stat-total">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon dark-red">
                            <i class='bx bx-error-circle'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Validaciones KM</h3>
                            <div class="stat-value" id="stat-validaciones">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon light-red">
                            <i class='bx bx-map-pin'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Sitios Vacíos</h3>
                            <div class="stat-value" id="stat-sitios">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class='bx bx-shield-x'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Fallas Masivas</h3>
                            <div class="stat-value" id="stat-fallas">0</div>
                        </div>
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
                    <!-- KM3201 -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-bookmark'></i>
                                KM3201 sin Requerimiento Rechazado
                            </span>
                            <span class="card-badge" id="badge-km3201">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-km3201">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                            <span class="card-badge" id="badge-tabla2">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-tabla2">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KMs no correspondientes - OCUPA TODA LA FILA -->
                    <div class="audit-card full-width-row">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-x-circle'></i>
                                KMs No Correspondientes MinTIC 7K
                            </span>
                            <span class="card-badge" id="badge-kms_no_correspondientes">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-kms_no_correspondientes">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                        <span class="card-badge" id="badge-responsabilidad_km">0</span>
                    </div>
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label class="filter-label">Ticket</label>
                            <input type="text" class="filter-input" id="filter-ticket" placeholder="Buscar ticket...">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Responsabilidad Asignada</label>
                            <select class="filter-input" id="filter-responsabilidad">
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Tipificación de Falla</label>
                            <select class="filter-input" id="filter-tipificacion">
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <button class="clear-filters-button" id="reset-filters">
                            <i class='bx bx-reset'></i> Quitar Filtros
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-wrapper" style="max-height: 500px;">
                            <div id="table-responsabilidad_km">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Prioridades vs Título -->
            <div class="tab-content" id="tab-prioridades">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-error'></i>
                        Prioridad vs Título
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
                            <span class="accordion-count" id="count-prioridad_p1">0</span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper" id="table-prioridad_p1">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                            <span class="accordion-count" id="count-prioridad_p2">0</span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper" id="table-prioridad_p2">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                            <span class="accordion-count" id="count-prioridad_p3">0</span>
                            <i class='bx bx-chevron-down accordion-icon'></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="table-wrapper" id="table-prioridad_p3">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                            <span class="card-badge" id="badge-incidente_mayor">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-incidente_mayor">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-server'></i>
                                Fallas Masivas con un Solo CI
                            </span>
                            <span class="card-badge" id="badge-fallas_masivas_un_ci">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-fallas_masivas_un_ci">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
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
                            <span class="card-badge" id="badge-tabla3">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" style="max-height: 350px;" id="table-tabla3">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="audit-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-search-alt'></i>
                                Sitios No Encontrados en Base de Última Milla
                            </span>
                            <span class="card-badge" id="badge-sitios_no_encontrados">0</span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper" id="table-sitios_no_encontrados">
                                <div class="loading">
                                    <i class='bx bx-loader'></i>
                                    <p>Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let responsabilidadKmData = [];
        let filteredResponsabilidadKmData = [];

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
                
                // Cargar datos de la pestaña activa
                loadTabData(tabId);
            });
        });

        // Cargar datos de una pestaña específica
        function loadTabData(tabId) {
            const tables = {
                'validaciones': ['km3201', 'tabla2', 'kms_no_correspondientes', 'responsabilidad_km'],
                'prioridades': ['prioridad_p1', 'prioridad_p2', 'prioridad_p3'],
                'fallas': ['incidente_mayor', 'fallas_masivas_un_ci'],
                'sitios': ['tabla3', 'sitios_no_encontrados']
            };

            if (tabId === 'validaciones') {
                loadStats();
            }

            tables[tabId]?.forEach(tableId => {
                loadTableData(tableId);
            });
        }

        // Cargar datos de una tabla específica
        function loadTableData(tableId) {
            fetch(`load_table_data.php?table=${tableId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateTableUI(tableId, data);
                        
                        // Inicializar filtros para responsabilidad_km
                        if (tableId === 'responsabilidad_km') {
                            initializeResponsabilidadFilters(data.rows);
                        }
                    } else {
                        document.getElementById(`table-${tableId}`).innerHTML = 
                            `<div class="empty-state"><i class='bx bx-error-circle'></i><p>Error: ${data.error}</p></div>`;
                    }
                })
                .catch(error => {
                    document.getElementById(`table-${tableId}`).innerHTML = 
                        `<div class="empty-state"><i class='bx bx-error-circle'></i><p>Error de conexión</p></div>`;
                });
        }

        // Cargar estadísticas
        function loadStats() {
            // Calcular stats basado en las tablas cargadas
            const tables = ['km3201', 'tabla2', 'kms_no_correspondientes', 'responsabilidad_km', 
                          'tabla3', 'incidente_mayor', 'fallas_masivas_un_ci'];
            
            let total = 0;
            let validacionesKM = 0;
            let sitiosVacios = 0;
            let fallasMasivas = 0;

            tables.forEach(tableId => {
                const badge = document.getElementById(`badge-${tableId}`);
                if (badge) {
                    const count = parseInt(badge.textContent) || 0;
                    total += count;
                    
                    if (['km3201', 'tabla2', 'kms_no_correspondientes', 'responsabilidad_km'].includes(tableId)) {
                        validacionesKM += count;
                    } else if (tableId === 'tabla3') {
                        sitiosVacios += count;
                    } else if (['incidente_mayor', 'fallas_masivas_un_ci'].includes(tableId)) {
                        fallasMasivas += count;
                    }
                }
            });

            document.getElementById('stat-total').textContent = total;
            document.getElementById('stat-validaciones').textContent = validacionesKM;
            document.getElementById('stat-sitios').textContent = sitiosVacios;
            document.getElementById('stat-fallas').textContent = fallasMasivas;
        }

        // Actualizar la UI de una tabla
        function updateTableUI(tableId, data) {
            const container = document.getElementById(`table-${tableId}`);
            const badge = document.getElementById(`badge-${tableId}`);
            const countElement = document.getElementById(`count-${tableId}`);
            
            if (badge) badge.textContent = data.count;
            if (countElement) countElement.textContent = data.count;
            
            container.innerHTML = renderTableModern(tableId, data);
        }

        // Renderizar tabla moderna
        function renderTableModern(tableId, data) {
            const config = {
                'km3201': {
                    columns: ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla'],
                    headers: ['Ticket', 'Acciones Correctivas', 'Tipificación']
                },
                'tabla2': {
                    columns: ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla'],
                    headers: ['Ticket', 'Acciones Correctivas', 'Tipificación']
                },
                'tabla3': {
                    columns: ['numero_ticket', 'sem_id_beneficiario', 'sem_cod_servicio'],
                    headers: ['Ticket', 'ID Beneficiario', 'Código Servicio']
                },
                'prioridad_p1': {
                    columns: ['ID de incidente', 'Prioridad', 'Título'],
                    headers: ['ID Incidente', 'Prioridad', 'Título']
                },
                'prioridad_p2': {
                    columns: ['ID de incidente', 'Prioridad', 'Título'],
                    headers: ['ID Incidente', 'Prioridad', 'Título']
                },
                'prioridad_p3': {
                    columns: ['ID de incidente', 'Prioridad', 'Título'],
                    headers: ['ID Incidente', 'Prioridad', 'Título']
                },
                'incidente_mayor': {
                    columns: ['ID de incidente', 'Título', 'Incidente Mayor'],
                    headers: ['ID Incidente', 'Título', 'Incidente Mayor']
                },
                'fallas_masivas_un_ci': {
                    columns: ['ID de incidente', 'Título', 'CI Relacionados'],
                    headers: ['ID Incidente', 'Título', 'CI Relacionados']
                },
                'responsabilidad_km': {
                    columns: ['numero_ticket', 'responsabilidad_por_ticket', 'acciones_correctivas', 'tipificacion_falla', 'causa_principal', 'responsabilidad_correcta'],
                    headers: ['Ticket', 'Responsabilidad Asignada', 'KM', 'Tipificación de Falla', 'Causa Principal', 'Responsabilidad Correcta']
                },
                'kms_no_correspondientes': {
                    columns: ['numero_ticket', 'acciones_correctivas', 'tipificacion_falla', 'causa_principal', 'responsabilidad_correcta'],
                    headers: ['Ticket', 'KM', 'Tipificación', 'Causa', 'Responsabilidad']
                },
                'sitios_no_encontrados': {
                    columns: ['numero_ticket', 'sem_id_beneficiario', 'sem_cod_servicio', 'observacion'],
                    headers: ['Ticket', 'ID Beneficiario', 'Cód. Servicio', 'Observación']
                }
            };

            if (!data.success || !data.rows || data.rows.length === 0) {
                return `<div class='empty-state'><i class='bx bx-inbox'></i><p>No hay datos para mostrar</p></div>`;
            }

            const tableConfig = config[tableId];
            if (!tableConfig) {
                return `<div class='empty-state'><i class='bx bx-error'></i><p>Configuración no encontrada</p></div>`;
            }

            let html = `<table class='modern-table'><thead><tr>`;
            tableConfig.headers.forEach(header => {
                html += `<th>${escapeHtml(header)}</th>`;
            });
            html += `</tr></thead><tbody>`;
            
            data.rows.forEach(row => {
                html += `<tr>`;
                tableConfig.columns.forEach(column => {
                    let value = row[column] ?? 'N/A';
                    if (value === null || value === '') {
                        value = '<em>Vacío</em>';
                    } else {
                        value = escapeHtml(value);
                    }
                    
                    // Aplicar colores específicos para responsabilidad_km
                    if (tableId === 'responsabilidad_km') {
                        if (column === 'responsabilidad_por_ticket') {
                            value = `<span class="text-red">${value}</span>`;
                        } else if (column === 'responsabilidad_correcta') {
                            value = `<span class="text-green">${value}</span>`;
                        }
                    }
                    
                    html += `<td>${value}</td>`;
                });
                html += `</tr>`;
            });
            html += `</tbody></table>`;
            
            return html;
        }

        // Inicializar filtros para responsabilidad KM
        function initializeResponsabilidadFilters(rows) {
            responsabilidadKmData = rows;
            filteredResponsabilidadKmData = [...rows];
            
            // Llenar selects de filtros
            const responsabilidades = [...new Set(rows.map(row => row.responsabilidad_por_ticket).filter(Boolean))];
            const tipificaciones = [...new Set(rows.map(row => row.tipificacion_falla).filter(Boolean))];
            
            const responsabilidadSelect = document.getElementById('filter-responsabilidad');
            const tipificacionSelect = document.getElementById('filter-tipificacion');
            
            responsabilidades.forEach(resp => {
                responsabilidadSelect.innerHTML += `<option value="${escapeHtml(resp)}">${escapeHtml(resp)}</option>`;
            });
            
            tipificaciones.forEach(tip => {
                tipificacionSelect.innerHTML += `<option value="${escapeHtml(tip)}">${escapeHtml(tip)}</option>`;
            });
            
            // Agregar event listeners
            document.getElementById('filter-ticket').addEventListener('input', applyResponsabilidadKmFilters);
            responsabilidadSelect.addEventListener('change', applyResponsabilidadKmFilters);
            tipificacionSelect.addEventListener('change', applyResponsabilidadKmFilters);
            document.getElementById('reset-filters').addEventListener('click', resetResponsabilidadKmFilters);
            
            updateResponsabilidadKmCount();
        }

        // Aplicar filtros a responsabilidad KM
        function applyResponsabilidadKmFilters() {
            const ticketFilter = document.getElementById('filter-ticket').value.toLowerCase();
            const responsabilidadFilter = document.getElementById('filter-responsabilidad').value;
            const tipificacionFilter = document.getElementById('filter-tipificacion').value;

            filteredResponsabilidadKmData = responsabilidadKmData.filter(row => {
                const matchesTicket = !ticketFilter || 
                    (row.numero_ticket && row.numero_ticket.toLowerCase().includes(ticketFilter));
                const matchesResponsabilidad = !responsabilidadFilter || 
                    row.responsabilidad_por_ticket === responsabilidadFilter;
                const matchesTipificacion = !tipificacionFilter || 
                    row.tipificacion_falla === tipificacionFilter;

                return matchesTicket && matchesResponsabilidad && matchesTipificacion;
            });

            updateResponsabilidadKmCount();
            renderResponsabilidadKmTable();
        }

        function updateResponsabilidadKmCount() {
            const countElement = document.getElementById('badge-responsabilidad_km');
            if (countElement) {
                countElement.textContent = filteredResponsabilidadKmData.length;
            }
            loadStats(); // Actualizar stats
        }

        function renderResponsabilidadKmTable() {
            const container = document.getElementById('table-responsabilidad_km');
            if (!container) return;

            const columns = ['numero_ticket', 'responsabilidad_por_ticket', 'acciones_correctivas', 'tipificacion_falla', 'causa_principal', 'responsabilidad_correcta'];
            const headers = ['Ticket', 'Responsabilidad Asignada', 'KM', 'Tipificación de Falla', 'Causa Principal', 'Responsabilidad Correcta'];

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
                        
                        // Aplicar colores específicos
                        if (column === 'responsabilidad_por_ticket') {
                            value = `<span class="text-red">${escapeHtml(value)}</span>`;
                        } else if (column === 'responsabilidad_correcta') {
                            value = `<span class="text-green">${escapeHtml(value)}</span>`;
                        } else if (column === 'causa_principal') {
                            value = `<span class="text-dark-red">${escapeHtml(value)}</span>`;
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
            document.getElementById('filter-tipificacion').value = '';
            filteredResponsabilidadKmData = [...responsabilidadKmData];
            updateResponsabilidadKmCount();
            renderResponsabilidadKmTable();
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return 'N/A';
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

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

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar datos de la pestaña activa por defecto
            loadTabData('validaciones');
            
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