<?php
session_start();
include("../../../includes/db.php");

// Proteger ruta
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login.php');
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

// Definir la página actual para la navegación
$current_page = 'tiempos_7k.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiempos - MinTIC 7K - CLARO NOC</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* MANTENER TODO EL CSS DE paradas_reloj_7k.php */
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

        /* Sidebar Ocultable - COPIADO DE paradas_reloj_7k.php */
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
            padding: 1.75rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            position: relative;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        .logo-text {
            font-size: 1.5rem;
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
            padding: 0.85rem 1.25rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
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
            gap: 0.85rem;
            min-width: 0;
        }

        .nav-item .nav-icon {
            font-size: 1.3rem;
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
            font-size: 1.1rem;
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
            padding: 0.7rem 1rem 0.7rem 1.5rem;
            font-size: 0.88rem;
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
            font-size: 1.15rem;
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
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
            flex-shrink: 0;
        }

        .user-avatar i {
            font-size: 1.3rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.92rem;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.78rem;
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
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .sidebar-btn i {
            font-size: 1.15rem;
            margin-right: 0.7rem;
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

        .badge-error {
            background: rgba(225, 0, 0, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        body.dark-mode .badge-error {
            background: rgba(225, 0, 0, 0.2);
            color: #ff6b6b;
        }

        /* Grid para tablas pequeñas lado a lado */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .tables-grid {
                grid-template-columns: 1fr;
            }
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

        /* Indicador de caché */
        .cache-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
            font-size: 0.7rem;
            color: var(--success-color);
            background: rgba(40, 167, 69, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }

        /* Responsive */
        @media (max-width: 1023px) {
            .sidebar {
                left: -280px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
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

        /* Desktop Styles */
        @media (min-width: 1024px) {
            .sidebar {
                left: -280px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .sidebar-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">
    
    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Ocultable - COPIADO DE paradas_reloj_7k.php -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class='bx bx-network-chart'></i>
                </div>
                <div class="logo-text">
                    <span class="logo-primary">CLARO</span>
                    <span>SINOC</span>
                </div>
            </div>
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
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol', ENT_QUOTES, 'UTF-8'); ?></span>
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
                <h1>Tiempos - MinTIC 7K</h1>
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
                    <h2>Tiempos - MinTIC 7K</h2>
                    <p>Auditoría completa de tiempos y fechas en incidentes del proyecto MinTIC 7K.</p>
                    <div class="badges">
                        <span class="badge"><i class='bx bx-time'></i> Auditoría de Tiempos</span>
                        <span class="badge"><i class='bx bx-calendar-check'></i> Validación de Fechas</span>
                        <span class="badge"><i class='bx bx-error-alt'></i> Detección de Inconsistencias</span>
                    </div>
                </div>
            </div>

            <!-- Tablas de Tiempos -->
            <!-- Tabla 1: Interrupción mayor a apertura - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-time'></i> Fecha de Interrupción Mayor a Fecha de Apertura
                        <span class="count-badge" id="count-interrupcion_mayor_apertura">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-interrupcion_mayor_apertura" data-table="interrupcion_mayor_apertura">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla 2: Mes diferente - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-calendar'></i> Mes de Interrupción Diferente al Mes de Apertura
                        <span class="count-badge" id="count-mes_diferente">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-mes_diferente" data-table="mes_diferente">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid para tablas pequeñas de fechas en blanco -->
            <div class="tables-grid">
                <!-- Tabla 3: Fechas en blanco (Bandera P) -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class='bx bx-calendar-x'></i> Fechas en Blanco (Bandera P)
                            <span class="count-badge" id="count-fechas_blanco_p">0</span>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <div class="lazy-table-container" id="table-fechas_blanco_p" data-table="fechas_blanco_p">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla 4: Fechas en blanco (Bandera O) -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class='bx bx-calendar-x'></i> Fechas en Blanco (Bandera O)
                            <span class="count-badge" id="count-fechas_blanco_o">0</span>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <div class="lazy-table-container" id="table-fechas_blanco_o" data-table="fechas_blanco_o">
                            <div class="table-placeholder">
                                <i class='bx bx-loader-circle'></i>
                                Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla 5: Tiempos muertos - CON SCROLL -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-timer'></i> Tiempos Negativos
                        <span class="count-badge" id="count-tiempos_muertos">0</span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <div class="table-scroll-vertical">
                        <div class="lazy-table-container" id="table-tiempos_muertos" data-table="tiempos_muertos">
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
        // Configuración para las tablas de tiempos
        const tableConfig = {
            'interrupcion_mayor_apertura': {
                columns: ['ID de incidente', 'Inicio de la interrupción de servicio', 'Fecha/hora de apertura'],
                headers: ['ID de Incidente', 'Inicio Interrupción', 'Fecha/Hora Apertura']
            },
            'mes_diferente': {
                columns: ['ID de incidente', 'Inicio de la interrupción de servicio', 'Fecha/hora de apertura'],
                headers: ['ID de Incidente', 'Inicio Interrupción', 'Fecha/Hora Apertura']
            },
            'fechas_blanco_p': {
                columns: ['numero_ticket', 'fecha_apertura', 'fecha_cierre'],
                headers: ['Número de Ticket', 'Fecha Apertura', 'Fecha Cierre']
            },
            'fechas_blanco_o': {
                columns: ['numero_ticket', 'fecha_apertura', 'fecha_cierre'],
                headers: ['Número de Ticket', 'Fecha Apertura', 'Fecha Cierre']
            },
            'tiempos_muertos': {
                columns: ['numero_ticket', 'motivo_parada', 'tiempos_muertos'],
                headers: ['Número de Ticket', 'Motivo de Parada', 'Tiempos Muertos Detectados']
            }
        };

        // Sistema de caché
        const CACHE_DURATION = 5 * 60 * 1000; // 5 minutos en milisegundos
        const tableCache = new Map();

        // Función para obtener datos del caché
        function getCachedData(tableId) {
            const cached = localStorage.getItem(`cache_tiempos_${tableId}`);
            if (!cached) return null;

            const { data, timestamp } = JSON.parse(cached);
            const now = Date.now();

            // Verificar si el caché ha expirado
            if (now - timestamp > CACHE_DURATION) {
                localStorage.removeItem(`cache_tiempos_${tableId}`);
                return null;
            }

            return data;
        }

        // Función para guardar datos en caché
        function setCachedData(tableId, data) {
            const cacheItem = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem(`cache_tiempos_${tableId}`, JSON.stringify(cacheItem));
            tableCache.set(tableId, data);
        }

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

        // Función para formatear fechas
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('es-ES');
        }

        // Función para renderizar una tabla
        function renderTable(tableId, data, fromCache = false) {
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
                // Agregar indicador de caché si corresponde
                if (fromCache) {
                    countBadge.innerHTML += `<span class="cache-indicator" title="Datos en caché"><i class='bx bx-check'></i>CACHÉ</span>`;
                }
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
                    
                    if (tableId === 'tiempos_muertos') {
                        // Renderizado especial para tiempos muertos
                        html += `<td>${escapeHtml(row.numero_ticket)}</td>`;
                        html += `<td>${escapeHtml(row.motivo_parada || 'Sin motivo')}</td>`;
                        html += `<td>`;
                        if (row.tiempos_muertos && row.tiempos_muertos.length > 0) {
                            row.tiempos_muertos.forEach((tiempo, index) => {
                                if (index === 0) {
                                    html += `${escapeHtml(tiempo.desde + ' → ' + tiempo.hasta)}<br>`;
                                    html += `<span class="badge-error">${tiempo.minutos_muertos} min</span>`;
                                }
                            });
                            if (row.tiempos_muertos.length > 1) {
                                html += `<br><small style="color: #666;">(+ ${row.tiempos_muertos.length - 1} tiempo(s) más)</small>`;
                            }
                        } else {
                            html += `No hay tiempos muertos`;
                        }
                        html += `</td>`;
                    } else {
                        // Renderizado normal para otras tablas
                        config.columns.forEach(column => {
                            let value = row[column];
                            // Formatear fechas si es necesario
                            if (column.includes('fecha') || column.includes('Fecha')) {
                                value = formatDate(value);
                            }
                            html += `<td>${escapeHtml(value)}</td>`;
                        });
                    }
                    
                    html += `</tr>`;
                });
            }
            
            html += `</tbody>`;
            html += `</table>`;
            
            container.innerHTML = html;
        }

        // Función para cargar una tabla
        async function loadTable(tableId) {
            const container = document.getElementById(`table-${tableId}`);
            
            // Verificar caché en memoria primero
            if (tableCache.has(tableId)) {
                renderTable(tableId, tableCache.get(tableId), true);
                return;
            }
            
            // Verificar caché en localStorage
            const cachedData = getCachedData(tableId);
            if (cachedData) {
                renderTable(tableId, cachedData, true);
                tableCache.set(tableId, cachedData);
                return;
            }
            
            // Si no hay caché, cargar desde el servidor
            try {
                const response = await fetch(`load_tiempos.php?table=${tableId}`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    // Guardar en caché
                    setCachedData(tableId, data);
                    renderTable(tableId, data, false);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                console.error(`Error cargando tabla ${tableId}:`, error);
                if (container) {
                    container.innerHTML = `<div class="table-error"><i class='bx bx-error-circle'></i> Error: ${error.message}</div>`;
                }
            }
        }

        // Cargar todas las tablas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainers = document.querySelectorAll('.lazy-table-container');
            
            tableContainers.forEach(container => {
                const tableId = container.getAttribute('data-table');
                loadTable(tableId);
            });

            // Manejo de la sidebar ocultable - COPIADO DE paradas_reloj_7k.php
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
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
                    
                    document.querySelectorAll('.nav-item.dropdown').forEach(d => d.classList.remove('active'));
                    
                    if (!wasActive) {
                        dropdown.classList.add('active');
                    }
                });
            });

            document.querySelectorAll('.dropdown-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
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