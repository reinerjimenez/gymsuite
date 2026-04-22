<?php
$rol = $_SESSION['rol'] ?? '';
$current_url = $_SERVER['REQUEST_URI'] ?? '';

function nav_active(string $path): string {
    global $current_url;
    return (strpos($current_url, $path) !== false) ? ' active' : '';
}
?>
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo-area">
        <?php if($gym_logo): ?>
            <img src="<?= BASE_URL ?>uploads/fotos/<?= h($gym_logo) ?>" alt="<?= h($gym_nombre) ?>" style="max-height:6rem; max-width:100%; object-fit:contain;">
        <?php else: ?>
            <span class="sidebar-brand-text">Gym<span>Suite</span></span>
        <?php endif; ?>
    </div>

    <!-- Nav scroll area -->
    <div class="sidebar-scroll">

        <?php if(in_array($rol, ['admin', 'recepcionista'])): ?>
        <span class="sidebar-section-label">Principal</span>
        <?php endif; ?>

        <ul class="sidebar-nav-list">
            <!-- Dashboard -->
            <li>
                <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="nav-link<?= nav_active('dashboard') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if(in_array($rol, ['admin', 'recepcionista'])): ?>
            <li>
                <a href="<?= BASE_URL ?>modules/accesos/index.php" class="nav-link<?= nav_active('accesos') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    <span>Control Accesos</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/socios/index.php" class="nav-link<?= nav_active('socios') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Socios</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/pagos/index.php" class="nav-link<?= nav_active('pagos') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Pagos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if($rol === 'admin'): ?>
            <span class="sidebar-section-label" style="margin-top:0.5rem;">Administración</span>
            <li>
                <a href="<?= BASE_URL ?>modules/planes/index.php" class="nav-link<?= nav_active('planes') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Planes</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/rutinas/index.php" class="nav-link<?= nav_active('rutinas') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Rutinas</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/reportes/index.php" class="nav-link<?= nav_active('reportes') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Reportes</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/recepcionistas/index.php" class="nav-link<?= nav_active('recepcionistas') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Recepcionistas</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/configuracion/index.php" class="nav-link<?= nav_active('configuracion') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Configuración</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if($rol === 'superadmin'): ?>
            <span class="sidebar-section-label" style="margin-top:0.5rem;">Sistema</span>
            <li>
                <a href="<?= BASE_URL ?>modules/superadmin/index.php" class="nav-link<?= nav_active('superadmin') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Gimnasios (SaaS)</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if($rol === 'socio'): ?>
            <li>
                <a href="<?= BASE_URL ?>modules/socio/mi_qr.php" class="nav-link<?= nav_active('mi_qr') ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Mi QR de Acceso</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Footer user section -->
    <div class="sidebar-footer">
        <div class="sidebar-user-chip">
            <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <p class="sidebar-user-name"><?= h(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')) ?></p>
                <p class="sidebar-user-role"><?= h($_SESSION['rol'] ?? '') ?></p>
            </div>
        </div>
        <?php if($rol !== 'socio'): ?>
        <a href="<?= BASE_URL ?>modules/dashboard/mi_perfil.php" class="nav-link<?= nav_active('mi_perfil') ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Mi Cuenta</span>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>logout.php" class="nav-link danger">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <button id="toggle_sidebar" class="mobile-toggle-btn" aria-label="Abrir menú">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="header-title"><?= h($gym_nombre) ?></span>
        </div>
        <div class="header-right">
            <div class="header-user">
                <div class="header-avatar"><?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?></div>
                <div class="hidden sm:flex" style="flex-direction:column;line-height:1.3;">
                    <span class="header-user-name"><?= h($_SESSION['nombre'] ?? '') ?></span>
                    <span class="header-user-role"><?= h($_SESSION['rol'] ?? '') ?></span>
                </div>
            </div>
        </div>
    </header>
    <div class="content-body">
        <?php mostrar_flash(); ?>
