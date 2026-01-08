<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Función segura
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Verificar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID de producto no especificado o inválido.");
}

$id = intval($_GET['id']);

// Obtener producto
$stmt = $conn->prepare("SELECT id, nombre, descripcion, precio_costo, precio_venta, stock_existencia, stock_minimo, categoria_id 
                        FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
if (!$producto) die("Error: Producto no encontrado.");

// Categorías
$categorias_query = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");

// Actualizar
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $producto_id = $_POST['id'] ?? 0;
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? 0;
    $stock_existencia = $_POST['stock_existencia'] ?? 0;
    $stock_minimo = $_POST['stock_minimo'] ?? 0;
    $precio_costo = $_POST['precio_costo'] ?? 0.0;
    $precio_venta = $_POST['precio_venta'] ?? 0.0;

    $stmt_update = $conn->prepare("UPDATE productos SET
        nombre=?, descripcion=?, categoria_id=?, stock_existencia=?, stock_minimo=?, 
        precio_costo=?, precio_venta=? WHERE id=?");

    $stmt_update->bind_param("ssiidddi",
        $nombre, $descripcion, $categoria_id, $stock_existencia, $stock_minimo,
        $precio_costo, $precio_venta, $producto_id
    );

    if ($stmt_update->execute()) {
        header("Location: productos.php?msg=Producto actualizado con éxito");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Producto</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    :root {
        --primary-color: #c62828;
        --secondary-color: #e53935;
        --light-gray: #f5f5f5;
    }
    body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .sidebar { min-height: 100vh; background: var(--primary-color); color: white; padding: 20px 0; }
    .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 10px 20px; margin: 5px 0; border-radius: 5px; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 px-0 sidebar">
      <div class="text-center mb-4">
        <h4 class="text-white">Carnicería</h4>
        <p class="text-white-50 small">Editar Producto</p>
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

    <!-- Main -->
    <div class="col-md-10 p-4">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Editar Producto</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <input type="hidden" name="id" value="<?= safe($producto['id']) ?>">

            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre *</label>
              <input type="text" name="nombre" id="nombre" class="form-control" value="<?= safe($producto['nombre']) ?>" required>
            </div>

            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea name="descripcion" id="descripcion" class="form-control"><?= safe($producto['descripcion']) ?></textarea>
            </div>

            <div class="mb-3">
              <label for="categoria_id" class="form-label">Categoría</label>
              <select name="categoria_id" id="categoria_id" class="form-select">
                <?php while($c = $categorias_query->fetch_assoc()): ?>
                  <option value="<?= $c['id'] ?>" <?= ($c['id'] == $producto['categoria_id']) ? 'selected' : '' ?>>
                    <?= safe($c['nombre']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="stock_existencia" class="form-label">Stock Existencia</label>
                <input type="number" name="stock_existencia" class="form-control" value="<?= safe($producto['stock_existencia']) ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                <input type="number" name="stock_minimo" class="form-control" value="<?= safe($producto['stock_minimo']) ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="precio_costo" class="form-label">Precio Costo</label>
                <input type="number" step="0.01" name="precio_costo" class="form-control" value="<?= safe($producto['precio_costo']) ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="precio_venta" class="form-label">Precio Venta</label>
                <input type="number" step="0.01" name="precio_venta" class="form-control" value="<?= safe($producto['precio_venta']) ?>">
              </div>
            </div>

            <button type="submit" class="btn btn-success w-100">💾 Guardar Cambios</button>
          </form>
          <a href="productos.php" class="btn btn-secondary w-100 mt-3">⬅ Volver</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
