--------------------------------------------------
-- Complete Cinema DB with 3 sessions per movie
--------------------------------------------------

DROP DATABASE IF EXISTS cinema_db;
CREATE DATABASE cinema_db CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE cinema_db;

-- ----------------- HALLS -----------------
DROP TABLE IF EXISTS halls;
CREATE TABLE halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    total_rows INT NOT NULL,
    seats_per_row INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO halls (name, total_rows, seats_per_row) VALUES
('Hall 1', 8, 12),
('Hall 2', 10, 15);

-- ----------------- MOVIES -----------------
DROP TABLE IF EXISTS movies;
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    duration INT,
    release_date DATE,
    poster VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO movies (title, description, genre, duration, release_date, poster) VALUES
('Fast & Furious 10','Action movie about street racers','Action',145,'2023-05-20','1'),
('Barbie','Comedy about the life of Barbie in the real world','Comedy',120,'2023-07-21','2'),
('Dune: Part Two','Epic sci-fi continuation of the Dune saga','Sci-Fi',165,'2024-03-01','3'),
('Star Wars: Episode II - Attack of the Clones','Forbidden romance and secret clone army','Action',140,'2023-05-20','4'),
('Star Wars: Episode I - The Phantom Menace','Two Jedi escape a hostile blockade','Epic',130,'1999-03-20','5'),
('Star Wars: Episode III - Revenge of the Sith','Clone Wars nears its end, new threat emerges','Action',140,'2005-01-14','6'),
('Star Wars: Episode V - The Empire Strikes Back','Luke trains with Yoda, Empire chases friends','Action',125,'1980-01-01','7'),
('House of Dynamite','Single missile launches race to determine responsibility','Thriller',120,'2025-11-14','8');

-- ----------------- USERS -----------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name,email,password,role) VALUES
('VJATSESLAV ROVLIN','vjatseslav.rovlin@tptlive.ee','$2y$10$neicwYs5VsC2Jhvd3DR/ke2JQgdh9mjP8bSPmt4.0DDMOdeQkzW.2','admin'),
('VJATSESLAV ROVLIN','slava.ryvlin@gmail.com','$2y$10$pqSWu9LhV9Bmhama.BjNn.7DMaE2FJEoYxL8WjfqI/ne5WINTStSi','user'),
('Avalsiom OÜ','avalsiom@gmail.com','$2y$10$xNkkJ0DnE.6MibfKnyf2MO1fgOaDKxr2q9miZk7f7g6WTuxe6KNSC','user');

-- ----------------- SESSIONS -----------------
DROP TABLE IF EXISTS sessions;
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    hall_id INT NOT NULL,
    show_time DATETIME NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3 сеанса на фильм, без пересечений в одном зале
INSERT INTO sessions (movie_id, hall_id, show_time, price) VALUES
-- Film 1
(1, 1, CONCAT(CURDATE(), ' 12:00:00'), 150),
(1, 1, CONCAT(CURDATE(), ' 15:30:00'), 150),
(1, 1, CONCAT(CURDATE(), ' 19:00:00'), 150),
-- Film 2
(2, 2, CONCAT(CURDATE(), ' 12:00:00'), 120),
(2, 2, CONCAT(CURDATE(), ' 15:30:00'), 120),
(2, 2, CONCAT(CURDATE(), ' 19:00:00'), 120),
-- Film 3
(3, 1, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 12:00:00'), 180),
(3, 1, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 15:30:00'), 180),
(3, 1, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 19:00:00'), 180),
-- Film 4
(4, 2, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 12:00:00'), 140),
(4, 2, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 15:30:00'), 140),
(4, 2, CONCAT(CURDATE() + INTERVAL 1 DAY, ' 19:00:00'), 140),
-- Film 5
(5, 1, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 12:00:00'), 130),
(5, 1, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 15:30:00'), 130),
(5, 1, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 19:00:00'), 130),
-- Film 6
(6, 2, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 12:00:00'), 140),
(6, 2, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 15:30:00'), 140),
(6, 2, CONCAT(CURDATE() + INTERVAL 2 DAY, ' 19:00:00'), 140),
-- Film 7
(7, 1, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 12:00:00'), 125),
(7, 1, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 15:30:00'), 125),
(7, 1, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 19:00:00'), 125),
-- Film 8
(8, 2, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 12:00:00'), 120),
(8, 2, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 15:30:00'), 120),
(8, 2, CONCAT(CURDATE() + INTERVAL 3 DAY, ' 19:00:00'), 120);

-- ----------------- BOOKINGS -----------------
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NOT NULL,
    seat_row INT NOT NULL,
    seat_number INT NOT NULL,
    status ENUM('booked','premium','paid','cancelled') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------- PAYMENTS -----------------
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    payment_method ENUM('cash','card','online') DEFAULT 'cash',
    status ENUM('pending','paid','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
