<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();
$usuario = usuarioActual();

require_once __DIR__ . '/../includes/sucursal_filter.php';

$esAgente = strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas';

$sucursalesAgente = [];
if ($esAgente) {
    $stmtSuc = $pdo->prepare("SELECT sucursal FROM agente_sucursales WHERE agente_id = ? ORDER BY sucursal");
    $stmtSuc->execute([(int)$usuario['id']]);
    $sucursalesAgente = $stmtSuc->fetchAll(PDO::FETCH_COLUMN);

    $whereSuc = '';
    $whereSuc2 = '';
    if (!empty($sucursalesAgente)) {
        $sucursalSeleccionada = implode(', ', $sucursalesAgente);
    }
}

$filtroBusqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT r.*, c.nombre as cliente_nombre, c.sucursal FROM redsalud r $joinSuc";
$where = ["(LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')"];
$params = [];

if ($esAgente) {
    if (empty($sucursalesAgente)) {
        $where[] = "r.agente_id = ?";
        $params[] = (int)$usuario['id'];
    } else {
        $in = implode(',', array_fill(0, count($sucursalesAgente), '?'));
        $where[] = "(c.sucursal IN ($in) OR r.agente_id = ?)";
        foreach ($sucursalesAgente as $s) $params[] = $s;
        $params[] = (int)$usuario['id'];
    }
}

if ($filtroBusqueda) {
    $where[] = "(r.nombre LIKE ? OR r.numero LIKE ? OR r.conversacion LIKE ?)";
    $p = "%$filtroBusqueda%";
    $params[] = $p; $params[] = $p; $params[] = $p;
}
$where[] = "1=1 " . $whereSuc;
$sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY r.fecha_actualizacion DESC, r.fecha_creacion DESC";

