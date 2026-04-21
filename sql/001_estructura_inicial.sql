-- Gimnasios registrados en el SaaS
CREATE TABLE gimnasios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  ruc VARCHAR(11),
  direccion VARCHAR(255),
  telefono VARCHAR(20),
  email VARCHAR(100),
  logo VARCHAR(255),
  estado ENUM('activo','suspendido') DEFAULT 'activo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuración individual por gimnasio
CREATE TABLE configuracion_gym (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  metodo_acceso ENUM('dni','clave','qr') DEFAULT 'dni',
  zona_horaria VARCHAR(50) DEFAULT 'America/Lima',
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Todos los usuarios del sistema (todos los roles)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NULL, -- NULL solo para superadmin
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('superadmin','admin','recepcionista') NOT NULL,
  estado TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Socios del gimnasio
CREATE TABLE socios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  dni VARCHAR(15),
  telefono VARCHAR(20),
  email VARCHAR(100),
  fecha_nacimiento DATE,
  fecha_inscripcion DATE NOT NULL,
  foto VARCHAR(255),
  clave_acceso VARCHAR(255), -- 4 dígitos hasheados con password_hash()
  token_qr VARCHAR(64) UNIQUE, -- bin2hex(random_bytes(16))
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Planes de membresía por gimnasio
CREATE TABLE planes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  duracion_dias INT NOT NULL,
  precio DECIMAL(8,2) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Membresías asignadas a socios
CREATE TABLE membresias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  plan_id INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('activa','vencida','cancelada') DEFAULT 'activa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (plan_id) REFERENCES planes(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Pagos registrados
CREATE TABLE pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  membresia_id INT,
  monto DECIMAL(8,2) NOT NULL,
  metodo ENUM('efectivo','yape','plin','transferencia','otro') DEFAULT 'efectivo',
  fecha DATE NOT NULL,
  notas TEXT,
  registrado_por INT NOT NULL, -- usuario_id
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id),
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
);

-- Log de accesos/entradas al gimnasio
CREATE TABLE accesos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  metodo_usado ENUM('dni','clave','qr','manual') NOT NULL,
  registrado_por INT, -- usuario_id del recepcionista/admin, NULL si fue automático por QR
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Plantillas de rutina
CREATE TABLE rutinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  objetivo ENUM('fuerza','cardio','perdida_peso','tonificacion','otro') DEFAULT 'otro',
  activa TINYINT(1) DEFAULT 1,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Ejercicios de cada rutina
CREATE TABLE ejercicios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rutina_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  series INT,
  repeticiones VARCHAR(20), -- puede ser "12" o "12-15" o "Al fallo"
  descanso_segundos INT,
  notas TEXT,
  orden INT DEFAULT 0,
  FOREIGN KEY (rutina_id) REFERENCES rutinas(id)
);

-- Asignación de rutina a socio
CREATE TABLE socio_rutina (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  rutina_id INT NOT NULL,
  fecha_asignacion DATE NOT NULL,
  activa TINYINT(1) DEFAULT 1,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (rutina_id) REFERENCES rutinas(id)
);
