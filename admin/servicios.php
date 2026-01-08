<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_servicio':
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $precio_base = floatval($_POST['precio_base']);
                $precio_por_hora = floatval($_POST['precio_por_hora']);
                $especialidad_id = intval($_POST['especialidad_id']);
                $duracion_estimada = intval($_POST['duracion_estimada']);
                
                $stmt = $conn->prepare("INSERT INTO servicios_legales (nombre, descripcion, precio_base, precio_por_hora, especialidad_id, duracion_estimada) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddii", $nombre, $descripcion, $precio_base, $precio_por_hora, $especialidad_id, $duracion_estimada);
                
                if ($stmt->execute()) {
                    $mensaje = "Servicio creado exitosamente";
                } else {
                    $error = "Error al crear el servicio: " . $stmt->error;
                }
                break;
                
            case 'crear_especialidad':
                $nombre = trim($_POST['nombre_esp']);
                $descripcion = trim($_POST['descripcion_esp']);
                
                $stmt = $conn->prepare("INSERT INTO especialidades (nombre, descripcion) VALUES (?, ?)");
                $stmt->bind_param("ss", $nombre, $descripcion);
                
                if ($stmt->execute()) {
                    $mensaje = "Especialidad creada exitosamente";
                } else {
                    $error = "Error al crear la especialidad: " . $stmt->error;
                }
                break;
                
            case 'actualizar_servicio':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $precio_base = floatval($_POST['precio_base']);
                $precio_por_hora = floatval($_POST['precio_por_hora']);
                $especialidad_id = intval($_POST['especialidad_id']);
                $duracion_estimada = intval($_POST['duracion_estimada']);
                
                $stmt = $conn->prepare("UPDATE servicios_legales SET nombre = ?, descripcion = ?, precio_base = ?, precio_por_hora = ?, especialidad_id = ?, duracion_estimada = ? WHERE id = ?");
                $stmt->bind_param("ssddiii", $nombre, $descripcion, $precio_base, $precio_por_hora, $especialidad_id, $duracion_estimada, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Servicio actualizado exitosamente";
                } else {
                    $error = "Error al actualizar el servicio: " . $stmt->error;
                }
                break;
                
            case 'eliminar_servicio':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM servicios_legales WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Servicio eliminado exitosamente";
                } else {
                    $error = "Error al eliminar el servicio: " . $stmt->error;
                }
                break;
        }
    }
}

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$especialidad = $_GET['especialidad'] ?? '';

// Obtener especialidades
$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");

// Construir consulta de servicios
$sql = "SELECT s.*, e.nombre as especialidad_nombre
        FROM servicios_legales s
        LEFT JOIN especialidades e ON s.especialidad_id = e.id
        WHERE 1=1";

if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $sql .= " AND (s.nombre LIKE '%$busq%' OR s.descripcion LIKE '%$busq%' OR e.nombre LIKE '%$busq%')";
}

if ($especialidad !== '') {
    $sql .= " AND s.especialidad_id = " . intval($especialidad);
}

$sql .= " ORDER BY s.nombre";
$servicios = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios - Gestión Inmobiliaria</title>
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
                <p class="text-white-50 small">Gestión de Servicios</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link"><i class="bi bi-calendar-event"></i> Citas</a></li>
                <li class="nav-item"><a href="servicios.php" class="nav-link active"><i class="bi bi-briefcase"></i> Servicios</a></li>
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
                <h4>Gestión de Servicios Legales</h4>
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
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o descripción..." value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="especialidad" class="form-select">
                                <option value="">-- Especialidad --</option>
                                <?php 
                                $especialidades_filtro = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
                                while ($esp = $especialidades_filtro->fetch_assoc()): 
                                ?>
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

            <!-- Botones de Acción -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearServicio">
                        <i class="bi bi-plus-circle"></i> Nuevo Servicio
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalCrearEspecialidad">
                        <i class="bi bi-tag"></i> Nueva Especialidad
                    </button>
                </div>
            </div>

            <!-- Lista de Servicios -->
            <div class="card">
                <div class="card-header">
                    <span>Lista de Servicios Legales</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Especialidad</th>
                                <th>Precio Base</th>
                                <th>Precio/Hora</th>
                                <th>Duración Est.</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($servicios && $servicios->num_rows > 0): ?>
                                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($servicio['nombre']) ?></strong></td>
                                        <td><?= htmlspecialchars($servicio['descripcion']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($servicio['especialidad_nombre']) ?></span>
                                        </td>
                                        <td>$<?= number_format($servicio['precio_base'], 2) ?></td>
                                        <td>$<?= number_format($servicio['precio_por_hora'], 2) ?></td>
                                        <td><?= $servicio['duracion_estimada'] ?> horas</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarServicio(<?= $servicio['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(<?= $servicio['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center">No se encontraron servicios con los filtros actuales.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Servicio -->
<div class="modal fade" id="modalCrearServicio" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Servicio Legal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_servicio">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Servicio *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Especialidad *</label>
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
                            <label class="form-label">Precio Base *</label>
                            <input type="number" name="precio_base" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio por Hora</label>
                            <input type="number" name="precio_por_hora" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duración Estimada (horas)</label>
                            <input type="number" name="duracion_estimada" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Especialidad -->
<div class="modal fade" id="modalCrearEspecialidad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Especialidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_especialidad">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Especialidad *</label>
                        <input type="text" name="nombre_esp" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion_esp" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Especialidad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarServicio(id) {
    // Implementar edición de servicio
    alert('Función de edición en desarrollo');
}

function eliminarServicio(id) {
    if (confirm('¿Está seguro de que desea eliminar este servicio?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar_servicio">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
