<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();
$usuario = usuarioActual();

if (strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas') {
    header('Location: ' . APP_URL . '/pages/agentes.php');
    exit;
}

foreach (['limite_leads' => "ALTER TABLE usuarios ADD COLUMN limite_leads INT DEFAULT 0 AFTER sucursal",
          'numero_contacto' => "ALTER TABLE usuarios ADD COLUMN numero_contacto VARCHAR(50) DEFAULT NULL AFTER limite_leads"] as $col => $ddl) {
    $existe = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '" . addslashes($col) . "'")->fetch();
    if (!$existe) {
        try {
            $pdo->exec($ddl);
        } catch (Exception $e) {}
    }
}

$mensaje = '';
$errorMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'asignar';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $numeroContacto = trim($_POST['numero_contacto'] ?? '');
        $limiteLeads = max(0, (int)($_POST['limite_leads'] ?? 0));

        $rolId = $pdo->query("SELECT id FROM roles WHERE nombre = 'Agente de Ventas'")->fetchColumn();

        if (!$nombre || !$email || !$password || !$rolId) {
            $errorMensaje = 'Complete nombre, email y contraseña para crear el agente.';
        } else {
            if (!$username) {
                $username = strtolower(explode('@', $email)[0]);
            }
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nombre, email, username, password, rol_id, activo, numero_contacto, limite_leads)
                    VALUES (?, ?, ?, ?, ?, 1, ?, ?)
                ");
                $stmt->execute([$nombre, $email, $username, password_hash($password, PASSWORD_DEFAULT), $rolId, $numeroContacto ?: null, $limiteLeads]);
                $mensaje = "Agente '$nombre' creado correctamente. Usuario: $username";
            } catch (Exception $e) {
                $errorMensaje = 'Error al crear el agente: ' . $e->getMessage();
            }
        }
    } elseif ($accion === 'guardar_agente') {
        $agenteId = (int)($_POST['agente_id'] ?? 0);
        $numeroContacto = trim($_POST['numero_contacto'] ?? '');
        $limiteLeads = max(0, (int)($_POST['limite_leads'] ?? 0));
        if ($agenteId > 0) {
            $pdo->prepare("UPDATE usuarios SET numero_contacto = ?, limite_leads = ? WHERE id = ?")
                ->execute([$numeroContacto ?: null, $limiteLeads, $agenteId]);
            $mensaje = 'Datos del agente actualizados.';
        }
    } elseif ($accion === 'desactivar') {
        $agenteId = (int)($_POST['agente_id'] ?? 0);
        if ($agenteId > 0) {
            $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$agenteId]);
            $pdo->prepare("DELETE FROM agente_sucursales WHERE agente_id = ?")->execute([$agenteId]);
            $mensaje = 'Agente desactivado.';
        }
    } else {
        $asignaciones = $_POST['asignacion'] ?? [];
        $borradas = 0;
        $insertadas = 0;
        $leadsAsignados = 0;

        $del = $pdo->prepare("DELETE FROM agente_sucursales WHERE agente_id = ?");
        $ins = $pdo->prepare("INSERT IGNORE INTO agente_sucursales (agente_id, sucursal) VALUES (?, ?)");
        $asigLeads = $pdo->prepare("
            UPDATE redsalud r
            JOIN clientesredsalud c ON r.numero COLLATE utf8mb4_unicode_ci = c.numero
            SET r.agente_id = ?
            WHERE c.sucursal = ?
              AND (LOWER(r.categoria_cliente) = 'cotizando' OR LOWER(r.categoria_cliente) = 'llamado')
        ");

        foreach ($asignaciones as $agenteId => $sucursales) {
            $agenteId = (int)$agenteId;
            if ($agenteId <= 0) continue;
            $del->execute([$agenteId]);
            $borradas++;
            $sucursales = is_array($sucursales) ? $sucursales : [];
            foreach ($sucursales as $sucursal) {
                if ($sucursal === '') continue;
                $ins->execute([$agenteId, $sucursal]);
                $insertadas++;
                $asigLeads->execute([$agenteId, $sucursal]);
                $leadsAsignados += $asigLeads->rowCount();
            }
        }

        $mensaje = "Asignaciones guardadas: $insertadas sucursales en $borradas agentes. $leadsAsignados leads asignados.";
    }
}

