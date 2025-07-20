<?php
// Incluir el archivo de configuración para la conexión a la base de datos
require_once '../config.php';

// Manejar la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar que se recibieron los parámetros necesarios
    if (isset($input['mensaje']) && isset($input['cliente_id'])) {
        $mensaje = $input['mensaje'];
        $cliente_id = $input['cliente_id']; // Asegúrate de que 'cliente_id' se envíe en la solicitud

        // Conectar a la base de datos
        $conn = connectDB();

        // Registrar el acceso del cliente
        registerAccess($conn, $cliente_id);

        // Verificar si el cliente ya tiene un chat abierto
        $stmt = $conn->prepare("SELECT id FROM chats WHERE cliente_id = ? AND abierto = 1");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Si no hay chat abierto, asignar un responsable
        if ($result->num_rows === 0) {
            $responsable_id = assignResponsable($conn);
            if ($responsable_id === null) {
                echo json_encode(['error' => 'No hay responsables disponibles.']);
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
        } else {
            // Si ya hay un chat abierto, obtener el chat_id
            $row = $result->fetch_assoc();
            $chat_id = $row['id'];
        }

        // Guardar el mensaje del cliente en la base de datos
        $stmt = $conn->prepare("INSERT INTO mensajes (chat_id, remitente, contenido, fecha, leido) VALUES (?, 'cliente', ?, NOW(), 0)");
        $stmt->bind_param("is", $chat_id, $mensaje);
        $stmt->execute();

        // Obtener la respuesta del bot
        $respuesta_bot = getBotResponse($mensaje, $conn);

        // Guardar la respuesta del bot en la base de datos
        $stmt = $conn->prepare("INSERT INTO mensajes (chat_id, remitente, contenido, fecha, leido) VALUES (?, 'bot', ?, NOW(), 1)");
        $stmt->bind_param("is", $chat_id, $respuesta_bot);
        $stmt->execute();

        // Cerrar la conexión
        $stmt->close();
        $conn->close();

        // Responder con el mensaje guardado y la respuesta del bot
        echo json_encode([
            'mensaje_guardado' => $mensaje,
            'respuesta_bot' => $respuesta_bot,
            'chat_id' => $chat_id // Devolver el chat_id
        ]);
    } else {
        // Responder con un error si faltan parámetros
        echo json_encode(['error' => 'Faltan parámetros necesarios.']);
    }
} else {
    // Responder con un error si no es un método POST
    echo json_encode(['error' => 'Método no permitido.']);
}

// Función para obtener la respuesta del bot
function getBotResponse($mensaje, $conn) {
    // Lógica de respuesta: buscar palabras clave en la tabla mensajes_pred
    $stmt = $conn->prepare("SELECT texto, palabras_clave FROM mensajes_pred WHERE tipo = 'bot'");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Comprobar si alguna de las palabras clave está en el mensaje
        $palabras_clave = explode(',', $row['palabras_clave']);
        foreach ($palabras_clave as $palabra) {
            if (stripos($mensaje, trim($palabra)) !== false) {
                return $row['texto']; // Devolver la respuesta si se encuentra una palabra clave
            }
        }
    }

    return "Lo siento, no tengo una respuesta para eso."; // Respuesta por defecto
}


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

// Función para registrar el acceso del usuario
function registerAccess($conn, $user_id) {
    $stmt = $conn->prepare("INSERT INTO accesos (user_id, fecha, ip) VALUES (?, NOW(), ?)");
    $ip_address = $_SERVER['REMOTE_ADDR']; // Obtener la dirección IP del usuario
    $stmt->bind_param("is", $user_id, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>
