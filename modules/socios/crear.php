<?php
// modules/socios/crear.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();

// Planes disponibles
$stmt = $pdo->prepare("SELECT id, nombre, precio, duracion_dias FROM planes WHERE gimnasio_id = ? AND activo = 1 ORDER BY precio ASC");
$stmt->execute([$gimnasio_id]);
$planes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null; // si vacío que sea nulo

    // Validación básica
    if (empty($nombre) || empty($apellido) || empty($dni)) {
        mensaje_flash('error', 'Nombre, Apellido y DNI son obligatorios.');
    } else {
        // Verificar duplicados
        $stmt = $pdo->prepare("SELECT id FROM socios WHERE dni = ? AND gimnasio_id = ?");
        $stmt->execute([$dni, $gimnasio_id]);
        if ($stmt->fetch()) {
            mensaje_flash('error', "El DNI $dni ya se encuentra registrado.");
        } else {
            // Generar clave aleatoria 4 dígitos
            $pin_aleatorio = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $clave_acceso = password_hash($pin_aleatorio, PASSWORD_DEFAULT);
            $token_qr = generar_token_qr();
            $fecha_inscripcion = date('Y-m-d');
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO socios (gimnasio_id, nombre, apellido, dni, telefono, email, fecha_nacimiento, fecha_inscripcion, clave_acceso, token_qr, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$gimnasio_id, $nombre, $apellido, $dni, $telefono, $email, $fecha_nacimiento, $fecha_inscripcion, $clave_acceso, $token_qr]);
                $socio_id = $pdo->lastInsertId();

                // Si seleccionó un plan inicial
                $plan_id = (int)($_POST['plan_id'] ?? 0);
                $metodo_pago = trim($_POST['metodo_pago'] ?? 'efectivo');
                if ($plan_id) {
                    $stmtPlan = $pdo->prepare("SELECT duracion_dias, precio FROM planes WHERE id = ? AND gimnasio_id = ? AND activo = 1");
                    $stmtPlan->execute([$plan_id, $gimnasio_id]);
                    $plan = $stmtPlan->fetch();
                    if ($plan) {
                        $f_inicio = date('Y-m-d');
                        $f_fin = date('Y-m-d', strtotime("+$plan[duracion_dias] days"));
                        
                        $stmtMem = $pdo->prepare("INSERT INTO membresias (socio_id, gimnasio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, ?, 'activa')");
                        $stmtMem->execute([$socio_id, $gimnasio_id, $plan_id, $f_inicio, $f_fin]);
                        $membresia_id = $pdo->lastInsertId();

                        $stmtPago = $pdo->prepare("INSERT INTO pagos (socio_id, gimnasio_id, membresia_id, monto, metodo, fecha, notas, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtPago->execute([$socio_id, $gimnasio_id, $membresia_id, $plan['precio'], $metodo_pago, date('Y-m-d'), 'Inscripción y primer plan.', $_SESSION['usuario_id']]);
                    }
                }
                
                $pdo->commit();

                // Mensaje SUCCESS con el PIN vital
                mensaje_flash('success', "
                    <div class='mb-2'><strong>¡Socio Registrado Exitosamente!</strong></div>
                    <div class='text-lg'>Su clave de acceso (PIN) inicial es: <b class='text-h2 block mt-1 tracking-[0.2em] '>$pin_aleatorio</b></div>
                    <div class='text-sm mt-2 text-green-200'>Anótelo y entrégueselo al socio. Esta clave ya está encriptada y no volverá a mostrarse.</div>
                ");
                header("Location: perfil.php?id=" . $socio_id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                mensaje_flash('error', 'Ocurrió un error al registrar: ' . $e->getMessage());
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container" style="max-width: 800px; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-h2 mb-1">Registrar Socio</h1>
            <p class="text-muted">Inscribe un nuevo miembro al gimnasio.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="nombre" class="form-label">Nombres *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required autofocus>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="apellido" class="form-label">Apellidos *</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="dni" class="form-label">DNI / Documento *</label>
                    <input type="text" id="dni" name="dni" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="telefono" class="form-label">Teléfono (WhatsApp)</label>
                    <input type="text" id="telefono" name="telefono" class="form-control">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control">
                </div>
            </div>

            <hr class="mb-6" style="border-color: #262626;">

            <h3 class="text-h3 mb-4">Plan Inicial (Opcional)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Asignar Plan Ahora</label>
                    <select name="plan_id" class="form-control">
                        <option value="">-- Registrar sin plan por ahora --</option>
                        <?php foreach($planes as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= h($p['nombre']) ?> — <?= formatear_moneda($p['precio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Método de Pago</label>
                    <select name="metodo_pago" class="form-control">
                        <option value="efectivo">Efectivo Módulo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="otro">Otro Método</option>
                    </select>
                </div>
            </div>

            <div class="bg-gray-900 border border-gray-800 p-4 rounded-lg mb-6 flex items-start gap-3">
                <svg class="text-primary w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm text-gray-400">
                    <strong class="text-white">Seguridad Automática:</strong><br>
                    Al guardar este registro, el sistema generará automáticamente un Código QR seguro y único, así como un PIN secreto de 4 dígitos al azar que se mostrará en pantalla una única vez.
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Registrar y Generar Accesos</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
