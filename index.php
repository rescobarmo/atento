<?php
require_once __DIR__ . '/includes/auth.php';

if (usuarioAutenticado()) {
    $u = usuarioActual();
    if ($u && strtolower($u['rol_nombre'] ?? '') === 'agente de ventas') {
        header('Location: ' . APP_URL . '/pages/agentes.php');
    } else {
        header('Location: ' . APP_URL . '/dashboard.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $resultado = intentarLogin($username, $password);
    if ($resultado['success']) {
        $usuario = usuarioActual();
        if ($usuario && strtolower($usuario['rol_nombre'] ?? '') === 'agente de ventas') {
            header('Location: ' . APP_URL . '/pages/agentes.php');
        } else {
            header('Location: ' . APP_URL . '/dashboard.php');
        }
        exit;
    }
    $error = $resultado['message'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .login-bg {
            background: #F4F8F8;
        }
        .login-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .login-input {
            background: #F4F8F8;
            border: 1px solid #E2E8F0;
            transition: all 0.3s ease;
            color: #1A202C;
        }
        .login-input:focus {
            border-color: #008089;
            box-shadow: 0 0 0 3px rgba(0, 128, 137, 0.12);
            background: #fff;
        }
        .login-btn {
            background: #008089;
            transition: all 0.3s ease;
            color: #fff;
        }
        .login-btn:hover {
            background: #026168;
            box-shadow: 0 4px 12px rgba(0, 128, 137, 0.3);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="mb-4">
                <img src="assets/img/logo01.jpeg" alt="RedSalud" class="h-16 mx-auto rounded-2xl">
            </div>
            <h1 class="text-3xl font-bold" style="color:#026168">RedSalud</h1>
            <p class="mt-1" style="color:#64748B">Sistema de Evaluaciones</p>
        </div>

        <div class="login-card rounded-2xl p-8">
            <?php if ($error): ?>
                <div class="rounded-lg px-4 py-3 mb-6 text-sm flex items-center gap-2" style="background:rgba(229,62,62,0.1);border:1px solid rgba(229,62,62,0.2);color:#E53E3E">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium mb-2" for="username" style="color:#64748B">
                        <i class="fas fa-user mr-1" style="color:#64748B"></i> Usuario
                    </label>
                    <input type="text" id="username" name="username" required
                           class="login-input w-full rounded-xl px-4 py-3 placeholder-slate-400 focus:outline-none"
                           placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" for="password" style="color:#64748B">
                        <i class="fas fa-lock mr-1" style="color:#64748B"></i> Contraseña
                    </label>
                    <input type="password" id="password" name="password" required
                           class="login-input w-full rounded-xl px-4 py-3 placeholder-slate-400 focus:outline-none"
                           placeholder="••••••••">
                </div>
                <button type="submit"
                        class="login-btn w-full text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2">
                    <i class="fas fa-right-to-bracket"></i>
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-6 pt-6" style="border-top:1px solid #E2E8F0">
                <p class="text-xs text-center" style="color:#64748B">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Credenciales: <span style="color:#C3E298">admin</span> / <span style="color:#C3E298">r3dsalud</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
