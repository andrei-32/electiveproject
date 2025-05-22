CREATE DATABASE IF NOT EXISTS rps_game;
USE rps_game;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS game_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    draws INT DEFAULT 0,
    winstreak INT DEFAULT 0,
    highest_winstreak INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS rooms (
    room_id VARCHAR(6) PRIMARY KEY,
    player1_id INT NOT NULL,
    player2_id INT DEFAULT NULL,
    player1_score INT DEFAULT 0,
    player2_score INT DEFAULT 0,
    player1_choice VARCHAR(10) DEFAULT NULL,
    player2_choice VARCHAR(10) DEFAULT NULL,
    round_complete BOOLEAN DEFAULT FALSE,
    status ENUM('waiting', 'playing', 'completed') DEFAULT 'waiting',
    rematch BOOLEAN DEFAULT FALSE,
    player1_rematch BOOLEAN DEFAULT FALSE,
    player2_rematch BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player1_id) REFERENCES users(id),
    FOREIGN KEY (player2_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS game_moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(6),
    user_id INT,
    move ENUM('rock', 'paper', 'scissors'),
    round_number INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 