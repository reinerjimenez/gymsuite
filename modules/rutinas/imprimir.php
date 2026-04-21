<?php
// modules/rutinas/imprimir.php — Vista de impresión / PDF + enlace WhatsApp
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Sesión opcional: puede ser accedida por admin o con token temporal
// Para uso interno: admin lo imprime o genera enlace al socio
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$rutina_id   = (int)($_GET['id'] ?? 0);
$socio_id    = (int)($_GET['socio_id'] ?? 0); // Opcional: para personalizar encabezado

if (!$rutina_id) { header("Location: index.php"); exit; }

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
</style>
</head>
<body>

<!-- Barra de acciones (solo pantalla) -->
<div class="action-bar">
    <div>
        <h2><?= h($rutina['nombre']) ?></h2>
        <p><?= h($rutina['gym_nombre']) ?> &nbsp;·&nbsp; <?= count($ejercicios) ?> ejercicios</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <a href="editar.php?id=<?= $rutina_id ?><?= $socio_id ? "&socio_id=$socio_id" : '' ?>" class="btn-back">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Editar
        </a>
        <?php if ($telefono_wa): ?>
        <a href="<?= $url_wa ?>" target="_blank" class="btn-wa">
            <svg style="width:1rem;height:1rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Enviar por WhatsApp<?= $socio ? ' a '.$socio['nombre'] : '' ?>
        </a>
        <?php else: ?>
        <a href="<?= $url_wa ?>" target="_blank" class="btn-wa">
            <svg style="width:1rem;height:1rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Compartir por WhatsApp
        </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-print">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir / Guardar PDF
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
        <?php endif; ?>
    </div>

    <div class="sheet-footer">
        <p><?= h($rutina['gym_nombre']) ?> &nbsp;·&nbsp; Documento generado el <?= $fecha_hoy ?></p>
        <p>GymSuite · by R.DEV</p>
    </div>
</div>

<div style="height:3rem;"></div>
</body>
</html>
