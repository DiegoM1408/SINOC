<?php
/**
 * Sidebar Component Mejorado - Sistema NOC Claro
 * Diseño moderno manteniendo colores corporativos
 */

$config_path = __DIR__ . '/sidebar_config.php';
if (file_exists($config_path)) {
    require_once($config_path);
} else {
    die("Error: No se encontró el archivo sidebar_config.php");
}
?>

<style>
/* ===== SIDEBAR MEJORADO ===== */
.sidebar {
    width: 280px;
    background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
    color: var(--text-light);
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 1000;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
}

.sidebar.collapsed {
    transform: translateX(-280px);
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

/* Divisores en el menú */
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

/* Logo NTT Data */
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

/* Ajuste para el main-content cuando sidebar está visible o colapsada */
@media (min-width: 1024px) {
    .main-content {
        margin-left: 280px;
        width: calc(100% - 280px);
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .sidebar.collapsed ~ .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .close-sidebar {
        display: none;
    }
}

@media (max-width: 1023px) {
    .sidebar {
        left: -280px;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .sidebar.collapsed {
        transform: translateX(0);
    }
}
</style>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
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
        
        <!-- Logo NTT Data -->
        <div class="ntt-logo-container">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo_nttdata7.png" alt="NTT Data">
        </div>
    </div>
</div>

<script>
(function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        // En escritorio, colapsa/expande la sidebar
        if (window.innerWidth >= 1024) {
            sidebar.classList.toggle('collapsed');
        } else {
            // En móvil, muestra/oculta con overlay
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
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

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
})();
</script>