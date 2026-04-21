<?php
// modules/configuracion/index.php — Configuración del gimnasio
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();

// Cargar datos del gimnasio
$stmt = $pdo->prepare("SELECT * FROM gimnasios WHERE id = ?");
$stmt->execute([$gimnasio_id]);
$gym = $stmt->fetch();

// Cargar configuración
$stmtC = $pdo->prepare("SELECT * FROM configuracion_gym WHERE gimnasio_id = ?");
$stmtC->execute([$gimnasio_id]);
$config = $stmtC->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Guardar datos del gimnasio ──
    if ($action === 'datos_gym') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $ruc       = trim($_POST['ruc']       ?? '');

        if (empty($nombre)) {
            mensaje_flash('error', 'El nombre del gimnasio es obligatorio.');
        } else {
            $pdo->prepare("UPDATE gimnasios SET nombre=?, direccion=?, telefono=?, email=?, ruc=? WHERE id=?")
                ->execute([$nombre, $direccion, $telefono, $email, $ruc, $gimnasio_id]);
            mensaje_flash('success', 'Datos del gimnasio actualizados.');
            header("Location: index.php"); exit;
        }
    }

    // ── Cambiar método de acceso ──
    if ($action === 'metodo_acceso') {
        $metodo = $_POST['metodo_acceso'] ?? 'dni';
        if (!in_array($metodo, ['dni', 'clave', 'qr'])) $metodo = 'dni';

        // Upsert: insertar si no existe, actualizar si existe
        $existe = $pdo->prepare("SELECT id FROM configuracion_gym WHERE gimnasio_id = ?");
        $existe->execute([$gimnasio_id]);
        if ($existe->fetch()) {
            $pdo->prepare("UPDATE configuracion_gym SET metodo_acceso=? WHERE gimnasio_id=?")
                ->execute([$metodo, $gimnasio_id]);
        } else {
            $pdo->prepare("INSERT INTO configuracion_gym (gimnasio_id, metodo_acceso) VALUES (?,?)")
                ->execute([$gimnasio_id, $metodo]);
        }
        mensaje_flash('success', 'Método de acceso actualizado. Los cambios son inmediatos.');
        header("Location: index.php"); exit;
    }

    // ── Upload de logo ──
    if ($action === 'logo' && isset($_FILES['logo'])) {
        $file = $_FILES['logo'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            mensaje_flash('error', 'Solo se permiten imágenes JPG, PNG o WebP.');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            mensaje_flash('error', 'La imagen no puede superar los 2MB.');
        } else {
            $nuevo_nombre = uniqid('logo_') . '.' . $ext;
            $destino = __DIR__ . '/../../uploads/fotos/' . $nuevo_nombre;
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                $pdo->prepare("UPDATE gimnasios SET logo=? WHERE id=?")->execute([$nuevo_nombre, $gimnasio_id]);
                mensaje_flash('success', 'Logo actualizado correctamente.');
            } else {
                mensaje_flash('error', 'Error al subir el archivo.');
            }
        }
        header("Location: index.php"); exit;
    }
}

// Recargar datos para mostrar
$stmt->execute([$gimnasio_id]);
$gym = $stmt->fetch();
$stmtC->execute([$gimnasio_id]);
$config = $stmtC->fetch();
$metodo_actual = $config['metodo_acceso'] ?? 'dni';

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container" style="max-width: 760px; margin: 0 auto;">
    <div style="margin-bottom: 1.75rem;">
        <h1 class="text-h2" style="margin-bottom:0.25rem;">Configuración</h1>
        <p class="text-muted">Gestiona los datos y preferencias de tu gimnasio.</p>
    </div>

    <?php mostrar_flash(); ?>

    <!-- ── Datos del Gimnasio ── -->
    <div class="card" style="margin-bottom: 1.25rem;">
        <h3 class="text-h3" style="margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
            <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Datos del Gimnasio
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="datos_gym">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Nombre del Gimnasio *</label>
                    <input type="text" name="nombre" class="form-control" value="<?= h($gym['nombre']) ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">RUC (opcional)</label>
                    <input type="text" name="ruc" class="form-control" value="<?= h($gym['ruc'] ?? '') ?>" maxlength="11">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= h($gym['telefono'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" value="<?= h($gym['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= h($gym['direccion'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Datos</button>
        </form>
    </div>

    <!-- ── Logo ── -->
    <div class="card" style="margin-bottom: 1.25rem;">
        <h3 class="text-h3" style="margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
            <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Logo del Gimnasio
        </h3>
        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
            <?php if ($gym['logo']): ?>
            <img src="/gymsuite/uploads/fotos/<?= h($gym['logo']) ?>" alt="Logo" style="height:4rem;border-radius:0.5rem;border:1px solid #333;">
            <?php else: ?>
            <div style="width:5rem;height:4rem;border-radius:0.5rem;border:2px dashed #333;display:flex;align-items:center;justify-content:center;">
                <svg style="width:1.5rem;height:1.5rem;color:#444;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" style="flex:1;">
                <input type="hidden" name="action" value="logo">
                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="form-control" style="flex:1;min-width:200px;" required>
                    <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">Subir Logo</button>
                </div>
                <p style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.4rem;">JPG, PNG o WebP — Máx. 2MB</p>
            </form>
        </div>
    </div>

    <!-- ── Método de Acceso ── -->
    <div class="card" style="margin-bottom: 1.25rem;">
        <h3 class="text-h3" style="margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem;">
            <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            Método de Acceso de Socios
        </h3>
        <p style="color:var(--color-text-muted);font-size:0.875rem;margin-bottom:1.25rem;">
            Define cómo identificarán los socios en la recepción. El cambio es inmediato y no borra datos anteriores.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="metodo_acceso">
            <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.25rem;">
                <?php
                $metodos = [
                    'dni'   => ['label'=>'Por DNI / Documento','desc'=>'El recepcionista escribe el DNI. Búsqueda en tiempo real. Ideal para gyms con atención personalizada.','icon'=>'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2'],
                    'clave' => ['label'=>'Por PIN de 4 dígitos','desc'=>'El socio ingresa su clave secreta en un teclado numérico visible. Mayor privacidad y autonomía.','icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                    'qr'    => ['label'=>'Por Código QR','desc'=>'El socio muestra su QR (celular o tarjeta). La cámara lo detecta automáticamente. El más rápido.','icon'=>'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                ];
                foreach ($metodos as $val => $info):
                    $activo = ($val === $metodo_actual);
                ?>
                <label for="met_<?= $val ?>" style="cursor:pointer;">
                    <div style="display:flex;align-items:flex-start;gap:1rem;padding:1rem;border-radius:0.75rem;border:2px solid <?= $activo ? 'var(--color-primary)' : '#333' ?>;background:<?= $activo ? 'rgba(249,115,22,0.05)' : 'transparent' ?>;transition:all 0.2s;">
                        <input type="radio" name="metodo_acceso" id="met_<?= $val ?>" value="<?= $val ?>" <?= $activo ? 'checked' : '' ?> style="margin-top:0.25rem;accent-color:var(--color-primary);">
                        <div style="display:flex;gap:0.75rem;align-items:flex-start;flex:1;">
                            <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:rgba(249,115,22,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $info['icon'] ?>"/></svg>
                            </div>
                            <div>
                                <p style="font-weight:700;margin-bottom:0.15rem;"><?= $info['label'] ?></p>
                                <p style="font-size:0.8rem;color:var(--color-text-muted);"><?= $info['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Cambiar Método</button>
            <a href="/gymsuite/modules/accesos/index.php" class="btn btn-secondary" style="margin-left:0.75rem;">Ir a Control de Accesos</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
