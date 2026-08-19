<?php
require_once __DIR__ . '/includes/auth.php';
requerirLogin();

$pdo = getDB();
$usuario = usuarioActual();

require_once __DIR__ . '/includes/sucursal_filter.php';

$redsalud = $pdo->query("
    SELECT
        COUNT(DISTINCT r.numero) as total_msgs,
        COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) IN ('cotizando','respondio','realizado','llamado') THEN r.id END) as evaluados,
        COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) = 'cotizando' THEN r.id END) as cotizando,
        COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) = 'respondio' THEN r.id END) as respondio,
        COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) = 'realizado' THEN r.id END) as realizado,
        COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) = 'llamado' THEN r.id END) as llamado
    FROM redsalud r
    $joinSuc
    WHERE 1=1 $whereSuc
")->fetch();

$totalClientes = $pdo->query("
    SELECT COUNT(*) FROM clientesredsalud
    WHERE 1=1 " . ($sucursalSeleccionada ? "AND sucursal = " . $pdo->quote($sucursalSeleccionada) : "")
)->fetchColumn();

$pendientes = $redsalud['total_msgs'] - $redsalud['evaluados'];

$contactados = $pdo->query("
    SELECT COUNT(DISTINCT r.numero) FROM redsalud r
    $joinSuc
    WHERE 1=1 $whereSuc
")->fetchColumn();

$interesados = $pdo->query("
    SELECT count(DISTINCT(r.numero)) as cantidad FROM redsalud r
    $joinSuc
    WHERE LOWER(r.categoria_cliente) = 'respondio' $whereSuc
")->fetchColumn();

$sinInteres = $pdo->query("
    SELECT COUNT(DISTINCT r.numero) FROM redsalud r
    $joinSuc
    WHERE conversacion = 'no' AND LOWER(r.categoria_cliente) = 'cancelada' $whereSuc
")->fetchColumn();

$nulos = $pdo->query("
    SELECT COUNT(1) FROM redsalud r
    $joinSuc
    WHERE conversacion IS NULL $whereSuc
")->fetchColumn();

$cumplimiento = $pendientes > 0
    ? round(($nulos / $pendientes) * 100, 1)
    : 0;

$msgsPorDia = $pdo->query("
    SELECT DATE_FORMAT(primeros_registros.fecha, '%d/%m') as dia,
           COUNT(primeros_registros.numero) as total
    FROM (
        SELECT r.numero, MIN(DATE(r.fecha_creacion)) as fecha
        FROM redsalud r
        $joinSuc
        WHERE 1=1 $whereSuc
        GROUP BY r.numero
    ) as primeros_registros
    WHERE primeros_registros.fecha >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY primeros_registros.fecha
    ORDER BY primeros_registros.fecha ASC
")->fetchAll();

$categorias = $pdo->query("
    SELECT 'CON HORARIO DE LLAMADA' as cat, COUNT(DISTINCT CASE WHEN LOWER(r.categoria_cliente) = 'cotizando' THEN r.id END) as total
    FROM redsalud r $joinSuc
    WHERE 1=1 $whereSuc
    UNION ALL
    SELECT 'INTERESADOS', COUNT(DISTINCT r.numero)
    FROM redsalud r $joinSuc
    WHERE 1=1 $whereSuc AND LOWER(r.categoria_cliente) = 'respondio'
    UNION ALL
    SELECT 'REALIZADO', COUNT(DISTINCT r.numero)
    FROM redsalud r $joinSuc
    WHERE 1=1 $whereSuc AND LOWER(r.categoria_cliente) = 'realizado'
    UNION ALL
    SELECT 'CONTACTADO', COUNT(DISTINCT r.numero)
    FROM redsalud r $joinSuc
    WHERE 1=1 $whereSuc AND LOWER(r.categoria_cliente) = 'llamado'
    UNION ALL
    SELECT 'PACIENTE SIN INTERES', COUNT(DISTINCT r.numero)
    FROM redsalud r $joinSuc
    WHERE 1=1 $whereSuc AND conversacion = 'no' AND LOWER(r.categoria_cliente) = 'cancelada'
    ORDER BY total DESC
")->fetchAll();

$ultimosContactos = $pdo->query("
    SELECT r.numero, r.nombre, r.fecha_creacion as ultimo, r.categoria_cliente
    FROM redsalud r
    $joinSuc
    INNER JOIN (
        SELECT r2.numero, MAX(r2.fecha_creacion) as max_fecha
        FROM redsalud r2
        $joinSuc2
        WHERE 1=1 $whereSuc2
        GROUP BY r2.numero
    ) ult ON r.numero = ult.numero AND r.fecha_creacion = ult.max_fecha
    WHERE 1=1 $whereSuc
    ORDER BY ultimo DESC LIMIT 10
")->fetchAll();

$tieneDatos = $redsalud['total_msgs'] > 0;
$hoy = new DateTime();
$fechaInicio = (new DateTime())->modify('-30 days')->format('Y-m-d');
$fechaFin = (new DateTime())->format('Y-m-d');

function badgeClass($cat) {
    $c = strtolower($cat ?? '');
    if (in_array($c, ['realizado', 'respondio'])) return 'badge-conforme';
    if (in_array($c, ['cotizando'])) return 'badge-observacion';
    if (in_array($c, ['llamado'])) return 'badge-conforme';
    return 'bg-gray-100 text-gray-600';
}
?>
<?php $titulo = 'Dashboard'; include __DIR__ . '/includes/header.php'; ?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Panel de Control</h1>
                    <p class="mt-1" style="color:#64748B">Bienvenido de nuevo, <?= htmlspecialchars(explode(' ', $usuario['nombre'])[0]) ?></p>
                </div>
                <div class="flex items-center gap-3 text-sm" style="color:#64748B">
                    <i class="fas fa-calendar"></i>
                    <span><?= (new DateTime())->format('d/m/Y') ?></span>
                </div>
            </div>

            <?php if (!$tieneDatos): ?>
            <div class="flex flex-col items-center justify-center h-96" style="color:#64748B">
                <i class="fas fa-clipboard-list text-6xl mb-4 opacity-30"></i>
                <p class="text-xl font-medium">No hay datos registrados</p>
                <p class="text-sm">Los indicadores aparecerán cuando existan evaluaciones</p>
            </div>
            <?php else: ?>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-5 mb-8 fade-in">
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Cumplimiento General</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-percent text-lg" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format($totalClientes) ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">registros enviados</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Contactado</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-check-double text-lg" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format($contactados) ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">de <?= number_format($totalClientes) ?> registros</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Interesados</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(0,128,137,0.12)">
                            <i class="fas fa-thumbs-up text-lg" style="color:#008089"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format($interesados) ?></p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Con Horario de Llamada</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,0.12)">
                            <i class="fas fa-clock text-lg" style="color:#F59E0B"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format($redsalud['cotizando']) ?></p>
                    <p class="text-xs mt-1" style="color:#64748B">en proceso de cotización</p>
                </div>
                <div class="stat-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color:#64748B">Paciente Sin Interes</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(229,62,62,0.12)">
                            <i class="fas fa-exclamation-triangle text-lg" style="color:#E53E3E"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold" style="color:#1A202C"><?= number_format($sinInteres) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 fade-in">
                <div class="lg:col-span-2 card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-semibold" style="color:#1A202C">Actividad por Día</h3>
                        <span class="text-xs" style="color:#64748B">Últimos 14 días</span>
                    </div>
                    <?php if (!empty($msgsPorDia)): ?>
                    <div style="height: 280px;"><canvas id="chartMsgs"></canvas></div>
                    <?php else: ?>
                    <div class="h-64 flex items-center justify-center" style="color:#64748B">
                        <p>Sin datos en los últimos 14 días</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-semibold" style="color:#1A202C">Estado Evaluaciones</h3>
                        <span class="text-xs" style="color:#64748B">distribución</span>
                    </div>
                    <?php if (!empty($categorias)): ?>
                    <div style="height: 280px;"><canvas id="chartCategorias"></canvas></div>
                    <?php else: ?>
                    <div class="h-64 flex items-center justify-center" style="color:#64748B">
                        <p>Sin datos</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card rounded-2xl p-6 fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold" style="color:#1A202C">Últimos Contactos</h3>
                    <a href="<?= APP_URL ?>/pages/campanas.php" class="text-xs font-medium" style="color:#008089">Ver todos →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider" style="color:#64748B">
                                <th class="pb-3 font-medium">Contacto</th>
                                <th class="pb-3 font-medium">Teléfono</th>
                                <th class="pb-3 font-medium">Estado</th>
                                <th class="pb-3 font-medium">Último mensaje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E2E8F0">
                            <?php foreach ($ultimosContactos as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 font-medium" style="color:#1A202C"><?= htmlspecialchars($c['nombre'] ?? 'Sin nombre') ?></td>
                                <td class="py-3 font-mono" style="color:#64748B"><?= htmlspecialchars($c['numero']) ?></td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= badgeClass($c['categoria_cliente']) ?>">
                                        <?= strtoupper(htmlspecialchars($c['categoria_cliente'] ?? 'SIN EVALUAR')) ?>
                                    </span>
                                </td>
                                <td class="py-3" style="color:#64748B"><?= date('d/m/Y H:i', strtotime($c['ultimo'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<?php if ($tieneDatos): ?>
<script>
<?php if (!empty($msgsPorDia)): ?>
new Chart(document.getElementById('chartMsgs'), {
    type: 'bar',
    data: {
        labels: [<?php foreach ($msgsPorDia as $m): ?>'<?= $m['dia'] ?>',<?php endforeach; ?>],
        datasets: [{
            label: 'Registros',
            data: [<?php foreach ($msgsPorDia as $m): ?><?= $m['total'] ?>,<?php endforeach; ?>],
            backgroundColor: 'rgba(0,128,137,0.7)',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false }, ticks: { color: '#64748B' } }
        },
        animation: { duration: 600, easing: 'easeOutQuart' }
    }
});
<?php endif; ?>

<?php if (!empty($categorias)): ?>
new Chart(document.getElementById('chartCategorias'), {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($categorias as $c): ?>'<?= $c['cat'] ?>',<?php endforeach; ?>],
        datasets: [{
            data: [<?php foreach ($categorias as $c): ?><?= $c['total'] ?>,<?php endforeach; ?>],
            backgroundColor: ['#E53E3E', '#C3E298', '#008089', '#F59E0B', '#94a3b8'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12, color: '#64748B' } }
        },
        cutout: '65%'
    }
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
