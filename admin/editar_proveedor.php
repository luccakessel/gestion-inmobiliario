<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

$mensaje = "";

// Verificar si viene el ID del proveedor
if (!isset($_GET["id"])) {
    die("❌ ID de proveedor no especificado.");
}

$id = intval($_GET["id"]);

// Obtener datos actuales del proveedor
$sql = "SELECT * FROM proveedores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("❌ Proveedor no encontrado.");
}

$proveedor = $resultado->fetch_assoc();

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $direccion = trim($_POST["direccion"]);
    $localidad = trim($_POST["localidad"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);
    $observaciones = trim($_POST["observaciones"]);

    if (empty($nombre) || empty($direccion) || empty($localidad)) {
        $mensaje = "⚠️ Por favor completa los campos obligatorios.";
    } else {
        $sql = "UPDATE proveedores 
                SET nombre=?, direccion=?, localidad=?, email=?, telefono=?, observaciones=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $nombre, $direccion, $localidad, $email, $telefono, $observaciones, $id);

        if ($stmt->execute()) {
            $mensaje = "✅ Proveedor actualizado correctamente.";
            $proveedor = [
                "nombre" => $nombre,
                "direccion" => $direccion,
                "localidad" => $localidad,
                "email" => $email,
                "telefono" => $telefono,
                "observaciones" => $observaciones
            ];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proveedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #c62828; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: var(--primary-color); color: white; padding: 20px 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 10px 20px; margin: 5px 0; border-radius: 5px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Carnicería</h4>
                <p class="text-white-50 small">Editar Proveedor</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link active"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="bi bi-pencil-square"></i> Editar Proveedor</h4>
                <a href="proveedores.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Localidad *</label>
                            <input type="text" name="localidad" value="<?= htmlspecialchars($proveedor['localidad']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección *</label>
                            <input type="text" name="direccion" value="<?= htmlspecialchars($proveedor['direccion']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono']) ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email']) ?>" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($proveedor['observaciones']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Actualizar Proveedor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
