<?php
session_start();
include("../../../includes/db.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../../login.php");
    exit;
}

// Inicializar modo oscuro
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

// Datos de sesión seguros
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombreRol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');

require_once '../../../config.php';

// Definir la página actual para la navegación
$current_page = 'paradas_reloj_7k.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradas de Reloj - MinTIC 7K - CLARO NOC</title>
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
            line-height: 1.6;
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

        /* Welcome Card - Rediseñada */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(225, 0, 0, 0.2);
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
            font-family: 'Poppins', sans-serif;
        }

        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            max-width: 800px;
            position: relative;
            z-index: 2;
            font-family: 'Poppins', sans-serif;
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
            font-family: 'Poppins', sans-serif;
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

        /* Estilos para las tablas */
        .table-container {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
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
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Poppins', sans-serif;
        }

        .count-badge {
            background: var(--primary-color);
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
            background: var(--bg-light);
            z-index: 10;
            box-shadow: 0 1px 0 var(--border-color);
        }

        .lazy-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .lazy-table th {
            background: var(--bg-light);
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
            color: var(--primary-color);
            font-weight: 500;
            background: rgba(225, 0, 0, 0.05);
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
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        body.dark-mode .badge-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffd76e;
        }

        /* Scrollbar personalizado */
        .table-scroll-vertical::-webkit-scrollbar {
            width: 8px;
        }

        .table-scroll-vertical::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 0 8px 8px 0;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Responsive */
        @media (max-width: 1023px) {
            .content-area {
                padding: 1.5rem;
            }
            
            .welcome-card {
                padding: 1.75rem;
            }
            
            .welcome-content h2 {
                font-size: 1.8rem;
            }
            
            .badges {
                flex-direction: column;
                gap: 0.75rem;
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
            
            .table-container {
                padding: 1.5rem;
            }
            
            .table-scroll-vertical {
                max-height: 400px;
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
                <h1>Paradas de Reloj - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="pdr_fechas_7k.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver a PDRs y Fechas - MinTIC 7K
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Paradas de Reloj - MinTIC 7K</h2>
                    <p>Auditoría especializada de motivos de parada y justificaciones en incidentes PDR del proyecto MinTIC 7K.</p>
                    <div class="badges">
                        <span class="badge"><i class='bx bx-time'></i> Auditoría de PDRs</span>
                        <span class="badge"><i class='bx bx-error-circle'></i> Validación de Motivos</span>
                        <span class="badge"><i class='bx bx-check-circle'></i> Control de Calidad</span>
                    </div>
                </div>
            </div>

            <!-- Tabla: Motivos de PDR no válidos o vacíos -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-error-circle'></i> Motivos de PDR no válidos o vacíos
                        <span class="count-badge" id="count-motivos_pdr">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="lazy-table-container" id="table-motivos_pdr" data-table="motivos_pdr">
                        <div class="table-placeholder">
                            <i class='bx bx-loader-circle'></i>
                            Cargando datos...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla: PDRS con Justificaciones Erradas - CON SCROLL VERTICAL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-time'></i> PDRS con Justificaciones Erradas
                        <span class="count-badge" id="count-justificaciones_erradas">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-justificaciones_erradas" data-table="justificaciones_erradas">
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
        // Configuración para las tablas de paradas de reloj
        const tableConfig = {
            'motivos_pdr': {
                columns: ['numero_ticket', 'prioridad', 'motivo_parada'],
                headers: ['Número de Ticket', 'Prioridad', 'Motivo de Parada']
            },
            'justificaciones_erradas': {
                columns: ['numero_ticket', 'motivo_parada', 'descripcion_apertura_o_parada'],
                headers: ['Número de Ticket', 'Motivo de Parada', 'Descripción Apertura/Parada']
            }
        };

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

        // Función para renderizar una tabla
        function renderTable(tableId, data) {
            const container = document.getElementById(`table-${tableId}`);
            const countBadge = document.getElementById(`count-${tableId}`);
            
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
            
            // Actualizar contador
            if (countBadge) {
                countBadge.textContent = data.count || data.rows.length;
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
                    
                    config.columns.forEach(column => {
                        let value = row[column];
                        // Resaltar campos vacíos o nulos
                        if (value === null || value === '' || value === 'NULL') {
                            html += `<td><span class="badge-warning">VACÍO</span></td>`;
                        } else {
                            // Para la columna de descripción, limitar el texto si es muy largo
                            if (column === 'descripcion_apertura_o_parada' && value.length > 150) {
                                value = value.substring(0, 150) + '...';
                            }
                            html += `<td title="${escapeHtml(value)}">${escapeHtml(value)}</td>`;
                        }
                    });
                    
                    html += `</tr>`;
                });
            }
            
            html += `</tbody>`;
            html += `</table>`;
            
            container.innerHTML = html;
        }

        // Cargar todas las tablas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainers = document.querySelectorAll('.lazy-table-container');
            
            tableContainers.forEach(container => {
                const tableId = container.getAttribute('data-table');
                
                fetch(`load_paradas_reloj.php?table=${tableId}`)
                    .then(response => response.json())
                    .then(data => {
                        renderTable(tableId, data);
                    })
                    .catch(error => {
                        console.error('Error cargando la tabla:', error);
                        container.innerHTML = `<div class="table-error">Error al cargar los datos: ${error.message}</div>`;
                    });
            });

            // === OVERRIDE DEL COMPORTAMIENTO DE LA SIDEBAR PARA ESTA PÁGINA ===
            // Sobrescribir el toggle de la sidebar para que funcione igual en todos los tamaños
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