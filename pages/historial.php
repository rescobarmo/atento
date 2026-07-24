<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$filtroBusqueda = $_GET['busqueda'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$filtroDesde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$filtroHasta = $_GET['hasta'] ?? date('Y-m-d');

$sql = "SELECT * FROM redsalud WHERE 1=1";
$params = [];

if ($filtroBusqueda) {
    $sql .= " AND (nombre LIKE ? OR numero LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
}
if ($filtroEstado) {
    $sql .= " AND LOWER(categoria_cliente) = ?";
    $params[] = strtolower($filtroEstado);
}
if ($filtroDesde) {
    $sql .= " AND DATE(fecha_creacion) >= ?";
    $params[] = $filtroDesde;
}
if ($filtroHasta) {
    $sql .= " AND DATE(fecha_creacion) <= ?";
    $params[] = $filtroHasta;
}
$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$estados = $pdo->query("
    SELECT DISTINCT LOWER(categoria_cliente) as cat FROM redsalud
    WHERE categoria_cliente IS NOT NULL AND categoria_cliente != ''
    ORDER BY cat
")->fetchAll();

function badgeClassReport($cat) {
    $c = strtolower($cat ?? '');
    if (in_array($c, ['realizado', 'respondio', 'llamado'])) return 'badge-conforme';
    if ($c === 'cotizando') return 'badge-observacion';
    if ($c === 'noconforme') return 'badge-noconforme';
    return 'bg-gray-100 text-gray-600';
}
?>
<?php $titulo = 'Historial'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Historial y Reportes</h1>
                    <p class="mt-1" style="color:#64748B">Registro completo de evaluaciones realizadas</p>
                </div>
                <a href="<?= APP_URL ?>/api/export_excel.php?<?= http_build_query($_GET) ?>"
                   class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> Descargar Reporte
                </a>
            </div>

            <div class="card rounded-2xl p-5 mb-6 fade-in">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($filtroBusqueda) ?>"
                               placeholder="Buscar por nombre o teléfono..."
                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <select name="estado" class="px-4 py-2.5 rounded-xl text-sm outline-none bg-white" style="border:1px solid #E2E8F0;color:#1A202C">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= htmlspecialchars($e['cat']) ?>" <?= $filtroEstado === $e['cat'] ? 'selected' : '' ?>>
                                <?= strtoupper(htmlspecialchars($e['cat'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="desde" value="<?= htmlspecialchars($filtroDesde) ?>"
                           class="px-4 py-2.5 rounded-xl text-sm outline-none bg-white" style="border:1px solid #E2E8F0;color:#1A202C">
                    <input type="date" name="hasta" value="<?= htmlspecialchars($filtroHasta) ?>"
                           class="px-4 py-2.5 rounded-xl text-sm outline-none bg-white" style="border:1px solid #E2E8F0;color:#1A202C">
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <?php if ($filtroBusqueda || $filtroEstado || $filtroDesde !== date('Y-m-d', strtotime('-30 days')) || $filtroHasta !== date('Y-m-d')): ?>
                        <a href="historial.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid #E2E8F0;color:#64748B">
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
                                <th class="px-6 py-4 font-medium">Contacto</th>
                                <th class="px-6 py-4 font-medium">Teléfono</th>
                                <th class="px-6 py-4 font-medium">Evaluación</th>
                                <th class="px-6 py-4 font-medium">Observaciones</th>
                                <th class="px-6 py-4 font-medium">Fecha</th>
                                <th class="px-6 py-4 font-medium text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php if (empty($registros)): ?>
                                <tr><td colspan="6" class="px-6 py-12 text-center" style="color:#64748B">No se encontraron registros</td></tr>
                            <?php endif; ?>
                            <?php foreach ($registros as $r): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-medium" style="color:#1A202C"><?= htmlspecialchars($r['nombre'] ?? 'Sin nombre') ?></p>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm" style="color:#64748B"><?= htmlspecialchars($r['numero']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= badgeClassReport($r['categoria_cliente']) ?>">
                                        <?php
                                        $cat = strtolower($r['categoria_cliente'] ?? '');
                                        $label = match($cat) {
                                            'respondio' => 'CONFORME',
                                            'realizado' => 'CONFORME',
                                            'llamado' => 'CONTACTADO',
                                            'cotizando' => 'OBSERVACIÓN',
                                            default => strtoupper($r['categoria_cliente'] ?? 'SIN EVALUAR')
                                        };
                                        echo $label;
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-sm truncate" style="color:#64748B"><?= htmlspecialchars($r['obs'] ?? '-') ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap" style="color:#64748B"><?= date('d/m/Y H:i', strtotime($r['fecha_actualizacion'] ?: $r['fecha_creacion'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="location.href='<?= APP_URL ?>/pages/evaluaciones.php'" class="text-sm font-medium" style="color:#008089">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 text-xs" style="color:#64748B">
                <i class="fas fa-database mr-1"></i> Total: <?= count($registros) ?> registros
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
