<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

require_once __DIR__ . '/../includes/sucursal_filter.php';

$busqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT * FROM clientesredsalud WHERE 1=1";
$params = [];
if ($sucursalSeleccionada) {
    $sql .= " AND sucursal = " . $pdo->quote($sucursalSeleccionada);
}
if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR numero LIKE ? OR sucursal LIKE ?)";
    $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%";
}
$sql .= " ORDER BY nombre ASC";
$clientes = $pdo->prepare($sql);
$clientes->execute($params);
$clientes = $clientes->fetchAll();

$total = $pdo->query("SELECT COUNT(*) FROM clientesredsalud" . ($sucursalSeleccionada ? " WHERE sucursal = " . $pdo->quote($sucursalSeleccionada) : ""))->fetchColumn();
?>
<?php $titulo = 'Clientes'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#FFFFFF">Clientes Red Salud</h1>
                    <p class="mt-1" style="color:rgba(255,255,255,0.7)"><?= $total ?> registros</p>
                </div>
                <a href="importar_clientes.php" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                    <i class="fas fa-upload"></i> Importar Excel
                </a>
            </div>

            <div class="card rounded-2xl p-5 mb-6 fade-in">
                <form method="GET" class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                               placeholder="Buscar por nombre, teléfono o sucursal..."
                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none" style="border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.08);color:#FFFFFF">
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <?php if ($busqueda): ?>
                        <a href="clientes.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.7)">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card rounded-2xl overflow-hidden fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider" style="color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.08)">
                                <th class="px-6 py-4 font-medium">Nombre</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Sucursal</th>
                                <th class="px-6 py-4 font-medium">Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:rgba(255,255,255,0.15)">
                            <?php if (empty($clientes)): ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center" style="color:rgba(255,255,255,0.7)">No hay clientes registrados</td></tr>
                            <?php endif; ?>
                            <?php foreach ($clientes as $c): ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-medium" style="color:#FFFFFF"><?= htmlspecialchars($c['nombre']) ?></td>
                                <td class="px-6 py-4 font-mono text-sm" style="color:rgba(255,255,255,0.7)"><?= htmlspecialchars($c['numero']) ?></td>
                                <td class="px-6 py-4" style="color:rgba(255,255,255,0.7)"><?= htmlspecialchars($c['sucursal'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-sm" style="color:rgba(255,255,255,0.7)"><?= isset($c['created_at']) && $c['created_at'] ? date('d/m/Y H:i', strtotime($c['created_at'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>