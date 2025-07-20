<?php
require_once '../config.php';
header('Content-Type: application/json');
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Conectar a la base de datos
$conn = connectDB();

// Obtener el chat_id de la solicitud
$chat_id = isset($_GET['chat_id']) ? intval($_GET['chat_id']) : 0;

// Verificar que el chat_id sea válido
if ($chat_id <= 0) {
    echo json_encode(['error' => 'Chat no válido.']);
    exit;
}

// Obtener los mensajes del chat
$stmt = $conn->prepare("SELECT contenido, fecha, remitente FROM mensajes WHERE chat_id = ? ORDER BY fecha ASC");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$mensajes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Devolver los mensajes en formato JSON
echo json_encode(['mensajes' => $mensajes]);
?>
