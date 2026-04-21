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
    $pin_manual = trim($_POST['pin'] ?? '');

    // Validación básica
    if (empty($nombre) || empty($apellido) || empty($dni) || empty($pin_manual)) {
        mensaje_flash('error', 'Nombre, Apellido, DNI y PIN son obligatorios.');
    } elseif (!preg_match('/^[0-9]{4}$/', $pin_manual)) {
        mensaje_flash('error', 'El PIN de acceso debe ser un número de exactamente 4 dígitos.');
    } else {
        // Verificar duplicados
        $stmt = $pdo->prepare("SELECT id FROM socios WHERE dni = ? AND gimnasio_id = ?");
        $stmt->execute([$dni, $gimnasio_id]);
        if ($stmt->fetch()) {
            mensaje_flash('error', "El DNI $dni ya se encuentra registrado.");
        } else {
            // Utilizar PIN ingresado manualmente
            $clave_acceso = password_hash($pin_manual, PASSWORD_DEFAULT);
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

                // Mensaje SUCCESS
                mensaje_flash('success', "
                    <div class='mb-2'><strong>¡Socio Registrado Exitosamente!</strong></div>
                    <div class='text-sm mt-1 text-green-200'>El PIN configurado manualmente ya se encuentra encriptado en el sistema de manera segura.</div>
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

            <hr style="border-color: #262626; margin: 2.5rem 0 1.5rem 0;">

            <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; color: white; font-size: 1.25rem; font-weight: bold;">
                <svg style="width: 1.5rem; height: 1.5rem; color: #a1a1aa;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                Configuración de Acceso
            </h3>
            
            <style>
                .pin-glass-card {
                    background: linear-gradient(145deg, rgba(24, 24, 27, 0.9) 0%, rgba(9, 9, 11, 0.95) 100%);
                    border: 1px solid rgba(63, 63, 70, 0.4);
                    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.8);
                    transition: all 0.3s ease;
                    padding: 1.75rem;
                    border-radius: 0.75rem;
                    margin-bottom: 2rem;
                    position: relative;
                    overflow: hidden;
                    display: flex;
                    align-items: center;
                    gap: 1.5rem;
                }
                .pin-glass-card:hover {
                    border-color: rgba(63, 63, 70, 0.8);
                    box-shadow: 0 10px 40px -10px rgba(0,0,0,1);
                }
                .pin-icon-wrapper {
                    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
                    box-shadow: inset 0 1px 1px rgba(255,255,255,0.05), 0 4px 10px rgba(0,0,0,0.5);
                    width: 4.5rem;
                    height: 4.5rem;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid rgba(63, 63, 70, 0.5);
                    flex-shrink: 0;
                }
                .pin-text-block {
                    flex-grow: 1;
                }
                .pin-title-text {
                    display: block;
                    font-size: 1.125rem;
                    font-weight: bold;
                    color: white;
                    margin-bottom: 0.25rem;
                }
                .pin-desc-text {
                    font-size: 0.875rem;
                    color: #9ca3af;
                    margin: 0;
                    line-height: 1.4;
                }
                .pin-input-container {
                    flex-shrink: 0;
                }
                .pin-input-premium {
                    background-color: #000 !important;
                    border: 2px solid #27272a !important;
                    color: #fff !important;
                    font-size: 2.2rem !important;
                    letter-spacing: 0.3em !important;
                    padding: 0 !important;
                    padding-left: 0.3em !important; /* Visual center compensation for tracking */
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    text-align: center !important;
                    box-shadow: inset 0 2px 4px rgba(0,0,0,0.8) !important;
                    height: 4rem !important;
                    width: 14rem !important;
                    border-radius: 0.5rem !important;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: monospace;
                }
                .pin-input-premium:focus {
                    border-color: var(--primary, #e11d48) !important;
                    outline: none !important;
                    box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.2), inset 0 2px 4px rgba(0,0,0,0.8) !important;
                }
                .pin-input-premium::placeholder {
                    color: #3f3f46;
                    letter-spacing: 0.1em !important;
                }
                @media (max-width: 768px) {
                    .pin-glass-card {
                        flex-direction: column;
                        text-align: center;
                        padding: 1.5rem 1rem;
                    }
                    .pin-input-premium {
                        width: 100% !important;
                    }
                }
                .info-banner {
                    background: linear-gradient(90deg, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.3) 100%);
                    border-left: 4px solid var(--primary, #e11d48);
                    padding: 1.25rem;
                    border-radius: 0 0.5rem 0.5rem 0;
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                    display: flex;
                    align-items: flex-start;
                    gap: 1rem;
                    border-top: 1px solid rgba(63, 63, 70, 0.2);
                    border-right: 1px solid rgba(63, 63, 70, 0.2);
                    border-bottom: 1px solid rgba(63, 63, 70, 0.2);
                }
                .info-text-wrapper {
                    font-size: 0.875rem;
                    color: #d1d5db;
                    line-height: 1.5;
                }
                .info-banner-title {
                    color: white;
                    display: block;
                    font-weight: bold;
                    margin-bottom: 0.25rem;
                    font-size: 1rem;
                }
            </style>

            <div class="pin-glass-card">
                <!-- Abstract glowing orb effect -->
                <div style="position: absolute; top: -50%; right: -10%; width: 250px; height: 250px; background: radial-gradient(circle, rgba(225, 29, 72, 0.1) 0%, transparent 70%); border-radius: 50%; pointer-events: none; z-index: 0;"></div>

                <div class="pin-icon-wrapper" style="position: relative; z-index: 10;">
                    <svg style="width: 2rem; height: 2rem; color: var(--primary, #e11d48); filter: drop-shadow(0 0 8px rgba(225,29,72,0.5));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                
                <div class="pin-text-block" style="position: relative; z-index: 10;">
                    <label for="pin" class="pin-title-text">PIN de Seguridad *</label>
                    <p class="pin-desc-text">El socio creará una clave de <strong style="color: #e5e7eb;">4 dígitos</strong> usando el teclado numérico. Esta clave cifrará su acceso.</p>
                </div>

                <div class="pin-input-container" style="position: relative; z-index: 10;">
                    <input type="password" id="pin" name="pin" class="pin-input-premium" maxlength="4" pattern="\d{4}" required placeholder="••••">
                </div>
            </div>

            <hr style="border-color: #262626; margin: 2.5rem 0 1.5rem 0;">

            <h3 style="margin-bottom: 1.5rem; color: white; font-size: 1.25rem; font-weight: bold;">Plan Inicial (Opcional)</h3>
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

            <div class="info-banner">
                <svg style="width: 1.5rem; height: 1.5rem; margin-top: 0.125rem; color: var(--primary, #e11d48); flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="info-text-wrapper">
                    <strong class="info-banner-title">Seguridad Algorítmica</strong>
                    <span style="color: #9ca3af;">El PIN se somete a hash unidireccional y se genera un Código QR único para ingresos por torniquete de forma transparente.</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Registrar y Generar Accesos</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
