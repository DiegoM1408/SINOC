<?php
// modules/PDRs_Fechas_ODH/PDRs_Fechas_index.php
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
    <title>PDRs y Fechas - MinTIC ODH - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* DISEÑO MODERNO - COMPACTO - COPIADO DE AUDITORIA_ODH.PHP */
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
            
            /* Variables adicionales para compatibilidad */
            --primary-blue: #E10000; /* ROJO */
            --secondary-blue: #CC0000; /* ROJO */
            --accent-cyan: #06B6D4;
            --purple: #8B5CF6;
        }

        /* Modo Oscuro - COPIADO DE AUDITORIA_ODH.PHP */
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
            font-size: 14px;
        }

        /* Main Content */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 280px);
            margin-left: 280px;
        }

        /* HEADER COMPACTO */
        .top-header {
            background: var(--card-bg);
            padding: 0.8rem 1.5rem;
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
            gap: 0.8rem;
        }

        .back-button-header {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
        }

        .back-button-header:hover {
            background: var(--secondary-color);
            transform: translateX(-2px);
        }

        .back-button-header i {
            font-size: 1rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: var(--text-dark);
            padding: 0.4rem;
        }

        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Poppins', sans-serif;
        }

        .header-right img {
            height: 30px;
            object-fit: contain;
        }

        /* Content Area */
        .content-area {
            padding: 1.5rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* HERO SECTION COMPACTA - ROJO COMO AUDITORIA_ODH.PHP */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(225, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            font-family: 'Poppins', sans-serif;
        }

        .hero-content p {
            font-size: 0.85rem;
            opacity: 0.95;
            max-width: 900px;
            line-height: 1.5;
            font-family: 'Poppins', sans-serif;
        }

        /* SECTION HEADER COMPACTO */
        .section-header {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Poppins', sans-serif;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .section-subtitle {
            color: var(--text-gray);
            font-size: 0.8rem;
            font-family: 'Poppins', sans-serif;
        }

        /* MODULES GRID - MÁS COMPACTO Y JUNTO */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* MODULE CARDS - MÁS COMPACTAS Y JUNTAS */
        .module-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            height: 280px;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--purple));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }

        .module-card:hover::before {
            opacity: 1;
        }

        /* LOS MÓDULOS MANTIENEN SUS COLORES ORIGINALES */
        .module-header {
            background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
            padding: 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .module-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .module-card:nth-child(2) .module-header {
            background: linear-gradient(135deg, var(--purple) 0%, #7C3AED 100%);
        }

        .module-card:nth-child(3) .module-header {
            background: linear-gradient(135deg, var(--accent-cyan) 0%, #0891B2 100%);
        }

        .module-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: white;
            position: relative;
            z-index: 2;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .module-card:hover .module-icon {
            animation: bounce 0.6s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .module-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: white;
            margin: 0;
            position: relative;
            z-index: 2;
            font-family: 'Poppins', sans-serif;
        }

        .module-body {
            padding: 1rem;
            flex: 1;
            overflow: hidden;
        }

        .module-features {
            margin: 0;
        }

        .module-features ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .module-features li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.4rem;
            padding: 0.2rem 0;
            transition: all 0.3s ease;
        }

        .module-features li:hover {
            transform: translateX(2px);
        }

        .module-features i {
            color: var(--success-color);
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .module-features span {
            color: var(--text-dark);
            font-size: 0.75rem;
            line-height: 1.4;
            font-family: 'Poppins', sans-serif;
        }

        .module-footer {
            padding: 0.8rem 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-light);
            flex-shrink: 0;
        }

        .module-footer span {
            font-size: 0.75rem;
            color: var(--text-gray);
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
        }

        .module-arrow {
            color: var(--primary-color);
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .module-card:hover .module-arrow {
            transform: translateX(3px);
        }

        /* BADGE CONTADOR COMPACTO */
        .count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
            margin-top: 0.3rem;
        }

        /* RESPONSIVE - COMPACTO */
        @media (max-width: 1023px) {
            .sidebar-toggle { display: flex; }
            .main-content { margin-left: 0; width: 100%; }
            
            .content-area { padding: 1rem; }
            
            .hero-section { 
                padding: 1.2rem 1rem;
            }
            
            .hero-content h1 { 
                font-size: 1.2rem;
            }
            
            .hero-content p {
                font-size: 0.8rem;
            }
            
            .modules-grid { 
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            
            .back-button-header span { 
                display: none; 
            }

            .page-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 640px) {
            .content-area { 
                padding: 0.8rem;
            }
            
            .hero-section { 
                padding: 1rem 0.8rem;
            }
            
            .hero-content h1 { 
                font-size: 1.1rem;
            }
            
            .hero-content p {
                font-size: 0.75rem;
            }
            
            .modules-grid { 
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .module-card {
                height: auto;
            }

            .module-header {
                padding: 0.8rem;
            }

            .module-icon {
                font-size: 1.8rem;
            }

            .module-card h3 {
                font-size: 0.95rem;
            }

            .module-body {
                padding: 0.8rem;
            }

            .module-footer {
                padding: 0.7rem 0.8rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content { 
                margin-left: 280px; 
                width: calc(100% - 280px); 
            }
        }

        /* ANIMACIÓN DE ENTRADA */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .module-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .module-card:nth-child(1) { animation-delay: 0.1s; }
        .module-card:nth-child(2) { animation-delay: 0.2s; }
        .module-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    
    <!-- INCLUIR LA SIDEBAR - SE VERÁ ROJA COMO EN AUDITORIA_ODH.PHP -->
    <?php include '../../../includes/sidebar.php'; ?>

    <!-- Main Content -->
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
                <h1 class="page-title">PDRs y Fechas ODH</h1>
            </div>
            <div class="header-right">
                <img src="../../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Hero Section Moderna -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1>📊 PDRs y Fechas - MinTIC ODH 5G</h1>
                </div>
            </div>

            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">
                    <i class='bx bx-shield-alt-2'></i>
                    Módulos de Auditoría Disponibles
                </h2>
            </div>

            <!-- Grid de Módulos -->
            <div class="modules-grid">
                <!-- Módulo Paradas de Reloj -->
                <a href="paradas_reloj_ODH.php" class="module-card">
                    <div class="module-header">
                        <div class="module-icon">
                            <i class='bx bx-time'></i>
                        </div>
                        <h3>Paradas de Reloj</h3>
                        <div class="count-badge">
                            <i class='bx bx-check-circle'></i>
                            2 Validaciones
                        </div>
                    </div>
                    <div class="module-body">
                        <div class="module-features">
                            <ul>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Valida la coherencia de las causas registradas</span>
                                </li>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Verifica uniformidad en las descripciones de paradas de reloj</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span>Validación de PDRs</span>
                        <i class='bx bx-right-arrow-alt module-arrow'></i>
                    </div>
                </a>

                <!-- Módulo Tiempos -->
                <a href="tiempos_ODH.php" class="module-card">
                    <div class="module-header">
                        <div class="module-icon">
                            <i class='bx bx-calendar'></i>
                        </div>
                        <h3>Tiempos y Fechas</h3>
                        <div class="count-badge">
                            <i class='bx bx-check-circle'></i>
                            2 Validaciones
                        </div>
                    </div>
                    <div class="module-body">
                        <div class="module-features">
                            <ul>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Verifica fecha de interrupción es mayor a la de apertura</span>
                                </li>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Valida que el mes de interrupción coincida con el de apertura</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span>Validación Temporal</span>
                        <i class='bx bx-right-arrow-alt module-arrow'></i>
                    </div>
                </a>

                <!-- Módulo Fuera de Horarios -->
                <a href="fuera_horarios_ODH.php" class="module-card">
                    <div class="module-header">
                        <div class="module-icon">
                            <i class='bx bx-moon'></i>
                        </div>
                        <h3>Fuera de Horarios</h3>
                        <div class="count-badge">
                            <i class='bx bx-check-circle'></i>
                            2 Validaciones
                        </div>
                    </div>
                    <div class="module-body">
                        <div class="module-features">
                            <ul>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Detecta casos que aplican para FDH pero no tienen el PDR correspondiente</span>
                                </li>
                                <li>
                                    <i class='bx bx-check-circle'></i>
                                    <span>Valida fuera horarios de 7pm a 7am en días hábiles</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span>Validación Horaria</span>
                        <i class='bx bx-right-arrow-alt module-arrow'></i>
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
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Manejo del sidebar toggle (responsivo)
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }

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
                        if (text) text.textContent = 'Modo Claro';
                    } else {
                        icon.className = 'bx bx-moon';
                        if (text) text.textContent = 'Modo Oscuro';
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

            document.querySelectorAll('.module-card').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>