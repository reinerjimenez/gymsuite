<?php
// modules/accesos/por_dni.php — Control de acceso por DNI
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
    .acceso-search-bar {
        background: var(--color-surface);
        border: 2px solid #333;
        border-radius: 0.75rem;
        font-size: 1.75rem;
        font-family: var(--font-heading);
        padding: 1rem 1.25rem;
        color: white;
        width: 100%;
        text-align: center;
        letter-spacing: 0.1em;
        transition: border-color 0.2s;
        min-height: 72px;
    }
    .acceso-search-bar:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(249,115,22,0.15);
    }
    .socio-card {
        border-radius: 1rem;
        padding: 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        animation: fadeInUp 0.3s ease;
        border: 2px solid transparent;
    }
    .socio-card.estado-activa    { background: rgba(34,197,94,0.07);  border-color: rgba(34,197,94,0.3); }
    .socio-card.estado-por_vencer{ background: rgba(234,179,8,0.07);  border-color: rgba(234,179,8,0.3); }
    .socio-card.estado-vencida   { background: rgba(239,68,68,0.07);  border-color: rgba(239,68,68,0.3); }
    .socio-card.estado-sin_membresia { background: rgba(163,163,163,0.07); border-color: rgba(163,163,163,0.2); }
    .socio-avatar {
        width: 5rem;
        height: 5rem;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #333;
        flex-shrink: 0;
    }
    .socio-avatar-placeholder {
        width: 5rem;
        height: 5rem;
        border-radius: 50%;
        background: var(--color-surface-hover);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--color-text-muted);
        flex-shrink: 0;
        font-family: var(--font-heading);
    }
    .estado-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .estado-indicator.activa     { background: rgba(34,197,94,0.15); color: #22c55e; }
    .estado-indicator.por_vencer { background: rgba(234,179,8,0.15);  color: #eab308; }
    .estado-indicator.vencida    { background: rgba(239,68,68,0.15);  color: #ef4444; }
    .estado-indicator.sin_membresia { background: rgba(163,163,163,0.1); color: #a3a3a3; }
    .btn-registrar-entrada {
        background: var(--color-primary);
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        border: none;
        cursor: pointer;
        width: 100%;
        min-height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.2s;
        font-family: var(--font-body);
    }
    .btn-registrar-entrada:hover:not(:disabled) {
        background: var(--color-primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(249,115,22,0.3);
    }
    .btn-registrar-entrada:disabled { opacity: 0.5; cursor: not-allowed; }
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--color-text-muted);
    }
    .empty-state svg { width: 3.5rem; height: 3.5rem; margin: 0 auto 1rem; opacity: 0.3; }
    .confirmed-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.85);
        z-index: 100;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }
    .confirmed-overlay.show { display: flex; animation: fadeIn 0.2s ease; }
    .confirmed-check {
        width: 5rem;
        height: 5rem;
        background: var(--color-success);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 40px rgba(34,197,94,0.5);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; } to { opacity: 1; }
    }
    .method-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #262626;
        padding-bottom: 0;
    }
    .method-tab {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-text-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .method-tab:hover { color: white; }
    .method-tab.active { color: var(--color-primary); border-bottom-color: var(--color-primary); }
</style>

<div style="max-width: 680px; margin: 0 auto;">
    <!-- Header -->
    <div class="flex items-center justify-between" style="margin-bottom: 1.5rem;">
        <div>
            <h1 class="text-h2" style="margin-bottom: 0.25rem;">Control de Accesos</h1>
            <p class="text-muted">Busca al socio por su número de DNI</p>
        </div>
        <?php if($_SESSION['rol'] === 'admin'): ?>
        <a href="/gymsuite/modules/configuracion/index.php" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem; min-height: 38px;">
            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Configurar
        </a>
        <?php endif; ?>
    </div>

    <!-- Tabs de método -->
    <nav class="method-tabs">
        <a href="por_dni.php" class="method-tab active">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
            Por DNI
        </a>
        <a href="por_clave.php" class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Por PIN
        </a>
        <a href="por_qr.php" class="method-tab">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Por QR
        </a>
    </nav>

    <!-- Buscador -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom: 0.75rem;">Número de DNI / Documento</label>
        <input type="text" id="dniInput" class="acceso-search-bar" placeholder="Escribe el DNI..." maxlength="15" autocomplete="off" autofocus>
        <p class="text-muted" style="font-size:0.8rem; margin-top:0.5rem; text-align:center;">La búsqueda es automática al escribir</p>
    </div>

    <!-- Resultado -->
    <div id="resultado">
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <p style="font-size:1.1rem; font-weight:600; margin-bottom:0.25rem;">Esperando búsqueda</p>
            <p style="font-size:0.875rem;">Escribe el DNI del socio para ver su estado</p>
        </div>
    </div>
</div>

<!-- Overlay de confirmación de entrada -->
<div class="confirmed-overlay" id="confirmedOverlay">
    <div class="confirmed-check">
        <svg style="width:2.5rem;height:2.5rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p style="font-size:1.75rem; font-weight:700; font-family:var(--font-heading); color:white;" id="confirmedNombre"></p>
    <p style="color:var(--color-success); font-weight:600; font-size:1.1rem;">¡Entrada Registrada!</p>
    <p style="color: var(--color-text-muted); font-size:0.875rem;" id="confirmedHora"></p>
</div>

<script>
(function() {
    const dniInput    = document.getElementById('dniInput');
    const resultado   = document.getElementById('resultado');
    const overlay     = document.getElementById('confirmedOverlay');
    let   debounceTimer;
    let   socioActual = null;

    // --- Beep de confirmación vía Web Audio API ---
    function beepOK() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(); osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}
    }

    function getAvatarHTML(socio) {
        if (socio.foto) {
            return `<img src="/gymsuite/uploads/fotos/${socio.foto}" alt="${socio.nombre}" class="socio-avatar">`;
        }
        const inicial = (socio.nombre || '?').charAt(0).toUpperCase();
        return `<div class="socio-avatar-placeholder">${inicial}</div>`;
    }

    function getEstadoLabel(estado, dias) {
        const labels = {
            'activa':        { text: 'Membresía Activa',    cls: 'activa',        dot: '#22c55e' },
            'por_vencer':    { text: 'Por Vencer',          cls: 'por_vencer',    dot: '#eab308' },
            'vencida':       { text: 'Membresía Vencida',   cls: 'vencida',       dot: '#ef4444' },
            'sin_membresia': { text: 'Sin Membresía',       cls: 'sin_membresia', dot: '#a3a3a3' },
        };
        const info = labels[estado] || labels['sin_membresia'];
        return `<span class="estado-indicator ${info.cls}">
            <span style="width:7px;height:7px;border-radius:50%;background:${info.dot};display:inline-block;"></span>
            ${info.text}
        </span>`;
    }

    function renderSocio(data) {
        socioActual = data;
        const esActiva    = data.estado === 'activa' || data.estado === 'por_vencer';
        const diasTexto   = data.dias_restantes !== null
            ? (data.dias_restantes >= 0 ? `${data.dias_restantes} días restantes` : `Venció hace ${Math.abs(data.dias_restantes)} días`)
            : '';

        resultado.innerHTML = `
        <div class="socio-card estado-${data.estado}">
            <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                ${getAvatarHTML(data)}
                <div style="flex:1; min-width:0;">
                    <h2 style="font-size:1.4rem; font-weight:700; margin-bottom:0.25rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${data.nombre}</h2>
                    <p style="color:var(--color-text-muted); font-size:0.9rem; margin-bottom:0.5rem;">DNI: ${data.dni}</p>
                    ${getEstadoLabel(data.estado, data.dias_restantes)}
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; background:rgba(0,0,0,0.25); border-radius:0.5rem; padding:1rem;">
                <div>
                    <p style="font-size:0.75rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; font-weight:600; margin-bottom:0.2rem;">Plan activo</p>
                    <p style="font-weight:600; color:white;">${data.plan || '—'}</p>
                </div>
                <div>
                    <p style="font-size:0.75rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; font-weight:600; margin-bottom:0.2rem;">Vencimiento</p>
                    <p style="font-weight:600; color:white;">${data.fecha_fin || '—'}</p>
                </div>
                ${diasTexto ? `<div style="grid-column:1/-1;">
                    <p style="font-size:0.75rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; font-weight:600; margin-bottom:0.2rem;">Estado</p>
                    <p style="font-weight:600; color:white;">${diasTexto}</p>
                </div>` : ''}
            </div>

            ${esActiva ? `
            <button class="btn-registrar-entrada" id="btnRegistrar" onclick="registrarEntrada()">
                <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Registrar Entrada
            </button>` : `
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:0.75rem; padding:1rem; text-align:center;">
                <p style="color:#ef4444; font-weight:700; margin-bottom:0.25rem;">Acceso denegado</p>
                <p style="color:var(--color-text-muted); font-size:0.875rem;">La membresía de este socio ha vencido.</p>
                <a href="/gymsuite/modules/membresias/renovar.php?socio_id=${data.id}" class="btn btn-primary" style="margin-top:0.75rem; font-size:0.875rem; min-height:42px; padding:0.6rem 1.25rem; display:inline-flex;">
                    Renovar Membresía
                </a>
            </div>`}
        </div>`;
    }

    function renderNoEncontrado(msg) {
        socioActual = null;
        resultado.innerHTML = `
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <p style="font-size:1.1rem; font-weight:600; margin-bottom:0.25rem;">${msg || 'Socio no encontrado'}</p>
            <p style="font-size:0.875rem;">Verifica el número de DNI o regístralo como nuevo socio.</p>
            <a href="/gymsuite/modules/socios/crear.php" class="btn btn-secondary" style="margin-top:1rem; display:inline-flex; font-size:0.875rem; min-height:40px; padding:0.5rem 1.1rem;">
                + Registrar nuevo socio
            </a>
        </div>`;
    }

    function buscarSocio(dni) {
        if (dni.length < 3) {
            resultado.innerHTML = `
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p style="font-size:1.1rem; font-weight:600; margin-bottom:0.25rem;">Esperando búsqueda</p>
                <p style="font-size:0.875rem;">Escribe al menos 3 caracteres del DNI</p>
            </div>`;
            return;
        }
        resultado.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--color-text-muted);">
            <div style="width:2rem;height:2rem;border:3px solid var(--color-primary);border-top-color:transparent;border-radius:50%;animation:spin 0.7s linear infinite;margin:0 auto 1rem;"></div>
            Buscando...
        </div>
        <style>@keyframes spin{to{transform:rotate(360deg)}}</style>`;

        fetch(`/gymsuite/ajax/buscar_socio.php?dni=${encodeURIComponent(dni)}`)
            .then(r => r.json())
            .then(data => {
                if (data.encontrado && !data.bloqueado) {
                    renderSocio(data);
                } else {
                    renderNoEncontrado(data.mensaje);
                }
            })
            .catch(() => renderNoEncontrado('Error de conexión'));
    }

    dniInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => buscarSocio(this.value.trim()), 400);
    });

    window.registrarEntrada = function() {
        if (!socioActual) return;
        const btn = document.getElementById('btnRegistrar');
        btn.disabled = true;
        btn.innerHTML = `<span style="width:1.2rem;height:1.2rem;border:2px solid white;border-top-color:transparent;border-radius:50%;animation:spin 0.7s linear infinite;display:inline-block;"></span> Registrando...`;

        fetch('/gymsuite/ajax/registrar_acceso.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `socio_id=${socioActual.id}&metodo=dni`
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                beepOK();
                document.getElementById('confirmedNombre').textContent = socioActual.nombre;
                document.getElementById('confirmedHora').textContent = new Date().toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
                overlay.classList.add('show');
                setTimeout(() => {
                    overlay.classList.remove('show');
                    dniInput.value = '';
                    resultado.innerHTML = `<div class="empty-state"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><p style="font-size:1.1rem;font-weight:600;margin-bottom:0.25rem;">Esperando búsqueda</p><p style="font-size:0.875rem;">Escribe el DNI del socio para ver su estado</p></div>`;
                    socioActual = null;
                    dniInput.focus();
                }, 3000);
            } else {
                btn.disabled = false;
                btn.innerHTML = `<svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Registrar Entrada`;
                alert(data.mensaje || 'Error al registrar');
            }
        });
    };
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
