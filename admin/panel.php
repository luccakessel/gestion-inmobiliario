<?php 
require_once("../includes/funciones.php"); 
require_once("../includes/db.php"); 
proteger();

// Verificar que el usuario tenga permisos de administrador
if ($_SESSION["rol"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Obtener el mes y año actual
$mesActual = date('m');
$anioActual = date('Y');

// =========================
// DATOS PARA EL DASHBOARD
// =========================

// Casos activos del mes actual
$sqlCasosActivos = "SELECT COUNT(*) AS total_casos_activos,
                           SUM(honorarios_cobrados) AS ingresos_totales,
                           (SELECT SUM(honorarios_cobrados) 
                            FROM casos c2 
                            WHERE MONTH(c2.fecha_inicio) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                            AND YEAR(c2.fecha_inicio) = YEAR(CURRENT_DATE())
                           ) AS ingresos_mes_anterior
                    FROM casos 
                    WHERE MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?";
$stmt = $conn->prepare($sqlCasosActivos);
$stmt->bind_param("ii", $mesActual, $anioActual);
$stmt->execute();
$resultCasos = $stmt->get_result()->fetch_assoc();

$totalCasosActivos = $resultCasos['total_casos_activos'] ?? 0;
$ingresosMensuales = $resultCasos['ingresos_totales'] ?? 0;
$ingresosMesAnterior = $resultCasos['ingresos_mes_anterior'] ?? 0;

// Calcular variación porcentual
$variacionIngresos = 0;
if ($ingresosMesAnterior > 0) {
    $variacionIngresos = (($ingresosMensuales - $ingresosMesAnterior) / $ingresosMesAnterior) * 100;
}

// Facturas pendientes de pago
$sqlFacturasPendientes = "SELECT f.numero_factura, f.total, f.fecha_vencimiento, 
                         CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre
                         FROM facturas f
                         JOIN clientes c ON f.cliente_id = c.id
                         WHERE f.estado = 'emitida' 
                         AND f.fecha_vencimiento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                         ORDER BY f.fecha_vencimiento ASC LIMIT 5";
$facturasPendientes = $conn->query($sqlFacturasPendientes);

// Casos por especialidad (para gráfico)
$sqlCasosPorEspecialidad = "SELECT e.nombre AS especialidad, 
                                   COUNT(c.id) AS cantidad_casos, 
                                   SUM(c.honorarios_cobrados) AS monto_total 
                            FROM casos c 
                            JOIN especialidades e ON c.especialidad_id = e.id 
                            WHERE MONTH(c.fecha_inicio) = ? AND YEAR(c.fecha_inicio) = ? 
                            GROUP BY e.nombre 
                            ORDER BY cantidad_casos DESC";
$stmt = $conn->prepare($sqlCasosPorEspecialidad);
$stmt->bind_param("ii", $mesActual, $anioActual);
$stmt->execute();
$casosPorEspecialidad = $stmt->get_result();

// Procesar datos para el gráfico
$especialidadesChart = [];
$datosChart = [];
$coloresChart = [];

while ($row = $casosPorEspecialidad->fetch_assoc()) {
    $especialidadesChart[] = '"' . htmlspecialchars($row['especialidad']) . '"';
    $datosChart[] = $row['cantidad_casos'];
    $coloresChart[] = '"#' . substr(md5($row['especialidad']), 0, 6) . '"';
}

$especialidadesJS = '[' . implode(',', $especialidadesChart) . ']';
$datosJS = '[' . implode(',', $datosChart) . ']';
$coloresJS = '[' . implode(',', $coloresChart) . ']';
?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gestión Inmobiliaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
            --accent-color: #4a90e2;
            --light-gray: #f5f5f5;
            --dark-gray: #333;
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
                <h4 class="text-white">Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Panel de Control</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="casos.php" class="nav-link">
                        <i class="bi bi-house"></i> Propiedades
                    </a>
                </li>
                <li class="nav-item">
                    <a href="clientes.php" class="nav-link">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="citas.php" class="nav-link">
                        <i class="bi bi-calendar-event"></i> Citas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="servicios.php" class="nav-link">
                        <i class="bi bi-briefcase"></i> Servicios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="facturacion.php" class="nav-link">
                        <i class="bi bi-receipt"></i> Facturación
                    </a>
                </li>
                <li class="nav-item">
                    <a href="documentos.php" class="nav-link">
                        <i class="bi bi-file-earmark-text"></i> Documentos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link">
                        <i class="bi bi-graph-up"></i> Reportes
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Panel de Control</h4>
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

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">INGRESOS DEL MES</h6>
                            <h3 class="mb-0">$<?= number_format($ingresosMensuales, 2) ?></h3>
                            <small class="text-<?= $variacionIngresos >= 0 ? 'success' : 'danger' ?>">
                                <i class="bi bi-arrow-<?= $variacionIngresos >= 0 ? 'up' : 'down' ?>-circle"></i>
                                <?= number_format(abs($variacionIngresos), 1) ?>% vs mes anterior
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">PROPIEDADES ACTIVAS</h6>
                            <h3 class="mb-0"><?= number_format($totalCasosActivos) ?></h3>
                            <small class="text-muted">Propiedades captadas este mes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">FACTURAS PENDIENTES</h6>
                            <h3 class="mb-0"><?= $facturasPendientes->num_rows ?? 0 ?></h3>
                            <small class="text-muted">Próximos 7 días</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico y Tabla -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Propiedades por Tipo</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="casosChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Facturas por Vencer</h6>
                            <a href="facturacion.php" class="btn btn-sm btn-outline-primary">Ver todo</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($facturasPendientes && $facturasPendientes->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($factura = $facturasPendientes->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($factura['numero_factura']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($factura['cliente_nombre']) ?></small>
                                            </div>
                                            <span class="badge bg-warning">$<?= number_format($factura['total'], 0) ?></span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No hay facturas próximas a vencer</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Acciones Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                            <a href="casos.php?accion=nuevo" class="btn btn-outline-primary w-100">
                                <i class="bi bi-house-add me-2"></i> Nueva Propiedad
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="clientes.php?accion=nuevo" class="btn btn-outline-success w-100">
                                        <i class="bi bi-person-plus me-2"></i> Nuevo Cliente
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="citas.php?accion=nueva" class="btn btn-outline-info w-100">
                                        <i class="bi bi-calendar-plus me-2"></i> Nueva Cita
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="facturacion.php?accion=nueva" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-receipt me-2"></i> Nueva Factura
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('casosChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= $especialidadesJS ?>,
            datasets: [{
                data: <?= $datosJS ?>,
                backgroundColor: <?= $coloresJS ?>,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 10
                    }
                }
            },
            cutout: '65%',
            radius: '90%'
        }
    });
});
</script>
</body>
</html>
