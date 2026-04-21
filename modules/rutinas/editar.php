<?php
// modules/rutinas/editar.php — Editor de ejercicios con drag & drop
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();
$rutina_id   = (int)($_GET['id'] ?? 0);

if (!$rutina_id) { header("Location: index.php"); exit; }

// Verificar que la rutina pertenece al gym
$stmt = $pdo->prepare("SELECT * FROM rutinas WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$rutina_id, $gimnasio_id]);
$rutina = $stmt->fetch();
if (!$rutina) { mensaje_flash('error', 'Rutina no encontrada.'); header("Location: index.php"); exit; }

// ── POST: agregar ejercicio ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'agregar_ejercicio') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $series    = (int)($_POST['series']   ?? 3);
        $reps      = trim($_POST['repeticiones'] ?? '12');
        $descanso  = (int)($_POST['descanso_segundos'] ?? 60);
        $notas     = trim($_POST['notas']     ?? '');

        if ($nombre) {
            // Orden: al final
            $stmtOrd = $pdo->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM ejercicios WHERE rutina_id=?");
            $stmtOrd->execute([$rutina_id]);
            $orden = (int)$stmtOrd->fetchColumn();

            $pdo->prepare("INSERT INTO ejercicios (rutina_id,nombre,series,repeticiones,descanso_segundos,notas,orden) VALUES (?,?,?,?,?,?,?)")
                ->execute([$rutina_id, $nombre, $series, $reps, $descanso, $notas, $orden]);
            mensaje_flash('success', "Ejercicio «{$nombre}» añadido.");
        }
        header("Location: editar.php?id=$rutina_id"); exit;
    }

    if ($action === 'eliminar_ejercicio') {
        $ej_id = (int)($_POST['ejercicio_id'] ?? 0);
        // Verificar que el ejercicio pertenece a esta rutina
        $chk = $pdo->prepare("SELECT id FROM ejercicios WHERE id=? AND rutina_id=?");
        $chk->execute([$ej_id, $rutina_id]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM ejercicios WHERE id=?")->execute([$ej_id]);
            mensaje_flash('success', 'Ejercicio eliminado.');
        }
        header("Location: editar.php?id=$rutina_id"); exit;
    }

    if ($action === 'guardar_orden') {
        // Recibe JSON: [{id:X, orden:Y}, ...]
        $items = json_decode($_POST['orden_json'] ?? '[]', true);
        if (is_array($items)) {
            $stmtUpd = $pdo->prepare("UPDATE ejercicios SET orden=? WHERE id=? AND rutina_id=?");
            foreach ($items as $item) {
                $stmtUpd->execute([(int)$item['orden'], (int)$item['id'], $rutina_id]);
            }
        }
        echo json_encode(['exito' => true]);
        exit;
    }

    if ($action === 'editar_rutina') {
        $nombre_r = trim($_POST['nombre']      ?? '');
        $desc_r   = trim($_POST['descripcion'] ?? '');
        $obj_r    = $_POST['objetivo'] ?? 'otro';
        if ($nombre_r) {
            $pdo->prepare("UPDATE rutinas SET nombre=?,descripcion=?,objetivo=? WHERE id=? AND gimnasio_id=?")
                ->execute([$nombre_r, $desc_r, $obj_r, $rutina_id, $gimnasio_id]);
            mensaje_flash('success', 'Datos de la rutina actualizados.');
        }
        header("Location: editar.php?id=$rutina_id"); exit;
    }
}

// Cargar ejercicios ordenados
$stmtEj = $pdo->prepare("SELECT * FROM ejercicios WHERE rutina_id=? ORDER BY orden ASC, id ASC");
$stmtEj->execute([$rutina_id]);
$ejercicios = $stmtEj->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';

$objetivos = ['fuerza'=>'Fuerza','cardio'=>'Cardio','perdida_peso'=>'Pérdida de Peso','tonificacion'=>'Tonificación','otro'=>'General'];
$genero = str_contains($rutina['nombre'], 'Mujeres') ? 'Mujeres' : 'Hombres';
?>

