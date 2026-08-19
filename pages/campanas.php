<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

require_once __DIR__ . '/../includes/sucursal_filter.php';

$filtroBusqueda = $_GET['busqueda'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';

$sql = "SELECT r.*, c.nombre as cliente_nombre, c.sucursal
        FROM redsalud r
        $joinSuc
        WHERE 1=1 $whereSuc";
$params = [];

if ($filtroBusqueda) {
    $sql .= " AND (r.nombre LIKE ? OR r.numero LIKE ? OR r.conversacion LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
}
if ($filtroCategoria) {
    $sql .= " AND r.categoria_cliente = ?";
    $params[] = $filtroCategoria;
}
$sql .= " ORDER BY COALESCE(NULLIF(c.nombre, ''), NULLIF(r.nombre, ''), r.numero) ASC, r.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conversaciones = $stmt->fetchAll();

$grupos = [];
foreach ($conversaciones as $row) {
    $nombre = $row['cliente_nombre'] ?: $row['nombre'] ?? 'Sin nombre';
    $grupos[$nombre][] = $row;
}

$totalMensajes = $pdo->query("
    SELECT COUNT(*) FROM clientesredsalud
    WHERE 1=1 " . ($sucursalSeleccionada ? "AND sucursal = " . $pdo->quote($sucursalSeleccionada) : "")
)->fetchColumn();

$resumen = $pdo->query("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT r.numero) as contactos_unicos,
        COUNT(DISTINCT CASE WHEN r.categoria_cliente != 'Sin Categoría' THEN r.id END) as categorizados,
        COUNT(DISTINCT r.categoria_cliente) as total_categorias
    FROM redsalud r
    $joinSuc
    WHERE 1=1 $whereSuc
")->fetch();

$categorias = $pdo->query("
    SELECT r.categoria_cliente, COUNT(*) as total
    FROM redsalud r
    $joinSuc
    WHERE 1=1 $whereSuc
    GROUP BY r.categoria_cliente
    ORDER BY total DESC
")->fetchAll();

$coloresCategoria = [
    'Sin Categoría' => 'bg-gray-100 text-gray-600',
    'cotizando' => 'bg-red-600 text-white',
    'COTIZANDO' => 'bg-red-600 text-white',
    'respondio' => 'bg-green-600 text-white',
    'RESPONDIO' => 'bg-green-600 text-white',
    'realizado' => 'bg-blue-600 text-white',
    'REALIZADO' => 'bg-blue-600 text-white',
    'llamado' => 'bg-purple-600 text-white',
    'LLAMADO' => 'bg-purple-600 text-white',
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Total Mensajes</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-comments" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= $totalMensajes ?></p>
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
                <?php if (empty($conversaciones)): ?>
                <div class="px-6 py-12 text-center" style="color:#64748B">No se encontraron registros</div>
                <?php endif; ?>
                <div class="divide-y" style="border-color:#E2E8F0">
                    <?php foreach ($grupos as $nombre => $rows):
                        $total = count($rows);
                        $ultimo = $rows[0];
                        $cat = strtolower($ultimo['categoria_cliente'] ?? 'sin categoría');
                        $colorBg = match($cat) {
                            'cotizando' => '#dc2626',
                            'respondio' => '#16a34a',
                            'realizado' => '#2563eb',
                            'llamado' => '#9333ea',
                            default => '#64748b'
                        };
                    ?>
                    <div class="grupo-contacto">
                        <div class="grupo-header flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleGrupo(this)">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <i class="fas fa-chevron-right text-xs transition-transform" style="color:#64748B"></i>
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0" style="background:<?= $colorBg ?>">
                                    <?= strtoupper(substr($nombre, 0, 2)) ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm" style="color:#1A202C"><?= htmlspecialchars($nombre) ?></p>
                                    <?php if ($rows[0]['cliente_nombre'] && $rows[0]['sucursal']): ?>
                                        <p class="text-xs" style="color:#64748B"><?= htmlspecialchars($rows[0]['sucursal']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-xs" style="color:#64748B">
                                <span><i class="far fa-comment mr-1"></i><?= $total ?> msgs</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white" style="background:<?= $colorBg ?>">
                                    <?= strtoupper(htmlspecialchars($ultimo['categoria_cliente'] ?? 'SIN CATEGORÍA')) ?>
                                </span>
                            </div>
                        </div>
                        <div class="grupo-body hidden border-t" style="border-color:#E2E8F0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs uppercase tracking-wider" style="color:#94a3b8;background:#FAFAFA">
                                            <th class="px-6 py-3 font-medium pl-14">Teléfono</th>
                                            <th class="px-6 py-3 font-medium">Conversación</th>
                                            <th class="px-6 py-3 font-medium">Categoría</th>
                                            <th class="px-6 py-3 font-medium text-right">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#F1F5F9">
                                        <?php foreach ($rows as $row): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3 pl-14 font-mono text-sm" style="color:#64748B"><?= htmlspecialchars($row['numero']) ?></td>
                                            <td class="px-6 py-3 max-w-md">
                                                <p class="truncate" style="color:#1A202C"><?= htmlspecialchars($row['conversacion'] ?? '') ?></p>
                                                <?php if (!empty($row['obs'])): ?>
                                                    <p class="text-xs mt-0.5 truncate" style="color:#94a3b8"><?= htmlspecialchars($row['obs']) ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    <?= $coloresCategoria[$row['categoria_cliente']] ?? 'bg-slate-100 text-slate-600' ?>">
                                                    <?= strtoupper(htmlspecialchars($row['categoria_cliente'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-right whitespace-nowrap text-xs" style="color:#64748B">
                                                <?= date('d/m/Y', strtotime($row['fecha_creacion'])) ?> <?= date('H:i', strtotime($row['fecha_creacion'])) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
let polling = true;

function cargarNuevos() {
    if (!polling) return;
    fetch('<?= APP_URL ?>/api/checkdata.php')
        .then(r => r.json())
        .then(d => {
            if (d.clientesredsalud !== undefined) {
                const cards = document.querySelectorAll('.stat-card p.text-2xl');
                if (cards.length >= 4) cards[0].textContent = d.clientesredsalud;
            }
        })
        .catch(() => {});
}

document.addEventListener('visibilitychange', () => { polling = !document.hidden; });
setInterval(cargarNuevos, 10000);

function toggleGrupo(el) {
    const icon = el.querySelector('.fa-chevron-right');
    const body = el.nextElementSibling;
    body.classList.toggle('hidden');
    icon.style.transform = body.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(90deg)';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
