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

        <nav class="space-y-1">
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
            <hr class="border-white/10 my-4">
            <a href="<?= APP_URL ?>/logout.php"
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-white/50 hover:text-white">
                <i class="fas fa-right-from-bracket w-5 text-center"></i>
                Cerrar Sesión
            </a>
        </nav>
    </div>
</aside>
