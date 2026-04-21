<?php
// modules/socio/dashboard.php — Vista personal del socio
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['socio']);

// Los socios tienen su id en sesión como 'socio_id'
$socio_id    = $_SESSION['socio_id'] ?? 0;
$gimnasio_id = $_SESSION['gimnasio_id'] ?? 0;

if (!$socio_id || !$gimnasio_id) { header("Location: /gymsuite/index.php"); exit; }

// Datos del socio
$stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();
if (!$socio) { header("Location: /gymsuite/logout.php"); exit; }

// Membresía activa (última)
$stmtM = $pdo->prepare("
    SELECT m.*, p.nombre as plan_nombre, p.duracion_dias
    FROM membresias m JOIN planes p ON p.id = m.plan_id
    WHERE m.socio_id = ? AND m.gimnasio_id = ?
    ORDER BY m.fecha_fin DESC LIMIT 1
");
$stmtM->execute([$socio_id, $gimnasio_id]);
$membresia = $stmtM->fetch();

// Calcular estado
$estado_mem = 'sin_membresia'; $dias_rest = null; $progreso = 0;
if ($membresia) {
    $dias_rest  = dias_restantes($membresia['fecha_fin']);
    $estado_mem = calcular_estado_membresia($membresia['fecha_fin']);
    // Progreso de días usados sobre duración total del plan
    $duracion   = max(1, (int)$membresia['duracion_dias']);
    $dias_usados = max(0, $duracion - max(0, $dias_rest));
    $progreso   = round(($dias_usados / $duracion) * 100);
}

// Últimos 5 accesos
$stmtA = $pdo->prepare("SELECT * FROM accesos WHERE socio_id=? AND gimnasio_id=? ORDER BY fecha_hora DESC LIMIT 5");
$stmtA->execute([$socio_id, $gimnasio_id]);
$accesos = $stmtA->fetchAll();

// Rutina asignada
$stmtR = $pdo->prepare("
    SELECT sr.rutina_id, r.nombre as rutina_nombre, r.objetivo, sr.fecha_asignacion
    FROM socio_rutina sr JOIN rutinas r ON r.id = sr.rutina_id
    WHERE sr.socio_id = ? AND sr.activa = 1 ORDER BY sr.id DESC LIMIT 1
");
$stmtR->execute([$socio_id]);
$rutina = $stmtR->fetch();

$estados_info = [
    'activa'        => ['color' => '#22c55e',  'bg' => 'rgba(34,197,94,0.1)',  'texto' => 'Membresía Activa'],
    'por_vencer'    => ['color' => '#eab308',  'bg' => 'rgba(234,179,8,0.1)', 'texto' => 'Por Vencer Pronto'],
    'vencida'       => ['color' => '#ef4444',  'bg' => 'rgba(239,68,68,0.1)', 'texto' => 'Membresía Vencida'],
    'sin_membresia' => ['color' => '#a3a3a3',  'bg' => 'rgba(163,163,163,0.08)', 'texto' => 'Sin Membresía'],
];
$info = $estados_info[$estado_mem];
$objetivos_labels = ['fuerza'=>'Fuerza','cardio'=>'Cardio','perdida_peso'=>'Pérdida de Peso','tonificacion'=>'Tonificación','otro'=>'General'];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    .socio-dash { max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; }

    /* Tarjeta de estado grande */
    .estado-hero {
        border-radius: 1rem;
        padding: 2rem;
        border: 2px solid;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .estado-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 0%, var(--hero-color-fade), transparent 70%);
        pointer-events: none;
    }
    .progress-bar-track {
        width: 100%;
        height: 8px;
        background: rgba(255,255,255,0.1);
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 1rem;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.6s ease;
    }

    /* Sección QR */
    .qr-section { text-align: center; }
    #qr_socio { display: inline-block; background: white; padding: 0.875rem; border-radius: 0.75rem; }

    /* Accesos mini-list */
    .acceso-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.75rem 0; border-bottom: 1px solid #1e1e1e;
        font-size: 0.875rem;
    }
    .acceso-row:last-child { border-bottom: none; }
    .metodo-tag {
        font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem;
        border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.04em;
        background: rgba(249,115,22,0.1); color: var(--color-primary);
        border: 1px solid rgba(249,115,22,0.2);
    }
</style>

