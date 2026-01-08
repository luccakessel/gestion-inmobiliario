<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

$id = (int)($_GET['id'] ?? 0);

// Obtener categoría por ID
$result = $conn->prepare("SELECT * FROM categorias WHERE id = ?");
$result->bind_param("i", $id);
$result->execute();
$categoria = $result->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
    $stmt->bind_param("si", $nombre, $id);
    $stmt->execute();
    header("Location: categoria.php?msg=Categoría actualizada con éxito");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h3>Editar Categoría</h3>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" 
                   value="<?= htmlspecialchars($categoria['nombre']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="categoria.php" class="btn btn-secondary">Cancelar</a>
    </form>
</body>
</html>
