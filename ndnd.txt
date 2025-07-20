CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(80),
    email VARCHAR(100) UNIQUE,
    pass CHAR(60),
    role ENUM('cliente','responsable')
);

CREATE TABLE contactos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    producto VARCHAR(80)
);

CREATE TABLE responsables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE
);

CREATE TABLE asignaciones (
    cliente_id INT PRIMARY KEY,
    responsable_id INT,
    fecha DATETIME
);

CREATE TABLE chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT,
    responsable_id INT,
    abierto BOOL DEFAULT 1
);

CREATE TABLE mensajes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT,
    remitente ENUM('cliente','bot','resp'),
    contenido TEXT,
    fecha DATETIME
);

CREATE TABLE accesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    fecha DATETIME,
    ip VARCHAR(45)
);

CREATE TABLE mensajes_pred (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('cliente','bot'),
    texto TEXT
);


INSERT INTO users (nombre, email, pass, role) VALUES
('Cliente 1', 'cliente1@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'cliente'),
('Cliente 2', 'cliente2@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'cliente'),
('Cliente 3', 'cliente3@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'cliente');


INSERT INTO users (nombre, email, pass, role) VALUES
('Responsable 1', 'responsable1@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'responsable'),
('Responsable 2', 'responsable2@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'responsable'),
('Responsable 3', 'responsable3@costasol.com', '$2y$10$BYS1ixY9lV/w.gMOJH5r1OQxqwUnwPebq.6L3VepT0ZlY6nDTmCmC', 'responsable');

-- Insertar Mensajes Predeterminados (5 Mensajes de Clientes y 5 Respuestas)
INSERT INTO mensajes_pred (tipo, texto, palabras_clave) VALUES
('bot', 'Hola, ¿en qué puedo ayudarte hoy?', 'hola, ayuda, pedido'),
('bot', 'Estoy aquí para resolver tus dudas.', 'dudas, problema'),
('bot', 'Por favor, proporciona más detalles sobre tu consulta.', 'detalles, consulta'),
('bot', 'Gracias por tu mensaje, un agente se comunicará contigo pronto.', 'agente, contacto'),
('bot', '¿Hay algo más en lo que pueda ayudarte?', 'más, ayuda'),
('bot', 'Para empezar, ¿podrías indicarme tu número de pedido o referencia?', 'pedido, referencia, número'),
('bot', 'Entendido. Estoy buscando información al respecto.', 'buscar, información, entendí'),
('bot', 'Parece que ha habido un error. Por favor, inténtalo de nuevo.', 'error, reintentar, problema'),
('bot', 'Para una atención más personalizada, ¿deseas hablar con un asesor?', 'asesor, hablar, personal'),
('bot', 'Tu satisfacción es nuestra prioridad. ¿Cómo evalúas mi asistencia hasta ahora?', 'satisfacción, evaluar, asistencia');