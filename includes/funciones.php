<?php
// Función para proteger las páginas privadas
function proteger() {
    session_start();
    if (!isset($_SESSION['usuario'])) {
        header("Location: ../index.php");
        exit();
    }
}

// Función para verificar permisos de administrador
function verificarAdmin() {
    if ($_SESSION['rol'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }
}

// Función para verificar permisos de abogado o admin
function verificarAbogado() {
    if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'abogado') {
        header("Location: ../index.php");
        exit();
    }
}

// Función para formatear fecha
function formatearFecha($fecha, $formato = 'd/m/Y') {
    return date($formato, strtotime($fecha));
}

// Función para calcular días restantes
function diasRestantes($fecha_vencimiento) {
    $fecha_venc = strtotime($fecha_vencimiento);
    $fecha_actual = time();
    return ceil(($fecha_venc - $fecha_actual) / (60 * 60 * 24));
}
?>
