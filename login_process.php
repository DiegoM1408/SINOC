<?php
session_start();
include("includes/db.php");

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios WHERE username='$username' AND estado='Activo' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['username']   = $row['username'];
        $_SESSION['nombre']     = $row['nombre'];

        header("Location: index.php");
        exit;
    } else {
        echo "Contraseña incorrecta.";
    }
} else {
    echo "Usuario no encontrado o inactivo.";
}
?>
