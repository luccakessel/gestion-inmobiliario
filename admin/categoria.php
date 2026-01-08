<?php
require_once("../includes/funciones.php");
require_once("../includes/db.php");
proteger();

// === Cambiar estado ===
if (isset($_GET['cambiar_estado'])) {
    $id = (int)$_GET['cambiar_estado'];
    $nuevo_estado = (int)$_GET['estado'];
    $stmt = $conn->prepare("UPDATE categorias SET activo = ? WHERE id = ?");
    $stmt->bind_param("ii", $nuevo_estado, $id);
    $stmt->execute();
    header("Location: categoria.php?msg=Categoría actualizada");
    exit();
}

// === Listar categorías ===
$resultado = $conn->query("SELECT * FROM categorias ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #c62828;
        }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: var(--primary-color); color: white; padding: 20px 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 10px 20px; margin: 5px 0; border-radius: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 px-0 sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">Carnicería</h4>
                <p class="text-white-50 small">Gestión de Categorías</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="panel.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="productos.php" class="nav-link"><i class="bi bi-box-seam"></i> Productos</a></li>
                <li class="nav-item"><a href="categorias.php" class="nav-link active"><i class="bi bi-tags"></i> Categorías</a></li>
            </ul>
        </div>

        <!-- Main -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Gestión de Categorías</h4>
                <a href="agregar_categoria.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nueva Categoría
                </a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <!-- Lista -->
            <div class="card">
                <div class="card-header">Lista de Categorías</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = $resultado->fetch_assoc()): ?>
                                <tr class="<?= $fila['activo'] ? '' : 'table-secondary' ?>">
                                    <td><?= $fila['id'] ?></td>
                                    <td><?= htmlspecialchars($fila['nombre']) ?></td>
                                    <td><?= $fila['activo'] ? 'Activo' : 'Inhabilitado' ?></td>
                                    <td>
                                        <a href="editar_categoria.php?id=<?= $fila['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <?php if ($fila['activo']): ?>
                                            <a href="categorias.php?cambiar_estado=<?= $fila['id'] ?>&estado=0" class="btn btn-sm btn-outline-warning">Inhabilitar</a>
                                        <?php else: ?>
                                            <a href="categorias.php?cambiar_estado=<?= $fila['id'] ?>&estado=1" class="btn btn-sm btn-outline-success">Habilitar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
