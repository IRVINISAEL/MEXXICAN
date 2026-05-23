-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 22-05-2026 a las 02:43:27
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u315319055_restaurante_fo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `restaurante_id`, `nombre`, `apellido`, `telefono`, `email`, `usuario`, `password`, `activo`, `created_at`) VALUES
(1, 3, 'IRVIN ISAEL', 'MARTINEZ ALEJO', '238 564 5125', 'julian@gmail.com', 'RESTAURANTE', '$2y$10$EuHZ48qoQPkyec52HVoz8uUiq0rgHRk8rGHboKvmciHkBs5xKffBO', 1, '2026-04-14 20:40:53'),
(2, 4, 'Hehe', 'D e e', '5151881', 'bebenenen', 'Nbbebbe', '$2y$10$/YdUwiBPOrCor3f.4YgvUeTrBBRkU4/n4ZvI2P5YJjliG7DH9Uj4m', 1, '2026-05-07 23:42:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` datetime DEFAULT NULL,
  `hora_salida` datetime DEFAULT NULL,
  `horas_trabajadas` decimal(5,2) DEFAULT NULL,
  `horas_extra` decimal(5,2) DEFAULT 0.00,
  `pago_normal` decimal(10,2) DEFAULT NULL,
  `pago_extra` decimal(10,2) DEFAULT 0.00,
  `pago_total` decimal(10,2) DEFAULT NULL,
  `tipo_salida` varchar(20) DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `token_sesion` varchar(100) NOT NULL,
  `estrellas` tinyint(1) NOT NULL,
  `comentario` text DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_puntos`
--

CREATE TABLE `config_puntos` (
  `restaurante_id` int(11) NOT NULL,
  `premio_nombre` varchar(150) DEFAULT 'Premio sorpresa',
  `puntos_meta` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contador_ordenes`
--

CREATE TABLE `contador_ordenes` (
  `fecha` date NOT NULL,
  `ultimo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contador_ordenes`
--

INSERT INTO `contador_ordenes` (`fecha`, `ultimo`) VALUES
('2026-04-18', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `puesto` varchar(80) DEFAULT 'mesero',
  `pin` varchar(255) DEFAULT NULL,
  `sueldo_hora` decimal(10,2) NOT NULL DEFAULT 80.00,
  `hora_extra_mult` decimal(4,2) NOT NULL DEFAULT 1.50,
  `token_qr` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `estado_mesero` varchar(20) DEFAULT 'disponible',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_intentos`
--

CREATE TABLE `login_intentos` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `categoria` varchar(80) DEFAULT 'comida',
  `emoji` varchar(10) DEFAULT '?️',
  `imagen` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu`
--

INSERT INTO `menu` (`id`, `restaurante_id`, `nombre`, `descripcion`, `precio`, `categoria`, `emoji`, `imagen`, `disponible`, `created_at`) VALUES
(1, 3, 'HAmbuerguesa', 'con refresco', 45.00, 'comida', '️', 'uploads/plato_5356900864b8e896.jpg', 1, '2026-04-18 18:35:52'),
(2, 3, 'Hambuerguesa & Papas Fritas', 'con refresco', 150.00, 'comida', '', 'uploads/plato_e9eb56d2788b6f67.jpg', 1, '2026-04-18 18:37:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `momentos`
--

CREATE TABLE `momentos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `lugar` varchar(150) NOT NULL,
  `autor` varchar(100) DEFAULT 'Usuario anónimo',
  `device` varchar(60) DEFAULT '? Mi celular',
  `estrellas` tinyint(1) NOT NULL DEFAULT 5,
  `resena` text DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `likes` int(11) NOT NULL DEFAULT 0,
  `oficial` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `momentos`
--

INSERT INTO `momentos` (`id`, `titulo`, `lugar`, `autor`, `device`, `estrellas`, `resena`, `imagen_path`, `likes`, `oficial`, `activo`, `created_at`) VALUES
(1, 'AQUI CENANDO', 'RESTAURANTE CIELO', 'Usuario anónimo', '📱 Mi celular', 4, 'MUY BUENA', 'uploads/momento_b7473af77449a71c.png', 1, 0, 1, '2026-04-15 18:33:14'),
(2, 'SORTEANDO', 'buenas cosas', 'JOse luisit', '📱 Mi celular', 5, NULL, 'uploads/momento_923ff18157feaf8f.png', 2, 0, 1, '2026-04-18 21:47:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `momentos_likes`
--

CREATE TABLE `momentos_likes` (
  `id` int(11) NOT NULL,
  `momento_id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `momentos_likes`
--

INSERT INTO `momentos_likes` (`id`, `momento_id`, `ip`, `created_at`) VALUES
(5, 1, '2806:10ae:20:b70:9000:7fd8:51f4:e9bd', '2026-04-18 21:46:37'),
(6, 2, '2806:10ae:20:b70:9000:7fd8:51f4:e9bd', '2026-04-18 21:48:22'),
(10, 2, '2806:10ae:20:b70:dc7:74c3:25b:5c58', '2026-04-18 21:50:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `tipo` varchar(50) DEFAULT 'info',
  `leida` tinyint(1) DEFAULT 0,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `mensaje`, `tipo`, `leida`, `creado_en`) VALUES
(1, 49, '👨‍🍳 Tu pedido está en preparación. ¡Ya vamos!', 'estado', 1, '2026-04-18 18:39:07'),
(2, 49, '✅ ¡Tu pedido está listo! El mesero va en camino.', 'estado', 1, '2026-04-18 18:56:44'),
(3, 49, '✅ Tu cuenta fue pagada, ¡gracias por visitarnos!', 'pago', 1, '2026-04-18 19:04:53'),
(4, 49, '⭐ Ganaste 3 puntos · Total: 3/10', 'puntos', 1, '2026-04-18 19:04:53'),
(5, 49, '👨‍🍳 Tu pedido está en preparación. ¡Ya vamos!', 'estado', 1, '2026-04-18 19:05:19'),
(6, 49, '✅ ¡Tu pedido está listo! El mesero va en camino.', 'estado', 0, '2026-04-18 19:05:34'),
(7, 49, '✅ Tu cuenta fue pagada, ¡gracias por visitarnos!', 'pago', 0, '2026-04-18 19:08:05'),
(8, 49, '⭐ Ganaste 3 puntos · Total: 6/10', 'puntos', 0, '2026-04-18 19:08:05'),
(9, 49, '👨‍🍳 Tu pedido está en preparación. ¡Ya vamos!', 'estado', 0, '2026-04-18 19:08:41'),
(10, 49, '✅ ¡Tu pedido está listo! El mesero va en camino.', 'estado', 0, '2026-04-18 19:08:50'),
(11, 49, '✅ Tu cuenta fue pagada, ¡gracias por visitarnos!', 'pago', 0, '2026-04-18 19:09:15'),
(12, 49, '⭐ Ganaste 3 puntos · Total: 9/10', 'puntos', 0, '2026-04-18 19:09:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `numero_orden` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'mesa',
  `mesa_numero` int(11) DEFAULT NULL,
  `mesero_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'pendiente',
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `restaurante_id`, `numero_orden`, `tipo`, `mesa_numero`, `mesero_id`, `total`, `notas`, `estado`, `creado_en`) VALUES
(1, 49, 3, 1, 'mesa', 6, NULL, 150.00, '', 'entregado', '2026-04-18 18:39:03'),
(2, 49, 3, 2, 'mesa', 6, NULL, 150.00, '', 'entregado', '2026-04-18 19:05:11'),
(3, 49, 3, 3, 'mesa', 6, NULL, 150.00, '', 'entregado', '2026-04-18 19:08:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_items`
--

CREATE TABLE `pedido_items` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_items`
--

INSERT INTO `pedido_items` (`id`, `pedido_id`, `menu_id`, `cantidad`, `precio_unit`, `subtotal`) VALUES
(1, 1, 2, 1, 150.00, 150.00),
(2, 2, 2, 1, 150.00, 150.00),
(3, 3, 2, 1, 150.00, 150.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_activos`
--

CREATE TABLE `planes_activos` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `plan` enum('basico','plus','premium') NOT NULL,
  `periodo` enum('mensual','anual') NOT NULL DEFAULT 'mensual',
  `estado` enum('pendiente','activo','vencido','cancelado') NOT NULL DEFAULT 'pendiente',
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `fecha_activacion` datetime DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `planes_activos`
--

INSERT INTO `planes_activos` (`id`, `restaurante_id`, `plan`, `periodo`, `estado`, `monto`, `fecha_solicitud`, `fecha_activacion`, `fecha_vencimiento`, `notas`) VALUES
(1, 3, '', 'mensual', 'cancelado', 0.00, '2026-04-14 20:40:53', '2026-04-14 14:40:53', '2026-05-14', 'Plan gratuito de bienvenida - 30 días'),
(2, 3, 'premium', 'mensual', 'cancelado', 599.00, '2026-04-14 20:42:20', '2026-04-14 14:42:20', '2026-05-14', NULL),
(3, 3, 'premium', 'mensual', 'activo', 599.00, '2026-04-18 18:35:33', '2026-04-18 12:35:33', '2026-05-18', NULL),
(4, 4, '', 'mensual', 'activo', 0.00, '2026-05-07 23:42:06', '2026-05-07 17:42:06', '2026-06-06', 'Plan gratuito de bienvenida - 30 días');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `titulo` varchar(120) DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`id`, `restaurante_id`, `imagen`, `titulo`, `descripcion`, `activa`, `creado_en`) VALUES
(1, 3, 'uploads/promo_b6c738fce33e6a6e.png', 'futbol', '', 1, '2026-04-18 18:41:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_clientes`
--

CREATE TABLE `puntos_clientes` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `puntos_total` int(11) DEFAULT 0,
  `visitas` int(11) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `puntos_clientes`
--

INSERT INTO `puntos_clientes` (`id`, `restaurante_id`, `telefono`, `puntos_total`, `visitas`, `updated_at`) VALUES
(1, 3, '2381172308', 9, 3, '2026-04-18 19:09:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas_plataforma`
--

CREATE TABLE `resenas_plataforma` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT 'Usuario anónimo',
  `texto` text NOT NULL,
  `estrellas` tinyint(1) NOT NULL DEFAULT 5,
  `ip` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resenas_plataforma`
--

INSERT INTO `resenas_plataforma` (`id`, `nombre`, `texto`, `estrellas`, `ip`, `activo`, `created_at`) VALUES
(1, 'jose', 'Muy buena', 4, '187.192.69.29', 1, '2026-04-27 02:44:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes`
--

CREATE TABLE `restaurantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` varchar(80) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `mesas` int(11) NOT NULL DEFAULT 10,
  `moneda` varchar(10) NOT NULL DEFAULT 'MXN',
  `acepta_llevar` tinyint(1) NOT NULL DEFAULT 1,
  `acepta_efectivo` tinyint(1) NOT NULL DEFAULT 1,
  `token` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `hero_imagen` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `tema_color` varchar(20) DEFAULT NULL,
  `tema_nombre` varchar(30) DEFAULT 'dorado',
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurantes`
--

INSERT INTO `restaurantes` (`id`, `nombre`, `tipo`, `direccion`, `descripcion`, `mesas`, `moneda`, `acepta_llevar`, `acepta_efectivo`, `token`, `activo`, `created_at`, `hero_imagen`, `slogan`, `tema_color`, `tema_nombre`, `lat`, `lng`) VALUES
(3, 'RESTAURANTE', 'Cafetería', '30 oriente 710, 0', '', 10, 'MXN', 1, 1, 'c78b551494616ebbd07fdc8f9454cddd', 1, '2026-04-14 20:40:53', NULL, NULL, '#a855f7', 'morado', NULL, NULL),
(4, 'Jehej', 'Pizzería', '', '', 10, 'MXN', 1, 1, '03b02120e6f53af4d6d89cf562227030', 1, '2026-05-07 23:42:06', NULL, NULL, NULL, 'dorado', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes_redes`
--

CREATE TABLE `restaurantes_redes` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_pago`
--

CREATE TABLE `solicitudes_pago` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `mesa_numero` int(11) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'mesa',
  `total` decimal(10,2) DEFAULT 0.00,
  `propina` int(11) DEFAULT 0,
  `items_json` text DEFAULT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime DEFAULT current_timestamp(),
  `restaurante_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes_pago`
--

INSERT INTO `solicitudes_pago` (`id`, `usuario_id`, `nombre_cliente`, `mesa_numero`, `tipo`, `total`, `propina`, `items_json`, `pagado`, `creado_en`, `restaurante_id`) VALUES
(29, 49, 'irvin', 6, 'mesa', 150.00, 0, '[{\"id\":1,\"numero_orden\":1,\"total\":\"150.00\",\"estado\":\"listo\",\"items_texto\":\"1x Hambuerguesa & Papas Fritas\"}]', 1, '2026-04-18 19:04:47', 3),
(30, 49, 'irvin', 6, 'mesa', 150.00, 0, '[{\"id\":2,\"numero_orden\":2,\"total\":\"150.00\",\"estado\":\"listo\",\"items_texto\":\"1x Hambuerguesa & Papas Fritas\"}]', 1, '2026-04-18 19:07:51', 3),
(31, 49, 'irvin', 6, 'mesa', 150.00, 0, '[{\"id\":3,\"numero_orden\":3,\"total\":\"150.00\",\"estado\":\"listo\",\"items_texto\":\"1x Hambuerguesa & Papas Fritas\"}]', 1, '2026-04-18 19:09:05', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_pin`
--

CREATE TABLE `solicitudes_pin` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `nombre_empleado` varchar(120) NOT NULL,
  `hora_entrada_registrada` datetime NOT NULL,
  `fecha` date NOT NULL,
  `resuelta` tinyint(1) DEFAULT 0,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'mesa',
  `mesa_numero` int(11) DEFAULT NULL,
  `token_sesion` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `telefono`, `tipo`, `mesa_numero`, `token_sesion`, `created_at`) VALUES
(49, 'irvin', '2381172308', 'mesa', 6, 'be4ec5a650433a97b917980f6cc687f1a1b1208938c1eef6738937f579f4b40e', '2026-04-18 18:23:06');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario` (`usuario`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_fecha` (`empleado_id`,`fecha`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token_rest` (`restaurante_id`,`token_sesion`);

--
-- Indices de la tabla `config_puntos`
--
ALTER TABLE `config_puntos`
  ADD PRIMARY KEY (`restaurante_id`);

--
-- Indices de la tabla `contador_ordenes`
--
ALTER TABLE `contador_ordenes`
  ADD PRIMARY KEY (`fecha`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `momentos`
--
ALTER TABLE `momentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `momentos_likes`
--
ALTER TABLE `momentos_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_like` (`momento_id`,`ip`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `planes_activos`
--
ALTER TABLE `planes_activos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `puntos_clientes`
--
ALTER TABLE `puntos_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rest_tel` (`restaurante_id`,`telefono`);

--
-- Indices de la tabla `resenas_plataforma`
--
ALTER TABLE `resenas_plataforma`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `restaurantes_redes`
--
ALTER TABLE `restaurantes_redes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rest` (`restaurante_id`);

--
-- Indices de la tabla `solicitudes_pago`
--
ALTER TABLE `solicitudes_pago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `solicitudes_pin`
--
ALTER TABLE `solicitudes_pin`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token_sesion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `momentos`
--
ALTER TABLE `momentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `momentos_likes`
--
ALTER TABLE `momentos_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `planes_activos`
--
ALTER TABLE `planes_activos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `puntos_clientes`
--
ALTER TABLE `puntos_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `resenas_plataforma`
--
ALTER TABLE `resenas_plataforma`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `restaurantes_redes`
--
ALTER TABLE `restaurantes_redes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `solicitudes_pago`
--
ALTER TABLE `solicitudes_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `solicitudes_pin`
--
ALTER TABLE `solicitudes_pin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `momentos_likes`
--
ALTER TABLE `momentos_likes`
  ADD CONSTRAINT `fk_momento` FOREIGN KEY (`momento_id`) REFERENCES `momentos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `planes_activos`
--
ALTER TABLE `planes_activos`
  ADD CONSTRAINT `planes_activos_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
