<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

require_once __DIR__ . '/../includes/sucursal_filter.php';

$filtroBusqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT r.*, c.nombre as cliente_nombre, c.sucursal, ag.nombre as agente_nombre FROM redsalud r $joinSuc LEFT JOIN usuarios ag ON ag.id = r.agente_id";
$where = ["(LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')"];
$params = [];

if ($filtroBusqueda) {
    $where[] = "(r.nombre LIKE ? OR r.numero LIKE ? OR r.conversacion LIKE ?)";
    $p = "%$filtroBusqueda%";
    $params[] = $p; $params[] = $p; $params[] = $p;
}
$where[] = "1=1 " . $whereSuc;
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY r.fecha_actualizacion DESC, r.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conversaciones = $stmt->fetchAll();
?>
<?php $titulo = 'Contactos Atento'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#FFFFFF">Contactos Cotizando</h1>
                    <p class="mt-1" style="color:rgba(255,255,255,0.7)">Gestiona contactos que están cotizando y registra llamadas</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= APP_URL ?>/api/export_excel.php?leads=1&busqueda=<?= urlencode($filtroBusqueda) ?>" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium text-white inline-flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </a>
                </div>
            </div>

            <div class="card rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($filtroBusqueda) ?>"
                               placeholder="Buscar por nombre, teléfono o conversación..."
                               class="w-full px-4 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.08);color:#FFFFFF">
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <?php if ($filtroBusqueda): ?>
                        <a href="leads.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.7)">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider" style="color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.08)">
                                <th class="px-6 py-4 font-medium">Nombre</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Categoría</th>
                                <th class="px-6 py-4 font-medium">Horario</th>
                                <th class="px-6 py-4 font-medium">Agente Asignado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:rgba(255,255,255,0.15)">
                            <?php if (empty($conversaciones)): ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-white/50">No se encontraron registros</td></tr>
                            <?php endif; ?>
                            <?php foreach ($conversaciones as $r): ?>
                            <?php
                                $cat = strtolower($r['categoria_cliente'] ?? '');
                                $colorBg = match($cat) {
                                    'cotizando' => '#dc2626',
                                    'respondio' => '#16a34a',
                                    'realizado' => '#2563eb',
                                    'llamado' => '#9333ea',
                                    default => '#64748b'
                                };
                            ?>
                            <tr class="hover:bg-white/5 transition-colors" data-id="<?= htmlspecialchars($r['id']) ?>">
                                <td class="px-6 py-4">
                                    <p class="font-medium" style="color:#FFFFFF"><?= htmlspecialchars($r['cliente_nombre'] ?: $r['nombre'] ?? 'Sin nombre') ?></p>
                                    <?php if ($r['cliente_nombre'] && $r['sucursal']): ?>
                                        <p class="text-xs" style="color:rgba(255,255,255,0.7)"><?= htmlspecialchars($r['sucursal']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm text-white/70"><?= htmlspecialchars($r['numero']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white" style="background:<?= $colorBg ?>">
                                        <?= htmlspecialchars(strtoupper($cat ?: 'SIN CATEGORÍA')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm" style="color:rgba(255,255,255,0.7)">
                                    <?= htmlspecialchars($r['horario'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($r['agente_nombre'])): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-medium" style="background:rgba(0,163,224,0.1);color:#FFFFFF">
                                            <i class="fas fa-user-tie"></i>
                                            <?= htmlspecialchars($r['agente_nombre']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-white/50">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
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
