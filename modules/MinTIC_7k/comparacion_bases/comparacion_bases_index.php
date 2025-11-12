<?php
// modules/MinTIC_7k/comparacion_bases/comparacion_bases_index.php
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
    <title>Comparación de Bases - MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* DISEÑO COMPACTO Y MODERNO */
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

        /* Main Content */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 280px);
            margin-left: 280px;
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
            display: none;
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

        /* Breadcrumb y botón de volver mejorado */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

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

        .badges {
            display: flex;
            gap: 0.75rem;
            position: relative;
            z-index: 2;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            font-size: 0.8rem;
        }

        .badge i {
            font-size: 1rem;
        }

        /* Tarjetas de selección más compactas */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-top: 1rem;
        }

        .selection-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            position: relative;
        }

        .selection-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .casos-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .paradas-icon {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
        }

        .card-description {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .card-features {
            list-style: none;
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }

        .card-features li {
            padding: 0.4rem 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
        }

        .card-features i {
            color: var(--success-color);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .card-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.6rem 1.25rem;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            width: 100%;
        }

        .card-cta:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .card-cta i {
            transition: transform 0.3s ease;
        }

        .selection-card:hover .card-cta i {
            transform: translateX(3px);
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
                padding: 1.25rem;
            }
            
            .welcome-card {
                padding: 1.25rem;
            }
            
            .welcome-content h2 {
                font-size: 1.2rem;
            }

            .cards-container {
                grid-template-columns: 1fr;
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

            .badges {
                gap: 0.5rem;
            }

            .badge {
                font-size: 0.75rem;
                padding: 0.35rem 0.7rem;
            }
            
            .selection-card {
                padding: 1.25rem;
            }
            
            .card-title {
                font-size: 1.1rem;
            }

            .card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
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
                <h1>Comparación de Bases - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="../../../index.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver al Inicio
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Comparación de Bases de Datos</h2>
                    <p>Selecciona el tipo de comparación que deseas realizar entre las bases de datos.</p>
                    <div class="badges">
                        <div class="badge">
                            <i class='bx bx-shield-quarter'></i>
                            <span>Validación de Integridad</span>
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

            <!-- Tarjetas de selección -->
            <div class="cards-container">
                <!-- Tarjeta Casos (O) -->
                <a href="comparar_bases.php" class="selection-card">
                    <div class="card-header">
                        <div class="card-icon casos-icon">
                            <i class='bx bx-folder-open'></i>
                        </div>
                        <h3 class="card-title">Casos (O)</h3>
                    </div>
                    <ul class="card-features">
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Comparación de fechas de apertura</span>
                        </li>
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Comparación de fechas de cierre</span>
                        </li>
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Validación de estados</span>
                        </li>
                    </ul>
                    <div class="card-cta">
                        <span>Abrir herramienta</span>
                        <i class='bx bx-chevron-right'></i>
                    </div>
                </a>

                <!-- Tarjeta Paradas (P) -->
                <a href="compararbasesP.php" class="selection-card">
                    <div class="card-header">
                        <div class="card-icon paradas-icon">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <h3 class="card-title">Paradas (P)</h3>
                    </div>
                    <ul class="card-features">
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Comparación de fechas de apertura</span>
                        </li>
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Comparación de fechas de cierre</span>
                        </li>
                        <li>
                            <i class='bx bx-check'></i>
                            <span>Validación de motivos de parada</span>
                        </li>
                    </ul>
                    <div class="card-cta">
                        <span>Abrir herramienta</span>
                        <i class='bx bx-chevron-right'></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Efectos hover mejorados para las cards
            document.querySelectorAll('.selection-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.12)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.08)';
                });
            });

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