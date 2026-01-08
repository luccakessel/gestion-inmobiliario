<?php
// clientes_fiados.php
include("../includes/db.php"); // ajusta la ruta según tu estructura

// Registrar nuevo fiado (usa clientes_fiados, ventas y ventas_fiadas)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']); // se usará como teléfono si la tabla no tiene dni
    $producto_id = intval($_POST['producto']); // id del producto

    // Obtener nombre y precio_venta del producto
    $stmt = $conn->prepare("SELECT nombre, precio_venta FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->bind_result($producto_nombre, $precio);
    $stmt->fetch();
    $stmt->close();

    if (!$producto_nombre) {
        // producto no encontrado, no insertar
    } else {
        // Buscar cliente existente (por nombre + teléfono)
        $stmt = $conn->prepare("SELECT id, saldo_actual FROM clientes_fiados WHERE nombre = ? AND telefono = ?");
        $stmt->bind_param("ss", $nombre, $dni);
        $stmt->execute();
        $stmt->bind_result($cliente_id, $saldo_actual);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            // Insertar nuevo cliente fiado
            $stmt = $conn->prepare("INSERT INTO clientes_fiados (nombre, telefono) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $dni);
            $stmt->execute();
            $cliente_id = $conn->insert_id;
            $saldo_actual = 0.00;
            $stmt->close();
        }

        // Insertar en ventas (cantidad 1 por defecto)
        $cantidad = 1;
        $stmt = $conn->prepare("INSERT INTO ventas (producto_id, cantidad, precio_unitario, fecha, metodo_pago) VALUES (?, ?, ?, NOW(), 'efectivo')");
        $stmt->bind_param("iid", $producto_id, $cantidad, $precio);
        $stmt->execute();
        $venta_id = $conn->insert_id;
        $stmt->close();

        // Insertar en ventas_fiadas
        $fecha_limite = date('Y-m-d', strtotime('+30 days'));
        $stmt = $conn->prepare("INSERT INTO ventas_fiadas (cliente_id, venta_id, monto, fecha_limite_pago, estado) VALUES (?, ?, ?, ?, 'pendiente')");
        $stmt->bind_param("iids", $cliente_id, $venta_id, $precio, $fecha_limite);
        $stmt->execute();
        $stmt->close();

        // Actualizar saldo del cliente
        $stmt = $conn->prepare("UPDATE clientes_fiados SET saldo_actual = saldo_actual + ? WHERE id = ?");
        $stmt->bind_param("di", $precio, $cliente_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Cambiar estado (cuando marcan como pagado) -> actualiza ventas_fiadas y saldo
if (isset($_GET['pagar'])) {
    $id = intval($_GET['pagar']);
    // Obtener info de la venta fiada
    $stmt = $conn->prepare("SELECT cliente_id, monto, estado FROM ventas_fiadas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($cliente_id_pay, $monto_pay, $estado_pay);
    $stmt->fetch();
    $stmt->close();

    if ($estado_pay === 'pendiente') {
        $stmt = $conn->prepare("UPDATE ventas_fiadas SET estado = 'pagada' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Reducir saldo del cliente
        $stmt = $conn->prepare("UPDATE clientes_fiados SET saldo_actual = GREATEST(0, saldo_actual - ?) WHERE id = ?");
        $stmt->bind_param("di", $monto_pay, $cliente_id_pay);
        $stmt->execute();
        $stmt->close();
    }
    // Redirigir para evitar reenvío accidental
    header("Location: clientes_fiados.php");
    exit();
}

// Obtener todos los fiados (ventas_fiadas join clientes, ventas y productos)
$fiados = $conn->query("SELECT vf.id, cf.nombre AS cliente, cf.telefono AS dni, p.nombre AS producto, v.precio_unitario AS precio, vf.fecha_limite_pago AS fecha, vf.estado
                        FROM ventas_fiadas vf
                        JOIN clientes_fiados cf ON vf.cliente_id = cf.id
                        JOIN ventas v ON vf.venta_id = v.id
                        JOIN productos p ON v.producto_id = p.id
                        ORDER BY vf.id DESC");

// Obtener lista de productos (usar precio_venta)
$productos = $conn->query("SELECT id, nombre, precio_venta AS precio FROM productos ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Fiados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #c62828; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: var(--primary-color); color: white; padding: 20px 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 10px 20px; margin: 5px 0; border-radius: 5px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .pagado { color: green; font-weight: bold; }
        .pendiente { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Carnicería</h4>
                <p class="text-white-50 small">Clientes Fiados</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link active"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="col-md-10 p-4">
            <!-- Top bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Clientes Fiados</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
                </div>
            </div>

            <!-- Formulario nuevo fiado -->
            <div class="card mb-4">
                <div class="card-header">Registrar Cliente Fiado</div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-4"><input type="text" name="nombre" class="form-control" placeholder="Nombre del cliente" required></div>
                        <div class="col-md-4"><input type="text" name="dni" class="form-control" placeholder="DNI" required></div>
                        <div class="col-md-4">
                            <select name="producto" class="form-select" required>
                                <option value="">Seleccionar producto</option>
                                <?php while ($p = $productos->fetch_assoc()): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> - $<?= number_format($p['precio'], 2) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="registrar" class="btn btn-success">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de fiados -->
            <div class="card">
                <div class="card-header">Lista de Fiados</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Fecha Límite</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $fiados->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['cliente']) ?></td>
                                    <td><?= htmlspecialchars($row['dni']) ?></td>
                                    <td><?= htmlspecialchars($row['producto']) ?></td>
                                    <td>$<?= number_format($row['precio'], 2) ?></td>
                                    <td><?= $row['fecha'] ?></td>
                                    <td class="<?= strtolower($row['estado']) ?>"><?= ucfirst($row['estado']) ?></td>
                                    <td>
                                        <?php if ($row['estado'] == 'pendiente'): ?>
                                            <a class="btn btn-sm btn-primary" href="?pagar=<?= $row['id'] ?>">Marcar como Pagado</a>
                                        <?php else: ?>
                                            <span class="badge bg-success">✔ Pagado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
