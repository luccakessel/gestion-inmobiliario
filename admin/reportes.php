<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Filtros
$filtroMes = $_GET['mes'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';
$filtroEspecialidad = $_GET['especialidad'] ?? '';

$condiciones = [];

if ($filtroMes) {
    $condiciones[] = "MONTH(sp.fecha_servicio) = " . intval($filtroMes) . " AND YEAR(sp.fecha_servicio) = YEAR(CURRENT_DATE())";
}
if ($filtroFecha) {
    $condiciones[] = "DATE(sp.fecha_servicio) = '" . $conn->real_escape_string($filtroFecha) . "'";
}
if ($filtroEspecialidad) {
    $condiciones[] = "e.id = " . intval($filtroEspecialidad);
}

$where = $condiciones ? "WHERE " . implode(" AND ", $condiciones) : "WHERE MONTH(sp.fecha_servicio) = MONTH(CURRENT_DATE()) AND YEAR(sp.fecha_servicio) = YEAR(CURRENT_DATE())";

// Consulta para servicios prestados
$sql = "SELECT sp.id, sp.fecha_servicio, sl.nombre AS servicio, e.nombre AS especialidad, 
        c.nombre AS cliente, sp.total AS honorarios, sp.estado_pago AS estado
        FROM servicios_prestados sp
        JOIN servicios_legales sl ON sp.servicio_id = sl.id
        JOIN especialidades e ON sl.especialidad_id = e.id
        JOIN casos ca ON sp.caso_id = ca.id
        JOIN clientes c ON ca.cliente_id = c.id
        $where
        ORDER BY sp.fecha_servicio DESC";

$resultado = $conn->query($sql);

$totalHonorarios = 0; // Suma combinada de servicios prestados + facturas pagadas
$servicios = [];
$especialidades = [];
$servicios_prestados = [];

while ($fila = $resultado->fetch_assoc()) {
    $totalHonorarios += (float)$fila['honorarios'];
    if (!isset($servicios[$fila['servicio']])) {
        $servicios[$fila['servicio']] = 0;
    }
    $servicios[$fila['servicio']] += (float)$fila['honorarios'];
    
    if (!isset($especialidades[$fila['especialidad']])) {
        $especialidades[$fila['especialidad']] = 0;
    }
    $especialidades[$fila['especialidad']] += (float)$fila['honorarios'];
    
    $servicios_prestados[] = $fila;
}

// ==============================
// Agregar FACTURAS PAGADAS (emitidas y cobradas) al reporte
// ==============================
// Construir condiciones para facturas en base a los mismos filtros, usando fecha_emision
$condFact = [];
if ($filtroMes) {
    $condFact[] = "MONTH(f.fecha_emision) = " . intval($filtroMes) . " AND YEAR(f.fecha_emision) = YEAR(CURRENT_DATE())";
}
if ($filtroFecha) {
    $condFact[] = "DATE(f.fecha_emision) = '" . $conn->real_escape_string($filtroFecha) . "'";
}
// Solo facturas pagadas
$condFact[] = "f.estado = 'pagada'";

// Filtro por especialidad si corresponde (desde servicios del detalle)
$condEspJoin = "";
if ($filtroEspecialidad) {
    $condFact[] = "e.id = " . intval($filtroEspecialidad);
    $condEspJoin = "JOIN servicios_legales sl2 ON df.servicio_id = sl2.id\n                   JOIN especialidades e ON sl2.especialidad_id = e.id";
}

$whereFact = $condFact ? ("WHERE " . implode(" AND ", $condFact)) : "WHERE MONTH(f.fecha_emision) = MONTH(CURRENT_DATE()) AND YEAR(f.fecha_emision) = YEAR(CURRENT_DATE()) AND f.estado = 'pagada'";

// 1) Sumar por servicio desde detalle_facturas
$sqlFactServicios = "SELECT sl.nombre AS servicio, SUM(df.total) AS monto\n                      FROM detalle_facturas df\n                      JOIN facturas f ON df.factura_id = f.id\n                      JOIN servicios_legales sl ON df.servicio_id = sl.id\n                      $condEspJoin\n                      $whereFact\n                      GROUP BY sl.nombre";
$resFactServ = $conn->query($sqlFactServicios);
if ($resFactServ) {
    while ($r = $resFactServ->fetch_assoc()) {
        $nombreServ = $r['servicio'];
        $monto = (float)$r['monto'];
        if (!isset($servicios[$nombreServ])) $servicios[$nombreServ] = 0;
        $servicios[$nombreServ] += $monto;
        $totalHonorarios += $monto;
    }
}

// 2) Sumar por especialidad desde detalle_facturas
$sqlFactEsp = "SELECT e.nombre AS especialidad, SUM(df.total) AS monto\n                FROM detalle_facturas df\n                JOIN facturas f ON df.factura_id = f.id\n                JOIN servicios_legales sl ON df.servicio_id = sl.id\n                JOIN especialidades e ON sl.especialidad_id = e.id\n                $whereFact\n                GROUP BY e.nombre";
$resFactEsp = $conn->query($sqlFactEsp);
if ($resFactEsp) {
    while ($r = $resFactEsp->fetch_assoc()) {
        $nombreEsp = $r['especialidad'];
        $monto = (float)$r['monto'];
        if (!isset($especialidades[$nombreEsp])) $especialidades[$nombreEsp] = 0;
        $especialidades[$nombreEsp] += $monto;
    }
}

// Obtener especialidades para el filtro
$especialidades_query = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Gestión Inmobiliaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary-color: #1a365d; 
            --secondary-color: #2d5a87;
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
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">🏠 Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Reportes</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link"><i class="bi bi-calendar"></i> Citas</a></li>
                <li class="nav-item"><a href="servicios.php" class="nav-link"><i class="bi bi-briefcase"></i> Servicios</a></li>
                <li class="nav-item"><a href="facturacion.php" class="nav-link"><i class="bi bi-receipt"></i> Facturación</a></li>
                <li class="nav-item"><a href="documentos.php" class="nav-link"><i class="bi bi-file-text"></i> Documentos</a></li>
                <li class="nav-item"><a href="vencimientos.php" class="nav-link"><i class="bi bi-clock"></i> Vencimientos</a></li>
                <li class="nav-item"><a href="reportes.php" class="nav-link active"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="col-md-10 p-4">
            <!-- Top bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>📊 Reportes Inmobiliarios</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
                </div>
            </div>

            <!-- Estadísticas generales -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5><i class="bi bi-currency-dollar"></i> Ingresos del Mes</h5>
                        <h3>$<?= number_format($totalHonorarios, 2) ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5><i class="bi bi-briefcase"></i> Servicios/Extras</h5>
                        <h3><?= count($servicios_prestados) ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5><i class="bi bi-graph-up"></i> Tipos de Propiedad</h5>
                        <h3><?= count($especialidades) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">🔍 Filtros de Búsqueda</div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label for="fecha" class="form-label">Fecha exacta:</label>
                            <input type="date" name="fecha" id="fecha" value="<?= $filtroFecha ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="especialidad" class="form-label">Tipo de Propiedad:</label>
                            <select name="especialidad" id="especialidad" class="form-select">
                                <option value="">-- Todas --</option>
                                <?php while ($esp = $especialidades_query->fetch_assoc()): ?>
                                    <option value="<?= $esp['id'] ?>" <?= ($filtroEspecialidad==$esp['id']?'selected':'') ?>>
                                        <?= htmlspecialchars($esp['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                            <a href="reportes.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de servicios prestados -->
            <div class="card mb-4">
                <div class="card-header">📋 Listado de Servicios Prestados</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Especialidad</th>
                                <th>Cliente</th>
                                <th>Honorarios</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($servicios_prestados as $fila): ?>
                            <tr>
                                <td><?= $fila['id'] ?></td>
                                <td><?= date('d/m/Y', strtotime($fila['fecha_servicio'])) ?></td>
                                <td><?= htmlspecialchars($fila['servicio']) ?></td>
                                <td><?= htmlspecialchars($fila['especialidad']) ?></td>
                                <td><?= htmlspecialchars($fila['cliente']) ?></td>
                                <td>$<?= number_format($fila['honorarios'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $fila['estado'] == 'pagado' ? 'success' : ($fila['estado'] == 'parcial' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($fila['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <h5 class="text-center text-primary mt-3">💰 Total de Honorarios: $<?= number_format($totalHonorarios, 2) ?></h5>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">📈 Ingresos por Servicio/Extra</div>
                        <div class="card-body">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">🥧 Distribución por Tipo de Propiedad</div>
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
    // Gráfico de barras - Honorarios por servicio
    const serviceLabels = <?= json_encode(array_keys($servicios)) ?>;
    const serviceData = <?= json_encode(array_values($servicios)) ?>;

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: { 
            labels: serviceLabels, 
            datasets: [{ 
                label: 'Honorarios por Servicio', 
                data: serviceData, 
                backgroundColor: 'rgba(26, 54, 93, 0.6)', 
                borderRadius: 8 
            }] 
        },
        options: { 
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico circular - Distribución por especialidad
    const specialtyLabels = <?= json_encode(array_keys($especialidades)) ?>;
    const specialtyData = <?= json_encode(array_values($especialidades)) ?>;

    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: { 
            labels: specialtyLabels, 
            datasets: [{ 
                data: specialtyData, 
                backgroundColor: [
                    'rgba(26, 54, 93, 0.6)',
                    'rgba(45, 90, 135, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
                ] 
            }] 
        },
        options: { 
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
</body>
</html>
