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
    echo "<h2 style='color:green;font-family:sans-serif'>OK: tabla clientes creada/verificada.</h2>";
} catch (Exception $e) {
    echo "<h2 style='color:red;font-family:sans-serif'>ERROR crear tabla: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
try {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN nombre VARCHAR(255) NOT NULL AFTER id");
    echo "<p style='color:green'>OK: columna nombre agregada</p>";
} catch (Exception $e) {
    echo "<p style='color:green'>OK: columna nombre ya existe</p>";
}
try {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN numero VARCHAR(50) NOT NULL AFTER nombre");
    echo "<p style='color:green'>OK: columna numero agregada</p>";
} catch (Exception $e) {
    echo "<p style='color:green'>OK: columna numero ya existe</p>";
}
try {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN sucursal VARCHAR(255) DEFAULT NULL AFTER numero");
    echo "<p style='color:green'>OK: columna sucursal agregada</p>";
} catch (Exception $e) {
    echo "<p style='color:green'>OK: columna sucursal ya existe</p>";
}