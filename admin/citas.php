<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $caso_id = !empty($_POST['caso_id']) ? intval($_POST['caso_id']) : null;
                $cliente_id = intval($_POST['cliente_id']);
                $abogado_id = intval($_POST['abogado_id']);
                $titulo = trim($_POST['titulo']);
                $descripcion = trim($_POST['descripcion']);
                $fecha_cita = $_POST['fecha_cita'];
                $duracion_estimada = intval($_POST['duracion_estimada']);
                $tipo = $_POST['tipo'];
                $ubicacion = trim($_POST['ubicacion']);
                $notas = trim($_POST['notas']);
                
                $stmt = $conn->prepare("INSERT INTO citas (caso_id, cliente_id, abogado_id, titulo, descripcion, fecha_cita, duracion_estimada, tipo, ubicacion, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisssisss", $caso_id, $cliente_id, $abogado_id, $titulo, $descripcion, $fecha_cita, $duracion_estimada, $tipo, $ubicacion, $notas);
                
                if ($stmt->execute()) {
                    $mensaje = "Cita creada exitosamente";
                } else {
                    $error = "Error al crear la cita: " . $stmt->error;
                }
                break;
                
            case 'actualizar':
                $id = intval($_POST['id']);
                $estado = $_POST['estado'];
                $notas = trim($_POST['notas']);
                
                $stmt = $conn->prepare("UPDATE citas SET estado = ?, notas = ? WHERE id = ?");
                $stmt->bind_param("ssi", $estado, $notas, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Cita actualizada exitosamente";
                } else {
                    $error = "Error al actualizar la cita: " . $stmt->error;
                }
                break;
        }
    }
}

// Obtener filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$estado = $_GET['estado'] ?? '';
$abogado = $_GET['abogado'] ?? '';

// Obtener abogados
$abogados = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'abogado' ORDER BY nombre");

// Obtener clientes
$clientes = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");

// Obtener casos
$casos = $conn->query("SELECT id, numero_expediente, titulo FROM casos WHERE estado = 'activo' ORDER BY numero_expediente");

// Construir consulta de citas
$sql = "SELECT c.*, 
               CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
               u.nombre as abogado_nombre,
               cas.numero_expediente,
               cas.titulo as caso_titulo
        FROM citas c
        LEFT JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN usuarios u ON c.abogado_id = u.id
        LEFT JOIN casos cas ON c.caso_id = cas.id
        WHERE c.fecha_cita BETWEEN ? AND ?";

$params = [$fecha_desde . ' 00:00:00', $fecha_hasta . ' 23:59:59'];
$types = "ss";

