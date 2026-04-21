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
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // ── Intentar login como socio (DNI + clave de 4 dígitos) ──
    // Si el campo usuario parece un DNI (solo números) y la clave tiene 4 dígitos
    if (preg_match('/^\d{6,12}$/', $usuario) && preg_match('/^\d{4}$/', $password)) {
        $stmtSocio = $pdo->prepare("SELECT s.*, g.estado as gym_estado FROM socios s JOIN gimnasios g ON g.id = s.gimnasio_id WHERE s.dni = ? AND s.activo = 1 LIMIT 1");
        $stmtSocio->execute([$usuario]);
        $socio = $stmtSocio->fetch();

        if ($socio && $socio['gym_estado'] === 'activo' && password_verify($password, $socio['clave_acceso'])) {
            // Login de socio exitoso
            $_SESSION['usuario_id']  = 'socio_' . $socio['id']; // Prefijo para diferenciar
            $_SESSION['socio_id']    = $socio['id'];
            $_SESSION['gimnasio_id'] = $socio['gimnasio_id'];
            $_SESSION['nombre']      = $socio['nombre'];
            $_SESSION['apellido']    = $socio['apellido'];
            $_SESSION['rol']         = 'socio';
            header("Location: /gymsuite/modules/socio/dashboard.php");
            exit;
        }
        // Si no encontró socio, caerá al mensaje de error abajo
        mensaje_flash('error', 'DNI o clave incorrectos.');
    } else {
        // ── Login como usuario del sistema (admin, recepcionista, superadmin) ──
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Verificar que el gimnasio no esté suspendido (si aplica)
            if ($user['gimnasio_id']) {
                $stmtG = $pdo->prepare("SELECT estado FROM gimnasios WHERE id = ?");
                $stmtG->execute([$user['gimnasio_id']]);
                $gym = $stmtG->fetch();
                if ($gym && $gym['estado'] === 'suspendido') {
                    mensaje_flash('error', 'Tu gimnasio se encuentra suspendido. Contacta al administrador del sistema.');
                    goto fin_post;
                }
            }
            $_SESSION['usuario_id']  = $user['id'];
            $_SESSION['gimnasio_id'] = $user['gimnasio_id'];
            $_SESSION['nombre']      = $user['nombre'];
            $_SESSION['apellido']    = $user['apellido'];
            $_SESSION['rol']         = $user['rol'];
            header("Location: /gymsuite/modules/dashboard/index.php");
            exit;
        } else {
            mensaje_flash('error', 'Usuario o contraseña incorrectos.');
        }
    }
    fin_post:
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
                <label for="usuario" class="form-label" id="labelUsuario">Usuario / DNI</label>
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Usuario del sistema o DNI" required autofocus autocomplete="username">
            </div>
            
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label for="password" class="form-label" id="labelPass">Contraseña / PIN</label>
                <div style="position:relative;display:flex;align-items:center;">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password" style="padding-right:3rem;">
                    <button type="button" id="togglePass" style="position:absolute;right:0.875rem;background:none;border:none;cursor:pointer;color:#555;padding:0.25rem;transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#555'">
                        <svg id="eyeIcon" style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1rem;min-height:52px;">Ingresar al Sistema</button>

            <div style="margin-top:1.25rem;padding:0.875rem;background:rgba(255,255,255,0.03);border:1px solid #222;border-radius:0.5rem;">
                <p style="font-size:0.72rem;color:#666;text-align:center;margin-bottom:0.35rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Tipos de acceso</p>
                <p style="font-size:0.75rem;color:#555;text-align:center;line-height:1.5;">
                    Personal: usuario + contraseña&nbsp;&nbsp;·&nbsp;&nbsp;Socios: DNI + PIN de 4 dígitos
                </p>
            </div>
        </form>
    </div>
</div>

<script src="/gymsuite/assets/js/main.js"></script>
</body>
</html>
