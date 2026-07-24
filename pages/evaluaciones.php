<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$conversaciones = $pdo->query("
    SELECT * FROM redsalud
    WHERE LOWER(categoria_cliente) NOT IN ('llamado', 'realizado', 'respondio')
    ORDER BY fecha_creacion DESC LIMIT 20
")->fetchAll();

$totalEvaluaciones = $pdo->query("
    SELECT COUNT(*) as c FROM redsalud
    WHERE LOWER(categoria_cliente) IN ('respondio', 'realizado', 'llamado')
")->fetch()['c'];

$totalPendientes = $pdo->query("
    SELECT COUNT(*) as c FROM redsalud
    WHERE LOWER(categoria_cliente) NOT IN ('respondio', 'realizado', 'llamado') OR categoria_cliente IS NULL OR categoria_cliente = ''
")->fetch()['c'];
?>
<?php $titulo = 'Evaluaciones'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Evaluaciones en Terreno</h1>
                    <p class="mt-1" style="color:#64748B">Checklist de auditoría para contactos WhatsApp</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm font-medium" style="color:#1A202C"><?= $totalEvaluaciones ?> completadas</p>
                        <p class="text-xs" style="color:#64748B"><?= $totalPendientes ?> pendientes</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <?php foreach ($conversaciones as $row): ?>
                <div class="card rounded-2xl overflow-hidden fade-in">
                    <div class="px-6 py-4 flex items-center justify-between" style="border-bottom:1px solid #E2E8F0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:#008089">
                                <?= strtoupper(substr($row['nombre'] ?? $row['numero'], 0, 2)) ?>
                            </div>
                            <div>
                                <p class="font-semibold text-sm" style="color:#1A202C"><?= htmlspecialchars($row['nombre'] ?? 'Sin nombre') ?></p>
                                <p class="text-xs font-mono" style="color:#64748B"><?= htmlspecialchars($row['numero']) ?></p>
                            </div>
                        </div>
                        <span class="text-xs" style="color:#64748B"><?= date('d/m/Y H:i', strtotime($row['fecha_creacion'])) ?></span>
                    </div>

                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color:#64748B">Conversación</p>
                            <p class="text-sm" style="color:#1A202C"><?= htmlspecialchars(mb_substr($row['conversacion'] ?? '', 0, 200)) ?></p>
                        </div>

                        <form class="evaluation-form" data-id="<?= htmlspecialchars($row['id']) ?>">
                            <div class="mb-4">
                                <p class="text-xs font-medium uppercase tracking-wider mb-3" style="color:#64748B">Resultado de la Evaluación</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="toggle-btn px-5 py-2.5 rounded-lg text-sm font-medium border eval-btn <?= strtolower($row['categoria_cliente'] ?? '') === 'respondio' ? 'active-conforme' : '' ?>" style="border-color:#E2E8F0;color:#64748B;background:#fff" data-value="RESPONDIO">
                                        <i class="fas fa-check-circle mr-1.5"></i> Conforme
                                    </button>
                                    <button type="button" class="toggle-btn px-5 py-2.5 rounded-lg text-sm font-medium border eval-btn <?= strtolower($row['categoria_cliente'] ?? '') === 'cotizando' ? 'active-observacion' : '' ?>" style="border-color:#E2E8F0;color:#64748B;background:#fff" data-value="COTIZANDO">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i> Observación
                                    </button>
                                    <button type="button" class="toggle-btn px-5 py-2.5 rounded-lg text-sm font-medium border eval-btn" style="border-color:#E2E8F0;color:#64748B;background:#fff" data-value="REALIZADO">
                                        <i class="fas fa-times-circle mr-1.5"></i> No Conforme
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="text-xs font-medium uppercase tracking-wider mb-2 block" style="color:#64748B">Observaciones</label>
                                <textarea class="w-full px-4 py-3 rounded-xl text-sm outline-none resize-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C" rows="2" maxlength="200" placeholder="Agregar observación..."><?= htmlspecialchars($row['obs'] ?? '') ?></textarea>
                                <div class="flex justify-between mt-1">
                                    <span class="text-xs" style="color:#008089"><i class="fas fa-camera mr-1"></i> Adjuntar foto</span>
                                    <span class="text-xs" style="color:#64748B"><span class="char-count">0</span>/200</span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-medium text-white flex items-center gap-2">
                                    <i class="fas fa-save"></i> Guardar Evaluación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($conversaciones)): ?>
                <div class="xl:col-span-2 flex flex-col items-center justify-center py-20" style="color:#64748B">
                    <i class="fas fa-check-circle text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">No hay evaluaciones pendientes</p>
                    <p class="text-sm">Todos los contactos han sido evaluados</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.eval-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const group = this.closest('.flex-wrap');
        group.querySelectorAll('.eval-btn').forEach(b => {
            b.className = b.className.replace(/active-\w+/g, '').trim();
            b.style.background = '#fff';
            b.style.color = '#64748B';
            b.style.borderColor = '#E2E8F0';
        });
        const val = this.dataset.value;
        if (val === 'RESPONDIO') {
            this.classList.add('active-conforme');
        } else if (val === 'COTIZANDO') {
            this.classList.add('active-observacion');
        } else {
            this.classList.add('active-noconforme');
        }
    });
});

document.querySelectorAll('.evaluation-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = this.dataset.id;
        const activeBtn = this.querySelector('.eval-btn.active-conforme, .eval-btn.active-observacion, .eval-btn.active-noconforme');
        const categoria = activeBtn ? activeBtn.dataset.value : '';
        const obs = this.querySelector('textarea').value;

        if (!categoria) {
            alert('Selecciona un resultado de evaluación');
            return;
        }

        fetch('<?= APP_URL ?>/api/update_redsalud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, campo: 'categoria_cliente', valor: categoria })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Error: ' + (data.error || '')); return; }
            return fetch('<?= APP_URL ?>/api/update_redsalud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id, campo: 'obs', valor: obs })
            });
        })
        .then(r => r ? r.json() : null)
        .then(data => {
            this.closest('.card').style.opacity = '0.4';
            this.closest('.card').style.transition = 'opacity 0.3s';
            setTimeout(() => { this.closest('.card').remove(); }, 600);
        })
        .catch(() => alert('Error de conexión'));
    });
});

document.querySelectorAll('textarea').forEach(ta => {
    const counter = ta.parentElement.querySelector('.char-count');
    if (counter) {
        counter.textContent = ta.value.length;
        ta.addEventListener('input', function() {
            counter.textContent = this.value.length;
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