<style>
    .ej-row {
        background: var(--color-surface);
        border: 1px solid #292929;
        border-radius: 0.75rem;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: grab;
        transition: box-shadow 0.2s, transform 0.15s;
        user-select: none;
    }
    .ej-row:active { cursor: grabbing; }
    .ej-row.dragging {
        opacity: 0.4;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    .ej-row.drag-over { border-color: var(--color-primary); background: rgba(249,115,22,0.04); }
    .ej-num {
        width: 2rem; height: 2rem; border-radius: 50%;
        background: rgba(249,115,22,0.1);
        color: var(--color-primary);
        font-weight: 800; font-size: 0.85rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-family: var(--font-heading);
    }
    .ej-drag-handle {
        flex-shrink: 0; color: #444; cursor: grab;
    }
    .ej-info { flex: 1; min-width: 0; }
    .ej-nombre { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ej-meta { font-size: 0.78rem; color: var(--color-text-muted); display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .ej-tag { display: flex; align-items: center; gap: 0.3rem; }
    .pill {
        font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem;
        border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.04em;
    }
    .save-bar {
        display: none;
        position: sticky; bottom: 1rem; z-index: 20;
        background: var(--color-primary); color: white;
        border-radius: 0.75rem; padding: 0.75rem 1.25rem;
        font-weight: 700; text-align: center; cursor: pointer;
        box-shadow: 0 4px 20px rgba(249,115,22,0.4);
        border: none; width: 100%; font-family: var(--font-body); font-size: 1rem;
        transition: all 0.2s;
    }
    .save-bar.visible { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
    .save-bar:hover { background: var(--color-primary-hover); }
    @media(max-width:600px) {
        .ej-row { flex-wrap: wrap; }
        .ej-meta { flex-direction: column; gap: 0.3rem; }
    }
</style>

<div style="max-width:780px;margin:0 auto;">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.4rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
                <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Rutinas
            </a>
            <h1 class="text-h2" style="margin-bottom:0.2rem;"><?= h($rutina['nombre']) ?></h1>
            <p class="text-muted"><?= $objetivos[$rutina['objetivo']] ?? 'General' ?> &nbsp;·&nbsp; Para <?= $genero ?> &nbsp;·&nbsp; <?= count($ejercicios) ?> ejercicios</p>
        </div>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a href="imprimir.php?id=<?= $rutina_id ?>" target="_blank" class="btn btn-secondary" style="font-size:0.875rem;min-height:40px;padding:0.6rem 1rem;">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir / PDF
            </a>
        </div>
    </div>

    <?php mostrar_flash(); ?>

    <!-- Editar datos de la rutina -->
    <div class="card" style="margin-bottom:1.25rem;">
        <details>
            <summary style="cursor:pointer;font-weight:700;font-size:0.95rem;color:var(--color-text-muted);list-style:none;display:flex;align-items:center;gap:0.5rem;">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar datos de la rutina
            </summary>
            <form method="POST" style="margin-top:1rem;">
                <input type="hidden" name="action" value="editar_rutina">
                <div style="display:grid;gap:1rem;grid-template-columns:1fr 1fr;margin-bottom:1rem;">
                    <div class="form-group" style="margin:0;grid-column:1/-1;">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="<?= h($rutina['nombre']) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Objetivo</label>
                        <select name="objetivo" class="form-control">
                            <?php foreach ($objetivos as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $rutina['objetivo']===$val?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;grid-column:1/-1;">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" style="resize:vertical;"><?= h($rutina['descripcion'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary" style="font-size:0.875rem;min-height:40px;">Guardar Datos</button>
            </form>
        </details>
    </div>

    <!-- Lista de ejercicios con DnD -->
    <div style="margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <h3 style="font-size:1.1rem;font-weight:700;margin:0;">Ejercicios</h3>
        <p style="font-size:0.78rem;color:var(--color-text-muted);margin:0;display:flex;align-items:center;gap:0.35rem;">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Arrastra para reordenar
        </p>
    </div>

    <div id="ejerciciosList" style="display:flex;flex-direction:column;gap:0.6rem;margin-bottom:1rem;">
        <?php if (empty($ejercicios)): ?>
        <div style="text-align:center;padding:2.5rem;color:var(--color-text-muted);background:var(--color-surface);border-radius:0.75rem;border:1px dashed #333;">
            <svg style="width:2.5rem;height:2.5rem;margin:0 auto 0.75rem;opacity:0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p style="font-weight:600;">Sin ejercicios aún</p>
            <p style="font-size:0.85rem;">Añade el primero usando el formulario de abajo.</p>
        </div>
        <?php else: ?>
        <?php foreach ($ejercicios as $i => $ej): ?>
        <div class="ej-row" draggable="true" data-id="<?= $ej['id'] ?>">
            <div class="ej-drag-handle" title="Arrastrar para reordenar">
                <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
            </div>
            <div class="ej-num"><?= $i + 1 ?></div>
            <div class="ej-info">
                <p class="ej-nombre"><?= h($ej['nombre']) ?></p>
                <div class="ej-meta">
                    <span class="ej-tag">
                        <svg style="width:0.8rem;height:0.8rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                        <strong><?= h($ej['series']) ?></strong> series &nbsp;×&nbsp; <strong><?= h($ej['repeticiones']) ?></strong> reps
                    </span>
                    <span class="ej-tag">
                        <svg style="width:0.8rem;height:0.8rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?= (int)$ej['descanso_segundos'] ?>s descanso
                    </span>
                    <?php if ($ej['notas']): ?>
                    <span class="ej-tag" style="color:#a3a3a3;font-style:italic;">"<?= h(mb_substr($ej['notas'], 0, 55)) ?><?= mb_strlen($ej['notas']) > 55 ? '…' : '' ?>"</span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="POST" onsubmit="return confirm('¿Eliminar «<?= h(addslashes($ej['nombre'])) ?>»?')">
                <input type="hidden" name="action" value="eliminar_ejercicio">
                <input type="hidden" name="ejercicio_id" value="<?= $ej['id'] ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#555;padding:0.25rem;border-radius:0.375rem;transition:color 0.15s;" title="Eliminar" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#555'">
                    <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Botón guardar orden (sticky) -->
    <button class="save-bar" id="saveOrderBtn" onclick="guardarOrden()">
        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Guardar nuevo orden
    </button>

    <!-- Formulario: agregar ejercicio -->
    <div class="card">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
            <svg style="width:1rem;height:1rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Añadir Ejercicio
        </h3>
        <form method="POST" id="formAgregar">
            <input type="hidden" name="action" value="agregar_ejercicio">
            <div style="display:grid;gap:0.875rem;grid-template-columns:1fr 1fr;margin-bottom:0.875rem;">
                <div class="form-group" style="margin:0;grid-column:1/-1;">
                    <label class="form-label">Nombre del ejercicio *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Sentadilla con barra" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Series</label>
                    <input type="number" name="series" class="form-control" value="3" min="1" max="20">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Repeticiones</label>
                    <input type="text" name="repeticiones" class="form-control" value="12" placeholder="12 ó 10-12 ó Al fallo">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Descanso (segundos)</label>
                    <input type="number" name="descanso_segundos" class="form-control" value="60" min="0" max="600">
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1;">
                    <label class="form-label">Notas (opcional)</label>
                    <input type="text" name="notas" class="form-control" placeholder="Indicaciones técnicas, variantes...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Añadir al Listado
            </button>
        </form>
    </div>
</div>

<script>
(function() {
    const list    = document.getElementById('ejerciciosList');
    const saveBtn = document.getElementById('saveOrderBtn');
    let   dragSrc = null;
    let   ordenCambiado = false;

    list.querySelectorAll('.ej-row').forEach(row => {
        row.addEventListener('dragstart', e => {
            dragSrc = row;
            row.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('dragging');
            list.querySelectorAll('.ej-row').forEach(r => r.classList.remove('drag-over'));
            // Actualizar números
            list.querySelectorAll('.ej-num').forEach((num, i) => { num.textContent = i + 1; });
        });
        row.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            list.querySelectorAll('.ej-row').forEach(r => r.classList.remove('drag-over'));
            if (row !== dragSrc) row.classList.add('drag-over');
        });
        row.addEventListener('drop', e => {
            e.preventDefault();
            if (row === dragSrc) return;
            const rows  = [...list.querySelectorAll('.ej-row')];
            const srcIdx = rows.indexOf(dragSrc);
            const dstIdx = rows.indexOf(row);
            if (srcIdx < dstIdx) {
                list.insertBefore(dragSrc, row.nextSibling);
            } else {
                list.insertBefore(dragSrc, row);
            }
            ordenCambiado = true;
            saveBtn.classList.add('visible');
        });
    });

    window.guardarOrden = function() {
        const rows = [...list.querySelectorAll('.ej-row')];
        const datos = rows.map((r, i) => ({ id: parseInt(r.dataset.id), orden: i + 1 }));

        saveBtn.textContent = 'Guardando...';
        saveBtn.disabled = true;

        fetch('editar.php?id=<?= $rutina_id ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=guardar_orden&orden_json=${encodeURIComponent(JSON.stringify(datos))}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                saveBtn.innerHTML = `<svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> ¡Orden guardado!`;
                setTimeout(() => {
                    saveBtn.classList.remove('visible');
                    saveBtn.innerHTML = `<svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Guardar nuevo orden`;
                    saveBtn.disabled = false;
                    ordenCambiado = false;
                }, 2000);
            }
        });
    };
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
