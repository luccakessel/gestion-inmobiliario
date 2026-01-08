<?php
require_once "../includes/db.php";
session_start();

// Solo admin puede ver
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: registrar_venta.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: sucursales.php");
    exit;
}

$id = intval($_GET["id"]);

// Traer info de la sucursal
$stmt = $conn->prepare("SELECT username, nombre FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$sucursal = $res->fetch_assoc();

if (!$sucursal) {
    echo "Sucursal no encontrada.";
    exit;
}

// Filtros de fechas
$fecha_desde = $_GET['desde'] ?? "";
$fecha_hasta = $_GET['hasta'] ?? "";

// Base query
$query = "SELECT v.id, v.fecha, p.nombre AS producto, v.cantidad, v.precio_unitario, (v.cantidad * v.precio_unitario) AS total
          FROM ventas v
          JOIN productos p ON v.producto_id = p.id
          WHERE v.usuario_id = ?";

$params = [$id];
$types = "i";

if ($fecha_desde !== "") {
    $query .= " AND v.fecha >= ?";
    $params[] = $fecha_desde;
    $types .= "s";
}
if ($fecha_hasta !== "") {
    $query .= " AND v.fecha <= ?";
    $params[] = $fecha_hasta;
    $types .= "s";
}

$query .= " ORDER BY v.fecha DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ventas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas - <?= htmlspecialchars($sucursal['username']) ?></title>
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
                <p class="text-white-50 small">Ventas por Sucursal</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="sucursales.php" class="nav-link active"><i class="bi bi-building"></i> Sucursales</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="categoria.php" class="nav-link"><i class="bi bi-tags"></i> Categorías</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Ventas de <?= htmlspecialchars($sucursal['nombre'] ?: $sucursal['username']) ?></h4>
                <div>
                    <a href="sucursales.php" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Filtro -->
            <div class="card mb-4">
                <div class="card-header">Filtrar por Fecha</div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="col-md-5">
                            <label class="form-label">Desde</label>
                            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-header">Ventas Registradas</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>ID Venta</th>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ventas->num_rows > 0): ?>
                                <?php while ($v = $ventas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $v['id'] ?></td>
                                        <td><?= $v['fecha'] ?></td>
                                        <td><?= htmlspecialchars($v['producto']) ?></td>
                                        <td><?= $v['cantidad'] ?></td>
                                        <td>$<?= number_format($v['precio_unitario'], 2) ?></td>
                                        <td><strong>$<?= number_format($v['total'], 2) ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No se encontraron ventas en este período.</td>
                                </tr>
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
