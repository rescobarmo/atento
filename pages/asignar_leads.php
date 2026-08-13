<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();
$usuario = usuarioActual();

if (strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas') {
    header('Location: ' . APP_URL . '/pages/agentes.php');
    exit;
}

require_once __DIR__ . '/../includes/sucursal_filter.php';

$filtroBusqueda = $_GET['busqueda'] ?? '';
$filtroAgente = $_GET['agente'] ?? '';

$agentes = $pdo->query("
    SELECT u.id, u.nombre, u.sucursal
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE r.nombre = 'Agente de Ventas' AND u.activo = 1
    ORDER BY u.nombre
")->fetchAll();

$sql = "SELECT r.*, c.nombre as cliente_nombre, c.sucursal, a.nombre as agente_nombre
        FROM redsalud r
        $joinSuc
        LEFT JOIN usuarios a ON r.agente_id = a.id
        WHERE (LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')
        AND 1=1 $whereSuc";
$params = [];

if ($filtroBusqueda) {
    $sql .= " AND (r.nombre LIKE ? OR r.numero LIKE ? OR r.conversacion LIKE ?)";
    $p = "%$filtroBusqueda%";
    $params[] = $p; $params[] = $p; $params[] = $p;
}
if ($filtroAgente !== '') {
    if ($filtroAgente === 'sin') {
        $sql .= " AND r.agente_id IS NULL";
    } else {
        $sql .= " AND r.agente_id = ?";
        $params[] = (int)$filtroAgente;
    }
}
$sql .= " ORDER BY r.fecha_actualizacion DESC, r.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$resumen = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        COUNT(r.agente_id) as asignados
    FROM redsalud r
    $joinSuc
    WHERE (LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')
    AND 1=1 $whereSuc
");
$resumen->execute();
$resumen = $resumen->fetch() ?: ['total' => 0, 'asignados' => 0];
?>
<?php $titulo = 'Asignación de Leads'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Asignación de Leads</h1>
                    <p class="mt-1" style="color:#64748B">Asigna cada lead a un agente de ventas</p>
                </div>
                <div class="flex items-center gap-3 text-sm" style="color:#64748B">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 font-medium">
                        <i class="fas fa-check-circle mr-1"></i> <?= (int)$resumen['asignados'] ?> / <?= (int)$resumen['total'] ?> asignados
                    </span>
                </div>
            </div>

            <div class="card rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($filtroBusqueda) ?>"
                               placeholder="Buscar por nombre, teléfono o conversación..."
                               class="w-full px-4 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <select name="agente" class="px-4 py-2.5 rounded-xl outline-none text-sm bg-white" style="border:1px solid #E2E8F0;color:#1A202C">
                        <option value="">Todos los agentes</option>
                        <option value="sin" <?= $filtroAgente === 'sin' ? 'selected' : '' ?>>Sin asignar</option>
                        <?php foreach ($agentes as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= $filtroAgente === (string)$a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <?php if ($filtroBusqueda || $filtroAgente !== ''): ?>
                        <a href="asignar_leads.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider" style="color:#64748B;background:#F4F8F8">
                                <th class="px-6 py-4 font-medium">Nombre</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Sucursal</th>
                                <th class="px-6 py-4 font-medium">Categoría</th>
                                <th class="px-6 py-4 font-medium">Agente</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($leads)): ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No se encontraron leads</td></tr>
                            <?php endif; ?>
                            <?php foreach ($leads as $r): ?>
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
                            <tr class="hover:bg-slate-50 transition-colors" data-id="<?= htmlspecialchars($r['id']) ?>">
                                <td class="px-6 py-4">
                                    <p class="font-medium" style="color:#1A202C"><?= htmlspecialchars($r['cliente_nombre'] ?: $r['nombre'] ?? 'Sin nombre') ?></p>
                                    <p class="text-xs truncate max-w-[260px]" style="color:#94a3b8"><?= htmlspecialchars(mb_substr($r['conversacion'] ?? '', 0, 80)) ?></p>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm text-slate-600"><?= htmlspecialchars($r['numero']) ?></td>
                                <td class="px-6 py-4 text-sm" style="color:#64748B"><?= htmlspecialchars($r['sucursal'] ?? '-') ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white" style="background:<?= $colorBg ?>">
                                        <?= htmlspecialchars(strtoupper($cat ?: 'SIN CATEGORÍA')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <select class="agente-select w-48 px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none bg-white"
                                                data-id="<?= htmlspecialchars($r['id']) ?>">
                                            <option value="">Sin asignar</option>
                                            <?php foreach ($agentes as $a): ?>
                                                <option value="<?= (int)$a['id'] ?>" <?= (int)($r['agente_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($a['nombre']) ?><?= $a['sucursal'] ? ' — ' . htmlspecialchars($a['sucursal']) : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-check text-emerald-500 text-xs save-ok hidden"></i>
                                    </div>
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

<script>
document.querySelectorAll('.agente-select').forEach(select => {
    select.addEventListener('change', function() {
        const id = this.dataset.id;
        const valor = this.value;
        const check = this.closest('div').querySelector('.save-ok');

        fetch('<?= APP_URL ?>/api/update_redsalud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, campo: 'agente_id', valor })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                check.classList.remove('hidden');
                setTimeout(() => check.classList.add('hidden'), 1500);
            } else {
                alert('Error: ' + (data.error || 'desconocido'));
            }
        })
        .catch(() => alert('Error de conexión'));
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>