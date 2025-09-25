-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Сен 25 2025 г., 22:07
-- Версия сервера: 10.4.28-MariaDB
-- Версия PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `tasty_pizza_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `sort_order`) VALUES
(1, 'Пицца', 'pizza', NULL, 10),
(2, 'Напитки', 'drinks', NULL, 20),
(3, 'Салаты', 'salads', NULL, 30),
(4, 'Закуски', 'snacks', NULL, 40),
(5, 'Десерты', 'desserts', NULL, 50);

-- --------------------------------------------------------

--
-- Структура таблицы `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `category` enum('pizza','drink','salad','snack','dessert') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `menu_items`
--

INSERT INTO `menu_items` (`id`, `category`, `name`, `description`, `price`, `image_path`, `is_available`, `created_at`) VALUES
(2, 'pizza', 'Пепперони', 'Пицца с пикантной колбаской пепперони.', 550.00, 'pizzas/pepperoni.jpg', 1, '2025-04-07 12:27:46'),
(3, 'pizza', 'Четыре Сыра', 'Моцарелла, дорблю, пармезан, чеддер.', 600.00, 'pizzas/4cheees.jpg', 1, '2025-04-07 12:27:46'),
(9, 'dessert', 'Чизкейк', 'Классический десерт.', 250.00, 'dessert/1744193393__________________________9_.jpg', 1, '2025-04-07 12:27:46'),
(10, 'drink', 'Добрый Кола 0.5', 'НУ ВКУСНАЯ КОЛА!!!', 100.00, 'drink/1744193491________________________.jpg', 1, '2025-04-09 10:11:31'),
(11, 'snack', 'Картошка Фри', 'Жареная картошка', 150.00, 'snack/1744193575__________________________1_.jpg', 0, '2025-04-09 10:11:57');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('new','processing','delivering','completed','cancelled') NOT NULL DEFAULT 'new',
  `order_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `operator_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `phone_number`, `delivery_address`, `total_price`, `status`, `order_time`, `operator_notes`) VALUES
(5, NULL, 'sex', '+79828318381', 'sex', 600.00, 'delivering', '2025-04-07 12:44:22', ''),
(6, NULL, 'sex', '+79828318381', 'sex', 250.00, 'delivering', '2025-04-07 12:54:28', '');

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `size` varchar(10) DEFAULT NULL COMMENT 'Размер для пиццы (35, 42, 55), NULL для других',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_per_item` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `size`, `quantity`, `price_per_item`) VALUES
(1, 5, 3, '42', 1, 600.00),
(2, 6, 9, NULL, 1, 250.00);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','operator','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone_number`, `address`, `role`, `created_at`) VALUES
(5, 'Admino', '$2y$10$7DPgP2hgYkN9gM0u5fFsbO3yWk.RqziQiIymyPJvr7c2hWw8udZ9i', 'Admino@yandex.ru', 'Admino', '+79828319999', '///', 'admin', '2025-04-08 12:53:21'),
(6, 'Operation', '$2y$10$0v.4deRFxfZ1G7IzFWb9GekzkgQXMSlJ8ENlfenjK9AS9YwU8b41O', 'Operation@yandex.ru', 'Operation', '+79828319999', '', 'operator', '2025-04-08 12:56:47'),
(7, 'vika', '$2y$10$4gBbrvlbf22F9N1vN5rxw.GBLGtf13ANb2W1MJibvg1Azw5kzEgS2', 'vika@yandex.ru', 'vika', '+798283331', '', 'customer', '2025-04-09 09:31:10'),
(8, 'Admin', '$2y$10$Z13oQbtC1SetHYv9Q9VLWuxuCZ7AwBI801i48t09LfxFy9vESSD2u', 'Admin@yandex.ru', '', '', '', 'admin', '2025-04-09 09:51:43');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
