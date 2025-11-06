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
            max-width: 1000px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
            min-height: calc(100vh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
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
            padding: 2.5rem 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(225, 0, 0, 0.02);
            position: relative;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(225, 0, 0, 0.05);
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
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .upload-area p {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        /* ==================== BUTTON ==================== */
        .btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 1rem 1.75rem;
            font-size: 1rem;
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
            gap: 0.6rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(225, 0, 0, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background: var(--text-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .progress-percent {
            font-size: 0.9rem;
            font-weight: 600;
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
            padding: 1.5rem;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: none;
        }

        .logs-header {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .logs-header i {
            color: var(--primary-color);
        }

        .log-entry {
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--border-color);
            font-family: monospace;
            font-size: 0.85rem;
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
            margin-top: 1.25rem;
            padding: 1.25rem;
            background: rgba(16, 185, 129, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            display: none;
        }

        .file-info-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--success-color);
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }

        .file-detail {
            display: flex;
            flex-direction: column;
        }

        .file-label {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-bottom: 0.2rem;
        }

        .file-value {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1023px) {
            .content-area {
                margin-top: 60px;
                min-height: calc(100vh - 60px);
                padding-top: 1rem;
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
            
            .upload-area {
                padding: 2rem 1rem;
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

            .form-subtitle {
                font-size: 0.85rem;
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
            
            .upload-area {
                padding: 1.5rem 0.75rem;
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
        }

        /* Ajustes específicos para pantallas muy pequeñas */
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
            
            .form-title {
                font-size: 1.1rem;
            }
            
            .upload-area {
                padding: 1.5rem 0.5rem;
            }
        }

        /* Asegurar que el contenido sea scrollable en móvil */
        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }
            
            .content-wrapper {
                padding-bottom: 1rem;
            }
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
                <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">
                        <i class='bx bx-refresh'></i>
                        Actualización de Bases de Datos
                    </h2>
                    <p class="form-subtitle">Sistema para actualizar bases de datos desde archivos CSV</p>
                </div>
                
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="database">Seleccionar Base de Datos:</label>
                        <select id="database" name="database" required>
                            <option value="">-- Seleccione una base --</option>
                            <option value="incidentes">Base de Incidentes para MinTIC-7K (Service Manager)</option>
                            <option value="reportes">Base de Reportes para MinTIC-7K (Portal de Reportes)</option>
                            <option value="incidentes_odh">Base de Incidentes ODH (Service Manager ODH)</option>
                            <option value="reportes_odh">Base de Reportes ODH (Portal de Reportes ODH)</option>
                            <option value="sds_7k">Base de SDs para MinTIC-7K(Service Manager SDs)</option>
                            <option value="sds_odh">Base de SDs para MinTIC-ODH (Service Manager SDs)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="csvFile">Archivo CSV:</label>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">📁</div>
                            <h3>Arrastra y suelta tu archivo CSV aquí</h3>
                            <p>o haz clic para seleccionar</p>
                            <input type="file" id="csvFile" name="csvFile" accept=".csv" required style="display: none;">
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
                    
                    <button type="submit" class="btn" id="submitBtn">
                        <i class='bx bx-cloud-upload'></i>
                        Iniciar Actualización
                    </button>
                </form>
                
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
                
                <div class="logs" id="logsContainer">
                    <div class="logs-header">
                        <i class='bx bx-list-check'></i>
                        Registro de Proceso
                    </div>
                    <div id="logs"></div>
                </div>
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
        // UPLOAD FUNCTIONALITY
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('csvFile');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileType = document.getElementById('fileType');
            const uploadForm = document.getElementById('uploadForm');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const statusText = document.getElementById('statusText');
            const logsContainer = document.getElementById('logsContainer');
            const logs = document.getElementById('logs');
            const submitBtn = document.getElementById('submitBtn');
            
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
                    fileType.textContent = file.type || 'CSV';
                    fileInfo.style.display = 'block';
                } else {
                    fileInfo.style.display = 'none';
                }
            }
            
            // Form submission
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(uploadForm);
                const database = document.getElementById('database').value;
                
                if (!database) {
                    alert('Por favor selecciona una base de datos');
                    return;
                }
                
                if (!fileInput.files.length) {
                    alert('Por favor selecciona un archivo CSV');
                    return;
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
                    submitBtn.disabled = false;
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