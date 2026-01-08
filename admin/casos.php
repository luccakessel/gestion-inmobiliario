<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Verificar que el usuario tenga permisos
if ($_SESSION["rol"] !== "admin" && $_SESSION["rol"] !== "abogado") {
    header("Location: ../index.php");
    exit;
}

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $numero_expediente = trim($_POST['numero_expediente']);
                $cliente_id = intval($_POST['cliente_id']);
                // Abogado opcional
                $abogado_id = isset($_POST['abogado_id']) && $_POST['abogado_id'] !== '' ? intval($_POST['abogado_id']) : null;
                $especialidad_id = intval($_POST['especialidad_id']);
                $titulo = trim($_POST['titulo']);
                $descripcion = trim($_POST['descripcion']);
                $fecha_inicio = $_POST['fecha_inicio'];
                $honorarios_estimados = floatval($_POST['honorarios_estimados']);
                
                $stmt = $conn->prepare("INSERT INTO casos (numero_expediente, cliente_id, abogado_id, especialidad_id, titulo, descripcion, fecha_inicio, honorarios_estimados) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                // Nota: permitimos NULL en abogado_id si no se selecciona
                $stmt->bind_param("siiisssd", $numero_expediente, $cliente_id, $abogado_id, $especialidad_id, $titulo, $descripcion, $fecha_inicio, $honorarios_estimados);
                
                if ($stmt->execute()) {
                    $mensaje = "Caso creado exitosamente";
                } else {
                    $error = "Error al crear el caso: " . $stmt->error;
                }
                break;
                
            case 'actualizar':
                $id = intval($_POST['id']);
                $estado = $_POST['estado'];
                $honorarios_cobrados = floatval($_POST['honorarios_cobrados']);
                $observaciones = trim($_POST['observaciones']);
                
                $stmt = $conn->prepare("UPDATE casos SET estado = ?, honorarios_cobrados = ?, observaciones = ? WHERE id = ?");
                $stmt->bind_param("sdsi", $estado, $honorarios_cobrados, $observaciones, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Caso actualizado exitosamente";
                } else {
                    $error = "Error al actualizar el caso: " . $stmt->error;
                }
                break;
            
            case 'eliminar':
                $id = intval($_POST['id']);
                // Limpieza de dependencias
                // 1) Detalles/servicios prestados del caso
                $stmt = $conn->prepare("DELETE FROM servicios_prestados WHERE caso_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // 2) Vencimientos asociados
                $stmt = $conn->prepare("DELETE FROM vencimientos WHERE caso_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // 3) Desasociar facturas (conservar historial)
                $stmt = $conn->prepare("UPDATE facturas SET caso_id = NULL WHERE caso_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // 4) Eliminar el caso
                $stmt = $conn->prepare("DELETE FROM casos WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $mensaje = "Caso eliminado correctamente";
                } else {
                    $error = "Error al eliminar el caso: " . $stmt->error;
                }
                break;
        }
    }
}

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$especialidad = $_GET['especialidad'] ?? '';

// Obtener especialidades
$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");

// Obtener abogados
$abogados = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'abogado' ORDER BY nombre");

// Obtener clientes
$clientes = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");

// Construir consulta de casos
$sql = "SELECT c.*, 
               CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
               u.nombre as abogado_nombre,
               e.nombre as especialidad_nombre
        FROM casos c
        LEFT JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN usuarios u ON c.abogado_id = u.id
        LEFT JOIN especialidades e ON c.especialidad_id = e.id
        WHERE 1=1";

if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $sql .= " AND (c.numero_expediente LIKE '%$busq%' OR c.titulo LIKE '%$busq%' OR cl.nombre LIKE '%$busq%' OR cl.apellido LIKE '%$busq%')";
}

if ($estado !== '') {
    $sql .= " AND c.estado = '" . $conn->real_escape_string($estado) . "'";
}

if ($especialidad !== '') {
    $sql .= " AND c.especialidad_id = " . intval($especialidad);
}

