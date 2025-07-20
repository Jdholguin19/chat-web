<?php
require_once 'config.php'; // Script para asignar responsables

// Conectar a la base de datos
$conn = connectDB();

// Obtener todos los usuarios con el rol de 'responsable'
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'responsable'");
$stmt->execute();
$result = $stmt->get_result();

// Insertar cada responsable en la tabla responsables
while ($row = $result->fetch_assoc()) {
    $responsable_id = $row['id'];
    $insert_stmt = $conn->prepare("INSERT INTO responsables (user_id) VALUES (?)");
    $insert_stmt->bind_param("i", $responsable_id);
    $insert_stmt->execute();
}

$stmt->close();
$conn->close();

echo "Responsables insertados correctamente.";
?>
