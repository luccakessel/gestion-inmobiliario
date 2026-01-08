<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

if (!isset($_GET['id'])) {
    die("Caso no especificado");
}
$id = intval($_GET['id']);

// Cargar datos para selects
$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
$abogados = $conn->query("SELECT id, nombre FROM usuarios WHERE rol='abogado' ORDER BY nombre");
$clientes = $conn->query("SELECT id, CONCAT(nombre,' ',apellido) AS nombre_completo FROM clientes ORDER BY nombre");

// Obtener caso
$stmt = $conn->prepare("SELECT * FROM casos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$caso = $stmt->get_result()->fetch_assoc();
if (!$caso) {
    die("Caso no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_expediente = trim($_POST['numero_expediente']);
    $cliente_id = intval($_POST['cliente_id']);
    $abogado_id = isset($_POST['abogado_id']) && $_POST['abogado_id'] !== '' ? intval($_POST['abogado_id']) : null;
    $especialidad_id = intval($_POST['especialidad_id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_cierre = $_POST['fecha_cierre'] !== '' ? $_POST['fecha_cierre'] : null;
    $honorarios_estimados = floatval($_POST['honorarios_estimados']);
    $honorarios_cobrados = floatval($_POST['honorarios_cobrados']);

    $stmt = $conn->prepare("UPDATE casos SET numero_expediente=?, cliente_id=?, abogado_id=?, especialidad_id=?, titulo=?, descripcion=?, estado=?, fecha_inicio=?, fecha_cierre=?, honorarios_estimados=?, honorarios_cobrados=? WHERE id=?");
    $stmt->bind_param("siiisssssddi", $numero_expediente, $cliente_id, $abogado_id, $especialidad_id, $titulo, $descripcion, $estado, $fecha_inicio, $fecha_cierre, $honorarios_estimados, $honorarios_cobrados, $id);

    if ($stmt->execute()) {
        header("Location: ver_caso.php?id=".$id);
        exit;
    } else {
        $error = "Error al actualizar: ".$stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Caso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Editar Caso</h4>
        <div>
            <a class="btn btn-secondary" href="casos.php">Volver</a>
            <a class="btn btn-outline-info" href="ver_caso.php?id=<?= $caso['id'] ?>">Ver</a>
        </div>
    </div>

    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" class="card p-3">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">N° Expediente</label>
                <input name="numero_expediente" class="form-control" value="<?= htmlspecialchars($caso['numero_expediente']) ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Título</label>
                <input name="titulo" class="form-control" value="<?= htmlspecialchars($caso['titulo']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select" required>
                    <?php while ($cl = $clientes->fetch_assoc()): ?>
                        <option value="<?= $cl['id'] ?>" <?= $cl['id']==$caso['cliente_id']? 'selected':'' ?>><?= htmlspecialchars($cl['nombre_completo']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Abogado (opcional)</label>
                <select name="abogado_id" class="form-select">
                    <option value="">Sin asignar</option>
                    <?php while ($ab = $abogados->fetch_assoc()): ?>
                        <option value="<?= $ab['id'] ?>" <?= $caso['abogado_id']==$ab['id']? 'selected':'' ?>><?= htmlspecialchars($ab['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Especialidad</label>
                <select name="especialidad_id" class="form-select" required>
                    <?php while ($es = $especialidades->fetch_assoc()): ?>
                        <option value="<?= $es['id'] ?>" <?= $caso['especialidad_id']==$es['id']? 'selected':'' ?>><?= htmlspecialchars($es['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($caso['fecha_inicio']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha cierre</label>
                <input type="date" name="fecha_cierre" class="form-control" value="<?= htmlspecialchars($caso['fecha_cierre']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Honorarios estimados</label>
                <input type="number" step="0.01" name="honorarios_estimados" class="form-control" value="<?= number_format($caso['honorarios_estimados'],2,'.','') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Honorarios cobrados</label>
                <input type="number" step="0.01" name="honorarios_cobrados" class="form-control" value="<?= number_format($caso['honorarios_cobrados'],2,'.','') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($caso['descripcion']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <?php foreach (['activo','pausado','cerrado','archivado'] as $st): ?>
                        <option value="<?= $st ?>" <?= $caso['estado']==$st? 'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn btn-secondary" href="casos.php">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
