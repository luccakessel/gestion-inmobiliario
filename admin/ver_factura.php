<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

$factura_id = intval($_GET['id']);

// Obtener factura
$stmt = $conn->prepare("SELECT f.*, 
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                       c.dni, c.telefono, c.email, c.direccion,
                       cas.numero_expediente, cas.titulo as caso_titulo
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN casos cas ON f.caso_id = cas.id
                WHERE f.id = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();

if (!$factura) {
    header("Location: facturacion.php");
    exit;
}

// Obtener detalles de la factura
$stmt = $conn->prepare("SELECT df.*, sl.nombre as servicio_nombre
                       FROM detalle_facturas df
                       LEFT JOIN servicios_legales sl ON df.servicio_id = sl.id
                       WHERE df.factura_id = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$detalles = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= htmlspecialchars($factura['numero_factura']) ?> - Gestión Inmobiliaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .factura-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .factura-header {
            background: linear-gradient(135deg, #1a365d, #2d5a87);
            color: white;
            padding: 30px;
        }
        
        .factura-body {
            padding: 30px;
        }
        
        .estado-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .estado-borrador { background-color: #f8f9fa; color: #6c757d; }
        .estado-emitida { background-color: #e7f3ff; color: #0d6efd; }
        .estado-pagada { background-color: #d1e7dd; color: #198754; }
        .estado-vencida { background-color: #f8d7da; color: #dc3545; }
        .estado-cancelada { background-color: #e2e3e5; color: #6c757d; }
        
        .table-factura {
            border: 1px solid #dee2e6;
        }
        
        .table-factura th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .totales {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        @media print {
            .no-print { display: none !important; }
            .factura-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="factura-container">
            <!-- Header -->
            <div class="factura-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">Gestión Inmobiliaria</h2>
                        <p class="mb-0">Sistema de Facturación</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <h3 class="mb-0">FACTURA</h3>
                        <h4 class="mb-0"><?= htmlspecialchars($factura['numero_factura']) ?></h4>
                        <span class="estado-badge estado-<?= $factura['estado'] ?>">
                            <?= ucfirst($factura['estado']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="factura-body">
                <!-- Información del Cliente -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Cliente:</h5>
                        <p class="mb-1"><strong><?= htmlspecialchars($factura['cliente_nombre']) ?></strong></p>
                        <?php if ($factura['dni']): ?>
                            <p class="mb-1">DNI: <?= htmlspecialchars($factura['dni']) ?></p>
                        <?php endif; ?>
                        <?php if ($factura['telefono']): ?>
                            <p class="mb-1">Tel: <?= htmlspecialchars($factura['telefono']) ?></p>
                        <?php endif; ?>
                        <?php if ($factura['email']): ?>
                            <p class="mb-1">Email: <?= htmlspecialchars($factura['email']) ?></p>
                        <?php endif; ?>
                        <?php if ($factura['direccion']): ?>
                            <p class="mb-1">Dirección: <?= htmlspecialchars($factura['direccion']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5>Información de la Factura:</h5>
                        <p class="mb-1"><strong>Fecha de Emisión:</strong> <?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></p>
                        <p class="mb-1"><strong>Fecha de Vencimiento:</strong> <?= date('d/m/Y', strtotime($factura['fecha_vencimiento'])) ?></p>
                        <p class="mb-1"><strong>Método de Pago:</strong> <?= ucfirst($factura['metodo_pago']) ?></p>
                        <?php if ($factura['numero_expediente']): ?>
                            <p class="mb-1"><strong>Propiedad:</strong> <?= htmlspecialchars($factura['numero_expediente']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Detalles de la Factura -->
                <div class="mb-4">
                    <h5>Detalles de la Factura:</h5>
                    <table class="table table-factura">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($detalle = $detalles->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detalle['servicio_nombre'] ?? 'Servicio') ?></td>
                                    <td><?= htmlspecialchars($detalle['descripcion']) ?></td>
                                    <td class="text-center"><?= number_format($detalle['cantidad'], 2) ?></td>
                                    <td class="text-end">$<?= number_format($detalle['precio_unitario'], 2) ?></td>
                                    <td class="text-end">$<?= number_format($detalle['total'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Totales -->
                <div class="row">
                    <div class="col-md-6">
                        <?php if ($factura['observaciones']): ?>
                            <h5>Observaciones:</h5>
                            <p><?= nl2br(htmlspecialchars($factura['observaciones'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="totales">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($factura['subtotal'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>IVA (21%):</span>
                                <span>$<?= number_format($factura['impuestos'], 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>TOTAL:</strong>
                                <strong>$<?= number_format($factura['total'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div class="text-center mt-4 no-print">
            <a href="facturacion.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Facturación
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
