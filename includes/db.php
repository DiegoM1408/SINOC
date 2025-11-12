<?php
//Conexión base usuarios
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "usuarios_log_sistemanoc";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Definir permisos por rol
$roles_permisos = [
    1 => [ // Admin
        'nombre' => 'Admin',
        'permisos' => [
            'configuracion' => ['read', 'write', 'delete'],
            'auditoria' => ['read', 'write', 'delete'],
            'usuarios' => ['read', 'write', 'delete']
        ]
    ],
    2 => [ // Gerencia
        'nombre' => 'Gerencia',
        'permisos' => [
            'configuracion' => ['read', 'write'],
            'auditoria' => ['read', 'write'],
            'usuarios' => ['read']
        ]
    ],
    3 => [ // Técnico
        'nombre' => 'Tecnico',
        'permisos' => [
            'configuracion' => ['read', 'write'],
            'auditoria' => ['read'],
            'usuarios' => []
        ]
    ]
];

// Asegurar que la columna 'role' existe en la tabla usuarios
$check_column = "SHOW COLUMNS FROM usuarios LIKE 'role'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Agregar columna role si no existe
    $add_column = "ALTER TABLE usuarios ADD COLUMN role INT DEFAULT 3";
    $conn->query($add_column);
    
    // Asignar rol de admin al primer usuario
    $set_admin = "UPDATE usuarios SET role = 1 WHERE id_usuario = 1";
    $conn->query($set_admin);
}

// Función para verificar permisos
function tienePermiso($modulo, $accion) {
    global $roles_permisos;
    
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $role_id = $_SESSION['role'];
    
    if ($role_id == 1) { // Admin tiene todos los permisos
        return true;
    }
    
    if (!isset($roles_permisos[$role_id]['permisos'][$modulo])) {
        return false;
    }
    
    return in_array($accion, $roles_permisos[$role_id]['permisos'][$modulo]);
}

//Conexión base de auditoría
$ic_servername = $servername;  // reutiliza tus credenciales actuales
$ic_username   = $username;
$ic_password   = $password;
$ic_dbname     = "incidentes_csv";


$conn_ic = @new mysqli($ic_servername, $ic_username, $ic_password, $ic_dbname);

if ($conn_ic && !$conn_ic->connect_error) {
    $conn_ic->set_charset("utf8mb4");
}

?>


