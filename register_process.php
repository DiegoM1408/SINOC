<?php
include("includes/db.php");

$nombre   = $_POST['nombre'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // 🔒 HASH
$estado   = "Activo"; // 👈 Siempre activo

$sql = "INSERT INTO usuarios (username, password, nombre, estado)
        VALUES ('$username', '$password', '$nombre', '$estado')";

if ($conn->query($sql) === TRUE) {
    echo "Usuario registrado con éxito. <a href='login.php'>Iniciar sesión</a>";
} else {
    echo "Error: " . $conn->error;
}
?>
