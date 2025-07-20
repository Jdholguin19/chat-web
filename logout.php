<?php
session_start(); // Iniciar la sesión

// Verificar si el usuario está autenticado
if (isset($_SESSION['user_id'])) {
    // Destruir la sesión
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
}

// Redirigir al usuario a la página de inicio de sesión
header("Location: login.php");
exit;
?>
