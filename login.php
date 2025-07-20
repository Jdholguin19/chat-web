<?php
session_start();
require_once 'config.php'; // Asegúrate de incluir tu archivo de configuración

// Verificar si el usuario ya está autenticado
if (isset($_SESSION['user_id'])) {
    // Redirigir según el rol
    if ($_SESSION['role'] === 'cliente') {
        header("Location: cliente/chat.php");
    } elseif ($_SESSION['role'] === 'responsable') {
        header("Location: responsable/panel.php"); // Cambia esto a la ruta correcta para responsables
    }
    exit;
}

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Conectar a la base de datos
    $conn = connectDB();

    // Verificar las credenciales del usuario
    $stmt = $conn->prepare("SELECT id, pass, role FROM users WHERE email = ?"); // Obtener el rol
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['pass'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role']; // Almacenar el rol en la sesión

            // Registrar el acceso
            registerAccess($conn, $row['id']);

            // Redirigir al panel del usuario según su rol
            if ($row['role'] === 'cliente') {
                header("Location: cliente/chat.php");
            } elseif ($row['role'] === 'responsable') {
                header("Location: responsable/panel.php"); // Cambia esto a la ruta correcta para responsables
            }
            exit;
        } else {
            $error_message = "Contraseña incorrecta.";
        }
    } else {
        $error_message = "Usuario no encontrado.";
    }

    $stmt->close();
    $conn->close();
}

// Función para registrar el acceso del usuario
function registerAccess($conn, $user_id) {
    $stmt = $conn->prepare("INSERT INTO accesos (user_id, fecha, ip) VALUES (?, NOW(), ?)");
    $ip_address = $_SERVER['REMOTE_ADDR']; // Obtener la dirección IP del usuario
    $stmt->bind_param("is", $user_id, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        #login-form {
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 400px;
            margin: auto;
        }
        h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .error {
            color: red;
            text-align: center;
        }
        #login-button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #login-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div id="login-form">
    <h2>Iniciar Sesión</h2>
    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" id="login-button">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>
