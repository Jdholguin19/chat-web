<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
session_start();

// Verificar si el usuario está autenticado y es un responsable
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'responsable') {
    header("Location: ../login.php");
    exit;
}

// Conectar a la base de datos
$conn = connectDB();

// Obtener el chat_id de la URL
$chat_id = isset($_GET['chat_id']) ? intval($_GET['chat_id']) : 0;

// Verificar que el chat_id sea válido
if ($chat_id <= 0) {
    echo "Chat no válido.";
    exit;
}

// Obtener los mensajes del chat y el cliente_id
$stmt = $conn->prepare("SELECT contenido, fecha, remitente, c.cliente_id FROM mensajes m JOIN chats c ON m.chat_id = c.id WHERE m.chat_id = ? ORDER BY m.fecha ASC");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$mensajes = $result->fetch_all(MYSQLI_ASSOC);

// Obtener el cliente_id
$cliente_id = $mensajes[0]['cliente_id'] ?? null; // Obtener el cliente_id del primer mensaje

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <a href="../logout.php">Cerrar Sesión</a>
    <title>Chat Responsable</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        #chat-container {
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }
        #messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        #message-input {
            display: flex;
        }
        #message {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        #send-button {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #send-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div id="chat-container">
    <h2>Chat con Cliente</h2>
    <div id="messages">
        <?php foreach ($mensajes as $mensaje): ?>
            <div><strong><?= htmlspecialchars($mensaje['remitente'] === 'responsable' ? 'Responsable' : 'Cliente') ?>:</strong> <?= htmlspecialchars($mensaje['contenido']) ?> <em>(<?= $mensaje['fecha'] ?>)</em></div>
        <?php endforeach; ?>
    </div>
    <div id="message-input">
        <input type="text" id="message" placeholder="Escribe tu mensaje..." required>
        <button id="send-button"><i class="fas fa-paper-plane"></i> Enviar</button>
    </div>
</div>

<script>
    // Función para cargar mensajes
    function loadMessages() {
        fetch('http://localhost/chat-web/api/get_messages.php?chat_id=<?= json_encode($chat_id) ?>')
            .then(response => response.json())
            .then(data => {
                const messagesContainer = document.getElementById('messages');
                messagesContainer.innerHTML = ''; // Limpiar mensajes anteriores
                data.mensajes.forEach(mensaje => {
                    messagesContainer.innerHTML += `<div><strong>${mensaje.remitente === 'responsable' ? 'Responsable' : 'Cliente'}:</strong> ${mensaje.contenido} <em>(${mensaje.fecha})</em></div>`;
                });
                messagesContainer.scrollTop = messagesContainer.scrollHeight; // Desplazar hacia abajo
            })
            .catch(error => console.error('Error al cargar mensajes:', error));
    }

    // Llamar a loadMessages cada 2 segundos
    setInterval(loadMessages, 2000);

    document.getElementById('send-button').addEventListener('click', function() {
        const messageInput = document.getElementById('message');
        const message = messageInput.value;

        if (message.trim() === '') {
            alert('Por favor, escribe un mensaje.');
            return;
        }

        // Enviar el mensaje al servidor
        fetch('http://localhost/chat-web/api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                chat_id: <?= json_encode($chat_id) ?>, // Enviar el ID del chat
                mensaje: message,
                cliente_id: <?= json_encode($cliente_id) ?> // Enviar el ID del cliente
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                // Mostrar el mensaje enviado en el feed
                const messagesDiv = document.getElementById('messages');
                messagesDiv.innerHTML += `<div><strong>Responsable:</strong> ${message} <em>(Ahora)</em></div>`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight; // Desplazar hacia abajo
                messageInput.value = ''; // Limpiar el campo de entrada
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un problema al enviar el mensaje.');
        });

    });
</script>


</body>
</html>
