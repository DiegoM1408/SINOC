<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (isset($_POST['toggle_dark_mode'])) {
    $_SESSION['dark_mode'] = !($_SESSION['dark_mode'] ?? false);
    echo json_encode(['success' => true, 'dark_mode' => $_SESSION['dark_mode']]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request']);
