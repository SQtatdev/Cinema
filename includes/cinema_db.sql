-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Ноя 13 2025 г., 11:58
-- Версия сервера: 8.0.39
-- Версия PHP: 7.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cinema_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_id` int NOT NULL,
  `seat_row` int NOT NULL,
  `seat_number` int NOT NULL,
  `status` enum('booked','premium','paid','cancelled') DEFAULT 'booked',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `session_id`, `seat_row`, `seat_number`, `status`, `created_at`) VALUES
(12, 3, 1, 8, 17, 'premium', '2025-11-05 18:57:10'),
(13, 3, 2, 1, 9, 'booked', '2025-11-05 19:18:30'),
(14, 3, 2, 8, 20, 'premium', '2025-11-05 19:18:50'),
(15, 4, 3, 7, 12, 'premium', '2025-11-05 19:27:13'),
(16, 3, 4, 10, 8, 'premium', '2025-11-05 19:53:39'),
(17, 3, 4, 10, 16, 'premium', '2025-11-05 19:53:39'),
(18, 3, 4, 2, 15, 'premium', '2025-11-05 19:53:39'),
(19, 3, 4, 5, 1, 'premium', '2025-11-05 19:53:39'),
(20, 3, 4, 5, 11, 'premium', '2025-11-05 19:53:39'),
(21, 3, 4, 1, 2, 'premium', '2025-11-05 19:53:39'),
(22, 3, 4, 7, 18, 'premium', '2025-11-05 19:53:39'),
(23, 3, 4, 9, 9, 'premium', '2025-11-05 19:53:39'),
(24, 3, 4, 10, 5, 'premium', '2025-11-05 19:53:39'),
(25, 3, 4, 2, 2, 'premium', '2025-11-05 19:53:39');

-- --------------------------------------------------------

--
-- Структура таблицы `halls`
--

CREATE TABLE `halls` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_rows` int NOT NULL,
  `seats_per_row` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `halls`
--

INSERT INTO `halls` (`id`, `name`, `total_rows`, `seats_per_row`) VALUES
(1, 'Hall 1', 8, 12),
(2, 'Hall 2', 10, 15);

-- --------------------------------------------------------

--
-- Структура таблицы `movies`
--

CREATE TABLE `movies` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `genre` varchar(50) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration`, `release_date`, `poster`, `created_at`) VALUES
(1, 'Fast & Furious 10', 'Action movie about street racers', 'Action', 145, '2023-05-20', '1', '2025-10-28 10:42:09'),
(2, 'Barbie', 'Comedy about the life of Barbie in the real world', 'Comedy', 120, '2023-07-21', '2', '2025-10-28 10:42:09'),
(3, 'Dune: Part Two', 'Epic sci-fi continuation of the Dune saga', 'Sci-Fi', 165, '2024-03-01', '3', '2025-10-28 10:42:09'),
(4, 'Star Wars: Episode II - Attack of the Clones', 'Ten years after initially meeting, Anakin Skywalker shares a forbidden romance with Padmé Amidala, while Obi-Wan Kenobi discovers a secret clone army crafted for the Jedi.', 'Action', 140, '2023-05-20', '4', '2025-10-28 10:53:39'),
(5, 'Star Wars: Episode I - The Phantom Menace', 'Two Jedi escape a hostile blockade to find allies and come across a young boy who may bring balance to the Force but the long-dormant Sith resurface to claim their former glory.', 'Epic', 130, '1999-03-20', '5', '1999-03-19 22:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `movie_id` int NOT NULL,
  `hall_id` int NOT NULL,
  `show_time` datetime NOT NULL,
  `price` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`id`, `movie_id`, `hall_id`, `show_time`, `price`) VALUES
(1, 1, 1, '2025-11-01 19:00:00', '350.00'),
(2, 2, 2, '2025-11-01 20:30:00', '300.00'),
(3, 3, 1, '2025-11-02 18:00:00', '400.00'),
(4, 4, 2, '2025-12-09 00:00:00', '100.00'),
(5, 5, 1, '2025-12-10 00:00:00', '150.00');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '123456', 'user', '2025-10-28 10:42:09'),
(2, 'Admin User', 'admin@example.com', 'admin123', 'admin', '2025-10-28 10:42:09'),
(3, 'VJATSESLAV ROVLIN', 'vjatseslav.rovlin@tptlive.ee', '$2y$10$neicwYs5VsC2Jhvd3DR/ke2JQgdh9mjP8bSPmt4.0DDMOdeQkzW.2', 'user', '2025-11-05 18:53:53'),
(4, 'VJATSESLAV ROVLIN', 'slava.ryvlin@gmail.com', '$2y$10$pqSWu9LhV9Bmhama.BjNn.7DMaE2FJEoYxL8WjfqI/ne5WINTStSi', 'user', '2025-11-05 19:26:47');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Индексы таблицы `halls`
--
ALTER TABLE `halls`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `hall_id` (`hall_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `halls`
--
ALTER TABLE `halls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
