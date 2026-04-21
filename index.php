<?php
// index.php - Login principal
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: /gymsuite/modules/dashboard/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login exitoso
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['gimnasio_id'] = $user['gimnasio_id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['rol'] = $user['rol'];
        
        header("Location: /gymsuite/modules/dashboard/index.php");
        exit;
    } else {
        mensaje_flash('error', 'Usuario o contraseña incorrectos.');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymSuite | Autenticación</title>
    <link rel="stylesheet" href="/gymsuite/assets/css/style.css">
    <link rel="stylesheet" href="/gymsuite/assets/css/components.css">
    <script src="https://unpkg.com/@heroicons/v2/24/outline/index.js"></script>
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% -20%, #2a1103, var(--color-bg));
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: var(--color-surface);
            border: 1px solid #262626;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-8">
            <!-- Isotipo SVG GymSuite -->
            <svg class="w-16 h-16 mx-auto text-primary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <h1 class="text-h2 mb-1">GymSuite</h1>
            <p class="text-muted">Acceso Seguro al Sistema</p>
        </div>

        <?php mostrar_flash(); ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Ej. admin_titan" required autofocus>
            </div>
            
            <div class="form-group mb-6 relative">
                <label for="password" class="form-label">Contraseña</label>
                <div class="relative flex items-center">
                    <input type="password" id="password" name="password" class="form-control pr-10" placeholder="••••••••" required>
                    <button type="button" class="absolute right-3 text-gray-400 hover:text-white toggle-password" data-target="password">
                        <svg class="w-5 h-5 icon-eye" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg class="w-5 h-5 hidden icon-eye-slash" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full">Ingresar al Sistema</button>
        </form>
    </div>
</div>

<script src="/gymsuite/assets/js/main.js"></script>
</body>
</html>
