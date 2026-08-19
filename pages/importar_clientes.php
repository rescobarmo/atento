<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();
$pdo = getDB();

$mensaje = '';
$error = '';
$columnas = [];
$filas = [];
$archivo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {
    $archivo = $_FILES['excel'];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo';
    } else {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'])) {
            $error = 'Solo se permiten archivos .xlsx o .csv';
        } else {
            if ($ext === 'csv') {
                $handle = fopen($archivo['tmp_name'], 'r');
                if ($handle) {
                    $cabeceras = fgetcsv($handle, 0, ',');
                    if ($cabeceras) {
                        $columnas = $cabeceras;
                        while (($row = fgetcsv($handle, 0, ',')) !== false) {
                            $filas[] = $row;
                        }
                    }
                    fclose($handle);
                }
            } else {
                try {
                    require_once __DIR__ . '/../lib/SimpleXLSX.php';
                    $xlsx = Shuchkin\SimpleXLSX::parse($archivo['tmp_name']);
                    if ($xlsx) {
                        $rows = $xlsx->rows();
                        if (!empty($rows)) {
                            $columnas = $rows[0];
                            for ($i = 1; $i < count($rows); $i++) {
                                $filas[] = $rows[$i];
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Error al leer el Excel: ' . $e->getMessage();
                }
            }

            if (empty($columnas)) {
                $error = 'No se pudieron leer las columnas del archivo';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    $pdo->beginTransaction();
    try {
        $map = $_POST['map'] ?? [];
        $datos = json_decode($_POST['datos'], true) ?? [];
        $insertados = 0;

        $stmt = $pdo->prepare("INSERT INTO clientesredsalud (nombre, numero, sucursal) VALUES (?, ?, ?)");

        foreach ($datos as $fila) {
            $nombre = $map['nombre'] !== '' && isset($fila[$map['nombre']]) ? trim($fila[$map['nombre']]) : '';
            $numero = $map['numero'] !== '' && isset($fila[$map['numero']]) ? trim($fila[$map['numero']]) : '';
            $sucursal = $map['sucursal'] !== '' && isset($fila[$map['sucursal']]) ? trim($fila[$map['sucursal']]) : '';

            if ($nombre === '' && $numero === '') continue;

            $stmt->execute([$nombre, $numero, $sucursal]);
            $insertados++;
        }

        $pdo->commit();
        $mensaje = "Se importaron $insertados clientes correctamente.";
        $columnas = [];
        $filas = [];
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error al importar: ' . $e->getMessage();
    }
}
?>
<?php $titulo = 'Importar Clientes'; include __DIR__ . '/../includes/header.php'; ?>
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 ml-64 p-6 lg:p-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8 fade-in">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#FFFFFF">Importar Clientes</h1>
                    <p class="mt-1" style="color:rgba(255,255,255,0.7)">Sube un archivo Excel (.xlsx) o CSV con los datos de clientes</p>
                </div>
                <a href="clientes.php" class="text-sm font-medium" style="color:#00A3E0">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a clientes
                </a>
            </div>

            <?php if ($mensaje): ?>
            <div class="rounded-xl px-5 py-4 mb-6 text-sm flex items-center gap-3" style="background:rgba(0,163,224,0.08);border:1px solid rgba(0,163,224,0.15);color:#FFFFFF">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="rounded-xl px-5 py-4 mb-6 text-sm flex items-center gap-3" style="background:rgba(229,62,62,0.08);border:1px solid rgba(229,62,62,0.15);color:#E53E3E">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (empty($columnas)): ?>
            <div class="card rounded-2xl p-8 fade-in">
                <form method="POST" enctype="multipart/form-data" class="flex flex-col items-center gap-6">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center" style="background:rgba(0,163,224,0.1)">
                        <i class="fas fa-file-excel text-3xl" style="color:#00A3E0"></i>
                    </div>
                    <div class="text-center">
                        <p class="font-medium" style="color:#FFFFFF">Selecciona un archivo Excel o CSV</p>
                        <p class="text-xs mt-1" style="color:rgba(255,255,255,0.7)">La primera fila debe contener los nombres de las columnas</p>
                    </div>
                    <label class="cursor-pointer btn-primary px-6 py-3 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                        <i class="fas fa-folder-open"></i> Elegir archivo
                        <input type="file" name="excel" accept=".xlsx,.csv" required class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <?php else: ?>
            <div class="card rounded-2xl p-6 mb-6 fade-in">
                <h3 class="font-semibold mb-4" style="color:#FFFFFF">Columnas detectadas (<?= count($columnas) ?>)</h3>
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach ($columnas as $i => $col): ?>
                    <span class="px-3 py-1 rounded-lg text-xs font-medium" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.15)">
                        <?= htmlspecialchars($col) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs" style="color:rgba(255,255,255,0.7)"><?= count($filas) ?> filas de datos detectadas</p>
            </div>

            <div class="card rounded-2xl p-6 fade-in">
                <h3 class="font-semibold mb-4" style="color:#FFFFFF">Mapear columnas</h3>
                <p class="text-xs mb-6" style="color:rgba(255,255,255,0.7)">Selecciona qué columna del Excel corresponde a cada campo</p>
                <form method="POST">
                    <input type="hidden" name="importar" value="1">
                    <input type="hidden" name="datos" value='<?= htmlspecialchars(json_encode($filas)) ?>'>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-xs font-medium mb-2" style="color:rgba(255,255,255,0.7)">Campo: <b style="color:#FFFFFF">Nombre</b></label>
                            <select name="map[nombre]" class="w-full px-4 py-2.5 rounded-xl text-sm outline-none bg-white/5" style="border:1px solid rgba(255,255,255,0.15);color:#FFFFFF">
                                <option value="">— No importar —</option>
                                <?php foreach ($columnas as $i => $col): ?>
                                <option value="<?= $i ?>"><?= htmlspecialchars($col) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-2" style="color:rgba(255,255,255,0.7)">Campo: <b style="color:#FFFFFF">Teléfono</b></label>
                            <select name="map[numero]" class="w-full px-4 py-2.5 rounded-xl text-sm outline-none bg-white/5" style="border:1px solid rgba(255,255,255,0.15);color:#FFFFFF">
                                <option value="">— No importar —</option>
                                <?php foreach ($columnas as $i => $col): ?>
                                <option value="<?= $i ?>"><?= htmlspecialchars($col) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-2" style="color:rgba(255,255,255,0.7)">Campo: <b style="color:#FFFFFF">Sucursal</b></label>
                            <select name="map[sucursal]" class="w-full px-4 py-2.5 rounded-xl text-sm outline-none bg-white/5" style="border:1px solid rgba(255,255,255,0.15);color:#FFFFFF">
                                <option value="">— No importar —</option>
                                <?php foreach ($columnas as $i => $col): ?>
                                <option value="<?= $i ?>"><?= htmlspecialchars($col) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="card rounded-xl p-4 mb-6" style="background:rgba(255,255,255,0.08)">
                        <p class="text-xs font-medium mb-3" style="color:rgba(255,255,255,0.7)">Vista previa (primeras 5 filas)</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="color:rgba(255,255,255,0.7)">
                                        <?php foreach ($columnas as $col): ?>
                                        <th class="px-3 py-2 text-left font-medium"><?= htmlspecialchars($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="border-color:rgba(255,255,255,0.15)">
                                    <?php foreach (array_slice($filas, 0, 5) as $fila): ?>
                                    <tr>
                                        <?php foreach ($columnas as $i => $col): ?>
                                        <td class="px-3 py-2" style="color:#FFFFFF"><?= htmlspecialchars($fila[$i] ?? '') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary px-8 py-3 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                            <i class="fas fa-database"></i> Importar <?= count($filas) ?> registros
                        </button>
                        <a href="importar_clientes.php" class="px-6 py-3 rounded-xl text-sm font-medium" style="border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.7)">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>