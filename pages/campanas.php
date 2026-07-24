<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$filtroBusqueda = $_GET['busqueda'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';

$sql = "SELECT * FROM redsalud WHERE 1=1";
$params = [];

if ($filtroBusqueda) {
    $sql .= " AND (nombre LIKE ? OR numero LIKE ? OR conversacion LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
}
if ($filtroCategoria) {
    $sql .= " AND categoria_cliente = ?";
    $params[] = $filtroCategoria;
}
$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conversaciones = $stmt->fetchAll();

$resumen = $pdo->query("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT numero) as contactos_unicos,
        COUNT(DISTINCT CASE WHEN categoria_cliente != 'Sin Categoría' THEN id END) as categorizados,
        COUNT(DISTINCT categoria_cliente) as total_categorias
    FROM redsalud
")->fetch();

$categorias = $pdo->query("
    SELECT categoria_cliente, COUNT(*) as total
    FROM redsalud
    GROUP BY categoria_cliente
    ORDER BY total DESC
")->fetchAll();

$coloresCategoria = [
    'Sin Categoría' => 'bg-gray-100 text-gray-600',
    'cotizando' => 'badge-observacion',
    'COTIZANDO' => 'badge-observacion',
    'respondio' => 'badge-conforme',
    'RESPONDIO' => 'badge-conforme',
    'realizado' => 'badge-conforme',
    'REALIZADO' => 'badge-conforme',
    'llamado' => 'badge-conforme',
    'LLAMADO' => 'badge-conforme',
];
?>
<?php $titulo = 'Red Salud'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Red Salud</h1>
                    <p class="mt-1" style="color:#64748B">Conversaciones y contactos</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Total Mensajes</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-comments" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $resumen['total'] ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">en la base de datos</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Contactos Únicos</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-users" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $resumen['contactos_unicos'] ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">números distintos</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Categorías</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-tags" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $resumen['total_categorias'] ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">tipos de cliente</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Categorizados</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-check-circle" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $resumen['categorizados'] ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">con categoría asignada</p>
                </div>
            </div>

            <div class="card rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($filtroBusqueda) ?>"
                               placeholder="Buscar por nombre, teléfono o conversación..."
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                    </div>
                    <select name="categoria" class="px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none text-sm bg-white">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['categoria_cliente']) ?>" <?= $filtroCategoria === $cat['categoria_cliente'] ? 'selected' : '' ?>>
                                <?= strtoupper(htmlspecialchars($cat['categoria_cliente'])) ?> (<?= $cat['total'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-search mr-1"></i> Filtrar
                    </button>
                    <a href="<?= APP_URL ?>/api/export_excel.php?<?= http_build_query($_GET) ?>"
                       class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2" style="background:#026168">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </a>
                    <?php if ($filtroBusqueda || $filtroCategoria): ?>
                        <a href="campanas.php" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 transition-colors">
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
                                <th class="px-6 py-4 font-medium">Contacto</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Conversación</th>
                                <th class="px-6 py-4 font-medium">Categoría</th>
                                <th class="px-6 py-4 font-medium text-right">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($conversaciones)): ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No se encontraron registros</td></tr>
                            <?php endif; ?>
                            <?php foreach ($conversaciones as $row): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-500/20 flex items-center justify-center text-sm font-bold text-blue-600">
                                            <?= strtoupper(substr($row['nombre'] ?? $row['numero'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-800"><?= htmlspecialchars($row['nombre'] ?? 'Sin nombre') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-slate-600"><?= htmlspecialchars($row['numero']) ?></span>
                                </td>
                                <td class="px-6 py-4 max-w-md">
                                    <p class="text-slate-700 truncate"><?= htmlspecialchars($row['conversacion']) ?></p>
                                    <?php if ($row['obs']): ?>
                                        <p class="text-xs text-slate-400 mt-0.5 truncate"><?= htmlspecialchars($row['obs']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        <?= $coloresCategoria[$row['categoria_cliente']] ?? 'bg-slate-100 text-slate-600' ?>">
                                        <?= strtoupper(htmlspecialchars($row['categoria_cliente'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm text-slate-600"><?= date('d/m/Y', strtotime($row['fecha_creacion'])) ?></p>
                                    <p class="text-xs text-slate-400"><?= date('H:i', strtotime($row['fecha_creacion'])) ?></p>
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
let ultimoTimestamp = '<?= $conversaciones[0]['fecha_creacion'] ?? date('Y-m-d H:i:s') ?>';
let polling = true;

function cargarNuevos() {
    if (!polling) return;
    fetch('<?= APP_URL ?>/api/latest.php?desde=' + encodeURIComponent(ultimoTimestamp))
        .then(r => r.json())
        .then(resp => {
            if (!resp.success || resp.total === 0) return;
            ultimoTimestamp = resp.ultimo;
            const tbody = document.querySelector('tbody');
            const emptyRow = tbody.querySelector('td[colspan]');
            if (emptyRow) tbody.innerHTML = '';

            resp.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition-colors nuevo-registro';
                tr.style.animation = 'fadeIn 0.5s ease';
                const avatar = (row.nombre || row.numero).substring(0, 2).toUpperCase();
                tr.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-500/20 flex items-center justify-center text-sm font-bold text-blue-600">${avatar}</div>
                            <div><p class="font-medium text-slate-800">${escapeHtml(row.nombre || 'Sin nombre')}</p></div>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="font-mono text-sm text-slate-600">${escapeHtml(row.numero)}</span></td>
                    <td class="px-6 py-4 max-w-md"><p class="text-slate-700 truncate">${escapeHtml(row.conversacion)}</p></td>
                    <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${getColorCategoria(row.categoria_cliente)}">${escapeHtml(row.categoria_cliente).toUpperCase()}</span></td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <p class="text-sm text-slate-600">${formatDate(row.fecha_creacion)}</p>
                        <p class="text-xs text-slate-400">${formatTime(row.fecha_creacion)}</p>
                    </td>
                `;
                tbody.prepend(tr);
            });

            mostrarToast(resp.total);
            actualizarResumen();
        })
        .catch(() => {});
}

function mostrarToast(cantidad) {
    const existente = document.querySelector('.toast-nuevos');
    if (existente) existente.remove();
    const toast = document.createElement('div');
    toast.className = 'toast-nuevos fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg z-50 flex items-center gap-3 fade-in text-white'; toast.style.background = '#008089';
    toast.innerHTML = `<i class="fas fa-bell"></i> <span class="font-medium">${cantidad} nuevo(s) registro(s)</span>`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; setTimeout(() => toast.remove(), 500); }, 4000);
}

function actualizarResumen() {
    fetch('<?= APP_URL ?>/api/checkdata.php')
        .then(r => r.json())
        .then(d => {
            const cards = document.querySelectorAll('.stat-card p.text-2xl');
            if (cards.length >= 4 && d.redsalud !== undefined) {
                cards[0].textContent = d.redsalud;
            }
        })
        .catch(() => {});
}

function getColorCategoria(cat) {
    if (!cat) return 'bg-slate-100 text-slate-600';
    const c = cat.toLowerCase();
    if (c === 'cotizando') return 'bg-red-600 text-white';
    if (c === 'respondio') return 'bg-green-600 text-white';
    if (c === 'realizado') return 'bg-blue-600 text-white';
    return 'bg-slate-100 text-slate-600';
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function parseDate(dt) {
    if (!dt) return null;
    const [f, h] = dt.split(' ');
    const [y, m, d] = f.split('-').map(Number);
    const [hh, mi, ss] = h ? h.split(':').map(Number) : [0, 0, 0];
    return new Date(y, m - 1, d, hh, mi, ss);
}

function formatDate(dt) {
    if (!dt) return '-';
    const d = parseDate(dt);
    return d.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTime(dt) {
    if (!dt) return '';
    const d = parseDate(dt);
    return d.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
}

document.addEventListener('visibilitychange', () => { polling = !document.hidden; });
setInterval(cargarNuevos, 5000);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