$agentes = $pdo->query("
    SELECT u.id, u.nombre, u.email, u.username, u.activo, u.numero_contacto, u.limite_leads
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE r.nombre = 'Agente de Ventas'
    ORDER BY u.activo DESC, u.nombre
")->fetchAll();

$sucursales = $pdo->query("SELECT DISTINCT sucursal FROM clientesredsalud WHERE sucursal IS NOT NULL AND sucursal != '' ORDER BY sucursal")->fetchAll(PDO::FETCH_COLUMN);

$asignadas = $pdo->query("SELECT agente_id, sucursal FROM agente_sucursales")->fetchAll();
$mapa = [];
foreach ($asignadas as $a) {
    $mapa[(int)$a['agente_id']][] = $a['sucursal'];
}
?>
<?php $titulo = 'Mantenedor de Agentes'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#1A202C">Mantenedor de Agentes</h1>
                    <p class="mt-1" style="color:#64748B">Asigna las sucursales que atiende cada agente de ventas</p>
                </div>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="rounded-2xl px-4 py-3 mb-6 text-sm flex items-center gap-2" style="background:rgba(22,163,74,0.1);border:1px solid rgba(22,163,74,0.25);color:#16a34a">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMensaje)): ?>
                <div class="rounded-2xl px-4 py-3 mb-6 text-sm flex items-center gap-2" style="background:rgba(229,62,62,0.1);border:1px solid rgba(229,62,62,0.25);color:#E53E3E">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMensaje) ?>
                </div>
            <?php endif; ?>

            <div class="card rounded-2xl p-6 mb-6">
                <h2 class="font-semibold mb-4" style="color:#1A202C"><i class="fas fa-user-plus mr-2" style="color:#008089"></i> Crear Agente de Ventas</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="hidden" name="accion" value="crear">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Nombre</label>
                        <input type="text" name="nombre" required placeholder="Nombre del agente"
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Email</label>
                        <input type="email" name="email" required placeholder="agente@redsalud.cl"
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Usuario (opcional)</label>
                        <input type="text" name="username" placeholder="auto desde email"
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Número de contacto</label>
                        <input type="text" name="numero_contacto" placeholder="+569..."
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#64748B">Límite de leads</label>
                        <input type="number" name="limite_leads" min="0" value="0"
                               class="w-full px-3 py-2.5 rounded-xl outline-none text-sm" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                            <i class="fas fa-plus mr-1"></i> Crear Agente
                        </button>
                    </div>
                </form>
            </div>

            <?php if (empty($agentes)): ?>
                <div class="card rounded-2xl p-12 text-center" style="color:#64748B">
                    <i class="fas fa-user-tie text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Aún no hay agentes de ventas</p>
                    <p class="text-sm mt-1">Usa el formulario "Crear Agente de Ventas" para agregar uno.</p>
                </div>
            <?php elseif (empty($sucursales)): ?>
                <div class="card rounded-2xl p-12 text-center" style="color:#64748B">
                    <i class="fas fa-store text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">No hay sucursales registradas</p>
                    <p class="text-sm mt-1">La lista de sucursales se obtiene de la tabla clientesredsalud.</p>
                </div>
            <?php else: ?>
                <form method="POST" id="form-asignar" class="card rounded-2xl overflow-hidden">
                    <input type="hidden" name="accion" value="asignar">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider" style="color:#64748B;background:#F4F8F8">
                                    <th class="px-6 py-4 font-medium">Agente</th>
                                    <th class="px-6 py-4 font-medium">Contacto</th>
                                    <th class="px-6 py-4 font-medium">Límite</th>
                                    <th class="px-6 py-4 font-medium">Sucursales asignadas</th>
                                    <th class="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color:#E2E8F0">
                                <?php foreach ($agentes as $a): ?>
                                <tr class="hover:bg-slate-50 transition-colors <?= (int)$a['activo'] ? '' : 'opacity-50' ?>">
                                    <td class="px-6 py-4 align-top">
                                        <p class="font-medium" style="color:#1A202C">
                                            <?= htmlspecialchars($a['nombre']) ?>
                                            <?php if (!(int)$a['activo']): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background:#94a3b8">INACTIVO</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs mt-0.5" style="color:#94a3b8"><?= htmlspecialchars($a['email']) ?></p>
                                        <p class="text-xs mt-0.5 font-mono" style="color:#94a3b8">@<?= htmlspecialchars($a['username'] ?? $a['email']) ?></p>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <input type="text" form="form-agente-<?= (int)$a['id'] ?>" name="numero_contacto" value="<?= htmlspecialchars($a['numero_contacto'] ?? '') ?>"
                                               placeholder="+569..."
                                               class="w-40 px-2 py-1.5 text-xs rounded-lg outline-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <input type="number" form="form-agente-<?= (int)$a['id'] ?>" name="limite_leads" min="0" value="<?= (int)$a['limite_leads'] ?>"
                                               class="w-20 px-2 py-1.5 text-xs rounded-lg outline-none" style="border:1px solid #E2E8F0;background:#F4F8F8;color:#1A202C">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <?php foreach ($sucursales as $s): ?>
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none px-3 py-1.5 rounded-lg border text-xs transition-colors"
                                                       style="border-color:#E2E8F0;background:#FAFAFA;color:#1A202C">
                                                    <input type="checkbox" name="asignacion[<?= (int)$a['id'] ?>][]" value="<?= htmlspecialchars($s) ?>"
                                                           class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                                           <?= in_array($s, $mapa[(int)$a['id']] ?? [], true) ? 'checked' : '' ?>
                                                           <?= (int)$a['activo'] ? '' : 'disabled' ?>>
                                                    <?= htmlspecialchars($s) ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right align-top whitespace-nowrap">
                                        <button type="submit" form="form-agente-<?= (int)$a['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors hover:bg-emerald-50" style="border:1px solid #E2E8F0;color:#16a34a">
                                            <i class="fas fa-check mr-1"></i> Guardar
                                        </button>
                                        <?php if ((int)$a['activo']): ?>
                                            <button type="button" onclick="desactivarAgente(<?= (int)$a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nombre']), ENT_QUOTES) ?>')"
                                                    class="ml-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors hover:bg-red-50" style="border:1px solid #E2E8F0;color:#E53E3E">
                                                <i class="fas fa-user-slash mr-1"></i> Desactivar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 flex items-center justify-between" style="background:#F4F8F8">
                        <p class="text-xs" style="color:#64748B">El agente verá los leads de todas las sucursales marcadas.</p>
                        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                            <i class="fas fa-save mr-1"></i> Guardar Asignaciones
                        </button>
                    </div>
                </form>

                <?php foreach ($agentes as $a): ?>
                    <form method="POST" id="form-agente-<?= (int)$a['id'] ?>" class="hidden">
                        <input type="hidden" name="accion" value="guardar_agente">
                        <input type="hidden" name="agente_id" value="<?= (int)$a['id'] ?>">
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
function desactivarAgente(id, nombre) {
    if (!confirm('¿Desactivar a ' + nombre + '? Ya no podrá ingresar al sistema.')) return;
    const fd = new FormData();
    fd.append('accion', 'desactivar');
    fd.append('agente_id', id);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => { if (r.ok) window.location.reload(); else alert('Error al desactivar'); })
        .catch(() => alert('Error de conexión'));
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>