$sql .= " ORDER BY c.fecha_inicio DESC";
$casos = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Propiedades - Agente Inmobiliario</title>
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
        
        .estado-activo { color: #28a745; font-weight: bold; }
        .estado-pausado { color: #ffc107; font-weight: bold; }
        .estado-cerrado { color: #6c757d; font-weight: bold; }
        .estado-archivado { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Gestión de Propiedades</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link active"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
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
                <h4>Gestión de Propiedades</h4>
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
                <div class="card-header">Filtros de búsqueda</div>
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-4">
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por código, título o cliente..." value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="estado" class="form-select">
                                <option value="">-- Estado --</option>
                                <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="pausado" <?= $estado === 'pausado' ? 'selected' : '' ?>>Pausado</option>
                                <option value="cerrado" <?= $estado === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                <option value="archivado" <?= $estado === 'archivado' ? 'selected' : '' ?>>Archivado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="especialidad" class="form-select">
                                <option value="">-- Tipo de propiedad --</option>
                                <?php while ($esp = $especialidades->fetch_assoc()): ?>
                                    <option value="<?= $esp['id'] ?>" <?= $especialidad == $esp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($esp['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Propiedades -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Listado de Propiedades</span>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearCaso">
                        <i class="bi bi-plus-circle"></i> Nueva Propiedad
                    </button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Cliente</th>
                                <th>Agente</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Fecha Inicio</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($casos && $casos->num_rows > 0): ?>
                                <?php while ($caso = $casos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($caso['numero_expediente']) ?></td>
                                        <td><?= htmlspecialchars($caso['titulo']) ?></td>
                                        <td><?= htmlspecialchars($caso['cliente_nombre']) ?></td>
                                        <td><?= $caso['abogado_nombre'] ? htmlspecialchars($caso['abogado_nombre']) : 'Sin asignar' ?></td>
                                        <td><?= htmlspecialchars($caso['especialidad_nombre']) ?></td>
                                        <td>
                                            <span class="estado-<?= $caso['estado'] ?>">
                                                <?= ucfirst($caso['estado']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($caso['fecha_inicio'])) ?></td>
                                        <td>
                                            <small class="text-muted">Est: $<?= number_format($caso['honorarios_estimados'], 2) ?></small><br>
                                            <small class="text-success">Cob: $<?= number_format($caso['honorarios_cobrados'], 2) ?></small>
                                        </td>
                                        <td>
                                            <a href="editar_caso.php?id=<?= $caso['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="ver_caso.php?id=<?= $caso['id'] ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a class="btn btn-sm btn-outline-success" href="facturacion.php?cliente_id=<?= $caso['cliente_id'] ?>&caso_id=<?= $caso['id'] ?>&open=1">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarCaso(<?= $caso['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center">No se encontraron propiedades con los filtros actuales.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Propiedad -->
<div class="modal fade" id="modalCrearCaso" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Propiedad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código de Propiedad</label>
                            <input type="text" name="numero_expediente" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Título de la Propiedad</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <select name="cliente_id" class="form-select" required>
                                <option value="">Seleccionar cliente</option>
                                <?php 
                                $clientes_modal = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");
                                while ($cliente = $clientes_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre_completo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agente Responsable (opcional)</label>
                            <select name="abogado_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php 
                                $abogados_modal = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'abogado' ORDER BY nombre");
                                while ($abogado = $abogados_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $abogado['id'] ?>"><?= htmlspecialchars($abogado['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Propiedad</label>
                            <select name="especialidad_id" class="form-select" required>
                                <option value="">Seleccionar especialidad</option>
                                <?php 
                                $especialidades_modal = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
                                while ($esp = $especialidades_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio Estimado</label>
                            <input type="number" name="honorarios_estimados" class="form-control" step="0.01" min="0" placeholder="Precio de publicación">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Propiedad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function eliminarCaso(id) {
    if (confirm('¿Eliminar esta propiedad? Esta acción no se puede deshacer.')) {
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
