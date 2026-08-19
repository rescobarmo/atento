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
$where = ["LOWER(r.categoria_cliente) = 'llamado'"];
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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$resumen = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        COALESCE(SUM(r.presupuesto), 0) as presupuesto
    FROM redsalud r
    $joinSuc
    WHERE LOWER(r.categoria_cliente) = 'llamado'
    AND 1=1 $whereSuc
");
$resumen->execute();
$resumen = $resumen->fetch() ?: ['total' => 0, 'presupuesto' => 0];
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
            COALESCE(SUM(r.presupuesto), 0) as presupuesto
        FROM redsalud r
        $joinSuc
        WHERE LOWER(r.categoria_cliente) = 'llamado'
        AND $whereResumen
    ");
    $stmtR->execute($paramResumen);
    $resumen = $stmtR->fetch() ?: ['total' => 0, 'presupuesto' => 0];
}
?>
<?php $titulo = 'Leads Contactados'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#FFFFFF">Leads Contactados</h1>
                    <p class="mt-1" style="color:rgba(255,255,255,0.7)">Clientes que respondieron al contacto</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl card">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:rgba(147,51,234,0.12)">
                            <i class="fas fa-phone-volume text-lg" style="color:#9333ea"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold" style="color:rgba(255,255,255,0.7)">Contactados</p>
                            <p class="font-bold" style="color:#FFFFFF"><?= number_format((int)$resumen['total']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl card">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:rgba(245,158,11,0.12)">
                            <i class="fas fa-coins text-lg" style="color:#F59E0B"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold" style="color:rgba(255,255,255,0.7)">Presupuesto</p>
                            <p class="font-bold" style="color:#FFFFFF">$<?= number_format((int)$resumen['presupuesto'], 0, ',', '.') ?></p>
                        </div>
                    </div>
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
                        <a href="contactados.php" class="px-4 py-2.5 rounded-xl text-sm font-medium" style="border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.7)">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($leads)): ?>
                <div class="card rounded-2xl p-12 text-center">
                    <i class="fas fa-phone-volume text-4xl mb-4" style="color:#CBD5E1"></i>
                    <p class="text-lg font-medium" style="color:rgba(255,255,255,0.7)">No hay leads contactados aún</p>
                </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <?php foreach ($leads as $r): ?>
                <?php
                    $cat = strtolower($r['categoria_cliente'] ?? '');
                    $esLlamado = $cat === 'llamado';
                    $colorBg = '#9333ea';
                    $inicial = mb_strtoupper(mb_substr(htmlspecialchars($r['cliente_nombre'] ?: $r['nombre'] ?? '?'), 0, 1));
                ?>
                <div class="card rounded-2xl overflow-hidden flex flex-col lead-card fade-in">
                    <div class="px-5 py-4 flex items-center justify-between gap-3" style="border-left:4px solid <?= $colorBg ?>">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex items-center justify-center w-11 h-11 rounded-xl text-white font-bold text-lg shrink-0" style="background:<?= $colorBg ?>">
                                <?= $inicial ?: '?' ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold truncate" style="color:#FFFFFF"><?= htmlspecialchars($r['cliente_nombre'] ?: $r['nombre'] ?? 'Sin nombre') ?></p>
                                <p class="text-xs font-mono" style="color:rgba(255,255,255,0.7)"><?= htmlspecialchars($r['numero']) ?></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold text-white uppercase shrink-0" style="background:<?= $colorBg ?>">
                            <?= htmlspecialchars($cat ?: 'SIN CATEGORÍA') ?>
                        </span>
                    </div>

                    <div class="px-5 py-4 flex-1 flex flex-col gap-3">
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs" style="color:rgba(255,255,255,0.7)">
                            <?php if (($esAgente || empty($sucursalSeleccionada)) && $r['sucursal']): ?>
                                <span><i class="fas fa-store mr-1.5" style="color:#00A3E0"></i><?= htmlspecialchars($r['sucursal']) ?></span>
                            <?php endif; ?>
                            <span><i class="far fa-clock mr-1.5" style="color:#00A3E0"></i><?= htmlspecialchars($r['horario'] ?? '-') ?></span>
                        </div>

                        <?php if (!empty($r['conversacion'])): ?>
                        <div class="rounded-xl px-3 py-2.5 text-xs leading-relaxed max-h-32 overflow-y-auto" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85)">
                            <?= nl2br(htmlspecialchars($r['conversacion'])) ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-auto pt-2 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-xs font-medium cursor-pointer select-none" style="color:rgba(255,255,255,0.7)">
                                    <input type="checkbox"
                                           class="w-5 h-5 rounded border-white/30 text-[#00A3E0] focus:ring-[#00A3E0] cursor-pointer contact-checkbox"
                                           <?= $esLlamado ? 'checked' : '' ?>
                                           data-id="<?= htmlspecialchars($r['id']) ?>">
                                    Contactado
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium" style="color:rgba(255,255,255,0.7)">Presupuesto</span>
                                    <input type="text" inputmode="numeric"
                                           class="w-28 px-2 py-1.5 text-xs text-right rounded-lg outline-none presupuesto-input"
                                           style="border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.08);color:#FFFFFF"
                                           value="<?= $r['presupuesto'] ? number_format((int)$r['presupuesto'], 0, ',', '.') : '' ?>"
                                           data-id="<?= htmlspecialchars($r['id']) ?>"
                                           placeholder="0">
                                </div>
                            </div>
                            <div>
                                <div class="relative">
                                    <textarea rows="3"
                                              class="w-full px-3 py-2 text-xs rounded-lg outline-none resize-none obs-input pr-16"
                                              style="border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.08);color:#FFFFFF"
                                              maxlength="200"
                                              data-id="<?= htmlspecialchars($r['id']) ?>"
                                              placeholder="Notas de seguimiento..."><?= htmlspecialchars($r['obs'] ?? '') ?></textarea>
                                    <span class="absolute bottom-2 right-2 text-[10px] text-white/50 obs-count"><?= mb_strlen($r['obs'] ?? '') ?>/200</span>
                                </div>
                            </div>
                            <button type="button"
                                    class="guardar-btn w-full px-4 py-2.5 rounded-xl text-sm font-medium text-white btn-primary transition-opacity flex items-center justify-center gap-2"
                                    data-id="<?= htmlspecialchars($r['id']) ?>">
                                <i class="fas fa-check"></i> Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function guardarTarjeta(card) {
    const btn = card.querySelector('.guardar-btn');
    const id = btn.dataset.id;
    const checkbox = card.querySelector('.contact-checkbox');
    const obsInput = card.querySelector('.obs-input');
    const presupuestoInput = card.querySelector('.presupuesto-input');
    const checked = checkbox.checked;

    let obs = obsInput.value.trim();
    if (!checked) obs = '';

    const valorCategoria = checked ? 'LLAMADO' : 'COTIZANDO';
    const valorPresupuesto = presupuestoInput.value.replace(/\./g, '');

    const campos = [
        { campo: 'categoria_cliente', valor: valorCategoria },
        { campo: 'presupuesto', valor: valorPresupuesto },
        { campo: 'obs', valor: obs }
    ];

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    Promise.all(campos.map(c =>
        fetch('<?= APP_URL ?>/api/update_redsalud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, campo: c.campo, valor: c.valor })
        }).then(r => r.json())
    ))
    .then(resultados => {
        const fallo = resultados.find(r => !r.success);
        if (fallo) {
            alert('Error: ' + (fallo.error || 'desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Guardar cambios';
        } else {
            location.reload();
        }
    })
    .catch(() => {
        alert('Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Guardar cambios';
    });
}

document.querySelectorAll('.guardar-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        guardarTarjeta(this.closest('.lead-card'));
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
});

document.querySelectorAll('.obs-input').forEach(input => {
    const counter = input.closest('.relative').querySelector('.obs-count');

    input.addEventListener('input', function() {
        const len = this.value.length;
        if (counter) counter.textContent = len + '/200';
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
