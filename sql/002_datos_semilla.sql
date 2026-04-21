-- Hash de 'Admin2026' : $2y$10$UMsL.6JKIcvgenWTy8n5R.mRnSj15t1Gz2vJVoAsavntMXoEHhOr2
-- Hash de '1234'      : $2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO

-- 1. Insertar Superadmin (usuario: superadmin, pass: Admin2026)
INSERT INTO usuarios (gimnasio_id, nombre, apellido, usuario, password, rol, estado) 
VALUES (NULL, 'Reiner', 'Jiménez', 'superadmin', '$2y$10$UMsL.6JKIcvgenWTy8n5R.mRnSj15t1Gz2vJVoAsavntMXoEHhOr2', 'superadmin', 1);

-- 2. Insertar Gimnasios (1 con DNI, 1 con QR)
INSERT INTO gimnasios (nombre, ruc, direccion, telefono, email, estado) VALUES
('Titan Fitness', '20123456789', 'Av. Principal 123, Tingo María', '999888777', 'contacto@titanfitness.com', 'activo'),
('Iron Core Gym', '20987654321', 'Calle Las Torres 456, Tingo María', '988777666', 'info@ironcore.com', 'activo');

-- Insertar Configuración de Gimnasios
INSERT INTO configuracion_gym (gimnasio_id, metodo_acceso, zona_horaria) VALUES
(1, 'dni', 'America/Lima'),
(2, 'qr', 'America/Lima');

-- 3. Usuarios Admin y Recepcionista para cada Gym (pass: 1234)
INSERT INTO usuarios (gimnasio_id, nombre, apellido, usuario, password, rol, estado) VALUES
(1, 'Carlos', 'Admin', 'admin_titan', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'admin', 1),
(1, 'Ana', 'Recepción', 'recepcion_titan', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'recepcionista', 1),
(2, 'Miguel', 'Admin', 'admin_iron', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'admin', 1),
(2, 'Luisa', 'Recepción', 'recepcion_iron', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'recepcionista', 1);

-- 4. Planes de membresía
INSERT INTO planes (gimnasio_id, nombre, duracion_dias, precio, activo) VALUES
(1, 'Mensual', 30, 80.00, 1),
(1, 'Trimestral', 90, 220.00, 1),
(2, 'Mensual', 30, 90.00, 1),
(2, 'Anual', 365, 800.00, 1);

-- 5. Socios para Gym 1 (Titan Fitness - DNI) - Solo 4 socios semilla de ejemplo
INSERT INTO socios (gimnasio_id, nombre, apellido, dni, telefono, email, fecha_inscripcion, clave_acceso, token_qr, activo) VALUES
(1, 'Juan', 'Pérez', '70123451', '912345678', 'juan@email.com', '2026-01-01', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6', 1),
(1, 'María', 'López', '70123452', '912345679', 'maria@email.com', '2026-02-15', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6a1', 1),
(1, 'Jorge', 'Gómez', '70123453', '912345680', 'jorge@email.com', '2025-10-10', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'c3d4e5f6g7h8i9j0k1l2m3n4o5p6a1b2', 1),
(1, 'Sofía', 'Castro', '70123454', '912345681', 'sofia@email.com', '2026-04-01', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'd4e5f6g7h8i9j0k1l2m3n4o5p6a1b2c3', 1);

-- 6. Membresías para Gym 1
INSERT INTO membresias (socio_id, gimnasio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES
(1, 1, 1, '2026-04-01', '2026-05-01', 'activa'),       -- Juan: Activa
(2, 1, 2, '2026-02-15', '2026-05-16', 'activa'),       -- María: Activa
(3, 1, 1, '2026-02-01', '2026-03-03', 'vencida'),      -- Jorge: Vencida
(4, 1, 1, '2026-04-01', '2026-04-25', 'activa');       -- Sofía: Por vencer

-- 7. Socios para Gym 2 (Iron Core - QR) - Solo 4 socios semilla
INSERT INTO socios (gimnasio_id, nombre, apellido, dni, telefono, email, fecha_inscripcion, clave_acceso, token_qr, activo) VALUES
(2, 'Pedro', 'Sánchez', '70123455', '912345682', 'pedro@email.com', '2026-01-01', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'e5f6g7h8i9j0k1l2m3n4o5p6a1b2c3d4', 1),
(2, 'Lucía', 'Fernández', '70123456', '912345683', 'lucia@email.com', '2026-02-15', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'f6g7h8i9j0k1l2m3n4o5p6a1b2c3d4e5', 1),
(2, 'Raúl', 'Vargas', '70123457', '912345684', 'raul@email.com', '2025-10-10', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'g7h8i9j0k1l2m3n4o5p6a1b2c3d4e5f6', 1),
(2, 'Diana', 'Torres', '70123458', '912345685', 'diana@email.com', '2026-04-01', '$2y$10$fDKa2FHvqkS5PkGIM5GGaeEFC80z2N7VMSEd.oXuC8H4AHhq0/qmO', 'h8i9j0k1l2m3n4o5p6a1b2c3d4e5f6g7', 1);

-- 8. Membresías para Gym 2
INSERT INTO membresias (socio_id, gimnasio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES
(5, 2, 3, '2026-04-01', '2026-05-01', 'activa'),       -- Pedro: Activa
(6, 2, 4, '2026-02-15', '2027-02-15', 'activa'),       -- Lucía: Activa
(7, 2, 3, '2026-02-01', '2026-03-03', 'vencida'),      -- Raúl: Vencida
(8, 2, 3, '2026-04-01', '2026-04-22', 'activa');       -- Diana: Por vencer

-- 9. Pagos recientes (Algunos de Gym 1)
INSERT INTO pagos (socio_id, gimnasio_id, membresia_id, monto, metodo, fecha, notas, registrado_por) VALUES
(1, 1, 1, 80.00, 'efectivo', '2026-04-01', 'Renovación mensual', 2),
(2, 1, 2, 220.00, 'yape', '2026-02-15', 'Plan trimestral', 2);

-- 10. Accesos recientes
INSERT INTO accesos (socio_id, gimnasio_id, fecha_hora, metodo_usado, registrado_por) VALUES
(1, 1, '2026-04-20 08:30:00', 'dni', 3),
(2, 1, '2026-04-20 09:15:00', 'dni', 3),
(5, 2, '2026-04-20 07:45:00', 'qr', NULL),
(6, 2, '2026-04-20 18:20:00', 'qr', NULL);

-- 11. Rutinas semilla
INSERT INTO rutinas (gimnasio_id, nombre, descripcion, objetivo, activa) VALUES
(1, 'Rutina Full Body Básica', 'Ideal para principiantes, 3 días a la semana.', 'tonificacion', 1),
(2, 'Hipertrofia Tren Superior', 'Enfoque en pecho, espalda y brazos.', 'fuerza', 1);

INSERT INTO ejercicios (rutina_id, nombre, series, repeticiones, descanso_segundos, notas, orden) VALUES
(1, 'Sentadillas', 4, '12-15', 60, 'Mantener espalda recta', 1),
(1, 'Press Banca', 4, '10-12', 90, 'Bajar hasta tocar el pecho', 2),
(2, 'Dominadas', 4, 'Al fallo', 120, 'Agarre prono', 1),
(2, 'Remo con barra', 4, '10-12', 90, 'Espalda a 45 grados', 2);

-- 12. Asignación de rutina
INSERT INTO socio_rutina (socio_id, rutina_id, fecha_asignacion, activa) VALUES
(1, 1, '2026-04-05', 1),
(5, 2, '2026-04-10', 1);
