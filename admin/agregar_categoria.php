<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    if ($nombre !== '') {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, activo) VALUES (?, 1)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        header("Location: categoria.php?msg=Categoría agregada con éxito");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h3>Nueva Categoría</h3>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="categoria.php" class="btn btn-secondary">Cancelar</a>
    </form>
</body>
</html>
