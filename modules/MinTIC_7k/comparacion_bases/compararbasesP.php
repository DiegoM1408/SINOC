<?php
// modules/comparacion_bases/compararbasesP.php
session_start();

define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
require_once(ROOT_PATH . '../../includes/db.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../../login.php');
    exit;
}

if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombreRol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');
$current_page = basename($_SERVER['PHP_SELF']);

require_once '../../../config.php';
include("compararbasesP_backend.php");

// Obtener datos de las 4 tablas
$discrepanciasCasos = compararCasos($conn);
$discrepanciasFechasInicio = compararFechasInicio($conn);
$discrepanciasFechasCierre = compararFechasCierre($conn);
$discrepanciasMotivos = compararMotivos($conn);

// Contadores
$countCasos = isset($discrepanciasCasos['error']) ? 0 : count($discrepanciasCasos);
$countInicio = isset($discrepanciasFechasInicio['error']) ? 0 : count($discrepanciasFechasInicio);
$countCierre = isset($discrepanciasFechasCierre['error']) ? 0 : count($discrepanciasFechasCierre);
$countMotivos = isset($discrepanciasMotivos['error']) ? 0 : count($discrepanciasMotivos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Bases P - MinTIC 7K - CLARO NOC</title>
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

        body.dark-mode {
            --bg-light: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-dark: #ffffff;
            --text-gray: #b3b3b3;
            --border-color: #404040;
            --sidebar-bg: #1e2a38;
            --sidebar-hover: #2c3e50;
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
        }

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
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 0.3px;
        }

        .header-right img {
            height: 32px;
            object-fit: contain;
        }

        .content-area {
            padding: 1.5rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Botón Volver */
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
            border: 1px solid var(--border-color);
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

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(225, 0, 0, 0.15);
        }

        .welcome-card h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-card p {
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.2rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.2;
            float: right;
        }

        /* Layout Grid para Tablas 2 y 3 */
        .tables-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1200px) {
            .tables-row {
                grid-template-columns: 1fr;
            }
        }

        .results-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .results-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .results-title i {
            font-size: 1.3rem;
        }

        .results-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table-container {
            padding: 1.5rem;
        }

        .table-scroll-container {
            overflow-x: auto;
            max-height: 480px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            position: relative;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .comparison-table thead {
            background: var(--bg-light);
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .comparison-table th {
            padding: 0.9rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg-light);
        }

        .comparison-table td {
            padding: 0.9rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .comparison-table tbody tr {
            transition: all 0.2s ease;
        }

        .comparison-table tbody tr:hover {
            background: var(--bg-light);
        }

        .table-scroll-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: var(--text-gray);
        }

        .no-results {
            text-align: center;
            padding: 2rem !important;
            color: var(--text-gray);
            font-size: 0.95rem;
        }

        .no-results i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--success-color);
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Sidebar Styles */
        #sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: var(--sidebar-bg);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        #sidebar.active {
            left: 0;
        }

        #sidebarOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        #sidebarOverlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .comparison-table {
                font-size: 0.8rem;
            }

            .comparison-table th,
            .comparison-table td {
                padding: 0.6rem;
            }

            #sidebar {
                width: 250px;
                left: -250px;
            }
        }
    </style>
