<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$busqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT r.numero, COALESCE(NULLIF(r.nombre, ''), 'Sin nombre') as nombre,
               COUNT(*) as total_msgs, MAX(r.fecha_creacion) as ultimo_contacto,
               MAX(r.categoria_cliente) as categoria_cliente
        FROM redsalud r
        LEFT JOIN clientesredsalud c ON r.numero COLLATE utf8mb4_unicode_ci = c.numero
        WHERE c.numero IS NULL";
$params = [];

if ($busqueda) {
    $sql .= " AND (r.nombre LIKE ? OR r.numero LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " GROUP BY r.numero, r.nombre ORDER BY ultimo_contacto DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$numeros = $stmt->fetchAll();

$total = $pdo->query("SELECT COUNT(DISTINCT r.numero)
    FROM redsalud r
    LEFT JOIN clientesredsalud c ON r.numero COLLATE utf8mb4_unicode_ci = c.numero
    WHERE c.numero IS NULL")->fetchColumn();
?>
<?php $titulo = 'No Contesta'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">No Contesta</h1>
                    <p class="mt-1" style="color:#64748B"><?= $total ?> números no registrados como clientes</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Total Números</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(229,62,62,0.12)">
                            <i class="fas fa-phone-slash text-lg" style="color:#E53E3E"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $total ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">sin cliente asociado</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Total Mensajes</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,0.12)">
                            <i class="fas fa-comments text-lg" style="color:#F59E0B"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= array_sum(array_column($numeros, 'total_msgs')) ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">mensajes enviados</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Contactos RedSalud</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-database text-lg" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $pdo->query("SELECT COUNT(*) FROM clientesredsalud")->fetchColumn() ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">clientes registrados</p>
                </div>
            </div>

            <div class="card rounded-2xl p-5 mb-6 fade-in">
                <form method="GET" class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                               placeholder="Buscar por nombre o teléfono..."
                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <?php if ($busqueda): ?>
                        <a href="no_contesta.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
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
                                <th class="px-6 py-4 font-medium">Mensajes</th>
                                <th class="px-6 py-4 font-medium">Último Contacto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($numeros)): ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center" style="color:#64748B">No se encontraron números sin cliente asociado</td></tr>
                            <?php endif; ?>
                            <?php foreach ($numeros as $n): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium" style="color:#1A202C"><?= htmlspecialchars($n['nombre']) ?></td>
                                <td class="px-6 py-4 font-mono text-sm" style="color:#64748B"><?= htmlspecialchars($n['numero']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">
                                        <?= $n['total_msgs'] ?> msgs
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm" style="color:#64748B"><?= date('d/m/Y H:i', strtotime($n['ultimo_contacto'])) ?></td>
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
