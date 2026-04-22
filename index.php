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
    <meta name="description" content="GymSuite — Sistema de Gestión para Gimnasios. Acceso seguro al panel.">
    <title>GymSuite | Acceso al Sistema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:      #F97316;
            --primary-dark: #EA580C;
            --primary-dim:  rgba(249,115,22,0.12);
            --font-heading: 'Space Grotesk', sans-serif;
            --font-body:    'Inter', sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font-body);
            background: #080808;
            color: #F2F2F2;
        }

        /* ── Full-screen background ───────────────── */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            position: relative;
            overflow: hidden;
        }

        .login-bg-image {
            position: absolute;
            inset: 0;
            background-image: url('<?= BASE_URL ?>assets/img/gym_bg.png');
            background-size: cover;
            background-position: center 30%;
            filter: brightness(0.35) saturate(0.8);
            z-index: 0;
            transform: scale(1.02); /* prevent white edge on parallax */
        }

        /* Dark gradient overlay left→right */
        .login-bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(8,8,8,0.92) 0%,
                rgba(8,8,8,0.55) 45%,
                rgba(249,115,22,0.08) 100%
            );
            z-index: 1;
        }

        /* Orange corner glow */
        .login-bg-glow {
            position: absolute;
            bottom: -120px; right: -120px;
            width: 550px; height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,0.18) 0%, transparent 70%);
            z-index: 1;
            pointer-events: none;
        }
        .login-bg-glow-top {
            position: absolute;
            top: -100px; left: 30%;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,0.07) 0%, transparent 70%);
            z-index: 1;
            pointer-events: none;
        }

        /* ── Layout split ─────────────────────────── */
        .login-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
        }

        /* Left hero text panel */
        .login-hero {
            flex: 1;
            display: none;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 6rem;
        }

        @media (min-width: 900px) {
            .login-hero { display: flex; }
        }

        .login-hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(249,115,22,0.15);
            border: 1px solid rgba(249,115,22,0.3);
            border-radius: 999px;
            padding: 0.3rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 1.25rem;
            width: fit-content;
        }

        .login-hero-tag span {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 6px rgba(249,115,22,0.8);
        }

        .login-hero-title {
            font-family: var(--font-heading);
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.05;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .login-hero-title em {
            font-style: normal;
            color: var(--primary);
        }

        .login-hero-desc {
            font-size: 1rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.7;
            max-width: 380px;
            margin-bottom: 2.5rem;
        }

        .login-hero-stats {
            display: flex;
            gap: 2.5rem;
        }

        .stat-item { display: flex; flex-direction: column; gap: 0.2rem; }
        .stat-value {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.04em;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            font-weight: 500;
            letter-spacing: 0.03em;
        }

        /* ── Right form panel ─────────────────────── */
        .login-form-panel {
            width: 100%;
            max-width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: rgba(8,8,8,0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-left: 1px solid rgba(255,255,255,0.05);
        }

        @media (max-width: 899px) {
            .login-form-panel {
                max-width: 100%;
                background: rgba(8,8,8,0.85);
                border-left: none;
            }
        }

        .login-form-inner {
            width: 100%;
            max-width: 380px;
        }

        /* Brand */
        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .login-brand-icon {
            width: 3rem; height: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 0.875rem;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 24px rgba(249,115,22,0.45);
            flex-shrink: 0;
        }
        .login-brand-icon svg {
            width: 1.5rem; height: 1.5rem;
            color: white;
        }
        .login-brand-name {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #fff;
        }
        .login-brand-name span { color: var(--primary); }

        /* Heading */
        .login-form-heading {
            margin-bottom: 0.5rem;
        }
        .login-form-title {
            font-family: var(--font-heading);
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 0.375rem;
        }
        .login-form-subtitle {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.4);
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        /* Alert flash */
        .login-alert {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            animation: fadeDown 0.25s ease;
        }
        .login-alert.error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #FCA5A5;
        }
        .login-alert.success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            color: #86EFAC;
        }
        .login-alert svg { width: 1rem; height: 1rem; flex-shrink: 0; }

        /* Form inputs */
        .field { margin-bottom: 1.125rem; }

        .field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.5rem;
            letter-spacing: 0.01em;
        }

        .field-wrap { position: relative; }

        .field-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.25);
            pointer-events: none;
            display: flex;
        }
        .field-icon svg { width: 1rem; height: 1rem; }

        .field-input {
            width: 100%;
            height: 48px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 0.75rem;
            color: #F2F2F2;
            font-size: 0.9375rem;
            font-family: var(--font-body);
            padding: 0 2.75rem;
            outline: none;
            transition: all 0.15s ease;
        }
        .field-input::placeholder { color: rgba(255,255,255,0.2); }
        .field-input:focus {
            border-color: var(--primary);
            background: rgba(249,115,22,0.05);
            box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
        }

        .pass-eye {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255,255,255,0.25);
            padding: 0.25rem;
            display: flex;
            transition: color 0.15s;
        }
        .pass-eye:hover { color: rgba(255,255,255,0.7); }
        .pass-eye svg { width: 1rem; height: 1rem; }

        /* Submit */
        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 0.875rem;
            color: #fff;
            font-family: var(--font-heading);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            transition: all 0.15s ease;
            box-shadow: 0 4px 20px rgba(249,115,22,0.35);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(249,115,22,0.5);
        }
        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(249,115,22,0.3);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .btn-submit svg { width: 1.125rem; height: 1.125rem; }

        /* Divider */
        .login-divider {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            margin: 1.5rem 0 1.125rem;
        }
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.07);
        }
        .login-divider span {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.25);
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Access hint pills */
        .access-hints {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .access-pill {
            flex: 1;
            min-width: 130px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
        }
        .access-pill-label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 0.2rem;
            display: block;
        }
        .access-pill-text {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.35);
            line-height: 1.4;
        }

        /* Footer */
        .login-foot {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.2);
        }

        /* Animations */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .spin { animation: spin 0.8s linear infinite; }
    </style>
