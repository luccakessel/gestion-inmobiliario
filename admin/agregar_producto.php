<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Obtener lista de proveedores
$proveedores = $conn->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC");

// Obtener lista de categorías activas
$categorias = $conn->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre ASC");

// Guardar producto
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $categoria_id = $_POST['categoria_id'];
    $proveedor_id = $_POST['proveedor_id'];
    $stock_minimo = $_POST['stock_minimo'];
    $stock_existencia = $_POST['stock_existencia'];
    $precio_costo = $_POST['precio_costo'];
    $precio_venta = $_POST['precio_venta'];

    $stmt = $conn->prepare("INSERT INTO productos 
        (nombre, descripcion, categoria_id, proveedor_id, stock_minimo, stock_existencia, precio_costo, precio_venta) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiiddi",
        $nombre, $descripcion, $categoria_id, $proveedor_id,
        $stock_minimo, $stock_existencia, $precio_costo, $precio_venta
    );

    if ($stmt->execute()) {
        header("Location: productos.php?msg=Producto agregado con éxito");
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
    <title>Agregar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #c62828;
            --secondary-color: #e53935;
            --light-gray: #f5f5f5;
            --white: #ffffff;
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
                <p class="text-white-50 small">Agregar Producto</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link active"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Agregar Producto</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['usuario'] ?? 'Administrador') ?>
                    </span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header">Nuevo Producto</div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <?php while($c = $categorias->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <?php while($p = $proveedores->fetch_assoc()): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Stock Existencia</label>
                            <input type="number" name="stock_existencia" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio Costo</label>
                            <input type="number" name="precio_costo" step="0.01" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio Venta</label>
                            <input type="number" name="precio_venta" step="0.01" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">💾 Guardar Producto</button>
                            <a href="productos.php" class="btn btn-secondary">⬅ Cancelar</a>
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
