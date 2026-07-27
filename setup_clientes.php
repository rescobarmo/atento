<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        numero VARCHAR(50) NOT NULL,
        sucursal VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<h2 style='color:green;font-family:sans-serif'>OK: tabla clientes creada.</h2>";
} catch (Exception $e) {
    echo "<h2 style='color:red;font-family:sans-serif'>ERROR: " . htmlspecialchars($e->getMessage()) . "</h2>";
}