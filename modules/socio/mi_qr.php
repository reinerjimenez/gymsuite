<?php
// modules/socio/mi_qr.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['socio']);

$socio_id    = $_SESSION['socio_id'];
$gimnasio_id = $_SESSION['gimnasio_id'];

$stmt = $pdo->prepare("SELECT nombre, apellido, token_qr FROM socios WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();

if (!$socio) { header("Location: " . BASE_URL . "logout.php"); exit; }

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div style="max-width:400px; margin:0 auto; text-align:center;">
    <div style="margin-bottom:2rem;text-align:left;">
        <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a Mi Panel
        </a>
    </div>

    <div class="card" style="padding:3rem 2rem;">
        <h2 class="text-h3" style="margin-bottom:0.5rem;"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></h2>
        <p style="color:var(--color-primary);font-size:0.875rem;margin-bottom:2rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">Pase de Acceso</p>
        
        <div style="background:white;padding:1.5rem;border-radius:1rem;display:inline-block;margin-bottom:1.5rem;box-shadow:0 10px 25px -5px rgba(249,115,22,0.2);">
            <div id="qr_box"></div>
        </div>
        
        <p style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5;">Acerca este código al escáner en recepción para registrar tu ingreso automáticamente.</p>
    </div>

    <!-- Botón para incrementar brillo en móvil simulado (opcional visual) -->
    <button onclick="window.print()" class="btn btn-secondary" style="width:100%;font-size:1.05rem;min-height:50px;margin-top:1.5rem;gap:0.5rem;">
        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Guardar o Imprimir
    </button>
</div>

<style>
@media print {
    .sidebar, .header, .btn, a { display: none !important; }
    .app-wrapper { padding:0 !important; margin:0 !important; justify-content:center; align-items:center; min-height:100vh; }
    body { background: white !important; }
    .card { background: white !important; border:none !important; color:black !important; }
    .card h2 { color:black !important; }
    * { print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById('qr_box'), {
        text: "<?= h($socio['token_qr']) ?>",
        width: 250,
        height: 250,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
