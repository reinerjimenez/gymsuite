<?php
// modules/recepcionistas/editar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo el admin puede editar

$gimnasio_id = get_gimnasio_id();
$id = (int)($_GET['id'] ?? 0);

// Validar que el recepcionista exista y pertenezca al gym
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND gimnasio_id = ? AND rol = 'recepcionista'");
$stmt->execute([$id, $gimnasio_id]);
$rec = $stmt->fetch();

if (!$rec) {
    mensaje_flash('error', 'Recepcionista no encontrado.');
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $password_nueva = $_POST['password'] ?? '';
    
    if ($password_nueva && strlen($password_nueva) < 6) {
        mensaje_flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
    } else {
        if ($password_nueva) {
            $hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, password = ? WHERE id = ?");
            $stmtUpd->execute([$nombre, $apellido, $hash, $id]);
        } else {
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ? WHERE id = ?");
            $stmtUpd->execute([$nombre, $apellido, $id]);
        }
        mensaje_flash('success', 'Datos actualizados correctamente.');
        header("Location: index.php");
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a la lista
        </a>
        <h1 class="text-h2">Editar Recepcionista</h1>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= h($rec['nombre']) ?>" required>
                </div>
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?= h($rec['apellido']) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Usuario de Ingreso</label>
                <input type="text" class="form-control" value="<?= h($rec['usuario']) ?>" disabled style="opacity:0.7;cursor:not-allowed;">
                <small style="color:var(--color-text-muted);font-size:0.75rem;margin-top:0.25rem;display:block;">El nombre de usuario no se puede cambiar por seguridad.</small>
            </div>

            <div class="form-group" style="margin-bottom:2rem;padding-top:1rem;border-top:1px solid #262626;">
                <label class="form-label" for="password">Restablecer Contraseña</label>
                <input type="text" id="password" name="password" class="form-control" placeholder="Solo si deseas cambiarla..." autocomplete="new-password">
                <small style="color:var(--color-text-muted);font-size:0.75rem;margin-top:0.25rem;display:block;">Déjalo en blanco para mantener la clave actual.</small>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='Guardando...';">Actulizar Datos</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
