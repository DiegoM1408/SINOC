<?php
session_start();
include("includes/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE username='$username' AND estado='Activo' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $role_id = $row['role'] ?? 3; // Default a Técnico si no tiene role
            
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['nombre']     = $row['nombre'];
            $_SESSION['role']       = $role_id;
            $_SESSION['nombre_rol'] = $roles_permisos[$role_id]['nombre'];
            $_SESSION['permisos']   = $roles_permisos[$role_id]['permisos'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado o inactivo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Claro</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .col {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .left-panel {
            background: linear-gradient(135deg, #E10000 0%, #CC0000 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .right-panel {
            background: #ffffff;
        }

        .welcome-content {
            max-width: 400px;
        }

        .claro-logo-img {
            max-width: 200px;
            margin-bottom: 2rem;
        }

        .ntt-logo-img {
            max-width: 150px;
            margin-top: 2rem;
        }

        .welcome-content h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #E10000;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.2rem;
            z-index: 2;
        }

        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa; /* Fondo gris claro */
        }

        .input-group input:focus {
            outline: none;
            border-color: #E10000;
            box-shadow: 0 0 0 2px rgba(225, 0, 0, 0.1);
            background-color: #ffffff; /* Fondo blanco al hacer focus */
        }

        button {
            width: 100%;
            padding: 1rem;
            background: #E10000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #CC0000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(225, 0, 0, 0.2);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 0.75rem;
            border-radius: 6px;
            margin: 1rem 0;
            text-align: center;
            font-size: 0.9rem;
        }

        .form p {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }

        .pointer {
            cursor: pointer;
            color: #E10000;
            font-weight: 600;
        }

        .pointer:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel {
                padding: 3rem 2rem;
            }
            
            .welcome-content {
                max-width: 100%;
            }
            
            .form-wrapper {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Panel izquierdo con mensaje de bienvenida -->
    <div class="col left-panel">        
        <div class="welcome-content">
            <img src="assets/images/claro-logo2.png" alt="Logo Claro" class="claro-logo-img">
            <h2>Bienvenido</h2>
            <p>Sistema de Aseguramiento y Cumplimiento <br> NOC Claro - MinTIC</p>
            <!-- Logo NTT DATA (desarrollador) -->
            <div class="ntt-logo">
                <img src="assets/images/logo_nttdata7.png" alt="Logo NTT DATA" class="ntt-logo-img">
            </div>
        </div>
    </div>
    
    <!-- Panel derecho con formulario de login -->
    <div class="col right-panel">
        <div class="form-wrapper">
            <form class="form" method="POST" action="">
                <div class="form-header">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>
                
                <div class="input-group">
                    <i class='bx bxs-user'></i>
                    <input type="text" name="username" placeholder="Usuario" required>
                </div>
                
                <div class="input-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                
                <button type="submit">Ingresar al Sistema</button>
                
                <?php if($error != "") echo "<div class='error-message'>$error</div>"; ?>
                
                <p>
                    <span>¿No tienes cuenta? </span>
                    <b onclick="toggleForm()" class="pointer">Regístrate aquí</b>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    window.location.href = 'register.php';
}
</script>
</body>
</html>