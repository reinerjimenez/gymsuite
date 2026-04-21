<?php
// modules/recepcionistas/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo el admin puede ver a sus recepcionistas

$gimnasio_id = get_gimnasio_id();

// Procesar baja lógica (estado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_estado') {
    $id_rec = (int)$_POST['id'];
    // Validar que el recepcionista pertenece a este gym
    $stmt = $pdo->prepare("SELECT id, estado FROM usuarios WHERE id = ? AND gimnasio_id = ? AND rol = 'recepcionista'");
    $stmt->execute([$id_rec, $gimnasio_id]);
    $rec = $stmt->fetch();
    
    if ($rec) {
        $nuevo_estado = $rec['estado'] == 1 ? 0 : 1;
        $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $id_rec]);
        mensaje_flash('success', $nuevo_estado == 1 ? 'Recepcionista habilitado.' : 'Recepcionista deshabilitado.');
    }
    header("Location: index.php");
    exit;
}

// Obtener lista de recepcionistas
$stmt = $pdo->prepare("
    SELECT id, nombre, apellido, usuario, estado, created_at
    FROM usuarios 
    WHERE gimnasio_id = ? AND rol = 'recepcionista'
    ORDER BY id DESC
");
$stmt->execute([$gimnasio_id]);
$recepcionistas = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 class="text-h2" style="margin-bottom:0.25rem;">Personal de Recepción</h1>
            <p class="text-muted">Gestiona los accesos del equipo para la aplicación.</p>
        </div>
        <div>
            <a href="crear.php" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Recepcionista
            </a>
        </div>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <?php if (empty($recepcionistas)): ?>
        <div style="text-align:center;color:var(--color-text-muted);padding:3rem 1rem;">
            <svg style="width:3rem;height:3rem;margin:0 auto 1rem;opacity:0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <p>No tienes recepcionistas creados.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario de acceso</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recepcionistas as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div style="width:2.2rem;height:2.2rem;border-radius:50%;background:rgba(249,115,22,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                                    <?= strtoupper(substr($r['nombre'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p style="font-weight:600;"><?= h($r['nombre'] . ' ' . $r['apellido']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-family:monospace;background:rgba(255,255,255,0.05);padding:0.2rem 0.5rem;border-radius:0.25rem;"><?= h($r['usuario']) ?></span></td>
                        <td>
                            <?php if ($r['estado'] == 1): ?>
                                <span class="badge" style="background:rgba(34,197,94,0.1);color:#22c55e;">Activo</span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--color-text-muted);font-size:0.875rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                                <a href="editar.php?id=<?= $r['id'] ?>" class="btn btn-secondary" style="padding:0.4rem 0.6rem;font-size:0.8rem;min-height:30px;">
                                    Editar
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro de cambiar el estado de este recepcionista?');">
                                    <input type="hidden" name="action" value="toggle_estado">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn" style="padding:0.4rem 0.6rem;font-size:0.8rem;min-height:30px;background:<?= $r['estado'] == 1 ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.1)' ?>;color:<?= $r['estado'] == 1 ? '#ef4444' : '#22c55e' ?>;border:none;">
                                        <?= $r['estado'] == 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
