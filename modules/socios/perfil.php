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

            <button onclick="window.print()" class="btn btn-secondary w-full" style="width: 100%;">Imprimir Ficha</button>
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
