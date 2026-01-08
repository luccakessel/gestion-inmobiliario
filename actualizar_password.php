<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos
require_once "includes/db.php";

// Verificar si la tabla usuarios existe
$tablas = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($tablas->num_rows == 0) {
    die("La tabla 'usuarios' no existe en la base de datos. Asegúrate de haber importado correctamente el archivo SQL.");
}

// Verificar si el usuario admin existe
$check = $conn->query("SELECT * FROM usuarios WHERE username = 'admin'");
if ($check->num_rows == 0) {
    // Si no existe, lo creamos
    $nueva_password = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre, rol) VALUES ('admin', ?, 'Administrador', 'admin')");
    $stmt->bind_param("s", $nueva_password);
    
    if ($stmt->execute()) {
        echo "<p>Usuario 'admin' creado correctamente con contraseña 'admin123'.</p>";
    } else {
        echo "<p>Error al crear el usuario: " . $conn->error . "</p>";
    }
} else {
    // Si existe, actualizamos su contraseña
    $nueva_password = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
    $stmt->bind_param("s", $nueva_password);
    
    if ($stmt->execute()) {
        echo "<p>Contraseña del usuario 'admin' actualizada correctamente a 'admin123'.</p>";
    } else {
        echo "<p>Error al actualizar la contraseña: " . $conn->error . "</p>";
    }
}

// Mostrar información de la base de datos para depuración
echo "<h3>Información de la base de datos:</h3>";
echo "<p>Base de datos: " . DB_NAME . "</p>";

// Mostrar tablas en la base de datos
echo "<h3>Tablas en la base de datos:</h3>";
$tablas = $conn->query("SHOW TABLES");
if ($tablas->num_rows > 0) {
    echo "<ul>";
    while ($tabla = $tablas->fetch_row()) {
        echo "<li>" . $tabla[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay tablas en la base de datos.</p>";
}

// Mostrar usuarios existentes
echo "<h3>Usuarios existentes:</h3>";
$usuarios = $conn->query("SELECT id, username, nombre, rol FROM usuarios");
if ($usuarios->num_rows > 0) {
    echo "<ul>";
    while ($usuario = $usuarios->fetch_assoc()) {
        echo "<li>ID: " . $usuario['id'] . " | Usuario: " . $usuario['username'] . " | Nombre: " . $usuario['nombre'] . " | Rol: " . $usuario['rol'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay usuarios en la base de datos.</p>";
}

echo "<p><a href='index.php'>Volver a la página de inicio de sesión</a></p>";
?>