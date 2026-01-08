<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = trim($_POST['nombre']);
                $apellido = trim($_POST['apellido']);
                $dni = trim($_POST['dni']);
                $telefono = trim($_POST['telefono']);
                $email = trim($_POST['email']);
                $direccion = trim($_POST['direccion']);
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $profesion = trim($_POST['profesion']);
                $estado_civil = $_POST['estado_civil'];
                
                $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellido, dni, telefono, email, direccion, fecha_nacimiento, profesion, estado_civil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion, $fecha_nacimiento, $profesion, $estado_civil);
                
                if ($stmt->execute()) {
                    $mensaje = "Cliente creado exitosamente";
                } else {
                    $error = "Error al crear el cliente: " . $stmt->error;
                }
                break;
                
            case 'actualizar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $apellido = trim($_POST['apellido']);
                $dni = trim($_POST['dni']);
                $telefono = trim($_POST['telefono']);
                $email = trim($_POST['email']);
                $direccion = trim($_POST['direccion']);
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $profesion = trim($_POST['profesion']);
                $estado_civil = $_POST['estado_civil'];
                
                $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, apellido = ?, dni = ?, telefono = ?, email = ?, direccion = ?, fecha_nacimiento = ?, profesion = ?, estado_civil = ? WHERE id = ?");
                $stmt->bind_param("sssssssssi", $nombre, $apellido, $dni, $telefono, $email, $direccion, $fecha_nacimiento, $profesion, $estado_civil, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Cliente actualizado exitosamente";
                } else {
                    $error = "Error al actualizar el cliente: " . $stmt->error;
                }
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Cliente eliminado exitosamente";
                } else {
                    $error = "Error al eliminar el cliente: " . $stmt->error;
                }
                break;
        }
    }
}

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$estado_civil = $_GET['estado_civil'] ?? '';

// Construir consulta de clientes
$sql = "SELECT c.*, 
               COUNT(cas.id) as total_casos,
               SUM(cas.honorarios_cobrados) as total_honorarios
        FROM clientes c
        LEFT JOIN casos cas ON c.id = cas.cliente_id
        WHERE 1=1";

if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $sql .= " AND (c.nombre LIKE '%$busq%' OR c.apellido LIKE '%$busq%' OR c.dni LIKE '%$busq%' OR c.email LIKE '%$busq%')";
}

if ($estado_civil !== '') {
    $sql .= " AND c.estado_civil = '" . $conn->real_escape_string($estado_civil) . "'";
}

$sql .= " GROUP BY c.id ORDER BY c.nombre, c.apellido";
$clientes = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Gestión Inmobiliaria</title>
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
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Gestión de Clientes</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link active"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link"><i class="bi bi-calendar-event"></i> Citas</a></li>
                <li class="nav-item"><a href="servicios.php" class="nav-link"><i class="bi bi-briefcase"></i> Servicios</a></li>
                <li class="nav-item"><a href="facturacion.php" class="nav-link"><i class="bi bi-receipt"></i> Facturación</a></li>
                <li class="nav-item"><a href="documentos.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Documentos</a></li>
                <li class="nav-item"><a href="vencimientos.php" class="nav-link"><i class="bi bi-clock-history"></i> Vencimientos</a></li>
                <li class="nav-item"><a href="reportes.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Clientes</h4>
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

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">Filtros</div>
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-6">
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, apellido, DNI o email..." value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="estado_civil" class="form-select">
                                <option value="">-- Estado Civil --</option>
                                <option value="soltero" <?= $estado_civil === 'soltero' ? 'selected' : '' ?>>Soltero</option>
                                <option value="casado" <?= $estado_civil === 'casado' ? 'selected' : '' ?>>Casado</option>
                                <option value="divorciado" <?= $estado_civil === 'divorciado' ? 'selected' : '' ?>>Divorciado</option>
                                <option value="viudo" <?= $estado_civil === 'viudo' ? 'selected' : '' ?>>Viudo</option>
                                <option value="concubinato" <?= $estado_civil === 'concubinato' ? 'selected' : '' ?>>Concubinato</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Lista de Clientes</span>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
                        <i class="bi bi-plus-circle"></i> Nuevo Cliente
                    </button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Nombre Completo</th>
                                <th>DNI</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado Civil</th>
                                <th>Profesión</th>
                                <th>Casos</th>
                                <th>Honorarios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clientes && $clientes->num_rows > 0): ?>
                                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></strong>
                                            <?php if ($cliente['fecha_nacimiento']): ?>
                                                <br><small class="text-muted"><?= date('d/m/Y', strtotime($cliente['fecha_nacimiento'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($cliente['dni']) ?></td>
                                        <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                                        <td><?= htmlspecialchars($cliente['email']) ?></td>
                                        <td><?= ucfirst($cliente['estado_civil']) ?></td>
                                        <td><?= htmlspecialchars($cliente['profesion']) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= $cliente['total_casos'] ?></span>
                                        </td>
                                        <td>
                                            <small class="text-success">$<?= number_format($cliente['total_honorarios'] ?? 0, 2) ?></small>
                                        </td>
                                        <td>
                                            <a href="editar_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="ver_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(<?= $cliente['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center">No se encontraron clientes con los filtros actuales.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Cliente -->
<div class="modal fade" id="modalCrearCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profesión</label>
                            <input type="text" name="profesion" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado Civil</label>
                            <select name="estado_civil" class="form-select">
                                <option value="soltero">Soltero</option>
                                <option value="casado">Casado</option>
                                <option value="divorciado">Divorciado</option>
                                <option value="viudo">Viudo</option>
                                <option value="concubinato">Concubinato</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function eliminarCliente(id) {
    if (confirm('¿Está seguro de que desea eliminar este cliente?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
