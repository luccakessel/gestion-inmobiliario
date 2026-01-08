<?php
require_once "../includes/funciones.php";
require_once "../includes/db.php";
session_start();

// Solo admin puede ver
if ($_SESSION["rol"] !== "admin") {
    header("Location: registrar_venta.php");
    exit;
}

// Traer sucursales (usuarios con rol vendedor o empleado)
$sucursales = $conn->query("SELECT id, username, nombre FROM usuarios WHERE rol IN ('vendedor','empleado') ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Sucursales</title>
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
                <p class="text-white-50 small">Gestión de Sucursales</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="categoria.php" class="nav-link"><i class="bi bi-tags"></i> Categorías</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
                <li class="nav-item"><a href="sucursales.php" class="nav-link active"><i class="bi bi-building"></i> Sucursales</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Sucursales</h4>
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

            <!-- Mensaje si no hay sucursales -->
            <?php if ($sucursales->num_rows === 0): ?>
                <div class="alert alert-warning">⚠️ No hay sucursales registradas todavía.</div>
            <?php endif; ?>

            <!-- Tarjetas de sucursales -->
            <div class="row g-3">
                <?php while ($s = $sucursales->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= htmlspecialchars($s['username']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($s['nombre']) ?></p>
                                <a href="sucursal.php?id=<?= $s['id'] ?>" class="btn btn-success">
                                    <i class="bi bi-eye"></i> Ver Ventas
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
