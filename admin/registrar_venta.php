<?php
session_start();
require_once "../includes/funciones.php";
require_once "../includes/db.php";

proteger();

// Lógica corregida para permitir a admins y empleados acceder
if ($_SESSION["rol"] !== "admin" && $_SESSION["rol"] !== "vendedor") {
    header("Location: ../index.php");
    exit;
}


$error = "";
$mensaje = "";

// Traer productos con categorías
$productos_query = $conn->query("SELECT p.id, p.nombre, p.precio_venta, p.stock_existencia, c.nombre as categoria 
                                FROM productos p 
                                JOIN categorias c ON p.categoria_id = c.id 
                                ORDER BY c.nombre, p.nombre ASC");

// Métodos de pago
$metodos_pago = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta de débito/crédito',
    'transferencia' => 'Transferencia bancaria'
];

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $producto_ids = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $metodo_pago = $_POST['metodo_pago'] ?? '';

    $conn->begin_transaction();
    $exito = true;

    foreach ($producto_ids as $index => $producto_id) {
        $cantidad = (float)$cantidades[$index]; // ✅ ahora admite decimales

        if ($cantidad <= 0) {
            continue;
        }

        // Obtener stock y precio actual
        $sql_stock = "SELECT stock_existencia, precio_venta FROM productos WHERE id = ?";
        $stmt = $conn->prepare($sql_stock);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto = $resultado->fetch_assoc();
        $stmt->close();

        if (!$producto) {
            $error = "❌ Producto no encontrado para ID: $producto_id.";
            $exito = false;
            break;
        }

        if ($producto['stock_existencia'] < $cantidad) {
            $error = "❌ Stock insuficiente. Disponible: {$producto['stock_existencia']} / Solicitado: $cantidad.";
            $exito = false;
            break;
        }

        // Actualizar stock
        $sql_update_stock = "UPDATE productos SET stock_existencia = stock_existencia - ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update_stock);
        $stmt->bind_param("di", $cantidad, $producto_id);
        if (!$stmt->execute()) {
            $error = "❌ Error al actualizar stock: " . $conn->error;
            $exito = false;
            break;
        }
        $stmt->close();

        $precio_unitario = (float)$producto['precio_venta'];
        $total = $precio_unitario * $cantidad;

        // Intereses según método de pago
        $interes = 0;
        if ($metodo_pago === "credito_3") $interes = 0.11;
        if ($metodo_pago === "credito_6") $interes = 0.16;
        if ($metodo_pago === "credito_9") $interes = 0.20;
        
        $total_final = $total + ($total * $interes);        

        $usuario_id = $_SESSION["usuario_id"]; // el que se logueó

        // Guardar venta
        $sql_insert_venta = "INSERT INTO ventas (producto_id, cantidad, precio_unitario, metodo_pago, usuario_id, fecha)
                             VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql_insert_venta);
        $stmt->bind_param("iddsi", $producto_id, $cantidad, $precio_unitario, $metodo_pago, $usuario_id);   
        
        if (!$stmt->execute()) {
            $error = "❌ Error al registrar la venta: " . $conn->error;
            $exito = false;
            break;
        }
        $stmt->close();
    }

    if ($exito) {
        $conn->commit();
        $mensaje = "✅ Venta registrada correctamente.";
    } else {
        $conn->rollback();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venta</title>
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
                <p class="text-white-50 small">Registrar Venta</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="registrar_venta.php" class="nav-link active"><i class="bi bi-cart-plus"></i> Nueva Venta</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="proveedores.php" class="nav-link"><i class="bi bi-truck"></i> Proveedores</a></li>
                <li class="nav-item"><a href="clientes_fiados.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="reporte_mensual.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Registrar Venta</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>
                    <a href="../public/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header">Nueva Venta</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center" id="tabla-productos">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad (kg)</th>
                                        <th>Precio Unitario ($)</th>
                                        <th>Subtotal ($)</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <input list="lista-productos" name="producto_nombre[]" class="form-control producto-input" required>
                                            <input type="hidden" name="producto_id[]" class="producto-id">
                                        </td>
                                        <td><input type="number" name="cantidad[]" class="form-control cantidad" value="1" min="0.001" step="0.001" required></td>
                                        <td class="precio">0.00</td>
                                        <td class="subtotal">0.00</td>
                                        <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarFila(this)">❌</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <datalist id="lista-productos">
                            <?php while ($p = $productos_query->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($p['nombre']) . ' (Stock: ' . htmlspecialchars($p['stock_existencia']) . ')' ?>"
                                        data-id="<?= htmlspecialchars($p['id']) ?>"
                                        data-precio="<?= htmlspecialchars($p['precio_venta']) ?>"
                                        data-stock="<?= htmlspecialchars($p['stock_existencia']) ?>"></option>
                            <?php endwhile; ?>
                        </datalist>

                        <div class="my-3">
                            <button type="button" class="btn btn-outline-success" onclick="agregarFila()">➕ Agregar Producto</button>
                        </div>

                        <div class="mb-3 text-end">
                            <h5>Total: $<span id="total">0.00</span></h5>
                            <div id="cuotas-info" class="text-muted" style="display:none;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="metodo_pago" class="form-label">Método de Pago:</label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                                <?php foreach ($metodos_pago as $key => $value): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">💾 Registrar Venta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const datalist = document.getElementById("lista-productos");
    const tablaProductosBody = document.querySelector("#tabla-productos tbody");
    const totalElement = document.getElementById("total");
    const cuotasInfoElement = document.getElementById("cuotas-info");
    const metodoPagoSelect = document.getElementById("metodo_pago");

    function agregarFila() {
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>
                <input list="lista-productos" name="producto_nombre[]" class="form-control producto-input" required>
                <input type="hidden" name="producto_id[]" class="producto-id">
            </td>
            <td><input type="number" name="cantidad[]" class="form-control cantidad" value="1" min="0.001" step="0.001" required></td>
            <td class="precio">0.00</td>
            <td class="subtotal">0.00</td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarFila(this)">❌</button></td>
        `;
        tablaProductosBody.appendChild(fila);
    }

    function eliminarFila(btn) {
        btn.closest("tr").remove();
        actualizarTotales();
    }

    function actualizarTotales() {
        let total = 0;
        document.querySelectorAll("#tabla-productos tbody tr").forEach(tr => {
            const cantidadInput = tr.querySelector(".cantidad");
            const precioCell = tr.querySelector(".precio");
            const subtotalCell = tr.querySelector(".subtotal");
            const hiddenIdInput = tr.querySelector(".producto-id");

            const cantidad = parseFloat(cantidadInput.value) || 0;
            const precio = parseFloat(hiddenIdInput.dataset.precio || 0);
            
            const subtotal = cantidad * precio;
            
            precioCell.textContent = precio.toFixed(2);
            subtotalCell.textContent = subtotal.toFixed(2);
            total += subtotal;
        });

        const metodo = metodoPagoSelect.value;
        let interes = 0;
        let cuotas = 1;

        if (metodo === "credito_3") { interes = 0.11; cuotas = 3; }
        if (metodo === "credito_6") { interes = 0.16; cuotas = 6; }
        if (metodo === "credito_9") { interes = 0.20; cuotas = 9; }

        const totalConInteres = total + (total * interes);
        totalElement.textContent = totalConInteres.toFixed(2);

        if (cuotas > 1) {
            const valorCuota = totalConInteres / cuotas;
            cuotasInfoElement.textContent = `En ${cuotas} cuotas de $${valorCuota.toFixed(2)}`;
            cuotasInfoElement.style.display = 'block';
        } else {
            cuotasInfoElement.style.display = 'none';
        }
    }

    document.addEventListener("input", (e) => {
        if (e.target.classList.contains("producto-input")) {
            const input = e.target;
            const hiddenId = input.closest("td").querySelector(".producto-id");
            const selectedOption = datalist.querySelector(`option[value="${input.value}"]`);

            if (selectedOption) {
                hiddenId.value = selectedOption.dataset.id;
                hiddenId.dataset.precio = selectedOption.dataset.precio;
                hiddenId.dataset.stock = selectedOption.dataset.stock;
            } else {
                hiddenId.value = '';
                hiddenId.dataset.precio = '';
                hiddenId.dataset.stock = '';
            }
            actualizarTotales();
        }
        if (e.target.classList.contains("cantidad")) {
            actualizarTotales();
        }
    });

    metodoPagoSelect.addEventListener("change", actualizarTotales);
    document.addEventListener("DOMContentLoaded", actualizarTotales);
</script>
</body>
</html>
