<?php
// index.php - Login principal
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "modules/dashboard/index.php");
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
            header("Location: " . BASE_URL . "modules/socio/dashboard.php");
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
            header("Location: " . BASE_URL . "modules/dashboard/index.php");
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
    <meta name="description" content="GymSuite — Sistema de Gestión para Gimnasios. Acceso seguro.">
    <title>GymSuite | Acceso al Sistema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
    <style>
        /* ── Login specific ───────────────────────── */
        .login-bg {
            min-height: 100vh;
            display: flex;
            background: var(--color-bg);
            position: relative;
            overflow: hidden;
        }

        /* Ambient glow blobs */
        .login-bg::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,0.08) 0%, transparent 70%);
            top: -200px; left: -200px;
            pointer-events: none;
        }
        .login-bg::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,0.05) 0%, transparent 70%);
            bottom: -100px; right: -100px;
            pointer-events: none;
        }

        /* Left panel — brand */
        .login-left {
            display: none;
            flex: 1;
            background: linear-gradient(135deg, #0F0F0F 0%, #080808 100%);
            border-right: 1px solid var(--color-border);
            padding: 3rem;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(249,115,22,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .login-brand-icon {
            width: 2.5rem; height: 2.5rem;
            background: var(--color-primary);
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 20px rgba(249,115,22,0.4);
        }
        .login-brand-icon svg { width: 1.375rem; height: 1.375rem; color: white; }
        .login-brand-name {
            font-family: var(--font-heading);
            font-size: 1.375rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--color-text-main);
        }

        .login-taglines { position: relative; z-index: 1; }
        .login-tagline-main {
            font-family: var(--font-heading);
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            color: var(--color-text-main);
            margin-bottom: 1rem;
        }
        .login-tagline-main span { color: var(--color-primary); }
        .login-tagline-sub {
            font-size: var(--text-base);
            color: var(--color-text-muted);
            line-height: 1.6;
            max-width: 360px;
        }

        .login-features {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        .login-feature {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
        }
        .login-feature-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--color-primary);
            flex-shrink: 0;
            box-shadow: 0 0 6px rgba(249,115,22,0.6);
        }

        @media (min-width: 900px) { .login-left { display: flex; } }

        /* Right panel — form */
        .login-right {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 460px;
            padding: 2rem;
        }

        @media (max-width: 900px) {
            .login-right { flex: 1; }
        }

        .login-card {
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-icon-wrap {
            width: 3rem; height: 3rem;
            background: var(--color-primary-dim);
            border: 1px solid rgba(249,115,22,0.25);
            border-radius: var(--radius-lg);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.25rem;
        }
        .login-icon-wrap svg { width: 1.5rem; height: 1.5rem; color: var(--color-primary); }

        .login-title {
            font-family: var(--font-heading);
            font-size: var(--text-2xl);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--color-text-main);
            margin-bottom: 0.375rem;
        }
        .login-subtitle {
            font-size: var(--text-sm);
            color: var(--color-text-muted);
        }

        .login-form-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-2xl);
            padding: 1.75rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .login-form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 1.5rem; right: 1.5rem; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
        }

        /* Submit button with loading state */
        .btn-login {
            width: 100%;
            height: 46px;
            font-size: var(--text-base);
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
            border: none;
            border-radius: var(--radius-lg);
            color: white;
            cursor: pointer;
            transition: var(--transition-base);
            box-shadow: 0 4px 15px rgba(249,115,22,0.3);
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(249,115,22,0.4);
        }
        .btn-login:active { transform: translateY(0); }

        /* Toggle password */
        .pass-toggle {
            position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--color-text-muted); padding: 0.25rem;
            transition: color 0.15s;
            display: flex; align-items: center;
        }
        .pass-toggle:hover { color: var(--color-text-main); }
        .pass-toggle svg { width: 1rem; height: 1rem; }

        .login-hint {
            margin-top: 1.25rem;
            padding: 0.875rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
        }
        .login-hint-label {
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--color-text-muted);
            margin-bottom: 0.375rem;
            display: block;
        }
        .login-hint-text {
            font-size: var(--text-xs);
            color: var(--color-text-muted);
            line-height: 1.6;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: var(--text-xs);
            color: var(--color-text-muted);
        }
    </style>
</head>
<body>

<div class="login-bg">

    <!-- Left brand panel (desktop) -->
    <div class="login-left">
        <div class="login-brand-logo">
            <div class="login-brand-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <span class="login-brand-name">GymSuite</span>
        </div>

        <div class="login-taglines">
            <h1 class="login-tagline-main">
                Gestión<br>de gimnasios<br><span>sin fricción.</span>
            </h1>
            <p class="login-tagline-sub">
                Controla accesos, socios, pagos y rutinas desde un solo panel diseñado para operar a velocidad real.
            </p>
        </div>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-dot"></div>
                <span>Acceso por PIN, QR o DNI en segundos</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-dot"></div>
                <span>Reportes y analíticas en tiempo real</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-dot"></div>
                <span>Multi-sede con gestión centralizada</span>
            </div>
        </div>
    </div>

    <!-- Right form panel -->
    <div class="login-right">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h2 class="login-title">Bienvenido</h2>
                <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            <?php mostrar_flash(); ?>

            <div class="login-form-card">
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="usuario" class="form-label">Usuario / DNI</label>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            class="form-control"
                            placeholder="admin o número de DNI"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="password" class="form-label">Contraseña / PIN</label>
                        <div style="position:relative;">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                style="padding-right:2.75rem;"
                            >
                            <button type="button" class="pass-toggle" id="togglePass" aria-label="Mostrar contraseña">
                                <svg id="eyeIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Ingresar al Sistema
                    </button>
                </form>
            </div>

            <div class="login-hint">
                <span class="login-hint-label">Tipos de acceso</span>
                <p class="login-hint-text">
                    Personal del sistema: usuario + contraseña<br>
                    Socios: DNI + PIN de 4 dígitos
                </p>
            </div>

            <p class="login-footer">GymSuite &copy; <?= date('Y') ?> &mdash; Sistema SaaS de Gestión</p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    const togglePass = document.getElementById('togglePass');
    const passInput  = document.getElementById('password');
    if (togglePass && passInput) {
        togglePass.addEventListener('click', () => {
            const isHidden = passInput.type === 'password';
            passInput.type = isHidden ? 'text' : 'password';
            togglePass.style.opacity = isHidden ? '1' : '0.5';
        });
    }
    // Loading state on submit
    const form = document.getElementById('loginForm');
    const btn  = document.getElementById('loginBtn');
    if (form && btn) {
        form.addEventListener('submit', () => {
            btn.disabled = true;
            btn.innerHTML = '<svg style="width:1rem;height:1rem;animation:spin 0.8s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Verificando...';
        });
    }
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>