if ($esAgente && (int)$usuario['limite_leads'] > 0) {
    $sql .= " LIMIT " . (int)$usuario['limite_leads'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$resumen = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(r.categoria_cliente) = 'cotizando' THEN 1 ELSE 0 END) as cotizando,
        SUM(CASE WHEN LOWER(r.categoria_cliente) = 'llamado' THEN 1 ELSE 0 END) as contactados,
        COALESCE(SUM(r.presupuesto), 0) as presupuesto
    FROM redsalud r
    $joinSuc
    WHERE (LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')
    AND 1=1 $whereSuc
");
$resumen->execute();
$resumen = $resumen->fetch() ?: ['total' => 0, 'cotizando' => 0, 'contactados' => 0, 'presupuesto' => 0];
if ($esAgente) {
    if (empty($sucursalesAgente)) {
        $whereResumen = "r.agente_id = " . (int)$usuario['id'];
        $paramResumen = [];
    } else {
        $in = implode(',', array_fill(0, count($sucursalesAgente), '?'));
        $whereResumen = "(c.sucursal IN ($in) OR r.agente_id = ?)";
        $paramResumen = array_merge($sucursalesAgente, [(int)$usuario['id']]);
    }
    $stmtR = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(r.categoria_cliente) = 'cotizando' THEN 1 ELSE 0 END) as cotizando,
            SUM(CASE WHEN LOWER(r.categoria_cliente) = 'llamado' THEN 1 ELSE 0 END) as contactados,
            COALESCE(SUM(r.presupuesto), 0) as presupuesto
        FROM redsalud r
        $joinSuc
        WHERE (LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')
        AND $whereResumen
    ");
    $stmtR->execute($paramResumen);
    $resumen = $stmtR->fetch() ?: ['total' => 0, 'cotizando' => 0, 'contactados' => 0, 'presupuesto' => 0];
}
?>
<?php $titulo = 'Panel Agente de Ventas'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Panel Agente de Ventas</h1>
                    <p class="mt-1" style="color:#64748B">Visión de sucursal y seguimiento de leads</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl card">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-store text-lg" style="color:#008089"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold" style="color:#64748B">Sucursal</p>
                            <p class="font-bold" style="color:#1A202C"><?= $esAgente ? (empty($sucursalesAgente) ? 'Sin asignar' : htmlspecialchars($sucursalSeleccionada)) : htmlspecialchars($sucursalSeleccionada ?: 'Todas') ?></p>
                        </div>
                    </div>
                    <?php if ($esAgente && (int)$usuario['limite_leads'] > 0): ?>
                    <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl card">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:rgba(245,158,11,0.12)">
                            <i class="fas fa-gauge-high text-lg" style="color:#F59E0B"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold" style="color:#64748B">Límite diario</p>
                            <p class="font-bold" style="color:#1A202C"><?= (int)$usuario['limite_leads'] ?> leads</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Total Leads</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-users text-lg" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format((int)$resumen['total']) ?></p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Cotizando</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(220,38,38,0.12)">
                            <i class="fas fa-file-invoice-dollar text-lg" style="color:#dc2626"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format((int)$resumen['cotizando']) ?></p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Contactados</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(147,51,234,0.12)">
                            <i class="fas fa-phone-volume text-lg" style="color:#9333ea"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format((int)$resumen['contactados']) ?></p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Presupuesto Total</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,0.12)">
                            <i class="fas fa-coins text-lg" style="color:#F59E0B"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C">$<?= number_format((int)$resumen['presupuesto'], 0, ',', '.') ?></p>
                </div>
            </div>

            <div class="card rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($filtroBusqueda) ?>"
                               placeholder="Buscar por nombre, teléfono o conversación..."
                               class="w-full px-4 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <?php if ($filtroBusqueda): ?>
                        <a href="agentes.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
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
                                <th class="px-6 py-4 font-medium">Conversación</th>
                                <th class="px-6 py-4 font-medium">Categoría</th>
                                <th class="px-6 py-4 font-medium">Horario</th>
                                <th class="px-6 py-4 font-medium">Presupuesto</th>
                                <th class="px-6 py-4 font-medium text-center">Contactado</th>
                                <th class="px-6 py-4 font-medium">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($leads)): ?>
                                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">No se encontraron leads para esta sucursal</td></tr>
                            <?php endif; ?>
                            <?php foreach ($leads as $r): ?>
                            <?php
                                $cat = strtolower($r['categoria_cliente'] ?? '');
                                $esLlamado = $cat === 'llamado';
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
                                    <?php if (($esAgente || empty($sucursalSeleccionada)) && $r['sucursal']): ?>
                                        <p class="text-xs" style="color:#64748B"><?= htmlspecialchars($r['sucursal']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm text-slate-600"><?= htmlspecialchars($r['numero']) ?></td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-slate-600 truncate"><?= htmlspecialchars(mb_substr($r['conversacion'] ?? '', 0, 100)) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white" style="background:<?= $colorBg ?>">
                                        <?= htmlspecialchars(strtoupper($cat ?: 'SIN CATEGORÍA')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm" style="color:#64748B">
                                    <?= htmlspecialchars($r['horario'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" inputmode="numeric"
                                           class="w-28 px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none presupuesto-input text-right"
                                           value="<?= $r['presupuesto'] ? number_format((int)$r['presupuesto'], 0, ',', '.') : '' ?>"
                                           data-id="<?= htmlspecialchars($r['id']) ?>"
                                           placeholder="0">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                           class="w-5 h-5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 cursor-pointer contact-checkbox"
                                           <?= $esLlamado ? 'checked' : '' ?>
                                           data-id="<?= htmlspecialchars($r['id']) ?>">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <input type="text"
                                               class="w-40 px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none obs-input"
                                               value="<?= htmlspecialchars($r['obs'] ?? '') ?>"
                                               maxlength="200"
                                               data-id="<?= htmlspecialchars($r['id']) ?>"
                                               placeholder="Escribir nota...">
                                        <span class="text-xs text-slate-400 obs-count"><?= mb_strlen($r['obs'] ?? '') ?>/200</span>
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
document.querySelectorAll('.contact-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const id = this.dataset.id;
        const checked = this.checked;
        const tr = this.closest('tr');
        const obsInput = tr.querySelector('.obs-input');

        if (!checked) {
            obsInput.value = '';
            const counter = obsInput.parentElement.querySelector('.obs-count');
            if (counter) counter.textContent = '0/200';

            fetch('<?= APP_URL ?>/api/update_redsalud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id, campo: 'obs', valor: '' })
            });
        }

        const valor = checked ? 'LLAMADO' : 'COTIZANDO';
        fetch('<?= APP_URL ?>/api/update_redsalud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, campo: 'categoria_cliente', valor })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + (data.error || 'desconocido'));
        })
        .catch(() => alert('Error de conexión'));
    });
});

document.querySelectorAll('.presupuesto-input').forEach(input => {
    input.addEventListener('keyup', function() {
        const raw = this.value.replace(/\./g, '');
        if (raw) {
            const num = parseInt(raw, 10);
            if (!isNaN(num)) this.value = num.toLocaleString('es-CL');
        }
    });

    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });

    let saveTimer;
    input.addEventListener('input', function() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const id = this.dataset.id;
            const valor = this.value.replace(/\./g, '');

            fetch('<?= APP_URL ?>/api/update_redsalud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id, campo: 'presupuesto', valor })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) alert('Error: ' + (data.error || 'desconocido'));
            })
            .catch(() => {});
        }, 800);
    });
});

document.querySelectorAll('.obs-input').forEach(input => {
    const counter = input.parentElement.querySelector('.obs-count');

    input.addEventListener('input', function() {
        const len = this.value.length;
        if (counter) counter.textContent = len + '/200';
    });

    let saveTimer;
    input.addEventListener('input', function() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const id = this.dataset.id;
            const valor = this.value;

            fetch('<?= APP_URL ?>/api/update_redsalud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id, campo: 'obs', valor })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) alert('Error: ' + (data.error || 'desconocido'));
            })
            .catch(() => {});
        }, 800);
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>