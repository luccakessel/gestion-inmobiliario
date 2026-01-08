<?php
session_start();
require_once "includes/db.php"; 

$error = "";

// Si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["username"]);
    $password = $_POST["password"];
    
    if (empty($usuario) || empty($password)) {
        $error = "Por favor complete todos los campos";
    } else {
        // Buscar usuario en la base de datos
        $stmt = $conn->prepare("SELECT id, username, password, rol, nombre FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verificar contraseña: soporta tanto hash como texto plano
            if (password_verify($password, $row["password"]) || $password === $row["password"]) {
                $_SESSION["usuario_id"] = $row["id"];
                $_SESSION["usuario"] = $row["username"];
                $_SESSION["nombre"] = $row["nombre"];
                $_SESSION["rol"] = $row["rol"];

                // Registrar último acceso
                $updateStmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $row["id"]);
                $updateStmt->execute();

                // Redirección según el rol
                if ($row["rol"] === "admin") {
                    header("Location: admin/panel.php");
                } else if ($row["rol"] === "abogado") {
                    header("Location: admin/casos.php");
                } else if ($row["rol"] === "secretario") {
                    header("Location: admin/citas.php");
                } else if ($row["rol"] === "contador") {
                    header("Location: admin/facturacion.php");
                } else {
                    header("Location: admin/");
                }
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos";
            }
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Sistema de Gestión Inmobiliaria</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
            --accent-color: #4a90e2;
            --light-gray: #f5f5f5;
            --dark-gray: #333;
            --white: #ffffff;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .login-header h1 { font-size: 24px; margin-bottom: 5px; }
        .login-header p { font-size: 14px; opacity: 0.9; }
        .login-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--dark-gray); font-weight: 500; }
        .form-control {
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(198, 40, 40, 0.2);
        }
        .btn {
            display: block; width: 100%; padding: 12px;
            background: var(--primary-color); color: white;
            border: none; border-radius: 5px; font-size: 16px; font-weight: 500;
            cursor: pointer; transition: background 0.3s;
        }
        .btn:hover { background: var(--secondary-color); }
        .error-message {
            background: #ffebee; color: #c62828;
            padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px;
            display: <?php echo !empty($error) ? 'block' : 'none'; ?>;
        }
        .login-footer {
            text-align: center; padding: 15px; border-top: 1px solid #eee; font-size: 13px; color: #666;
        }
        .logo { font-size: 28px; margin-bottom: 15px; color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo" style="font-size: 3rem; margin-bottom: 10px;">🏠</div>
            <h1>Gestión Inmobiliaria</h1>
            <p>Sistema para agente inmobiliario independiente</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Ingrese su usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
        </div>
        
        <div class="login-footer">
            Gestión Inmobiliaria <?= date('Y'); ?> - Todos los derechos reservados
        </div>
    </div>
</body>
</html>
