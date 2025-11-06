<?php
session_start();
include("../../../../includes/db.php");

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
    exit;
}

if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombreRol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');

require_once '../../../../config.php';
require_once '../../../../includes/sidebar_config.php';

$current_page = 'fuera_horarios_7k.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuera de Horarios - MinTIC 7K - CLARO NOC</title>
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
            --success-color: #3dcc70;
            --warning-color: #ffd76e;
            --info-color: #5bc0de;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: var(--text-light);
            position: fixed;
            left: -280px;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(225, 0, 0, 0.5);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(225, 0, 0, 0.7);
        }

        .sidebar-header {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            position: relative;
        }

        .close-sidebar {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(225, 0, 0, 0.1);
            border: 1px solid rgba(225, 0, 0, 0.3);
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-sidebar:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-50%) rotate(90deg);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
        }

        .logo-primary {
            color: var(--primary-color);
            text-shadow: 0 2px 8px rgba(225, 0, 0, 0.3);
        }

        .sidebar-nav {
            padding: 1.25rem 0;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .nav-item {
            margin: 0.35rem 0.75rem;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.1rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-item a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item a:hover {
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.15) 0%, rgba(225, 0, 0, 0.08) 100%);
            color: #fff;
            transform: translateX(4px);
        }

        .nav-item a:hover::before {
            transform: scaleY(1);
        }

        .nav-item.active a {
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        .nav-item.active a::before {
            display: none;
        }

        .nav-item .nav-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 0.75rem;
        }

        .nav-item .nav-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .nav-item .nav-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
            opacity: 0.9;
        }

        .nav-item a:hover .nav-icon {
            opacity: 1;
        }

        .nav-item .nav-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-item .dropdown-arrow {
            font-size: 1rem;
            transition: transform 0.3s ease;
            opacity: 0.7;
        }

        .nav-item.dropdown.active .dropdown-arrow {
            transform: rotate(180deg);
            opacity: 1;
        }

        .nav-item.dropdown {
            position: relative;
        }

        .dropdown-menu {
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(0, 0, 0, 0.15);
            margin: 0.35rem 0.5rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .nav-item.dropdown.active .dropdown-menu {
            max-height: 600px;
        }

        .dropdown-menu li {
            margin: 0;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 0.65rem 1rem 0.65rem 1.5rem;
            font-size: 0.8rem;
            border-radius: 6px;
            background: transparent;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.75);
            min-height: auto;
            margin: 0.25rem 0.5rem;
            font-weight: 400;
        }

        .dropdown-menu a:hover {
            background: rgba(225, 0, 0, 0.2);
            color: #fff;
            transform: translateX(4px);
        }

        .dropdown-menu a.active {
            background: rgba(225, 0, 0, 0.3);
            color: #fff;
            border-left: 3px solid var(--primary-color);
        }

        .dropdown-menu a .nav-icon {
            font-size: 1.05rem;
            margin-right: 0.75rem;
        }

        .menu-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 0.5rem 1rem;
        }

        .sidebar-footer {
            padding: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.15);
            margin-top: auto;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.7rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
            flex-shrink: 0;
        }

        .user-avatar i {
            font-size: 1.2rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.6);
            font-family: 'Poppins', sans-serif;
        }

        .sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            padding: 0.7rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .sidebar-btn i {
            font-size: 1.05rem;
            margin-right: 0.7rem;
        }

        .ntt-logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 0 0.5rem 0;
            margin-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .ntt-logo-container img {
            max-width: 140px;
            height: auto;
            opacity: 0.85;
            transition: opacity 0.3s ease;
        }

        .ntt-logo-container img:hover {
            opacity: 1;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(3px);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        body.dark-mode .sidebar {
            background: linear-gradient(180deg, #1e2a38 0%, #0f1419 100%);
        }

        body.dark-mode .dropdown-menu {
            background: rgba(0, 0, 0, 0.3);
        }

        /* MAIN CONTENT */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: 100%;
            margin-left: 0;
        }

        @media (min-width: 1024px) {
            .sidebar.active ~ .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
            
            .close-sidebar {
                display: block;
            }
        }

        @media (max-width: 1023px) {
            .sidebar-overlay.active {
                display: block;
                opacity: 1;
            }
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
        }

        .top-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-right {
            height: 40px;
        }

        .content-area {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(225, 0, 0, 0.2);
        }

        .welcome-content h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            max-width: 800px;
        }

        .badges {
            display: flex;
            gap: 1rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.15);
            font-size: 0.95rem;
        }

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
        }

        .back-button:hover {
            background: var(--secondary-color);
        }

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

        .table-scroll-vertical {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        /* Estilos personalizados para el scroll */
        .table-scroll-vertical::-webkit-scrollbar {
            width: 12px;
        }

        .table-scroll-vertical::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 6px;
            border: 2px solid #f1f1f1;
        }

        .table-scroll-vertical::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        body.dark-mode .table-scroll-vertical::-webkit-scrollbar-track {
            background: #2d2d2d;
        }

        body.dark-mode .table-scroll-vertical::-webkit-scrollbar-thumb {
            border: 2px solid #2d2d2d;
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
        }

        .lazy-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
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
        }

        .no-data {
            text-align: center;
            color: var(--text-gray);
            font-style: italic;
            padding: 2rem;
        }

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
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .table-container {
                padding: 1.5rem;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .badges {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar - Oculto por defecto -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class='bx bx-signal-5'></i>
                </div>
                <div class="logo-text">
                    <span class="logo-primary">CLARO</span>
                    <span>SINOC</span>
                </div>
            </div>
            <button class="close-sidebar" id="closeSidebar">
                <i class='bx bx-x'></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <?php foreach ($sidebar_menu as $menu_item): ?>
                    <?php if ($menu_item['type'] === 'single'): ?>
                        <li class="nav-item <?php echo isMenuActive($menu_item, $current_page) ? 'active' : ''; ?>">
                            <a href="<?php echo htmlspecialchars($menu_item['url']); ?>" class="nav-row">
                                <span class="nav-left">
                                    <i class='bx <?php echo $menu_item['icon']; ?> nav-icon'></i>
                                    <span class="nav-label"><?php echo htmlspecialchars($menu_item['label']); ?></span>
                                </span>
                            </a>
                        </li>

                    <?php elseif ($menu_item['type'] === 'dropdown'): ?>
                        <li class="nav-item dropdown <?php echo isDropdownActive($menu_item['items'], $current_page) ? 'active' : ''; ?>">
                            <a href="#" class="dropdown-toggle nav-row">
                                <span class="nav-left">
                                    <i class='bx <?php echo $menu_item['icon']; ?> nav-icon'></i>
                                    <span class="nav-label"><?php echo htmlspecialchars($menu_item['label']); ?></span>
                                </span>
                                <i class='bx bx-chevron-down dropdown-arrow'></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($menu_item['items'] as $sub_item): ?>
                                    <?php if (isset($sub_item['type']) && $sub_item['type'] === 'divider'): ?>
                                        <div class="menu-divider"></div>
                                    <?php else: ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars($sub_item['url']); ?>" 
                                               class="<?php echo (isset($sub_item['page']) && $sub_item['page'] === $current_page) ? 'active' : ''; ?>">
                                                <span class="nav-left">
                                                    <i class='bx <?php echo $sub_item['icon']; ?> nav-icon'></i>
                                                    <span class="nav-label"><?php echo htmlspecialchars($sub_item['label']); ?></span>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class='bx bx-user'></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $nombre; ?></span>
                    <span class="user-role"><?php echo $nombreRol; ?></span>
                </div>
            </div>
            
            <div class="sidebar-actions">
                <button class="sidebar-btn" id="darkModeToggle">
                    <i class='bx <?php echo $_SESSION['dark_mode'] ? 'bx-sun' : 'bx-moon'; ?>'></i>
                    <span><?php echo $_SESSION['dark_mode'] ? 'Modo Claro' : 'Modo Oscuro'; ?></span>
                </button>
                
                <a href="<?php echo BASE_URL; ?>/logout.php" class="sidebar-btn">
                    <i class='bx bx-log-out'></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
            
            <!-- Logo NTT Data -->
            <div class="ntt-logo-container">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo_nttdata7.png" alt="NTT Data">
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Fuera de Horarios - MinTIC 7K</h1>
            </div>
            <div class="header-right">
                <img src="../../../../assets/images/claro-logo6.png" alt="CLARO" style="height: 40px;">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Botón de volver -->
            <a href="../pdr_fechas_7k.php" class="back-button">
                <i class='bx bx-arrow-back'></i>
                Volver a PDRs y Fechas - MinTIC 7K
            </a>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>Fuera de Horarios - MinTIC 7K</h2>
                    <p>Auditoría completa de casos que deberían aplicar para PDR de Fuera de Horario según las fechas y horas de incidente.</p>
                    <div class="badges">
                        <span class="badge"><i class='bx bx-time'></i> Auditoría de Fuera de Horarios</span>
                        <span class="badge"><i class='bx bx-calendar-check'></i> Validación de Horarios FDH</span>
                        <span class="badge"><i class='bx bx-error-alt'></i> Detección de Inconsistencias</span>
                    </div>
                </div>
            </div>

            <!-- Tabla 1: Aplica para PDR "Fuera de Horario" -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-time'></i> Aplica para FDH o Corrección de Horas FDH
                        <span class="count-badge" id="count-aplica">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div id="table-aplica">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla 2: No Aplica para FDH -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-calendar-edit'></i> No Aplica para FDH
                        <span class="count-badge" id="count-no_aplica">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div id="table-no_aplica">
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

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }
            return date.toLocaleString('es-ES');
        }

        function renderTable(tableId, data) {
            const container = document.getElementById(`table-${tableId}`);
            const countBadge = document.getElementById(`count-${tableId}`);
            
            if (!container) return;
            
            if (!data.success) {
                container.innerHTML = `<div class="table-error">Error: ${escapeHtml(data.error)}</div>`;
                return;
            }
            
            if (countBadge) {
                countBadge.textContent = data.count || data.rows.length;
            }
            
            // Columnas diferentes para cada tabla
            let columns, headers;
            
            if (tableId === 'no_aplica') {
                // Para "No Aplica para FDH": mostrar solo número_ticket, fecha_apertura, fecha_cierre, motivo_parada
                columns = ['numero_ticket', 'fecha_apertura', 'fecha_cierre', 'motivo_parada'];
                headers = ['Número Ticket', 'Fecha Apertura', 'Fecha Cierre', 'Motivo Parada'];
            } else {
                // Para "Aplica para FDH": mostrar solo número_ticket, fecha_apertura, fecha_cierre, motivo_parada
                columns = ['numero_ticket', 'fecha_apertura', 'fecha_cierre', 'motivo_parada'];
                headers = ['Número Ticket', 'Fecha Apertura', 'Fecha Cierre', 'Motivo Parada'];
            }
            
            let html = `<table class="lazy-table">`;
            html += `<thead><tr>`;
            headers.forEach(header => {
                html += `<th>${escapeHtml(header)}</th>`;
            });
            html += `</tr></thead>`;
            html += `<tbody>`;
            
            if (data.rows.length === 0) {
                html += `<tr><td colspan="${headers.length}" class="no-data">No se encontraron registros</td></tr>`;
            } else {
                data.rows.forEach(row => {
                    html += `<tr>`;
                    columns.forEach(column => {
                        let value = row[column];
                        if (column.includes('fecha')) {
                            value = formatDate(value);
                        } else {
                            value = escapeHtml(value);
                        }
                        html += `<td>${value}</td>`;
                    });
                    html += `</tr>`;
                });
            }
            
            html += `</tbody>`;
            html += `</table>`;
            
            container.innerHTML = html;
        }

        async function loadTableNoAplica() {
            const container = document.getElementById('table-no_aplica');
            const countBadge = document.getElementById('count-no_aplica');
            
            try {
                container.innerHTML = `<div class="table-placeholder"><i class='bx bx-loader-circle'></i>Cargando datos...</div>`;
                countBadge.textContent = '0';
                
                const response = await fetch('FDH_backend.php?table=no_aplica_fdh');
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    renderTable('no_aplica', data);
                } else {
                    throw new Error(data.message || data.error || 'Error del servidor');
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `<div class="table-error">
                    <i class='bx bx-error-circle'></i> 
                    <div><strong>Error:</strong> ${escapeHtml(error.message)}</div>
                </div>`;
            }
        }

        async function loadTableAplica() {
            const container = document.getElementById('table-aplica');
            const countBadge = document.getElementById('count-aplica');
            
            try {
                container.innerHTML = `<div class="table-placeholder"><i class='bx bx-loader-circle'></i>Cargando datos...</div>`;
                countBadge.textContent = '0';
                
                const response = await fetch('FDH_backend.php?table=aplica_fdh');
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    renderTable('aplica', data);
                } else {
                    throw new Error(data.message || data.error || 'Error del servidor');
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `<div class="table-error">
                    <i class='bx bx-error-circle'></i> 
                    <div><strong>Error:</strong> ${escapeHtml(error.message)}</div>
                </div>`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Cargar ambas tablas
            loadTableAplica();
            loadTableNoAplica();

            // Manejo de la sidebar
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // En móvil, prevenir scroll cuando sidebar está abierto
                if (window.innerWidth < 1024) {
                    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                }
            }

            if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
            if (closeSidebar) closeSidebar.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', toggleSidebar);

            // Cerrar sidebar en móvil al hacer clic en un enlace
            document.querySelectorAll('.nav-item a:not(.dropdown-toggle)').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });

            // Manejo de dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.parentElement;
                    const wasActive = dropdown.classList.contains('active');
                    
                    // Cerrar todos los dropdowns primero
                    document.querySelectorAll('.nav-item.dropdown').forEach(d => {
                        if (d !== dropdown) d.classList.remove('active');
                    });
                    
                    // Abrir/cerrar el dropdown actual
                    if (!wasActive) {
                        dropdown.classList.add('active');
                    } else {
                        dropdown.classList.remove('active');
                    }
                });
            });

            // Cerrar dropdowns al hacer clic fuera de ellos
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-item.dropdown')) {
                    document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });

            // Manejo del modo oscuro
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

                    // Guardar preferencia
                    fetch('../../../includes/toggle_dark_mode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'toggle_dark_mode=true'
                    });
                });
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>
