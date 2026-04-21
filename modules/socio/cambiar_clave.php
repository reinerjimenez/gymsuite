<?php
// modules/socio/cambiar_clave.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['socio']);

$socio_id    = $_SESSION['socio_id'];
$gimnasio_id = $_SESSION['gimnasio_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave_actual = $_POST['clave_actual'] ?? '';
    $clave_nueva  = $_POST['clave_nueva'] ?? '';
    $clave_conf   = $_POST['clave_conf'] ?? '';

    $stmt = $pdo->prepare("SELECT clave_acceso FROM socios WHERE id = ? AND gimnasio_id = ?");
    $stmt->execute([$socio_id, $gimnasio_id]);
    $socio = $stmt->fetch();

    if (!$socio || !password_verify($clave_actual, $socio['clave_acceso'])) {
        mensaje_flash('error', 'El PIN de acceso actual que ingresaste es incorrecto.');
    } elseif ($clave_nueva !== $clave_conf) {
        mensaje_flash('error', 'El nuevo PIN y su comprobación no coinciden.');
    } elseif (!preg_match('/^\d{4}$/', $clave_nueva)) {
        mensaje_flash('error', 'El nuevo PIN debe ser exactamente de 4 dígitos numéricos.');
    } else {
        $hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE socios SET clave_acceso = ? WHERE id = ?")->execute([$hash, $socio_id]);
        mensaje_flash('success', 'Tu PIN de acceso ha sido actualizado correctamente.');
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div style="max-width:500px; margin:0 auto;">
    <div style="margin-bottom:2rem;text-align:left;">
        <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a Mi Panel
        </a>
        <h1 class="text-h2">Cambiar mi PIN de Acceso</h1>
        <p class="text-muted">Si tu gimnasio usa acceso en pantalla táctil, este es el código que digitas.</p>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="clave_actual">PIN de Acceso Actual</label>
                <input type="password" id="clave_actual" name="clave_actual" class="form-control" placeholder="••••" required maxlength="4" style="font-family:monospace;font-size:1.5rem;letter-spacing:0.5em;text-align:center;">
            </div>

            <hr style="border:none;border-top:1px solid #262626;margin:1.5rem 0;">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="clave_nueva">Nuevo PIN (4 Dígitos)</label>
                <input type="password" id="clave_nueva" name="clave_nueva" class="form-control" placeholder="••••" required pattern="\d{4}" maxlength="4" style="font-family:monospace;font-size:1.5rem;letter-spacing:0.5em;text-align:center;">
            </div>

            <div class="form-group" style="margin-bottom:2rem;">
                <label class="form-label" for="clave_conf">Confirmar el Nuevo PIN</label>
                <input type="password" id="clave_conf" name="clave_conf" class="form-control" placeholder="••••" required pattern="\d{4}" maxlength="4" style="font-family:monospace;font-size:1.5rem;letter-spacing:0.5em;text-align:center;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.05rem;min-height:50px;" onclick="this.innerHTML='Actualizando...';">
                Actualizar PIN
            </button>
        </form>
    </div>
    <p style="text-align:center;font-size:0.75rem;color:var(--color-text-muted);margin-top:1rem;">Por seguridad, utiliza un PIN que solo tú conozcas, pero que puedas recordar fácilmente.</p>
</div>

<!-- Lógica para que en móviles aparezca panel numérico -->
<script>
document.querySelectorAll('input[type="password"]').forEach(el => {
    // Para forzar el teclado numérico en iOS/Android
    el.setAttribute('inputmode', 'numeric');
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
