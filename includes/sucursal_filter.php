<?php
if (isset($_GET['sucursal'])) {
    $_SESSION['sucursal'] = $_GET['sucursal'];
}

$sucursalSeleccionada = $_SESSION['sucursal'] ?? '';

$sucursales = $pdo->query("SELECT DISTINCT sucursal FROM clientesredsalud WHERE sucursal IS NOT NULL AND sucursal != '' ORDER BY sucursal")->fetchAll(PDO::FETCH_COLUMN);

$joinSuc = "LEFT JOIN clientesredsalud c ON r.numero COLLATE utf8mb4_unicode_ci = c.numero";
$joinSuc2 = "LEFT JOIN clientesredsalud c2 ON r2.numero COLLATE utf8mb4_unicode_ci = c2.numero";
$whereSuc = $sucursalSeleccionada ? "AND c.sucursal = " . $pdo->quote($sucursalSeleccionada) : '';
$whereSuc2 = $sucursalSeleccionada ? "AND c2.sucursal = " . $pdo->quote($sucursalSeleccionada) : '';
