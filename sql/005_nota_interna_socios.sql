-- Archivo: 005_nota_interna_socios.sql
-- Descripción: Agrega un recordatorio interno opcional a los socios.
-- INSTRUCCIONES: Ejecutar manualmente en phpMyAdmin antes de probar el cambio 1.

ALTER TABLE socios ADD COLUMN nota_interna TEXT NULL;
