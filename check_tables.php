<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();
echo "<h2>Tabla: clientesredsaud</h2>";
$r = $pdo->query("SELECT COUNT(*) FROM clientesredsaud")->fetchColumn();
echo "<p>Registros: $r</p>";
if ($r > 0) {
    $data = $pdo->query("SELECT * FROM clientesredsaud LIMIT 5")->fetchAll();
    echo "<pre>"; print_r($data); echo "</pre>";
}
echo "<hr>";
echo "<h2>Tabla: redsalud</h2>";
$r2 = $pdo->query("SELECT COUNT(*) FROM redsalud")->fetchColumn();
echo "<p>Registros: $r2</p>";