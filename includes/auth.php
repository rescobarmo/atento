<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function usuarioAutenticado(): bool {
    return isset($_SESSION['usuario_id']);
}

function requerirLogin(): void {
    if (!usuarioAutenticado()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function usuarioActual(): ?array {
    if (!usuarioAutenticado()) return null;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ? AND u.activo = 1
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function tienePermiso(string ...$roles): bool {
    $usuario = usuarioActual();
    if (!$usuario) return false;
    return in_array($usuario['rol_nombre'], $roles);
}

function intentarLogin(string $username, string $password): array {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE (u.username = ? OR u.email = ?) AND u.activo = 1
        ");
        $stmt->execute([$username, $username]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }

        $_SESSION['usuario_id'] = $usuario['id'];

        $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?")
            ->execute([$usuario['id']]);

        return ['success' => true, 'message' => 'Inicio de sesión exitoso'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
    }
}
