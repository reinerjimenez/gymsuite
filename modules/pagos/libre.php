<?php
// modules/pagos/libre.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);
$gimnasio_id = (int)get_gimnasio_id();
if (!isset($_SESSION['pago_libre_csrf'])) $_SESSION['pago_libre_csrf'] = bin2hex(random_bytes(32));

$stmt = $pdo->prepare('SELECT id, nombre, apellido, dni FROM socios WHERE gimnasio_id = ? AND activo = 1 ORDER BY nombre, apellido');
$stmt->execute([$gimnasio_id]);
$socios = $stmt->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = trim($_POST['monto'] ?? '');
    $concepto = trim($_POST['concepto'] ?? '');
    $socio_id = (int)($_POST['socio_id'] ?? 0);
    $metodo = $_POST['metodo'] ?? '';
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['pago_libre_csrf'], $_POST['csrf'])) {
        $error = 'El formulario venció. Recarga la página.';
    } elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $monto) || (float)$monto <= 0) {
        $error = 'Indica un monto mayor que cero, con hasta dos decimales.';
    } elseif ($concepto === '' && !$socio_id) {
        $error = 'Indica un concepto para el pago sin socio.';
    } elseif (strlen($concepto) > 150) {
        $error = 'El concepto no puede superar 150 caracteres.';
    } elseif (!in_array($metodo, ['efectivo', 'yape', 'plin', 'transferencia'], true)) {
        $error = 'Método de pago no válido.';
    } else {
        if ($socio_id) {
            $stmt = $pdo->prepare('SELECT id FROM socios WHERE id = ? AND gimnasio_id = ? AND activo = 1');
            $stmt->execute([$socio_id, $gimnasio_id]);
            if (!$stmt->fetchColumn()) $error = 'El socio no pertenece a este gimnasio o está inactivo.';
        }
        if ($error === '') {
            $stmt = $pdo->prepare('INSERT INTO pagos (socio_id, gimnasio_id, membresia_id, monto, metodo, fecha, notas, registrado_por, concepto) VALUES (?, ?, NULL, ?, ?, CURDATE(), NULL, ?, ?)');
            $stmt->execute([$socio_id ?: null, $gimnasio_id, $monto, $metodo, $_SESSION['usuario_id'], $concepto === '' ? null : $concepto]);
            mensaje_flash('success', 'Pago libre registrado.');
            header('Location: index.php');
            exit;
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div class="container" style="max-width:650px;margin:0 auto;">
    <div class="flex justify-between items-center mb-6"><div><h1 class="text-h2">Registrar pago libre</h1><p class="text-muted">Cobro sin membresía para un visitante o socio.</p></div><a href="index.php" class="btn btn-secondary" style="min-height:44px;">Volver</a></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= h($error) ?></div><?php endif; ?>
    <div class="card"><form method="post">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['pago_libre_csrf']) ?>">
        <div class="form-group"><label for="monto" class="form-label">Monto (S/) *</label><input id="monto" name="monto" type="number" min="0.01" max="999999.99" step="0.01" class="form-control" value="<?= h($_POST['monto'] ?? '') ?>" required></div>
        <div class="form-group"><label for="concepto" class="form-label">Concepto (obligatorio sin socio)</label><input id="concepto" name="concepto" type="text" maxlength="150" class="form-control" placeholder="Entrada día suelto, clase de prueba..." value="<?= h($_POST['concepto'] ?? '') ?>"></div>
        <div class="form-group"><label for="socio_id" class="form-label">Socio (opcional)</label><select id="socio_id" name="socio_id" class="form-control"><option value="">Público general</option><?php foreach ($socios as $socio): ?><option value="<?= (int)$socio['id'] ?>" <?= (int)($_POST['socio_id'] ?? 0) === (int)$socio['id'] ? 'selected' : '' ?>><?= h($socio['nombre'] . ' ' . $socio['apellido'] . ' · ' . $socio['dni']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="metodo" class="form-label">Método de pago *</label><select id="metodo" name="metodo" class="form-control" required><?php foreach (['efectivo'=>'Efectivo','yape'=>'Yape','plin'=>'Plin','transferencia'=>'Transferencia'] as $valor=>$nombre): ?><option value="<?= $valor ?>" <?= ($_POST['metodo'] ?? 'efectivo') === $valor ? 'selected' : '' ?>><?= $nombre ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-primary" style="width:100%;min-height:44px;">Registrar pago</button>
    </form></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
