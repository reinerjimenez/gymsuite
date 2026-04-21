<?php
// modules/socios/perfil.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$socio_id = (int)($_GET['id'] ?? 0);

if (!$socio_id) { header("Location: index.php"); exit; }

// Obtener datos del socio
$stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();

if (!$socio) {
    mensaje_flash('error', 'Socio no encontrado.');
    header("Location: index.php"); exit;
}

// Obtener membresía activa (la última)
$stmt = $pdo->prepare("
    SELECT m.*, p.nombre as plan_nombre 
    FROM membresias m 
    JOIN planes p ON m.plan_id = p.id
    WHERE m.socio_id = ?
    ORDER BY m.id DESC LIMIT 1
");
$stmt->execute([$socio_id]);
$membresia = $stmt->fetch();

// Planes disponibles para renovar
$stmt = $pdo->prepare("SELECT id, nombre, precio, duracion_dias FROM planes WHERE gimnasio_id = ? AND activo = 1 ORDER BY precio ASC");
$stmt->execute([$gimnasio_id]);
$planes = $stmt->fetchAll();

// Historial de Pagos del socio
$stmt = $pdo->prepare("SELECT * FROM pagos WHERE socio_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$socio_id]);
$pagos = $stmt->fetchAll();

// Rutina asignada activa
$stmtRut = $pdo->prepare("
    SELECT sr.id as asignacion_id, r.id as rutina_id, r.nombre AS rutina_nombre,
           r.objetivo, sr.fecha_asignacion
    FROM socio_rutina sr
    JOIN rutinas r ON r.id = sr.rutina_id
    WHERE sr.socio_id = ? AND sr.activa = 1
    ORDER BY sr.id DESC LIMIT 1
");
$stmtRut->execute([$socio_id]);
$rutina_asignada = $stmtRut->fetch();

// Rutinas disponibles para asignar
$stmtRuts = $pdo->prepare("SELECT id, nombre FROM rutinas WHERE gimnasio_id = ? AND activa = 1 ORDER BY id ASC");
$stmtRuts->execute([$gimnasio_id]);
$rutinas_disponibles = $stmtRuts->fetchAll();

// Últimos accesos del socio
$stmtAcc = $pdo->prepare("SELECT fecha_hora, metodo_usado FROM accesos WHERE socio_id = ? AND gimnasio_id = ? ORDER BY fecha_hora DESC LIMIT 8");
$stmtAcc->execute([$socio_id, $gimnasio_id]);
$accesos = $stmtAcc->fetchAll();

// Teléfono WhatsApp formateado
$tel_wa = null;
if ($socio['telefono']) {
    $tel = preg_replace('/\D/', '', $socio['telefono']);
    if (strlen($tel) === 9) $tel = '51' . $tel;
    $tel_wa = $tel;
}
$objetivos_labels = ['fuerza'=>'Fuerza','cardio'=>'Cardio','perdida_peso'=>'Pérdida de Peso','tonificacion'=>'Tonificación','otro'=>'General'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_rutina'])) {
    $nueva_rutina_id = (int)($_POST['rutina_id'] ?? 0);
    if ($nueva_rutina_id) {
        // Desactivar rutinas anteriores
        $pdo->prepare("UPDATE socio_rutina SET activa=0 WHERE socio_id=?")->execute([$socio_id]);
        // Asignar nueva
        $pdo->prepare("INSERT INTO socio_rutina (socio_id, rutina_id, fecha_asignacion, activa) VALUES (?,?,CURDATE(),1)")
            ->execute([$socio_id, $nueva_rutina_id]);
        mensaje_flash('success', 'Rutina asignada correctamente.');
    }
    header("Location: perfil.php?id=$socio_id"); exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Importar QR Code JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="dashboard-grid profile-grid mb-6">
    <!-- INFO BÁSICA Y ESTADO -->
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-h2 mb-1">Perfil de <?= h($socio['nombre']) ?></h1>
            <a href="editar.php?id=<?= $socio_id ?>" class="btn btn-secondary">Editar Datos</a>
        </div>
        
        <?php mostrar_flash(); ?>

        <div class="card mb-6">
            <?php
            $estado_mem = 'Sin Registro';
            $badge_clase = "badge-warning";
            $dias = 0;
            
            if ($membresia) {
                $dias = dias_restantes($membresia['fecha_fin']);
                $estado_texto = calcular_estado_membresia($membresia['fecha_fin']);
                
                if ($estado_texto === 'activa') {
                    $badge_clase = 'badge-success'; $estado_mem = "ACTIVA — Vence en $dias días";
                } elseif ($estado_texto === 'por_vencer') {
                    $badge_clase = 'badge-warning'; $estado_mem = "POR VENCER — Vence en $dias días";
                } else {
                    $badge_clase = 'badge-danger'; $estado_mem = "VENCIDA hace " . abs($dias) . " días";
                }
            }
            ?>
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-800" style="border-color: #262626; border-bottom-width: 1px;">
                <div>
                    <h3 class="text-lg font-bold text-white"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></h3>
                    <p class="text-sm text-gray-400">DNI: <?= h($socio['dni']) ?> — <?= h($socio['telefono'] ?: 'Sin Teléfono') ?></p>
                </div>
                <div>
                    <?php if((int)$socio['activo'] === 1): ?>
                        <span class="badge badge-success">Socio Habilitado</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Socio de Baja</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-gray-900 rounded p-4 border border-gray-800 mb-4" style="background-color: #111; border-radius: 0.5rem; padding: 1rem;">
                <h4 class="text-gray-400 text-xs uppercase mb-1">Estado de Membresía Actual</h4>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white font-bold text-xl"><?= $membresia ? h($membresia['plan_nombre']) : 'Sin Plan' ?></p>
                        <p class="text-sm text-gray-400 mt-1"><span class="badge <?= $badge_clase ?>"><?= $estado_mem ?></span></p>
                    </div>
                    <?php if((int)$socio['activo'] === 1): ?>
                        <!-- Botón abre modal -->
                        <button onclick="document.getElementById('modalRenovar').style.display='block'" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                            Renovar Membresía
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HISTORIAL DE PAGOS -->
        <div class="card">
            <h3 class="text-h3 mb-4">Últimos Pagos</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th></tr></thead>
                    <tbody>
                        <?php foreach($pagos as $pago): ?>
                            <tr>
                                <td><?= formatear_fecha($pago['fecha']) ?></td>
                                <td class="text-green-400 font-bold"><?= formatear_moneda($pago['monto']) ?></td>
                                <td><span class="badge" style="background:rgba(255,255,255,0.1);"><?= h(strtoupper($pago['metodo'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($pagos)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-gray-500">Sin pagos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HISTORIAL DE ACCESOS -->
        <div class="card" style="margin-bottom:1.25rem;">
            <h3 class="text-h3" style="margin-bottom:1rem;">Historial de Ingresos</h3>
            <?php if (empty($accesos)): ?>
            <p style="color:var(--color-text-muted);font-size:0.875rem;text-align:center;padding:1rem;">Sin accesos registrados.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha y Hora</th><th>Método</th></tr></thead>
                    <tbody>
                    <?php foreach($accesos as $acc):
                        $metodos_label=['dni'=>'DNI','clave'=>'PIN','qr'=>'Código QR','manual'=>'Manual'];
                        $metodo_tag = $metodos_label[$acc['metodo_usado']] ?? $acc['metodo_usado'];
                    ?>
                    <tr>
                        <td style="font-size:0.875rem;"><?= date('d/m/Y H:i', strtotime($acc['fecha_hora'])) ?></td>
                        <td><span class="badge" style="background:rgba(249,115,22,0.1);color:var(--color-primary);border:1px solid rgba(249,115,22,0.2);"><?= h($metodo_tag) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- RUTINA ASIGNADA -->
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
                <h3 class="text-h3" style="margin:0;">Rutina Asignada</h3>
                <?php if ($rutina_asignada): ?>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>modules/rutinas/imprimir.php?id=<?= $rutina_asignada['rutina_id'] ?>&socio_id=<?= $socio_id ?>" target="_blank" class="btn btn-secondary" style="font-size:0.8rem;min-height:36px;padding:0.4rem 0.875rem;">
                        <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Ver / PDF
                    </a>
                    <?php if ($tel_wa): ?>
                    <?php
                        $msg_wa_rutina = "Hola {$socio['nombre']}! Aqui esta tu rutina de entrenamiento: ";
                        $url_wa_rutina = "https://wa.me/{$tel_wa}?text=" . rawurlencode("Hola {$socio['nombre']}! 💪 Vi mi rutina asignada en el sistema de {$gym_nombre}. Puedes verla en el siguiente enlace o pedir una copia impresa en recepción.");
                    ?>
                    <a href="<?= BASE_URL ?>modules/rutinas/imprimir.php?id=<?= $rutina_asignada['rutina_id'] ?>&socio_id=<?= $socio_id ?>" target="_blank"
                       onclick="if(confirm('\u00bfEnviar la rutina por WhatsApp a <?= h($socio['nombre']) ?>?')){window.open('https://wa.me/<?= $tel_wa ?>?text=<?= rawurlencode('Hola '.$socio['nombre'].'! 💪 Aqui tienes tu rutina de entrenamiento asignada en '.$gym_nombre.'. Pasa por recepcion para la version impresa.') ?>', '_blank');}return false;"
                       class="btn" style="background:#22c55e;color:white;font-size:0.8rem;min-height:36px;padding:0.4rem 0.875rem;gap:0.35rem;">
                        <svg style="width:0.9rem;height:0.9rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($rutina_asignada): ?>
            <div style="background:rgba(249,115,22,0.05);border:1px solid rgba(249,115,22,0.15);border-radius:0.75rem;padding:1rem;margin-bottom:1rem;">
                <p style="font-weight:700;color:white;margin-bottom:0.2rem;"><?= h($rutina_asignada['rutina_nombre']) ?></p>
                <p style="font-size:0.8rem;color:var(--color-text-muted);">Objetivo: <?= h($objetivos_labels[$rutina_asignada['objetivo']] ?? 'General') ?> &nbsp;·&nbsp; Desde: <?= formatear_fecha($rutina_asignada['fecha_asignacion']) ?></p>
            </div>
            <?php else: ?>
            <p style="color:var(--color-text-muted);font-size:0.875rem;margin-bottom:0.875rem;">Este socio no tiene una rutina asignada aún.</p>
            <?php endif; ?>

            <?php if (!empty($rutinas_disponibles)): ?>
            <form method="POST" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
                <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                    <label class="form-label" style="font-size:0.75rem;"><?= $rutina_asignada ? 'Cambiar rutina' : 'Asignar rutina' ?></label>
                    <select name="rutina_id" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($rutinas_disponibles as $rd): ?>
                        <option value="<?= $rd['id'] ?>" <?= ($rutina_asignada && $rutina_asignada['rutina_id'] == $rd['id']) ? 'selected' : '' ?>>
                            <?= h($rd['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="asignar_rutina" value="1" class="btn btn-primary" style="font-size:0.875rem;min-height:48px;padding:0.6rem 1rem;white-space:nowrap;">
                    <?= $rutina_asignada ? 'Actualizar' : 'Asignar' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- CÓDIGO QR LATERAL -->
    <div>
        <div class="card text-center flex items-center justify-center" style="display:flex; flex-direction:column;">
            <h3 class="text-h3 mb-2">QR de Acceso</h3>
            <p class="text-xs text-muted mb-4">Código único de identidad</p>
            
            <div id="qrcode_container" class="bg-white p-4 rounded-lg inline-block mb-4" style="background: white; padding: 1rem; border-radius: 0.75rem;"></div>
            
            <p class="text-xs text-gray-500 mb-4 max-w-[200px] break-all" style="word-break: break-all;">
                Token: <?= h(substr($socio['token_qr'], 0, 10)) ?>...
            </p>

            <button onclick="window.print()" class="btn btn-secondary w-full" style="width: 100%; margin-bottom: 0.5rem;">Imprimir Ficha</button>
            <?php if ($tel_wa): ?>
            <?php 
                // Generar URL para WhatsApp
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $pase_url = $protocol . $host . BASE_URL . "pase.php?tk=" . $socio['token_qr'];
                $msg_qr = "Hola " . h($socio['nombre']) . "! Te enviamos tu Pase de Acceso Digital para el gimnasio.\n\nMuestra el codigo QR en recepcion para ingresar abriendo este enlace:\n" . $pase_url;
            ?>
            <a href="https://wa.me/<?= $tel_wa ?>?text=<?= urlencode($msg_qr) ?>" target="_blank" class="btn relative" style="width: 100%; background:#22c55e; color:white; display:flex; align-items:center; justify-content:center; gap:0.5rem; text-decoration:none;">
                <svg style="width:1.2rem;height:1.2rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Enviar QR por WhatsApp
            </a>
            <?php else: ?>
            <p class="text-xs text-red-500 mt-2">Agrega un teléfono para enviar por WhatsApp</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL RENOVACIÓN -->
<div id="modalRenovar" class="modal">
    <div class="modal-content">
        <h2 class="text-h2 mb-2">Renovar Membresía</h2>
        <p class="text-muted mb-4">Elige el plan y la forma de pago para <?= h($socio['nombre']) ?>. Al guardar, se extenderá el acceso y se registrará el ingreso automáticamente.</p>
        
        <form method="POST" action="../membresias/procesar_renovacion.php">
            <input type="hidden" name="socio_id" value="<?= $socio_id ?>">
            
            <div class="form-group">
                <label class="form-label">Plan a contratar</label>
                <select name="plan_id" class="form-control" required>
                    <option value="">-- Seleccionar Plan --</option>
                    <?php foreach($planes as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= h($p['nombre']) ?> (<?= $p['duracion_dias'] ?> días) — <?= formatear_moneda($p['precio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-6">
                <label class="form-label">Método de Pago</label>
                <select name="metodo_pago" class="form-control" required>
                    <option value="efectivo">Efectivo Módulo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="transferencia">Transferencia Bancaria</option>
                    <option value="otro">Otro Método</option>
                </select>
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary" style="flex:1;">Procesar Pago y Renovar</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalRenovar').style.display='none'" style="flex:1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generar el QR visual en pantalla partiendo del Token almacenado
    const qrcodeContainer = document.getElementById("qrcode_container");
    const token = "<?= h($socio['token_qr']) ?>";
    
    if(token && qrcodeContainer) {
        new QRCode(qrcodeContainer, {
            text: token,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }

    // Modal background click to close
    var modal = document.getElementById('modalRenovar');
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});
</script>

<style>
/* Ocultar elementos innecesarios al imprimir */
@media print {
    body * { visibility: hidden; }
    #qrcode_container, #qrcode_container * { visibility: visible; }
    #qrcode_container { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); transform-origin: center; zoom: 1.5; }
    .sidebar, .header, .btn, .modal { display: none !important; }
    .main-content { margin: 0; padding: 0; background: white; }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
