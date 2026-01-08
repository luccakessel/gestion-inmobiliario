<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

if (!isset($_GET['id'])) { die("Cliente no especificado"); }
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
if (!$cliente) { die("Cliente no encontrado"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $profesion = trim($_POST['profesion']);
    $estado_civil = $_POST['estado_civil'];

    $stmt = $conn->prepare("UPDATE clientes SET nombre=?, apellido=?, dni=?, telefono=?, email=?, direccion=?, fecha_nacimiento=?, profesion=?, estado_civil=? WHERE id=?");
    $stmt->bind_param("sssssssssi", $nombre, $apellido, $dni, $telefono, $email, $direccion, $fecha_nacimiento, $profesion, $estado_civil, $id);
    if ($stmt->execute()) {
        header("Location: ver_cliente.php?id=".$id);
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
    <title>Editar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Editar Cliente</h4>
        <div>
            <a class="btn btn-secondary" href="clientes.php">Volver</a>
            <a class="btn btn-outline-info" href="ver_cliente.php?id=<?= $cliente['id'] ?>">Ver</a>
        </div>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="card p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input name="nombre" class="form-control" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Apellido</label>
                <input name="apellido" class="form-control" value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">DNI</label>
                <input name="dni" class="form-control" value="<?= htmlspecialchars($cliente['dni']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Teléfono</label>
                <input name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <input name="direccion" class="form-control" value="<?= htmlspecialchars($cliente['direccion']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($cliente['fecha_nacimiento']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Profesión</label>
                <input name="profesion" class="form-control" value="<?= htmlspecialchars($cliente['profesion']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado civil</label>
                <select name="estado_civil" class="form-select">
                    <?php foreach (['soltero','casado','divorciado','viudo','concubinato'] as $ec): ?>
                        <option value="<?= $ec ?>" <?= $cliente['estado_civil']===$ec?'selected':'' ?>><?= ucfirst($ec) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn btn-secondary" href="clientes.php">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
