<?php
// modules/reportes/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo el admin tiene acceso a los reportes

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <h1 class="text-h2" style="margin-bottom:0.25rem;">Reportes y Analíticas</h1>
    <p class="text-muted" style="margin-bottom:2rem;">Selecciona el reporte que deseas visualizar o exportar.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:1.5rem;">
        
        <!-- Reporte de Ingresos -->
        <a href="ingresos.php" class="card" style="text-decoration:none;transition:transform 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='var(--color-primary)'" onmouseout="this.style.transform='none';this.style.borderColor=''">
            <div style="width:3rem;height:3rem;border-radius:0.75rem;background:rgba(34,197,94,0.1);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                <svg style="width:1.5rem;height:1.5rem;color:#22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-h3" style="color:white;margin-bottom:0.5rem;">Reporte de Ingresos</h3>
            <p style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5;">Ingresos detallados por métodos de pago y comparativa mensual del año actual.</p>
        </a>

        <!-- Reporte de Productos más vendidos -->
        <a href="productos.php" class="card" style="text-decoration:none;transition:transform 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='var(--color-primary)'" onmouseout="this.style.transform='none';this.style.borderColor=''">
            <div style="width:3rem;height:3rem;border-radius:0.75rem;background:rgba(168,85,247,0.1);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                <svg style="width:1.5rem;height:1.5rem;color:#a855f7;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="text-h3" style="color:white;margin-bottom:0.5rem;">Productos más vendidos</h3>
            <p style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5;">Unidades e ingresos por producto durante el año.</p>
        </a>

        <!-- Reporte de Asistencia -->
        <a href="asistencia.php" class="card" style="text-decoration:none;transition:transform 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='var(--color-primary)'" onmouseout="this.style.transform='none';this.style.borderColor=''">
            <div style="width:3rem;height:3rem;border-radius:0.75rem;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                <svg style="width:1.5rem;height:1.5rem;color:#3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-h3" style="color:white;margin-bottom:0.5rem;">Reporte de Asistencias</h3>
            <p style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5;">Conteo de accesos por método (DNI, PIN, QR) y listado filtrable por fechas.</p>
        </a>

        <!-- Reporte de Socios y Estados -->
        <a href="socios.php" class="card" style="text-decoration:none;transition:transform 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='var(--color-primary)'" onmouseout="this.style.transform='none';this.style.borderColor=''">
            <div style="width:3rem;height:3rem;border-radius:0.75rem;background:rgba(249,115,22,0.1);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                <svg style="width:1.5rem;height:1.5rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="text-h3" style="color:white;margin-bottom:0.5rem;">Censo Estructurado</h3>
            <p style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5;">Desglose del stock de clientes, distribuciones por plan y listados por vencer.</p>
        </a>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
