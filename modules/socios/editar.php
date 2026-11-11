<?php
// modules/socios/editar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$socio_id = (int)($_GET['id'] ?? 0);

if (!$socio_id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ? AND gimnasio_id = ? AND eliminado_en IS NULL");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();

if (!$socio) {
    mensaje_flash('error', 'Socio no encontrado.');
    header("Location: index.php"); exit;
}
if (!isset($_SESSION['socio_estado_csrf'])) $_SESSION['socio_estado_csrf'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'reset_pin') {
        $pin_aleatorio = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $clave_acceso = password_hash($pin_aleatorio, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE socios SET clave_acceso = ? WHERE id = ?");
        $stmt->execute([$clave_acceso, $socio_id]);
        mensaje_flash('success', "
            <div class='mb-2'><strong>¡PIN Restablecido Exitosamente!</strong></div>
            <div class='text-lg'>La NUEVA clave de acceso (PIN) es: <b class='text-h2 block mt-1 tracking-[0.2em]'>$pin_aleatorio</b></div>
            <div class='text-sm mt-2 text-green-200'>Anótelo y entrégueselo al socio de inmediato.</div>
        ");
        header("Location: editar.php?id=" . $socio_id);
        exit;
    }

    // Si viene action de baja lógica (SOLO ADMIN)
    if (isset($_POST['action']) && $_POST['action'] === 'baja_logica') {
        verificar_rol(['admin']); // extra validacion de seguridad profunda
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['socio_estado_csrf'], $_POST['csrf'])) {
            mensaje_flash('error', 'Formulario vencido. Recarga el perfil e inténtalo de nuevo.');
            header("Location: perfil.php?id=" . $socio_id);
            exit;
        }
        $nuevo_estado = (int)$socio['activo'] === 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE socios SET activo = ? WHERE id = ? AND gimnasio_id = ? AND eliminado_en IS NULL");
        $stmt->execute([$nuevo_estado, $socio_id, $gimnasio_id]);
        mensaje_flash('success', $nuevo_estado === 1 ? 'El socio ha sido rehabilitado.' : 'El socio ha sido bloqueado (Baja).');
        header("Location: perfil.php?id=" . $socio_id);
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
    $nota_interna = trim($_POST['nota_interna'] ?? '');

    if (empty($nombre) || empty($apellido) || empty($telefono)) {
        mensaje_flash('error', 'Nombres, Apellidos y Teléfono son obligatorios.');
    } else {
        $stmt = $pdo->prepare("
            UPDATE socios 
            SET nombre = ?, apellido = ?, dni = ?, telefono = ?, email = ?, fecha_nacimiento = ?, nota_interna = ?
            WHERE id = ? AND gimnasio_id = ?
        ");
        if ($stmt->execute([$nombre, $apellido, $dni, $telefono, $email, $fecha_nacimiento, $nota_interna === '' ? null : $nota_interna, $socio_id, $gimnasio_id])) {
            mensaje_flash('success', 'Datos actualizados exitosamente.');
            header("Location: perfil.php?id=" . $socio_id);
            exit;
        } else {
            mensaje_flash('error', 'Hubo un error al actualizar al socio.');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container" style="max-width: 800px; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-h2 mb-1">Editar Socio</h1>
            <p class="text-muted">Actualizando a <?= h($socio['nombre']) ?>.</p>
        </div>
        <a href="perfil.php?id=<?= $socio_id ?>" class="btn btn-secondary">Volver al Perfil</a>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card mb-6">
        <h3 class="text-h3 mb-4">Información Personal</h3>
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Nombres</label>
                    <input type="text" name="nombre" value="<?= h($socio['nombre']) ?>" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellido" value="<?= h($socio['apellido']) ?>" class="form-control" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">DNI</label>
                    <input type="text" name="dni" value="<?= h($socio['dni']) ?>" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Teléfono (WhatsApp) *</label>
                    <input type="text" name="telefono" value="<?= h($socio['telefono']) ?>" class="form-control" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" value="<?= h($socio['email']) ?>" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Fecha Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?= h($socio['fecha_nacimiento']) ?>" class="form-control">
                </div>
            </div>

            <div class="form-group"><label for="nota_interna" class="form-label">Nota interna (opcional)</label><textarea id="nota_interna" name="nota_interna" class="form-control" rows="3"><?= h($socio['nota_interna'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <div class="card mb-6">
        <h3 class="text-h3 mb-2">Restablecer Accesos</h3>
        <p class="text-gray-400 text-sm mb-4">
            Si el socio olvidó su PIN de 4 dígitos, puedes generar uno nuevo de emergencia aquí. Reemplazará al anterior irreversiblemente.
        </p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="reset_pin">
            <button type="submit" class="btn btn-secondary require-confirm" data-confirm="¿Generar un nuevo PIN aleatorio para <?= h($socio['nombre']) ?>?">
                <svg class="nav-icon" style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                Generar Nuevo PIN
            </button>
        </form>
    </div>

    <?php if($_SESSION['rol'] === 'admin'): ?>
    <!-- Zona de Peligro exclusiva Admin -->
    <div class="card border border-red-500/30" style="background-color: rgba(69, 10, 10, 0.4);">
        <h3 class="text-red-400 font-bold mb-2">Zona de Control (Admin)</h3>
        <p class="text-gray-400 text-sm mb-4">
            <?= (int)$socio['activo'] === 1 ? 'Puedes desactivar este usuario del sistema. Su historial se conservará pero no podrá ingresar.' : 'El usuario está actualmente bloqueado. Puedes reestablecerle el acceso.' ?>
        </p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="baja_logica">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['socio_estado_csrf']) ?>">
            <?php if((int)$socio['activo'] === 1): ?>
                <button type="submit" class="btn btn-danger require-confirm" data-confirm="¿Bloquear a <?= h($socio['nombre']) ?>? Perderá acceso por completo.">
                    Dar de Baja (Bloquear)
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-secondary require-confirm" data-confirm="¿Habilitar nuevamente a <?= h($socio['nombre']) ?>?">
                    Habilitar Socio
                </button>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
