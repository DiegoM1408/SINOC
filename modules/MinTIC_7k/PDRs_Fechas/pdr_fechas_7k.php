<?php
// modules/PDRs_Fechas/pdr_fechas_7k.php
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDRs y Fechas - MinTIC 7K - CLARO NOC</title>
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

        /* Welcome Card Compacta */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(225, 0, 0, 0.2);
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
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
            font-family: 'Poppins', sans-serif;
        }

        .welcome-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
            max-width: 800px;
            position: relative;
            z-index: 2;
            font-family: 'Poppins', sans-serif;
        }

        /* Modules Grid - Nuevo estilo para las cards de módulos */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        @media (min-width: 1200px) {
            .modules-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .module-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        .module-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-align: center;
        }

        .module-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }

        .module-features {
            margin: 1rem 0;
        }

        .module-features ul {
            list-style: none;
            padding: 0;
        }

        .module-features li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.3rem 0;
        }

        .module-features i {
            color: var(--success-color);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .module-features span {
            color: var(--text-dark);
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
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
            font-size: 0.9rem;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* ===== ESTILOS RESPONSIVE ===== */
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
            
            .welcome-card {
                padding: 1.25rem;
            }
            
            .welcome-content h2 {
                font-size: 1.3rem;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .module-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .welcome-card {
                padding: 1rem;
            }
            
            .welcome-content h2 {
                font-size: 1.2rem;
            }
            
            .welcome-content p {
                font-size: 0.85rem;
            }
            
            .module-card {
                padding: 1.25rem;
            }
            
            .module-icon {
                font-size: 2rem;
            }
            
            .module-card h3 {
                font-size: 1.1rem;
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
    
    <!-- INCLUIR LA NUEVA SIDEBAR MEJORADA -->
    <?php include '../../../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>PDRs y Fechas - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="../../../index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver al Inicio
            </a>

            <!-- Welcome Card Compacta -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>PDRs y Fechas - MinTIC 7K</h2>
                    <p>Selecciona el tipo de auditoría que deseas realizar para el proyecto MinTIC 7K. Cada módulo contiene revisiones especializadas para garantizar la calidad de los datos.</p>
                </div>
            </div>

            <!-- Grid de Módulos -->
            <div class="modules-grid">
                <!-- Módulo Paradas de Reloj -->
                <a href="paradas_reloj_7k.php" class="module-card">
                    <div class="module-icon">
                        <i class='bx bx-time'></i>
                    </div>
                    <h3>Paradas de Reloj</h3>
                    <div class="module-features">
                        <ul>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Motivos de PDR no válidos o vacíos</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>PDRS con justificaciones erradas</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Validación de causas de parada</span>
                            </li>
                         
                        </ul>
                    </div>
                </a>

                <!-- Módulo Tiempos -->
                <a href="tiempos_7k.php" class="module-card">
                    <div class="module-icon">
                        <i class='bx bx-calendar'></i>
                    </div>
                    <h3>Tiempos</h3>
                    <div class="module-features">
                        <ul>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Fecha de interrupción mayor a apertura</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Mes de interrupción diferente al de apertura</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Fechas en blanco (banderas P y O)</span>
                            </li>
                        </ul>
                    </div>
                </a>

                <!-- Módulo Fuera de Horarios -->
                <a href="FDH/fuera_horarios_7k.php" class="module-card">
                    <div class="module-icon">
                        <i class='bx bx-moon'></i>
                    </div>
                    <h3>Fuera de Horarios</h3>
                    <div class="module-features">
                        <ul>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Casos que aplican para FDH por fecha pero no tienen PDR de FDH</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Validación de horarios L-V 9pm a 7am</span>
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <span>Manejo de festivos colombianos</span>
                           
                        </ul>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Efectos hover mejorados para las cards
            document.querySelectorAll('.module-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.08)';
                });
            });

            // Manejo del modo oscuro (ya incluido en sidebar.php)
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