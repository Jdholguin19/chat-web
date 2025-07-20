
<?php
// Nueva contraseña para cada cliente y responsable
$clientes = [
    'cliente1@costasol.com' => 'hola123',
    'cliente2@costasol.com' => 'hola123',
    'cliente3@costasol.com' => 'hola123',
];

$responsables = [
    'responsable1@costasol.com' => 'hola123',
    'responsable2@costasol.com' => 'hola123',
    'responsable3@costasol.com' => 'hola123',
];

// Generar y mostrar los hashes
foreach ($clientes as $email => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "Email: $email, Nueva Contraseña: $password, Hash: $hash\n";
}

foreach ($responsables as $email => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "Email: $email, Nueva Contraseña: $password, Hash: $hash\n";
}
?>
