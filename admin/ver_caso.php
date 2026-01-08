<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

if (!isset($_GET['id'])) {
    die("Caso no especificado");
}
$id = intval($_GET['id']);

$sql = "SELECT c.*, 
               CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
               u.nombre as abogado_nombre,
               e.nombre as especialidad_nombre
        FROM casos c
        LEFT JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN usuarios u ON c.abogado_id = u.id
        LEFT JOIN especialidades e ON c.especialidad_id = e.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$caso = $stmt->get_result()->fetch_assoc();

if (!$caso) {
    die("Caso no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Caso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Expediente <?= htmlspecialchars($caso['numero_expediente']) ?> - <?= htmlspecialchars($caso['titulo']) ?></h4>
            <div>
                <a class="btn btn-secondary" href="casos.php">Volver</a>
                <a class="btn btn-primary" href="editar_caso.php?id=<?= $caso['id'] ?>">Editar</a>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($caso['cliente_nombre']) ?></p>
                <p><strong>Abogado:</strong> <?= $caso['abogado_nombre'] ? htmlspecialchars($caso['abogado_nombre']) : 'Sin asignar' ?></p>
                <p><strong>Especialidad:</strong> <?= htmlspecialchars($caso['especialidad_nombre']) ?></p>
                <p><strong>Estado:</strong> <?= ucfirst($caso['estado']) ?></p>
                <p><strong>Fecha inicio:</strong> <?= htmlspecialchars($caso['fecha_inicio']) ?></p>
                <?php if ($caso['fecha_cierre']): ?><p><strong>Fecha cierre:</strong> <?= htmlspecialchars($caso['fecha_cierre']) ?></p><?php endif; ?>
                <p><strong>Honorarios estimados:</strong> $<?= number_format($caso['honorarios_estimados'],2) ?></p>
                <p><strong>Honorarios cobrados:</strong> $<?= number_format($caso['honorarios_cobrados'],2) ?></p>
                <?php if ($caso['descripcion']): ?><p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($caso['descripcion'])) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
