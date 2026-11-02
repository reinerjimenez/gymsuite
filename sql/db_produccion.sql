-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql211.infinityfree.com
-- Tiempo de generación: 02-11-2026 a las 09:22:23
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
(1, 6, 1, '2026-11-01 15:44:02', 'manual', 2);

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
(1, 'Simetric GYM', '', 'Jr. Aucayacu #244 - 2do Piso Ex SENATI, Tingo María, Perú', '+51 929 845 249', 'georgejulca974@gmail.com', 'logo_6ac5262b56a40.webp', 'activo', '2026-10-06 16:42:51');

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
(21, 22, 1, 2, '2026-10-23', '2026-11-22', 'activa', '2026-11-01 21:38:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `gimnasio_id` int(11) NOT NULL,
  `membresia_id` int(11) DEFAULT NULL,
  `monto` decimal(8,2) NOT NULL,
  `metodo` enum('efectivo','yape','plin','transferencia','otro') DEFAULT 'efectivo',
  `fecha` date NOT NULL,
  `notas` text DEFAULT NULL,
  `registrado_por` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `socio_id`, `gimnasio_id`, `membresia_id`, `monto`, `metodo`, `fecha`, `notas`, `registrado_por`, `created_at`) VALUES
(1, 1, 1, 1, '60.00', 'yape', '2026-10-01', 'Inscripción y primer plan.', 2, '2026-11-01 19:19:27'),
(2, 2, 1, 2, '60.00', 'efectivo', '2026-10-02', 'Inscripción y primer plan.', 2, '2026-11-01 19:31:24'),
(3, 3, 1, 3, '60.00', 'efectivo', '2026-10-02', 'Inscripción y primer plan.', 2, '2026-11-01 19:37:15'),
(4, 4, 1, 4, '100.00', 'yape', '2026-10-05', 'Inscripción y primer plan.', 2, '2026-11-01 19:41:58'),
(5, 6, 1, 6, '60.00', 'yape', '2026-10-05', 'Inscripción y primer plan.', 2, '2026-11-01 19:44:47'),
(6, 7, 1, 7, '60.00', 'yape', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:47:20'),
(7, 9, 1, 8, '60.00', 'efectivo', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:52:31'),
(8, 10, 1, 9, '60.00', 'yape', '2026-10-06', 'Inscripción y primer plan.', 2, '2026-11-01 19:54:46'),
(9, 11, 1, 10, '60.00', 'efectivo', '2026-10-07', 'Inscripción y primer plan.', 2, '2026-11-01 20:34:46'),
(10, 12, 1, 11, '60.00', 'yape', '2026-10-10', 'Inscripción y primer plan.', 2, '2026-11-01 20:37:59'),
(11, 13, 1, 12, '60.00', 'yape', '2026-10-16', 'Inscripción y primer plan.', 2, '2026-11-01 20:50:39'),
(12, 14, 1, 13, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-01 21:24:42'),
(13, 15, 1, 14, '60.00', 'yape', '2026-10-15', 'Inscripción y primer plan.', 2, '2026-11-01 21:26:17'),
(14, 16, 1, 15, '60.00', 'yape', '2026-10-17', 'Inscripción y primer plan.', 2, '2026-11-01 21:27:46'),
(15, 17, 1, 16, '60.00', 'yape', '2026-10-19', 'Inscripción y primer plan.', 2, '2026-11-01 21:29:07'),
(16, 18, 1, 17, '60.00', 'efectivo', '2026-10-19', 'Inscripción y primer plan.', 2, '2026-11-01 21:32:51'),
(17, 19, 1, 18, '60.00', 'yape', '2026-10-20', 'Inscripción y primer plan.', 2, '2026-11-01 21:34:14'),
(18, 20, 1, 19, '60.00', 'yape', '2026-10-14', 'Inscripción y primer plan.', 2, '2026-11-01 21:35:46'),
(19, 21, 1, 20, '60.00', 'yape', '2026-10-22', 'Inscripción y primer plan.', 2, '2026-11-01 21:36:47');

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
(6, 1, 'INTERDIARIO', 30, '40.00', 1);

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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `socios`
--

INSERT INTO `socios` (`id`, `gimnasio_id`, `nombre`, `apellido`, `dni`, `telefono`, `email`, `fecha_nacimiento`, `fecha_inscripcion`, `foto`, `clave_acceso`, `token_qr`, `activo`, `created_at`) VALUES
(1, 1, 'Rafael Renzo', 'Tarazona', '', '982311748', '', NULL, '2026-10-01', NULL, '$2y$12$aonGFbCMIrVh8cd9dwxCqOFVil/lrqEgD73CuQsdNC.dASB0DQY5q', 'f30a90679fb5a4e70cb0651b81de87e6', 1, '2026-11-01 19:19:27'),
(2, 1, 'Alejandra', 'Lucia Espejo', '', '917954210', '', NULL, '2026-10-02', NULL, '$2y$12$rhKPyTYIl7YzL3pgPaRq2.C/BBEMGIvdkxakA1aguq9/BMTgJsrxW', '15c99c3a5e307b24e5e1ebdb7c778110', 1, '2026-11-01 19:31:24'),
(3, 1, 'Deivis Jarol', 'Esteban Vargas', '', '917954210', '', NULL, '2026-10-02', NULL, '$2y$12$JCJc7OqnVQk7Coh3w4/mGex4wn0hCmt1Z8N4qnyYR1ubtWCaJjApK', 'ad3334fd3ebbaf4bae2495b8c8f2150a', 1, '2026-11-01 19:37:15'),
(4, 1, 'MARCO ANTONIO', 'TELLO TUHANAMA', '', '975342099', '', NULL, '2026-10-05', NULL, '$2y$12$xQXhxpoBGpzKqVpS8K5gwuualO7njf22DM5DjP4XNE7RWOuCAuReW', '3419c58d379e37b644a1ca609363eac8', 1, '2026-11-01 19:41:58'),
(5, 1, 'DOLY SHUAN', 'MONTALVO', '', '994480803', '', NULL, '2026-10-05', NULL, '$2y$12$Diu71nfpX3TGJx1SUP6keuYMmPDbMZekdo0.O4GncNbhMjLBTZL1m', '055284e7076e539f084c66a35c8ab2eb', 1, '2026-11-01 19:43:22'),
(6, 1, 'REINER', 'JIMENEZ', '', '977480721', '', NULL, '2026-10-05', NULL, '$2y$12$Y2cc2T3RNX9UKhnC5ZQiROyyBZ0UD0kgtTUnxk8v3Cbq5DxjH5KyK', '786747478b57daf464f81bd7efb448eb', 1, '2026-11-01 19:44:47'),
(7, 1, 'JULIO', 'COTRINA', '', '949299633', '', NULL, '2026-10-06', NULL, NULL, 'b130321ec9251729a0ff21b77871ffc6', 1, '2026-11-01 19:47:20'),
(8, 1, 'ANDREA', 'ROBLES', '', '926243309', '', NULL, '2026-10-06', NULL, NULL, 'e5bf9b67303f9e1f428eca709b34239f', 0, '2026-11-01 19:49:50'),
(9, 1, 'ANDREA', 'ROBLES S', '', '926243309', '', NULL, '2026-10-06', NULL, NULL, 'd7f5434b46c9bae6afccd350d5558873', 1, '2026-11-01 19:52:31'),
(10, 1, 'JESENIA', 'SALAZAR', '', '983205808', '', NULL, '2026-10-06', NULL, NULL, '1a29e341e540769ef002b5d5f659185f', 1, '2026-11-01 19:54:46'),
(11, 1, 'JESUS', 'MACEDO', '', '974743715', '', NULL, '2026-10-07', NULL, NULL, '704d89415e4e16323d8fc042d27d4dca', 1, '2026-11-01 20:34:46'),
(12, 1, 'DIEGO', 'GOMEZ', '', '927266132', '', NULL, '2026-10-10', NULL, NULL, '86dc59516b83e0a2519c8101b1ac6fcc', 1, '2026-11-01 20:37:59'),
(13, 1, 'DANIELA', 'PERICHE PAREDES', '', '900168717', '', NULL, '2026-10-16', NULL, NULL, 'f2d4ad9c7027b074a985a0f63bb548d1', 1, '2026-11-01 20:50:39'),
(14, 1, 'YANCY', 'VENTURA', '', '900482159', '', NULL, '2026-10-15', NULL, NULL, 'e75245bcd0c4d06c97eb240f241ba380', 1, '2026-11-01 21:24:42'),
(15, 1, 'DILVER', 'SILVESTRE', '', '911193171', '', NULL, '2026-10-15', NULL, NULL, '3e338b2b3b032890164368503960e5f5', 1, '2026-11-01 21:26:17'),
(16, 1, 'ALLISON', 'CALERO', '', '936949225', '', NULL, '2026-10-17', NULL, NULL, '9a2a86bca6d9e09d16573e32217ca4f0', 1, '2026-11-01 21:27:46'),
(17, 1, 'ELVIS', 'VASQUEZ SOTO', '', '957323744', '', NULL, '2026-10-19', NULL, NULL, '03dc4df27a992448602516860abbec56', 1, '2026-11-01 21:29:07'),
(18, 1, 'RUDY', 'MIÑANO', '', '963026854', '', NULL, '2026-10-19', NULL, NULL, 'e13d3a8492b1dd4d22fce0decad58b0b', 1, '2026-11-01 21:32:51'),
(19, 1, 'JHONNY JESUS', 'COTRINA DORIAN', '', '9917859884', '', NULL, '2026-10-20', NULL, NULL, '6746ef83bc0b2b55d97bcb7414bb3b44', 1, '2026-11-01 21:34:13'),
(20, 1, 'JUAN DAVID', 'CASIMIRO ALVAREZ', '', '922098251', '', NULL, '2026-10-14', NULL, NULL, 'feb2ba29a9286e6ce2fabe2f7a455842', 1, '2026-11-01 21:35:46'),
(21, 1, 'JUNIOR', 'CHIRINOS', '', '906318541', '', NULL, '2026-10-22', NULL, NULL, 'bb22068c04ae0247eb2fa9c5419302a7', 1, '2026-11-01 21:36:47'),
(22, 1, 'JORGE Y  ERLITH MUÑOZ', 'MONCADA', '', '9388715227', '', NULL, '2026-10-23', NULL, NULL, 'bd1a06f88a0c8e8f6ba040eb6be2073f', 1, '2026-11-01 21:38:07');

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
-- Indices de la tabla `configuracion_gym`
--
ALTER TABLE `configuracion_gym`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_id` (`gimnasio_id`);

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
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accesos`
--
ALTER TABLE `accesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion_gym`
--
ALTER TABLE `configuracion_gym`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `socios`
--
ALTER TABLE `socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
