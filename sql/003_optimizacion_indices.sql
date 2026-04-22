-- 003_optimizacion_indices.sql
-- Optimización de velocidad para producción (InfinityFree)
-- Este script agrega índices a las tablas para acelerar drásticamente las búsquedas y evitar Full Table Scans.

-- 1. Índices para la tabla 'socios'
-- Acelera la búsqueda en la barra superior y en el control de acceso por DNI
ALTER TABLE socios ADD INDEX idx_socio_dni (gimnasio_id, dni);
ALTER TABLE socios ADD INDEX idx_socio_nombre (gimnasio_id, nombre, apellido);
ALTER TABLE socios ADD INDEX idx_socio_activo (gimnasio_id, activo);

-- 2. Índices para la tabla 'membresias'
-- Acelera la obtención de membresías recientes, dashboard y cálculo de membresías activas/vencidas
ALTER TABLE membresias ADD INDEX idx_mem_estado (gimnasio_id, estado);
ALTER TABLE membresias ADD INDEX idx_mem_fecha_fin (gimnasio_id, fecha_fin);
-- Índice compuesto crítico para evitar N+1 o File Sorts al buscar la última membresía de un socio
ALTER TABLE membresias ADD INDEX idx_mem_socio_gim_fecha (socio_id, gimnasio_id, fecha_fin);

-- 3. Índices para la tabla 'accesos'
-- Acelera el panel de control (Últimas entradas) y reportes estadísticos
ALTER TABLE accesos ADD INDEX idx_acceso_fecha (gimnasio_id, fecha_hora);
ALTER TABLE accesos ADD INDEX idx_acceso_socio (socio_id, gimnasio_id);

-- 4. Índices para la tabla 'pagos'
-- Acelera reportes de ingresos (filtro por año/mes) y el historial de pagos de un socio
ALTER TABLE pagos ADD INDEX idx_pagos_fecha (gimnasio_id, fecha);
ALTER TABLE pagos ADD INDEX idx_pagos_socio (socio_id, gimnasio_id);
