<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();
$usuario = usuarioActual();

if (strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas') {
    header('Location: ' . APP_URL . '/pages/agentes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asignaciones = $_POST['asignacion'] ?? [];
    $borradas = 0;
    $insertadas = 0;

    $del = $pdo->prepare("DELETE FROM agente_sucursales WHERE agente_id = ?");
    $ins = $pdo->prepare("INSERT IGNORE INTO agente_sucursales (agente_id, sucursal) VALUES (?, ?)");

    foreach ($asignaciones as $agenteId => $sucursales) {
        $agenteId = (int)$agenteId;
        if ($agenteId <= 0) continue;
        $del->execute([$agenteId]);
        $borradas++;
        $sucursales = is_array($sucursales) ? $sucursales : [];
        foreach ($sucursales as $sucursal) {
            if ($sucursal === '') continue;
            $ins->execute([$agenteId, $sucursal]);
            $insertadas++;
        }
    }

    $mensaje = "Asignaciones guardadas: $insertadas sucursales registradas en $borradas agentes.";
}

$agentes = $pdo->query("
    SELECT u.id, u.nombre, u.email, u.sucursal
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1
    ORDER BY u.nombre
")->fetchAll();

$sucursales = $pdo->query("SELECT DISTINCT sucursal FROM clientesredsalud WHERE sucursal IS NOT NULL AND sucursal != '' ORDER BY sucursal")->fetchAll(PDO::FETCH_COLUMN);

$asignadas = $pdo->query("SELECT agente_id, sucursal FROM agente_sucursales")->fetchAll();
$mapa = [];
foreach ($asignadas as $a) {
    $mapa[(int)$a['agente_id']][] = $a['sucursal'];
}
?>
<?php $titulo = 'Mantenedor de Agentes'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Mantenedor de Agentes</h1>
                    <p class="mt-1" style="color:#64748B">Asigna las sucursales que atiende cada agente de ventas</p>
                </div>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="rounded-2xl px-4 py-3 mb-6 text-sm flex items-center gap-2" style="background:rgba(22,163,74,0.1);border:1px solid rgba(22,163,74,0.25);color:#16a34a">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($agentes)): ?>
                <div class="card rounded-2xl p-12 text-center" style="color:#64748B">
                    <i class="fas fa-user-tie text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">No hay agentes de ventas activos</p>
                    <p class="text-sm mt-1">Ejecuta setup_agentes.php para crear uno o verifica el rol en la base de datos.</p>
                </div>
            <?php elseif (empty($sucursales)): ?>
                <div class="card rounded-2xl p-12 text-center" style="color:#64748B">
                    <i class="fas fa-store text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">No hay sucursales registradas</p>
                    <p class="text-sm mt-1">La lista de sucursales se obtiene de la tabla clientesredsalud.</p>
                </div>
            <?php else: ?>
                <form method="POST" class="card rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider" style="color:#64748B;background:#F4F8F8">
                                    <th class="px-6 py-4 font-medium">Agente</th>
                                    <th class="px-6 py-4 font-medium">Sucursales asignadas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color:#E2E8F0">
                                <?php foreach ($agentes as $a): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 align-top">
                                        <p class="font-medium" style="color:#1A202C"><?= htmlspecialchars($a['nombre']) ?></p>
                                        <p class="text-xs mt-0.5" style="color:#94a3b8"><?= htmlspecialchars($a['email']) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <?php foreach ($sucursales as $s): ?>
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none px-3 py-1.5 rounded-lg border text-xs transition-colors"
                                                       style="border-color:#E2E8F0;background:#FAFAFA;color:#1A202C">
                                                    <input type="checkbox" name="asignacion[<?= (int)$a['id'] ?>][]" value="<?= htmlspecialchars($s) ?>"
                                                           class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                                           <?= in_array($s, $mapa[(int)$a['id']] ?? [], true) ? 'checked' : '' ?>>
                                                    <?= htmlspecialchars($s) ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 flex items-center justify-between" style="background:#F4F8F8">
                        <p class="text-xs" style="color:#64748B">El agente verá los leads de todas las sucursales marcadas.</p>
                        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                            <i class="fas fa-save mr-1"></i> Guardar Asignaciones
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>