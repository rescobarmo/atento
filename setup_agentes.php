<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup Agentes de Ventas</title>";
echo "<script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-900 text-white p-8'>";
echo "<div class='max-w-2xl mx-auto'><h1 class='text-2xl font-bold mb-6'>Configuración de Agentes de Ventas</h1>";

$pdo = getDB();

try {
    $pdo->exec("INSERT INTO roles (nombre) VALUES ('Agente de Ventas') ON DUPLICATE KEY UPDATE nombre = nombre");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: rol 'Agente de Ventas' verificado</div>";
} catch (Exception $e) {
    echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
}

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN username VARCHAR(50) DEFAULT NULL AFTER email");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'username' agregada a usuarios</div>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'username' ya existe</div>";
    } else {
        echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN sucursal VARCHAR(255) DEFAULT NULL AFTER rol_id");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'sucursal' agregada a usuarios</div>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'sucursal' ya existe</div>";
    } else {
        echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$sucursales = [];
try {
    $sucursales = $pdo->query("SELECT DISTINCT sucursal FROM clientesredsalud WHERE sucursal IS NOT NULL AND sucursal != '' ORDER BY sucursal")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$rolId = $pdo->query("SELECT id FROM roles WHERE nombre = 'Agente de Ventas'")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
$stmt->execute(['agente@redsalud.cl']);
if ((int)$stmt->fetchColumn() === 0 && $rolId) {
    $sucursal = $sucursales[0] ?? null;
    try {
        $pdo->prepare("INSERT INTO usuarios (nombre, email, username, password, rol_id, sucursal, activo) VALUES (?, ?, ?, ?, ?, ?, 1)")
            ->execute([
                'Agente de Ventas',
                'agente@redsalud.cl',
                'agente',
                password_hash('r3dsalud', PASSWORD_DEFAULT),
                $rolId,
                $sucursal,
            ]);
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: usuario 'agente' creado (agente@redsalud.cl / r3dsalud)" . ($sucursal ? " - Sucursal: $sucursal" : '') . "</div>";
    } catch (Exception $e) {
        echo "<div class='bg-red-900/50 border border-red-700 rounded p-3 mb-2 text-sm'>ERROR creando usuario: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: usuario 'agente' ya existe</div>";
}

echo "<div class='mt-6 bg-blue-900/50 border border-blue-700 rounded p-4 text-blue-300'>";
echo "Usuarios con rol Agente de Ventas:<br>";
$agentes = $pdo->query("SELECT u.nombre, u.username, u.email, u.sucursal FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1")->fetchAll();
if (empty($agentes)) {
    echo "<span class='text-slate-400'>No hay agentes activos aún.</span>";
} else {
    foreach ($agentes as $a) {
        echo "👤 " . htmlspecialchars($a['nombre']) . " — usuario: <b>" . htmlspecialchars($a['username'] ?? $a['email']) . "</b> — sucursal: " . htmlspecialchars($a['sucursal'] ?? 'Sin asignar') . "<br>";
    }
}
echo "</div>";

echo "<div class='mt-4 flex gap-3'>";
echo "<a href='index.php' class='px-6 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition'>Ir al Login</a>";
echo "</div>";

echo "</div></body></html>";
