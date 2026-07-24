<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$filtroBusqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT * FROM redsalud";
$where = ["LOWER(categoria_cliente) = 'cotizando' OR LOWER(categoria_cliente) = 'llamado'"];
$params = [];

if ($filtroBusqueda) {
    $where[] = "(nombre LIKE ? OR numero LIKE ? OR conversacion LIKE ?)";
    $p = "%$filtroBusqueda%";
    $params[] = $p; $params[] = $p; $params[] = $p;
}
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY fecha_actualizacion DESC, fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conversaciones = $stmt->fetchAll();
?>
<?php $titulo = 'Contactos Red Salud'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Contactos Cotizando</h1>
                    <p class="mt-1" style="color:#64748B">Gestiona contactos que están cotizando y registra llamadas</p>
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
                        <a href="leads.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
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
                                <th class="px-6 py-4 font-medium text-center">Contactado</th>
                                <th class="px-6 py-4 font-medium">Notas</th>
                                <th class="px-6 py-4 font-medium">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($conversaciones)): ?>
                                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">No se encontraron registros</td></tr>
                            <?php endif; ?>
                            <?php foreach ($conversaciones as $r): ?>
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
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($r['nombre'] ?? 'Sin nombre') ?></p>
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
                                <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($r['fecha_actualizacion'] ?: $r['fecha_creacion'])) ?></td>
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
