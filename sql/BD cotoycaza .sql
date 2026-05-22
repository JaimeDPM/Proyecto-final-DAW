-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 20-05-2026 a las 13:35:50
-- Versión del servidor: 8.4.6-6
-- Versión de PHP: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db1nfackwku24n`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotos`
--

CREATE TABLE `cotos` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `letra_provincia` enum('AV','BU','LE','P','SA','SG','SO','VA','ZA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_matricula` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `provincia` enum('Ávila','Burgos','León','Palencia','Salamanca','Segovia','Soria','Valladolid','Zamora') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `municipio` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `titular_id` int DEFAULT NULL,
  `razon_social` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_nif` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_telefonomovil` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_tipovia` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_direccion` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pj_numero` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_portal` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_escalera` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_piso` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_puerta` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_municipio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pj_localidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pj_provincia` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pj_cp` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `notas` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotos`
--

INSERT INTO `cotos` (`id`, `usuario_id`, `letra_provincia`, `numero_matricula`, `provincia`, `municipio`, `titular_id`, `razon_social`, `pj_nif`, `pj_telefono`, `pj_telefonomovil`, `pj_email`, `pj_tipovia`, `pj_direccion`, `pj_numero`, `pj_portal`, `pj_escalera`, `pj_piso`, `pj_puerta`, `pj_municipio`, `pj_localidad`, `pj_provincia`, `pj_cp`, `notas`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, 'P', '10078', 'Palencia', 'Villaviudas', 1, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '', NULL, '', '', NULL, '2026-05-11 15:06:50', '2026-05-18 10:38:46', NULL),
(2, 7, 'SO', '60001', 'Soria', 'Berlanga de Duero', NULL, 'Sociedad de Cazadores El Encinar', 'G12345678', '975100001', '679200001', 'encinar@email.com', 'Plaza', 'Mayor', '1', NULL, NULL, NULL, NULL, 'Berlanga de Duero', 'Berlanga de Duero', 'Soria', '42210', NULL, '2026-05-11 15:06:50', '2026-05-11 15:06:50', NULL),
(3, 7, 'P', '11111', 'Palencia', 'Magaz de Pisuerga', NULL, 'Club cazaores del Pisuerga', 'C41414121', '698525212', '541212121', 'yoquese@gmail.com', NULL, 'C/ Mayor 1', NULL, NULL, NULL, NULL, NULL, 'Magaz de Pisuerga', NULL, 'Palencia', '34110', NULL, '2026-05-18 10:39:46', '2026-05-18 11:14:00', NULL),
(4, 7, 'SA', '12020', 'Salamanca', 'Yo que se de arriba', NULL, 'Yo que se de arriba Club', 'G12345678', '987454545', '987454545', 'yoquese@gmail.com', NULL, 'C/ CR7', NULL, NULL, NULL, NULL, NULL, 'Yo que se de arriba', NULL, 'Salamanca', '37016', NULL, '2026-05-18 11:03:20', '2026-05-18 11:15:24', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotos_colindantes`
--

CREATE TABLE `cotos_colindantes` (
  `id` int NOT NULL,
  `coto_id` int NOT NULL,
  `provincia` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `numero_coto` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `menos_500m` tinyint(1) NOT NULL DEFAULT '0',
  `notas` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotos_colindantes`
--

INSERT INTO `cotos_colindantes` (`id`, `coto_id`, `provincia`, `numero_coto`, `menos_500m`, `notas`, `created_at`, `updated_at`) VALUES
(4, 2, 'SO', '60002', 1, 'Linda por el este', '2026-05-11 15:07:42', '2026-05-11 15:07:42'),
(5, 2, 'SO', '60003', 0, NULL, '2026-05-11 15:07:42', '2026-05-11 15:07:42'),
(6, 1, 'Palencia', '10079', 1, 'Linda por el norte', '2026-05-18 10:38:46', '2026-05-18 10:38:46'),
(7, 1, 'Palencia', '10080', 0, '', '2026-05-18 10:38:46', '2026-05-18 10:38:46'),
(8, 1, 'Palencia', '10023', 0, '', '2026-05-18 10:38:46', '2026-05-18 10:38:46'),
(15, 3, 'Palencia', '10987', 0, '', '2026-05-18 11:14:00', '2026-05-18 11:14:00'),
(16, 3, 'Palencia', '10200', 0, '', '2026-05-18 11:14:00', '2026-05-18 11:14:00'),
(17, 4, 'Salamanca', '11111', 0, '', '2026-05-18 11:15:24', '2026-05-18 11:15:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('caceria','tramite','precinto','temporada') COLLATE utf8mb4_general_ci NOT NULL,
  `icono` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Emoji del evento, usado en tipo temporada',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `coto_id` int DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_general_ci,
  `recurrente` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `para_todos` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(7) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `usuario_id`, `titulo`, `tipo`, `icono`, `fecha_inicio`, `fecha_fin`, `coto_id`, `comentario`, `recurrente`, `created_at`, `updated_at`, `deleted_at`, `para_todos`, `color`) VALUES
(13, 3, 'Temporada jabali prueba', 'temporada', '🐗', '2026-04-01', '2026-04-15', NULL, 'Prueba editado', 1, '2026-04-29 10:35:50', '2026-04-29 10:36:08', NULL, 0, NULL),
(14, 6, 'prueba evento caceria', 'caceria', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 10:48:15', '2026-04-29 10:48:15', NULL, 0, NULL),
(16, 6, 'Prueba precinto 2', 'precinto', NULL, '2026-04-01', '2026-04-10', NULL, NULL, 0, '2026-04-29 10:49:05', '2026-04-29 10:49:05', NULL, 0, NULL),
(17, 3, 'Temporada venado macho', 'temporada', '🦌', '2026-02-01', '2027-01-31', NULL, 'Solo venados macho prueba', 0, '2026-04-29 10:54:24', '2026-04-29 10:54:24', NULL, 0, NULL),
(18, 6, 'repeticion anual', 'tramite', NULL, '2026-03-01', NULL, NULL, NULL, 1, '2026-04-29 11:05:46', '2026-04-29 11:05:46', NULL, 0, NULL),
(19, 3, 'TEMPORADA LOBO', 'temporada', '🐺', '2026-04-01', '2026-04-30', NULL, NULL, 1, '2026-04-29 20:56:04', '2026-04-29 20:56:04', NULL, 0, NULL),
(20, 3, 'jyfuhjtrfhtr', 'tramite', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:02:57', '2026-04-29 21:02:57', NULL, 0, NULL),
(21, 3, 'efesfesf', 'precinto', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:03:03', '2026-04-29 21:03:03', NULL, 0, NULL),
(22, 3, 'tehtrhtr', 'precinto', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:06:21', '2026-04-29 21:06:21', NULL, 0, NULL),
(23, 3, 'rtettretgert', 'precinto', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:06:26', '2026-04-29 21:06:26', NULL, 0, NULL),
(24, 3, 'gfregregerg', 'caceria', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:06:29', '2026-04-29 21:06:29', NULL, 0, NULL),
(25, 3, 'regergre', 'caceria', NULL, '2026-04-08', NULL, NULL, NULL, 0, '2026-04-29 21:06:32', '2026-04-29 21:06:32', NULL, 0, NULL),
(26, 3, 'Lobito', 'temporada', '🐺', '2026-05-01', NULL, NULL, NULL, 1, '2026-05-06 21:23:58', '2026-05-06 21:23:58', NULL, 0, NULL),
(28, 3, 'Precinto TEST', 'caceria', NULL, '2026-05-11', '2026-05-13', 1, 'Fulanito tiene el precinto', 0, '2026-05-07 08:28:35', '2026-05-18 08:36:36', NULL, 0, NULL),
(29, 3, 'TEMPORADA TEST', 'temporada', '🐇', '2026-05-18', '2026-05-19', NULL, 'TEMPORADA TEST', 0, '2026-05-07 09:33:59', '2026-05-07 09:33:59', NULL, 0, NULL),
(30, 3, 'TEST UN DIA', 'precinto', NULL, '2026-05-19', NULL, NULL, NULL, 0, '2026-05-07 10:32:50', '2026-05-07 10:32:50', NULL, 0, NULL),
(31, 3, 'Test TEST TEST TEST TEST TEST TEST TEST TEST TEST ETST', 'precinto', NULL, '2026-05-21', NULL, 1, NULL, 1, '2026-05-07 11:44:20', '2026-05-18 08:48:17', NULL, 0, NULL),
(32, 3, 'Test precinto 1', 'precinto', NULL, '2026-05-13', NULL, NULL, NULL, 0, '2026-05-07 12:06:54', '2026-05-18 08:47:35', NULL, 0, NULL),
(33, 3, 'test tramite', 'tramite', NULL, '2026-05-13', NULL, NULL, NULL, 0, '2026-05-07 12:07:14', '2026-05-07 12:07:14', NULL, 0, NULL),
(34, 3, 'test 4 control poblacional', 'caceria', NULL, '2026-05-13', NULL, 2, NULL, 0, '2026-05-07 12:07:37', '2026-05-18 08:36:48', NULL, 0, NULL),
(35, 3, 'test 5 control alimañas', 'caceria', NULL, '2026-05-13', NULL, 2, NULL, 0, '2026-05-07 12:07:51', '2026-05-18 08:37:05', NULL, 0, NULL),
(36, 3, 'temporada jabali 2026/2027', 'temporada', '⭐', '2026-02-03', '2027-07-19', NULL, NULL, 1, '2026-05-19 18:09:54', '2026-05-19 18:09:54', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido1` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido2` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dni_nif` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `telefonomovil` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tipovia` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `numero` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `portal` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `escalera` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `piso` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `puerta` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `municipio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `localidad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `provincia` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `cp` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `notas` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id`, `usuario_id`, `nombre`, `apellido1`, `apellido2`, `dni_nif`, `telefono`, `telefonomovil`, `email`, `tipovia`, `direccion`, `numero`, `portal`, `escalera`, `piso`, `puerta`, `municipio`, `localidad`, `provincia`, `cp`, `notas`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, 'Santiago', 'Fombellida', 'Nieto', '12345678A', '979100001', '679100001', 'santiago@email.com', 'Calle', 'Mayor', '5', NULL, NULL, NULL, NULL, 'Palencia', 'Palencia', 'Palencia', '34001', NULL, '2026-05-11 15:06:50', '2026-05-11 15:06:50', NULL),
(2, 7, 'Jaime', 'De Pablos', 'Martínez', '87654321B', '979100002', '679100002', 'jaime@proa.com', 'Calle', 'La Cestilla', '2', NULL, NULL, NULL, NULL, 'Palencia', 'Palencia', 'Palencia', '34001', NULL, '2026-05-11 15:06:50', '2026-05-19 17:35:28', NULL),
(3, 7, 'Manuel', 'García', 'Sancho', '71942268Q', '979100003', '679210472', 'manuel@email.com', 'Urbanización', 'La Jornada', '4', NULL, NULL, NULL, NULL, 'Villaviudas', 'Villaviudas', 'Palencia', '34239', NULL, '2026-05-11 15:06:50', '2026-05-15 08:10:16', NULL),
(4, 7, 'Pedro', 'Rodríguez', 'Torres', '23456789B', '979100004', '679100004', 'pedro@email.com', 'Avenida', 'Castilla', '10', NULL, NULL, NULL, NULL, 'Valladolid', 'Valladolid', 'Valladolid', '47001', NULL, '2026-05-11 15:06:50', '2026-05-11 15:06:50', NULL),
(5, 7, 'Pepito', 'Palotes', NULL, '74114741D', '696454545', '696454545', 'prueba@gmail.com', 'Calle', 'Alfareros', '2', NULL, NULL, NULL, NULL, 'Palencia', '', 'Palencia', '34003', NULL, '2026-05-18 11:25:12', '2026-05-18 13:31:53', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas`
--

CREATE TABLE `plantillas` (
  `id` int NOT NULL,
  `tipo` enum('word','pdf') COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_visible` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_archivo` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `marcadores` json DEFAULT NULL,
  `subido_por` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plantillas`
--

INSERT INTO `plantillas` (`id`, `tipo`, `nombre_visible`, `nombre_archivo`, `marcadores`, `subido_por`, `created_at`) VALUES
(1, 'word', 'Autorizacion control poblacional TEST', 'plantilla_69fb62bac8ae4_Autorizacion_control_poblacional_TEST.docx', '[\"autorizado_nif\", \"autorizado_nombre\", \"coto_matricula\", \"cupo\", \"especie\", \"fecha_fin\", \"fecha_inicio\", \"modalidad\", \"num_peticion\", \"representante_dni\", \"representante_nombre\", \"temporada\", \"titular_nif\", \"titular_nombre\"]', 3, '2026-05-06 15:48:10'),
(5, 'word', 'Anexo firmas caceria colectiva', 'plantilla_6a01f1f6648f3_Anexo_firmas_caceria_colectiva.docx', '[\"coto_matricula\", \"especies\", \"fecha_comunicacion\", \"fecha_firma\", \"modalidad_detalle\", \"num_expediente\", \"organizador_direccion\", \"organizador_municipio\", \"organizador_nif\", \"organizador_nombre\", \"organizador_provincia\", \"organizador_telefono\", \"razon_social\", \"representante_nombre\", \"titular_cp\", \"titular_direccion\", \"titular_municipio\", \"titular_nombre\", \"titular_provincia\"]', 3, '2026-05-11 15:12:54'),
(6, 'word', 'Autorizacion Uso APP', 'plantilla_6a034c3a7b189_Autorizacion_Uso_APP.docx', '[\"cargo_titular\", \"coto_matricula\", \"fecha_firma\", \"persona_email\", \"persona_nif\", \"persona_nombre\", \"razon_social\", \"titular_nif\", \"titular_nombre\", \"titular_provincia\"]', 3, '2026-05-12 15:50:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'bcrypt hash',
  `rol` enum('admin','usuario') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'usuario',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 = cuenta desactivada',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `activo`, `created_at`, `updated_at`, `must_change_password`) VALUES
(3, 'jaime', 'jdepablos95@hotmail.es', '$2y$10$CapYNorglKvLn84d7H0Wiuc6ugbtdxhlqJ7g3zovQiK7MAKSfApWC', 'admin', 1, '2026-04-20 13:07:21', '2026-04-22 09:34:04', 0),
(6, 'pruebacliente', 'pruebacliente@gmail.com', '$2y$10$8zRnRWVsIYeA46w8ST0Ox.XCpSFD5fOewT/yZyet0qZ5jjPb2bEl.', 'usuario', 1, '2026-04-20 13:48:25', '2026-05-20 09:37:11', 0),
(7, 'proa', 'jaime@proaempresarial.com', '$2y$10$1M9Ff2801yOlpgJr4FhRyunSIRZ.GfRopadkBl1oGzEp/qbyZuHVe', 'usuario', 1, '2026-04-22 09:34:32', '2026-04-22 09:34:32', 0),
(9, 'pruebaok', 'pruebaok@gmail.com', '$2y$10$NA3/bhzosIooOyeJlhc9nu6VuDuVVXeKfl2UDA8xNI6YpKQ5Lszmm', 'usuario', 1, '2026-05-18 13:12:55', '2026-05-18 13:13:04', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cotos`
--
ALTER TABLE `cotos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_matricula` (`letra_provincia`,`numero_matricula`),
  ADD KEY `idx_cotos_matricula` (`letra_provincia`,`numero_matricula`),
  ADD KEY `idx_cotos_titular` (`titular_id`),
  ADD KEY `idx_cotos_usuario` (`usuario_id`);

--
-- Indices de la tabla `cotos_colindantes`
--
ALTER TABLE `cotos_colindantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_colindantes_coto` (`coto_id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eventos_fecha` (`fecha_inicio`),
  ADD KEY `idx_eventos_coto` (`coto_id`),
  ADD KEY `idx_eventos_usuario` (`usuario_id`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_personas_dni` (`dni_nif`),
  ADD KEY `idx_personas_apellido` (`apellido1`),
  ADD KEY `idx_personas_usuario` (`usuario_id`);

--
-- Indices de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plantillas_usuario` (`subido_por`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cotos`
--
ALTER TABLE `cotos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cotos_colindantes`
--
ALTER TABLE `cotos_colindantes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cotos`
--
ALTER TABLE `cotos`
  ADD CONSTRAINT `cotos_ibfk_1` FOREIGN KEY (`titular_id`) REFERENCES `personas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cotos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cotos_colindantes`
--
ALTER TABLE `cotos_colindantes`
  ADD CONSTRAINT `colindantes_ibfk_1` FOREIGN KEY (`coto_id`) REFERENCES `cotos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`coto_id`) REFERENCES `cotos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eventos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `personas`
--
ALTER TABLE `personas`
  ADD CONSTRAINT `fk_personas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD CONSTRAINT `fk_plantillas_usuario` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
