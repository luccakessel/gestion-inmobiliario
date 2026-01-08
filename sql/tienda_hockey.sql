-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS despacho_abogados;
USE despacho_abogados;

-- Tabla de usuarios (para login en el panel admin)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'abogado', 'secretario', 'contador') NOT NULL DEFAULT 'secretario',
    matricula VARCHAR(20),
    especialidad VARCHAR(100),
    ultimo_acceso DATETIME
);

-- Insertar usuario administrador por defecto (usuario: admin / pass: admin123)
INSERT INTO usuarios (username, password, nombre, rol, matricula, especialidad) VALUES 
('admin', '$2y$10$8MNXAOYLSbWYRGX1jHZZK.XZmGBx.KO9PD5dEQol3S0gVeAF3pBOO', 'Dr. Administrador', 'admin', '12345', 'Derecho General');

-- Tabla de especialidades legales
CREATE TABLE IF NOT EXISTS especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

-- Insertar especialidades de ejemplo
INSERT INTO especialidades (nombre, descripcion) VALUES
('Derecho Civil', 'Contratos, daños y perjuicios, responsabilidad civil'),
('Derecho Penal', 'Defensa criminal, delitos, faltas'),
('Derecho Laboral', 'Despidos, accidentes de trabajo, sindicatos'),
('Derecho Comercial', 'Sociedades, quiebras, contratos comerciales'),
('Derecho de Familia', 'Divorcios, tenencia, alimentos, sucesiones');

-- Tabla de servicios legales
CREATE TABLE IF NOT EXISTS servicios_legales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2) NOT NULL,
    precio_por_hora DECIMAL(10,2) DEFAULT 0,
    especialidad_id INT NOT NULL,
    duracion_estimada INT DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);

-- Insertar servicios de ejemplo
INSERT INTO servicios_legales (nombre, descripcion, precio_base, precio_por_hora, especialidad_id, duracion_estimada) VALUES
('Consulta Legal', 'Consulta inicial de 1 hora', 150.00, 150.00, 1, 1),
('Redacción de Contrato', 'Elaboración de contrato personalizado', 500.00, 200.00, 1, 3),
('Defensa Penal', 'Defensa en proceso penal', 2000.00, 300.00, 2, 20),
('Demanda Laboral', 'Inicio de demanda por despido', 800.00, 250.00, 3, 5),
('Divorcio Express', 'Tramitación de divorcio de mutuo acuerdo', 1200.00, 200.00, 5, 8);

-- Tabla de clientes
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
    estado_civil ENUM('soltero', 'casado', 'divorciado', 'viudo', 'concubinato'),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre, apellido, dni, telefono, email, direccion, profesion, estado_civil) VALUES
('Juan', 'Pérez', '12345678', '11-2222-3333', 'juan.perez@email.com', 'Av. Corrientes 1200, CABA', 'Comerciante', 'casado'),
('María', 'González', '87654321', '11-7777-8888', 'maria.gonzalez@email.com', 'Ruta 8 Km 50, Buenos Aires', 'Empleada', 'soltera');

-- Tabla de casos/expedientes
CREATE TABLE IF NOT EXISTS casos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expediente VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    abogado_id INT NOT NULL,
    especialidad_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'pausado', 'cerrado', 'archivado') DEFAULT 'activo',
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE,
    honorarios_estimados DECIMAL(10,2) DEFAULT 0,
    honorarios_cobrados DECIMAL(10,2) DEFAULT 0,
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (abogado_id) REFERENCES usuarios(id),
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);

-- Tabla de servicios prestados
CREATE TABLE IF NOT EXISTS servicios_prestados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha_servicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    horas_trabajadas DECIMAL(4,2) DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    estado_pago ENUM('pendiente', 'pagado', 'parcial') DEFAULT 'pendiente',
    FOREIGN KEY (caso_id) REFERENCES casos(id),
    FOREIGN KEY (servicio_id) REFERENCES servicios_legales(id)
);

-- Tabla de citas y audiencias
CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT,
    cliente_id INT NOT NULL,
    abogado_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_cita DATETIME NOT NULL,
    duracion_estimada INT DEFAULT 60,
    tipo ENUM('consulta', 'audiencia', 'reunion', 'llamada', 'otro') DEFAULT 'consulta',
    estado ENUM('programada', 'realizada', 'cancelada', 'reprogramada') DEFAULT 'programada',
    ubicacion VARCHAR(200),
    notas TEXT,
    FOREIGN KEY (caso_id) REFERENCES casos(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (abogado_id) REFERENCES usuarios(id)
);

-- Tabla de documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT,
    cliente_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_documento ENUM('contrato', 'demanda', 'escrito', 'sentencia', 'otro') DEFAULT 'otro',
    descripcion TEXT,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    tamaño_archivo INT,
    FOREIGN KEY (caso_id) REFERENCES casos(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabla de vencimientos legales
CREATE TABLE IF NOT EXISTS vencimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_vencimiento DATETIME NOT NULL,
    tipo_vencimiento ENUM('plazo_legal', 'audiencia', 'presentacion', 'pago', 'otro') DEFAULT 'plazo_legal',
    estado ENUM('pendiente', 'cumplido', 'vencido') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    recordatorio_dias INT DEFAULT 7,
    FOREIGN KEY (caso_id) REFERENCES casos(id)
);

-- Tabla de facturación
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    caso_id INT,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('borrador', 'emitida', 'pagada', 'vencida', 'cancelada') DEFAULT 'borrador',
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'tarjeta') DEFAULT 'efectivo',
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (caso_id) REFERENCES casos(id)
);

-- Tabla de detalle de facturas
CREATE TABLE IF NOT EXISTS detalle_facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(4,2) DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id),
    FOREIGN KEY (servicio_id) REFERENCES servicios_legales(id)
);
