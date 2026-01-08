<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

if (!isset($_GET['id'])) {
    die("Cliente no especificado");
}
$id = intval($_GET['id']);

$sql = "SELECT * FROM clientes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
if (!$cliente) { die("Cliente no encontrado"); }

// Traer casos del cliente
$casos = $conn->prepare("SELECT id, numero_expediente, titulo, estado FROM casos WHERE cliente_id = ? ORDER BY fecha_inicio DESC");
$casos->bind_param("i", $id);
$casos->execute();
$lista_casos = $casos->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Cliente: <?= htmlspecialchars($cliente['nombre'].' '.$cliente['apellido']) ?></h4>
        <div>
            <a class="btn btn-secondary" href="clientes.php">Volver</a>
            <a class="btn btn-primary" href="editar_cliente.php?id=<?= $cliente['id'] ?>">Editar</a>
        </div>
    </div>
    <div class="card mb-3"><div class="card-body">
        <p><strong>DNI:</strong> <?= htmlspecialchars($cliente['dni']) ?></p>
        <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
        <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion']) ?></p>
        <?php if ($cliente['fecha_nacimiento']): ?><p><strong>Nacimiento:</strong> <?= htmlspecialchars($cliente['fecha_nacimiento']) ?></p><?php endif; ?>
        <p><strong>Estado civil:</strong> <?= ucfirst($cliente['estado_civil']) ?></p>
        <p><strong>Profesión:</strong> <?= htmlspecialchars($cliente['profesion']) ?></p>
    </div></div>

    <div class="card">
        <div class="card-header">Casos</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Expediente</th><th>Título</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php while ($c = $lista_casos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['numero_expediente']) ?></td>
                        <td><?= htmlspecialchars($c['titulo']) ?></td>
                        <td><?= ucfirst($c['estado']) ?></td>
                        <td><a class="btn btn-sm btn-outline-info" href="ver_caso.php?id=<?= $c['id'] ?>">Ver</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