</head>
<body>

<div class="login-page">
    <!-- Background layers -->
    <div class="login-bg-image"></div>
    <div class="login-bg-overlay"></div>
    <div class="login-bg-glow"></div>
    <div class="login-bg-glow-top"></div>

    <div class="login-content">

        <!-- Left hero panel (desktop only) -->
        <div class="login-hero">
            <div>
                <div class="login-hero-tag">
                    <span></span>
                    Sistema activo
                </div>
                <h1 class="login-hero-title">
                    Gestiona tu<br>gimnasio <em>sin<br>fricciones.</em>
                </h1>
                <p class="login-hero-desc">
                    Control total de accesos, socios, membresías y pagos desde un solo panel. Veloz, seguro y diseñado para operar en tiempo real.
                </p>
                <div class="login-hero-stats">
                    <div class="stat-item">
                        <span class="stat-value">3</span>
                        <span class="stat-label">Métodos de acceso</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">100%</span>
                        <span class="stat-label">Control en línea</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">24/7</span>
                        <span class="stat-label">Operativo</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="login-form-panel">
            <div class="login-form-inner">

                <!-- Brand -->
                <div class="login-brand">
                    <div class="login-brand-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="login-brand-name">Gym<span>Suite</span></span>
                </div>

                <!-- Heading -->
                <div class="login-form-heading">
                    <h2 class="login-form-title">¡Bienvenido!</h2>
                    <p class="login-form-subtitle">Ingresa tus credenciales para acceder al panel</p>
                </div>

                <!-- Flash message -->
                <?php
                if (isset($_SESSION['flash'])) {
                    $tipo  = $_SESSION['flash']['tipo'];
                    $texto = htmlspecialchars($_SESSION['flash']['texto'], ENT_QUOTES, 'UTF-8');
                    $clase = ($tipo === 'error') ? 'error' : 'success';
                    $icon  = ($tipo === 'error')
                        ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                    echo "<div class='login-alert {$clase}'>{$icon}<span>{$texto}</span></div>";
                    unset($_SESSION['flash']);
                }
                ?>

                <!-- Form -->
                <form method="POST" action="" id="loginForm" novalidate>

                    <div class="field">
                        <label for="usuario" class="field-label">Usuario / DNI</label>
                        <div class="field-wrap">
                            <span class="field-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                class="field-input"
                                placeholder="admin o número de DNI"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="field">
                        <label for="password" class="field-label">Contraseña / PIN</label>
                        <div class="field-wrap">
                            <span class="field-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="field-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="pass-eye" id="togglePass" aria-label="Mostrar contraseña">
                                <svg id="eyeIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="loginBtn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Ingresar al Sistema
                    </button>

                </form>

                <!-- Divider + hints -->
                <div class="login-divider"><span>Tipos de acceso</span></div>
                <div class="access-hints">
                    <div class="access-pill">
                        <span class="access-pill-label">Personal</span>
                        <span class="access-pill-text">Usuario + contraseña del sistema</span>
                    </div>
                    <div class="access-pill">
                        <span class="access-pill-label">Socios</span>
                        <span class="access-pill-text">DNI + PIN de 4 dígitos</span>
                    </div>
                </div>

                <p class="login-foot">GymSuite &copy; <?= date('Y') ?> &mdash; Desarrollado por Dev. REINER JIMENEZ</p>
            </div>
        </div>

    </div><!-- /login-content -->
</div><!-- /login-page -->

<script>
    // Toggle password visibility
    (function() {
        const btn   = document.getElementById('togglePass');
        const input = document.getElementById('password');
        if (!btn || !input) return;
        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.style.color = show ? 'rgba(255,255,255,0.7)' : 'rgba(255,255,255,0.25)';
        });
    })();

    // Loading state on submit
    (function() {
        const form = document.getElementById('loginForm');
        const btn  = document.getElementById('loginBtn');
        if (!form || !btn) return;
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) return;
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="spin" style="width:1.125rem;height:1.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Verificando...
            `;
        });
    })();
</script>
</body>
</html>