<div class="socio-dash">
    <!-- Saludo -->
    <div>
        <p style="color:var(--color-text-muted);font-size:0.875rem; margin-bottom:0.2rem;">Bienvenido de vuelta</p>
        <h1 style="font-size:1.5rem;font-weight:800;font-family:var(--font-heading);"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></h1>
    </div>

    <!-- Estado de membresía (hero) -->
    <div class="estado-hero" style="background:<?= $info['bg'] ?>;border-color:<?= $info['color'] ?>30;--hero-color-fade:<?= $info['color'] ?>22;">
        <div style="width:4rem;height:4rem;border-radius:50%;background:<?= $info['bg'] ?>;border:2px solid <?= $info['color'] ?>40;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg style="width:1.75rem;height:1.75rem;color:<?= $info['color'] ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:<?= $info['color'] ?>;margin-bottom:0.5rem;"><?= $info['texto'] ?></p>

        <?php if ($membresia): ?>
        <p style="font-size:1.6rem;font-weight:800;font-family:var(--font-heading);color:white;margin-bottom:0.25rem;"><?= h($membresia['plan_nombre']) ?></p>
        <p style="color:var(--color-text-muted);font-size:0.875rem;">
            Vence: <?= formatear_fecha($membresia['fecha_fin']) ?>
            <?php if ($dias_rest !== null): ?>
            &nbsp;·&nbsp;
            <strong style="color:<?= $info['color'] ?>;"><?= abs($dias_rest) ?> días <?= $dias_rest >= 0 ? 'restantes' : 'vencida' ?></strong>
            <?php endif; ?>
        </p>

        <?php if ($estado_mem !== 'vencida'): ?>
        <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width:<?= min(100, $progreso) ?>%;background:<?= $info['color'] ?>;"></div>
        </div>
        <p style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.5rem;"><?= $progreso ?>% completado de tu plan</p>
        <?php endif; ?>

        <?php else: ?>
        <p style="color:var(--color-text-muted);font-size:0.875rem;">No tienes ninguna membresía registrada.</p>
        <p style="font-size:0.8rem;color:var(--color-text-muted);margin-top:0.5rem;">Acércate a recepción para activar tu plan.</p>
        <?php endif; ?>
    </div>

    <!-- Rutina Asignada -->
    <?php if ($rutina): ?>
    <div class="card" style="display:flex;align-items:center;gap:1rem;">
        <div style="width:3rem;height:3rem;border-radius:0.75rem;background:rgba(249,115,22,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg style="width:1.4rem;height:1.4rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>
        <div style="flex:1;min-width:0;">
            <p style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);font-weight:700;margin-bottom:0.15rem;">Mi Rutina</p>
            <p style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($rutina['rutina_nombre']) ?></p>
            <p style="font-size:0.75rem;color:var(--color-text-muted);"><?= h($objetivos_labels[$rutina['objetivo']] ?? 'General') ?></p>
        </div>
        <a href="/gymsuite/modules/rutinas/imprimir.php?id=<?= $rutina['rutina_id'] ?>&socio_id=<?= $socio_id ?>" target="_blank" class="btn btn-secondary" style="font-size:0.8rem;min-height:38px;padding:0.5rem 0.875rem;white-space:nowrap;flex-shrink:0;">
            Ver Rutina
        </a>
    </div>
    <?php endif; ?>

    <!-- Últimos ingresos -->
    <div class="card">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem;">Mis Últimos Ingresos</h3>
        <?php if (empty($accesos)): ?>
        <p style="color:var(--color-text-muted);font-size:0.875rem;text-align:center;padding:0.75rem;">Sin registros de ingresos aún.</p>
        <?php else: ?>
        <?php
        $metodos_label = ['dni'=>'DNI','clave'=>'PIN','qr'=>'QR','manual'=>'Manual'];
        foreach ($accesos as $a):
        ?>
        <div class="acceso-row">
            <div>
                <p style="font-weight:600;margin-bottom:0.1rem;"><?= date('d/m/Y', strtotime($a['fecha_hora'])) ?></p>
                <p style="font-size:0.775rem;color:var(--color-text-muted);"><?= date('H:i', strtotime($a['fecha_hora'])) ?></p>
            </div>
            <span class="metodo-tag"><?= $metodos_label[$a['metodo_usado']] ?? $a['metodo_usado'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- QR Personal -->
    <div class="card qr-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.25rem;">Mi Código QR</h3>
        <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:1rem;">Muéstralo en recepción para registrar tu entrada rápidamente.</p>
        <div id="qr_socio"></div>
        <p style="font-size:0.7rem;color:var(--color-text-muted);margin-top:0.75rem;">Token: <?= h(substr($socio['token_qr'], 0, 8)) ?>...</p>
        <button onclick="window.print()" class="btn btn-secondary" style="margin-top:1rem;font-size:0.875rem;min-height:42px;">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir QR
        </button>
    </div>
</div>

<style>
@media print {
    .sidebar, .header, .btn, .acceso-row, .estado-hero p:not(:first-of-type) { display: none !important; }
    #qr_socio { margin: 3rem auto; display: block !important; }
    .socio-dash > *:not(:has(#qr_socio)) { display: none !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = "<?= h($socio['token_qr']) ?>";
    if (token && document.getElementById('qr_socio')) {
        new QRCode(document.getElementById('qr_socio'), {
            text: token, width: 200, height: 200,
            colorDark: "#000000", colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
