<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_factura':
                $cliente_id = intval($_POST['cliente_id']);
                $caso_id = !empty($_POST['caso_id']) ? intval($_POST['caso_id']) : null;
                $fecha_emision = $_POST['fecha_emision'];
                $fecha_vencimiento = $_POST['fecha_vencimiento'];
                $metodo_pago = $_POST['metodo_pago'];
                $observaciones = trim($_POST['observaciones']);
                
                // Generar número de factura
                $numero_factura = 'FAC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Calcular totales
                $subtotal = 0;
                $impuestos = 0;
                $total = 0;
                
                if (isset($_POST['servicios'])) {
                    foreach ($_POST['servicios'] as $servicio) {
                        $cantidad = floatval($servicio['cantidad']);
                        $precio = floatval($servicio['precio']);
                        $subtotal += $cantidad * $precio;
                    }
                }
                
                $impuestos = $subtotal * 0.21; // IVA 21%
                $total = $subtotal + $impuestos;
                
                // Insertar factura
                $stmt = $conn->prepare("INSERT INTO facturas (numero_factura, cliente_id, caso_id, fecha_emision, fecha_vencimiento, subtotal, impuestos, total, metodo_pago, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida')");
                $stmt->bind_param("siisssddss", $numero_factura, $cliente_id, $caso_id, $fecha_emision, $fecha_vencimiento, $subtotal, $impuestos, $total, $metodo_pago, $observaciones);
                
                if ($stmt->execute()) {
                    $factura_id = $conn->insert_id;
                    
                    // Insertar detalles de factura
                    if (isset($_POST['servicios'])) {
                        foreach ($_POST['servicios'] as $servicio) {
                            $servicio_id = intval($servicio['id']);
                            $cantidad = floatval($servicio['cantidad']);
                            $precio = floatval($servicio['precio']);
                            $total_item = $cantidad * $precio;
                            $descripcion = $servicio['descripcion'];
                            
                            $stmt_detalle = $conn->prepare("INSERT INTO detalle_facturas (factura_id, servicio_id, descripcion, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt_detalle->bind_param("iisddd", $factura_id, $servicio_id, $descripcion, $cantidad, $precio, $total_item);
                            $stmt_detalle->execute();
                        }
                    }
                    
                    $mensaje = "Factura creada exitosamente: " . $numero_factura;
                } else {
                    $error = "Error al crear la factura: " . $stmt->error;
                }
                break;
                
            case 'marcar_pagada':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE facturas SET estado = 'pagada' WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Factura marcada como pagada";
                } else {
                    $error = "Error al actualizar la factura: " . $stmt->error;
                }
                break;
            
            case 'eliminar_factura':
                $id = intval($_POST['id']);
                // borrar detalles primero
                $stmt = $conn->prepare("DELETE FROM detalle_facturas WHERE factura_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // borrar factura
                $stmt = $conn->prepare("DELETE FROM facturas WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $mensaje = "Factura eliminada";
                } else {
                    $error = "Error al eliminar la factura: " . $stmt->error;
                }
                break;
                
            case 'duplicar_factura':
                $id = intval($_POST['id']);
                
                // Obtener factura original
                $stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $factura_original = $stmt->get_result()->fetch_assoc();
                
                if ($factura_original) {
                    // Generar nuevo número de factura
                    $numero_factura = 'FAC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    // Crear nueva factura
                    $stmt = $conn->prepare("INSERT INTO facturas (numero_factura, cliente_id, caso_id, fecha_emision, fecha_vencimiento, subtotal, impuestos, total, metodo_pago, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'borrador')");
                    $stmt->bind_param("siisssddss", $numero_factura, $factura_original['cliente_id'], $factura_original['caso_id'], $factura_original['fecha_emision'], $factura_original['fecha_vencimiento'], $factura_original['subtotal'], $factura_original['impuestos'], $factura_original['total'], $factura_original['metodo_pago'], $factura_original['observaciones']);
                    
                    if ($stmt->execute()) {
                        $nueva_factura_id = $conn->insert_id;
                        
                        // Duplicar detalles
                        $stmt_detalles = $conn->prepare("SELECT * FROM detalle_facturas WHERE factura_id = ?");
                        $stmt_detalles->bind_param("i", $id);
                        $stmt_detalles->execute();
                        $detalles = $stmt_detalles->get_result();
                        
                        while ($detalle = $detalles->fetch_assoc()) {
                            $stmt_insert = $conn->prepare("INSERT INTO detalle_facturas (factura_id, servicio_id, descripcion, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt_insert->bind_param("iisddd", $nueva_factura_id, $detalle['servicio_id'], $detalle['descripcion'], $detalle['cantidad'], $detalle['precio_unitario'], $detalle['total']);
                            $stmt_insert->execute();
                        }
                        
                        $mensaje = "Factura duplicada exitosamente: " . $numero_factura;
                    } else {
                        $error = "Error al duplicar la factura: " . $stmt->error;
                    }
                } else {
                    $error = "Factura no encontrada";
                }
                break;
                
            case 'cambiar_estado':
                $id = intval($_POST['id']);
                $nuevo_estado = $_POST['nuevo_estado'];
                
                $stmt = $conn->prepare("UPDATE facturas SET estado = ? WHERE id = ?");
                $stmt->bind_param("si", $nuevo_estado, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Estado de factura actualizado a: " . ucfirst($nuevo_estado);
                } else {
                    $error = "Error al actualizar el estado: " . $stmt->error;
                }
                break;
                
            case 'reiniciar_contador':
                // Obtener el último número de factura del año actual
                $anio_actual = date('Y');
                $stmt = $conn->prepare("SELECT numero_factura FROM facturas WHERE numero_factura LIKE ? ORDER BY id DESC LIMIT 1");
                $patron = 'FAC-' . $anio_actual . '-%';
                $stmt->bind_param("s", $patron);
                $stmt->execute();
                $ultima_factura = $stmt->get_result()->fetch_assoc();
                
                if ($ultima_factura) {
                    // Extraer el número actual
                    $numero_actual = intval(substr($ultima_factura['numero_factura'], -4));
                    $mensaje = "El contador actual de facturas para " . $anio_actual . " es: " . str_pad($numero_actual, 4, '0', STR_PAD_LEFT);
                } else {
                    $mensaje = "No hay facturas registradas para el año " . $anio_actual;
                }
                break;
        }
    }
}

