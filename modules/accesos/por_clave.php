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
    /* ── PIN Pad ── */
    .pinpad-wrapper {
        max-width: 420px;
        margin: 0 auto;
    }
    .pin-display {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin: 1.5rem 0;
    }
    .pin-dot {
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 50%;
        border: 2px solid #444;
        background: transparent;
        transition: all 0.15s ease;
    }
    .pin-dot.filled {
        background: var(--color-primary);
        border-color: var(--color-primary);
        box-shadow: 0 0 10px rgba(249,115,22,0.5);
        transform: scale(1.15);
    }
    .pinpad-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .pin-btn {
        background: var(--color-surface);
        border: 1px solid #333;
        border-radius: 0.875rem;
        color: white;
        font-size: 1.6rem;
        font-weight: 700;
        font-family: var(--font-heading);
        cursor: pointer;
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.1rem;
        min-height: 80px;
        transition: all 0.12s ease;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        line-height: 1;
    }
    .pin-btn:hover { background: var(--color-surface-hover); border-color: #444; transform: scale(1.03); }
    .pin-btn:active { transform: scale(0.96); background: #2a2a2a; }
    .pin-btn .sub-letters {
        font-size: 0.5rem;
        letter-spacing: 0.1em;
        color: var(--color-text-muted);
        font-family: var(--font-body);
        font-weight: 500;
        margin-top: 1px;
    }
    .pin-btn.del-btn {
        background: rgba(239,68,68,0.08);
        border-color: rgba(239,68,68,0.2);
        font-size: 1rem;
        color: #ef4444;
    }
    .pin-btn.del-btn:hover { background: rgba(239,68,68,0.15); }
    .pin-btn.enter-btn {
        background: var(--color-primary);
        border-color: var(--color-primary);
        font-size: 1rem;
        color: white;
        font-weight: 700;
    }
    .pin-btn.enter-btn:hover { background: var(--color-primary-hover); }
    .pin-btn.enter-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* ── Resultado ── */
    .result-card {
        border-radius: 1rem;
        padding: 1.5rem;
        animation: slideUp 0.3s ease;
        border: 2px solid transparent;
        margin-top: 1rem;
    }
    .result-card.estado-activa     { background: rgba(34,197,94,0.07);  border-color: rgba(34,197,94,0.3); }
    .result-card.estado-por_vencer { background: rgba(234,179,8,0.07);  border-color: rgba(234,179,8,0.3); }
    .result-card.estado-vencida    { background: rgba(239,68,68,0.07);  border-color: rgba(239,68,68,0.3); }
    .result-card.estado-error      { background: rgba(239,68,68,0.07);  border-color: rgba(239,68,68,0.25); }

    .socio-avatar {
        width: 4rem; height: 4rem; border-radius: 50%;
        object-fit: cover; border: 2px solid #333; flex-shrink: 0;
    }
    .socio-avatar-placeholder {
        width: 4rem; height: 4rem; border-radius: 50%;
        background: var(--color-surface-hover);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; font-weight: 700;
        color: var(--color-text-muted); flex-shrink: 0;
        font-family: var(--font-heading);
    }

    /* ── Lockout ── */
    .lockout-banner {
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        display: none;
        animation: slideUp 0.3s ease;
    }
    .lockout-banner.show { display: block; }

    /* ── Overlay ── */
    .confirmed-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.88); z-index: 100;
        flex-direction: column; align-items: center; justify-content: center; gap: 1rem;
    }
    .confirmed-overlay.show { display: flex; animation: fadeIn 0.2s ease; }
    .confirmed-check {
        width: 5rem; height: 5rem; background: var(--color-success); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 40px rgba(34,197,94,0.5);
    }

    .method-tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid #262626; padding-bottom:0; }
    .method-tab { padding:0.5rem 1rem; font-size:0.875rem; font-weight:600; color:var(--color-text-muted); text-decoration:none; border-bottom:2px solid transparent; transition:all 0.2s; display:flex; align-items:center; gap:0.4rem; }
    .method-tab:hover { color:white; }
    .method-tab.active { color:var(--color-primary); border-bottom-color:var(--color-primary); }

    @keyframes slideUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
    @keyframes shake   { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-6px)} 40%,80%{transform:translateX(6px)} }
    @keyframes spin    { to{transform:rotate(360deg)} }
