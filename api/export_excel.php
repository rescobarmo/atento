<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();

$pdo = getDB();

$filtroBusqueda = $_GET['busqueda'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';
$leads = $_GET['leads'] ?? '';

$sql = "SELECT * FROM redsalud WHERE 1=1";
$params = [];

if ($leads) {
    $sql .= " AND (LOWER(categoria_cliente) = 'cotizando' OR LOWER(categoria_cliente) = 'llamado')";
}
if ($filtroBusqueda) {
    $sql .= " AND (nombre LIKE ? OR numero LIKE ? OR conversacion LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
}
if ($filtroCategoria) {
    $sql .= " AND categoria_cliente = ?";
    $params[] = $filtroCategoria;
}
$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'redsalud_' . date('Y-m-d_Hi') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>RedSalud</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
echo '<body><table>';
echo '<thead>';
echo '<tr style="background:#1e293b;color:#fff;font-weight:bold">';

if ($leads) {
    echo '<th style="padding:8px">Nombre</th>';
    echo '<th style="padding:8px">Teléfono</th>';
    echo '<th style="padding:8px">Conversación</th>';
    echo '<th style="padding:8px">Categoría</th>';
    echo '<th style="padding:8px">Horario</th>';
    echo '<th style="padding:8px">Presupuesto</th>';
    echo '<th style="padding:8px">Contactado</th>';
    echo '<th style="padding:8px">Notas</th>';
} else {
    echo '<th style="padding:8px">Contacto</th>';
    echo '<th style="padding:8px">Teléfono</th>';
    echo '<th style="padding:8px">Categoría</th>';
    echo '<th style="padding:8px">Conversación</th>';
    echo '<th style="padding:8px">Notas</th>';
    echo '<th style="padding:8px">Fecha Creación</th>';
    echo '<th style="padding:8px">Fecha Actualización</th>';
}
echo '</tr>';
echo '</thead><tbody>';

foreach ($rows as $r) {
    $cat = strtolower($r['categoria_cliente'] ?? 'sin categoría');
    $bg = match($cat) {
        'cotizando' => '#dc2626',
        'respondio' => '#16a34a',
        'realizado' => '#2563eb',
        'llamado' => '#9333ea',
        default => '#64748b'
    };
    echo '<tr>';
    if ($leads) {
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['nombre'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['numero'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['conversacion'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0;background:' . $bg . ';color:#fff;font-weight:bold">' . strtoupper(htmlspecialchars($r['categoria_cliente'] ?? 'sin categoría')) . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['horario'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0;text-align:right">' . ($r['presupuesto'] ? number_format((int)$r['presupuesto'], 0, ',', '.') : '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . ($cat === 'llamado' ? 'Sí' : 'No') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['obs'] ?? '') . '</td>';
    } else {
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['nombre'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['numero'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0;background:' . $bg . ';color:#fff;font-weight:bold">' . strtoupper(htmlspecialchars($r['categoria_cliente'] ?? 'sin categoría')) . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['conversacion'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['obs'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['fecha_creacion'] ?? '') . '</td>';
        echo '<td style="padding:6px;border:1px solid #e2e8f0">' . htmlspecialchars($r['fecha_actualizacion'] ?? '') . '</td>';
    }
    echo '</tr>';
}

echo '</tbody></table></body></html>';
