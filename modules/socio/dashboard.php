<?php
// modules/socio/dashboard.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['socio']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <h1 class="text-h2 mb-4">Mi Panel</h1>
    <div class="card mb-6">
        <p class="text-muted">Este es el panel del socio. Aquí se mostrará próximamente tu estado de membresía y rutina.</p>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
