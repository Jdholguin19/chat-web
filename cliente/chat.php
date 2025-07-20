<?php
// Incluir el archivo de configuración para la conexión a la base de datos
require_once '../config.php';
session_start(); // Asegúrate de iniciar la sesión

// Conectar a la base de datos
$conn = connectDB();

// Obtener el ID del cliente desde la sesión
$cliente_id = $_SESSION['user_id'];

// Verificar si el cliente ya tiene un chat abierto
$stmt = $conn->prepare("SELECT id FROM chats WHERE cliente_id = ? AND abierto = 1");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Si hay un chat abierto, obtener el chat_id
    $row = $result->fetch_assoc();
    $chat_id = $row['id'];
} else {
    // Si no hay chat abierto, asignar un responsable y crear un nuevo chat
    $responsable_id = assignResponsable($conn);
    if ($responsable_id === null) {
        echo "No hay responsables disponibles.";
        exit;
    }

    // Crear un nuevo registro en la tabla chats
    $stmt = $conn->prepare("INSERT INTO chats (cliente_id, responsable_id, abierto) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $cliente_id, $responsable_id);
    $stmt->execute();
    $chat_id = $stmt->insert_id; // Obtener el ID del nuevo chat
    $stmt->close();

    // Crear un registro en la tabla asignaciones
    $stmt = $conn->prepare("INSERT INTO asignaciones (cliente_id, responsable_id, fecha) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $cliente_id, $responsable_id);
    $stmt->execute();
    $stmt->close();
}

// Obtener mensajes anteriores del chat
$stmt = $conn->prepare("SELECT contenido, fecha FROM mensajes WHERE chat_id = ? ORDER BY fecha ASC");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$mensajes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Función para asignar un responsable con menos chats activos
function assignResponsable($conn) {
    // Seleccionar al responsable con menos chats activos
    $stmt = $conn->prepare("SELECT r.user_id FROM responsables r
                             LEFT JOIN chats c ON r.user_id = c.responsable_id AND c.abierto = 1
                             GROUP BY r.user_id
                             ORDER BY COUNT(c.id) ASC
                             LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['user_id']; // Devolver el ID del responsable
    }

    return null; // No hay responsables disponibles
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <a href="../logout.php">Cerrar Sesión</a>
    <title>Chat Cliente</title>
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
    <a href="../logout.php">Cerrar Sesión</a>
    <h2>Chat con Soporte</h2>
    <div id="messages">
        <?php foreach ($mensajes as $mensaje): ?>
            <div><strong>Cliente:</strong> <?= htmlspecialchars($mensaje['contenido']) ?> <em>(<?= $mensaje['fecha'] ?>)</em></div>
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
                    messagesContainer.innerHTML += `<div><strong>Cliente:</strong> ${mensaje.contenido} <em>(${mensaje.fecha})</em></div>`;
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
                mensaje: message,
                cliente_id: <?= json_encode($_SESSION['user_id']) ?> // Enviar el ID del cliente
            })
        })
        .then(response => response.json())
        .then(data => {
            // Mostrar el mensaje enviado en el feed
            const messagesDiv = document.getElementById('messages');
            messagesDiv.innerHTML += `<div><strong>Cliente:</strong> ${message} <em>(Ahora)</em></div>`;
            messagesDiv.innerHTML += `<div><strong>Bot:</strong> ${data.respuesta_bot} <em>(Ahora)</em></div>`;
            messagesDiv.scrollTop = messagesDiv.scrollHeight; // Desplazar hacia abajo
            messageInput.value = ''; // Limpiar el campo de entrada
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un problema al enviar el mensaje.');
        });
    });
</script>

</body>
</html>
