-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Počítač: db.dw189.webglobe.com
-- Vytvořeno: Čtv 29. kvě 2025, 14:02
-- Verze serveru: 8.0.41-32
-- Verze PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `vyroba_myrec_cz`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `change_history`
--

CREATE TABLE `change_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `catalog` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `order_date` date NOT NULL,
  `goods_ordered_date` date DEFAULT NULL,
  `goods_stocked_date` date DEFAULT NULL,
  `preview_status` enum('Čeká','Schváleno','Zamítnuto') COLLATE utf8mb4_general_ci DEFAULT 'Čeká',
  `preview_approved_date` date DEFAULT NULL,
  `shipping_date` date DEFAULT NULL,
  `production_status` enum('Čekající','V_výrobě','Hotovo') COLLATE utf8mb4_general_ci DEFAULT 'Čekající',
  `completion_date` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `salesperson` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Vypisuji data pro tabulku `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `catalog`, `quantity`, `order_date`, `goods_ordered_date`, `goods_stocked_date`, `preview_status`, `preview_approved_date`, `shipping_date`, `production_status`, `completion_date`, `notes`, `salesperson`, `created_at`, `updated_at`) VALUES
(1, '25VP-00002', '', 905, '2025-01-02', NULL, NULL, 'Čeká', NULL, NULL, 'Čekající', NULL, NULL, NULL, '2025-05-29 08:05:04', '2025-05-29 08:05:04'),
(5, '25VP-00004', '13030683-03', 1500, '2025-01-02', '2025-01-03', '2025-01-06', 'Čeká', NULL, NULL, 'Čekající', NULL, NULL, NULL, '2025-05-29 08:05:04', '2025-05-29 08:07:57');

-- --------------------------------------------------------

--
-- Struktura tabulky `order_notes`
--

CREATE TABLE `order_notes` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `production_schedule`
--

CREATE TABLE `production_schedule` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `technology_id` int DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `technologies`
--

CREATE TABLE `technologies` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `color` varchar(7) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Vypisuji data pro tabulku `technologies`
--

INSERT INTO `technologies` (`id`, `name`, `color`) VALUES
(1, 'Sítotisk', '#10b981'),
(2, 'Potisk', '#3b82f6'),
(3, 'Gravírování', '#f59e0b'),
(4, 'Výšivka', '#eab308'),
(5, 'Laser', '#06b6d4'),
(6, 'Pila', '#ff6b6b'),
(7, 'Broušení', '#4ecdc4'),
(8, 'Lakování', '#45b7d1'),
(9, 'Montáž', '#96ceb4'),
(10, 'Balení', '#feca57');

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','obchodnik','vyroba','grafik') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(8, 'admin', 'admin@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Správce Systému', 'admin', 1, '2025-05-29 11:44:07', '2025-05-29 09:41:58', '2025-05-29 11:44:07'),
(9, 'pavel.novak', 'pavel.novak@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Pavel Novák', 'obchodnik', 1, NULL, '2025-05-29 09:41:58', '2025-05-29 09:41:58'),
(10, 'marie.svoboda', 'marie.svoboda@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Marie Svobodová', 'obchodnik', 1, NULL, '2025-05-29 09:41:58', '2025-05-29 09:41:58'),
(11, 'jan.dvorak', 'jan.dvorak@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Jan Dvořák', 'vyroba', 1, NULL, '2025-05-29 09:41:58', '2025-05-29 09:41:58'),
(12, 'tomas.krejci', 'tomas.krejci@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Tomáš Krejčí', 'vyroba', 1, NULL, '2025-05-29 09:41:58', '2025-05-29 09:41:58'),
(13, 'anna.horak', 'anna.horak@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Anna Horáková', 'grafik', 1, '2025-05-29 10:49:10', '2025-05-29 09:41:58', '2025-05-29 10:49:10'),
(14, 'petr.vesely', 'petr.vesely@vyroba.cz', '$2y$10$xUhdnGyMWQZWehQ0RfZ/A.xeAk7A32rf0uTPmJA0BkSbK81/W0iyO', 'Petr Veselý', 'grafik', 1, NULL, '2025-05-29 09:41:58', '2025-05-29 09:41:58');

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `change_history`
--
ALTER TABLE `change_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_record` (`table_name`,`record_id`);

--
-- Indexy pro tabulku `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`);

--
-- Indexy pro tabulku `order_notes`
--
ALTER TABLE `order_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexy pro tabulku `production_schedule`
--
ALTER TABLE `production_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `technology_id` (`technology_id`);

--
-- Indexy pro tabulku `technologies`
--
ALTER TABLE `technologies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `change_history`
--
ALTER TABLE `change_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pro tabulku `order_notes`
--
ALTER TABLE `order_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `production_schedule`
--
ALTER TABLE `production_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `technologies`
--
ALTER TABLE `technologies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `change_history`
--
ALTER TABLE `change_history`
  ADD CONSTRAINT `change_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Omezení pro tabulku `order_notes`
--
ALTER TABLE `order_notes`
  ADD CONSTRAINT `order_notes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `production_schedule`
--
ALTER TABLE `production_schedule`
  ADD CONSTRAINT `production_schedule_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_schedule_ibfk_2` FOREIGN KEY (`technology_id`) REFERENCES `technologies` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
