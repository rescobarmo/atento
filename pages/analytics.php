<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$analytics = $pdo->query("
    SELECT
        DATE_FORMAT(mc.fecha, '%Y-%m') as mes,
        SUM(mc.impresiones) as impresiones,
        SUM(mc.clicks) as clicks,
        SUM(mc.conversiones) as conversiones,
        SUM(mc.ingresos) as ingresos,
        SUM(mc.gasto) as gasto,
        CASE WHEN SUM(mc.gasto) > 0
            THEN ROUND((SUM(mc.ingresos) - SUM(mc.gasto)) / SUM(mc.gasto) * 100, 1)
            ELSE 0 END as roi,
        CASE WHEN SUM(mc.impresiones) > 0
            THEN ROUND(SUM(mc.clicks) / SUM(mc.impresiones) * 100, 2)
            ELSE 0 END as ctr,
        CASE WHEN SUM(mc.clicks) > 0
            THEN ROUND(SUM(mc.conversiones) / SUM(mc.clicks) * 100, 2)
            ELSE 0 END as tasa_conversion
    FROM metricas_campana mc
    GROUP BY mes
    ORDER BY mes ASC
")->fetchAll();

$visitasPorCanal = $pdo->query("
    SELECT ch.nombre, ch.color, SUM(vs.visitantes) as visitantes, SUM(vs.visitas) as visitas, SUM(vs.paginas_vistas) as paginas
    FROM visitas_sitio vs
    JOIN canales ch ON vs.canal_id = ch.id
    GROUP BY vs.canal_id
    ORDER BY visitantes DESC
")->fetchAll();
?>
<?php $titulo = 'Analíticas'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold" style="color:#FFFFFF">Analíticas</h1>
                <p class="mt-1" style="color:rgba(255,255,255,0.7)">Métricas detalladas de rendimiento</p>
            </div>

            <div class="bg-white/5 rounded-2xl border border-white/15 overflow-hidden mb-8">
                <div class="p-6 border-b border-white/10">
                    <h3 class="font-semibold" style="color:#FFFFFF">Rendimiento Mensual</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-white/5 text-left text-xs uppercase tracking-wider text-white/60">
                                <th class="px-6 py-4 font-medium">Mes</th>
                                <th class="px-6 py-4 font-medium text-right">Impresiones</th>
                                <th class="px-6 py-4 font-medium text-right">Clicks</th>
                                <th class="px-6 py-4 font-medium text-right">CTR</th>
                                <th class="px-6 py-4 font-medium text-right">Conversiones</th>
                                <th class="px-6 py-4 font-medium text-right">Tasa Conv.</th>
                                <th class="px-6 py-4 font-medium text-right">Ingresos</th>
                                <th class="px-6 py-4 font-medium text-right">Gasto</th>
                                <th class="px-6 py-4 font-medium text-right">ROI</th>
                            </tr>
                        </thead>
<tbody class="divide-y divide-white/10">
                            <?php foreach ($analytics as $a): ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-medium" style="color:#FFFFFF"><?= $a['mes'] ?></td>
                                <td class="px-6 py-4 text-right text-white/70"><?= number_format($a['impresiones']) ?></td>
                                <td class="px-6 py-4 text-right text-white/70"><?= number_format($a['clicks']) ?></td>
                                <td class="px-6 py-4 text-right font-medium <?= $a['ctr'] >= 2 ? 'text-green-400' : 'text-yellow-400' ?>"><?= $a['ctr'] ?>%</td>
                                <td class="px-6 py-4 text-right text-white/70"><?= $a['conversiones'] ?></td>
                                <td class="px-6 py-4 text-right font-medium <?= $a['tasa_conversion'] >= 3 ? 'text-green-400' : 'text-yellow-400' ?>"><?= $a['tasa_conversion'] ?>%</td>
                                <td class="px-6 py-4 text-right font-semibold text-green-400">$<?= number_format($a['ingresos'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 text-right text-red-400">$<?= number_format($a['gasto'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 text-right font-bold <?= $a['roi'] >= 0 ? 'text-green-400' : 'text-red-400' ?>"><?= $a['roi'] ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($visitasPorCanal)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white/5 rounded-2xl p-6 border border-white/15">
                    <h3 class="font-semibold mb-6" style="color:#FFFFFF">Tráfico por Canal</h3>
                    <canvas id="chartTraficoCanal" height="300"></canvas>
                </div>
                <div class="bg-white/5 rounded-2xl p-6 border border-white/15">
                    <h3 class="font-semibold mb-6" style="color:#FFFFFF">Resumen por Canal</h3>
                    <div class="space-y-4">
                        <?php foreach ($visitasPorCanal as $vc): ?>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full" style="background:<?= htmlspecialchars($vc['color']) ?>"></span>
                                <span class="font-medium text-sm" style="color:#FFFFFF"><?= htmlspecialchars($vc['nombre']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold" style="color:#FFFFFF"><?= number_format($vc['visitantes']) ?></span>
                                <span class="text-xs text-white/50 ml-2">visitantes</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if (!empty($visitasPorCanal)): ?>
<script>
new Chart(document.getElementById('chartTraficoCanal'), {
    type: 'bar',
    data: {
        labels: [<?php foreach ($visitasPorCanal as $v): ?>'<?= $v['nombre'] ?>',<?php endforeach; ?>],
        datasets: [
            {
                label: 'Visitantes',
                data: [<?php foreach ($visitasPorCanal as $v): ?><?= $v['visitantes'] ?>,<?php endforeach; ?>],
                backgroundColor: [<?php foreach ($visitasPorCanal as $v): ?>'<?= $v['color'] ?>',<?php endforeach; ?>],
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => {
                        if (v >= 1000) return (v / 1000).toFixed(1) + 'K';
                        return v;
                    }
                }
            },
            x: { grid: { display: false } }
        },
        animation: { duration: 600, easing: 'easeOutQuart' }
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
