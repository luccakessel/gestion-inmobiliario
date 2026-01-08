<?php
// Script de prueba para verificar el login
session_start();
require_once "includes/db.php";

echo "<h2>🧪 Prueba de Login - Despacho de Abogados</h2>";

// Simular login
$usuario = 'admin';
$password = 'admin123';

echo "<h3>1. Verificando usuario en base de datos...</h3>";

$stmt = $conn->prepare("SELECT id, username, password, nombre, rol FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "✅ Usuario encontrado:<br>";
    echo "ID: " . $row['id'] . "<br>";
    echo "Username: " . $row['username'] . "<br>";
    echo "Nombre: " . $row['nombre'] . "<br>";
    echo "Rol: " . $row['rol'] . "<br>";
    
    echo "<h3>2. Verificando contraseña...</h3>";
    
    // Verificar contraseña
    if (password_verify($password, $row["password"]) || $password === $row["password"]) {
        echo "✅ Contraseña correcta<br>";
        
        // Simular sesión
        $_SESSION["usuario_id"] = $row["id"];
        $_SESSION["usuario"] = $row["username"];
        $_SESSION["nombre"] = $row["nombre"];
        $_SESSION["rol"] = $row["rol"];
        
        echo "<h3>3. Sesión creada:</h3>";
        echo "Usuario ID: " . $_SESSION["usuario_id"] . "<br>";
        echo "Usuario: " . $_SESSION["usuario"] . "<br>";
        echo "Nombre: " . $_SESSION["nombre"] . "<br>";
        echo "Rol: " . $_SESSION["rol"] . "<br>";
        
        echo "<h3>4. Verificando redirección...</h3>";
        if ($row["rol"] === "admin") {
            $redirect = "admin/panel.php";
            echo "✅ Redirección: " . $redirect . "<br>";
            
            // Verificar que el archivo existe
            if (file_exists($redirect)) {
                echo "✅ Archivo de destino existe<br>";
                echo "🚀 <a href='$redirect'>Hacer clic aquí para ir al panel</a><br>";
            } else {
                echo "❌ Archivo de destino NO existe<br>";
            }
        }
        
    } else {
        echo "❌ Contraseña incorrecta<br>";
    }
    
} else {
    echo "❌ Usuario no encontrado<br>";
}

echo "<hr>";
echo "<h3>5. Información del sistema:</h3>";
echo "Directorio actual: " . getcwd() . "<br>";
echo "URL base: " . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) : 'N/A') . "<br>";
echo "Archivo actual: " . __FILE__ . "<br>";

$conn->close();
?>


