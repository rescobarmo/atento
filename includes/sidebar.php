<?php
$usuario = usuarioActual();
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed left-0 top-0 h-full w-64 z-50 sidebar overflow-y-auto" style="background:#026168">
    <div class="p-6">
        <div class="flex justify-center mb-8">
            <img src="<?= APP_URL ?>/assets/img/logo01.jpeg" alt="RedSalud" class="h-20 w-auto rounded-2xl">
        </div>

        <div class="flex items-center gap-3 px-3 py-3 bg-white/5 rounded-xl mb-6">
            <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:rgba(0,128,137,0.3)">
                <span class="font-semibold text-sm" style="color:#C3E298">
                    <?= strtoupper(substr($usuario['nombre'], 0, 2)) ?>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($usuario['nombre']) ?></p>
                <p class="text-white/50 text-xs"><?= htmlspecialchars($usuario['rol_nombre']) ?></p>
            </div>
        </div>

        <?php $esAgenteSidebar = strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas'; ?>
        <?php if (!$esAgenteSidebar): ?>
        <div class="mb-6 px-3">
            <label class="text-white/50 text-xs uppercase tracking-wider block mb-2">Sucursal</label>
            <select name="sucursal" onchange="cambiarSucursal(this.value)"
                    class="w-full px-3 py-2 rounded-lg text-xs border-0 focus:ring-2 focus:ring-white/30 outline-none bg-white/10 text-white">
                <option value="" class="text-gray-800">Todas</option>
                <?php if (isset($sucursales)): ?>
                    <?php foreach ($sucursales as $s): ?>
                        <option class="text-gray-800" value="<?= htmlspecialchars($s) ?>" <?= ($sucursalSeleccionada ?? '') === $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <?php else: ?>
        <div class="mb-6 px-3">
            <label class="text-white/50 text-xs uppercase tracking-wider block mb-2">Sucursales</label>
            <div class="px-3 py-2 rounded-lg text-xs bg-white/10 text-white font-medium">
                <?= htmlspecialchars($sucursalSeleccionada ?: 'Sin asignar') ?>
            </div>
        </div>
        <?php endif; ?>

        <nav class="space-y-1">
            <?php if ($esAgenteSidebar): ?>
            <a href="<?= APP_URL ?>/pages/agentes.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'agentes.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-user-tie w-5 text-center"></i>
                Agente de Ventas
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/dashboard.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'dashboard.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>

            <a href="<?= APP_URL ?>/pages/historial.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'historial.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-clock-rotate w-5 text-center"></i>
                Historial
            </a>
            <div class="pt-4 pb-2">
                <p class="text-white/40 text-xs uppercase tracking-wider px-4">Módulos</p>
            </div>
            <a href="<?= APP_URL ?>/pages/campanas.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'campanas.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-comments w-5 text-center"></i>
                Red Salud
            </a>
            <a href="<?= APP_URL ?>/pages/leads.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'leads.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-users w-5 text-center"></i>
                Leads
            </a>
            <a href="<?= APP_URL ?>/pages/mantenedor_agentes.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'mantenedor_agentes.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-user-tag w-5 text-center"></i>
                Mantenedor de Agentes
            </a>
            <a href="<?= APP_URL ?>/pages/asignar_leads.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'asignar_leads.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-user-check w-5 text-center"></i>
                Asignación de Leads
            </a>
            <a href="<?= APP_URL ?>/pages/clientes.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'clientes.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-address-book w-5 text-center"></i>
                Clientes Red Salud
            </a>
            <a href="<?= APP_URL ?>/pages/no_contesta.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm <?= $paginaActual === 'no_contesta.php' ? 'active text-white' : 'text-white/70' ?>">
                <i class="fas fa-phone-slash w-5 text-center"></i>
                No Contesta
            </a>
            <?php endif; ?>
            <hr class="border-white/10 my-4">
            <a href="<?= APP_URL ?>/logout.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-white/50 hover:text-white">
                <i class="fas fa-right-from-bracket w-5 text-center"></i>
                Cerrar Sesión
            </a>
        </nav>
    </div>
</aside>
<script>
function cambiarSucursal(valor) {
    const url = new URL(window.location.href);
    if (valor) {
        url.searchParams.set('sucursal', valor);
    } else {
        url.searchParams.delete('sucursal');
    }
    window.location.href = url.toString();
}
</script>
