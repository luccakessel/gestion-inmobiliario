<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// Crear directorio de documentos si no existe
$upload_dir = "../uploads/documentos/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'subir_documento':
                $caso_id = !empty($_POST['caso_id']) ? intval($_POST['caso_id']) : null;
                $cliente_id = intval($_POST['cliente_id']);
                $tipo_documento = $_POST['tipo_documento'];
                $descripcion = trim($_POST['descripcion']);
                
                if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                    $archivo = $_FILES['archivo'];
                    $nombre_archivo = $archivo['name'];
                    $tamaño_archivo = $archivo['size'];
                    $tipo_archivo = $archivo['type'];
                    $archivo_tmp = $archivo['tmp_name'];
                    
                    // Validar tipo de archivo
                    $tipos_permitidos = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
                    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, $tipos_permitidos)) {
                        // Generar nombre único
                        $nombre_unico = uniqid() . '_' . $nombre_archivo;
                        $ruta_archivo = $upload_dir . $nombre_unico;
                        
                        if (move_uploaded_file($archivo_tmp, $ruta_archivo)) {
                            $stmt = $conn->prepare("INSERT INTO documentos (caso_id, cliente_id, nombre_archivo, ruta_archivo, tipo_documento, descripcion, tamaño_archivo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iissssi", $caso_id, $cliente_id, $nombre_archivo, $ruta_archivo, $tipo_documento, $descripcion, $tamaño_archivo);
                            
                            if ($stmt->execute()) {
                                $mensaje = "Documento subido exitosamente";
                            } else {
                                $error = "Error al guardar el documento: " . $stmt->error;
                            }
                        } else {
                            $error = "Error al subir el archivo";
                        }
                    } else {
                        $error = "Tipo de archivo no permitido. Tipos permitidos: " . implode(', ', $tipos_permitidos);
                    }
                } else {
                    $error = "No se seleccionó ningún archivo";
                }
                break;
                
            case 'eliminar_documento':
                $id = intval($_POST['id']);
                
                // Obtener información del documento
                $stmt = $conn->prepare("SELECT ruta_archivo FROM documentos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($documento = $result->fetch_assoc()) {
                    // Eliminar archivo físico
                    if (file_exists($documento['ruta_archivo'])) {
                        unlink($documento['ruta_archivo']);
                    }
                    
                    // Eliminar registro de la base de datos
                    $stmt = $conn->prepare("DELETE FROM documentos WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Documento eliminado exitosamente";
                    } else {
                        $error = "Error al eliminar el documento: " . $stmt->error;
                    }
                } else {
                    $error = "Documento no encontrado";
                }
                break;
        }
    }
}

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$caso = $_GET['caso'] ?? '';
$cliente = $_GET['cliente'] ?? '';

// Obtener casos
$casos = $conn->query("SELECT id, numero_expediente, titulo FROM casos ORDER BY numero_expediente");

// Obtener clientes
$clientes = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes ORDER BY nombre");

// Construir consulta de documentos
$sql = "SELECT d.*, 
               c.numero_expediente,
               c.titulo as caso_titulo,
               CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre
        FROM documentos d
        LEFT JOIN casos c ON d.caso_id = c.id
        LEFT JOIN clientes cl ON d.cliente_id = cl.id
        WHERE 1=1";

$params = [];
$types = "";

if ($busqueda !== '') {
    $busq = $conn->real_escape_string($busqueda);
    $sql .= " AND (d.nombre_archivo LIKE '%$busq%' OR d.descripcion LIKE '%$busq%' OR c.numero_expediente LIKE '%$busq%' OR cl.nombre LIKE '%$busq%' OR cl.apellido LIKE '%$busq%')";
}

if ($tipo !== '') {
    $sql .= " AND d.tipo_documento = ?";
    $params[] = $tipo;
    $types .= "s";
}

if ($caso !== '') {
    $sql .= " AND d.caso_id = ?";
    $params[] = intval($caso);
    $types .= "i";
}

if ($cliente !== '') {
    $sql .= " AND d.cliente_id = ?";
    $params[] = intval($cliente);
    $types .= "i";
}

$sql .= " ORDER BY d.fecha_subida DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$documentos = $stmt->get_result();

