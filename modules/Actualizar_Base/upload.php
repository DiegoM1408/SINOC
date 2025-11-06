<?php
// upload.php
require_once 'config.php';

// Configurar para output en tiempo real
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Función para enviar mensajes en formato JSON
function sendMessage($type, $message, $level = 'info', $percent = null) {
    $data = ['type' => $type, 'message' => $message, 'level' => $level];
    if ($percent !== null) $data['percent'] = $percent;
    echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    ob_flush();
    flush();
}

// Función para limpiar archivos antiguos
function cleanupOldFiles($uploadDir, $maxAgeHours = 24) {
    $files = glob($uploadDir . '*.csv');
    $now = time();
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            // Eliminar archivos más antiguos que maxAgeHours
            if (($now - filemtime($file)) > ($maxAgeHours * 3600)) {
                unlink($file);
                $deleted++;
            }
        }
    }
    
    return $deleted;
}

// Función para ejecutar comando Node.js con output en tiempo real
function executeNodeScript($scriptPath, $csvFile) {
    $command = 'node "' . $scriptPath . '" "' . $csvFile . '" 2>&1';
    $output = [];
    $returnCode = 0;
    
    sendMessage('log', "Ejecutando: $command", 'info');
    
    // Ejecutar el comando
    exec($command, $output, $returnCode);
    
    // Procesar output
    foreach ($output as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Detectar tipo de mensaje por emojis/patrones
            if (strpos($line, '❌') !== false || strpos($line, '💥') !== false || strpos($line, '⚠️') !== false) {
                sendMessage('log', $line, 'error');
            } else if (strpos($line, '✅') !== false) {
                sendMessage('log', $line, 'success');
            } else if (strpos($line, '🔄') !== false) {
                sendMessage('log', $line, 'warning');
            } else if (preg_match('/Progreso:\s*\d+\/\d+\s*\((\d+)%\)/', $line, $matches)) {
                sendMessage('progress', $line, 'info', (int)$matches[1]);
            } else {
                sendMessage('log', $line, 'info');
            }
        }
    }
    
    return $returnCode;
}

try {
    // Verificar que es una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Solo se aceptan solicitudes POST.');
    }
    
    // Verificar base de datos seleccionada
    if (!isset($_POST['database']) || !array_key_exists($_POST['database'], Config::DATABASES)) {
        throw new Exception('Base de datos no válida o no seleccionada.');
    }
    
    $dbConfig = Config::DATABASES[$_POST['database']];
    
    // Verificar archivo subido
    if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Error al subir el archivo. ';
        switch ($_FILES['csvFile']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg .= 'El archivo es demasiado grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg .= 'El archivo se subió parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg .= 'No se seleccionó ningún archivo.';
                break;
            default:
                $errorMsg .= 'Error desconocido (Código: ' . $_FILES['csvFile']['error'] . ')';
        }
        throw new Exception($errorMsg);
    }
    
    $uploadedFile = $_FILES['csvFile'];
    
    // Validar tipo de archivo
    $fileType = mime_content_type($uploadedFile['tmp_name']);
    $allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv', 'text/x-csv'];
    
    if (!in_array($fileType, $allowedTypes) && pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'csv') {
        throw new Exception('Solo se permiten archivos CSV. Tipo detectado: ' . $fileType);
    }
    
    // Validar tamaño del archivo (límite: 100MB)
    $maxFileSize = 100 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxFileSize) {
        throw new Exception('El archivo es demasiado grande. Tamaño máximo permitido: 100MB');
    }
    
    if ($uploadedFile['size'] == 0) {
        throw new Exception('El archivo está vacío.');
    }
    
    // Crear directorios si no existen
    if (!is_dir(Config::getUploadPath())) {
        if (!mkdir(Config::getUploadPath(), 0777, true)) {
            throw new Exception('No se pudo crear el directorio de uploads.');
        }
    }
    
    if (!is_dir(Config::getLogPath())) {
        if (!mkdir(Config::getLogPath(), 0777, true)) {
            throw new Exception('No se pudo crear el directorio de logs.');
        }
    }
    
    sendMessage('progress', 'Iniciando proceso de actualización...', 'info', 5);
    
    // Limpiar archivos antiguos (más de 24 horas)
    $cleaned = cleanupOldFiles(Config::getUploadPath());
    if ($cleaned > 0) {
        sendMessage('log', "Se limpiaron $cleaned archivos temporales antiguos", 'info');
    }
    
    sendMessage('progress', 'Procesando archivo subido...', 'info', 10);
    
    // Generar nombre único para el archivo
    $filename = $dbConfig['name'] . '_' . date('Y-m-d_His') . '.csv';
    $filepath = Config::getUploadPath() . $filename;
    
    // Mover archivo subido
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
        throw new Exception('No se pudo guardar el archivo en el servidor.');
    }
    
    sendMessage('log', "✅ Archivo guardado: " . $uploadedFile['name'], 'success');
    sendMessage('log', "📊 Tamaño: " . round($uploadedFile['size'] / 1024 / 1024, 2) . " MB", 'info');
    sendMessage('progress', 'Archivo preparado correctamente', 'info', 20);
    
    // Verificar que el script existe
    $scriptPath = Config::BASE_DIR . $dbConfig['script'];
    if (!file_exists($scriptPath)) {
        throw new Exception('Script de importación no encontrado: ' . $dbConfig['script']);
    }
    
    sendMessage('progress', 'Ejecutando importación...', 'info', 30);
    
    // Ejecutar script Node.js
    $returnCode = executeNodeScript($scriptPath, $filepath);
    
    if ($returnCode !== 0) {
        throw new Exception('El script de importación falló con código: ' . $returnCode);
    }
    
    sendMessage('progress', 'Realizando limpieza final...', 'info', 90);
    
    // Limpiar archivo temporal
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            sendMessage('log', '✅ Archivo temporal eliminado', 'success');
        } else {
            sendMessage('log', '⚠️ No se pudo eliminar el archivo temporal', 'warning');
        }
    }
    
    sendMessage('progress', 'Proceso completado exitosamente', 'success', 100);
    sendMessage('complete', 'Base de datos "' . $dbConfig['description'] . '" actualizada correctamente');
    
} catch (Exception $e) {
    sendMessage('error', $e->getMessage());
    
    // Limpiar archivo en caso de error
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Log del error
    $logFile = Config::getLogPath() . 'error_' . date('Y-m-d') . '.log';
    $errorLog = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorLog, FILE_APPEND | LOCK_EX);
}
?>