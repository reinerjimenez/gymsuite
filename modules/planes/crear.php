<?php
// modules/planes/crear.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo admin

$gimnasio_id = get_gimnasio_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $duracion_dias = (int)($_POST['duracion_dias'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    
    if (empty($nombre) || $duracion_dias <= 0 || $precio < 0) {
        mensaje_flash('error', 'Por favor, completa todos los campos correctamente.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO planes (gimnasio_id, nombre, duracion_dias, precio, activo) VALUES (?, ?, ?, ?, 1)");
        if ($stmt->execute([$gimnasio_id, $nombre, $duracion_dias, $precio])) {
            mensaje_flash('success', 'Plan creado exitosamente.');
            header("Location: index.php");
            exit;
        } else {
            mensaje_flash('error', 'Hubo un error al crear el plan.');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container" style="max-width: 600px; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-h2 mb-1">Crear Plan</h1>
            <p class="text-muted">Nuevo plan de membresía para el gimnasio.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre" class="form-label">Nombre del Plan</label>
                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej. Plan Mensual Premium" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="duracion_dias" class="form-label">Duración (en Días)</label>
                <input type="number" id="duracion_dias" name="duracion_dias" class="form-control" placeholder="Ej. 30" min="1" required>
                <p class="text-xs text-muted mt-1">Cuántos días de acceso otorga este plan.</p>
            </div>

            <div class="form-group mb-6">
                <label for="precio" class="form-label">Precio (S/)</label>
                <input type="number" step="0.01" id="precio" name="precio" class="form-control" placeholder="Ej. 80.00" min="0" required>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Guardar Plan</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
