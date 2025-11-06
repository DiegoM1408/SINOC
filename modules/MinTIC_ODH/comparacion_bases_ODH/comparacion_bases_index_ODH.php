<?php
// modules/comparacion_bases_ODH/comparacion_bases_index_ODH.php
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
    <title>Comparación de Bases - MinTIC ODH - CLARO NOC</title>
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
            font-size: 0.875rem;
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
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--text-dark);
            padding: 0.5rem;
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
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.5px;
            font-family: 'Poppins', sans-serif;
        }

        .header-right {
            height: 36px;
            object-fit: contain;
        }

        /* Content Area */
        .content-area {
            padding: 1.5rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(225, 0, 0, 0.2);
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
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .welcome-content p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 0;
            max-width: 800px;
            position: relative;
            z-index: 2;
        }

        /* Botón de volver */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.625rem 1.25rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        /* Tarjetas de selección */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .selection-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: default;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            height: fit-content;
            min-height: 280px;
            /* Deshabilitar completamente la interactividad */
            pointer-events: none;
            user-select: none;
        }

        .selection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        /* Header de la tarjeta con ícono y título en línea */
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .casos-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .paradas-icon {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            margin: 0;
        }

        .card-description {
            font-size: 0.8rem;
            line-height: 1.5;
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .card-features {
            list-style: none;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .card-features li {
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
        }

        .card-features i {
            color: var(--success-color);
            font-size: 0.9rem;
            flex-shrink: 0;
            min-width: 16px;
        }

        .card-features span {
            color: var(--text-dark);
            font-weight: 500;
        }

        .card-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            background: var(--info-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            width: 100%;
            justify-content: center;
            margin-top: auto;
            cursor: default;
        }

        /* Estilos para módulos en desarrollo */
        .selection-card.disabled {
            opacity: 0.8;
            position: relative;
        }

        .selection-card.disabled::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 12px;
        }

        .development-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--info-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 2;
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
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .welcome-card {
                padding: 1rem;
            }
            
            .welcome-content h2 {
                font-size: 1.1rem;
            }
            
            .welcome-content p {
                font-size: 0.8rem;
            }
            
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .selection-card {
                padding: 1.25rem;
                min-height: 260px;
            }
            
            .card-title {
                font-size: 1.1rem;
            }

            .card-header {
                gap: 0.75rem;
            }

            .card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }

            .development-badge {
                font-size: 0.65rem;
                padding: 0.2rem 0.5rem;
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
                <h1>Comparación de Bases - MinTIC ODH</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 36px;">
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
                    <h2>Comparación de Bases de Datos - ODH</h2>
                    <p>Selecciona el tipo de comparación que deseas realizar para el proyecto MinTIC ODH 5G.</p>
                </div>
            </div>

            <!-- Tarjetas de selección -->
            <div class="cards-container">
                <!-- Tarjeta Casos (O) - EN DESARROLLO -->
                <div class="selection-card disabled">
                    <div class="development-badge">EN DESARROLLO</div>
                    <div class="card-header">
                        <div class="card-icon casos-icon">
                            <i class='bx bx-folder-open'></i>
                        </div>
                        <h3 class="card-title">Casos (O)</h3>
                    </div>
                    <p class="card-description">
                        Herramienta para comparar y validar la base de datos de casos ODH.
                    </p>
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
                        <span>Próximamente disponible</span>
                    </div>
                </div>

                <!-- Tarjeta Paradas (P) - EN DESARROLLO -->
                <div class="selection-card disabled">
                    <div class="development-badge">EN DESARROLLO</div>
                    <div class="card-header">
                        <div class="card-icon paradas-icon">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <h3 class="card-title">Paradas (P)</h3>
                    </div>
                    <p class="card-description">
                        Herramienta para comparar y validar la base de datos de paradas ODH.
                    </p>
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
                        <span>Próximamente disponible</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Los módulos están completamente deshabilitados con pointer-events: none

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