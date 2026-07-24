<?php
require_once __DIR__ . '/includes/auth.php';
requerirLogin();
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE redsalud ADD COLUMN horario VARCHAR(20) DEFAULT NULL AFTER obs");
    echo "<h2 style='color:green;font-family:sans-serif'>OK: columna horario agregada.</h2>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<h2 style='color:green;font-family:sans-serif'>OK: columna horario ya existe.</h2>";
    } else {
        echo "<h2 style='color:red;font-family:sans-serif'>ERROR: " . htmlspecialchars($e->getMessage()) . "</h2>";
    }
}
