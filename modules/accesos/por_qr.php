<?php
// modules/accesos/por_qr.php — Control de acceso por código QR via cámara
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
    .qr-wrapper { max-width: 520px; margin: 0 auto; }
    .method-tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid #262626; }
    .method-tab  { padding:0.5rem 1rem; font-size:0.875rem; font-weight:600; color:var(--color-text-muted); text-decoration:none; border-bottom:2px solid transparent; transition:all 0.2s; display:flex; align-items:center; gap:0.4rem; }
    .method-tab:hover  { color:white; }
    .method-tab.active { color:var(--color-primary); border-bottom-color:var(--color-primary); }

    /* Cámara */
    .camera-container {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        max-height: 380px;
        background: #000;
        border-radius: 1rem;
        overflow: hidden;
        border: 2px solid #333;
    }
    #qrVideo {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    /* Marco de escaneo animado */
    .scan-frame {
        position: absolute;
        inset: 15%;
        border-radius: 0.5rem;
        pointer-events: none;
    }
    .scan-corner {
        position: absolute;
        width: 2.2rem; height: 2.2rem;
        border-color: var(--color-primary);
        border-style: solid;
    }
    .scan-corner.tl { top:0; left:0;  border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
    .scan-corner.tr { top:0; right:0; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
    .scan-corner.bl { bottom:0; left:0;  border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }
    .scan-corner.br { bottom:0; right:0; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
    .scan-line {
        position: absolute;
        left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--color-primary), transparent);
        animation: scanMove 2s ease-in-out infinite;
        opacity: 0.8;
    }
    @keyframes scanMove {
        0%   { top: 5%; }
        50%  { top: 90%; }
        100% { top: 5%; }
    }

    /* Estado overlay sobre cámara */
    .camera-status {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.75);
        text-align: center;
        padding: 1rem;
    }
    .camera-status.hidden { display: none; }

    /* Resultado del socio */
    .result-card {
        border-radius: 1rem; padding: 1.25rem;
        animation: fadeUp 0.3s ease; border: 2px solid transparent; margin-top: 1rem;
    }
    .result-card.estado-activa      { background: rgba(34,197,94,0.07);  border-color: rgba(34,197,94,0.3); }
    .result-card.estado-por_vencer  { background: rgba(234,179,8,0.07);  border-color: rgba(234,179,8,0.3); }
    .result-card.estado-vencida     { background: rgba(239,68,68,0.07);  border-color: rgba(239,68,68,0.3); }
    .result-card.estado-error       { background: rgba(239,68,68,0.07);  border-color: rgba(239,68,68,0.25); }

    .socio-avatar           { width:4rem;height:4rem;border-radius:50%;object-fit:cover;border:2px solid #333;flex-shrink:0; }
    .socio-avatar-placeholder {
        width:4rem;height:4rem;border-radius:50%;background:var(--color-surface-hover);
        display:flex;align-items:center;justify-content:center;
        font-size:1.4rem;font-weight:700;color:var(--color-text-muted);flex-shrink:0;
    }

    /* Overlay confirmación */
    .confirmed-overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88);
        z-index:100; flex-direction:column; align-items:center; justify-content:center; gap:1rem;
    }
    .confirmed-overlay.show { display:flex; animation:fadeIn 0.2s ease; }
    .confirmed-check {
        width:5rem;height:5rem;background:var(--color-success);border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 0 40px rgba(34,197,94,0.5);
    }
    @keyframes fadeUp  { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
    @keyframes spin    { to{transform:rotate(360deg)} }
</style>

<!-- jsQR via CDN -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<div class="qr-wrapper">
    <div style="margin-bottom:1.5rem;">
        <h1 class="text-h2" style="margin-bottom:0.25rem;">Control de Accesos</h1>
        <p class="text-muted">Escaneo automático de código QR</p>
    </div>

    <!-- Tabs -->
    <nav class="method-tabs">
        <a href="por_dni.php"   class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
            Por DNI
        </a>
        <a href="por_clave.php" class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Por PIN
        </a>
        <a href="por_qr.php"    class="method-tab active">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Por QR
        </a>
    </nav>

    <!-- Visor de cámara -->
    <div class="card" style="padding:1rem;">
        <div class="camera-container" id="cameraContainer">
            <video id="qrVideo" playsinline autoplay muted></video>
            <canvas id="qrCanvas" style="display:none;"></canvas>

            <!-- Marco de escaneo -->
            <div class="scan-frame" id="scanFrame" style="display:none;">
                <div class="scan-corner tl"></div>
                <div class="scan-corner tr"></div>
                <div class="scan-corner bl"></div>
                <div class="scan-corner br"></div>
                <div class="scan-line"></div>
            </div>

            <!-- Status overlay -->
            <div class="camera-status" id="cameraStatus">
                <div style="width:3rem;height:3rem;border:3px solid var(--color-primary);border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;margin-bottom:1rem;"></div>
                <p style="color:white;font-weight:600;margin-bottom:0.25rem;">Iniciando cámara...</p>
                <p style="font-size:0.8rem;color:var(--color-text-muted);">Por favor permite el acceso a la cámara</p>
            </div>
        </div>

        <p style="text-align:center;font-size:0.8rem;color:var(--color-text-muted);margin-top:0.75rem;" id="instruccion">
            Apunta la cámara hacia el código QR del socio
        </p>

        <!-- Botón de reinicio -->
        <button onclick="reiniciarEscaneo()" id="btnReiniciar" class="btn btn-secondary" style="width:100%;margin-top:0.75rem;display:none;">
            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Escanear otro QR
        </button>
    </div>

    <!-- Resultado del socio -->
    <div id="resultadoQR"></div>
</div>

<!-- Overlay de confirmación -->
<div class="confirmed-overlay" id="confirmedOverlay">
    <div class="confirmed-check">
        <svg style="width:2.5rem;height:2.5rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p style="font-size:1.75rem;font-weight:700;font-family:var(--font-heading);color:white;" id="confirmedNombre"></p>
    <p style="color:var(--color-success);font-weight:600;font-size:1.1rem;">¡Entrada Registrada!</p>
    <p style="color:var(--color-text-muted);font-size:0.875rem;" id="confirmedHora"></p>
</div>

<script>
(function() {
    const video      = document.getElementById('qrVideo');
    const canvas     = document.getElementById('qrCanvas');
    const ctx        = canvas.getContext('2d');
    const statusDiv  = document.getElementById('cameraStatus');
    const scanFrame  = document.getElementById('scanFrame');
    const instruccion = document.getElementById('instruccion');
    const btnRein    = document.getElementById('btnReiniciar');

    let scanning     = false;
    let procesando   = false;
    let animFrameId  = null;

    function beepOK() {
        try {
            const ac = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ac.createOscillator(); const gain = ac.createGain();
            osc.connect(gain); gain.connect(ac.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ac.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + 0.4);
            osc.start(); osc.stop(ac.currentTime + 0.4);
        } catch(e) {}
    }


    async function iniciarCamara() {
        try {
            // El uso de 'ideal' permite que si es laptop (solo tiene frontal), la use inmediatamente sin colgarse.
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: { ideal: 'environment' } } 
            });

            video.srcObject = stream;
            video.setAttribute("playsinline", true);

            // 3. Esperar a que el video cargue los metadatos antes de reproducir
            video.onloadedmetadata = async () => {
                try {
                    await video.play();
                    statusDiv.classList.add('hidden');
                    scanFrame.style.display = 'block';
                    scanning = true;
                    requestAnimationFrame(tick);
                } catch (playErr) {
                    mostrarErrorCamara("No se pudo iniciar el video: " + playErr.message);
                }
            };
        } catch (err) {
            console.error("Error getUserMedia:", err);
            mostrarErrorCamara("Cámara no disponible o bloqueada. Revisa los permisos.");
        }
    }

    function mostrarErrorCamara(msg) {
        statusDiv.innerHTML = `
        <svg style="width:3rem;height:3rem;color:#ef4444;margin-bottom:0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <p style="color:#ef4444;font-weight:700;margin-bottom:0.25rem;">Error de Cámara</p>
        <p style="font-size:0.8rem;color:var(--color-text-muted);">${msg}</p>`;
    }

    iniciarCamara();

    function tick() {
        if (!scanning) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width  = video.videoWidth;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code && code.data && !procesando) {
                procesarToken(code.data);
            }
        }
        animFrameId = requestAnimationFrame(tick);
    }

    function procesarToken(token) {
        procesando = true;
        scanning   = false;
        cancelAnimationFrame(animFrameId);
        instruccion.textContent = 'Validando código...';
        scanFrame.style.display = 'none';

        fetch(BASE_URL . 'ajax/validar_qr.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `token=${encodeURIComponent(token)}`
        })
        .then(r => r.json())
        .then(data => {
            if (!data.exito) {
                mostrarError(data.mensaje || 'QR no válido');
                return;
            }
            // Registrar acceso automáticamente si membresía activa
            const esActiva = data.estado === 'activa' || data.estado === 'por_vencer';
            if (esActiva) {
                return fetch(BASE_URL . 'ajax/registrar_acceso.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `socio_id=${data.id}&metodo=qr`
                })
                .then(r => r.json())
                .then(res => {
                    if (res.exito) {
                        beepOK();
                        document.getElementById('confirmedNombre').textContent = data.nombre;
                        document.getElementById('confirmedHora').textContent = new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'});
                        document.getElementById('confirmedOverlay').classList.add('show');
                        setTimeout(() => {
                            document.getElementById('confirmedOverlay').classList.remove('show');
                            reiniciarEscaneo();
                        }, 3000);
                    }
                });
            } else {
                // Membresía vencida — mostrar card y botón renovar
                mostrarVencido(data);
            }
        })
        .catch(() => mostrarError('Error de conexión'))
        .finally(() => { btnRein.style.display = ''; });
    }

    function mostrarError(msg) {
        document.getElementById('resultadoQR').innerHTML = `
        <div class="result-card estado-error">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:3rem;height:3rem;border-radius:50%;background:rgba(239,68,68,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg style="width:1.5rem;height:1.5rem;color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p style="font-weight:700;color:#ef4444;margin-bottom:0.15rem;">QR No Válido</p>
                    <p style="font-size:0.875rem;color:var(--color-text-muted);">${msg}</p>
                </div>
            </div>
        </div>`;
        instruccion.textContent = 'QR no reconocido';
        btnRein.style.display = '';
    }

    function mostrarVencido(data) {
        const avatarHTML = data.foto
            ? `<img src="<?= BASE_URL ?>uploads/fotos/${data.foto}" class="socio-avatar" alt="${data.nombre}">`
            : `<div class="socio-avatar-placeholder">${(data.nombre||'?').charAt(0).toUpperCase()}</div>`;

        document.getElementById('resultadoQR').innerHTML = `
        <div class="result-card estado-vencida">
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                ${avatarHTML}
                <div>
                    <p style="font-size:1.1rem;font-weight:700;margin-bottom:0.15rem;">${data.nombre}</p>
                    <p style="font-size:0.8rem;color:var(--color-text-muted);">DNI: ${data.dni}</p>
                </div>
            </div>
            <div style="text-align:center;background:rgba(239,68,68,0.1);border-radius:0.5rem;padding:0.75rem;">
                <p style="color:#ef4444;font-weight:700;margin-bottom:0.25rem;">Membresía Vencida</p>
                <a href="<?= BASE_URL ?>modules/membresias/renovar.php?socio_id=${data.id}" class="btn btn-primary" style="margin-top:0.5rem;font-size:0.875rem;min-height:40px;padding:0.5rem 1rem;display:inline-flex;">Renovar Membresía</a>
            </div>
        </div>`;
        instruccion.textContent = 'Membresía vencida';
    }

    window.reiniciarEscaneo = function() {
        document.getElementById('resultadoQR').innerHTML = '';
        instruccion.textContent = 'Apunta la cámara hacia el código QR del socio';
        btnRein.style.display = 'none';
        scanFrame.style.display = 'block';
        procesando = false;
        scanning   = true;
        tick();
    };
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
