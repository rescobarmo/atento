<?php
require_once __DIR__ . '/includes/auth.php';

if (usuarioAutenticado()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $resultado = intentarLogin($email, $password);
    if ($resultado['success']) {
        header('Location: ' . APP_URL . '/dashboard.php');
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
            background: linear-gradient(135deg, #026168 0%, #0D1B2A 50%, #026168 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .login-input {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.3s ease;
            color: #F4F4F6;
        }
        .login-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #008089;
            box-shadow: 0 0 0 3px rgba(0, 128, 137, 0.15);
        }
        .login-btn {
            background: #008089;
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            background: #026168;
            box-shadow: 0 8px 25px -8px rgba(0, 128, 137, 0.5);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="mb-4">
                <img src="assets/img/logo01.jpeg" alt="RedSalud" class="h-16 mx-auto">
            </div>
            <h1 class="text-3xl font-bold text-white">RedSalud</h1>
            <p class="text-white/60 mt-1">Sistema de Evaluaciones</p>
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
                    <label class="block text-sm font-medium text-slate-300 mb-2" for="email">
                        <i class="fas fa-envelope mr-1" style="color:#8E8E93"></i> Correo electrónico
                    </label>
                    <input type="email" id="email" name="email" required
                           class="login-input w-full rounded-xl px-4 py-3 placeholder-white/40 focus:outline-none"
                           placeholder="tu@correo.cl" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" for="password" style="color:#8E8E93">
                        <i class="fas fa-lock mr-1" style="color:#8E8E93"></i> Contraseña
                    </label>
                    <input type="password" id="password" name="password" required
                           class="login-input w-full rounded-xl px-4 py-3 placeholder-white/40 focus:outline-none"
                           placeholder="••••••••">
                </div>
                <button type="submit"
                        class="login-btn w-full text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2">
                    <i class="fas fa-right-to-bracket"></i>
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-6 pt-6" style="border-top:1px solid rgba(255,255,255,0.08)">
                <p class="text-xs text-center" style="color:#8E8E93">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Credenciales demo: <span style="color:#C3E298">admin@redsalud.cl</span> / <span style="color:#C3E298">password</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