if ($estado !== '') {
    $sql .= " AND c.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

if ($abogado !== '') {
    $sql .= " AND c.abogado_id = ?";
    $params[] = intval($abogado);
    $types .= "i";
}

$sql .= " ORDER BY c.fecha_cita ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$citas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Visitas - Gestión Inmobiliaria</title>
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
        
        .estado-programada { color: #007bff; font-weight: bold; }
        .estado-realizada { color: #28a745; font-weight: bold; }
        .estado-cancelada { color: #dc3545; font-weight: bold; }
        .estado-reprogramada { color: #ffc107; font-weight: bold; }
        
        .cita-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 10px;
        }
        
        .cita-card.consulta { border-left-color: #28a745; }
        .cita-card.audiencia { border-left-color: #dc3545; }
        .cita-card.reunion { border-left-color: #ffc107; }
        .cita-card.llamada { border-left-color: #17a2b8; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Gestión de Visitas</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link active"><i class="bi bi-calendar-event"></i> Citas</a></li>
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
                <h4>Gestión de Visitas</h4>
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
                        <div class="col-md-3">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">-- Todos --</option>
                                <option value="programada" <?= $estado === 'programada' ? 'selected' : '' ?>>Programada</option>
                                <option value="realizada" <?= $estado === 'realizada' ? 'selected' : '' ?>>Realizada</option>
                                <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                <option value="reprogramada" <?= $estado === 'reprogramada' ? 'selected' : '' ?>>Reprogramada</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Agente</label>
                            <select name="abogado" class="form-select">
                                <option value="">-- Todos --</option>
                                <?php 
                                $abogados_filtro = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'abogado' ORDER BY nombre");
                                while ($abogado_filtro = $abogados_filtro->fetch_assoc()): 
                                ?>
                                    <option value="<?= $abogado_filtro['id'] ?>" <?= $abogado == $abogado_filtro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($abogado_filtro['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botón Nueva Cita -->
            <div class="mb-4">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearCita">
                    <i class="bi bi-plus-circle"></i> Nueva Cita
                </button>
            </div>

            <!-- Lista de Citas -->
            <div class="row">
                <?php if ($citas && $citas->num_rows > 0): ?>
                    <?php while ($cita = $citas->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card cita-card <?= $cita['tipo'] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title"><?= htmlspecialchars($cita['titulo']) ?></h6>
                                        <span class="badge bg-<?= $cita['estado'] === 'programada' ? 'primary' : ($cita['estado'] === 'realizada' ? 'success' : ($cita['estado'] === 'cancelada' ? 'danger' : 'warning')) ?>">
                                            <?= ucfirst($cita['estado']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($cita['fecha_cita'])) ?><br>
                                            <i class="bi bi-clock"></i> <?= $cita['duracion_estimada'] ?> min<br>
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($cita['cliente_nombre']) ?><br>
                                            <i class="bi bi-briefcase"></i> <?= htmlspecialchars($cita['abogado_nombre']) ?><br>
                                            <?php if ($cita['numero_expediente']): ?>
                                                <i class="bi bi-folder"></i> <?= htmlspecialchars($cita['numero_expediente']) ?><br>
                                            <?php endif; ?>
                                            <?php if ($cita['ubicacion']): ?>
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($cita['ubicacion']) ?><br>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    
                                    <?php if ($cita['descripcion']): ?>
                                        <p class="card-text"><?= htmlspecialchars($cita['descripcion']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between">
                                        <small class="text-<?= $cita['tipo'] === 'consulta' ? 'success' : ($cita['tipo'] === 'audiencia' ? 'danger' : ($cita['tipo'] === 'reunion' ? 'warning' : 'info')) ?>">
                                            <?= ucfirst($cita['tipo']) ?>
                                        </small>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarCita(<?= $cita['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center p-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">No hay citas programadas en el período seleccionado</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Cita -->
<div class="modal fade" id="modalCrearCita" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="consulta">Consulta</option>
                                <option value="audiencia">Audiencia</option>
                                <option value="reunion">Reunión</option>
                                <option value="llamada">Llamada</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
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
                            <label class="form-label">Agente *</label>
                            <select name="abogado_id" class="form-select" required>
                                <option value="">Seleccionar abogado</option>
                                <?php 
                                $abogados_modal = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'abogado' ORDER BY nombre");
                                while ($abogado = $abogados_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $abogado['id'] ?>"><?= htmlspecialchars($abogado['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Caso (Opcional)</label>
                            <select name="caso_id" class="form-select">
                                <option value="">Seleccionar caso</option>
                                <?php 
                                $casos_modal = $conn->query("SELECT id, numero_expediente, titulo FROM casos WHERE estado = 'activo' ORDER BY numero_expediente");
                                while ($caso = $casos_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $caso['id'] ?>"><?= htmlspecialchars($caso['numero_expediente'] . ' - ' . $caso['titulo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha y Hora *</label>
                            <input type="datetime-local" name="fecha_cita" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duración (minutos)</label>
                            <input type="number" name="duracion_estimada" class="form-control" min="15" step="15" value="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Oficina, Tribunal, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notas" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarCita(id) {
    // Implementar edición de cita
    alert('Función de edición en desarrollo');
}
</script>
</body>
</html>
