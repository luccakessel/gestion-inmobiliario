<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// === Lógica actualización precios ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_precios') {
    $categoria_a_actualizar = $_POST['categoria_a_actualizar'] ?? '';
    $porcentaje_aumento = $_POST['porcentaje_aumento'] ?? 0;

    if ($categoria_a_actualizar && is_numeric($porcentaje_aumento) && $porcentaje_aumento > 0) {
        $factor_aumento = 1 + ($porcentaje_aumento / 100);

        $stmt = $conn->prepare("UPDATE productos SET precio_venta = precio_venta * ? WHERE categoria_id = ?");
        $stmt->bind_param("di", $factor_aumento, $categoria_a_actualizar);

        if ($stmt->execute()) {
            $filas_afectadas = $stmt->affected_rows;
            header("Location: productos.php?msg=Se actualizaron los precios de $filas_afectadas productos en un $porcentaje_aumento%.");
            exit();
        } else {
            $error_msg = "Error al actualizar precios: " . $stmt->error;
        }
    } else {
        $error_msg = "Por favor, selecciona una categoría y un porcentaje válido.";
    }
}

// === Capturar filtros GET ===
$busqueda = $_GET['busqueda'] ?? '';
$stock = $_GET['stock'] ?? '';
$id = $_GET['id'] ?? '';
$patrimonio_tipo = $_GET['patrimonio'] ?? '';

// === Categorías ===
$categorias_query = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
if (!$categorias_query) {
    die("Error al obtener categorías: " . $conn->error);
}

// === Productos ===
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE 1=1";

if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $sql .= " AND (p.nombre LIKE '%$busq%' OR c.nombre LIKE '%$busq%' OR p.descripcion LIKE '%$busq%')";
}
if ($stock === 'con') {
    $sql .= " AND p.stock_existencia > 0";
} elseif ($stock === 'sin') {
    $sql .= " AND p.stock_existencia <= 0";
}
if ($id !== '') {
    $id_esc = $conn->real_escape_string($id);
    $sql .= " AND p.codigo LIKE '%$id_esc%'";
}
$sql .= " ORDER BY p.id DESC";
$resultado = $conn->query($sql);

// === Patrimonio ===
$patrimonio = 0;
if ($patrimonio_tipo && $resultado && $resultado->num_rows > 0) {
    mysqli_data_seek($resultado, 0);
    while ($fila_calc = $resultado->fetch_assoc()) {
        $stock_exist = (float)$fila_calc['stock_existencia'];
        if ($patrimonio_tipo === "real") {
            $patrimonio += $stock_exist * (float)$fila_calc['precio_costo'];
        } elseif ($patrimonio_tipo === "potencial") {
            $patrimonio += $stock_exist * (float)$fila_calc['precio_venta'];
        }
    }
    mysqli_data_seek($resultado, 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
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
                <p class="text-white-50 small">Gestión de Productos</p>
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
                <h4>Gestión de Productos</h4>
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

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['msg'] ?? '') ?></div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_msg ?? '') ?></div>
            <?php endif; ?>

            <!-- Card filtros -->
            <div class="card mb-4">
                <div class="card-header">Filtros</div>
                <div class="card-body">
                    <form class="row g-3" method="GET" action="">
                        <div class="col-md-4">
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o descripción..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="id" class="form-control" placeholder="Código" value="<?= htmlspecialchars($id ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="stock" class="form-select">
                                <option value="">-- Stock --</option>
                                <option value="con" <?= $stock==='con'?'selected':'' ?>>Con stock</option>
                                <option value="sin" <?= $stock==='sin'?'selected':'' ?>>Sin stock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                        <div class="col-md-2">
                            <a href="productos.php" class="btn btn-secondary w-100">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card actualizar precios -->
            <div class="card mb-4">
                <div class="card-header">Actualizar precios por categoría</div>
                <div class="card-body">
                    <form class="row g-3" method="POST" action="productos.php">
                        <input type="hidden" name="accion" value="actualizar_precios">
                        <div class="col-md-6">
                            <select name="categoria_a_actualizar" class="form-select" required>
                                <option value="">-- Selecciona una Categoría --</option>
                                <?php while ($cat_fila = $categorias_query->fetch_assoc()): ?>
                                    <option value="<?= $cat_fila['id'] ?>"><?= htmlspecialchars($cat_fila['nombre'] ?? '') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="porcentaje_aumento" class="form-control" placeholder="% Aumento" min="1" step="any" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card patrimonio -->
            <div class="card mb-4">
                <div class="card-header">Calcular Patrimonio</div>
                <div class="card-body">
                    <form class="d-flex gap-2" method="GET" action="">
                        <input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda ?? '') ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id ?? '') ?>">
                        <input type="hidden" name="stock" value="<?= htmlspecialchars($stock ?? '') ?>">
                        <button type="submit" name="patrimonio" value="real" class="btn btn-outline-primary">📊 Real</button>
                        <button type="submit" name="patrimonio" value="potencial" class="btn btn-outline-info">📈 Potencial</button>
                    </form>
                    <?php if ($patrimonio_tipo): ?>
                        <div class="mt-3">
                            <strong>
                                <?= $patrimonio_tipo === "real" 
                                    ? "Patrimonio Real (stock × compra): $" . number_format($patrimonio, 2)
                                    : "Patrimonio Potencial (stock × venta): $" . number_format($patrimonio, 2) ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card tabla -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Lista de Productos</span>
        <a href="agregar_producto.php" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle"></i> Agregar Producto
        </a>
    </div>
    <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Precio Costo</th>
                                <th>Precio Venta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($resultado && $resultado->num_rows > 0): ?>
                            <?php while ($fila = $resultado->fetch_assoc()): ?>
                                <tr class="<?= (int)$fila['stock_existencia'] <= 0 ? 'table-danger' : 'table-success' ?>">
                                    <td><?= $fila['id'] ?></td>
                                    <td><?= htmlspecialchars($fila['nombre'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($fila['descripcion'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($fila['categoria_nombre'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($fila['stock_existencia'] ?? '') ?></td>
                                    <td>$<?= number_format((float)($fila['precio_costo'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float)($fila['precio_venta'] ?? 0), 2) ?></td>
                                    <td><a href="editar_producto.php?id=<?= $fila['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No se encontraron productos con los filtros actuales.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
