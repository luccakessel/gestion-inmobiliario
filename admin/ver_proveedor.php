<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Verificar ID del proveedor
if (!isset($_GET['id'])) {
    die("Proveedor no especificado");
}
$proveedor_id = intval($_GET['id']);

// Traer datos del proveedor
$sql = "SELECT * FROM proveedores WHERE id = ?";
$stmt_proveedor = $conn->prepare($sql);
$stmt_proveedor->bind_param("i", $proveedor_id);
$stmt_proveedor->execute();
$result = $stmt_proveedor->get_result();
$proveedor = $result->fetch_assoc();

if (!$proveedor) {
    die("Proveedor no encontrado");
}

// Guardar producto
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $stock_minimo = $_POST['stock_minimo'];
    $stock_existencia = $_POST['stock_existencia'];
    $precio_compra = $_POST['precio_compra'];
    $precio_venta = $_POST['precio_venta'];

    $stmt = $conn->prepare("INSERT INTO productos 
        (codigo, nombre, proveedor_id, stock_minimo, stock_existencia, precio_costo, precio_venta) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiidd",
        $codigo, $nombre, $proveedor_id, $stock_minimo, $stock_existencia, $precio_compra, $precio_venta
    );

    if ($stmt->execute()) {
        header("Location: productos.php?ok=1");
        exit;
    } else {
        $error = "Error al guardar producto: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedor <?= htmlspecialchars($proveedor['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #c62828;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Carnicería</h4>
                <p class="text-white-50 small">Gestión de Proveedores</p>
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
            <!-- Encabezado -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="bi bi-truck"></i> Proveedor: <?= htmlspecialchars($proveedor['nombre']) ?></h4>
                <a href="proveedores.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>

            <!-- Datos proveedor -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-danger text-white">
                    Datos del proveedor
                </div>
                <div class="card-body">
                    <p><strong>Contacto:</strong> <?= htmlspecialchars($proveedor['contacto']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($proveedor['telefono']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($proveedor['email']) ?></p>
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($proveedor['direccion']) ?></p>
                    <p><strong>Fecha de registro:</strong> <?= htmlspecialchars($proveedor['fecha_registro']) ?></p>
                </div>
            </div>

            <!-- Formulario agregar producto -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Agregar producto a este proveedor
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Existencia</label>
                            <input type="number" name="stock_existencia" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio Compra</label>
                            <input type="number" step="0.01" name="precio_compra" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio Venta</label>
                            <input type="number" step="0.01" name="precio_venta" class="form-control">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Guardar Producto</button>
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
