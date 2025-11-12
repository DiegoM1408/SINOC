<?php
// modules/comparacion_bases/comparar_bases.php
session_start();

// Definir la ruta base del proyecto
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));

// Incluir archivos usando rutas absolutas
require_once(ROOT_PATH . '../../includes/db.php');

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

// Obtener la página actual para el sidebar
$current_page = basename($_SERVER['PHP_SELF']);

require_once '../../../config.php';

// Incluir backend después de definir la conexión
include("comparar_bases_backend.php");

// Obtener datos directamente
$discrepancias = compararFechasApertura($conn);
$discrepanciasCierre = compararFechasCierre($conn);
$discrepanciasEstados = compararEstados($conn);

// Contadores
$countApertura = isset($discrepancias['error']) ? 0 : count($discrepancias);
$countCierre = isset($discrepanciasCierre['error']) ? 0 : count($discrepanciasCierre);
$countEstados = isset($discrepanciasEstados['error']) ? 0 : count($discrepanciasEstados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Bases - MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            line-height: 1.5;
        }

        /* === OVERRIDE PARA OCULTAR SIDEBAR EN ESTA PÁGINA === */
        .sidebar {
            left: -280px !important;
        }

        .sidebar.active {
            left: 0 !important;
        }

        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }

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

        .top-header {
            background: var(--card-bg);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: var(--text-dark);
            padding: 0.4rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .sidebar-toggle:hover {
            background: var(--bg-light);
            transform: scale(1.05);
        }

        .top-header h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 0.3px;
            font-family: 'Poppins', sans-serif;
        }

        .header-right img {
            height: 32px;
            object-fit: contain;
        }

        /* Content Area */
        .content-area {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Welcome Card más compacto */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(225, 0, 0, 0.15);
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
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .welcome-content h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .welcome-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            max-width: 800px;
            position: relative;
            z-index: 2;
        }

        /* Botón de volver mejorado */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            background: var(--card-bg);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            border: 1px solid var(--border-color);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            background: var(--bg-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateX(-3px);
        }

        .back-button i {
            font-size: 1.1rem;
        }

        /* Cards de resumen más compactos */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: var(--card-bg);
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .summary-card h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-card h3 i {
            font-size: 1.1rem;
        }

        .summary-card .count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.4rem;
        }

        .summary-card .description {
            color: var(--text-gray);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        /* === NUEVO DISEÑO PARA TABLAS EN PARALELO === */
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .full-width-table {
            grid-column: 1 / -1;
        }

        /* Estilos para la tabla de resultados */
        .results-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            height: fit-content;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--primary-color);
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .results-title i {
            font-size: 1.2rem;
        }

        .results-count {
            background: var(--primary-color);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            /* Scroll suave para mejor QoS */
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        /* === MEJORAS DE QoS Y SCROLL === */
        /* Contenedor de tabla con scroll limitado a 8 filas */
        .table-scroll-container {
            max-height: 400px; /* Aproximadamente 8 filas */
            overflow-y: auto;
            position: relative;
        }

        /* Personalización de scrollbar rojo */
        .table-scroll-container::-webkit-scrollbar {
            width: 10px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: rgba(225, 0, 0, 0.1);
            border-radius: 5px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 5px;
            border: 2px solid rgba(225, 0, 0, 0.1);
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Para Firefox */
        .table-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) rgba(225, 0, 0, 0.1);
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            will-change: transform;
        }

        .comparison-table th {
            background: var(--primary-color);
            color: white;
            padding: 0.85rem;
            text-align: left;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .comparison-table td {
            padding: 0.85rem;
            border-bottom: 1px solid var(--border-color);
            min-width: 120px;
        }

        .comparison-table tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.02);
        }

        .dark-mode .comparison-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
            will-change: background-color;
        }

        .comparison-table tr {
            transition: background-color 0.15s ease;
        }

        .comparison-table tr:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        .dark-mode .comparison-table tr:hover {
            background: rgba(255, 255, 255, 0.04);
            transition: background-color 0.2s ease;
        }

        .no-results {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-gray);
            font-style: italic;
            font-size: 0.9rem;
        }

        .no-results i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Optimización de transiciones */
        .summary-card, .results-container, .welcome-card {
            will-change: transform;
            transform: translateZ(0);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1023px) {
            .content-area {
                padding: 1.25rem;
            }
            
            .welcome-card {
                padding: 1.25rem;
            }
            
            .welcome-content h2 {
                font-size: 1.2rem;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 968px) {
            .tables-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .results-container {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            .table-scroll-container {
                max-height: 350px;
            }
            
            .table-scroll-container::-webkit-scrollbar {
                width: 6px;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .top-header {
                padding: 0.6rem 1rem;
            }

            .top-header h1 {
                font-size: 0.95rem;
            }

            .header-right img {
                height: 28px;
            }
            
            .welcome-card {
                padding: 1rem;
            }
            
            .welcome-content h2 {
                font-size: 1.1rem;
            }
            
            .welcome-content p {
                font-size: 0.85rem;
            }
            
            .results-container {
                padding: 1rem;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .comparison-table {
                font-size: 0.75rem;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 0.65rem 0.4rem;
            }
        }
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
                <h1>Comparación de Bases - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="comparacion_bases_index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver al Menú
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Comparación de Bases - Casos (O)</h2>
                </div>
            </div>

            <!-- Cards de Resumen -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><i class='bx bx-calendar'></i> Fechas de Apertura</h3>
                    <div class="count"><?php echo $countApertura; ?></div>
                    <div class="description">Discrepancias en fechas de inicio de interrupción</div>
                </div>
                
                <div class="summary-card">
                    <h3><i class='bx bx-calendar-check'></i> Fechas de Cierre</h3>
                    <div class="count"><?php echo $countCierre; ?></div>
                    <div class="description">Discrepancias en fechas de fin de interrupción</div>
                </div>
                
                <div class="summary-card">
                    <h3><i class='bx bx-list-check'></i> Estados</h3>
                    <div class="count"><?php echo $countEstados; ?></div>
                    <div class="description">Discrepancias en estados de registros</div>
                </div>
            </div>

            <!-- Contenedor grid para las tablas -->
            <div class="tables-grid">
                
                <!-- Primera tabla - Fechas Apertura -->
                <div class="results-container">
                    <div class="results-header">
                        <h2 class="results-title">
                            <i class='bx bx-calendar'></i>
                            Fecha Inicio Interrupción
                        </h2>
                        <div class="results-count">
                            <?php echo $countApertura; ?> Discrepancias
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>ID de incidente</th>
                                        <th>Inicio de la interrupción de servicio</th>
                                        <th>fecha_apertura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($discrepancias['error'])): ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-error'></i>
                                                Error al cargar los datos: <?php echo $discrepancias['error']; ?>
                                            </td>
                                        </tr>
                                    <?php elseif (count($discrepancias) > 0): ?>
                                        <?php foreach ($discrepancias as $fila): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['Inicio de la interrupción de servicio'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['fecha_apertura'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-check-circle'></i>
                                                No se encontraron discrepancias en las fechas de apertura
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Segunda tabla - Fechas de Cierre -->
                <div class="results-container">
                    <div class="results-header">
                        <h2 class="results-title">
                            <i class='bx bx-calendar-check'></i>
                            Fecha Fin Interrupción
                        </h2>
                        <div class="results-count">
                            <?php echo $countCierre; ?> Discrepancias
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>ID de incidente</th>
                                        <th>Fin de la interrupción de servicio</th>
                                        <th>fecha_cierre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($discrepanciasCierre['error'])): ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-error'></i>
                                                Error al cargar los datos: <?php echo $discrepanciasCierre['error']; ?>
                                            </td>
                                        </tr>
                                    <?php elseif (count($discrepanciasCierre) > 0): ?>
                                        <?php foreach ($discrepanciasCierre as $fila): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['Fin de la interrupción de servicio'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['fecha_cierre'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-check-circle'></i>
                                                No se encontraron discrepancias en las fechas de cierre
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tercera tabla - Estados (ocupa todo el ancho) -->
                <div class="results-container full-width-table">
                    <div class="results-header">
                        <h2 class="results-title">
                            <i class='bx bx-list-check'></i>
                            Comparación de Estados
                        </h2>
                        <div class="results-count">
                            <?php echo $countEstados; ?> Discrepancias
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>ID de incidente</th>
                                        <th>Estado del registro (Incidentes)</th>
                                        <th>Estado (Reportes)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($discrepanciasEstados['error'])): ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-error'></i>
                                                Error al cargar los datos: <?php echo $discrepanciasEstados['error']; ?>
                                            </td>
                                        </tr>
                                    <?php elseif (count($discrepanciasEstados) > 0): ?>
                                        <?php foreach ($discrepanciasEstados as $fila): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['ID de incidente'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['estado_incidentes'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($fila['estado_reportes'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="no-results">
                                                <i class='bx bx-check-circle'></i>
                                                No se encontraron discrepancias en los estados
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === OVERRIDE DEL COMPORTAMIENTO DE LA SIDEBAR PARA ESTA PÁGINA ===
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebarCustom() {
                sidebar.classList.toggle('active');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebarCustom);
            }

            if (closeSidebar) {
                closeSidebar.addEventListener('click', toggleSidebarCustom);
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebarCustom);
            }

            // Cerrar sidebar al hacer clic en cualquier enlace
            document.querySelectorAll('.nav-item a:not(.dropdown-toggle)').forEach(item => {
                item.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    if (overlay) {
                        overlay.classList.remove('active');
                    }
                    document.body.style.overflow = '';
                });
            });
            // === FIN OVERRIDE ===

            // Manejo del modo oscuro
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