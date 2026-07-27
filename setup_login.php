<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN username VARCHAR(50) DEFAULT NULL AFTER email");
    echo "OK: columna username agregada.<br>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "OK: columna username ya existe.<br>";
    } else {
        echo "ERROR: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

$hash = password_hash('r3dsalud', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE usuarios SET username = 'admin' WHERE email = 'admin@redsalud.cl'");
$stmt->execute();
echo "OK: username 'admin' asignado a admin@redsalud.cl<br>";

$stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);
echo "OK: password actualizada a 'r3dsalud'<br>";

echo "<p style='color:green;font-size:18px'>Listo. Ahora ingresa con usuario: <b>admin</b> / clave: <b>r3dsalud</b></p>";