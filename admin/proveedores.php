<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Búsqueda por ID o nombre (q en GET)
$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$proveedores = null;

if ($q !== "") {
    if (ctype_digit($q)) {
        $id = (int)$q;
        $stmt = $conn->prepare("SELECT * FROM proveedores WHERE id=? OR nombre LIKE ? ORDER BY nombre ASC");
        $like = "%$q%";
        $stmt->bind_param("is", $id, $like);
    } else {
        $stmt = $conn->prepare("SELECT * FROM proveedores WHERE nombre LIKE ? ORDER BY nombre ASC");
        $like = "%$q%";
        $stmt->bind_param("s", $like);
    }
    $stmt->execute();
    $proveedores = $stmt->get_result();
} else {
    $proveedores = $conn->query("SELECT * FROM proveedores ORDER BY nombre ASC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores</title>
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
                <p class="text-white-50 small">Proveedores</p>
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

        <!-- Main content -->
        <div class="col-md-10 p-4">
            <!-- Top bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Proveedores</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
                </div>
            </div>

            <!-- Card búsqueda -->
            <div class="card mb-4">
                <div class="card-header">Buscar Proveedor</div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="q" class="form-control" placeholder="Buscar por ID o nombre..." value="<?= htmlspecialchars($q) ?>">
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <a href="agregar_proveedor.php" class="btn btn-success">+ Nuevo Proveedor</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Mensaje si no hay resultados -->
            <?php if ($q !== "" && $proveedores->num_rows === 0): ?>
                <div class="alert alert-warning">⚠️ No se encontraron proveedores con el criterio "<strong><?= htmlspecialchars($q) ?></strong>".</div>
            <?php endif; ?>

            <!-- Tabla de proveedores -->
            <div class="card">
                <div class="card-header">Listado de Proveedores</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-danger">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Localidad</th>
                                <th>Email</th>
                                <th>CUIT</th>
                                <th>Resp. Inscripto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($proveedores && $proveedores->num_rows > 0): ?>
                            <?php while($p = $proveedores->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['localidad']) ?></td>
                                    <td><?= htmlspecialchars($p['email']) ?></td>
                                    <td><?= htmlspecialchars($p['cuil']) ?></td>
                                    <td>
                                        <?php if ((int)$p['responsable_inscripto'] === 1): ?>
                                            <span class="badge bg-success">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ver_proveedor.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        <a href="editar_proveedor.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No hay proveedores cargados.</td></tr>
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