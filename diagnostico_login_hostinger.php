<?php
// Script de diagnóstico para problemas de login en Hostinger
session_start();
require_once "includes/db.php";

echo "<h2>🔍 Diagnóstico de Login - Hostinger</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; background: #fff8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// 1. Verificar conexión a la base de datos
echo "<h3>1. Verificación de Conexión a Base de Datos</h3>";
if ($conn->connect_error) {
    echo "<div class='error'>❌ Error de conexión: " . $conn->connect_error . "</div>";
    exit;
} else {
    echo "<div class='success'>✅ Conexión exitosa a la base de datos</div>";
    echo "<div class='info'>📊 Base de datos: " . DB_NAME . "</div>";
    echo "<div class='info'>👤 Usuario: " . DB_USERNAME . "</div>";
    echo "<div class='info'>🌐 Host: " . DB_HOST . "</div>";
}

// 2. Verificar si la tabla usuarios existe
echo "<h3>2. Verificación de Estructura de Base de Datos</h3>";
$tablas = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($tablas->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'usuarios' no existe</div>";
    echo "<div class='info'>💡 Necesitas importar el archivo SQL de la base de datos</div>";
} else {
    echo "<div class='success'>✅ La tabla 'usuarios' existe</div>";
}

// 3. Verificar estructura de la tabla usuarios
echo "<h3>3. Estructura de la Tabla Usuarios</h3>";
$estructura = $conn->query("DESCRIBE usuarios");
if ($estructura) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    while ($row = $estructura->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No se pudo obtener la estructura de la tabla</div>";
}

// 4. Verificar usuarios existentes
echo "<h3>4. Usuarios Existentes en la Base de Datos</h3>";
$usuarios = $conn->query("SELECT id, username, password, nombre, rol, email FROM usuarios");
if ($usuarios && $usuarios->num_rows > 0) {
    echo "<div class='success'>✅ Se encontraron " . $usuarios->num_rows . " usuario(s)</div>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password (hash)</th><th>Nombre</th><th>Rol</th><th>Email</th></tr>";
    while ($row = $usuarios->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . substr($row['password'], 0, 20) . "...</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['rol'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No se encontraron usuarios en la base de datos</div>";
    echo "<div class='info'>💡 Necesitas crear usuarios o importar datos de prueba</div>";
}

// 5. Probar login con diferentes credenciales
echo "<h3>5. Prueba de Login con Diferentes Credenciales</h3>";

$credenciales_prueba = [
    ['admin', 'admin123'],
    ['admin', 'admin'],
    ['agente1', 'agente123'],
    ['agente1', 'agente1']
];

foreach ($credenciales_prueba as $credencial) {
    $usuario = $credencial[0];
    $password = $credencial[1];
    
    echo "<h4>Probando: $usuario / $password</h4>";
    
    $stmt = $conn->prepare("SELECT id, username, password, rol, nombre FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<div class='info'>👤 Usuario encontrado: " . $row['nombre'] . " (Rol: " . $row['rol'] . ")</div>";
        
        // Verificar contraseña
        if (password_verify($password, $row["password"])) {
            echo "<div class='success'>✅ Contraseña correcta (hash verificado)</div>";
        } elseif ($password === $row["password"]) {
            echo "<div class='success'>✅ Contraseña correcta (texto plano)</div>";
        } else {
            echo "<div class='error'>❌ Contraseña incorrecta</div>";
            echo "<div class='info'>🔍 Hash almacenado: " . substr($row['password'], 0, 30) . "...</div>";
        }
    } else {
        echo "<div class='error'>❌ Usuario no encontrado</div>";
    }
    echo "<hr>";
}

// 6. Crear usuario admin si no existe
echo "<h3>6. Crear Usuario Admin (si no existe)</h3>";
$check_admin = $conn->query("SELECT id FROM usuarios WHERE username = 'admin'");
if ($check_admin->num_rows == 0) {
    echo "<div class='warning'>⚠️ Usuario admin no existe, creándolo...</div>";
    
    $password_hash = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (username, password, rol, nombre, email) VALUES (?, ?, ?, ?, ?)");
    $username = 'admin';
    $rol = 'admin';
    $nombre = 'Administrador';
    $email = 'admin@tudominio.com';
    
    $stmt->bind_param("sssss", $username, $password_hash, $rol, $nombre, $email);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ Usuario admin creado exitosamente</div>";
        echo "<div class='info'>👤 Usuario: admin</div>";
        echo "<div class='info'>🔑 Contraseña: admin123</div>";
    } else {
        echo "<div class='error'>❌ Error al crear usuario admin: " . $stmt->error . "</div>";
    }
} else {
    echo "<div class='success'>✅ Usuario admin ya existe</div>";
}

// 7. Información del servidor
echo "<h3>7. Información del Servidor</h3>";
echo "<div class='info'>🌐 Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</div>";
echo "<div class='info'>📁 Directorio: " . getcwd() . "</div>";
echo "<div class='info'>🐘 PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>📅 Fecha/Hora: " . date('Y-m-d H:i:s') . "</div>";

// 8. Verificar archivos importantes
echo "<h3>8. Verificación de Archivos Importantes</h3>";
$archivos_importantes = [
    'index.php',
    'admin/panel.php',
    'includes/db.php',
    'includes/funciones.php'
];

foreach ($archivos_importantes as $archivo) {
    if (file_exists($archivo)) {
        echo "<div class='success'>✅ $archivo existe</div>";
    } else {
        echo "<div class='error'>❌ $archivo NO existe</div>";
    }
}

echo "<hr>";
echo "<h3>🚀 Próximos Pasos</h3>";
echo "<div class='info'>1. Si no hay usuarios, ejecuta este script para crear el usuario admin</div>";
echo "<div class='info'>2. Prueba el login en: <a href='index.php'>index.php</a></div>";
echo "<div class='info'>3. Si persiste el problema, verifica la configuración de Hostinger</div>";

$conn->close();
?>
