-- Archivo: 006_eliminar_socio.sql
-- Descripción: Registra la anonimización definitiva de socios inactivos.
-- INSTRUCCIONES: Ejecutar manualmente en phpMyAdmin antes de probar este módulo.

ALTER TABLE socios ADD COLUMN eliminado_en DATETIME NULL;