</head>
<body id="body" <?php echo $_SESSION['dark_mode'] ? 'class="dark-mode"' : ''; ?>>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php 
    // Incluir la sidebar centralizada
    require_once '../../../includes/sidebar.php';
    ?>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Comparación de Bases - Casos (P)</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <div class="content-area">
            <!-- Botón de volver -->
            <a href="comparacion_bases_index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver al Menú
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>Comparación de Bases - Casos (P)</h2>
                <p>Comparación entre tablas reportes e incidentes_etl para casos con bandera P</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class='bx bx-filter-alt'></i>
                    <h3>Casos Sin Coincidencia</h3>
                    <div class="number"><?php echo $countCasos; ?></div>
                </div>
                <div class="stat-card">
                    <i class='bx bx-calendar'></i>
                    <h3>Discrepancias Fecha Inicio</h3>
                    <div class="number"><?php echo $countInicio; ?></div>
                </div>
                <div class="stat-card">
                    <i class='bx bx-calendar-check'></i>
                    <h3>Discrepancias Fecha Cierre</h3>
                    <div class="number"><?php echo $countCierre; ?></div>
                </div>
                <div class="stat-card">
                    <i class='bx bx-list-check'></i>
                    <h3>Discrepancias Motivos</h3>
                    <div class="number"><?php echo $countMotivos; ?></div>
                </div>
            </div>

            <!-- TABLA 1: Depuración de Casos (SOLA) -->
            <div class="results-container">
                <div class="results-header">
                    <h2 class="results-title">
                        <i class='bx bx-filter-alt'></i>
                        Tabla 1: Depuración de Casos
                    </h2>
                    <div class="results-count">
                        <?php echo $countCasos; ?> Casos
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll-container">
                        <table class="comparison-table">
                            <thead>
                                <tr>
                                    <th>Número de Ticket</th>
                                    <th>En incidentes_etl</th>
                                    <th>En reportes</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($discrepanciasCasos['error'])): ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            <i class='bx bx-error'></i>
                                            Error: <?php echo htmlspecialchars($discrepanciasCasos['error']); ?>
                                        </td>
                                    </tr>
                                <?php elseif (count($discrepanciasCasos) > 0): ?>
                                    <?php foreach ($discrepanciasCasos as $fila): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fila['numero_ticket']); ?></strong></td>
                                            <td>
                                                <?php if ($fila['en_incidentes_etl'] == 'Sí'): ?>
                                                    <span class="badge badge-success">Sí</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fila['en_reportes'] == 'Sí'): ?>
                                                    <span class="badge badge-success">Sí</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($fila['observacion']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-results">
                                            <i class='bx bx-check-circle'></i>
                                            ¡Excelente! Todos los casos coinciden
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLAS 2 y 3 JUNTAS -->
            <div class="tables-row">
                <!-- TABLA 2: Fechas de Inicio -->
                <div class="results-container">
                    <div class="results-header">
                        <h2 class="results-title">
                            <i class='bx bx-calendar'></i>
                            Tabla 2: Fechas Inicio
                        </h2>
                        <div class="results-count">
                            <?php echo $countInicio; ?> 
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Fecha Apertura</th>
                                        <th>Fecha Inicio PR</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($discrepanciasFechasInicio['error'])): ?>
                                        <tr>
                                            <td colspan="4" class="no-results">
                                                <i class='bx bx-error'></i>
                                                Error: <?php echo htmlspecialchars($discrepanciasFechasInicio['error']); ?>
                                            </td>
                                        </tr>
                                    <?php elseif (count($discrepanciasFechasInicio) > 0): ?>
                                        <?php foreach ($discrepanciasFechasInicio as $fila): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($fila['numero_ticket']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($fila['fecha_apertura']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['Fecha inicio parada de reloj']); ?></td>
                                                <td>
                                                    <?php if (strpos($fila['observacion'], 'Falla masiva') !== false): ?>
                                                        <span class="badge badge-warning"><?php echo htmlspecialchars($fila['observacion']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info"><?php echo htmlspecialchars($fila['observacion']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="no-results">
                                                <i class='bx bx-check-circle'></i>
                                                Sin discrepancias
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TABLA 3: Fechas de Cierre -->
                <div class="results-container">
                    <div class="results-header">
                        <h2 class="results-title">
                            <i class='bx bx-calendar-check'></i>
                            Tabla 3: Fechas Cierre
                        </h2>
                        <div class="results-count">
                            <?php echo $countCierre; ?>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Fecha Cierre</th>
                                        <th>Fecha Fin PR</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($discrepanciasFechasCierre['error'])): ?>
                                        <tr>
                                            <td colspan="4" class="no-results">
                                                <i class='bx bx-error'></i>
                                                Error: <?php echo htmlspecialchars($discrepanciasFechasCierre['error']); ?>
                                            </td>
                                        </tr>
                                    <?php elseif (count($discrepanciasFechasCierre) > 0): ?>
                                        <?php foreach ($discrepanciasFechasCierre as $fila): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($fila['numero_ticket']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($fila['fecha_cierre']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['Fecha fin parada de reloj']); ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo htmlspecialchars($fila['observacion']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="no-results">
                                                <i class='bx bx-check-circle'></i>
                                                Sin discrepancias
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA 4: Motivos (ABAJO, SOLA) -->
            <div class="results-container">
                <div class="results-header">
                    <h2 class="results-title">
                        <i class='bx bx-list-check'></i>
                        Tabla 4: Comparación de Motivos
                    </h2>
                    <div class="results-count">
                        <?php echo $countMotivos; ?> 
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll-container">
                        <table class="comparison-table">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Motivo Reportes</th>
                                    <th>Motivo Incidentes ETL</th>
                                    <th>Motivo Esperado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($discrepanciasMotivos['error'])): ?>
                                    <tr>
                                        <td colspan="5" class="no-results">
                                            <i class='bx bx-error'></i>
                                            Error: <?php echo htmlspecialchars($discrepanciasMotivos['error']); ?>
                                        </td>
                                    </tr>
                                <?php elseif (count($discrepanciasMotivos) > 0): ?>
                                    <?php foreach ($discrepanciasMotivos as $fila): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fila['numero_ticket']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($fila['motivo_parada_reportes']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['Motivo_parada_reloj_incidentes']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['motivo_esperado']); ?></td>
                                            <td>
                                                <span class="badge badge-warning"><?php echo htmlspecialchars($fila['observacion']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="no-results">
                                            <i class='bx bx-check-circle'></i>
                                            Sin discrepancias
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebarCustom() {
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
                if (overlay) {
                    overlay.classList.toggle('active');
                }
                document.body.style.overflow = sidebar && sidebar.classList.contains('active') ? 'hidden' : '';
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

            document.querySelectorAll('.nav-item a:not(.dropdown-toggle)').forEach(item => {
                item.addEventListener('click', function() {
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                    if (overlay) {
                        overlay.classList.remove('active');
                    }
                    document.body.style.overflow = '';
                });
            });

            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.getElementById('body');
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    body.classList.toggle('dark-mode');
                    const isDarkMode = body.classList.contains('dark-mode');
                    
                    const icon = darkModeToggle.querySelector('i');
                    const text = darkModeToggle.querySelector('span');
                    
                    if (isDarkMode) {
                        icon.className = 'bx bx-sun';
                        text.textContent = 'Modo Claro';
                    } else {
                        icon.className = 'bx bx-moon';
                        text.textContent = 'Modo Oscuro';
                    }

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
