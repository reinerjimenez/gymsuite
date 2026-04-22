<?php
// modules/rutinas/imprimir.php — Vista de impresión / PDF + enlace WhatsApp
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Sesión opcional: puede ser accedida por admin o con token temporal
// Para uso interno: admin lo imprime o genera enlace al socio
require_once __DIR__ . '/../../includes/functions.php';

// Sesión opcional: puede ser accedida por admin o con token temporal
// Para uso interno: admin lo imprime o genera enlace al socio
session_start();

$is_admin    = isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'recepcionista']);
$rutina_id   = (int)($_GET['id'] ?? 0);
$socio_id    = (int)($_GET['socio_id'] ?? 0); 
$tk          = $_GET['tk'] ?? '';

if (!$rutina_id) { header("Location: index.php"); exit; }

$hash_seguridad = substr(md5($rutina_id . 'gymsuite_rutina'), 0, 10);

if (!$is_admin) {
    if ($socio_id) {
        // Validar token del socio
        if (!$tk) die('Acceso denegado. Enlace inválido.');
        $stmtS = $pdo->prepare("SELECT id, gimnasio_id FROM socios WHERE id=? AND token_qr=?");
        $stmtS->execute([$socio_id, $tk]);
        $socio_auth = $stmtS->fetch();
        if (!$socio_auth) die('Acceso denegado. El token de seguridad no es válido.');
        $gimnasio_id = $socio_auth['gimnasio_id'];
    } else {
        // Validar hash público de la rutina general
        $hash = $_GET['hash'] ?? '';
        if ($hash !== $hash_seguridad) die('Acceso denegado. Enlace inválido.');
        $stmt_g = $pdo->prepare("SELECT gimnasio_id FROM rutinas WHERE id=?");
        $stmt_g->execute([$rutina_id]);
        $gimnasio_id = $stmt_g->fetchColumn();
        if (!$gimnasio_id) die('Rutina no encontrada.');
    }
} else {
    $gimnasio_id = isset($_SESSION['gimnasio_id']) ? $_SESSION['gimnasio_id'] : null;
}

// Generar URL pública para el QR
if ($socio_id && isset($socio['token_qr'])) {
    // Si la vemos después de cargar el socio, lo actualizaremos abajo. Aquí definimos la base.
    $url_publica = ""; 
} else {
    $url_publica = BASE_URL . "modules/rutinas/imprimir.php?id={$rutina_id}&hash={$hash_seguridad}";
}

// Verificar que la rutina pertenece al gym
$stmt = $pdo->prepare("SELECT r.*, g.nombre as gym_nombre FROM rutinas r JOIN gimnasios g ON g.id=r.gimnasio_id WHERE r.id=? AND r.gimnasio_id=?");
$stmt->execute([$rutina_id, $gimnasio_id]);
$rutina = $stmt->fetch();
if (!$rutina) die('Rutina no encontrada.');

// Cargar ejercicios
$stmtEj = $pdo->prepare("SELECT * FROM ejercicios WHERE rutina_id=? ORDER BY orden ASC, id ASC");
$stmtEj->execute([$rutina_id]);
$ejercicios = $stmtEj->fetchAll();

// Cargar datos del socio si se pasó socio_id
$socio = null;
$telefono_wa = null;
if ($socio_id) {
    $stmtS = $pdo->prepare("SELECT nombre, apellido, telefono FROM socios WHERE id=? AND gimnasio_id=?");
    $stmtS->execute([$socio_id, $gimnasio_id]);
    $socio = $stmtS->fetch();
    if ($socio && $socio['telefono']) {
        // Limpiar número: solo dígitos, agregar prefijo Perú si es 9 dígitos
        $tel = preg_replace('/\D/', '', $socio['telefono']);
        if (strlen($tel) === 9) $tel = '51' . $tel;
        $telefono_wa = $tel;
    }
}

$objetivos = ['fuerza'=>'Fuerza','cardio'=>'Cardio','perdida_peso'=>'Pérdida de Peso','tonificacion'=>'Tonificación','otro'=>'General'];
$fecha_hoy = formatear_fecha(date('Y-m-d'));

