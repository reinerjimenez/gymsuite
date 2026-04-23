-- Script para modificar las restricciones de la base de datos (FOREIGN KEYS)
-- Esto permitirá eliminar un socio directamente desde la base de datos
-- (o desde el sistema) y automáticamente eliminará todo su historial asociado (CASCADE)

-- 1. Eliminar las restricciones actuales (RESTRICT por defecto)
ALTER TABLE membresias DROP FOREIGN KEY membresias_ibfk_1;
ALTER TABLE pagos DROP FOREIGN KEY pagos_ibfk_1;
ALTER TABLE accesos DROP FOREIGN KEY accesos_ibfk_1;
ALTER TABLE socio_rutina DROP FOREIGN KEY socio_rutina_ibfk_1;

-- 2. Añadir las nuevas restricciones con "ON DELETE CASCADE"
ALTER TABLE membresias ADD CONSTRAINT membresias_ibfk_1 FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE;
ALTER TABLE pagos ADD CONSTRAINT pagos_ibfk_1 FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE;
ALTER TABLE accesos ADD CONSTRAINT accesos_ibfk_1 FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE;
ALTER TABLE socio_rutina ADD CONSTRAINT socio_rutina_ibfk_1 FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE;
