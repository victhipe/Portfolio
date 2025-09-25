-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Сен 26 2025 г., 00:42
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
-- База данных: `forum_dex`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bans`
--

CREATE TABLE `bans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ban_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiration_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `bans`
--

INSERT INTO `bans` (`id`, `user_id`, `ip_address`, `reason`, `ban_date`, `expiration_date`) VALUES
(2, 2, NULL, '1', '2024-07-23 09:41:40', '2024-07-24 07:41:40');

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--
-- Структура чтения ошибок для таблицы forum_dex.comments: #1932 - Table &#039;forum_dex.comments&#039; doesn&#039;t exist in engine
-- Ошибка считывания данных таблицы forum_dex.comments: #1064 - У вас ошибка в запросе. Изучите документацию по используемой версии MariaDB на предмет корректного синтаксиса около &#039;FROM `forum_dex`.`comments`&#039; на строке 1

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`id`, `room_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 3, '1', '2024-07-15 08:15:56');

-- --------------------------------------------------------

--
-- Структура таблицы `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `section` varchar(50) NOT NULL DEFAULT 'все'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `posts`
--

INSERT INTO `posts` (`id`, `username`, `title`, `content`, `image_path`, `audio_path`, `video_path`, `created_at`, `section`) VALUES
(1, 'admin1', 'Проверка', 'проверка', 'img/222785-bravo-stars-logotip-26.jpg', '', '', '2024-07-10 08:37:16', 'все'),
(7, 'Avatarity', 'Зацени этот загловоок', 'Да этот заголовок хороший', '', '', '', '2024-07-10 09:42:07', 'все'),
(10, 'Avatarity', 'Раз, два,три', 'Раз,два,три, четыре!', '', '', '', '2024-07-15 09:55:28', 'все'),
(16, 'Avatarity', 'авввав', 'авававав', '', '', '', '2024-07-22 08:14:36', 'новости'),
(18, 'Avatarity', 'Аналитика', 'Аналитика', '', '', '', '2024-07-22 08:14:51', 'системная-аналитика'),
(22, 'Avatarity', 'Аналитика', 'Аналитика', '', '', '', '2024-07-22 08:15:38', 'новости');

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `created_at`) VALUES
(1, 'k', '2024-07-15 08:08:50'),
(2, 'e', '2024-07-15 08:21:41');

-- --------------------------------------------------------

--
-- Структура таблицы `room_members`
--

CREATE TABLE `room_members` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `room_members`
--

INSERT INTO `room_members` (`id`, `room_id`, `user_id`, `joined_at`) VALUES
(1, 1, 2, '2024-07-15 08:15:44');

-- --------------------------------------------------------

--
-- Структура таблицы `support_requests`
--

CREATE TABLE `support_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `issue_description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `support_requests`
--

INSERT INTO `support_requests` (`id`, `email`, `issue_description`, `created_at`) VALUES
(2, 'R0mbik1@yandex.ru', 'R0mbik1@yandex.ru', '2024-07-16 10:35:24');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT 'user',
  `gender` enum('male','female') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `password`, `avatar_path`, `created_at`, `role`, `gender`) VALUES
(2, 'admin1', 'admin1', '$2y$10$AbZqGkwzUOyfv7Qhb4cx2OwLggOwzF937iUovuZry/SHf4v0C2j7S', NULL, '2024-07-10 08:36:39', 'user', 'male'),
(3, 'Avatarity', 'Avatarity', '$2y$10$BsAkALi3JT.bJPmd/2zfseGGMhQGKrB23UWcj4KrbXU9q18Ii4Y3y', 'avatars/668e6251ce49f.jpg', '2024-07-10 08:50:00', 'admin', 'male'),
(4, '12', '12', '$2y$10$56v6wj5g0bKOTQJ2IDf8rugJTwSxzr7EiXp8iemU66Dc3rvjs4Oaa', 'avatars/avatar.png', '2024-07-24 09:01:35', 'user', 'male'),
(5, '3332', '33331', '$2y$10$ypWX8Tb5ESCutJUaMm4oe.iCMfr3yVuDeOj5uw/tf5cFokFwZJrk.', 'avatars/avatar.png', '2024-07-24 09:25:47', 'user', 'male'),
(9, 'r', '333', '$2y$10$BCHm23p.QUfoPjwOyrGFgekZAWvOx9pYFQ9xRnJIkwHRd15gznqee', 'avatars/avatar.png', '2024-07-24 09:31:40', 'user', 'male'),
(10, '434', '433', '$2y$10$U1MqxzrWV7zYIkujbshpc.c48L6nNQRWKvGcL9zr9wWQkjpBXO/.G', 'avatars/avatar.png', '2024-07-24 09:32:11', 'user', 'male'),
(11, '32', '32', '$2y$10$FH0T.8pyx0giVgjYEMsh6.hLadikJd5dFSr7UOQ24c/qLZXMOAg5a', 'avatars/avatar.png', '2024-07-24 10:26:02', 'user', 'male'),
(12, '122', '122', '$2y$10$Lm3PFllpI8ucKWgSbH6FBegjv.vZDJZ5jW8vWVF74r8/hcgYBVkCe', 'avatars/avatar.png', '2024-07-24 10:26:15', 'user', 'female'),
(13, '22', '12', '$2y$10$MqLEZqR3QzctRrJ3NwC0n./sYPHyol3sh6hn0jdE0qv0md/k8rS.6', 'avatars/avatar.png', '2024-07-24 10:26:30', 'user', 'male'),
(14, 'ненеегг', '12', '$2y$10$OuZ84jStmjoRaB7xNbTI4u0S7PNkWVa4WWaKu.JwvXcpBBipBp1.y', 'avatars/avatar.png', '2024-07-24 10:27:43', 'user', 'male');

-- --------------------------------------------------------

--
-- Структура таблицы `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `vote` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `votes`
--

INSERT INTO `votes` (`id`, `user_id`, `post_id`, `comment_id`, `vote`, `created_at`, `updated_at`) VALUES
(3, 3, 1, NULL, 1, '2024-07-11 08:54:31', '2024-07-11 08:54:31'),
(11, 3, 7, NULL, 1, '2024-07-17 08:53:04', '2024-07-17 08:53:04'),
(12, 3, NULL, 6, 1, '2024-07-17 10:56:40', '2024-07-22 07:58:38'),
(13, 3, NULL, 10, 1, '2024-07-17 11:51:20', '2024-07-17 11:51:25');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `room_members`
--
ALTER TABLE `room_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `support_requests`
--
ALTER TABLE `support_requests`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Индексы таблицы `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`user_id`,`post_id`,`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `room_members`
--
ALTER TABLE `room_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `support_requests`
--
ALTER TABLE `support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `bans`
--
ALTER TABLE `bans`
  ADD CONSTRAINT `bans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`);

--
-- Ограничения внешнего ключа таблицы `room_members`
--
ALTER TABLE `room_members`
  ADD CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
