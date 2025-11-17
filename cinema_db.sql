-- --------------------------------------------------------
-- Хост:                         127.0.0.1
-- Версия сервера:               8.4.3 - MySQL Community Server - GPL
-- Операционная система:         Win64
-- HeidiSQL Версия:              12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Дамп структуры базы данных cinema_db
CREATE DATABASE IF NOT EXISTS `cinema_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `cinema_db`;

-- Дамп структуры для таблица cinema_db.bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` int NOT NULL,
  `seat_row` int NOT NULL,
  `seat_number` int NOT NULL,
  `status` enum('booked','premium','paid','cancelled') DEFAULT 'booked',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.bookings: ~0 rows (приблизительно)
INSERT INTO `bookings` (`id`, `user_id`, `session_id`, `seat_row`, `seat_number`, `status`, `created_at`) VALUES
	(3, 5, 16, 8, 17, 'booked', '2025-11-14 10:35:29');

-- Дамп структуры для таблица cinema_db.halls
CREATE TABLE IF NOT EXISTS `halls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `total_rows` int NOT NULL,
  `seats_per_row` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.halls: ~2 rows (приблизительно)
INSERT INTO `halls` (`id`, `name`, `total_rows`, `seats_per_row`) VALUES
	(1, 'Hall 1', 8, 12),
	(2, 'Hall 2', 10, 15);

-- Дамп структуры для таблица cinema_db.movies
CREATE TABLE IF NOT EXISTS `movies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `genre` varchar(50) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.movies: ~12 rows (приблизительно)
INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration`, `release_date`, `poster`, `created_at`) VALUES
	(1, 'Fast & Furious 10', 'Action movie about street racers', 'Action', 145, '2023-05-20', '1', '2025-11-14 10:23:34'),
	(2, 'Barbie', 'Comedy about the life of Barbie in the real world', 'Comedy', 120, '2023-07-21', '2', '2025-11-14 10:23:34'),
	(3, 'Dune: Part Two', 'Epic sci-fi continuation of the Dune saga', 'Sci-Fi', 165, '2024-03-01', '3', '2025-11-14 10:23:34'),
	(4, 'Star Wars: Episode II - Attack of the Clones', 'Forbidden romance and secret clone army', 'Action', 140, '2023-05-20', '4', '2025-11-14 10:23:34'),
	(5, 'Star Wars: Episode I - The Phantom Menace', 'Two Jedi escape a hostile blockade', 'Epic', 130, '1999-03-20', '5', '2025-11-14 10:23:34'),
	(6, 'Star Wars: Episode III - Revenge of the Sith', 'Clone Wars nears its end, new threat emerges', 'Action', 140, '2005-01-14', '6', '2025-11-14 10:23:34'),
	(7, 'Star Wars: Episode V - The Empire Strikes Back', 'Luke trains with Yoda, Empire chases friends', 'Action', 125, '1980-01-01', '7', '2025-11-14 10:23:34'),
	(8, 'House of Dynamite', 'Single missile launches race to determine responsibility', 'Thriller', 120, '2025-11-14', '8', '2025-11-14 10:23:34'),
	(9, 'Алиса в Стране чудес', '15-летняя Алиса попадает в Страну чудес, где солнце всегда в зените и нет времени. Среди жителей она узнаёт своих родных, знакомых и друзей, но никто из них не узнаёт Алису. Она для них чужая, антипят, а сами жители Страны чудес называют себя антиподами. Чтобы вернуться домой, Алисе предстоит преодолеть множество препятствий и странностей.', 'fantasy', 108, '2005-07-14', '9', '2025-11-16 19:02:45'),
	(10, 'Borat: Cultural Learnings of America for Make Benefit Glorious Nation of Kazakhstan', 'Kazakh TV talking head Borat is dispatched to the United States to report on the greatest country in the world.', 'comedy', 84, '2006-06-07', '10', '2025-11-16 19:03:28'),
	(11, 'The Hangover', 'Three buddies wake up from a bachelor party in Las Vegas with no memory of the previous night and the bachelor missing. They must make their way around the city in order to find their friend in time for his wedding.', 'comedy', 100, '2010-06-08', '11', '2025-11-16 19:04:06'),
	(12, "You Don\'t Mess with the Zohan", 'An Israeli Special Forces Soldier fakes his death so he can re-emerge in New York City as a hair stylist.', 'comedy, action', 113, '2008-10-07', '12', '2025-11-16 19:07:20');

-- Дамп структуры для таблица cinema_db.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.payments: ~0 rows (приблизительно)

-- Дамп структуры для таблица cinema_db.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `movie_id` int NOT NULL,
  `hall_id` int NOT NULL,
  `show_time` datetime NOT NULL,
  `price` decimal(6,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_id` (`movie_id`),
  KEY `hall_id` (`hall_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.sessions: ~24 rows (приблизительно)
INSERT INTO sessions (movie_id, hall_id, show_time, price) VALUES
(1, 1, '2025-12-05 10:00:00', 150.00),
(1, 1, '2026-01-05 10:00:00', 150.00),
(1, 1, '2026-02-05 10:00:00', 150.00),
(1, 1, '2026-03-05 10:00:00', 150.00),
(1, 1, '2026-04-05 10:00:00', 150.00),
(1, 1, '2026-05-05 10:00:00', 150.00),
(1, 1, '2026-06-05 10:00:00', 150.00),
(1, 1, '2026-07-05 10:00:00', 150.00),
(1, 1, '2026-08-05 10:00:00', 150.00),
(1, 1, '2026-09-05 10:00:00', 150.00),
(1, 1, '2026-10-05 10:00:00', 150.00),
(1, 1, '2026-11-05 10:00:00', 150.00),

(2, 2, '2025-12-05 12:00:00', 150.00),
(2, 2, '2026-01-05 12:00:00', 150.00),
(2, 2, '2026-02-05 12:00:00', 150.00),
(2, 2, '2026-03-05 12:00:00', 150.00),
(2, 2, '2026-04-05 12:00:00', 150.00),
(2, 2, '2026-05-05 12:00:00', 150.00),
(2, 2, '2026-06-05 12:00:00', 150.00),
(2, 2, '2026-07-05 12:00:00', 150.00),
(2, 2, '2026-08-05 12:00:00', 150.00),
(2, 2, '2026-09-05 12:00:00', 150.00),
(2, 2, '2026-10-05 12:00:00', 150.00),
(2, 2, '2026-11-05 12:00:00', 150.00),

(3, 1, '2025-12-05 14:00:00', 150.00),
(3, 1, '2026-01-05 14:00:00', 150.00),
(3, 1, '2026-02-05 14:00:00', 150.00),
(3, 1, '2026-03-05 14:00:00', 150.00),
(3, 1, '2026-04-05 14:00:00', 150.00),
(3, 1, '2026-05-05 14:00:00', 150.00),
(3, 1, '2026-06-05 14:00:00', 150.00),
(3, 1, '2026-07-05 14:00:00', 150.00),
(3, 1, '2026-08-05 14:00:00', 150.00),
(3, 1, '2026-09-05 14:00:00', 150.00),
(3, 1, '2026-10-05 14:00:00', 150.00),
(3, 1, '2026-11-05 14:00:00', 150.00),

(4, 2, '2025-12-05 16:00:00', 150.00),
(4, 2, '2026-01-05 16:00:00', 150.00),
(4, 2, '2026-02-05 16:00:00', 150.00),
(4, 2, '2026-03-05 16:00:00', 150.00),
(4, 2, '2026-04-05 16:00:00', 150.00),
(4, 2, '2026-05-05 16:00:00', 150.00),
(4, 2, '2026-06-05 16:00:00', 150.00),
(4, 2, '2026-07-05 16:00:00', 150.00),
(4, 2, '2026-08-05 16:00:00', 150.00),
(4, 2, '2026-09-05 16:00:00', 150.00),
(4, 2, '2026-10-05 16:00:00', 150.00),
(4, 2, '2026-11-05 16:00:00', 150.00),

(5, 1, '2025-12-05 18:00:00', 150.00),
(5, 1, '2026-01-05 18:00:00', 150.00),
(5, 1, '2026-02-05 18:00:00', 150.00),
(5, 1, '2026-03-05 18:00:00', 150.00),
(5, 1, '2026-04-05 18:00:00', 150.00),
(5, 1, '2026-05-05 18:00:00', 150.00),
(5, 1, '2026-06-05 18:00:00', 150.00),
(5, 1, '2026-07-05 18:00:00', 150.00),
(5, 1, '2026-08-05 18:00:00', 150.00),
(5, 1, '2026-09-05 18:00:00', 150.00),
(5, 1, '2026-10-05 18:00:00', 150.00),
(5, 1, '2026-11-05 18:00:00', 150.00),

(6, 2, '2025-12-05 20:00:00', 150.00),
(6, 2, '2026-01-05 20:00:00', 150.00),
(6, 2, '2026-02-05 20:00:00', 150.00),
(6, 2, '2026-03-05 20:00:00', 150.00),
(6, 2, '2026-04-05 20:00:00', 150.00),
(6, 2, '2026-05-05 20:00:00', 150.00),
(6, 2, '2026-06-05 20:00:00', 150.00),
(6, 2, '2026-07-05 20:00:00', 150.00),
(6, 2, '2026-08-05 20:00:00', 150.00),
(6, 2, '2026-09-05 20:00:00', 150.00),
(6, 2, '2026-10-05 20:00:00', 150.00),
(6, 2, '2026-11-05 20:00:00', 150.00),

(7, 1, '2025-12-05 22:00:00', 150.00),
(7, 1, '2026-01-05 22:00:00', 150.00),
(7, 1, '2026-02-05 22:00:00', 150.00),
(7, 1, '2026-03-05 22:00:00', 150.00),
(7, 1, '2026-04-05 22:00:00', 150.00),
(7, 1, '2026-05-05 22:00:00', 150.00),
(7, 1, '2026-06-05 22:00:00', 150.00),
(7, 1, '2026-07-05 22:00:00', 150.00),
(7, 1, '2026-08-05 22:00:00', 150.00),
(7, 1, '2026-09-05 22:00:00', 150.00),
(7, 1, '2026-10-05 22:00:00', 150.00),
(7, 1, '2026-11-05 22:00:00', 150.00),

(8, 2, '2025-12-05 11:00:00', 150.00),
(8, 2, '2026-01-05 11:00:00', 150.00),
(8, 2, '2026-02-05 11:00:00', 150.00),
(8, 2, '2026-03-05 11:00:00', 150.00),
(8, 2, '2026-04-05 11:00:00', 150.00),
(8, 2, '2026-05-05 11:00:00', 150.00),
(8, 2, '2026-06-05 11:00:00', 150.00),
(8, 2, '2026-07-05 11:00:00', 150.00),
(8, 2, '2026-08-05 11:00:00', 150.00),
(8, 2, '2026-09-05 11:00:00', 150.00),
(8, 2, '2026-10-05 11:00:00', 150.00),
(8, 2, '2026-11-05 11:00:00', 150.00),

(9, 1, '2025-12-05 13:00:00', 150.00),
(9, 1, '2026-01-05 13:00:00', 150.00),
(9, 1, '2026-02-05 13:00:00', 150.00),
(9, 1, '2026-03-05 13:00:00', 150.00),
(9, 1, '2026-04-05 13:00:00', 150.00),
(9, 1, '2026-05-05 13:00:00', 150.00),
(9, 1, '2026-06-05 13:00:00', 150.00),
(9, 1, '2026-07-05 13:00:00', 150.00),
(9, 1, '2026-08-05 13:00:00', 150.00),
(9, 1, '2026-09-05 13:00:00', 150.00),
(9, 1, '2026-10-05 13:00:00', 150.00),
(9, 1, '2026-11-05 13:00:00', 150.00),

(10, 2, '2025-12-05 15:00:00', 150.00),
(10, 2, '2026-01-05 15:00:00', 150.00),
(10, 2, '2026-02-05 15:00:00', 150.00),
(10, 2, '2026-03-05 15:00:00', 150.00),
(10, 2, '2026-04-05 15:00:00', 150.00),
(10, 2, '2026-05-05 15:00:00', 150.00),
(10, 2, '2026-06-05 15:00:00', 150.00),
(10, 2, '2026-07-05 15:00:00', 150.00),
(10, 2, '2026-08-05 15:00:00', 150.00),
(10, 2, '2026-09-05 15:00:00', 150.00),
(10, 2, '2026-10-05 15:00:00', 150.00),
(10, 2, '2026-11-05 15:00:00', 150.00),

(11, 1, '2025-12-05 17:00:00', 150.00),
(11, 1, '2026-01-05 17:00:00', 150.00),
(11, 1, '2026-02-05 17:00:00', 150.00),
(11, 1, '2026-03-05 17:00:00', 150.00),
(11, 1, '2026-04-05 17:00:00', 150.00),
(11, 1, '2026-05-05 17:00:00', 150.00),
(11, 1, '2026-06-05 17:00:00', 150.00),
(11, 1, '2026-07-05 17:00:00', 150.00),
(11, 1, '2026-08-05 17:00:00', 150.00),
(11, 1, '2026-09-05 17:00:00', 150.00),
(11, 1, '2026-10-05 17:00:00', 150.00),
(11, 1, '2026-11-05 17:00:00', 150.00),

(12, 2, '2025-12-05 19:00:00', 150.00),
(12, 2, '2026-01-05 19:00:00', 150.00),
(12, 2, '2026-02-05 19:00:00', 150.00),
(12, 2, '2026-03-05 19:00:00', 150.00),
(12, 2, '2026-04-05 19:00:00', 150.00),
(12, 2, '2026-05-05 19:00:00', 150.00),
(12, 2, '2026-06-05 19:00:00', 150.00),
(12, 2, '2026-07-05 19:00:00', 150.00),
(12, 2, '2026-08-05 19:00:00', 150.00),
(12, 2, '2026-09-05 19:00:00', 150.00),
(12, 2, '2026-10-05 19:00:00', 150.00),
(12, 2, '2026-11-05 19:00:00', 150.00);

-- Дамп структуры для таблица cinema_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Дамп данных таблицы cinema_db.users: ~3 rows (приблизительно)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
	(1, 'VJATSESLAV ROVLIN', 'vjatseslav.rovlin@tptlive.ee', '$2y$10$neicwYs5VsC2Jhvd3DR/ke2JQgdh9mjP8bSPmt4.0DDMOdeQkzW.2', 'admin', '2025-11-14 10:23:34'),
	(2, 'VJATSESLAV ROVLIN', 'slava.ryvlin@gmail.com', '$2y$10$pqSWu9LhV9Bmhama.BjNn.7DMaE2FJEoYxL8WjfqI/ne5WINTStSi', 'user', '2025-11-14 10:23:34'),
	(3, 'Avalsiom OÜ', 'avalsiom@gmail.com', '$2y$10$xNkkJ0DnE.6MibfKnyf2MO1fgOaDKxr2q9miZk7f7g6WTuxe6KNSC', 'user', '2025-11-14 10:23:34'),
	(5, 'marta', 'marta@gmail.com', '$2y$10$QgIIERrvDMb3rB/0Yf.9e.7ap8cPeBr2MrS1qGxmgx1ZW/LrvOYoS', 'user', '2025-11-14 10:35:29');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
s