// Prefill desde caso
$pref_cliente = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
$pref_caso = isset($_GET['caso_id']) ? intval($_GET['caso_id']) : null;
$open_modal = isset($_GET['open']) && $_GET['open'] == '1';

// Obtener filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$estado = $_GET['estado'] ?? '';
$cliente = $_GET['cliente'] ?? '';

// Obtener clientes
$clientes = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");

// Obtener servicios
$servicios = $conn->query("SELECT id, nombre, precio_base, precio_por_hora FROM servicios_legales ORDER BY nombre");

// Construir consulta de facturas
$sql = "SELECT f.*, 
               CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
               cas.numero_expediente
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN casos cas ON f.caso_id = cas.id
        WHERE f.fecha_emision BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$types = "ss";

if ($estado !== '') {
    $sql .= " AND f.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

if ($cliente !== '') {
    $sql .= " AND f.cliente_id = ?";
    $params[] = intval($cliente);
    $types .= "i";
}

$sql .= " ORDER BY f.fecha_emision DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$facturas = $stmt->get_result();

// Calcular estadísticas
$stats_query = $conn->query("SELECT 
    COUNT(*) as total_facturas,
    SUM(CASE WHEN estado = 'emitida' THEN total ELSE 0 END) as pendiente_cobro,
    SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as cobrado,
    AVG(total) as promedio_factura
    FROM facturas 
    WHERE fecha_emision BETWEEN '" . $fecha_desde . "' AND '" . $fecha_hasta . "'");

$stats = $stats_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturación - Gestión Inmobiliaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
            --accent-color: #4a90e2;
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
        
        .estado-borrador { color: #6c757d; font-weight: bold; }
        .estado-emitida { color: #007bff; font-weight: bold; }
        .estado-pagada { color: #28a745; font-weight: bold; }
        .estado-vencida { color: #dc3545; font-weight: bold; }
        .estado-cancelada { color: #6c757d; font-weight: bold; }
        
        .servicio-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .estado-borrador { 
            color: #6c757d; 
            font-weight: bold; 
            background-color: #f8f9fa;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .estado-emitida { 
            color: #0d6efd; 
            font-weight: bold; 
            background-color: #e7f3ff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .estado-pagada { 
            color: #198754; 
            font-weight: bold; 
            background-color: #d1e7dd;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .estado-vencida { 
            color: #dc3545; 
            font-weight: bold; 
            background-color: #f8d7da;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .estado-cancelada { 
            color: #6c757d; 
            font-weight: bold; 
            background-color: #e2e3e5;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        @media print {
            .sidebar, .btn, .modal { display: none !important; }
            .col-md-10 { width: 100% !important; }
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
                <p class="text-white-50 small">Facturación</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link"><i class="bi bi-calendar-event"></i> Citas</a></li>
                <li class="nav-item"><a href="servicios.php" class="nav-link"><i class="bi bi-briefcase"></i> Servicios</a></li>
                <li class="nav-item"><a href="facturacion.php" class="nav-link active"><i class="bi bi-receipt"></i> Facturación</a></li>
                <li class="nav-item"><a href="documentos.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Documentos</a></li>
                <li class="nav-item"><a href="reportes.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Facturación</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?>
                    </span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>

            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?= $stats['total_facturas'] ?></h5>
                            <p class="card-text">Total Facturas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">$<?= number_format($stats['pendiente_cobro'], 2) ?></h5>
                            <p class="card-text">Pendiente de Cobro</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">$<?= number_format($stats['cobrado'], 2) ?></h5>
                            <p class="card-text">Cobrado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">$<?= number_format($stats['promedio_factura'], 2) ?></h5>
                            <p class="card-text">Promedio por Factura</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">Filtros</div>
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">-- Todos --</option>
                                <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                                <option value="emitida" <?= $estado === 'emitida' ? 'selected' : '' ?>>Emitida</option>
                                <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                                <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencida</option>
                                <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cliente</label>
                            <select name="cliente" class="form-select">
                                <option value="">-- Todos --</option>
                                <?php 
                                $clientes_filtro = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");
                                while ($cliente_filtro = $clientes_filtro->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cliente_filtro['id'] ?>" <?= $cliente == $cliente_filtro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cliente_filtro['nombre_completo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="mb-4 d-flex flex-wrap gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearFactura">
                    <i class="bi bi-plus-circle"></i> Nueva Factura
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalReiniciarContador">
                    <i class="bi bi-arrow-clockwise"></i> Ver Contador
                </button>
                <button class="btn btn-warning" onclick="exportarFacturas()">
                    <i class="bi bi-download"></i> Exportar
                </button>
                <button class="btn btn-secondary" onclick="imprimirFacturas()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>

            <!-- Lista de Facturas -->
            <div class="card">
                <div class="card-header">
                    <span>Lista de Facturas</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Propiedad</th>
                                <th>Fecha Emisión</th>
                                <th>Fecha Vencimiento</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Método Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($facturas && $facturas->num_rows > 0): ?>
                                <?php while ($factura = $facturas->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($factura['numero_factura']) ?></strong></td>
                                        <td><?= htmlspecialchars($factura['cliente_nombre']) ?></td>
                                        <td><?= htmlspecialchars($factura['numero_expediente'] ?? 'N/A') ?></td>
                                        <td><?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($factura['fecha_vencimiento'])) ?></td>
                                        <td>$<?= number_format($factura['total'], 2) ?></td>
                                        <td>
                                            <span class="estado-<?= $factura['estado'] ?>">
                                                <?= ucfirst($factura['estado']) ?>
                                            </span>
                                        </td>
                                        <td><?= ucfirst($factura['metodo_pago']) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="ver_factura.php?id=<?= $factura['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary" onclick="duplicarFactura(<?= $factura['id'] ?>)" title="Duplicar">
                                                    <i class="bi bi-files"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="cambiarEstado(<?= $factura['id'] ?>, '<?= $factura['estado'] ?>')" title="Cambiar Estado">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <?php if ($factura['estado'] === 'emitida'): ?>
                                                    <button class="btn btn-sm btn-outline-success" onclick="marcarPagada(<?= $factura['id'] ?>)" title="Marcar como Pagada">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarFactura(<?= $factura['id'] ?>)" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center">No se encontraron facturas con los filtros actuales.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Factura -->
<div class="modal fade" id="modalCrearFactura" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formFactura">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_factura">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required onchange="cargarCasos()">
                                <option value="">Seleccionar cliente</option>
                                <?php 
                                $clientes_modal = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");
                                while ($cliente = $clientes_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cliente['id'] ?>" <?= ($pref_cliente && $pref_cliente==$cliente['id'])?'selected':'' ?>><?= htmlspecialchars($cliente['nombre_completo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Propiedad (Opcional)</label>
                            <select name="caso_id" class="form-select" id="casosSelect">
                                <option value="">Seleccionar caso</option>
                                <?php if ($pref_caso):
                                    $caso_pref = $conn->query("SELECT id, numero_expediente, titulo FROM casos WHERE id=".$pref_caso)->fetch_assoc();
                                    if ($caso_pref): ?>
                                        <option value="<?= $caso_pref['id'] ?>" selected><?= htmlspecialchars($caso_pref['numero_expediente'].' - '.$caso_pref['titulo']) ?></option>
                                    <?php endif; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Método de Pago *</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Emisión *</label>
                            <input type="date" name="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Vencimiento *</label>
                            <input type="date" name="fecha_vencimiento" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="mb-4">
                        <h6>Servicios</h6>
                        <div id="serviciosContainer">
                            <div class="servicio-item">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Servicio</label>
                                        <select name="servicios[0][id]" class="form-select servicio-select" onchange="actualizarPrecio(this)">
                                            <option value="">Seleccionar servicio</option>
                                            <?php 
                                            $servicios_modal = $conn->query("SELECT id, nombre, precio_base, precio_por_hora FROM servicios_legales ORDER BY nombre");
                                            while ($servicio = $servicios_modal->fetch_assoc()): 
                                            ?>
                                                <option value="<?= $servicio['id'] ?>" data-precio-base="<?= $servicio['precio_base'] ?>" data-precio-hora="<?= $servicio['precio_por_hora'] ?>">
                                                    <?= htmlspecialchars($servicio['nombre']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" name="servicios[0][cantidad]" class="form-control" min="0" step="0.5" value="1" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Precio Unit.</label>
                                        <input type="number" name="servicios[0][precio]" class="form-control precio-unitario" min="0" step="0.01" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" name="servicios[0][descripcion]" class="form-control">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-danger w-100" onclick="eliminarServicio(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary" onclick="agregarServicio()">
                            <i class="bi bi-plus"></i> Agregar Servicio
                        </button>
                    </div>

                    <!-- Totales -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Resumen de Factura</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">$0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>IVA (21%):</span>
                                        <span id="impuestos">$0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="total">$0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Factura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Contador -->
<div class="modal fade" id="modalReiniciarContador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contador de Facturas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="reiniciar_contador">
                    <p>¿Desea verificar el contador actual de facturas para el año <?= date('Y') ?>?</p>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Esta acción mostrará el último número de factura generado para el año actual.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Ver Contador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado de Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formCambiarEstado">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="id" id="facturaIdEstado">
                    <div class="mb-3">
                        <label class="form-label">Nuevo Estado</label>
                        <select name="nuevo_estado" class="form-select" required>
                            <option value="borrador">Borrador</option>
                            <option value="emitida">Emitida</option>
                            <option value="pagada">Pagada</option>
                            <option value="vencida">Vencida</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cambiar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let servicioIndex = 1;

function cargarCasos() {
    const clienteId = document.querySelector('select[name="cliente_id"]').value;
    const casosSelect = document.getElementById('casosSelect');
    
    if (clienteId) {
        // Aquí se haría una llamada AJAX para cargar los casos del cliente
        // Por simplicidad, se mantiene la lista completa
    }
}

function actualizarPrecio(select) {
    const option = select.selectedOptions[0];
    const precioBase = option.dataset.precioBase;
    const precioHora = option.dataset.precioHora;
    
    const precioInput = select.closest('.servicio-item').querySelector('.precio-unitario');
    precioInput.value = precioBase || precioHora || 0;
    
    calcularTotal();
}

function agregarServicio() {
    const container = document.getElementById('serviciosContainer');
    const newServicio = container.querySelector('.servicio-item').cloneNode(true);
    
    // Actualizar índices
    newServicio.innerHTML = newServicio.innerHTML.replace(/servicios\[0\]/g, `servicios[${servicioIndex}]`);
    
    // Limpiar valores
    newServicio.querySelectorAll('input, select').forEach(input => {
        if (input.type !== 'hidden') {
            input.value = '';
        }
    });
    
    container.appendChild(newServicio);
    servicioIndex++;
}

function eliminarServicio(button) {
    if (document.querySelectorAll('.servicio-item').length > 1) {
        button.closest('.servicio-item').remove();
        calcularTotal();
    }
}

function calcularTotal() {
    let subtotal = 0;
    
    document.querySelectorAll('.servicio-item').forEach(item => {
        const cantidad = parseFloat(item.querySelector('input[name*="[cantidad]"]').value) || 0;
        const precio = parseFloat(item.querySelector('input[name*="[precio]"]').value) || 0;
        subtotal += cantidad * precio;
    });
    
    const impuestos = subtotal * 0.21;
    const total = subtotal + impuestos;
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('impuestos').textContent = '$' + impuestos.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

function marcarPagada(id) {
    if (confirm('¿Marcar esta factura como pagada?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="marcar_pagada">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function eliminarFactura(id) {
    if (confirm('¿Eliminar esta factura?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar_factura">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function duplicarFactura(id) {
    if (confirm('¿Duplicar esta factura?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="duplicar_factura">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function cambiarEstado(id, estadoActual) {
    document.getElementById('facturaIdEstado').value = id;
    document.querySelector('select[name="nuevo_estado"]').value = estadoActual;
    new bootstrap.Modal(document.getElementById('modalCambiarEstado')).show();
}

function exportarFacturas() {
    // Crear tabla temporal para exportar
    const tabla = document.querySelector('.table');
    const filas = tabla.querySelectorAll('tbody tr');
    
    let csv = 'Número,Cliente,Propiedad,Fecha Emisión,Fecha Vencimiento,Total,Estado,Método Pago\n';
    
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length > 1) {
            const filaData = Array.from(celdas).map(celda => {
                let texto = celda.textContent.trim();
                // Limpiar texto de botones
                texto = texto.replace(/Ver|Duplicar|Cambiar|Marcar|Eliminar/g, '');
                return `"${texto}"`;
            }).join(',');
            csv += filaData + '\n';
        }
    });
    
    // Descargar archivo
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `facturas_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

function imprimirFacturas() {
    window.print();
}

// Inicializar cálculo
document.addEventListener('DOMContentLoaded', function() {
    calcularTotal();
});
</script>
</body>
</html>
