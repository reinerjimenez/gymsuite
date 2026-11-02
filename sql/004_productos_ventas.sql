-- Archivo: 004_productos_ventas.sql
-- Descripción: Catálogo, ventas e historial de stock por gimnasio.
-- INSTRUCCIONES: Ejecutar manualmente en phpMyAdmin antes de probar el módulo.
-- Las tablas existentes (gimnasios, socios y usuarios) usan MyISAM en producción.
-- Por ello sus relaciones se validan en PHP; las tablas nuevas usan InnoDB
-- y conservan claves foráneas entre sí para proteger ventas y stock.

CREATE TABLE categorias_producto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categoria_gimnasio_nombre (gimnasio_id, nombre),
  UNIQUE KEY uq_categoria_gimnasio_id (gimnasio_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  categoria_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  precio_venta DECIMAL(10,2) NOT NULL,
  stock_actual INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  imagen VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_producto_gimnasio_id (gimnasio_id, id),
  KEY idx_producto_categoria (gimnasio_id, categoria_id, activo),
  FOREIGN KEY (gimnasio_id, categoria_id) REFERENCES categorias_producto(gimnasio_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  socio_id INT NULL,
  total DECIMAL(12,2) NOT NULL,
  metodo ENUM('efectivo','yape','plin','transferencia') NOT NULL,
  registrado_por INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  UNIQUE KEY uq_venta_gimnasio_id (gimnasio_id, id),
  KEY idx_venta_fecha (gimnasio_id, fecha_hora),
  KEY idx_venta_socio (gimnasio_id, socio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE detalle_venta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  venta_id INT NOT NULL,
  producto_id INT NOT NULL,
  nombre_producto VARCHAR(150) NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  KEY idx_detalle_producto (gimnasio_id, producto_id),
  FOREIGN KEY (gimnasio_id, venta_id) REFERENCES ventas(gimnasio_id, id),
  FOREIGN KEY (gimnasio_id, producto_id) REFERENCES productos(gimnasio_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE movimientos_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  producto_id INT NOT NULL,
  venta_id INT NULL,
  tipo ENUM('venta','ajuste') NOT NULL,
  cantidad INT NOT NULL,
  stock_anterior INT NOT NULL,
  stock_nuevo INT NOT NULL,
  registrado_por INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  nota VARCHAR(255) NULL,
  KEY idx_movimiento_producto (gimnasio_id, producto_id, fecha_hora),
  FOREIGN KEY (gimnasio_id, producto_id) REFERENCES productos(gimnasio_id, id),
  FOREIGN KEY (gimnasio_id, venta_id) REFERENCES ventas(gimnasio_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