// Función para formatear tamaño de archivo
function formatearTamaño($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Función para obtener icono por tipo de archivo
function obtenerIcono($extension) {
    $iconos = [
        'pdf' => 'bi-file-earmark-pdf text-danger',
        'doc' => 'bi-file-earmark-word text-primary',
        'docx' => 'bi-file-earmark-word text-primary',
        'txt' => 'bi-file-earmark-text text-secondary',
        'jpg' => 'bi-file-earmark-image text-success',
        'jpeg' => 'bi-file-earmark-image text-success',
        'png' => 'bi-file-earmark-image text-success',
        'gif' => 'bi-file-earmark-image text-success'
    ];
    
    return $iconos[$extension] ?? 'bi-file-earmark text-muted';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Documentos - Gestión Inmobiliaria</title>
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
        
        .documento-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .documento-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .tipo-contrato { border-left: 4px solid #007bff; }
        .tipo-demanda { border-left: 4px solid #dc3545; }
        .tipo-escrito { border-left: 4px solid #28a745; }
        .tipo-sentencia { border-left: 4px solid #ffc107; }
        .tipo-otro { border-left: 4px solid #6c757d; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Gestión Inmobiliaria</h4>
                <p class="text-white-50 small">Gestión de Documentos</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="casos.php" class="nav-link"><i class="bi bi-house"></i> Propiedades</a></li>
                <li class="nav-item"><a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a></li>
                <li class="nav-item"><a href="citas.php" class="nav-link"><i class="bi bi-calendar-event"></i> Citas</a></li>
                <li class="nav-item"><a href="servicios.php" class="nav-link"><i class="bi bi-briefcase"></i> Servicios</a></li>
                <li class="nav-item"><a href="facturacion.php" class="nav-link"><i class="bi bi-receipt"></i> Facturación</a></li>
                <li class="nav-item"><a href="documentos.php" class="nav-link active"><i class="bi bi-file-earmark-text"></i> Documentos</a></li>
                <li class="nav-item"><a href="vencimientos.php" class="nav-link"><i class="bi bi-clock-history"></i> Vencimientos</a></li>
                <li class="nav-item"><a href="reportes.php" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Documentos</h4>
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
                            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o descripción..." value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="tipo" class="form-select">
                                <option value="">-- Tipo --</option>
                                <option value="contrato" <?= $tipo === 'contrato' ? 'selected' : '' ?>>Contrato</option>
                                <option value="demanda" <?= $tipo === 'demanda' ? 'selected' : '' ?>>Demanda</option>
                                <option value="escrito" <?= $tipo === 'escrito' ? 'selected' : '' ?>>Escrito</option>
                                <option value="sentencia" <?= $tipo === 'sentencia' ? 'selected' : '' ?>>Sentencia</option>
                                <option value="otro" <?= $tipo === 'otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="caso" class="form-select">
                                <option value="">-- Propiedad --</option>
                                <?php 
                                $casos_filtro = $conn->query("SELECT id, numero_expediente, titulo FROM casos ORDER BY numero_expediente");
                                while ($caso_filtro = $casos_filtro->fetch_assoc()): 
                                ?>
                                    <option value="<?= $caso_filtro['id'] ?>" <?= $caso == $caso_filtro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($caso_filtro['numero_expediente'] . ' - ' . $caso_filtro['titulo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="cliente" class="form-select">
                                <option value="">-- Cliente --</option>
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
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botón Subir Documento -->
            <div class="mb-4">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSubirDocumento">
                    <i class="bi bi-cloud-upload"></i> Subir Documento
                </button>
            </div>

            <!-- Lista de Documentos -->
            <div class="row">
                <?php if ($documentos && $documentos->num_rows > 0): ?>
                    <?php while ($documento = $documentos->fetch_assoc()): ?>
                        <?php
                        $extension = strtolower(pathinfo($documento['nombre_archivo'], PATHINFO_EXTENSION));
                        $icono = obtenerIcono($extension);
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card documento-card tipo-<?= $documento['tipo_documento'] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title">
                                            <i class="bi <?= $icono ?>"></i>
                                            <?= htmlspecialchars($documento['nombre_archivo']) ?>
                                        </h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?= $documento['ruta_archivo'] ?>" target="_blank">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a></li>
                                                <li><a class="dropdown-item" href="<?= $documento['ruta_archivo'] ?>" download>
                                                    <i class="bi bi-download"></i> Descargar
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="eliminarDocumento(<?= $documento['id'] ?>)">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-tag"></i> <?= ucfirst($documento['tipo_documento']) ?><br>
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($documento['cliente_nombre']) ?><br>
                                            <?php if ($documento['numero_expediente']): ?>
                                                <i class="bi bi-folder"></i> <?= htmlspecialchars($documento['numero_expediente']) ?><br>
                                            <?php endif; ?>
                                            <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($documento['fecha_subida'])) ?><br>
                                            <i class="bi bi-hdd"></i> <?= formatearTamaño($documento['tamaño_archivo']) ?>
                                        </small>
                                    </p>
                                    
                                    <?php if ($documento['descripcion']): ?>
                                        <p class="card-text"><?= htmlspecialchars($documento['descripcion']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center p-4">
                            <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">No se encontraron documentos con los filtros actuales</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Subir Documento -->
<div class="modal fade" id="modalSubirDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="subir_documento">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required onchange="cargarCasos()">
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
                            <label class="form-label">Propiedad (Opcional)</label>
                            <select name="caso_id" class="form-select" id="casosSelect">
                                <option value="">Seleccionar caso</option>
                                <?php 
                                $casos_modal = $conn->query("SELECT id, numero_expediente, titulo FROM casos ORDER BY numero_expediente");
                                while ($caso = $casos_modal->fetch_assoc()): 
                                ?>
                                    <option value="<?= $caso['id'] ?>"><?= htmlspecialchars($caso['numero_expediente'] . ' - ' . $caso['titulo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Documento *</label>
                            <select name="tipo_documento" class="form-select" required>
                                <option value="contrato">Contrato</option>
                                <option value="escritura">Escritura</option>
                                <option value="plano">Plano</option>
                                <option value="tasacion">Tasación</option>
                                <option value="imagen">Imagen</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Archivo *</label>
                            <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif" required>
                            <small class="form-text text-muted">Tipos permitidos: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción del documento..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cargarCasos() {
    const clienteId = document.querySelector('select[name="cliente_id"]').value;
    const casosSelect = document.getElementById('casosSelect');
    
    if (clienteId) {
        // Aquí se haría una llamada AJAX para cargar los casos del cliente
        // Por simplicidad, se mantiene la lista completa
    }
}

function eliminarDocumento(id) {
    if (confirm('¿Está seguro de que desea eliminar este documento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar_documento">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
