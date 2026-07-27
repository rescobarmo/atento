<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    $desde = $_GET['desde'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));

    $stmt = $pdo->prepare("SELECT r.*, c.nombre as cliente_nombre, c.sucursal FROM redsalud r LEFT JOIN clientesredsalud c ON r.numero COLLATE utf8mb4_unicode_ci = c.numero WHERE r.fecha_creacion > ? ORDER BY r.fecha_creacion ASC");
    $stmt->execute([$desde]);
    $registros = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'total' => count($registros),
        'ultimo' => $registros ? end($registros)['fecha_creacion'] : $desde,
        'data' => $registros
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
