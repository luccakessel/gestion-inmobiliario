-- Esquema base para Gestión Inmobiliaria (compatible con el código actual)
-- Recomendado: crear base de datos nueva y usar este esquema.

-- Opcional: crear y usar BD
-- CREATE DATABASE IF NOT EXISTS gestion_inmobiliaria CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE gestion_inmobiliaria;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =============================
-- Tabla: usuarios
-- (usado por login y roles)
-- =============================
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
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
DROP TABLE IF EXISTS clientes;
CREATE TABLE clientes (
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
-- compatible con el código que usa "especialidades"
-- =============================
DROP TABLE IF EXISTS especialidades;
CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: casos (Propiedades)
-- mantiene nombres/columnas esperadas por el sistema
-- =============================
DROP TABLE IF EXISTS casos;
CREATE TABLE casos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expediente VARCHAR(50) UNIQUE NOT NULL, -- Código de propiedad (ej: PROP-0001)
    cliente_id INT NOT NULL,                       -- Propietario/Vendedor
    abogado_id INT NULL,                           -- Agente responsable (nullable)
    especialidad_id INT NOT NULL,                  -- Tipo de propiedad
    titulo VARCHAR(200) NOT NULL,                  -- Título del aviso
    descripcion TEXT,
    estado ENUM('activo','pausado','cerrado','archivado') DEFAULT 'activo',
    fecha_inicio DATE NOT NULL,                    -- Fecha de captación/publicación
    fecha_cierre DATE NULL,                        -- Baja/cierre de publicación/venta
    honorarios_estimados DECIMAL(10,2) DEFAULT 0,  -- Usado como Precio Estimado
    honorarios_cobrados DECIMAL(10,2) DEFAULT 0,   -- Usado como Precio/Señado/Comisión cobrada
    observaciones TEXT,
    CONSTRAINT fk_casos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_casos_agente FOREIGN KEY (abogado_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_casos_tipo FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE RESTRICT,
    INDEX idx_casos_estado (estado),
    INDEX idx_casos_fecha (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: servicios_legales (Servicios/Extras)
-- opcional, mantenida por compatibilidad
-- =============================
DROP TABLE IF EXISTS servicios_legales;
CREATE TABLE servicios_legales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2) DEFAULT 0,
    precio_por_hora DECIMAL(10,2) DEFAULT 0,
    especialidad_id INT NULL,
    duracion_estimada INT NULL,
    CONSTRAINT fk_servicios_tipo FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: servicios_prestados (detalle de servicios por propiedad)
-- =============================
DROP TABLE IF EXISTS servicios_prestados;
CREATE TABLE servicios_prestados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha_servicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    horas_trabajadas DECIMAL(4,2) DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    estado_pago ENUM('pendiente','pagado','parcial') DEFAULT 'pendiente',
    CONSTRAINT fk_sp_caso FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE,
    CONSTRAINT fk_sp_servicio FOREIGN KEY (servicio_id) REFERENCES servicios_legales(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: citas (Visitas)
-- =============================
DROP TABLE IF EXISTS citas;
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,               -- Propiedad visitada
    cliente_id INT NULL,                -- Interesado/comprador
    usuario_id INT NULL,                -- Agente que agenda
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    estado ENUM('pendiente','confirmada','realizada','cancelada') DEFAULT 'pendiente',
    ubicacion VARCHAR(255),
    CONSTRAINT fk_citas_caso FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE,
    CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_citas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_citas_estado (estado),
    INDEX idx_citas_fecha (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: vencimientos (Recordatorios)
-- =============================
DROP TABLE IF EXISTS vencimientos;
CREATE TABLE vencimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    fecha_vencimiento DATETIME NOT NULL,
    estado ENUM('pendiente','cumplido','vencido') DEFAULT 'pendiente',
    prioridad ENUM('baja','media','alta') DEFAULT 'media',
    CONSTRAINT fk_venc_caso FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE,
    INDEX idx_venc_fecha (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: facturas (Ingresos/Comisiones)
-- =============================
DROP TABLE IF EXISTS facturas;
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    caso_id INT NULL,
    numero VARCHAR(50) UNIQUE,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) DEFAULT 0,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','pagado','anulado') DEFAULT 'pendiente',
    metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') DEFAULT 'efectivo',
    observaciones TEXT,
    CONSTRAINT fk_fact_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fact_caso FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE SET NULL,
    INDEX idx_fact_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Tabla: documentos
-- =============================
DROP TABLE IF EXISTS documentos;
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo VARCHAR(100),
    ruta VARCHAR(255) NOT NULL,
    tamanio INT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_docs_caso FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE,
    INDEX idx_docs_caso (caso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Datos de ejemplo mínimos
-- =============================

INSERT INTO usuarios (username, password, rol, nombre, email)
VALUES
('admin', 'admin', 'admin', 'Administrador', 'admin@example.com'),
('agente1', 'agente123', 'abogado', 'Agente Inmobiliario 1', 'agente1@example.com');

INSERT INTO especialidades (nombre, descripcion) VALUES
('Departamento', 'Departamento en edificio'),
('Casa', 'Casa residencial'),
('Local Comercial', 'Local apto comercial');

INSERT INTO clientes (nombre, apellido, dni, telefono, email, direccion, profesion, estado_civil) VALUES
('Juan', 'Pérez', '12345678', '11-2222-3333', 'juan.perez@email.com', 'Av. Corrientes 1200, CABA', 'Comerciante', 'casado'),
('María', 'González', '87654321', '11-7777-8888', 'maria.gonzalez@email.com', 'Ruta 8 Km 50, Buenos Aires', 'Empleada', 'soltera');

-- Propiedad de ejemplo (tabla "casos")
INSERT INTO casos (
    numero_expediente, cliente_id, abogado_id, especialidad_id, titulo, descripcion, fecha_inicio, honorarios_estimados, honorarios_cobrados, estado
) VALUES (
    'PROP-0001', 1, 2, 1, 'Depto 2 ambientes en Palermo', 'Excelente ubicación, 45 m2, balcón, luminoso', CURDATE(), 120000.00, 0.00, 'activo'
);

-- Servicios de ejemplo (opcionales)
INSERT INTO servicios_legales (nombre, descripcion, precio_base, precio_por_hora, especialidad_id, duracion_estimada) VALUES
('Publicación Premium', 'Destacar en portales', 10000.00, 0, NULL, NULL),
('Fotografía Profesional', 'Pack de fotos y video', 30000.00, 0, NULL, NULL);

-- Visita de ejemplo
INSERT INTO citas (caso_id, cliente_id, usuario_id, titulo, descripcion, fecha_inicio, estado, ubicacion) VALUES
(1, 2, 2, 'Visita a propiedad', 'Coordinación con interesado', DATE_ADD(NOW(), INTERVAL 2 DAY), 'confirmada', 'Palermo, CABA');

-- Factura de ejemplo
INSERT INTO facturas (cliente_id, caso_id, numero, subtotal, impuestos, total, estado, metodo_pago, observaciones) VALUES
(1, 1, 'FAC-0001', 100000.00, 21000.00, 121000.00, 'pendiente', 'transferencia', 'Señal de compra');


