<?php
// Configuración de Base de Datos
// Para desarrollo local (XAMPP)
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    define("DB_HOST", "localhost"); 
    define("DB_NAME", "gestion_inmobiliaria");
    define("DB_USERNAME", "root");
    define("DB_PASSWORD", "");
    define("DB_ENCODE", "utf8");
} else {
    // Para Hostinger (producción)
    define("DB_HOST", "localhost"); // En Hostinger generalmente es localhost
    define("DB_NAME", "u277227798_inmobiliaria"); // Tu nombre de BD real
    define("DB_USERNAME", "u277227798_admin"); // Tu usuario de BD real
    define("DB_PASSWORD", "HS73iLp:7"); // Tu contraseña real
    define("DB_ENCODE", "utf8mb4");
}

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset(DB_ENCODE);

// Configuración de timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>
