<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $direccion = trim($_POST["direccion"]);
    $codigo_postal = trim($_POST["codigo_postal"]);
    $localidad = trim($_POST["localidad"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);
    $web = trim($_POST["web"]);
    $iva = $_POST["iva"];
    $observaciones = trim($_POST["observaciones"]);
    $cuil = trim($_POST["cuil"]);

    // Si IVA == Responsable Inscripto -> 1, sino 0
    $responsable_inscripto = ($iva === "Responsable Inscripto") ? 1 : 0;

    // Validación simple
    if (empty($nombre) || empty($direccion) || empty($localidad)) {
        $mensaje = "⚠️ Por favor completa los campos obligatorios.";
    } else {
        $sql = "INSERT INTO proveedores 
                (nombre, direccion, codigo_postal, localidad, email, telefono, web, iva, responsable_inscripto, observaciones, cuil) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssiss", $nombre, $direccion, $codigo_postal, $localidad, $email, $telefono, $web, $iva, $responsable_inscripto, $observaciones, $cuil);

        if ($stmt->execute()) {
            $mensaje = "✅ Proveedor agregado correctamente.";
        } else {
            $mensaje = "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Proveedor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            color: #34495e;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #dcdde1;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0px 0px 6px rgba(52,152,219,0.3);
        }

        .full-width {
            grid-column: span 2;
        }

        button {
            grid-column: span 2;
            padding: 12px;
            background: #3498db;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #2980b9;
        }

        .mensaje {
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-weight: bold;
        }

        .mensaje.exito {
            background: #2ecc71;
            color: white;
        }

        .mensaje.error {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="proveedores.php">⬅ Volver</a>
        <h2>➕ Agregar Proveedor</h2>
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'exito' : 'error' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div>
                <label>Nombre *</label>
                <input type="text" name="nombre" required>
            </div>
            <div>
                <label>Dirección *</label>
                <input type="text" name="direccion" required>
            </div>
            <div>
                <label>Código Postal</label>
                <input type="text" name="codigo_postal">
            </div>
            <div>
                <label>Localidad *</label>
                <input type="text" name="localidad" required>
            </div>
            <div>
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Teléfono</label>
                <input type="text" name="telefono">
            </div>
            <div>
                <label>Web</label>
                <input type="text" name="web" placeholder="www.ejemplo.com">
            </div>
            <div>
                <label>IVA</label>
                <select name="iva">
                    <option value="Responsable Inscripto">Responsable Inscripto</option>
                    <option value="Monotributo">Monotributo</option>
                    <option value="Exento">Exento</option>
                    <option value="Consumidor Final">Consumidor Final</option>
                </select>
            </div>
            <div>
                <label>CUIT</label>
                <input type="text" name="cuil">
            </div>
            <div class="full-width">
                <label>Observaciones</label>
                <input type="text" name="observaciones" placeholder="Marcas, notas, etc.">
            </div>
            <button type="submit">Guardar Proveedor</button>
        </form>
    </div>
</body>
</html>
