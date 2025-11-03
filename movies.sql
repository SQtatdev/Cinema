-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Ноя 03 2025 г., 20:56
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

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
