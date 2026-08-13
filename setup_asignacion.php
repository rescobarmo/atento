<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup Asignación de Leads</title>";
echo "<script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-900 text-white p-8'>";
echo "<div class='max-w-2xl mx-auto'><h1 class='text-2xl font-bold mb-6'>Configuración de Asignación de Leads</h1>";

$pdo = getDB();

try {
    $pdo->exec("ALTER TABLE redsalud ADD COLUMN agente_id INT DEFAULT NULL AFTER obs");
    echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'agente_id' agregada a redsalud</div>";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "<div class='bg-green-900/50 border border-green-700 rounded p-3 mb-2 text-sm'>OK: columna 'agente_id' ya existe</div>";
    } else {
        echo "<div class='bg-yellow-900/50 border border-yellow-700 rounded p-3 mb-2 text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "<div class='mt-6 bg-blue-900/50 border border-blue-700 rounded p-4 text-blue-300'>";
$agentes = $pdo->query("SELECT u.id, u.nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1 ORDER BY u.nombre")->fetchAll();
echo "Agentes disponibles para asignar leads:<br>";
if (empty($agentes)) {
    echo "<span class='text-slate-400'>No hay agentes activos. Ejecuta setup_agentes.php primero.</span>";
} else {
    foreach ($agentes as $a) {
        echo "👤 " . htmlspecialchars($a['nombre']) . "<br>";
    }
}
echo "</div>";

echo "<div class='mt-4 flex gap-3'>";
echo "<a href='pages/asignar_leads.php' class='px-6 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition'>Ir a Asignación de Leads</a>";
echo "<a href='index.php' class='px-6 py-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition'>Ir al Login</a>";
echo "</div>";

echo "</div></body></html>";
