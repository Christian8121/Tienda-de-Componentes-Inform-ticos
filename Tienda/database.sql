CREATE USER 'severino'@'%' IDENTIFIED BY 'severino';

-- Crear base de datos con codificación UTF-8
CREATE DATABASE IF NOT EXISTS tienda_informatica 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_spanish_ci;

USE tienda_informatica;

-- Asignar privilegios al usuario
GRANT ALL PRIVILEGES ON tienda_informatica.* TO 'severino'@'%';
FLUSH PRIVILEGES;

-- Establecer codificación por defecto para la sesión
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion_corta VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) DEFAULT 'default.jpg',
    categoria VARCHAR(100) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla carrito
CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY carrito_user_product (usuario_id, producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    direccion_envio TEXT NOT NULL,
    metodo_pago VARCHAR(100) NOT NULL,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla historial_estados_pedido
CREATE TABLE IF NOT EXISTS historial_estados_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') NOT NULL,
    comentario TEXT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla detalle_pedido
CREATE TABLE IF NOT EXISTS detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo VARCHAR(50) DEFAULT 'general',
    leida TINYINT(1) DEFAULT 0,
    enlace VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar usuarios por defecto (Contraseña: admin123 - cifrada con password_hash)
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Administrador', 'admin@sevestore.com', 'admin123', 'admin');

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, descripcion_corta, descripcion, precio, stock, categoria, imagen) VALUES 
('Portátil Acer Aspire 3', 'Portátil con procesador i5, 8GB RAM y 256GB SSD', 'El Acer Aspire 3 es un portátil potente y económico ideal para el día a día. Cuenta con un procesador Intel Core i5, 8GB de memoria RAM y un disco SSD de 256GB para un rendimiento óptimo.', 599.99, 10, 'Portátiles', 'acer_aspire3.png'),
('Monitor HP 24fw', 'Monitor Full HD IPS de 24 pulgadas', 'El HP 24fw es un monitor con resolución Full HD (1920x1080) y tecnología IPS que ofrece ángulos de visión de 178° y colores precisos. Diseño ultrafino con bordes reducidos.', 159.99, 15, 'Monitores', '4TB29AA-1_T1679069285.avif'),
('Teclado Logitech K380', 'Teclado bluetooth multidispositivo', 'Teclado inalámbrico Bluetooth que se conecta a cualquier dispositivo compatible. Ideal para trabajar con ordenadores, tablets y smartphones. Tamaño compacto y batería de larga duración.', 39.99, 20, 'Periféricos', 'logitech_k380.jpg'),
('Ratón Gaming Razer DeathAdder', 'Ratón gaming con sensor óptico de 16000 DPI', 'El Razer DeathAdder es un ratón gaming con un sensor óptico avanzado que ofrece una precisión de 16000 DPI. Ideal para gamers que buscan rendimiento y ergonomía.', 69.99, 8, 'Gaming', 'razer_deathadder.jpg'),
('Impresora Canon PIXMA TS3150', 'Impresora multifunción WiFi', 'Impresora multifunción que permite imprimir, escanear y fotocopiar. Conectividad WiFi para imprimir desde cualquier dispositivo. Ideal para uso doméstico.', 79.99, 5, 'Impresoras', 'canon_pixma.jpg'),
('Disco Duro Externo Seagate 2TB', 'Disco duro portátil USB 3.0', 'Disco duro externo con 2TB de capacidad y conexión USB 3.0 para una transferencia de datos rápida. Compatible con Windows y Mac.', 89.99, 12, 'Almacenamiento', 'seagate_2tb.jpg'),
('SSD Samsung 970 EVO 500GB', 'Unidad SSD NVMe M.2', 'Unidad de estado sólido con interfaz NVMe y formato M.2. Velocidades de lectura de hasta 3.500 MB/s y escritura de hasta 2.500 MB/s.', 129.99, 7, 'Componentes', 'samsung_970evo.jpg'),
('Tarjeta Gráfica NVIDIA RTX 3060', 'GPU gaming con raytracing', 'Tarjeta gráfica para gaming de última generación con tecnología de raytracing y DLSS. 12GB de memoria GDDR6. Ideal para juegos en resolución 1440p.', 399.99, 3, 'Componentes', 'nvidia_rtx3060.jpg');
