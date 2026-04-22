<?php
// modules/accesos/por_clave.php — Control de acceso por PIN de 4 dígitos
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
    /* ── Method Tabs ─────────────────────────────── */
    .method-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 1.75rem;
        border-bottom: 1px solid var(--color-border);
    }
    .method-tab {
        padding: 0.625rem 1.125rem;
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--color-text-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: var(--transition-fast);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        letter-spacing: 0.01em;
    }
    .method-tab:hover { color: var(--color-text-main); }
    .method-tab.active { color: var(--color-primary); border-bottom-color: var(--color-primary); }

    /* ── PIN Pad wrapper ─────────────────────────── */
    .pinpad-wrapper { max-width: 400px; margin: 0 auto; }

    /* ── PIN Display ─────────────────────────────── */
    .pin-display-area {
        background: #0A0A0A;
        border: 1px solid var(--color-border-strong);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        margin-bottom: 0.875rem;
        text-align: center;
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.6);
        position: relative;
        overflow: hidden;
    }
    .pin-display-area::before {
        content: '';
        position: absolute;
        top: 0; left: 1rem; right: 1rem; height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.04), transparent);
    }
    .pin-display-label {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--color-text-muted);
        margin-bottom: 1rem;
        display: block;
    }
    .pin-display {
        display: flex;
        justify-content: center;
        gap: 1.125rem;
    }
    .pin-dot {
        width: 14px; height: 14px;
        border-radius: 50%;
        border: 1.5px solid #2E2E2E;
        background: transparent;
        transition: all 0.15s cubic-bezier(0.4,0,0.2,1);
    }
    .pin-dot.filled {
        background: var(--color-primary);
        border-color: var(--color-primary);
        box-shadow: 0 0 12px rgba(249,115,22,0.6), 0 0 4px rgba(249,115,22,0.9);
        transform: scale(1.2);
    }

    /* ── Lockout ─────────────────────────────────── */
    .lockout-banner {
        background: var(--color-danger-dim);
        border: 1px solid var(--color-danger-border);
        border-radius: var(--radius-lg);
        padding: 1rem 1.25rem;
        text-align: center;
        display: none;
        margin-bottom: 0.875rem;
    }
    .lockout-banner.show { display: block; animation: slideUp 0.25s ease; }

    /* ── Vault Keypad ────────────────────────────── */
    .vault-keypad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .pin-btn {
        position: relative;
        background: linear-gradient(160deg, #1A1A1A 0%, #111111 100%);
        border: 1px solid #242424;
        border-bottom: 2px solid #0A0A0A;
        border-radius: var(--radius-lg);
        color: var(--color-text-main);
        font-size: 1.5rem;
        font-weight: 700;
        font-family: var(--font-heading);
        letter-spacing: -0.02em;
        cursor: pointer;
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        min-height: 76px;
        transition: all 0.1s cubic-bezier(0.4,0,0.2,1);
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
        overflow: hidden;
    }

    /* Subtle top glint */
    .pin-btn::before {
        content: '';
        position: absolute;
        top: 0; left: 15%; right: 15%;
        height: 1px;
        background: rgba(255,255,255,0.06);
    }

    .pin-btn:hover {
        background: linear-gradient(160deg, #222 0%, #181818 100%);
        border-color: #303030;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.05);
        color: #fff;
    }

    .pin-btn:active {
        transform: translateY(1px);
        box-shadow: 0 1px 3px rgba(0,0,0,0.6), inset 0 2px 4px rgba(0,0,0,0.3);
        background: linear-gradient(160deg, #111 0%, #0E0E0E 100%);
        border-color: #1A1A1A;
    }

    .pin-btn .sub-letters {
        font-size: 0.5rem;
        letter-spacing: 0.12em;
        color: var(--color-text-muted);
        font-family: var(--font-body);
        font-weight: 600;
        line-height: 1;
    }

    .pin-btn.del-btn {
        background: linear-gradient(160deg, #1A0E0E 0%, #110808 100%);
        border-color: rgba(239,68,68,0.2);
        color: var(--color-danger);
        font-size: 0.875rem;
    }
    .pin-btn.del-btn:hover {
        background: linear-gradient(160deg, #260E0E 0%, #1A0808 100%);
        border-color: rgba(239,68,68,0.35);
        box-shadow: 0 4px 10px rgba(0,0,0,0.5), 0 0 12px rgba(239,68,68,0.12);
    }

    .pin-btn.enter-btn {
        background: linear-gradient(160deg, #C05A10 0%, var(--color-primary-hover) 100%);
        border-color: var(--color-primary-hover);
        border-bottom-color: rgba(0,0,0,0.5);
        color: #fff;
        font-size: 0.875rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.5), 0 0 16px rgba(249,115,22,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
    }
    .pin-btn.enter-btn:hover {
        background: linear-gradient(160deg, #D06418 0%, #EA580C 100%);
        box-shadow: 0 4px 12px rgba(0,0,0,0.5), var(--shadow-glow-primary);
        transform: translateY(-1px);
    }
    .pin-btn.enter-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none;
    }

    /* ── Result Card ─────────────────────────────── */
    .result-card {
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        animation: slideUp 0.25s ease;
        border: 1px solid transparent;
        margin-top: 0.875rem;
    }
    .result-card.estado-activa     { background: rgba(34,197,94,0.07);   border-color: var(--color-success-border); }
    .result-card.estado-por_vencer { background: rgba(245,158,11,0.07);  border-color: var(--color-warning-border); }
    .result-card.estado-vencida    { background: rgba(239,68,68,0.07);   border-color: var(--color-danger-border); }
    .result-card.estado-error      { background: rgba(239,68,68,0.07);   border-color: var(--color-danger-border); }

    .socio-avatar {
        width: 3.5rem; height: 3.5rem; border-radius: 50%;
        object-fit: cover; border: 2px solid var(--color-border-strong); flex-shrink: 0;
    }
    .socio-avatar-placeholder {
        width: 3.5rem; height: 3.5rem; border-radius: 50%;
        background: var(--color-surface-hover);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; font-weight: 800;
        color: var(--color-text-muted); flex-shrink: 0;
        font-family: var(--font-heading);
        border: 2px solid var(--color-border-strong);
    }

    /* ── Confirmed Overlay ───────────────────────── */
    .confirmed-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.92);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 300;
        flex-direction: column; align-items: center; justify-content: center; gap: 1.25rem;
    }
    .confirmed-overlay.show { display: flex; animation: fadeIn 0.2s ease; }
    .confirmed-check {
        width: 5.5rem; height: 5.5rem;
        background: radial-gradient(circle at 40% 35%, #26D869, var(--color-success));
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 60px rgba(34,197,94,0.5), 0 0 120px rgba(34,197,94,0.2);
        animation: popIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes popIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<div class="pinpad-wrapper">
    <div style="margin-bottom:1.75rem;">
        <h1 class="text-h2" style="margin-bottom:0.25rem;">Control de Accesos</h1>
        <p class="text-muted" style="font-size:var(--text-sm);">El socio ingresa su PIN de 4 dígitos</p>
    </div>

    <!-- Tabs -->
    <nav class="method-tabs">
        <a href="por_dni.php" class="method-tab">
            <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
            Por DNI
        </a>
        <a href="por_clave.php" class="method-tab active">
            <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Por PIN
        </a>
        <a href="por_qr.php" class="method-tab">
            <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Por QR
        </a>
    </nav>

    <!-- PIN Display -->
    <div class="pin-display-area">
        <span class="pin-display-label">Ingresa tu clave secreta</span>
        <div class="pin-display" id="pinDisplay">
            <div class="pin-dot" id="dot0"></div>
            <div class="pin-dot" id="dot1"></div>
            <div class="pin-dot" id="dot2"></div>
            <div class="pin-dot" id="dot3"></div>
        </div>
    </div>

    <!-- Lockout banner -->
    <div class="lockout-banner" id="lockoutBanner">
        <p style="color:var(--color-danger); font-weight:700; font-size:0.9rem; margin-bottom:0.25rem;">Sistema bloqueado temporalmente</p>
        <p id="lockoutTimer" style="font-size:2rem; font-weight:800; font-family:var(--font-heading); color:var(--color-danger); margin-bottom:0.25rem; letter-spacing:-0.03em;">2:00</p>
        <p style="font-size:0.75rem; color:var(--color-text-muted);">Demasiados intentos fallidos</p>
    </div>

    <!-- Vault keypad -->
    <div id="pinpadGrid">
        <div class="vault-keypad">
            <button class="pin-btn" onclick="appendDigit('1')">1</button>
            <button class="pin-btn" onclick="appendDigit('2')">2<span class="sub-letters">ABC</span></button>
            <button class="pin-btn" onclick="appendDigit('3')">3<span class="sub-letters">DEF</span></button>
            <button class="pin-btn" onclick="appendDigit('4')">4<span class="sub-letters">GHI</span></button>
            <button class="pin-btn" onclick="appendDigit('5')">5<span class="sub-letters">JKL</span></button>
            <button class="pin-btn" onclick="appendDigit('6')">6<span class="sub-letters">MNO</span></button>
            <button class="pin-btn" onclick="appendDigit('7')">7<span class="sub-letters">PQRS</span></button>
            <button class="pin-btn" onclick="appendDigit('8')">8<span class="sub-letters">TUV</span></button>
            <button class="pin-btn" onclick="appendDigit('9')">9<span class="sub-letters">WXYZ</span></button>
            <button class="pin-btn del-btn" onclick="deleteDigit()">
                <svg style="width:1.375rem;height:1.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/></svg>
            </button>
            <button class="pin-btn" onclick="appendDigit('0')">0</button>
            <button class="pin-btn enter-btn" id="enterBtn" onclick="validarPin()" disabled>
                <svg style="width:1.375rem;height:1.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
    </div>

    <!-- Resultado -->
    <div id="resultadoPin"></div>
</div>

<!-- Overlay de confirmación -->
<div class="confirmed-overlay" id="confirmedOverlay">
    <div class="confirmed-check">
        <svg style="width:2.75rem;height:2.75rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p style="font-size:2rem; font-weight:800; font-family:var(--font-heading); color:white; letter-spacing:-0.03em; margin:0;" id="confirmedNombre"></p>
    <p style="color:var(--color-success); font-weight:600; font-size:1rem; letter-spacing:0.02em; text-transform:uppercase;">¡Entrada Registrada!</p>
    <p style="color:var(--color-text-muted); font-size:var(--text-sm);" id="confirmedHora"></p>
</div>


<script>
(function() {
    let pin = '';
    let lockoutActive = false;
    let lockoutInterval = null;
    let socioActual = null;
    const BASE = '<?= BASE_URL ?>';

    function beepOK() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(); const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(); osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}
    }

    function updateDisplay() {
        for (let i = 0; i < 4; i++) {
            document.getElementById('dot' + i).classList.toggle('filled', i < pin.length);
        }
        document.getElementById('enterBtn').disabled = (pin.length < 4);
    }

    window.appendDigit = function(d) {
        if (lockoutActive || pin.length >= 4) return;
        pin += d;
        updateDisplay();
        if (pin.length === 4) validarPin();
    };

    window.deleteDigit = function() {
        if (lockoutActive) return;
        pin = pin.slice(0, -1);
        updateDisplay();
        document.getElementById('resultadoPin').innerHTML = '';
    };

    function resetPin() {
        pin = '';
        updateDisplay();
        document.getElementById('enterBtn').disabled = true;
    }

    function shakeDots() {
        const display = document.getElementById('pinDisplay');
        display.style.animation = 'shake 0.4s ease';
        setTimeout(() => display.style.animation = '', 400);
    }

    function startLockout(segundos) {
        lockoutActive = true;
        const banner = document.getElementById('lockoutBanner');
        banner.classList.add('show');

        function updateTimer() {
            const m = Math.floor(segundos / 60);
            const s = segundos % 60;
            document.getElementById('lockoutTimer').textContent = `${m}:${s.toString().padStart(2,'0')}`;
        }
        updateTimer();

        lockoutInterval = setInterval(() => {
            segundos--;
            if (segundos <= 0) {
                clearInterval(lockoutInterval);
                lockoutActive = false;
                banner.classList.remove('show');
                resetPin();
                document.getElementById('resultadoPin').innerHTML = '';
            } else {
                updateTimer();
            }
        }, 1000);
    }

    window.validarPin = function() {
        if (pin.length < 4 || lockoutActive) return;
        document.getElementById('enterBtn').disabled = true;

        fetch(BASE + 'ajax/validar_clave.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `pin=${encodeURIComponent(pin)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.bloqueado) {
                shakeDots();
                startLockout(data.segundos || 120);
                resetPin();
                return;
            }
            if (!data.exito) {
                shakeDots();
                resetPin();
                document.getElementById('resultadoPin').innerHTML = `
                <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:0.75rem;padding:0.875rem 1rem;text-align:center;margin-top:0.75rem;animation:slideUp 0.25s ease;">
                    <p style="color:#ef4444;font-weight:600;font-size:0.9rem;">${data.mensaje || 'PIN incorrecto'}</p>
                </div>`;
                return;
            }

            // ✅ PIN correcto
            socioActual = data;
            const esActiva = data.estado === 'activa' || data.estado === 'por_vencer';
            const estadoClases = { activa:'estado-activa', por_vencer:'estado-por_vencer', vencida:'estado-vencida', sin_membresia:'estado-error' };

            const avatarHTML = data.foto
                ? `<img src="<?= BASE_URL ?>uploads/fotos/${data.foto}" class="socio-avatar" alt="${data.nombre}">`
                : `<div class="socio-avatar-placeholder">${(data.nombre||'?').charAt(0).toUpperCase()}</div>`;

            document.getElementById('resultadoPin').innerHTML = `
            <div class="result-card ${estadoClases[data.estado] || 'estado-error'}">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                    ${avatarHTML}
                    <div>
                        <p style="font-size:1.15rem;font-weight:700;margin-bottom:0.15rem;">${data.nombre}</p>
                        <p style="font-size:0.8rem;color:var(--color-text-muted);">DNI: ${data.dni}</p>
                        <p style="font-size:0.8rem;color:var(--color-text-muted);">${data.plan || 'Sin plan activo'}</p>
                    </div>
                </div>
                ${esActiva ? `
                <button onclick="registrarEntrada()" id="btnRegistrar" class="pin-btn enter-btn" style="width:100%;aspect-ratio:unset;min-height:52px;font-size:1rem;border-radius:0.75rem;gap:0.5rem;font-family:var(--font-body);">
                    <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Registrar Entrada
                </button>` : `
                <div style="text-align:center;padding:0.75rem;background:rgba(239,68,68,0.1);border-radius:0.5rem;">
                    <p style="color:#ef4444;font-weight:700;margin-bottom:0.25rem;">Membresía Vencida</p>
                    <a href="<?= BASE_URL ?>modules/membresias/renovar.php?socio_id=${data.id}" class="btn btn-primary" style="margin-top:0.5rem;font-size:0.875rem;min-height:40px;padding:0.5rem 1rem;display:inline-flex;">Renovar</a>
                </div>`}
            </div>`;

            if (esActiva) registrarEntrada();
        })
        .catch(() => {
            document.getElementById('resultadoPin').innerHTML = `<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:0.75rem;padding:0.875rem;text-align:center;margin-top:0.75rem;"><p style="color:#ef4444;font-weight:600;">Error de conexión</p></div>`;
            resetPin();
        });
    };

    window.registrarEntrada = function() {
        if (!socioActual) return;
        fetch(BASE + 'ajax/registrar_acceso.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `socio_id=${socioActual.id}&metodo=clave`
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                beepOK();
                document.getElementById('confirmedNombre').textContent = socioActual.nombre;
                document.getElementById('confirmedHora').textContent = new Date().toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'});
                document.getElementById('confirmedOverlay').classList.add('show');
                setTimeout(() => {
                    document.getElementById('confirmedOverlay').classList.remove('show');
                    document.getElementById('resultadoPin').innerHTML = '';
                    socioActual = null;
                    resetPin();
                }, 3500);
            } else {
                document.getElementById('resultadoPin').innerHTML = `
                <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:0.75rem;padding:1.5rem;text-align:center;margin-top:0.75rem;animation:slideUp 0.25s ease;">
                    <svg style="width:3rem;height:3rem;color:#ef4444;margin:0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p style="color:#ef4444;font-weight:700;font-size:1.1rem;margin-bottom:0.5rem;">Acceso Denegado</p>
                    <p style="color:var(--color-text-muted);">${data.mensaje}</p>
                    <button onclick="document.getElementById('resultadoPin').innerHTML=''; resetPin();" class="btn btn-secondary" style="margin-top:1rem;width:100%;">Intentar con otro PIN</button>
                </div>`;
            }
        });
    };

    // Soporte teclado físico
    document.addEventListener('keydown', (e) => {
        if (e.key >= '0' && e.key <= '9') appendDigit(e.key);
        else if (e.key === 'Backspace') deleteDigit();
        else if (e.key === 'Enter' && pin.length === 4) validarPin();
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