</style>

<div class="pinpad-wrapper">
    <div style="margin-bottom:1.5rem;">
        <h1 class="text-h2" style="margin-bottom:0.25rem;">Control de Accesos</h1>
        <p class="text-muted">El socio ingresa su PIN de 4 dígitos</p>
    </div>

    <!-- Tabs -->
    <nav class="method-tabs">
        <a href="por_dni.php"   class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
            Por DNI
        </a>
        <a href="por_clave.php" class="method-tab active">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Por PIN
        </a>
        <a href="por_qr.php"    class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Por QR
        </a>
    </nav>

    <!-- Card PIN -->
    <div class="card">
        <p style="text-align:center; font-size:0.8rem; font-weight:600; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem;">Ingresa tu clave secreta</p>

        <!-- Indicadores de dígitos -->
        <div class="pin-display" id="pinDisplay">
            <div class="pin-dot" id="dot0"></div>
            <div class="pin-dot" id="dot1"></div>
            <div class="pin-dot" id="dot2"></div>
            <div class="pin-dot" id="dot3"></div>
        </div>

        <!-- Bloqueo por intentos -->
        <div class="lockout-banner" id="lockoutBanner">
            <p style="color:#ef4444; font-weight:700; font-size:0.95rem; margin-bottom:0.25rem;">Sistema bloqueado temporalmente</p>
            <p id="lockoutTimer" style="font-size:2rem; font-weight:800; font-family:var(--font-heading); color:#ef4444; margin-bottom:0.25rem;">2:00</p>
            <p style="font-size:0.8rem; color:var(--color-text-muted);">Demasiados intentos fallidos</p>
        </div>

        <!-- Teclado numérico -->
        <div id="pinpadGrid">
            <div class="pinpad-grid">
                <button class="pin-btn" onclick="appendDigit('1')">1</button>
                <button class="pin-btn" onclick="appendDigit('2')">2<span class="sub-letters">ABC</span></button>
                <button class="pin-btn" onclick="appendDigit('3')">3<span class="sub-letters">DEF</span></button>
                <button class="pin-btn" onclick="appendDigit('4')">4<span class="sub-letters">GHI</span></button>
                <button class="pin-btn" onclick="appendDigit('5')">5<span class="sub-letters">JKL</span></button>
                <button class="pin-btn" onclick="appendDigit('6')">6<span class="sub-letters">MNO</span></button>
                <button class="pin-btn" onclick="appendDigit('7')">7<span class="sub-letters">PQRS</span></button>
                <button class="pin-btn" onclick="appendDigit('8')">8<span class="sub-letters">TUV</span></button>
                <button class="pin-btn" onclick="appendDigit('9')">9<span class="sub-letters">WXYZ</span></button>
                <!-- 0, Del, Enter -->
                <button class="pin-btn del-btn" onclick="deleteDigit()">
                    <svg style="width:1.6rem;height:1.6rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/></svg>
                </button>
                <button class="pin-btn" onclick="appendDigit('0')">0</button>
                <button class="pin-btn enter-btn" id="enterBtn" onclick="validarPin()" disabled>
                    <svg style="width:1.6rem;height:1.6rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Resultado de validación -->
    <div id="resultadoPin"></div>
</div>

<!-- Overlay de confirmación -->
<div class="confirmed-overlay" id="confirmedOverlay">
    <div class="confirmed-check">
        <svg style="width:2.5rem;height:2.5rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p style="font-size:1.75rem; font-weight:700; font-family:var(--font-heading); color:white;" id="confirmedNombre"></p>
    <p style="color:var(--color-success); font-weight:600; font-size:1.1rem;">¡Entrada Registrada!</p>
    <p style="color:var(--color-text-muted); font-size:0.875rem;" id="confirmedHora"></p>
</div>

<script>
(function() {
    let pin = '';
    let lockoutActive = false;
    let lockoutInterval = null;
    let socioActual = null;

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

        fetch(BASE_URL . 'ajax/validar_clave.php', {
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
        fetch(BASE_URL . 'ajax/registrar_acceso.php', {
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
