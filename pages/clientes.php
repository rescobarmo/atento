<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$busqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT * FROM clientesredsalud";
$params = [];
if ($busqueda) {
    $sql .= " WHERE nombre LIKE ? OR numero LIKE ? OR sucursal LIKE ?";
    $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%";
}
$sql .= " ORDER BY nombre ASC";
$clientes = $pdo->prepare($sql);
$clientes->execute($params);
$clientes = $clientes->fetchAll();

$total = $pdo->query("SELECT COUNT(*) FROM clientesredsalud")->fetchColumn();
?>
<?php $titulo = 'Clientes'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Clientes Red Salud</h1>
                    <p class="mt-1" style="color:#64748B"><?= $total ?> registros</p>
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
                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <?php if ($busqueda): ?>
                        <a href="clientes.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card rounded-2xl overflow-hidden fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider" style="color:#64748B;background:#F4F8F8">
                                <th class="px-6 py-4 font-medium">Nombre</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Sucursal</th>
                                <th class="px-6 py-4 font-medium">Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($clientes)): ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center" style="color:#64748B">No hay clientes registrados</td></tr>
                            <?php endif; ?>
                            <?php foreach ($clientes as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium" style="color:#1A202C"><?= htmlspecialchars($c['nombre']) ?></td>
                                <td class="px-6 py-4 font-mono text-sm" style="color:#64748B"><?= htmlspecialchars($c['numero']) ?></td>
                                <td class="px-6 py-4" style="color:#64748B"><?= htmlspecialchars($c['sucursal'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-sm" style="color:#64748B"><?= isset($c['created_at']) && $c['created_at'] ? date('d/m/Y H:i', strtotime($c['created_at'])) : '-' ?></td>
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