// Construir mensaje WhatsApp
$msg_wa = "Hola " . ($socio ? $socio['nombre'] : 'socio') . "! 👋\n\n";
$msg_wa .= "Aquí está tu *Rutina de Entrenamiento* de *{$rutina['gym_nombre']}*:\n\n";
$msg_wa .= "📋 *{$rutina['nombre']}*\n";
$msg_wa .= "🎯 Objetivo: " . ($objetivos[$rutina['objetivo']] ?? 'General') . "\n\n";
foreach ($ejercicios as $i => $ej) {
    $msg_wa .= ($i+1) . ". *{$ej['nombre']}*\n";
    $msg_wa .= "   Series: {$ej['series']} × {$ej['repeticiones']} reps | Descanso: {$ej['descanso_segundos']}s\n";
    if ($ej['notas']) $msg_wa .= "   _{$ej['notas']}_\n";
    $msg_wa .= "\n";
}
$msg_wa .= "💪 ¡Mucho éxito en tu entrenamiento!\n_— {$rutina['gym_nombre']}_";
$url_wa = $telefono_wa
    ? "https://wa.me/{$telefono_wa}?text=" . rawurlencode($msg_wa)
    : "https://wa.me/?text=" . rawurlencode($msg_wa);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($rutina['nombre']) ?> — <?= h($rutina['gym_nombre']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --orange: #F97316;
        --dark:   #0A0A0A;
        --mid:    #1A1A1A;
        --border: #2a2a2a;
        --muted:  #6b7280;
        --white:  #ffffff;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#f4f4f5; font-family:'Plus Jakarta Sans',sans-serif; color:#111; font-size:15px; }

    /* Barra de acciones — solo pantalla */
    .action-bar {
        background:var(--dark); border-bottom:1px solid var(--border);
        padding:0.75rem 1.5rem; display:flex; align-items:center;
        justify-content:space-between; gap:1rem; flex-wrap:wrap;
        position:sticky; top:0; z-index:10;
    }
    .action-bar h2 { font-family:'Outfit',sans-serif; color:white; font-size:1rem; font-weight:700; }
    .action-bar p  { font-size:0.75rem; color:#6b7280; }
    .btn-print {
        background:var(--orange); color:white; border:none; border-radius:0.5rem;
        padding:0.6rem 1.1rem; font-weight:700; font-size:0.875rem; cursor:pointer;
        display:inline-flex; align-items:center; gap:0.4rem; font-family:'Plus Jakarta Sans',sans-serif;
        text-decoration:none; min-height:40px; transition:background 0.2s;
    }
    .btn-print:hover { background:#ea580c; }
    .btn-wa {
        background:#22c55e; color:white; border:none; border-radius:0.5rem;
        padding:0.6rem 1.1rem; font-weight:700; font-size:0.875rem; cursor:pointer;
        display:inline-flex; align-items:center; gap:0.4rem;
        text-decoration:none; min-height:40px; transition:background 0.2s; font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-wa:hover { background:#16a34a; }
    .btn-back {
        color:#9ca3af; font-size:0.8rem; text-decoration:none;
        display:inline-flex; align-items:center; gap:0.3rem;
    }
    .btn-back:hover { color:white; }

    /* Hoja imprimible */
    .sheet {
        max-width: 820px;
        margin: 2rem auto;
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 30px rgba(0,0,0,0.1);
    }

    /* Encabezado de la hoja */
    .sheet-header {
        background: var(--dark);
        padding: 2rem 2.5rem;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .sheet-header h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 0.25rem;
    }
    .sheet-header .gym-name { font-size:0.85rem; color:var(--orange); font-weight:700; margin-bottom:0.3rem; }
    .sheet-header .objetivo-pill {
        display:inline-block; padding:0.2rem 0.65rem; border-radius:9999px;
        background:rgba(249,115,22,0.15); color:var(--orange);
        font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em;
    }
    .sheet-meta { text-align:right; color:#5a5a5a; font-size:0.8rem; line-height:1.6; }
    .sheet-meta strong { color:white; }

    /* Socio (si aplica) */
    .socio-bar {
        background:#f9fafb; border-bottom:2px solid #e5e7eb;
        padding:0.875rem 2.5rem;
        display:flex; align-items:center; gap:0.75rem;
    }
    .socio-bar .label { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--muted); }
    .socio-bar .name  { font-size:1rem; font-weight:700; color:#111; }

    /* Tabla de ejercicios */
    .ejercicios-section { padding: 2rem 2.5rem; }
    .ej-table { width:100%; border-collapse:collapse; }
    .ej-table thead tr { background:#f1f5f9; }
    .ej-table th {
        padding:0.6rem 0.875rem; text-align:left;
        font-size:0.7rem; font-weight:800; text-transform:uppercase;
        letter-spacing:0.06em; color:var(--muted);
    }
    .ej-table th:first-child { border-radius:0.375rem 0 0 0.375rem; }
    .ej-table th:last-child  { border-radius:0 0.375rem 0.375rem 0; }
    .ej-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.1s; }
    .ej-table tbody tr:last-child { border-bottom:none; }
    .ej-table tbody tr:hover { background:#fffbf7; }
    .ej-table td { padding:0.875rem 0.875rem; vertical-align:top; }
    .ej-num-cell {
        width:2.2rem; height:2.2rem; border-radius:50%;
        background:rgba(249,115,22,0.1); color:var(--orange);
        font-weight:800; font-size:0.85rem; font-family:'Outfit',sans-serif;
        display:flex; align-items:center; justify-content:center;
    }
    .ej-nombre { font-weight:700; font-size:0.95rem; color:#111; margin-bottom:0.2rem; }
    .ej-notas  { font-size:0.78rem; color:var(--muted); font-style:italic; }
    .ej-pill {
        display:inline-block; padding:0.2rem 0.5rem; border-radius:0.375rem;
        background:#f1f5f9; color:#374151; font-size:0.72rem; font-weight:700;
        margin-right:0.25rem; font-family:'Outfit',sans-serif; white-space:nowrap;
    }
    .descanso-pill { background:#fef9ee; color:#92400e; }

    /* Footer de hoja */
    .sheet-footer {
        background:#f9fafb; border-top:1px solid #e5e7eb;
        padding:1.25rem 2.5rem;
        display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;
    }
    .sheet-footer p { font-size:0.75rem; color:var(--muted); }

    /* ── PRINT ── */
    @media print {
        body { background:white; }
        .action-bar { display:none !important; }
        .sheet { margin:0; border-radius:0; box-shadow:none; max-width:100%; }
        .ej-table tbody tr:hover { background:white; }
        @page { margin: 1.5cm; }
    }

    /* ── MOBILE ── */
    @media (max-width: 600px) {
        .sheet-header { padding: 1.5rem; }
        .socio-bar { padding: 1rem 1.5rem; }
        .ejercicios-section { padding: 1.5rem; }
        .sheet-footer { padding: 1rem 1.5rem; }
        .ej-table th, .ej-table td { padding: 0.75rem 0.5rem; }
        .ej-pill { padding: 0.2rem 0.4rem; font-size: 0.65rem; }
    }
</style>
</head>
<body>
<?php
if ($socio_id && $socio && !empty($socio['token_qr'])) {
    $url_publica = BASE_URL . "modules/rutinas/imprimir.php?id={$rutina_id}&socio_id={$socio_id}&tk={$socio['token_qr']}";
}
?>
<!-- Barra de acciones (solo pantalla) -->
<div class="action-bar">
    <div>
        <h2><?= h($rutina['nombre']) ?></h2>
        <p><?= h($rutina['gym_nombre']) ?> &nbsp;·&nbsp; <?= count($ejercicios) ?> ejercicios</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <?php if ($is_admin): ?>
        <a href="editar.php?id=<?= $rutina_id ?><?= $socio_id ? "&socio_id=$socio_id" : '' ?>" class="btn-back">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Editar
        </a>
        <button onclick="document.getElementById('qrModal').style.display='flex'" class="btn-wa" style="background:#0ea5e9;">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Compartir por QR
        </button>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-print">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir / Guardar PDF
        </button>
    </div>
</div>

<!-- Modal QR -->
<div id="qrModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div style="background:white; border-radius:1rem; padding:2rem; text-align:center; max-width:320px; width:100%; position:relative;">
        <button onclick="document.getElementById('qrModal').style.display='none'" style="position:absolute; top:1rem; right:1rem; background:none; border:none; cursor:pointer; color:#6b7280;">
            <svg style="width:1.5rem;height:1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 style="font-family:'Outfit',sans-serif; font-size:1.25rem; font-weight:800; color:#111; margin-bottom:0.25rem;">Escanear Rutina</h3>
        <p style="font-size:0.875rem; color:#6b7280; margin-bottom:1.5rem;">El socio puede escanear este código con su celular para ver la rutina.</p>
        
        <div id="qrcode_box" style="background:white; padding:1rem; border:2px solid #f1f5f9; border-radius:0.75rem; display:inline-block; margin-bottom:1rem;"></div>
        
        <button onclick="navigator.clipboard.writeText('<?= $url_publica ?>'); alert('Enlace copiado al portapapeles');" class="btn-back" style="background:#f1f5f9; color:#374151; padding:0.5rem 1rem; border-radius:0.5rem; width:100%; justify-content:center; text-decoration:none; font-weight:600;">
            Copiar Enlace
        </button>
    </div>
</div>

<!-- Hoja A4 imprimible -->
<div class="sheet">
    <div class="sheet-header">
        <div>
            <p class="gym-name"><?= h($rutina['gym_nombre']) ?></p>
            <h1><?= h($rutina['nombre']) ?></h1>
            <span class="objetivo-pill"><?= $objetivos[$rutina['objetivo']] ?? 'General' ?></span>
        </div>
        <div class="sheet-meta">
            <p><?= $fecha_hoy ?></p>
            <p><?= count($ejercicios) ?> ejercicios</p>
            <?php if ($rutina['descripcion']): ?>
            <p style="max-width:220px;text-align:right;color:#9ca3af;font-size:0.75rem;margin-top:0.3rem;line-height:1.4;"><?= h($rutina['descripcion']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($socio): ?>
    <div class="socio-bar">
        <div>
            <p class="label">Asignada a</p>
            <p class="name"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="ejercicios-section">
        <?php if (empty($ejercicios)): ?>
        <p style="color:var(--muted);text-align:center;padding:2rem;">Sin ejercicios registrados.</p>
        <?php else: ?>
        <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="ej-table">
                <thead>
                    <tr>
                        <th style="width:2.5rem;">#</th>
                        <th>Ejercicio</th>
                        <th style="white-space:nowrap;">Series × Reps</th>
                        <th>Descanso</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ejercicios as $i => $ej): ?>
                <tr>
                    <td><div class="ej-num-cell"><?= $i+1 ?></div></td>
                    <td>
                        <p class="ej-nombre"><?= h($ej['nombre']) ?></p>
                        <?php if ($ej['notas']): ?><p class="ej-notas"><?= h($ej['notas']) ?></p><?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <span class="ej-pill"><?= h($ej['series']) ?> series</span>
                        <span class="ej-pill"><?= h($ej['repeticiones']) ?> reps</span>
                    </td>
                    <td>
                        <span class="ej-pill descanso-pill"><?= (int)$ej['descanso_segundos'] ?>s</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="sheet-footer">
        <p><?= h($rutina['gym_nombre']) ?> &nbsp;·&nbsp; Documento generado el <?= $fecha_hoy ?></p>
        <p>GymSuite · by R.DEV</p>
    </div>
</div>

<div style="height:3rem;"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    if (document.getElementById('qrcode_box')) {
        new QRCode(document.getElementById('qrcode_box'), {
            text: "<?= $url_publica ?>",
            width: 200,
            height: 200,
            colorDark : "#111111",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
</script>

</body>
</html>
