<?php
// modules/planes/editar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo admin

$gimnasio_id = get_gimnasio_id();
$plan_id = (int)($_GET['id'] ?? 0);

if (!$plan_id) {
    header("Location: index.php");
    exit;
}

// Obtener detalles básicos
$stmt = $pdo->prepare("SELECT * FROM planes WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$plan_id, $gimnasio_id]);
$plan = $stmt->fetch();

if (!$plan) {
    mensaje_flash('error', 'El plan no existe o no tiene acceso a él.');
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $duracion_dias = (int)($_POST['duracion_dias'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    
    if (empty($nombre) || $duracion_dias <= 0 || $precio < 0) {
        mensaje_flash('error', 'Por favor, completa todos los campos correctamente.');
    } else {
        $stmt = $pdo->prepare("UPDATE planes SET nombre = ?, duracion_dias = ?, precio = ? WHERE id = ? AND gimnasio_id = ?");
        if ($stmt->execute([$nombre, $duracion_dias, $precio, $plan_id, $gimnasio_id])) {
            mensaje_flash('success', 'Plan actualizado exitosamente.');
            header("Location: index.php");
            exit;
        } else {
            mensaje_flash('error', 'Hubo un error al actualizar el plan.');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container" style="max-width: 600px; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-h2 mb-1">Editar Plan</h1>
            <p class="text-muted">Ajusta los detalles de este plan.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">Volver</a>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre" class="form-label">Nombre del Plan</label>
                <input type="text" id="nombre" name="nombre" value="<?= h($plan['nombre']) ?>" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="duracion_dias" class="form-label">Duración (en Días)</label>
                <input type="number" id="duracion_dias" name="duracion_dias" value="<?= (int)$plan['duracion_dias'] ?>" class="form-control" min="1" required>
            </div>

            <div class="form-group mb-6">
                <label for="precio" class="form-label">Precio (S/)</label>
                <input type="number" step="0.01" id="precio" name="precio" value="<?= number_format($plan['precio'], 2, '.', '') ?>" class="form-control" min="0" required>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Actualizar Plan</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
