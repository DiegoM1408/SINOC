<?php

session_start();
include("../../includes/db.php");

require_once '../../config.php';
require_once '../../includes/sidebar_config.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['role'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Datos de sesión seguros
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$nombre_rol = htmlspecialchars($_SESSION['nombre_rol'] ?? 'General', ENT_QUOTES, 'UTF-8');

// Obtener estadísticas de usuarios
$sql_stats = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) as total_gerencia,
    SUM(CASE WHEN role = 3 THEN 1 ELSE 0 END) as total_tecnicos,
    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as total_activos,
    SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as total_inactivos
FROM usuarios";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$mensaje = '';
$tipo_mensaje = '';

// Procesar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cambiar rol
    if (isset($_POST['cambiar_rol'])) {
        $user_id = intval($_POST['user_id']);
        $nuevo_rol = intval($_POST['nuevo_rol']);
        
        $stmt = $conn->prepare("UPDATE usuarios SET role = ? WHERE id_usuario = ?");
        $stmt->bind_param("ii", $nuevo_rol, $user_id);
        
        if ($stmt->execute()) {
            $mensaje = "Rol actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el rol";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
    
    // Agregar nuevo usuario
    if (isset($_POST['agregar_usuario'])) {
        $nuevo_nombre = trim($_POST['nuevo_nombre']);
        $nuevo_username = trim($_POST['nuevo_username']);
        $nuevo_password = password_hash($_POST['nuevo_password'], PASSWORD_DEFAULT);
        $nuevo_role = intval($_POST['nuevo_role']);
        $nuevo_email = trim($_POST['nuevo_email'] ?? '');
        
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $nuevo_username);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $mensaje = "El nombre de usuario ya existe";
            $tipo_mensaje = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, username, password, role, estado, email) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->bind_param("sssis", $nuevo_nombre, $nuevo_username, $nuevo_password, $nuevo_role, $nuevo_email);
            
            if ($stmt->execute()) {
                $mensaje = "Usuario agregado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al agregar usuario";
                $tipo_mensaje = "error";
            }
        }
        $stmt->close();
    }
    
    // Eliminar usuario
    if (isset($_POST['eliminar_usuario'])) {
        $user_id = intval($_POST['user_id']);
        
        if ($user_id == $_SESSION['id_usuario']) {
            $mensaje = "No puedes eliminar tu propio usuario";
            $tipo_mensaje = "error";
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar usuario";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
    }
    
    // Activar/Desactivar usuario
    if (isset($_POST['toggle_estado'])) {
        $user_id = intval($_POST['user_id']);
        $nuevo_estado = intval($_POST['nuevo_estado']);
        
        if ($user_id == $_SESSION['id_usuario'] && $nuevo_estado == 0) {
            $mensaje = "No puedes desactivar tu propio usuario";
            $tipo_mensaje = "error";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
            $stmt->bind_param("ii", $nuevo_estado, $user_id);
            
            if ($stmt->execute()) {
                $estado_texto = $nuevo_estado ? "activado" : "desactivado";
                $mensaje = "Usuario $estado_texto correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al cambiar estado";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
    }
    
    // Restablecer contraseña
    if (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $nueva_password = password_hash("123456", PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $nueva_password, $user_id);
        
        if ($stmt->execute()) {
            $mensaje = "Contraseña restablecida a '123456' correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al restablecer contraseña";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
    
    // Editar usuario
    if (isset($_POST['editar_usuario'])) {
        $user_id = intval($_POST['user_id']);
        $edit_nombre = trim($_POST['edit_nombre']);
        $edit_username = trim($_POST['edit_username']);
        $edit_email = trim($_POST['edit_email'] ?? '');
        $edit_role = intval($_POST['edit_role']);
        
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, username = ?, email = ?, role = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssii", $edit_nombre, $edit_username, $edit_email, $edit_role, $user_id);
        
        if ($stmt->execute()) {
            $mensaje = "Usuario actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar usuario";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
}

// Obtener todos los usuarios
$sql_usuarios = "SELECT * FROM usuarios ORDER BY id_usuario";
$result_usuarios = $conn->query($sql_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - CLARO NOC</title>
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
            --bg-light: #f5f7fa;
            --border-color: #e9ecef;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        body.dark-mode {
            --bg-light: #0f1419;
            --card-bg: #1a1f2e;
            --text-dark: #ffffff;
            --text-gray: #b3b3b3;
            --border-color: #2d3748;
            --sidebar-bg: #1e2a38;
            --sidebar-hover: #2c3e50;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
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

        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: 100%;
        }

        .top-header {
            background: var(--card-bg);
            padding: 1.25rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .sidebar-toggle {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: white;
            padding: 0.65rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
        }

        .sidebar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(225, 0, 0, 0.4);
        }

        .sidebar-toggle:active {
            transform: translateY(0);
        }

        .top-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .header-right img {
            height: 42px;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
        }

        .content-area {
            padding: 2.5rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* Welcome Card - Estilo Index */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-content h2 {
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }

        .welcome-content p {
            font-size: 1.15rem;
            opacity: 0.95;
            font-weight: 400;
        }

        /* Mensaje */
        .mensaje {
            padding: 1.25rem 1.75rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: fadeInUp 0.6s ease;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        body.dark-mode .mensaje.success {
            background: #1e4620;
            color: #a3d9a5;
        }

        body.dark-mode .mensaje.error {
            background: #4a1c1c;
            color: #ff9e9e;
        }

        .mensaje i {
            font-size: 1.5rem;
        }

        /* Stats Grid - Estilo Index */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, rgba(225, 0, 0, 0.1) 0%, rgba(225, 0, 0, 0.05) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .stat-icon-wrapper i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .stat-card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-gray);
        }

        .stat-card-value {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .stat-card-desc {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        /* Section Header - Estilo Index */
        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease 0.4s both;
        }

        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color) 0%, transparent 100%);
            border-radius: 2px;
        }

        /* Form Card - Estilo Index */
        .form-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease 0.6s both;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .form-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-card h3 i {
            color: var(--primary-color);
            font-size: 1.6rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group select {
            padding: 0.9rem 1.1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(225, 0, 0, 0.1);
            background: var(--card-bg);
        }

        .btn {
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(225, 0, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(225, 0, 0, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-sm {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }

        .btn-secondary {
            background: var(--text-gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Table Card - Estilo Index */
        .table-card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.6s ease 0.8s both;
        }

        .table-header {
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-header h2 i {
            font-size: 1.7rem;
        }

        .count-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--bg-light);
            color: var(--text-dark);
            font-weight: 600;
            padding: 1.1rem;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: rgba(225, 0, 0, 0.04);
        }

        /* Badges */
        .role-badge, .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .role-admin {
            background-color: rgba(225, 0, 0, 0.15);
            color: var(--primary-color);
        }

        .role-gerencia {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }

        .role-tecnico {
            background-color: rgba(127, 140, 141, 0.15);
            color: var(--text-gray);
        }

        .status-active {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }

        .status-inactive {
            background-color: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }

        /* Dropdown Acciones */
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dropdown-toggle-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .actions-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            min-width: 220px;
            padding: 0.5rem 0;
        }

        .actions-menu.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .action-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            color: var(--text-dark);
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .action-item:hover {
            background: var(--bg-light);
        }

        .action-item i {
            font-size: 1.2rem;
            width: 20px;
        }

        .action-divider {
            height: 1px;
            background: var(--border-color);
            margin: 0.5rem 0;
        }

        .action-item.danger {
            color: var(--danger-color);
        }

        .action-item.danger:hover {
            background: rgba(220, 53, 69, 0.1);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--primary-color);
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-body {
            margin-bottom: 2rem;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .form-inline {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .form-inline select {
            padding: 0.7rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-inline select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Responsive */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }

        @media (max-width: 1023px) {
            .content-area {
                padding: 1.75rem;
            }
            
            .welcome-card {
                padding: 2rem;
            }
            
            .welcome-content h2 {
                font-size: 1.9rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: 1.25rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .welcome-content h2 {
                font-size: 1.6rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card-value {
                font-size: 2.5rem;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">

    <?php include('../../includes/sidebar.php'); ?>

    <!-- Modal Confirmación -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">⚠️ Confirmar acción</h3>
            </div>
            <div class="modal-body" id="modalMessage">
                ¿Estás seguro de que deseas realizar esta acción?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modalCancel">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modalConfirm">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal Edición -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Usuario</h3>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="form-group">
                        <label for="edit_nombre">
                            <i class='bx bx-user'></i> Nombre Completo
                        </label>
                        <input type="text" id="edit_nombre" name="edit_nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_username">
                            <i class='bx bx-at'></i> Nombre de Usuario
                        </label>
                        <input type="text" id="edit_username" name="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">
                            <i class='bx bx-envelope'></i> Email
                        </label>
                        <input type="email" id="edit_email" name="edit_email">
                    </div>
                    <div class="form-group">
                        <label for="edit_role">
                            <i class='bx bx-shield'></i> Rol
                        </label>
                        <select id="edit_role" name="edit_role" required>
                            <option value="1">Administrador</option>
                            <option value="2">Gerencia</option>
                            <option value="3">Técnico</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="editCancel">Cancelar</button>
                    <button type="submit" name="editar_usuario" class="btn btn-primary">
                        <i class='bx bx-save'></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Gestión de Usuarios</h1>
            </div>
            <div class="header-right">
                <img src="../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <div class="content-area">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>👥 Gestión de Usuarios</h2>
                    <p>Administra los usuarios del sistema, sus roles, permisos y estados</p>
                </div>
            </div>

            <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <i class='bx <?php echo $tipo_mensaje == 'error' ? 'bx-error-circle' : 'bx-check-circle'; ?>'></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-group'></i>
                        </div>
                        <h3>Total Usuarios</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_usuarios']; ?></div>
                    <p class="stat-card-desc">Usuarios registrados</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-user-check'></i>
                        </div>
                        <h3>Activos</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_activos']; ?></div>
                    <p class="stat-card-desc">Usuarios activos</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-user-x'></i>
                        </div>
                        <h3>Inactivos</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_inactivos']; ?></div>
                    <p class="stat-card-desc">Usuarios inactivos</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-crown'></i>
                        </div>
                        <h3>Admins</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_admins']; ?></div>
                    <p class="stat-card-desc">Administradores</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-briefcase'></i>
                        </div>
                        <h3>Gerencia</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_gerencia']; ?></div>
                    <p class="stat-card-desc">Gerentes</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class='bx bx-wrench'></i>
                        </div>
                        <h3>Técnicos</h3>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['total_tecnicos']; ?></div>
                    <p class="stat-card-desc">Usuarios técnicos</p>
                </div>
            </div>

            <!-- Section Header -->
            <div class="section-header">
                <h2>➕ Agregar Nuevo Usuario</h2>
                <div class="section-line"></div>
            </div>

            <!-- Formulario Agregar Usuario -->
            <div class="form-card">
                <h3><i class='bx bx-user-plus'></i> Datos del Nuevo Usuario</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nuevo_nombre">
                                <i class='bx bx-user'></i> Nombre Completo
                            </label>
                            <input type="text" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="nuevo_username">
                                <i class='bx bx-at'></i> Nombre de Usuario
                            </label>
                            <input type="text" id="nuevo_username" name="nuevo_username" required>
                        </div>
                        <div class="form-group">
                            <label for="nuevo_email">
                                <i class='bx bx-envelope'></i> Email
                            </label>
                            <input type="email" id="nuevo_email" name="nuevo_email">
                        </div>
                        <div class="form-group">
                            <label for="nuevo_password">
                                <i class='bx bx-lock'></i> Contraseña
                            </label>
                            <input type="password" id="nuevo_password" name="nuevo_password" required>
                        </div>
                        <div class="form-group">
                            <label for="nuevo_role">
                                <i class='bx bx-shield'></i> Rol
                            </label>
                            <select id="nuevo_role" name="nuevo_role" required>
                                <option value="1">Administrador</option>
                                <option value="2">Gerencia</option>
                                <option value="3">Técnico</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="agregar_usuario" class="btn btn-primary">
                        <i class='bx bx-user-plus'></i> Agregar Usuario
                    </button>
                </form>
            </div>

            <!-- Section Header -->
            <div class="section-header">
                <h2>📋 Lista de Usuarios</h2>
                <div class="section-line"></div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="table-card">
                <div class="table-header">
                    <h2><i class='bx bx-list-ul'></i> Usuarios del Sistema</h2>
                    <span class="count-badge"><?php echo $result_usuarios->num_rows; ?> usuarios</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Cambiar Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $usuario['id_usuario']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email'] ?? 'No definido'); ?></td>
                                <td>
                                    <?php 
                                    if ($usuario['role'] == 1) echo '<span class="role-badge role-admin">Admin</span>';
                                    elseif ($usuario['role'] == 2) echo '<span class="role-badge role-gerencia">Gerencia</span>';
                                    else echo '<span class="role-badge role-tecnico">Técnico</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($usuario['estado'] == 1) echo '<span class="status-badge status-active">Activo</span>';
                                    else echo '<span class="status-badge status-inactive">Inactivo</span>';
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                        <select name="nuevo_rol">
                                            <option value="1" <?php echo $usuario['role'] == 1 ? 'selected' : ''; ?>>Admin</option>
                                            <option value="2" <?php echo $usuario['role'] == 2 ? 'selected' : ''; ?>>Gerencia</option>
                                            <option value="3" <?php echo $usuario['role'] == 3 ? 'selected' : ''; ?>>Técnico</option>
                                        </select>
                                        <button type="submit" name="cambiar_rol" class="btn btn-sm btn-primary">
                                            Cambiar
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions-dropdown">
                                        <button class="dropdown-toggle-btn" onclick="toggleDropdown(this)">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <div class="actions-menu">
                                            <button type="button" class="action-item" onclick="openEditModal(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES); ?>', <?php echo $usuario['role']; ?>)">
                                                <i class='bx bx-edit'></i>
                                                Editar
                                            </button>

                                            <form method="POST" style="display: none;" id="formToggle<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?php echo $usuario['estado'] ? 0 : 1; ?>">
                                                <input type="hidden" name="toggle_estado" value="1">
                                            </form>
                                            <button type="button" class="action-item" onclick="document.getElementById('formToggle<?php echo $usuario['id_usuario']; ?>').submit()">
                                                <i class='bx <?php echo $usuario['estado'] ? 'bx-user-x' : 'bx-user-check'; ?>'></i>
                                                <?php echo $usuario['estado'] ? 'Desactivar' : 'Activar'; ?>
                                            </button>

                                            <form method="POST" style="display: none;" id="formReset<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="reset_password" value="1">
                                            </form>
                                            <button type="button" class="action-item" onclick="if(confirm('¿Restablecer contraseña a 123456?')) document.getElementById('formReset<?php echo $usuario['id_usuario']; ?>').submit()">
                                                <i class='bx bx-refresh'></i>
                                                Resetear Password
                                            </button>

                                            <?php if ($usuario['id_usuario'] != $_SESSION['id_usuario']): ?>
                                            <div class="action-divider"></div>
                                            <form method="POST" style="display: none;" id="formDelete<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                                <input type="hidden" name="eliminar_usuario" value="1">
                                            </form>
                                            <button type="button" class="action-item danger" onclick="confirmDelete(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES); ?>')">
                                                <i class='bx bx-trash'></i>
                                                Eliminar
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.getElementById('body');
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function() {
                const isDarkMode = body.classList.toggle('dark-mode');
                const icon = this.querySelector('i');
                const text = this.querySelector('span');
                
                icon.className = isDarkMode ? 'bx bx-sun' : 'bx bx-moon';
                text.textContent = isDarkMode ? 'Modo Claro' : 'Modo Oscuro';

                fetch('../../includes/toggle_dark_mode.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'toggle_dark_mode=true'
                });
            });
        }

        // Dropdown actions
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            const isOpen = dropdown.classList.contains('show');
            
            document.querySelectorAll('.actions-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            if (!isOpen) {
                dropdown.classList.add('show');
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.actions-dropdown')) {
                document.querySelectorAll('.actions-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        // Confirm delete
        let currentDeleteForm = null;
        const confirmModal = document.getElementById('confirmModal');
        
        function confirmDelete(userId, userName) {
            currentDeleteForm = document.getElementById('formDelete' + userId);
            document.getElementById('modalTitle').textContent = '⚠️ Confirmar Eliminación';
            document.getElementById('modalMessage').textContent = `¿Estás seguro de que deseas eliminar al usuario "${userName}"? Esta acción no se puede deshacer.`;
            confirmModal.classList.add('show');
        }

        document.getElementById('modalCancel').addEventListener('click', () => {
            confirmModal.classList.remove('show');
            currentDeleteForm = null;
        });

        document.getElementById('modalConfirm').addEventListener('click', () => {
            if (currentDeleteForm) currentDeleteForm.submit();
            confirmModal.classList.remove('show');
        });

        // Open edit modal
        const editModal = document.getElementById('editModal');
        
        function openEditModal(userId, nombre, username, email, role) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            editModal.classList.add('show');
        }

        document.getElementById('editCancel').addEventListener('click', () => {
            editModal.classList.remove('show');
        });

        // Close modals on outside click
        [confirmModal, editModal].forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    currentDeleteForm = null;
                }
            });
        });
    </script>
</body>
</html>