<?php
session_start();
include("../../includes/db.php");

require_once '../../config.php';
require_once '../../includes/sidebar_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../index.php");
    exit;
}

// Determinar qué usuario estamos editando
if (isset($_GET['edit_user']) && $_SESSION['role'] == 1) {
    $user_id = intval($_GET['edit_user']);
    $editing_own_profile = ($user_id == $_SESSION['id_usuario']);
} else {
    $user_id = $_SESSION['id_usuario'];
    $editing_own_profile = true;
}

// Verificar permisos
if (!$editing_own_profile && $_SESSION['role'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Procesar actualización de datos
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $target_user_id = intval($_POST['id_usuario']);
    
    $is_admin_editing = ($_SESSION['role'] == 1 && !$editing_own_profile);
    
    if (!$is_admin_editing && $target_user_id != $_SESSION['id_usuario']) {
        $mensaje = 'Error: No tienes permisos para modificar este usuario';
        $tipo_mensaje = 'error';
    } else {
        // Usar prepared statements para seguridad
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($is_admin_editing && isset($_POST['role'])) {
                $nuevo_rol = intval($_POST['role']);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, username = ?, password = ?, role = ? WHERE id_usuario = ?");
                $stmt->bind_param("sssii", $nombre, $username, $hashed_password, $nuevo_rol, $target_user_id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, username = ?, password = ? WHERE id_usuario = ?");
                $stmt->bind_param("sssi", $nombre, $username, $hashed_password, $target_user_id);
            }
        } else {
            if ($is_admin_editing && isset($_POST['role'])) {
                $nuevo_rol = intval($_POST['role']);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, username = ?, role = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssii", $nombre, $username, $nuevo_rol, $target_user_id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, username = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssi", $nombre, $username, $target_user_id);
            }
        }
        
        if ($stmt->execute()) {
            $mensaje = 'Datos actualizados correctamente';
            $tipo_mensaje = 'success';
            
            if ($target_user_id == $_SESSION['id_usuario']) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['username'] = $username;
            }
        } else {
            $mensaje = 'Error al actualizar: ' . $stmt->error;
            $tipo_mensaje = 'error';
        }
        $stmt->close();
    }
}

// Obtener datos del usuario a editar
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();
$usuario = $result_user->fetch_assoc();
$stmt->close();

// Determinar si mostramos controles de admin
$show_admin_controls = ($_SESSION['role'] == 1 && !$editing_own_profile);

