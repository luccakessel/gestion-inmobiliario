-- Script de Base de Datos para Hostinger
-- Ejecutar este script en phpMyAdmin de Hostinger

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =============================
-- Tabla: usuarios
-- =============================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','abogado','secretario','contador') NOT NULL DEFAULT 'abogado',
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    ultimo_acceso DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: clientes
-- =============================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    fecha_nacimiento DATE,
    profesion VARCHAR(100),
    estado_civil ENUM('soltero','casado','divorciado','viudo','concubinato'),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clientes_apellido (apellido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: especialidades (Tipos de Propiedad)
-- =============================
CREATE TABLE IF NOT EXISTS especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: casos (Propiedades)
-- =============================
CREATE TABLE IF NOT EXISTS casos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expediente VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    abogado_id INT NULL,
    especialidad_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo','pausado','cerrado','archivado') DEFAULT 'activo',
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NULL,
    honorarios_estimados DECIMAL(10,2) DEFAULT 0,
    honorarios_cobrados DECIMAL(10,2) DEFAULT 0,
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (abogado_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE RESTRICT,
    INDEX idx_casos_estado (estado),
    INDEX idx_casos_fecha (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: servicios_legales (Servicios/Extras)
-- =============================
CREATE TABLE IF NOT EXISTS servicios_legales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2) DEFAULT 0,
    precio_por_hora DECIMAL(10,2) DEFAULT 0,
    especialidad_id INT NULL,
    duracion_estimada INT NULL,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: servicios_prestados (detalle por propiedad)
-- =============================
CREATE TABLE IF NOT EXISTS servicios_prestados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha_servicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    horas_trabajadas DECIMAL(4,2) DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    estado_pago ENUM('pendiente','pagado','parcial') DEFAULT 'pendiente',
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios_legales(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: citas (Visitas)
-- =============================
CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NULL,
    cliente_id INT NULL,
    abogado_id INT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_cita DATETIME NOT NULL,
    duracion_estimada INT NULL,
    tipo VARCHAR(50) NULL,
    ubicacion VARCHAR(255),
    notas TEXT,
    estado ENUM('programada','realizada','cancelada','reprogramada') DEFAULT 'programada',
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (abogado_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_citas_estado (estado),
    INDEX idx_citas_fecha (fecha_cita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: facturas
-- =============================
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    caso_id INT NULL,
    numero_factura VARCHAR(50) UNIQUE,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME NULL,
    subtotal DECIMAL(10,2) DEFAULT 0,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('borrador','emitida','pagada','vencida','cancelada') DEFAULT 'emitida',
    metodo_pago ENUM('efectivo','transferencia','cheque','tarjeta','otro') DEFAULT 'efectivo',
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE SET NULL,
    INDEX idx_fact_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: detalle_facturas
-- =============================
CREATE TABLE IF NOT EXISTS detalle_facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios_legales(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: documentos
-- =============================
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NULL,
    cliente_id INT NULL,
    nombre_archivo VARCHAR(200) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    tipo_documento VARCHAR(100),
    descripcion TEXT,
    `tamaño_archivo` INT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    INDEX idx_docs_caso (caso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Datos iniciales
-- =============================
INSERT IGNORE INTO usuarios (username, password, rol, nombre, email) VALUES
('admin', 'admin123', 'admin', 'Administrador', 'admin@tudominio.com'),
('agente1', 'agente123', 'abogado', 'Agente Inmobiliario 1', 'agente1@tudominio.com');

INSERT IGNORE INTO especialidades (nombre, descripcion) VALUES
('Departamento', 'Departamento en edificio'),
('Casa', 'Casa residencial'),
('Local Comercial', 'Local apto comercial'),
('Oficina', 'Oficina comercial'),
('Terreno', 'Terreno para construcción');

INSERT IGNORE INTO clientes (nombre, apellido, dni, telefono, email, direccion, profesion, estado_civil) VALUES
('Juan', 'Pérez', '12345678', '11-2222-3333', 'juan.perez@email.com', 'Av. Corrientes 1200, CABA', 'Comerciante', 'casado'),
('María', 'González', '87654321', '11-7777-8888', 'maria.gonzalez@email.com', 'Ruta 8 Km 50, Buenos Aires', 'Empleada', 'soltera'),
('Carlos', 'López', '11223344', '11-5555-6666', 'carlos.lopez@email.com', 'Av. Santa Fe 2000, CABA', 'Empresario', 'casado');

INSERT IGNORE INTO casos (
    numero_expediente, cliente_id, abogado_id, especialidad_id, titulo, descripcion, fecha_inicio, honorarios_estimados, honorarios_cobrados, estado
) VALUES 
('PROP-0001', 1, 2, 1, 'Depto 2 ambientes en Palermo', 'Excelente ubicación, 45 m2, balcón, luminoso', CURDATE(), 120000.00, 0.00, 'activo'),
('PROP-0002', 2, 2, 2, 'Casa 3 dormitorios en San Isidro', 'Casa familiar con jardín, 120 m2', CURDATE(), 180000.00, 0.00, 'activo');

INSERT IGNORE INTO servicios_legales (nombre, descripcion, precio_base, precio_por_hora) VALUES
('Publicación Premium', 'Destacar en portales inmobiliarios', 10000.00, 0),
('Fotografía Profesional', 'Pack de fotos y video 360°', 30000.00, 0),
('Avalúo Inmobiliario', 'Tasación profesional', 25000.00, 0),
('Gestión de Contratos', 'Redacción y gestión de contratos', 0, 5000.00);

-- Una visita de ejemplo
INSERT IGNORE INTO citas (caso_id, cliente_id, abogado_id, titulo, descripcion, fecha_cita, duracion_estimada, tipo, ubicacion, estado) VALUES
(1, 2, 2, 'Visita a propiedad', 'Coordinación con interesado', DATE_ADD(NOW(), INTERVAL 2 DAY), 60, 'visita', 'Palermo, CABA', 'programada');

-- Factura de ejemplo
INSERT IGNORE INTO facturas (cliente_id, caso_id, numero_factura, fecha_emision, fecha_vencimiento, subtotal, impuestos, total, estado, metodo_pago, observaciones) VALUES
(1, 1, 'FAC-2024-0001', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 100000.00, 21000.00, 121000.00, 'emitida', 'transferencia', 'Señal de compra');

INSERT IGNORE INTO detalle_facturas (factura_id, servicio_id, descripcion, cantidad, precio_unitario, total) VALUES
(1, 1, 'Publicación Premium', 1, 10000.00, 10000.00);
