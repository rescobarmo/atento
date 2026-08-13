<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup Mantenedor de Agentes</title>";
echo "<script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-900 text-white p-8'>";
echo "<div class='max-w-2xl mx-auto'><h1 class='text-2xl font-bold mb-6'>Configuración del Mantenedor de Agentes</h1>";

$pdo = getDB();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS agente_sucursales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agente_id INT NOT NULL,
        sucursal VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_agente_sucursal (agente_id, sucursal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: tabla 'agente_sucursales' creada/verificada</div>";
} catch (Exception $e) {
    echo "<div class='bg-red-900/50 border border-red-700 rounded p-3 mb-2 text-sm'>ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
}

try {
    $pdo->exec("INSERT IGNORE INTO agente_sucursales (agente_id, sucursal)
        SELECT u.id, u.sucursal FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1 AND u.sucursal IS NOT NULL AND u.sucursal != ''");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: sucursales migradas desde usuarios</div>";
} catch (Exception $e) {
    echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
}

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN limite_leads INT DEFAULT 0 AFTER sucursal");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'limite_leads' agregada a usuarios</div>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'limite_leads' ya existe</div>";
    } else {
        echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN numero_contacto VARCHAR(50) DEFAULT NULL AFTER limite_leads");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'numero_contacto' agregada a usuarios</div>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'numero_contacto' ya existe</div>";
    } else {
        echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "<div class='mt-6 bg-blue-900/50 border border-blue-700 rounded p-4 text-blue-300'>";
$rows = $pdo->query("SELECT u.nombre, GROUP_CONCAT(DISTINCT asg.sucursal ORDER BY asg.sucursal SEPARATOR ', ') as sucursales
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    LEFT JOIN agente_sucursales asg ON u.id = asg.agente_id
    WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1
    GROUP BY u.id, u.nombre ORDER BY u.nombre")->fetchAll();
if (empty($rows)) {
    echo "<span class='text-slate-400'>No hay agentes activos. Ejecuta setup_agentes.php primero.</span>";
} else {
    foreach ($rows as $r) {
        echo "👤 " . htmlspecialchars($r['nombre']) . " → <b>" . htmlspecialchars($r['sucursales'] ?? 'Sin sucursal') . "</b><br>";
    }
}
echo "</div>";

echo "<div class='mt-4 flex gap-3'>";
echo "<a href='pages/mantenedor_agentes.php' class='px-6 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition'>Ir al Mantenedor de Agentes</a>";
echo "<a href='index.php' class='px-6 py-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition'>Ir al Login</a>";
echo "</div>";

echo "</div></body></html>";
