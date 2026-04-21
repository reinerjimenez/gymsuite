<?php
// modules/membresias/renovar.php — Formulario rápido de renovación desde el módulo de accesos
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$socio_id    = (int)($_GET['socio_id'] ?? 0);

if (!$socio_id) { header("Location: /gymsuite/modules/accesos/index.php"); exit; }

// Cargar socio
$stmt = $pdo->prepare("SELECT id, nombre, apellido, dni, foto FROM socios WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();
if (!$socio) { mensaje_flash('error', 'Socio no encontrado.'); header("Location: /gymsuite/modules/accesos/index.php"); exit; }

// Membresía más reciente
$stmtM = $pdo->prepare("
    SELECT m.fecha_fin, m.estado, p.nombre as plan_nombre
    FROM membresias m JOIN planes p ON p.id = m.plan_id
    WHERE m.socio_id = ? AND m.gimnasio_id = ?
    ORDER BY m.fecha_fin DESC LIMIT 1
");
$stmtM->execute([$socio_id, $gimnasio_id]);
$mem_actual = $stmtM->fetch();

// Planes disponibles
$stmtP = $pdo->prepare("SELECT id, nombre, duracion_dias, precio FROM planes WHERE gimnasio_id = ? AND activo = 1 ORDER BY precio ASC");
$stmtP->execute([$gimnasio_id]);
$planes = $stmtP->fetchAll();

// URL de retorno: puede ir a accesos o al perfil del socio
$url_retorno = $_GET['retorno'] ?? 'accesos';

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
    .plan-option {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border: 2px solid #2a2a2a;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.18s ease;
        background: transparent;
    }
    .plan-option:hover { border-color: var(--color-primary); background: rgba(249,115,22,0.03); }
    .plan-option.selected { border-color: var(--color-primary); background: rgba(249,115,22,0.07); }
    .plan-option input[type="radio"] { accent-color: var(--color-primary); width: 1.1rem; height: 1.1rem; flex-shrink: 0; }
    .plan-price { font-size: 1.15rem; font-weight: 800; font-family: var(--font-heading); color: var(--color-primary); white-space: nowrap; }
    .plan-dias  { font-size: 0.75rem; color: var(--color-text-muted); }
</style>

<div style="max-width: 560px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom: 1.75rem;">
        <a href="/gymsuite/modules/accesos/index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a Accesos
        </a>
        <h1 class="text-h2" style="margin-bottom:0.25rem;">Renovar Membresía</h1>
        <p class="text-muted">Asigna un nuevo plan y registra el pago en un solo paso.</p>
    </div>

    <?php mostrar_flash(); ?>

    <!-- Card del socio -->
    <div class="card" style="margin-bottom: 1.25rem; display:flex; align-items:center; gap:1rem;">
        <?php if ($socio['foto']): ?>
        <img src="/gymsuite/uploads/fotos/<?= h($socio['foto']) ?>" alt="" style="width:3.5rem;height:3.5rem;border-radius:50%;object-fit:cover;border:2px solid #333;flex-shrink:0;">
        <?php else: ?>
        <div style="width:3.5rem;height:3.5rem;border-radius:50%;background:var(--color-surface-hover);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:var(--color-text-muted);flex-shrink:0;font-family:var(--font-heading);">
            <?= strtoupper(substr($socio['nombre'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div>
            <p style="font-weight:700;font-size:1.05rem;"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></p>
            <p style="font-size:0.8rem;color:var(--color-text-muted);">DNI: <?= h($socio['dni']) ?></p>
            <?php if ($mem_actual): ?>
            <p style="font-size:0.75rem;color:#ef4444;margin-top:0.2rem;">
                Última membresía: <strong><?= h($mem_actual['plan_nombre']) ?></strong> — Venció el <?= formatear_fecha($mem_actual['fecha_fin']) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulario de renovación -->
    <form method="POST" action="procesar_renovacion.php" id="formRenovar">
        <input type="hidden" name="socio_id" value="<?= $socio_id ?>">

        <!-- Selección de plan -->
        <div class="card" style="margin-bottom: 1.25rem;">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Elige un Plan</h3>
            <?php if (empty($planes)): ?>
            <p style="color:var(--color-text-muted);font-size:0.875rem;">No hay planes disponibles. <a href="/gymsuite/modules/planes/index.php">Crear planes</a></p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:0.6rem;" id="planesContainer">
                <?php foreach ($planes as $i => $p): ?>
                <label class="plan-option <?= $i === 0 ? 'selected' : '' ?>" for="plan_<?= $p['id'] ?>">
                    <input type="radio" id="plan_<?= $p['id'] ?>" name="plan_id" value="<?= $p['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                    <div style="flex:1;">
                        <p style="font-weight:700;margin-bottom:0.1rem;"><?= h($p['nombre']) ?></p>
                        <p class="plan-dias"><?= (int)$p['duracion_dias'] ?> días de acceso</p>
                    </div>
                    <span class="plan-price"><?= formatear_moneda($p['precio']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Método de pago -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Método de Pago</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:0.6rem;">
                <?php
                $metodos = ['efectivo'=>'Efectivo','yape'=>'Yape','plin'=>'Plin','transferencia'=>'Transferencia','otro'=>'Otro'];
                foreach ($metodos as $val => $lbl):
                ?>
                <label for="met_<?= $val ?>" style="cursor:pointer;">
                    <input type="radio" id="met_<?= $val ?>" name="metodo_pago" value="<?= $val ?>" <?= $val==='efectivo'?'checked':'' ?> style="display:none;">
                    <div class="met-option" data-val="<?= $val ?>" style="padding:0.65rem;border-radius:0.5rem;border:2px solid <?= $val==='efectivo'?'var(--color-primary)':'#2a2a2a' ?>;text-align:center;font-size:0.85rem;font-weight:600;background:<?= $val==='efectivo'?'rgba(249,115,22,0.07)':'transparent' ?>;transition:all 0.15s;color:<?= $val==='efectivo'?'var(--color-primary)':'var(--color-text-muted)' ?>;">
                        <?= $lbl ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" id="btnRenovar" class="btn btn-primary" style="width:100%;font-size:1.05rem;min-height:54px;"
            onclick="this.disabled=true;this.innerHTML='<span style=\'display:inline-block;width:1rem;height:1rem;border:2px solid white;border-top-color:transparent;border-radius:50%;animation:spin 0.7s linear infinite;\'></span>&nbsp; Procesando...';this.form.submit();">
            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Procesar Renovación y Registrar Pago
        </button>
    </form>
</div>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
// Highlight de plan seleccionado
document.getElementById('planesContainer')?.addEventListener('change', function(e) {
    document.querySelectorAll('.plan-option').forEach(el => el.classList.remove('selected'));
    if(e.target.type === 'radio') e.target.closest('.plan-option').classList.add('selected');
});

// Highlight de método de pago
document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.met-option').forEach(el => {
            el.style.borderColor = '#2a2a2a';
            el.style.background  = 'transparent';
            el.style.color       = 'var(--color-text-muted)';
        });
        const div = document.querySelector(`.met-option[data-val="${this.value}"]`);
        if(div) {
            div.style.borderColor = 'var(--color-primary)';
            div.style.background  = 'rgba(249,115,22,0.07)';
            div.style.color       = 'var(--color-primary)';
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
