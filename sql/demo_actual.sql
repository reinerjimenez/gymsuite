-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql211.infinityfree.com
-- Tiempo de generación: 11-11-2026 a las 10:06:21
-- Versión del servidor: 11.4.13-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_43081289_gym`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesos`
--

CREATE TABLE `accesos` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `metodo_usado` enum('dni','clave','qr','manual') NOT NULL,
  `registrado_por` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `accesos`
--

INSERT INTO `accesos` (`id`, `socio_id`, `gimnasio_id`, `fecha_hora`, `metodo_usado`, `registrado_por`) VALUES
(1, 6, 1, '2026-11-01 15:44:02', 'manual', 2),
(2, 6, 1, '2026-11-02 17:44:32', 'clave', 2),
(3, 6, 1, '2026-11-10 20:36:54', 'manual', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_producto`
--

CREATE TABLE `categorias_producto` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_producto`
--

INSERT INTO `categorias_producto` (`id`, `gimnasio_id`, `nombre`, `activo`) VALUES
(1, 1, 'SUPLEMENTO', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_gym`
--

CREATE TABLE `configuracion_gym` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `metodo_acceso` enum('dni','clave','qr') DEFAULT 'dni',
  `zona_horaria` varchar(50) DEFAULT 'America/Lima'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `configuracion_gym`
--

INSERT INTO `configuracion_gym` (`id`, `gimnasio_id`, `metodo_acceso`, `zona_horaria`) VALUES
(1, 1, 'dni', 'America/Lima');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejercicios`
--

CREATE TABLE `ejercicios` (
  `id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `series` int(11) DEFAULT NULL,
  `repeticiones` varchar(20) DEFAULT NULL,
  `descanso_segundos` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ejercicios`
--

INSERT INTO `ejercicios` (`id`, `rutina_id`, `nombre`, `series`, `repeticiones`, `descanso_segundos`, `notas`, `orden`) VALUES
(1, 1, 'Sentadilla con barra', 4, '10-12', 90, 'Mantén la espalda recta y rodillas alineadas.', 1),
(2, 1, 'Press de banca', 4, '10-12', 90, 'Agarre medio, baja la barra hasta el pecho.', 2),
(3, 1, 'Peso muerto convencional', 3, '8', 120, 'Caderas hacia atrás, espalda neutra.', 3),
(4, 1, 'Jalón al pecho', 3, '12', 60, 'Codos hacia abajo en el movimiento.', 4),
(5, 1, 'Press militar', 3, '10', 75, 'Core apretado, sin arquear la espalda.', 5),
(6, 1, 'Curl de bíceps', 3, '12-15', 60, 'Codos fijos a los costados.', 6),
(7, 1, 'Extensiones de tríceps', 3, '12-15', 60, 'Polea alta o mancuerna.', 7),
(8, 1, 'Plancha abdominal', 3, '45 seg', 45, 'Cuerpo alineado de cabeza a talones.', 8),
(9, 2, 'Sentadilla con mancuernas', 4, '15', 60, 'Pies a la altura de los hombros.', 1),
(10, 2, 'Hip Thrust', 4, '15-20', 60, 'Aprieta el glúteo en la parte alta.', 2),
(11, 2, 'Peso muerto rumano', 3, '12', 75, 'Enfocado en isquiotibiales y glúteos.', 3),
(12, 2, 'Prensa de piernas', 3, '15', 60, 'Pies en posición media, no bloquees las rodillas.', 4),
(13, 2, 'Remo con mancuerna', 3, '12', 60, 'Espalda recta, codo cerca del cuerpo.', 5),
(14, 2, 'Press de hombros mancuerna', 3, '12', 60, 'Peso moderado para control.', 6),
(15, 2, 'Abducción lateral con banda', 3, '20', 45, 'Banda a la altura de tobillos.', 7),
(16, 2, 'Plancha lateral', 3, '30 seg', 45, 'Cada lado. Cadera elevada.', 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gimnasios`
--

CREATE TABLE `gimnasios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `ruc` varchar(11) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `estado` enum('activo','suspendido') DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gimnasios`
--

INSERT INTO `gimnasios` (`id`, `nombre`, `ruc`, `direccion`, `telefono`, `email`, `logo`, `estado`, `created_at`) VALUES
(1, 'Simetric GYM', '', 'Jr. Aucayacu #244 - 2do Piso Ex SENATI, Tingo María, Perú', '+51 929 845 249', 'georgejulca974@gmail.com', 'logo_6ae911814ce29.webp', 'activo', '2026-10-06 16:42:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `membresias`
--

CREATE TABLE `membresias` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activa','vencida','cancelada') DEFAULT 'activa',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `membresias`
--

INSERT INTO `membresias` (`id`, `socio_id`, `gimnasio_id`, `plan_id`, `fecha_inicio`, `fecha_fin`, `estado`, `created_at`) VALUES
(1, 1, 1, 1, '2026-10-01', '2026-10-31', 'activa', '2026-11-01 19:19:27'),
(2, 2, 1, 1, '2026-10-02', '2026-11-01', 'activa', '2026-11-01 19:31:24'),
(3, 3, 1, 1, '2026-10-02', '2026-11-01', 'activa', '2026-11-01 19:37:15'),
(4, 4, 1, 2, '2026-10-05', '2026-11-04', 'activa', '2026-11-01 19:41:58'),
(5, 5, 1, 2, '2026-10-05', '2026-11-04', 'activa', '2026-11-01 19:43:22'),
(6, 6, 1, 1, '2026-10-05', '2026-11-04', 'activa', '2026-11-01 19:44:47'),
(7, 7, 1, 1, '2026-10-06', '2026-11-05', 'activa', '2026-11-01 19:47:20'),
(8, 9, 1, 1, '2026-10-06', '2026-11-05', 'activa', '2026-11-01 19:52:31'),
(9, 10, 1, 1, '2026-10-06', '2026-11-05', 'activa', '2026-11-01 19:54:46'),
(10, 11, 1, 1, '2026-10-07', '2026-11-06', 'activa', '2026-11-01 20:34:46'),
(11, 12, 1, 1, '2026-10-10', '2026-11-09', 'activa', '2026-11-01 20:37:59'),
(12, 13, 1, 1, '2026-10-16', '2026-11-15', 'activa', '2026-11-01 20:50:39'),
(13, 14, 1, 1, '2026-10-15', '2026-11-14', 'activa', '2026-11-01 21:24:42'),
(14, 15, 1, 1, '2026-10-15', '2026-11-14', 'activa', '2026-11-01 21:26:17'),
(15, 16, 1, 1, '2026-10-17', '2026-11-16', 'activa', '2026-11-01 21:27:46'),
(16, 17, 1, 1, '2026-10-19', '2026-11-18', 'activa', '2026-11-01 21:29:07'),
(17, 18, 1, 1, '2026-10-19', '2026-11-18', 'activa', '2026-11-01 21:32:51'),
(18, 19, 1, 1, '2026-10-20', '2026-11-19', 'activa', '2026-11-01 21:34:14'),
(19, 20, 1, 1, '2026-10-14', '2026-11-13', 'activa', '2026-11-01 21:35:46'),
(20, 21, 1, 1, '2026-10-22', '2026-11-21', 'activa', '2026-11-01 21:36:47'),
(21, 22, 1, 2, '2026-10-23', '2026-11-22', 'activa', '2026-11-01 21:38:07'),
(22, 3, 1, 2, '2026-11-02', '2026-12-02', 'activa', '2026-11-02 22:36:16'),
(23, 2, 1, 2, '2026-11-02', '2026-12-02', 'activa', '2026-11-02 22:36:47'),
(24, 1, 1, 1, '2026-11-02', '2026-12-02', 'activa', '2026-11-03 00:14:11'),
(25, 23, 1, 1, '2026-11-05', '2026-12-05', 'activa', '2026-11-05 22:53:58'),
(26, 24, 1, 1, '2026-11-01', '2026-12-01', 'activa', '2026-11-05 23:02:02'),
(27, 25, 1, 1, '2026-11-29', '2026-12-29', 'activa', '2026-11-05 23:10:22'),
(28, 26, 1, 1, '2026-11-06', '2026-12-06', 'activa', '2026-11-05 23:12:36'),
(29, 27, 1, 1, '2026-11-02', '2026-12-02', 'activa', '2026-11-05 23:15:27'),
(30, 28, 1, 3, '2026-11-07', '2026-12-07', 'activa', '2026-11-08 01:50:56'),
(31, 29, 1, 1, '2026-11-09', '2026-12-09', 'activa', '2026-11-09 14:51:01'),
(32, 30, 1, 1, '2026-11-09', '2026-12-09', 'activa', '2026-11-09 15:16:21'),
(33, 31, 1, 1, '2026-10-15', '2026-11-14', 'activa', '2026-11-09 15:17:50'),
(34, 32, 1, 1, '2026-10-15', '2026-11-14', 'activa', '2026-11-09 15:18:42'),
(35, 33, 1, 1, '2026-10-19', '2026-11-18', 'activa', '2026-11-09 15:20:13'),
(36, 34, 1, 1, '2026-10-20', '2026-11-19', 'activa', '2026-11-09 15:21:20'),
(37, 35, 1, 1, '2026-11-07', '2026-12-07', 'activa', '2026-11-09 15:25:12'),
(38, 36, 1, 1, '2026-12-14', '2027-01-13', 'activa', '2026-11-09 16:12:57'),
(39, 37, 1, 1, '2026-11-09', '2026-12-09', 'activa', '2026-11-09 16:50:06'),
(40, 38, 1, 1, '2026-11-09', '2026-12-09', 'activa', '2026-11-09 16:51:45'),
(41, 39, 1, 1, '2026-11-07', '2026-12-07', 'activa', '2026-11-09 19:44:53'),
(42, 40, 1, 1, '2026-11-06', '2026-12-06', 'activa', '2026-11-09 20:34:52'),
(43, 41, 1, 1, '2026-10-15', '2026-11-14', 'activa', '2026-11-09 21:32:27'),
(44, 42, 1, 1, '2026-10-29', '2026-11-28', 'activa', '2026-11-09 21:36:17'),
(45, 43, 1, 1, '2026-10-29', '2026-11-28', 'activa', '2026-11-09 21:38:04'),
(46, 44, 1, 6, '2026-10-29', '2026-11-28', 'activa', '2026-11-09 21:39:38'),
(47, 45, 1, 5, '2026-10-29', '2026-11-13', 'activa', '2026-11-09 21:40:44'),
(48, 46, 1, 6, '2026-10-29', '2026-11-28', 'activa', '2026-11-09 21:41:52'),
(49, 47, 1, 5, '2026-10-30', '2026-11-14', 'activa', '2026-11-09 21:44:04'),
(50, 48, 1, 1, '2026-10-22', '2026-11-21', 'activa', '2026-11-09 21:45:33'),
(51, 49, 1, 1, '2026-10-24', '2026-11-23', 'activa', '2026-11-09 21:47:33'),
(52, 50, 1, 2, '2026-10-23', '2026-11-22', 'activa', '2026-11-09 21:49:37'),
(53, 51, 1, 2, '2026-10-22', '2026-11-21', 'activa', '2026-11-09 21:50:41'),
(54, 52, 1, 1, '2026-10-22', '2026-11-21', 'activa', '2026-11-09 21:51:55'),
(55, 53, 1, 1, '2026-10-14', '2026-11-13', 'activa', '2026-11-09 21:53:59'),
(56, 54, 1, 1, '2026-10-10', '2026-11-09', 'activa', '2026-11-09 21:57:01'),
(57, 55, 1, 2, '2026-11-09', '2026-12-09', 'activa', '2026-11-10 02:19:00'),
(58, 56, 1, 2, '2026-11-09', '2026-12-09', 'activa', '2026-11-10 02:21:08'),
(59, 57, 1, 1, '2026-11-10', '2026-12-10', 'activa', '2026-11-10 17:05:34'),
(60, 58, 1, 1, '2026-11-10', '2026-12-10', 'activa', '2026-11-10 17:09:32'),
(61, 11, 1, 1, '2026-11-10', '2026-12-10', 'activa', '2026-11-11 01:32:53'),
(62, 6, 1, 6, '2026-11-10', '2026-12-10', 'activa', '2026-11-11 01:34:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_stock`
--

CREATE TABLE `movimientos_stock` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `venta_id` int(11) DEFAULT NULL,
  `tipo` enum('venta','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `nota` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_stock`
--

INSERT INTO `movimientos_stock` (`id`, `gimnasio_id`, `producto_id`, `venta_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `registrado_por`, `fecha_hora`, `nota`) VALUES
(1, 1, 1, NULL, 'ajuste', 2, 0, 2, 2, '2026-11-02 18:53:25', 'conteo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) DEFAULT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `membresia_id` int(11) DEFAULT NULL,
  `monto` decimal(8,2) NOT NULL,
  `metodo` enum('efectivo','yape','plin','transferencia','otro') DEFAULT 'efectivo',
  `fecha` date NOT NULL,
  `notas` text DEFAULT NULL,
  `registrado_por` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `concepto` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `socio_id`, `gimnasio_id`, `membresia_id`, `monto`, `metodo`, `fecha`, `notas`, `registrado_por`, `created_at`, `concepto`) VALUES
(1, 1, 1, 1, '60.00', 'yape', '2026-10-01', 'Inscripción y primer plan.', 2, '2026-11-01 19:19:27', NULL),
(2, 2, 1, 2, '60.00', 'efectivo', '2026-10-02', 'Inscripción y primer plan.', 2, '2026-11-01 19:31:24', NULL),
(3, 3, 1, 3, '60.00', 'efectivo', '2026-10-02', 'Inscripción y primer plan.', 2, '2026-11-01 19:37:15', NULL),
(4, 4, 1, 4, '100.00', 'yape', '2026-10-05', 'Inscripción y primer plan.', 2, '2026-11-01 19:41:58', NULL),
(5, 6, 1, 6, '60.00', 'yape', '2026-10-05', 'Inscripción y primer plan.', 2, '2026-11-01 19:44:47', NULL),
(6, 7, 1, 7, '60.00', 'yape', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:47:20', NULL),
(7, 9, 1, 8, '60.00', 'efectivo', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:52:31', NULL),
(8, 10, 1, 9, '60.00', 'yape', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:54:46', NULL),
(9, 11, 1, 10, '60.00', 'efectivo', '2026-10-07', 'Inscripción y primer plan.', 2, '2026-11-01 20:34:46', NULL),
(10, 12, 1, 11, '60.00', 'yape', '2026-10-10', 'Inscripción y primer plan.', 2, '2026-11-01 20:37:59', NULL),
(11, 13, 1, 12, '60.00', 'yape', '2026-10-16', 'Inscripción y primer plan.', 2, '2026-11-01 20:50:39', NULL),
(12, 14, 1, 13, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-01 21:24:42', NULL),
(13, 15, 1, 14, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-01 21:26:17', NULL),
(14, 16, 1, 15, '60.00', 'yape', '2026-10-17', 'Inscripción y primer plan.', 2, '2026-11-01 21:27:46', NULL),
(15, 17, 1, 16, '60.00', 'yape', '2026-10-19', 'Inscripción y primer plan.', 2, '2026-11-01 21:29:07', NULL),
(16, 18, 1, 17, '60.00', 'efectivo', '2026-10-19', 'Inscripción y primer plan.', 2, '2026-11-01 21:32:51', NULL),
(17, 19, 1, 18, '60.00', 'yape', '2026-10-20', 'Inscripción y primer plan.', 2, '2026-11-01 21:34:14', NULL),
(18, 20, 1, 19, '60.00', 'yape', '2026-10-14', 'Inscripción y primer plan.', 2, '2026-11-01 21:35:46', NULL),
(19, 21, 1, 20, '60.00', 'yape', '2026-10-22', 'Inscripción y primer plan.', 2, '2026-11-01 21:36:47', NULL),
(20, 3, 1, 22, '100.00', 'efectivo', '2026-11-02', 'Pagado al renovar plan.', 2, '2026-11-02 22:36:16', NULL),
(21, 1, 1, 24, '60.00', 'efectivo', '2026-11-02', 'Pagado al renovar plan.', 2, '2026-11-03 00:14:11', NULL),
(22, 23, 1, 25, '60.00', 'yape', '2026-11-05', 'Inscripción y primer plan.', 2, '2026-11-05 22:53:58', NULL),
(23, 24, 1, 26, '60.00', 'yape', '2026-11-01', 'Inscripción y primer plan.', 2, '2026-11-05 23:02:02', NULL),
(24, 25, 1, 27, '60.00', 'efectivo', '2026-11-29', 'Inscripción y primer plan.', 2, '2026-11-05 23:10:22', NULL),
(25, 26, 1, 28, '60.00', 'efectivo', '2026-11-06', 'Inscripción y primer plan.', 2, '2026-11-05 23:12:36', NULL),
(26, 27, 1, 29, '60.00', 'efectivo', '2026-11-02', 'Inscripción y primer plan.', 2, '2026-11-05 23:15:27', NULL),
(27, 28, 1, 30, '140.00', 'yape', '2026-11-07', 'Inscripción y primer plan.', 2, '2026-11-08 01:50:56', NULL),
(28, 29, 1, 31, '60.00', 'efectivo', '2026-11-09', 'Inscripción y primer plan.', 2, '2026-11-09 14:51:01', NULL),
(29, 30, 1, 32, '60.00', 'yape', '2026-11-09', 'Inscripción y primer plan.', 2, '2026-11-09 15:16:21', NULL),
(30, 31, 1, 33, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-09 15:17:50', NULL),
(31, 32, 1, 34, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-09 15:18:42', NULL),
(32, 33, 1, 35, '60.00', 'yape', '2026-10-19', 'Inscripción y primer plan.', 2, '2026-11-09 15:20:13', NULL),
(33, 34, 1, 36, '60.00', 'yape', '2026-10-20', 'Inscripción y primer plan.', 2, '2026-11-09 15:21:20', NULL),
(34, 35, 1, 37, '60.00', 'efectivo', '2026-11-07', 'Inscripción y primer plan.', 2, '2026-11-09 15:25:12', NULL),
(35, 36, 1, 38, '60.00', 'efectivo', '2026-12-14', 'Inscripción y primer plan.', 2, '2026-11-09 16:12:57', NULL),
(36, 37, 1, 39, '60.00', 'yape', '2026-11-09', 'Inscripción y primer plan.', 2, '2026-11-09 16:50:06', NULL),
(37, 38, 1, 40, '60.00', 'yape', '2026-11-09', 'Inscripción y primer plan.', 2, '2026-11-09 16:51:45', NULL),
(38, 39, 1, 41, '60.00', 'yape', '2026-11-07', 'Inscripción y primer plan.', 2, '2026-11-09 19:44:53', NULL),
(39, 40, 1, 42, '60.00', 'yape', '2026-11-06', 'Inscripción y primer plan.', 2, '2026-11-09 20:34:52', NULL),
(40, 41, 1, 43, '60.00', 'efectivo', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-09 21:32:27', NULL),
(41, 42, 1, 44, '60.00', 'efectivo', '2026-10-29', 'Inscripción y primer plan.', 2, '2026-11-09 21:36:17', NULL),
(42, 43, 1, 45, '60.00', 'efectivo', '2026-10-29', 'Inscripción y primer plan.', 2, '2026-11-09 21:38:04', NULL),
(43, 44, 1, 46, '40.00', 'yape', '2026-10-29', 'Inscripción y primer plan.', 2, '2026-11-09 21:39:38', NULL),
(44, 45, 1, 47, '40.00', 'yape', '2026-10-29', 'Inscripción y primer plan.', 2, '2026-11-09 21:40:44', NULL),
(45, 46, 1, 48, '40.00', 'yape', '2026-10-29', 'Inscripción y primer plan.', 2, '2026-11-09 21:41:52', NULL),
(46, 47, 1, 49, '40.00', 'yape', '2026-10-30', 'Inscripción y primer plan.', 2, '2026-11-09 21:44:04', NULL),
(47, 48, 1, 50, '60.00', 'efectivo', '2026-10-22', 'Inscripción y primer plan.', 2, '2026-11-09 21:45:33', NULL),
(48, 49, 1, 51, '60.00', 'yape', '2026-10-24', 'Inscripción y primer plan.', 2, '2026-11-09 21:47:33', NULL),
(49, 50, 1, 52, '100.00', 'efectivo', '2026-10-23', 'Inscripción y primer plan.', 2, '2026-11-09 21:49:37', NULL),
(50, 52, 1, 54, '60.00', 'yape', '2026-10-22', 'Inscripción y primer plan.', 2, '2026-11-09 21:51:55', NULL),
(51, 53, 1, 55, '60.00', 'efectivo', '2026-10-14', 'Inscripción y primer plan.', 2, '2026-11-09 21:53:59', NULL),
(52, 54, 1, 56, '60.00', 'yape', '2026-10-10', 'Inscripción y primer plan.', 2, '2026-11-09 21:57:01', NULL),
(53, 56, 1, 58, '100.00', 'efectivo', '2026-11-09', 'Inscripción y primer plan.', 2, '2026-11-10 02:21:08', NULL),
(54, 57, 1, 59, '60.00', 'yape', '2026-11-10', 'Inscripción y primer plan.', 2, '2026-11-10 17:05:34', NULL),
(55, 58, 1, 60, '60.00', 'efectivo', '2026-11-10', 'Inscripción y primer plan.', 2, '2026-11-10 17:09:32', NULL),
(56, 11, 1, 61, '60.00', 'efectivo', '2026-11-10', 'Pagado al renovar plan.', 2, '2026-11-11 01:32:53', NULL),
(57, 6, 1, 62, '40.00', 'yape', '2026-11-10', 'Pagado al renovar plan.', 2, '2026-11-11 01:34:29', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `duracion_dias` int(11) NOT NULL,
  `precio` decimal(8,2) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `gimnasio_id`, `nombre`, `duracion_dias`, `precio`, `activo`) VALUES
(1, 1, 'MENSUAL', 30, '60.00', 1),
(2, 1, 'PAREJA', 30, '100.00', 1),
(3, 1, 'GRUPAL (3)', 30, '140.00', 1),
(4, 1, 'DIARIO', 1, '6.00', 0),
(5, 1, 'QUINCENAL', 15, '40.00', 1),
(6, 1, 'INTERDIARIO', 30, '40.00', 1),
(7, 1, 'BIMESTRAL', 60, '100.00', 1),
(8, 1, 'FIDELIZACION', 30, '50.00', 1),
(9, 1, 'TRIMESTRAL', 90, '140.00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `gimnasio_id`, `categoria_id`, `nombre`, `precio_venta`, `stock_actual`, `stock_minimo`, `imagen`, `activo`, `created_at`) VALUES
(1, 1, 1, 'ANIMAL PACK', '375.00', 2, 2, '632283742ab223c0056e1bfadb2828cf.webp', 1, '2026-11-02 22:33:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutinas`
--

CREATE TABLE `rutinas` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `objetivo` enum('fuerza','cardio','perdida_peso','tonificacion','otro') DEFAULT 'otro',
  `activa` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `rutinas`
--

INSERT INTO `rutinas` (`id`, `gimnasio_id`, `nombre`, `descripcion`, `objetivo`, `activa`) VALUES
(1, 1, 'Rutina Universal — Hombres', 'Rutina de entrenamiento completa orientada a hombres. Enfoque en fuerza funcional y desarrollo muscular general.', 'fuerza', 1),
(2, 1, 'Rutina Universal — Mujeres', 'Rutina de entrenamiento completa orientada a mujeres. Enfoque en tonificación, glúteos y resistencia.', 'tonificacion', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `socios`
--

CREATE TABLE `socios` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(15) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_inscripcion` date NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `clave_acceso` varchar(255) DEFAULT NULL,
  `token_qr` varchar(64) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `nota_interna` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `socios`
--

INSERT INTO `socios` (`id`, `gimnasio_id`, `nombre`, `apellido`, `dni`, `telefono`, `email`, `fecha_nacimiento`, `fecha_inscripcion`, `foto`, `clave_acceso`, `token_qr`, `activo`, `created_at`, `nota_interna`) VALUES
(1, 1, 'Rafael Renzo', 'Tarazona', '', '982311748', '', NULL, '2026-10-01', NULL, '$2y$12$aonGFbCMIrVh8cd9dwxCqOFVil/lrqEgD73CuQsdNC.dASB0DQY5q', 'f30a90679fb5a4e70cb0651b81de87e6', 1, '2026-11-01 19:19:27', NULL),
(2, 1, 'Alejandra', 'Lucia Espejo', '', '917954210', '', NULL, '2026-10-02', NULL, '$2y$12$rhKPyTYIl7YzL3pgPaRq2.C/BBEMGIvdkxakA1aguq9/BMTgJsrxW', '15c99c3a5e307b24e5e1ebdb7c778110', 1, '2026-11-01 19:31:24', NULL),
(3, 1, 'Deivis Jarol', 'Esteban Vargas', '', '917954210', '', NULL, '2026-10-02', NULL, '$2y$12$JCJc7OqnVQk7Coh3w4/mGex4wn0hCmt1Z8N4qnyYR1ubtWCaJjApK', 'ad3334fd3ebbaf4bae2495b8c8f2150a', 1, '2026-11-01 19:37:15', NULL),
(4, 1, 'MARCO ANTONIO', 'TELLO TUHANAMA', '', '975342099', '', NULL, '2026-10-05', NULL, '$2y$12$xQXhxpoBGpzKqVpS8K5gwuualO7njf22DM5DjP4XNE7RWOuCAuReW', '3419c58d379e37b644a1ca609363eac8', 1, '2026-11-01 19:41:58', NULL),
(5, 1, 'DOLY SHUAN', 'MONTALVO', '', '994480803', '', NULL, '2026-10-05', NULL, '$2y$12$Diu71nfpX3TGJx1SUP6keuYMmPDbMZekdo0.O4GncNbhMjLBTZL1m', '055284e7076e539f084c66a35c8ab2eb', 1, '2026-11-01 19:43:22', NULL),
(6, 1, 'REINER', 'JIMENEZ', '', '977480721', '', NULL, '2026-10-05', NULL, '$2y$12$Y2cc2T3RNX9UKhnC5ZQiROyyBZ0UD0kgtTUnxk8v3Cbq5DxjH5KyK', '786747478b57daf464f81bd7efb448eb', 1, '2026-11-01 19:44:47', NULL),
(7, 1, 'JULIO', 'COTRINA', '', '949299633', '', NULL, '2026-10-06', NULL, NULL, 'b130321ec9251729a0ff21b77871ffc6', 1, '2026-11-01 19:47:20', NULL),
(8, 1, 'ANDREA', 'ROBLES', '', '926243309', '', NULL, '2026-10-06', NULL, NULL, 'e5bf9b67303f9e1f428eca709b34239f', 0, '2026-11-01 19:49:50', NULL),
(9, 1, 'ANDREA', 'ROBLES S', '', '926243309', '', NULL, '2026-10-06', NULL, NULL, 'd7f5434b46c9bae6afccd350d5558873', 1, '2026-11-01 19:52:31', NULL),
(10, 1, 'JESENIA', 'SALAZAR', '', '983205808', '', NULL, '2026-10-06', NULL, NULL, '1a29e341e540769ef002b5d5f659185f', 1, '2026-11-01 19:54:46', NULL),
(11, 1, 'JESUS', 'MACEDO', '', '974743715', '', NULL, '2026-10-07', NULL, NULL, '704d89415e4e16323d8fc042d27d4dca', 1, '2026-11-01 20:34:46', NULL),
(12, 1, 'DIEGO', 'GOMEZ', '', '927266132', '', NULL, '2026-10-10', NULL, NULL, '86dc59516b83e0a2519c8101b1ac6fcc', 1, '2026-11-01 20:37:59', NULL),
(13, 1, 'DANIELA', 'PERICHE PAREDES', '', '900168717', '', NULL, '2026-10-16', NULL, NULL, 'f2d4ad9c7027b074a985a0f63bb548d1', 1, '2026-11-01 20:50:39', NULL),
(14, 1, 'YANCY', 'VENTURA', '', '900482159', '', NULL, '2026-10-15', NULL, NULL, 'e75245bcd0c4d06c97eb240f241ba380', 1, '2026-11-01 21:24:42', NULL),
(15, 1, 'DILVER', 'SILVESTRE', '', '911193171', '', NULL, '2026-10-15', NULL, NULL, '3e338b2b3b032890164368503960e5f5', 1, '2026-11-01 21:26:17', NULL),
(16, 1, 'ALLISON', 'CALERO', '', '936949225', '', NULL, '2026-10-17', NULL, NULL, '9a2a86bca6d9e09d16573e32217ca4f0', 1, '2026-11-01 21:27:46', NULL),
(17, 1, 'ELVIS', 'VASQUEZ SOTO', '', '957323744', '', NULL, '2026-10-19', NULL, NULL, '03dc4df27a992448602516860abbec56', 1, '2026-11-01 21:29:07', NULL),
(18, 1, 'RUDY', 'MIÑANO', '', '963026854', '', NULL, '2026-10-19', NULL, NULL, 'e13d3a8492b1dd4d22fce0decad58b0b', 1, '2026-11-01 21:32:51', NULL),
(19, 1, 'JHONNY JESUS', 'COTRINA DORIAN', '', '9917859884', '', NULL, '2026-10-20', NULL, NULL, '6746ef83bc0b2b55d97bcb7414bb3b44', 1, '2026-11-01 21:34:13', NULL),
(20, 1, 'JUAN DAVID', 'CASIMIRO ALVAREZ', '', '922098251', '', NULL, '2026-10-14', NULL, NULL, 'feb2ba29a9286e6ce2fabe2f7a455842', 1, '2026-11-01 21:35:46', NULL),
(21, 1, 'JUNIOR', 'CHIRINOS', '', '906318541', '', NULL, '2026-10-22', NULL, NULL, 'bb22068c04ae0247eb2fa9c5419302a7', 1, '2026-11-01 21:36:47', NULL),
(22, 1, 'JORGE Y  ERLITH MUÑOZ', 'MONCADA', '', '9388715227', '', NULL, '2026-10-23', NULL, NULL, 'bd1a06f88a0c8e8f6ba040eb6be2073f', 1, '2026-11-01 21:38:07', NULL),
(23, 1, 'GROVER', 'SILVA PIO', '61275322', '930531557', '', NULL, '2026-11-05', NULL, NULL, 'f282d833906f80476eb5f5e876abeb7f', 1, '2026-11-05 22:53:58', NULL),
(24, 1, 'RAFAEL RENZO', 'TARAZONA BAUTISTA', '602765622', '926243309', '', NULL, '2026-11-01', NULL, NULL, '044cd0d26a18538ea0d8816277207987', 1, '2026-11-05 23:02:02', NULL),
(25, 1, 'ISAAC', 'ATACHAHUA', '', '9262433009', '', NULL, '2026-11-29', NULL, NULL, '9e79dcb47b8d27b570b94c133227bb8c', 1, '2026-11-05 23:10:22', NULL),
(26, 1, 'ANDREA', 'ROBLES GABRIEL', '', '60275622', '', NULL, '2026-11-06', NULL, NULL, '437a242070c316c784d5dbd46d19a80b', 1, '2026-11-05 23:12:36', NULL),
(27, 1, 'GASPAROV', 'DE LA CRUZ ECHEVARIA', '43241803', '996144552', '', NULL, '2026-11-02', NULL, NULL, '15d145f6667c348cdf45581a010f45f1', 1, '2026-11-05 23:15:27', NULL),
(28, 1, 'RICHARD', 'ESPINOSA EUSEBIO', '45567758', '902242682', '', '1988-09-26', '2026-11-07', NULL, NULL, '3a88f557cc18a659778f86d249699ecb', 1, '2026-11-08 01:50:56', NULL),
(29, 1, 'JESSICA', 'LINO CHAVEZ', '', '932573796', '', '2026-10-14', '2026-11-09', NULL, NULL, '57f21c19c396eaebad94ac0a5a544552', 1, '2026-11-09 14:51:01', NULL),
(30, 1, 'DANIELA', 'PERICHE PAREDES', '75118544', '900168717', '', '2026-10-16', '2026-11-09', NULL, NULL, '7db4131206d672cb6b1cf7504ae076a9', 1, '2026-11-09 15:16:21', NULL),
(31, 1, 'DILVER', 'SILVESTRE', '', '911193171', '', NULL, '2026-10-15', NULL, NULL, '8f80611162cd9ec4070cb5cd9fde4a21', 1, '2026-11-09 15:17:50', NULL),
(32, 1, 'YANCY', 'VENTURA', '', '900482159', '', NULL, '2026-10-15', NULL, NULL, '1d5854311d9dd7fde1c52c7b0310534a', 1, '2026-11-09 15:18:42', NULL),
(33, 1, 'ELVIS', 'VASQUEZ SOTO', '', '957323744', '', NULL, '2026-10-19', NULL, NULL, 'cc14e0faa2095fab4fd913117b6a7edd', 1, '2026-11-09 15:20:13', NULL),
(34, 1, 'JHONNY JESUS', 'COTRINA DORIAN', '', '9917785984', '', NULL, '2026-10-20', NULL, NULL, '5e094984908ffb6b0e3b6ffacec332ad', 1, '2026-11-09 15:21:20', NULL),
(35, 1, 'JESUS', 'MACEDO', '73325146', '974743715', '', '1999-09-01', '2026-11-07', NULL, NULL, '8cea6a3bfa331b4a67e2d443bc07ad6d', 1, '2026-11-09 15:25:12', NULL),
(36, 1, 'JESSICA MARLENI', 'LINO CHAVEZ', '42173644', '932573796', '', NULL, '2026-12-14', NULL, NULL, '0dfb074614a1cffd4b9d6b330966b84f', 1, '2026-11-09 16:12:57', NULL),
(37, 1, 'MILAGROS', 'LLOCLLA MARCELO', '46729349', '949925886', '', '1990-12-23', '2026-11-09', NULL, NULL, 'c3be40c9141de9ef105b4e44536dc967', 1, '2026-11-09 16:50:06', NULL),
(38, 1, 'PATRICIA ROCIO', 'ALMERCO MENDOZA', '44403464', '932580153', '', '1987-08-01', '2026-11-09', NULL, NULL, '49540aaaf985b7999c05ad6c2c03a6aa', 1, '2026-11-09 16:51:45', NULL),
(39, 1, 'ELIZABETH', 'HUAMAN ZAMBRANO', '43905662', '932047691', '', '1986-10-12', '2026-11-07', NULL, NULL, '5a5a53e33a3346abf59580fee4184920', 1, '2026-11-09 19:44:53', NULL),
(40, 1, 'LUCY', 'LINO CHAVEZ', '44020261', '933344213', '', NULL, '2026-11-06', NULL, NULL, '1c8929bdc4eed6607395c22d003c38df', 1, '2026-11-09 20:34:52', NULL),
(41, 1, 'HENRRY', 'FLORES', '', '980469869', '', NULL, '2026-10-15', NULL, NULL, 'dc22126bdd73de58ad6074d5d98c4b57', 1, '2026-11-09 21:32:27', NULL),
(42, 1, 'JOSSY', 'ACOSTA', '', '916000925', '', NULL, '2026-10-29', NULL, NULL, 'c9c53aa6f6fcbbbe4c80602384edfd9a', 1, '2026-11-09 21:36:17', NULL),
(43, 1, 'ISAAC', 'ATACHAHUA', '', '926243309', '', NULL, '2026-10-29', NULL, NULL, '36fa67777694ab58cb2950568e0692a7', 1, '2026-11-09 21:38:04', NULL),
(44, 1, 'MIGUEL', 'DURAN FAUSTINO', '', '931448284', '', NULL, '2026-10-29', NULL, NULL, '6a5bcf1fea8fcd2521c1c670fc4ad2b8', 1, '2026-11-09 21:39:38', NULL),
(45, 1, 'JOSE ADRIAN', 'SUARES SANDOVAL', '', '987911258', '', NULL, '2026-10-29', NULL, NULL, '47098cc7aa1647067b4b69131001ec62', 1, '2026-11-09 21:40:44', NULL),
(46, 1, 'HAROY', 'VELA HUAMAN', '', '989870967', '', NULL, '2026-10-29', NULL, NULL, '8882ef2e025d544f2c22351bac13027b', 1, '2026-11-09 21:41:52', NULL),
(47, 1, 'CHRISTIAN', 'RIVERA', '', '935055098', '', NULL, '2026-10-30', NULL, NULL, 'f970af1448e15e104e03db728525d72e', 1, '2026-11-09 21:44:04', NULL),
(48, 1, 'MERCEDES', 'MUÑOZ', '', '9948422624', '', NULL, '2026-10-22', NULL, NULL, '175a77ce2291f2a59bf26708911ad343', 1, '2026-11-09 21:45:33', NULL),
(49, 1, 'REY', 'MONTEINOS', '', '910234175', '', NULL, '2026-10-24', NULL, NULL, '22bb4a826322affe59e71dbbde3830bb', 1, '2026-11-09 21:47:33', NULL),
(50, 1, 'JORGE', 'MONCADA', '', '938871527', '', NULL, '2026-10-23', NULL, NULL, '7891caa7e926cc8d8a01508d014d79d7', 1, '2026-11-09 21:49:37', NULL),
(51, 1, 'ERLITH', 'MUÑOZ', '', '921473001', '', NULL, '2026-10-22', NULL, NULL, '96adda15e19b660b3cf2292aade8c16e', 1, '2026-11-09 21:50:41', NULL),
(52, 1, 'JUNIOR', 'CHIRINOS', '', '906318541', '', NULL, '2026-10-22', NULL, NULL, '417535684368c4ecd329deac9c0157e9', 1, '2026-11-09 21:51:55', NULL),
(53, 1, 'JUAN DAVID', 'CASIMIRO ALVARO', '', '922098251', '', NULL, '2026-10-14', NULL, NULL, 'c744c72cb478204682ab22890538b8de', 1, '2026-11-09 21:53:59', NULL),
(54, 1, 'DIEGO', 'GOMEZ', '', '927266132', '', NULL, '2026-10-10', NULL, NULL, '026f5049cbefd04adf431a585313e961', 1, '2026-11-09 21:57:01', NULL),
(55, 1, 'MARCO ANTONIO', 'TELLO TUANAMA', '', '975342099', '', NULL, '2026-11-09', NULL, NULL, '6585165ba279361673c969b818a65040', 1, '2026-11-10 02:19:00', NULL),
(56, 1, 'DOLLY', 'SHUAN MONTALVO', '', '994480803', '', NULL, '2026-11-09', NULL, NULL, '9006718115f10f034bd9fdbbccbd9e1d', 1, '2026-11-10 02:21:08', NULL),
(57, 1, 'ALAN', 'MORI MARCES', '42152187', '963734021', '', '1982-11-20', '2026-11-10', NULL, NULL, 'e59fd445a81558847eb633117d4282df', 1, '2026-11-10 17:05:34', NULL),
(58, 1, 'JEAN', 'SALAS SANCHEZ', '71447242', '983479317', '', '2000-07-06', '2026-11-10', NULL, NULL, '237b07f7152a0f3247dfae2f8b74cba0', 1, '2026-11-10 17:09:32', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `socio_rutina`
--

CREATE TABLE `socio_rutina` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','recepcionista') NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `gimnasio_id`, `nombre`, `apellido`, `usuario`, `password`, `rol`, `estado`, `created_at`) VALUES
(1, NULL, 'Reiner', 'Jiménez', 'superadmin', '$2y$10$UMsL.6JKIcvgenWTy8n5R.mRnSj15t1Gz2vJVoAsavntMXoEHhOr2', 'superadmin', 1, '2026-10-06 16:40:32'),
(2, 1, 'Admin', 'Simetric GYM', 'simetricgym', '$2y$12$tB4yuU.4acwuvoi0p1BT5.Yr9G2SPY.eL6CFYH64H0xw.D3ZU/WUa', 'admin', 1, '2026-10-06 16:42:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `socio_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `metodo` enum('efectivo','yape','plin','transferencia') NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accesos`
--
ALTER TABLE `accesos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_acceso_fecha` (`gimnasio_id`,`fecha_hora`),
  ADD KEY `idx_acceso_socio` (`socio_id`,`gimnasio_id`);

--
-- Indices de la tabla `categorias_producto`
--
ALTER TABLE `categorias_producto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categoria_gimnasio_nombre` (`gimnasio_id`,`nombre`),
  ADD UNIQUE KEY `uq_categoria_gimnasio_id` (`gimnasio_id`,`id`);

--
-- Indices de la tabla `configuracion_gym`
--
ALTER TABLE `configuracion_gym`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_id` (`gimnasio_id`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_detalle_producto` (`gimnasio_id`,`producto_id`),
  ADD KEY `gimnasio_id` (`gimnasio_id`,`venta_id`);

--
-- Indices de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rutina_id` (`rutina_id`);

--
-- Indices de la tabla `gimnasios`
--
ALTER TABLE `gimnasios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `membresias`
--
ALTER TABLE `membresias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `idx_mem_estado` (`gimnasio_id`,`estado`),
  ADD KEY `idx_mem_fecha_fin` (`gimnasio_id`,`fecha_fin`),
  ADD KEY `idx_mem_socio_gim_fecha` (`socio_id`,`gimnasio_id`,`fecha_fin`);

--
-- Indices de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimiento_producto` (`gimnasio_id`,`producto_id`,`fecha_hora`),
  ADD KEY `gimnasio_id` (`gimnasio_id`,`venta_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_pagos_fecha` (`gimnasio_id`,`fecha`),
  ADD KEY `idx_pagos_socio` (`socio_id`,`gimnasio_id`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_id` (`gimnasio_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_producto_gimnasio_id` (`gimnasio_id`,`id`),
  ADD KEY `idx_producto_categoria` (`gimnasio_id`,`categoria_id`,`activo`);

--
-- Indices de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_id` (`gimnasio_id`);

--
-- Indices de la tabla `socios`
--
ALTER TABLE `socios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_qr` (`token_qr`),
  ADD KEY `idx_socio_dni` (`gimnasio_id`,`dni`),
  ADD KEY `idx_socio_nombre` (`gimnasio_id`,`nombre`,`apellido`),
  ADD KEY `idx_socio_activo` (`gimnasio_id`,`activo`);

--
-- Indices de la tabla `socio_rutina`
--
ALTER TABLE `socio_rutina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `socio_id` (`socio_id`),
  ADD KEY `rutina_id` (`rutina_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `gimnasio_id` (`gimnasio_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_venta_gimnasio_id` (`gimnasio_id`,`id`),
  ADD KEY `idx_venta_fecha` (`gimnasio_id`,`fecha_hora`),
  ADD KEY `idx_venta_socio` (`gimnasio_id`,`socio_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accesos`
--
ALTER TABLE `accesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `categorias_producto`
--
ALTER TABLE `categorias_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion_gym`
--
ALTER TABLE `configuracion_gym`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `gimnasios`
--
ALTER TABLE `gimnasios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `membresias`
--
ALTER TABLE `membresias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `socios`
--
ALTER TABLE `socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `socio_rutina`
--
ALTER TABLE `socio_rutina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`gimnasio_id`,`venta_id`) REFERENCES `ventas` (`gimnasio_id`, `id`),
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`gimnasio_id`,`producto_id`) REFERENCES `productos` (`gimnasio_id`, `id`);

--
-- Filtros para la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD CONSTRAINT `movimientos_stock_ibfk_1` FOREIGN KEY (`gimnasio_id`,`producto_id`) REFERENCES `productos` (`gimnasio_id`, `id`),
  ADD CONSTRAINT `movimientos_stock_ibfk_2` FOREIGN KEY (`gimnasio_id`,`venta_id`) REFERENCES `ventas` (`gimnasio_id`, `id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`gimnasio_id`,`categoria_id`) REFERENCES `categorias_producto` (`gimnasio_id`, `id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
