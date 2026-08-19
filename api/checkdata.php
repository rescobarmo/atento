<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    $tablas = ['redsalud', 'clientesredsalud', 'campanas', 'canales', 'leads', 'metricas_campana', 'conversiones', 'visitas_sitio'];
    $result = [];
    foreach ($tablas as $t) {
        $row = $pdo->query("SELECT COUNT(*) as c FROM `$t`")->fetch();
        $result[$t] = (int)$row['c'];
    }
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
