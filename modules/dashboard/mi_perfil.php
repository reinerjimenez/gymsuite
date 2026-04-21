<?php
// modules/dashboard/mi_perfil.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();

// Asegurarse de que no es socio (los socios usan modules/socio/cambiar_clave.php)
if ($_SESSION['rol'] === 'socio') {
    header("Location: " . BASE_URL . "modules/socio/cambiar_clave.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("SELECT nombre, apellido, usuario, rol FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$u = $stmt->fetch();

if (!$u) {
    header("Location: " . BASE_URL . "logout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_usuario = trim($_POST['usuario'] ?? '');
    $clave_actual  = $_POST['clave_actual'] ?? '';
    $clave_nueva   = $_POST['clave_nueva'] ?? '';
    $clave_conf    = $_POST['clave_conf'] ?? '';

    // Validar contraseña actual si se quiere cambiar la contraseña o el username
    // Por seguridad pedimos clave actual para cualquier cambio aquí.
    $stmtVer = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmtVer->execute([$usuario_id]);
    $hash_db = $stmtVer->fetchColumn();

    if (!password_verify($clave_actual, $hash_db)) {
        mensaje_flash('error', 'La contraseña actual es incorrecta.');
    } else {
        $errores = false;
        
        // Verificar si se cambia el nombre de usuario y si ya existe
        if ($nuevo_usuario !== $u['usuario']) {
            $stmtVal = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmtVal->execute([$nuevo_usuario, $usuario_id]);
            if ($stmtVal->fetch()) {
                mensaje_flash('error', 'El nombre de usuario elegido ya está ocupado.');
                $errores = true;
            } else {
                $pdo->prepare("UPDATE usuarios SET usuario = ? WHERE id = ?")->execute([$nuevo_usuario, $usuario_id]);
            }
        }

        // Si digitó nueva clave, validamos y actualizamos
        if (!$errores && $clave_nueva !== '') {
            if (strlen($clave_nueva) < 6) {
                mensaje_flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
                $errores = true;
            } elseif ($clave_nueva !== $clave_conf) {
                mensaje_flash('error', 'Las contraseñas nuevas no coinciden.');
                $errores = true;
            } else {
                $hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $usuario_id]);
            }
        }

        if (!$errores) {
            mensaje_flash('success', 'Tus credenciales se han actualizado correctamente.');
            // Actualizar la variable $u para refrescar el valor en el input
            $u['usuario'] = $nuevo_usuario;
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h1 class="text-h2">Ajustes de mi Cuenta</h1>
        <p class="text-muted">Actualiza tu nombre de usuario o cambia tu contraseña de acceso.</p>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 class="text-h3" style="margin-bottom:1.25rem;">Credenciales de Acceso</h3>
        <form method="POST" action="">
            
            <div style="margin-bottom:1.5rem;">
                <label class="form-label" for="usuario">Nombre de Usuario (Para el login)</label>
                <input type="text" id="usuario" name="usuario" class="form-control" value="<?= h($u['usuario']) ?>" pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo (_)" required>
                <small style="color:var(--color-text-muted);display:block;margin-top:0.25rem;">Si lo cambias, deberás usar el nuevo nombre la próxima vez que inicies sesión.</small>
            </div>

            <h3 class="text-h3" style="font-size:1rem;margin-bottom:1rem;color:var(--color-primary);border-top:1px solid #262626;padding-top:1.5rem;">Seguridad y Confirmación</h3>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="clave_nueva">Nueva Contraseña (Opcional)</label>
                <input type="password" id="clave_nueva" name="clave_nueva" class="form-control" placeholder="Déjalo en blanco si no deseas cambiarla" minlength="6" autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="clave_conf">Repite la Nueva Contraseña</label>
                <input type="password" id="clave_conf" name="clave_conf" class="form-control" placeholder="Solo si escribiste una nueva arriba" autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-bottom:2rem;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2);padding:1rem;border-radius:0.5rem;">
                <label class="form-label" for="clave_actual" style="color:#fca5a5;">Contraseña Actual requerida *</label>
                <input type="password" id="clave_actual" name="clave_actual" class="form-control" placeholder="Ingresa tu clave actual para guardar los cambios" required autocomplete="current-password" style="border-color:rgba(239,68,68,0.3);">
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="min-width:150px;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
