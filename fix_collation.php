<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "<h2>Collations:</h2>";
$tables = ['redsalud', 'clientesredsalud'];
foreach ($tables as $t) {
    $r = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_NAME = '$t' AND TABLE_SCHEMA = 'marketing'")->fetch();
    echo "<p>$t: " . ($r['TABLE_COLLATION'] ?? 'N/A') . "</p>";

    $cols = $pdo->query("SHOW FULL COLUMNS FROM $t")->fetchAll();
    foreach ($cols as $c) {
        if ($c['Field'] === 'numero') {
            echo "<p>&nbsp;&nbsp;numero collation: " . ($c['Collation'] ?? 'N/A') . "</p>";
        }
    }
}

$pdo->exec("ALTER TABLE clientesredsalud CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "<p style='color:green'>OK: clientesredsalud convertida a utf8mb4_unicode_ci</p>";

$pdo->exec("ALTER TABLE clientesredsalud MODIFY numero VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
echo "<p style='color:green'>OK: columna numero collation corregida</p>";