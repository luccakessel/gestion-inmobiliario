<?php
require_once "includes/db.php";

header('Content-Type: text/html; charset=utf-8');

echo "<h3>Actualizar esquema: permitir NULL en casos.abogado_id</h3>";

try {
    // Verificar si la tabla existe
    $res = $conn->query("SHOW TABLES LIKE 'casos'");
    if ($res->num_rows === 0) {
        echo "❌ La tabla 'casos' no existe.";
        exit;
    }

    // Obtener definición actual de la columna
    $desc = $conn->query("SHOW COLUMNS FROM casos LIKE 'abogado_id'");
    if ($desc->num_rows === 0) {
        echo "❌ La columna 'abogado_id' no existe en 'casos'.";
        exit;
    }
    $col = $desc->fetch_assoc();

    echo "Columna actual: ".htmlspecialchars(json_encode($col))."<br>";

    // Si es NOT NULL, cambiar a NULL
    if (strtoupper($col['Null']) === 'NO') {
        echo "Aplicando ALTER TABLE...<br>";
        // Mantener el tipo INT y la FK, solo permitir NULL
        $sql = "ALTER TABLE casos MODIFY abogado_id INT NULL";
        if (!$conn->query($sql)) {
            throw new Exception("Error en ALTER TABLE: " . $conn->error);
        }
        echo "✅ 'abogado_id' ahora permite NULL.<br>";
    } else {
        echo "✅ 'abogado_id' ya permite NULL. No se realizaron cambios.<br>";
    }

    echo "<hr><strong>Listo.</strong> Ya puedes crear casos sin asignar abogado.";
} catch (Exception $e) {
    echo "❌ Error: ".$e->getMessage();
}

$conn->close();
