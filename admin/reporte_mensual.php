<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Filtros
$filtroMes = $_GET['mes'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';

$condiciones = [];

if ($filtroMes) {
    $condiciones[] = "MONTH(v.fecha) = " . intval($filtroMes) . " AND YEAR(v.fecha) = YEAR(CURRENT_DATE())";
}
if ($filtroFecha) {
    $condiciones[] = "DATE(v.fecha) = '" . $conn->real_escape_string($filtroFecha) . "'";
}

$where = $condiciones ? "WHERE " . implode(" AND ", $condiciones) : "WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE()) AND YEAR(v.fecha) = YEAR(CURRENT_DATE())";

// Usamos el precio_unitario de la venta y añadimos la categoría
$sql = "SELECT v.id, v.fecha, p.nombre, c.nombre AS categoria, v.cantidad, v.precio_unitario, 
        (v.cantidad * v.precio_unitario) AS total, v.metodo_pago
        FROM ventas v
        JOIN productos p ON v.producto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        $where
        ORDER BY v.fecha DESC";

$resultado = $conn->query($sql);

$totalIngresos = 0;
$productos = [];
$ventas = [];

while ($fila = $resultado->fetch_assoc()) {
    $totalIngresos += $fila['total'];
    if (!isset($productos[$fila['nombre']])) {
        $productos[$fila['nombre']] = 0;
    }
    $productos[$fila['nombre']] += $fila['total'];
    $ventas[] = $fila;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <p class="text-white-50 small">Reportes</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link active"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="col-md-10 p-4">
            <!-- Top bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Reporte Mensual de Ventas</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">Filtros</div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="mes" class="form-label">Mes:</label>
                            <select name="mes" id="mes" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <?php for ($i=1; $i<=12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($filtroMes==$i?'selected':'') ?>>
                                        <?= date("F", mktime(0,0,0,$i,1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha" class="form-label">Fecha exacta:</label>
                            <input type="date" name="fecha" id="fecha" value="<?= $filtroFecha ?>" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                            <a href="reporte_mensual.php" class="btn btn-secondary">Limpiar</a>
                            <a href="sucursales.php" class="btn btn-dark ms-2">👥 Sucursales</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de ventas -->
            <div class="card mb-4">
                <div class="card-header">Listado de Ventas</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-danger">
                            <tr>
                                <th>ID Venta</th>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Total</th>
                                <th>Método de Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ventas as $fila): ?>
                            <tr>
                                <td><?= $fila['id'] ?></td>
                                <td><?= $fila['fecha'] ?></td>
                                <td><?= $fila['nombre'] ?></td>
                                <td><?= $fila['categoria'] ?></td>
                                <td><?= $fila['cantidad'] ?> kg</td>
                                <td>$<?= number_format($fila['precio_unitario'], 2) ?></td>
                                <td>$<?= number_format($fila['total'], 2) ?></td>
                                <td><?= ucfirst($fila['metodo_pago']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <h5 class="text-center text-danger mt-3">Total de Ingresos: $<?= number_format($totalIngresos, 2) ?></h5>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">Ingresos por Producto</div>
                        <div class="card-body">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">Distribución de Ventas</div>
                        <div class="card-body">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const labels = <?= json_encode(array_keys($productos)) ?>;
    const data = <?= json_encode(array_values($productos)) ?>;

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: { labels: labels, datasets: [{ label: 'Ingresos por Producto', data: data, backgroundColor: 'rgba(75, 192, 192, 0.6)', borderRadius: 8 }] },
        options: { responsive: true }
    });

    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: { labels: labels, datasets: [{ data: data, backgroundColor: ['rgba(255, 99, 132, 0.6)','rgba(54, 162, 235, 0.6)','rgba(255, 206, 86, 0.6)','rgba(75, 192, 192, 0.6)','rgba(153, 102, 255, 0.6)','rgba(255, 159, 64, 0.6)'] }] },
        options: { responsive: true }
    });
</script>
</body>
</html>
