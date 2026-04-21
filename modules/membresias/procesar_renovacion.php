<?php
// modules/membresias/procesar_renovacion.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $socio_id = (int)($_POST['socio_id'] ?? 0);
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'efectivo');

    if ($socio_id && $plan_id) {
        $pdo->beginTransaction();
        try {
            // Verificar plan y obtener días/precio
            $stmt = $pdo->prepare("SELECT duracion_dias, precio FROM planes WHERE id = ? AND gimnasio_id = ? AND activo = 1");
            $stmt->execute([$plan_id, $gimnasio_id]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                throw new Exception("El plan seleccionado no es válido o está inactivo.");
            }

            // Calcular fecha de vencimiento (siempre desde hoy para mayor facilidad en fase 2)
            $fecha_inicio = date('Y-m-d');
            $fecha_fin = date('Y-m-d', strtotime("+$plan[duracion_dias] days"));

            // 1. Inserción de la membresía
            $stmtMem = $pdo->prepare("
                INSERT INTO membresias (socio_id, gimnasio_id, plan_id, fecha_inicio, fecha_fin, estado)
                VALUES (?, ?, ?, ?, ?, 'activa')
            ");
            $stmtMem->execute([$socio_id, $gimnasio_id, $plan_id, $fecha_inicio, $fecha_fin]);
            $membresia_id = $pdo->lastInsertId();

            // 2. Transacción de pago acoplada
            $stmtPago = $pdo->prepare("
                INSERT INTO pagos (socio_id, gimnasio_id, membresia_id, monto, metodo, fecha, notas, registrado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtPago->execute([
                $socio_id, $gimnasio_id, $membresia_id, $plan['precio'], $metodo_pago, date('Y-m-d'), 'Pagado al renovar plan.', $_SESSION['usuario_id']
            ]);

            $pdo->commit();
            mensaje_flash('success', 'Membresía renovada y pago registrado exitosamente.');
            header("Location: ../socios/perfil.php?id=" . $socio_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            mensaje_flash('error', $e->getMessage() ?: 'Error al procesar la renovación.');
            header("Location: ../socios/perfil.php?id=" . $socio_id);
            exit;
        }
    }
}
header("Location: ../socios/index.php");
exit;
