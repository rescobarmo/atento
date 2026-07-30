<?php
require_once __DIR__ . '/../includes/auth.php';
requerirLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$pdo = getDB();
$id = $_POST['id'] ?? '';
$campo = $_POST['campo'] ?? '';
$valor = $_POST['valor'] ?? '';

if (!$id || !$campo) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

$camposPermitidos = ['categoria_cliente', 'obs', 'horario', 'presupuesto'];
if (!in_array($campo, $camposPermitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Campo no permitido']);
    exit;
}

if ($campo === 'obs' && mb_strlen($valor) > 200) {
    http_response_code(400);
    echo json_encode(['error' => 'El texto no puede exceder 200 caracteres']);
    exit;
}

if ($campo === 'presupuesto') {
    $valor = $valor === '' ? null : (int)$valor;
}

try {
    $stmt = $pdo->prepare("UPDATE redsalud SET $campo = ?, fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->execute([$valor, $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Registro no encontrado']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
