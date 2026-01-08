<?php
require_once("../includes/db.php");

// Ventas por mes (últimos 6 meses) -> facturación en pesos
$sqlMeses = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, 
                    SUM(cantidad * precio_unitario) AS total 
             FROM ventas 
             GROUP BY mes 
             ORDER BY mes DESC 
             LIMIT 6";
$resultMeses = $conn->query($sqlMeses);

$ventasMes = [];
while ($row = $resultMeses->fetch_assoc()) {
    $ventasMes[] = $row;
}

// Productos más vendidos (top 5) -> por cantidad
$sqlProductos = "SELECT p.nombre, SUM(v.cantidad) AS total 
                 FROM ventas v 
                 JOIN productos p ON v.producto_id = p.id
                 GROUP BY p.id 
                 ORDER BY total DESC 
                 LIMIT 5";
$resultProductos = $conn->query($sqlProductos);

$productosVendidos = [];
while ($row = $resultProductos->fetch_assoc()) {
    $productosVendidos[] = $row;
}

// Ventas por categoría
$sqlCategorias = "SELECT c.nombre AS categoria, SUM(v.cantidad) AS cantidad, 
                  SUM(v.cantidad * v.precio_unitario) AS total
                  FROM ventas v
                  JOIN productos p ON v.producto_id = p.id
                  JOIN categorias c ON p.categoria_id = c.id
                  GROUP BY c.id
                  ORDER BY total DESC";
$resultCategorias = $conn->query($sqlCategorias);

$ventasCategorias = [];
while ($row = $resultCategorias->fetch_assoc()) {
    $ventasCategorias[] = $row;
}

// Respuesta en JSON
echo json_encode([
    "ventasMes" => array_reverse($ventasMes),  // en orden cronológico
    "productosVendidos" => $productosVendidos,
    "ventasCategorias" => $ventasCategorias
]);