// Datos de sesión seguros
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Claro</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #E10000;
            --secondary-color: #CC0000;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --text-light: #ffffff;
            --text-dark: #2c3e50;
            --text-gray: #7f8c8d;
            --card-bg: #ffffff;
            --bg-light: #f5f7fa;
            --border-color: #e9ecef;
            --success-color: #28a745;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode {
            --bg-light: #1a1f2e;
            --card-bg: #252d3d;
            --text-dark: #ffffff;
            --text-gray: #b3b3b3;
            --border-color: #3a4556;
            --sidebar-bg: #1e2a38;
            --sidebar-hover: #2c3e50;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
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

        /* MAIN CONTENT - CENTRADO PERFECTO */
        .main-content {
            width: calc(100% - 280px);
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Cuando el sidebar está oculto, el contenido ocupa todo el ancho y se centra */
        .main-content.expanded {
            width: 100%;
            margin-left: 0;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar hidden state */
        .sidebar.hidden {
            transform: translateX(-100%);
        }

        /* TOP HEADER */
        .top-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 280px;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
            transition: left 0.3s ease;
        }

        body.dark-mode .top-header {
            background: rgba(30, 36, 51, 0.95);
        }

        .main-content.expanded .top-header {
            left: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: var(--primary-color);
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
        }

        .sidebar-toggle:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }

        .top-header h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .header-right img {
            height: 35px;
        }

        /* CONTENT AREA - CENTRADO HORIZONTAL */
        .content-area {
            padding: 1.5rem;
            padding-top: calc(56px + 1.5rem);
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
            transition: all 0.3s ease;
        }

        /* Cuando sidebar está oculto, centrar contenido en toda la pantalla */
        .main-content.expanded .content-area {
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 2rem;
            padding-right: 2rem;
        }

        .page-title {
            margin-bottom: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        /* Título con más énfasis cuando sidebar está oculto */
        .main-content.expanded .page-title {
            margin-bottom: 2rem;
        }

        .page-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
        }

        .page-title p {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            animation: slideDown 0.3s ease;
            font-size: 0.9rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        body.dark-mode .alert.success {
            background: #1e4620;
            color: #a3d9a5;
        }

        body.dark-mode .alert.error {
            background: #4a1c1c;
            color: #ff9e9e;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* GRID DE CONFIGURACIÓN - CENTRADO */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        /* Grid más compacto cuando sidebar está oculto para mejor centrado visual */
        .main-content.expanded .config-grid {
            max-width: 900px;
        }

        .config-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .config-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        body.dark-mode .config-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1), rgba(225, 0, 0, 0.05));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-icon i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-group label i {
            margin-right: 0.4rem;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 3px rgba(225, 0, 0, 0.1);
        }

        .form-group input[disabled] {
            background: var(--border-color);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .password-note {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-top: 0.4rem;
            font-style: italic;
        }

        /* BOTONES */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(225, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(225, 0, 0, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--text-gray);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            text-align: center;
            transition: all 0.3s ease;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Columna completa para botones */
        .full-column {
            grid-column: 1 / -1;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }

            .top-header {
                left: 0;
            }

            .config-grid {
                grid-template-columns: 1fr;
                max-width: 600px;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
                padding-top: calc(52px + 1rem);
            }
            
            .config-card {
                padding: 1.5rem;
            }

            .page-title h2 {
                font-size: 1.3rem;
            }

            .page-title p {
                font-size: 0.85rem;
            }

            .top-header h1 {
                font-size: 1rem;
            }

            .top-header {
                padding: 0.85rem 1rem;
            }

            .card-header h3 {
                font-size: 1rem;
            }

            .form-group input,
            .form-group select {
                font-size: 0.9rem;
                padding: 0.65rem 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .config-card {
                padding: 1.25rem;
            }

            .card-icon {
                width: 40px;
                height: 40px;
            }

            .card-icon i {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">

    <?php include('../../includes/sidebar.php'); ?>

    <div class="main-content" id="mainContent">
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Configuración</h1>
            </div>
            <div class="header-right">
                <img src="../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <div class="content-area">
            <div class="page-title">
                <h2><?php echo $editing_own_profile ? 'Mi Cuenta' : 'Editar Usuario'; ?></h2>
                <p>Gestiona tus credenciales de acceso</p>
            </div>

            <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo $tipo_mensaje; ?>">
                <i class='bx <?php echo $tipo_mensaje == 'error' ? 'bx-error-circle' : 'bx-check-circle'; ?>'></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                
                <div class="config-grid">
                    <!-- Columna 1: Información Personal -->
                    <div class="config-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class='bx bx-user-circle'></i>
                            </div>
                            <h3>Información Personal</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre">
                                <i class='bx bx-user'></i> Nombre completo
                            </label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">
                                <i class='bx bx-at'></i> Nombre de usuario
                            </label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
                        </div>
                    </div>

                    <!-- Columna 2: Seguridad -->
                    <div class="config-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class='bx bx-shield-alt'></i>
                            </div>
                            <h3>Seguridad</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">
                                <i class='bx bx-lock-alt'></i> Nueva contraseña
                            </label>
                            <input type="password" id="password" name="password" 
                                   placeholder="••••••••">
                            <p class="password-note">Dejar en blanco para mantener la contraseña actual</p>
                        </div>
                        
                        <?php if ($show_admin_controls): ?>
                        <div class="form-group">
                            <label for="role">
                                <i class='bx bx-shield'></i> Rol de usuario
                            </label>
                            <select id="role" name="role">
                                <option value="1" <?php echo $usuario['role'] == 1 ? 'selected' : ''; ?>>Administrador</option>
                                <option value="2" <?php echo $usuario['role'] == 2 ? 'selected' : ''; ?>>Gerencia</option>
                                <option value="3" <?php echo $usuario['role'] == 3 ? 'selected' : ''; ?>>Técnico</option>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label for="role_display">
                                <i class='bx bx-shield'></i> Rol de usuario
                            </label>
                            <input type="text" id="role_display" value="<?php 
                                if ($usuario['role'] == 1) echo 'Administrador';
                                elseif ($usuario['role'] == 2) echo 'Gerencia';
                                else echo 'Técnico';
                            ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones en fila completa -->
                    <div class="full-column">
                        <button type="submit" class="btn-primary">
                            <i class='bx bx-save'></i> Guardar cambios
                        </button>
                        
                        <?php if (!$editing_own_profile): ?>
                        <a href="../../modules/gestion_usuario/gestion_usuarios.php" class="btn-secondary">
                            <i class='bx bx-arrow-back'></i> Volver a Gestión de Usuarios
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ==========================================
        // BOTÓN HAMBURGUESA - TOGGLE SIDEBAR
        // ==========================================
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.getElementById('mainContent');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            });
        }

        // ==========================================
        // MODO OSCURO
        // ==========================================
        window.addEventListener('load', function() {
            setTimeout(function() {
                const body = document.getElementById('body');
                
                // Buscar todos los elementos que puedan ser el botón de modo oscuro
                const allElements = document.querySelectorAll('a, button, .sidebar-item, [onclick]');
                
                allElements.forEach(function(element) {
                    const text = element.textContent || element.innerText || '';
                    
                    // Si el texto contiene "Modo Oscuro" o "Modo Claro"
                    if (text.includes('Modo Oscuro') || text.includes('Modo Claro')) {
                        
                        // Reemplazar el elemento para limpiar event listeners previos
                        const newElement = element.cloneNode(true);
                        element.parentNode.replaceChild(newElement, element);
                        
                        // Agregar nuevo event listener
                        newElement.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            // Toggle dark mode
                            body.classList.toggle('dark-mode');
                            const isDark = body.classList.contains('dark-mode');
                            
                            // Actualizar el icono y texto
                            const icon = this.querySelector('i');
                            const span = this.querySelector('span');
                            
                            if (icon) {
                                icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
                            }
                            
                            if (span) {
                                span.textContent = isDark ? 'Modo Claro' : 'Modo Oscuro';
                            }
                            
                            // Guardar en el servidor
                            fetch('../../includes/toggle_dark_mode.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'toggle_dark_mode=true'
                            });
                            
                            return false;
                        }, true);
                    }
                });
            }, 200);
        });
    </script>
</body>
</html>