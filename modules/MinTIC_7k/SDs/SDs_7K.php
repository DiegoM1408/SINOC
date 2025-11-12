<?php
// modules/MinTIC_7k/SDs/SDs_7K.php
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

// Obtener datos del backend
$tablas_data = [];
try {
    include 'back_SDs_7K.php';
    $tablas_data = obtenerDatosSDs();
} catch (Exception $e) {
    error_log("Error cargando datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDs - MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* Navegación interna con tabs */
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

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
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
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.red { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .stat-icon.orange { background: linear-gradient(135deg, #ff9800, #f57c00); }
        .stat-icon.blue { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .stat-icon.green { background: linear-gradient(135deg, #4CAF50, #388E3C); }
        .stat-icon.purple { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }

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

        /* Audit Grid - 2x2 */
        .audit-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .audit-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .audit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

        /* Tablas */
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background: var(--bg-light);
        }

        /* Tabla ancha para comparación */
        .full-width-card {
            grid-column: 1 / -1;
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

        /* Responsive */
        @media (max-width: 1023px) {
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
            
            .audit-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .back-button-header span {
                display: none;
            }

            .internal-nav {
                padding: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .audit-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .internal-nav {
                flex-direction: column;
                gap: 0;
            }

            .nav-tab {
                padding: 0.8rem 1rem;
                border-bottom: 1px solid var(--border-color);
                border-left: 3px solid transparent;
            }

            .nav-tab.active {
                border-left-color: var(--primary-color);
                border-bottom-color: var(--border-color);
            }
        }

        /* Desktop Styles */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    
    <?php include '../../../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <a href="../../../index.php" class="back-button-header">
                    <i class='bx bx-arrow-back'></i>
                    <span>Volver</span>
                </a>
                <h1>SDs - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Navegación por tabs -->
        <nav class="internal-nav">
            <div class="nav-tab active" data-tab="sd-kms">
                <i class='bx bx-check-shield'></i>
                SD con KM's
            </div>
            <div class="nav-tab" data-tab="sd-comparacion">
                <i class='bx bx-transfer-alt'></i>
                SDs Comparación
            </div>
        </nav>

        <!-- Content Area -->
        <div class="content-area">
            <!-- TAB 1: SD con KM's -->
            <div class="tab-content active" id="tab-sd-kms">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class='bx bx-x-circle'></i>
                        </div>
                        <div class="stat-info">
                            <h3>SDs Cerrados sin KM</h3>
                            <div class="stat-value"><?php echo $tablas_data['sds_sin_km']['count'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class='bx bx-error'></i>
                        </div>
                        <div class="stat-info">
                            <h3>KMs No Correspondientes</h3>
                            <div class="stat-value"><?php echo $tablas_data['kms_no_correspondientes']['count'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class='bx bx-bookmark'></i>
                        </div>
                        <div class="stat-info">
                            <h3>KM3201 sin Cancelled</h3>
                            <div class="stat-value"><?php echo $tablas_data['km3201_sin_cancelled']['count'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class='bx bx-bookmarks'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Otros KMs sin Fulfilled</h3>
                            <div class="stat-value"><?php echo $tablas_data['otros_kms_sin_fulfilled']['count'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <!-- SD con KM's Section -->
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-check-shield'></i>
                        SD con KM's
                    </h2>
                    <p class="section-subtitle">Validación de Service Desk con acciones correctivas (KM)</p>
                </div>

                <div class="audit-grid">
                    <!-- Tabla 1: SDs cerrados sin KM's -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-x-circle'></i>
                                SDs Cerrados sin KM's
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['sds_sin_km']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableSDs('sds_sin_km', $tablas_data['sds_sin_km'] ?? []); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla 2: SDs con KMs no correspondientes -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-error'></i>
                                KMs No Correspondientes 7K
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['kms_no_correspondientes']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableSDs('kms_no_correspondientes', $tablas_data['kms_no_correspondientes'] ?? []); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla 3: KM3201 sin Cancelled -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-bookmark'></i>
                                KM3201 sin "Cancelled"
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['km3201_sin_cancelled']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableSDs('km3201_sin_cancelled', $tablas_data['km3201_sin_cancelled'] ?? []); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla 4: Otros KMs sin Fulfilled -->
                    <div class="audit-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-bookmarks'></i>
                                Otros KMs sin "Fulfilled"
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['otros_kms_sin_fulfilled']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableSDs('otros_kms_sin_fulfilled', $tablas_data['otros_kms_sin_fulfilled'] ?? []); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: SDs Comparación -->
            <div class="tab-content" id="tab-sd-comparacion">
                <!-- Stats Cards para Comparación -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class='bx bx-transfer-alt'></i>
                        </div>
                        <div class="stat-info">
                            <h3>SDs No Aplica Portal</h3>
                            <div class="stat-value"><?php echo $tablas_data['sds_no_aplica_portal']['count'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <!-- SDs Comparación Section -->
                <div class="section-header">
                    <h2 class="section-title">
                        <i class='bx bx-transfer-alt'></i>
                        SDs Comparación
                    </h2>
                    <p class="section-subtitle">Comparación entre incidentes SD y reportes del portal</p>
                </div>

                <div class="audit-grid">
                    <!-- Tabla: SD's - No aplica visual portal de reportes -->
                    <div class="audit-card full-width-card">
                        <div class="card-header">
                            <span class="card-title">
                                <i class='bx bx-transfer-alt'></i>
                                SD's - No aplica visual portal de reportes
                            </span>
                            <span class="card-badge"><?php echo $tablas_data['sds_no_aplica_portal']['count'] ?? 0; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <?php echo renderTableSDs('sds_no_aplica_portal', $tablas_data['sds_no_aplica_portal'] ?? []); ?>
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

        document.addEventListener('DOMContentLoaded', function() {
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

<?php
function renderTableSDs($tableId, $data) {
    $config = [
        'sds_sin_km' => [
            'columns' => ['numero_ticket', 'estado', 'acciones_correctivas'],
            'headers' => ['Ticket', 'Estado', 'Acciones Correctivas']
        ],
        'kms_no_correspondientes' => [
            'columns' => ['numero_ticket', 'estado', 'acciones_correctivas'],
            'headers' => ['Ticket', 'Estado', 'KM']
        ],
        'km3201_sin_cancelled' => [
            'columns' => ['numero_ticket', 'estado', 'acciones_correctivas', 'tipificacion_falla'],
            'headers' => ['Ticket', 'Estado', 'KM', 'Tipificación']
        ],
        'otros_kms_sin_fulfilled' => [
            'columns' => ['numero_ticket', 'estado', 'acciones_correctivas', 'tipificacion_falla'],
            'headers' => ['Ticket', 'Estado', 'KM', 'Tipificación']
        ],
        'sds_no_aplica_portal' => [
            'columns' => ['id_incidente', 'prioridad', 'estado', 'ticket_en_reportes'],
            'headers' => ['ID Interacción', 'Prioridad', 'Estado', 'Ticket en Reportes']
        ]
    ];

    // El resto del código de la función permanece igual...
    if (empty($data) || !isset($data['success']) || !$data['success']) {
        $error = $data['error'] ?? 'Error desconocido';
        return "<div class='empty-state'><i class='bx bx-error-circle'></i><p>Error: " . htmlspecialchars($error) . "</p></div>";
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
                $value = '<em style="color: var(--text-gray);">Vacío</em>';
            } else {
                $value = htmlspecialchars($value);
            }
            $html .= "<td>" . $value . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    
    return $html;
}
?>