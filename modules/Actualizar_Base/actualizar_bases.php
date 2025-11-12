<?php
session_start();
include("../../includes/db.php");

require_once '../../config.php';
require_once '../../includes/sidebar_config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

date_default_timezone_set('America/Bogota');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Bases de Datos - Sistema NOC Claro</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon-16x16.png">
    <link rel="shortcut icon" href="../../assets/images/favicon.ico">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #E10000;
            --secondary-color: #CC0000;
            --accent-color: #FF5252;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: #252b3d;
            --text-light: #ffffff;
            --text-dark: #1a1f2e;
            --text-gray: #64748b;
            --card-bg: #ffffff;
            --bg-light: #f8fafc;
            --bg-gradient-start: #fafbfc;
            --bg-gradient-end: #f1f5f9;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body.dark-mode {
            --bg-light: #0f1419;
            --bg-gradient-start: #0f1419;
            --bg-gradient-end: #1a1f2e;
            --card-bg: #1e2433;
            --text-dark: #f1f5f9;
            --text-gray: #94a3b8;
            --border-color: #2d3748;
            --sidebar-bg: #14181f;
            --sidebar-hover: #1e2433;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-dark);
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar hidden state */
        .sidebar.hidden {
            transform: translateX(-100%);
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded .content-area {
                margin-left: auto;
                margin-right: auto;
            }
        }

        /* ==================== HEADER ==================== */
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
            box-shadow: var(--shadow-md);
            z-index: 900;
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
            transition: left 0.3s ease;
            height: 60px;
        }

        body.dark-mode .top-header {
            background: rgba(30, 36, 51, 0.95);
        }

        /* Header cuando sidebar está oculta */
        .main-content.expanded .top-header {
            left: 0;
        }

        @media (max-width: 1023px) {
            .top-header {
                left: 0;
            }
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
            box-shadow: 0 2px 8px rgba(225, 0, 0, 0.3);
        }

        .sidebar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.4);
        }

        .sidebar-toggle:active {
            transform: translateY(0);
        }

        .top-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, #CC0000 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .header-right img {
            height: 35px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        /* ==================== CONTENT AREA ==================== */
        .content-area {
            padding: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
        }

        .content-wrapper {
            width: 100%;
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

        /* ==================== SECTION DIVIDER ==================== */
        .section-divider {
            display: flex;
            align-items: center;
            margin: 2.5rem 0;
            gap: 1rem;
        }

        .divider-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--border-color) 50%, transparent 100%);
        }

        .divider-text {
            padding: 0.5rem 1.5rem;
            background: var(--card-bg);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-gray);
            border: 1px solid var(--border-color);
            white-space: nowrap;
        }

        /* ==================== FORM CARD ==================== */
        .form-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            width: 100%;
            margin-bottom: 2rem;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(225, 0, 0, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid var(--border-color);
        }

        .form-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-title i {
            font-size: 1.6rem;
            color: var(--primary-color);
        }

        .form-subtitle {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        /* ==================== DROPDOWN STYLES ==================== */
        .dropdown-container {
            margin-bottom: 2rem;
        }

        .dropdown-header {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .dropdown-header:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .dropdown-header.active {
            border-color: var(--primary-color);
            background: rgba(225, 0, 0, 0.03);
            box-shadow: 0 0 0 3px rgba(225, 0, 0, 0.1);
        }

        .dropdown-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .dropdown-title i {
            font-size: 1.5rem;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            color: var(--text-gray);
        }

        .dropdown-header.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-content {
            display: none;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.5rem;
            animation: slideDown 0.3s ease;
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

        /* ==================== FREQUENCY BADGE ==================== */
        .frequency-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.75rem;
        }

        .badge-constant {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-monthly {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* ==================== DATABASE LIST ==================== */
        .database-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .database-item {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .database-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .database-icon {
            font-size: 2rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .database-info {
            flex: 1;
        }

        .database-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .database-description {
            font-size: 0.85rem;
            color: var(--text-gray);
            line-height: 1.4;
        }

        .database-frequency {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        /* ==================== MODAL STYLES ==================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 1rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid var(--border-color);
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-gray);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: rgba(225, 0, 0, 0.1);
            color: var(--primary-color);
            transform: rotate(90deg);
        }

        .selected-database-info {
            background: rgba(225, 0, 0, 0.05);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(225, 0, 0, 0.2);
        }

        .database-display {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .database-display-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .database-display-info {
            flex: 1;
        }

        .database-display-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .database-display-description {
            font-size: 0.85rem;
            color: var(--text-gray);
            line-height: 1.4;
        }

        /* ==================== FORM ELEMENTS ==================== */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        select, input[type="file"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(225, 0, 0, 0.1);
        }

        /* ==================== UPLOAD AREA ==================== */
        .upload-area {
            border: 3px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(225, 0, 0, 0.02);
            position: relative;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(225, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .upload-area h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .upload-area p {
            color: var(--text-gray);
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* ==================== BUTTON ==================== */
        .btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(225, 0, 0, 0.4);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn:disabled {
            background: var(--text-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-gray);
            border: 2px solid var(--border-color);
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: var(--border-color);
            color: var(--text-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* ==================== PROGRESS ==================== */
        .progress-container {
            margin-top: 1.5rem;
            display: none;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .progress-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .progress-percent {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .status {
            text-align: center;
            margin-top: 0.75rem;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        /* ==================== LOGS ==================== */
        .logs {
            margin-top: 1.5rem;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: none;
        }

        .logs-header {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logs-header i {
            color: var(--primary-color);
        }

        .log-entry {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
            font-family: monospace;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .success { color: var(--success-color); }
        .error { color: var(--primary-color); }
        .warning { color: var(--warning-color); }
        .info { color: var(--info-color); }

        /* ==================== FILE INFO ==================== */
        .file-info {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(16, 185, 129, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            display: none;
        }

        .file-info-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--success-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
        }

        .file-detail {
            display: flex;
            flex-direction: column;
        }

        .file-label {
            font-size: 0.75rem;
            color: var(--text-gray);
            margin-bottom: 0.2rem;
        }

        .file-value {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* ==================== SUCCESS MESSAGE ==================== */
        .success-message {
            text-align: center;
            padding: 2rem 1.5rem;
            display: none;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        .success-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 0.75rem;
        }

        .success-text {
            color: var(--text-gray);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1023px) {
            .content-area {
                margin-top: 60px;
                min-height: calc(100vh - 60px);
                padding-top: 1rem;
            }
            
            .modal-content {
                max-width: 95%;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
                min-height: calc(100vh - 55px);
                margin-top: 55px;
                padding-top: 0.5rem;
            }
            
            .top-header {
                padding: 0.5rem 1rem;
                height: 55px;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .modal-content {
                padding: 1.25rem;
                max-width: 98%;
                max-height: 90vh;
            }
            
            .upload-area {
                padding: 1.5rem 1rem;
            }
            
            .upload-icon {
                font-size: 2.5rem;
            }
            
            .top-header h1 {
                font-size: 1rem;
            }

            .form-title {
                font-size: 1.2rem;
            }

            .modal-title {
                font-size: 1.1rem;
            }

            .form-subtitle {
                font-size: 0.85rem;
            }

            .database-item {
                padding: 1rem;
            }

            .database-icon {
                font-size: 1.75rem;
            }

            .divider-text {
                padding: 0.4rem 1rem;
                font-size: 0.8rem;
            }

            .dropdown-header {
                padding: 1rem;
            }

            .database-display {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .database-display-icon {
                font-size: 2rem;
            }

            .button-group {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .btn {
                padding: 0.9rem 1.25rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .content-area {
                padding: 0.75rem;
                min-height: calc(100vh - 50px);
                margin-top: 50px;
                padding-top: 0.25rem;
            }
            
            .top-header {
                padding: 0.4rem 0.75rem;
                height: 50px;
            }
            
            .form-card {
                padding: 1.25rem;
            }
            
            .modal-content {
                padding: 1rem;
                max-width: 99%;
            }
            
            .upload-area {
                padding: 1.25rem 0.75rem;
            }
            
            .file-details {
                grid-template-columns: 1fr;
            }
            
            .top-header h1 {
                font-size: 0.9rem;
            }
            
            .header-right img {
                height: 30px;
            }
            
            .sidebar-toggle {
                width: 32px;
                height: 32px;
                font-size: 1.1rem;
            }

            .frequency-badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .database-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .database-frequency {
                position: static;
                align-self: flex-end;
            }

            .dropdown-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .dropdown-title {
                width: 100%;
            }

            .modal-header {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }

            .close-modal {
                align-self: flex-end;
                margin-top: -2.5rem;
            }

            .success-message {
                padding: 1.5rem 1rem;
            }

            .success-icon {
                font-size: 3rem;
            }

            .success-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 360px) {
            .content-area {
                padding: 0.5rem;
                margin-top: 50px;
                min-height: calc(100vh - 50px);
            }
            
            .top-header {
                height: 50px;
                padding: 0.3rem 0.5rem;
            }
            
            .top-header h1 {
                font-size: 0.8rem;
            }
            
            .form-card {
                padding: 1rem;
            }
            
            .modal-content {
                padding: 0.75rem;
                max-width: 100%;
            }
            
            .form-title {
                font-size: 1.1rem;
            }
            
            .modal-title {
                font-size: 1rem;
            }
            
            .upload-area {
                padding: 1rem 0.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }
            
            .content-wrapper {
                padding-bottom: 1rem;
            }
        }

        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body id="body" class="<?php echo $_SESSION['dark_mode'] ? 'dark-mode' : ''; ?>">

    <!-- Sidebar Izquierda -->
    <?php include('../../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Actualizar Bases de Datos</h1>
            </div>
            <div class="header-right">
                <img src="../../assets/images/claro-logo6.png" alt="CLARO">
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <div class="content-wrapper">
                <!-- Sección de Selección de Base de Datos -->
                <div class="form-card">
                    <div class="form-header">
                        <h2 class="form-title">
                            <i class='bx bx-data'></i>
                            Seleccionar Base de Datos
                        </h2>
                        <p class="form-subtitle">Elige la base de datos que deseas actualizar</p>
                    </div>
                    
                    <!-- Sección de Actualización Constante -->
                    <div class="dropdown-container">
                        <div class="dropdown-header" id="constantDropdown">
                            <div class="dropdown-title">
                                <i class='bx bx-refresh' style="color: var(--success-color);"></i>
                                Actualización Constante
                                <span class="frequency-badge badge-constant">
                                    <i class='bx bx-time'></i>
                                    Actualización Frecuente
                                </span>
                            </div>
                            <i class='bx bx-chevron-down dropdown-arrow'></i>
                        </div>
                        <div class="dropdown-content" id="constantContent">
                            <div class="database-list">
                                <div class="database-item" data-value="incidentes" data-name="Base de Incidentes MinTIC-7K" data-description="Service Manager - Incidentes para el contrato 7K" data-icon="bx-error-circle">
                                    <div class="database-icon">
                                        <i class='bx bx-error-circle'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de Incidentes MinTIC-7K</div>
                                        <div class="database-description">Service Manager - Incidentes para 7K</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>
                                
                                <div class="database-item" data-value="reportes" data-name="Base de Reportes MinTIC-7K" data-description="Portal de Reportes - Métricas y reportes para 7K" data-icon="bx-bar-chart-alt">
                                    <div class="database-icon">
                                        <i class='bx bx-bar-chart-alt'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de Reportes MinTIC-7K</div>
                                        <div class="database-description">Portal de Reportes - Métricas y reportes para 7K</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>
                                
                                <div class="database-item" data-value="incidentes_odh" data-name="Base de Incidentes ODH" data-description="Service Manager ODH - Incidentes para ODH" data-icon="bx-error">
                                    <div class="database-icon">
                                        <i class='bx bx-error'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de Incidentes ODH</div>
                                        <div class="database-description">Service Manager ODH - Incidentes para ODH</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>
                                
                                <div class="database-item" data-value="reportes_odh" data-name="Base de Reportes ODH" data-description="Portal de Reportes ODH - Métricas y reportes para ODH" data-icon="bx-pie-chart-alt">
                                    <div class="database-icon">
                                        <i class='bx bx-pie-chart-alt'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de Reportes ODH</div>
                                        <div class="database-description">Portal de Reportes ODH - Métricas y reportes para ODH</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>

                                <div class="database-item" data-value="sds_7k" data-name="Base de SDs MinTIC-7K" data-description="Service Manager SDs - Solicitudes de servicio para 7K" data-icon="bx-task">
                                    <div class="database-icon">
                                        <i class='bx bx-task'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de SDs MinTIC-7K</div>
                                        <div class="database-description">Service Manager SDs - Solicitudes de servicio para 7K</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>
                                
                                <div class="database-item" data-value="sds_odh" data-name="Base de SDs MinTIC-ODH" data-description="Service Manager SDs - Solicitudes de servicio para ODH" data-icon="bx-clipboard">
                                    <div class="database-icon">
                                        <i class='bx bx-clipboard'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Base de SDs MinTIC-ODH</div>
                                        <div class="database-description">Service Manager SDs - Solicitudes de servicio para ODH</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-constant">Constante</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Actualización Mensual -->
                    <div class="dropdown-container">
                        <div class="dropdown-header" id="monthlyDropdown">
                            <div class="dropdown-title">
                                <i class='bx bx-calendar' style="color: var(--warning-color);"></i>
                                Actualización Mensual
                                <span class="frequency-badge badge-monthly">
                                    <i class='bx bx-calendar-event'></i>
                                    Actualización Mensual
                                </span>
                            </div>
                            <i class='bx bx-chevron-down dropdown-arrow'></i>
                        </div>
                        <div class="dropdown-content" id="monthlyContent">
                            <div class="database-list">
                                <div class="database-item" data-value="semilla_7k" data-name="Semilla 7K" data-description="Base de datos de inventario semilla para MinTIC-7K" data-icon="bx-leaf">
                                    <div class="database-icon">
                                        <i class='bx bx-leaf'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Semilla 7K</div>
                                        <div class="database-description">Base de datos de inventario semilla para MinTIC-7K</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-monthly">Mensual</span>
                                    </div>
                                </div>
                                
                                <div class="database-item" data-value="semilla_odh" data-name="Semilla ODH" data-description="Base de datos de inventario semilla para MinTIC-ODH" data-icon="bx-leaf">
                                    <div class="database-icon">
                                        <i class='bx bx-leaf'></i>
                                    </div>
                                    <div class="database-info">
                                        <div class="database-name">Semilla ODH</div>
                                        <div class="database-description">Base de datos de inventario semilla para MinTIC-ODH</div>
                                    </div>
                                    <div class="database-frequency">
                                        <span class="frequency-badge badge-monthly">Mensual</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Actualización -->
    <div class="modal-overlay" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class='bx bx-cloud-upload'></i>
                    Actualizar Base de Datos
                </h2>
                <button class="close-modal" id="closeModal">
                    <i class='bx bx-x'></i>
                </button>
            </div>

            <!-- Información de la base seleccionada -->
            <div class="selected-database-info" id="selectedDatabaseInfo">
                <div class="database-display">
                    <div class="database-display-icon">
                        <i class='bx' id="modalDatabaseIcon"></i>
                    </div>
                    <div class="database-display-info">
                        <div class="database-display-name" id="modalDatabaseName"></div>
                        <div class="database-display-description" id="modalDatabaseDescription"></div>
                    </div>
                    <span class="frequency-badge" id="modalDatabaseFrequency"></span>
                </div>
            </div>

            <!-- Formulario de carga -->
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" id="selectedDatabase" name="database" required>
                
                <div class="form-group">
                    <label for="fileInput">Archivo:</label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">📁</div>
                        <h3 id="uploadTitle">Arrastra y suelta tu archivo CSV aquí</h3>
                        <p id="uploadSubtitle">o haz clic para seleccionar</p>
                        <input type="file" id="fileInput" name="csvFile" required style="display: none;">
                    </div>
                    <div class="file-info" id="fileInfo">
                        <div class="file-info-title">
                            <i class='bx bx-info-circle'></i>
                            Información del archivo
                        </div>
                        <div class="file-details">
                            <div class="file-detail">
                                <span class="file-label">Nombre:</span>
                                <span class="file-value" id="fileName">-</span>
                            </div>
                            <div class="file-detail">
                                <span class="file-label">Tamaño:</span>
                                <span class="file-value" id="fileSize">-</span>
                            </div>
                            <div class="file-detail">
                                <span class="file-label">Tipo:</span>
                                <span class="file-value" id="fileType">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">
                        <i class='bx bx-x'></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn" id="submitBtn" disabled>
                        <i class='bx bx-cloud-upload'></i>
                        Iniciar Actualización
                    </button>
                </div>
            </form>
            
            <!-- Progreso de actualización -->
            <div class="progress-container" id="progressContainer">
                <div class="progress-header">
                    <div class="progress-title">Progreso de actualización</div>
                    <div class="progress-percent" id="progressPercent">0%</div>
                </div>
                <div class="progress-bar">
                    <div class="progress" id="progressBar"></div>
                </div>
                <div class="status" id="statusText">Preparando actualización...</div>
            </div>
            
            <!-- Logs del proceso -->
            <div class="logs" id="logsContainer">
                <div class="logs-header">
                    <i class='bx bx-list-check'></i>
                    Registro de Proceso
                </div>
                <div id="logs"></div>
            </div>

            <!-- Mensaje de éxito -->
            <div class="success-message" id="successMessage">
                <div class="success-icon">
                    <i class='bx bx-check-circle'></i>
                </div>
                <div class="success-title">¡Actualización Completada!</div>
                <div class="success-text">La base de datos ha sido actualizada exitosamente.</div>
                <button class="btn" id="closeSuccessBtn">
                    <i class='bx bx-check'></i>
                    Cerrar
                </button>
            </div>
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
        // DARK MODE
        // ==========================================
        window.addEventListener('load', function() {
            setTimeout(function() {
                const body = document.getElementById('body');
                const allElements = document.querySelectorAll('a, button, .sidebar-item, [onclick]');
                
                allElements.forEach(function(element) {
                    const text = element.textContent || element.innerText || '';
                    
                    if (text.includes('Modo Oscuro') || text.includes('Modo Claro')) {
                        const newElement = element.cloneNode(true);
                        element.parentNode.replaceChild(newElement, element);
                        
                        newElement.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            body.classList.toggle('dark-mode');
                            const isDark = body.classList.contains('dark-mode');
                            
                            const icon = this.querySelector('i');
                            const span = this.querySelector('span');
                            
                            if (icon) {
                                icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
                            }
                            
                            if (span) {
                                span.textContent = isDark ? 'Modo Claro' : 'Modo Oscuro';
                            }
                            
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

        // ==========================================
        // MODAL Y FUNCIONALIDAD PRINCIPAL
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del modal
            const updateModal = document.getElementById('updateModal');
            const closeModal = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelBtn');
            const closeSuccessBtn = document.getElementById('closeSuccessBtn');

            // Configurar dropdowns
            const constantDropdown = document.getElementById('constantDropdown');
            const constantContent = document.getElementById('constantContent');
            const monthlyDropdown = document.getElementById('monthlyDropdown');
            const monthlyContent = document.getElementById('monthlyContent');

            // Función para alternar dropdowns
            function toggleDropdown(header, content) {
                const isActive = header.classList.contains('active');
                
                // Cerrar todos los dropdowns primero
                document.querySelectorAll('.dropdown-header').forEach(h => {
                    h.classList.remove('active');
                });
                document.querySelectorAll('.dropdown-content').forEach(c => {
                    c.style.display = 'none';
                });
                
                // Abrir el dropdown clickeado si no estaba activo
                if (!isActive) {
                    header.classList.add('active');
                    content.style.display = 'block';
                }
            }

            // Event listeners para dropdowns
            constantDropdown.addEventListener('click', function() {
                toggleDropdown(constantDropdown, constantContent);
            });

            monthlyDropdown.addEventListener('click', function() {
                toggleDropdown(monthlyDropdown, monthlyContent);
            });

            // Cerrar dropdowns al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-container')) {
                    constantDropdown.classList.remove('active');
                    monthlyDropdown.classList.remove('active');
                    constantContent.style.display = 'none';
                    monthlyContent.style.display = 'none';
                }
            });

            // ==========================================
            // SELECTOR DE BASE DE DATOS Y MODAL
            // ==========================================
            const databaseItems = document.querySelectorAll('.database-item');
            const selectedDatabaseInput = document.getElementById('selectedDatabase');
            const submitBtn = document.getElementById('submitBtn');
            const fileInput = document.getElementById('fileInput');
            const uploadArea = document.getElementById('uploadArea');
            const uploadTitle = document.getElementById('uploadTitle');
            const uploadSubtitle = document.getElementById('uploadSubtitle');
            const progressContainer = document.getElementById('progressContainer');
            const logsContainer = document.getElementById('logsContainer');
            const logs = document.getElementById('logs');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const statusText = document.getElementById('statusText');
            const successMessage = document.getElementById('successMessage');

            // Elementos de información del modal
            const modalDatabaseIcon = document.getElementById('modalDatabaseIcon');
            const modalDatabaseName = document.getElementById('modalDatabaseName');
            const modalDatabaseDescription = document.getElementById('modalDatabaseDescription');
            const modalDatabaseFrequency = document.getElementById('modalDatabaseFrequency');

            // Definir los tipos de archivo por base de datos
            const fileTypes = {
                semilla_7k: { accept: '.xlsx, .xls', message: 'Archivo Excel (.xlsx, .xls)' },
                semilla_odh: { accept: '.xlsx, .xls', message: 'Archivo Excel (.xlsx, .xls)' },
                default: { accept: '.csv', message: 'Archivo CSV (.csv)' }
            };

            function updateUploadArea(databaseValue) {
                const fileType = fileTypes[databaseValue] || fileTypes.default;
                fileInput.setAttribute('accept', fileType.accept);
                uploadTitle.textContent = `Arrastra y suelta tu ${fileType.message} aquí`;
                uploadSubtitle.textContent = `o haz clic para seleccionar un ${fileType.message}`;
            }

            // Función para abrir el modal
            function openModal(databaseItem) {
                const databaseValue = databaseItem.getAttribute('data-value');
                const databaseName = databaseItem.getAttribute('data-name');
                const databaseDescription = databaseItem.getAttribute('data-description');
                const databaseIcon = databaseItem.getAttribute('data-icon');
                const frequencyBadge = databaseItem.querySelector('.frequency-badge').cloneNode(true);
                
                // Actualizar información en el modal
                modalDatabaseIcon.className = `bx ${databaseIcon}`;
                modalDatabaseName.textContent = databaseName;
                modalDatabaseDescription.textContent = databaseDescription;
                modalDatabaseFrequency.innerHTML = '';
                modalDatabaseFrequency.appendChild(frequencyBadge);
                
                // Actualizar formulario
                selectedDatabaseInput.value = databaseValue;
                updateUploadArea(databaseValue);
                
                // Reiniciar estado del modal
                resetModalState();
                
                // Mostrar modal
                updateModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Cerrar dropdowns
                constantDropdown.classList.remove('active');
                monthlyDropdown.classList.remove('active');
                constantContent.style.display = 'none';
                monthlyContent.style.display = 'none';
            }

            // Función para cerrar el modal
            function closeModalFunc() {
                updateModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                resetModalState();
            }

            // Función para reiniciar el estado del modal
            function resetModalState() {
                // Resetear formulario
                document.getElementById('uploadForm').reset();
                document.getElementById('fileInfo').style.display = 'none';
                
                // Resetear indicadores de progreso
                progressContainer.style.display = 'none';
                logsContainer.style.display = 'none';
                logs.innerHTML = '';
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                statusText.textContent = 'Preparando actualización...';
                submitBtn.disabled = true;
                
                // Ocultar mensaje de éxito
                successMessage.style.display = 'none';
                
                // Mostrar formulario
                document.getElementById('uploadForm').style.display = 'block';
            }

            // Event listeners para cerrar modal
            closeModal.addEventListener('click', closeModalFunc);
            cancelBtn.addEventListener('click', closeModalFunc);
            closeSuccessBtn.addEventListener('click', closeModalFunc);

            // Cerrar modal al hacer clic fuera del contenido
            updateModal.addEventListener('click', function(e) {
                if (e.target === updateModal) {
                    closeModalFunc();
                }
            });

            // Selección de base de datos
            databaseItems.forEach(item => {
                item.addEventListener('click', function() {
                    openModal(this);
                });
            });

            // ==========================================
            // UPLOAD FUNCTIONALITY
            // ==========================================
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileType = document.getElementById('fileType');
            const uploadForm = document.getElementById('uploadForm');
            
            // Drag and drop functionality
            uploadArea.addEventListener('click', () => fileInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileInfo();
                }
            });
            
            fileInput.addEventListener('change', updateFileInfo);
            
            function updateFileInfo() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    fileName.textContent = file.name;
                    fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    
                    // Mostrar el tipo de archivo correctamente
                    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                        fileType.textContent = 'Excel';
                    } else if (file.name.endsWith('.csv')) {
                        fileType.textContent = 'CSV';
                    } else {
                        fileType.textContent = file.type || 'Archivo';
                    }
                    
                    fileInfo.style.display = 'block';
                    submitBtn.disabled = false;
                } else {
                    fileInfo.style.display = 'none';
                    submitBtn.disabled = true;
                }
            }
            
            // Form submission
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!selectedDatabaseInput.value) {
                    alert('Por favor selecciona una base de datos');
                    return;
                }
                
                if (!fileInput.files.length) {
                    alert('Por favor selecciona un archivo');
                    return;
                }

                // Validar tipo de archivo según la base seleccionada
                const selectedDatabase = selectedDatabaseInput.value;
                const file = fileInput.files[0];
                const fileName = file.name.toLowerCase();

                if (selectedDatabase === 'semilla_7k' || selectedDatabase === 'semilla_odh') {
                    // Para semillas, permitir solo Excel
                    if (!fileName.endsWith('.xlsx') && !fileName.endsWith('.xls')) {
                        alert('Para las bases Semilla, solo se permiten archivos Excel (.xlsx, .xls)');
                        return;
                    }
                } else {
                    // Para otras bases, permitir solo CSV
                    if (!fileName.endsWith('.csv')) {
                        alert('Para esta base de datos, solo se permiten archivos CSV (.csv)');
                        return;
                    }
                }
                
                // Reset UI
                progressContainer.style.display = 'block';
                logsContainer.style.display = 'block';
                logs.innerHTML = '';
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                statusText.textContent = 'Iniciando proceso...';
                submitBtn.disabled = true;
                
                try {
                    const formData = new FormData(uploadForm);
                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        
                        const chunk = decoder.decode(value);
                        const lines = chunk.split('\n');
                        
                        for (const line of lines) {
                            if (line.trim()) {
                                try {
                                    const data = JSON.parse(line);
                                    handleProgressUpdate(data);
                                } catch (e) {
                                    console.log('Non-JSON line:', line);
                                }
                            }
                        }
                    }
                    
                } catch (error) {
                    addLog(`❌ Error: ${error.message}`, 'error');
                    statusText.textContent = 'Error en el proceso';
                    submitBtn.disabled = false;
                }
            });
            
            function handleProgressUpdate(data) {
                if (data.type === 'progress') {
                    progressBar.style.width = data.percent + '%';
                    progressPercent.textContent = data.percent + '%';
                    statusText.textContent = data.message;
                } else if (data.type === 'log') {
                    addLog(data.message, data.level);
                } else if (data.type === 'complete') {
                    addLog('✅ Proceso completado exitosamente', 'success');
                    statusText.textContent = 'Actualización completada';
                    
                    // Mostrar mensaje de éxito
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        logsContainer.style.display = 'none';
                        uploadForm.style.display = 'none';
                        successMessage.style.display = 'block';
                    }, 1000);
                } else if (data.type === 'error') {
                    addLog(`❌ ${data.message}`, 'error');
                    statusText.textContent = 'Error en el proceso';
                    submitBtn.disabled = false;
                }
            }
            
            function addLog(message, level = 'info') {
                const logEntry = document.createElement('div');
                logEntry.className = `log-entry ${level}`;
                
                let icon = 'bx-info-circle';
                if (level === 'success') icon = 'bx-check-circle';
                if (level === 'error') icon = 'bx-error-circle';
                if (level === 'warning') icon = 'bx-error';
                
                logEntry.innerHTML = `<i class='bx ${icon}'></i> ${message}`;
                logs.appendChild(logEntry);
                logs.scrollTop = logs.scrollHeight;
            }
        });
    </script>
</body>
</html>