-- Archivo: 005_pago_libre.sql
-- Descripción: Permite pagos de visitantes sin socio y guarda su concepto.
-- INSTRUCCIONES: Ejecutar manualmente en phpMyAdmin antes de probar el cambio 2.

ALTER TABLE pagos
  MODIFY COLUMN socio_id INT NULL,
  ADD COLUMN concepto VARCHAR(150) NULL;
