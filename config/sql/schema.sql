CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_slug VARCHAR(40) NOT NULL,
    score INT NOT NULL,
    won TINYINT(1) NOT NULL DEFAULT 0,
    meta JSON NULL,
    created DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_game (user_id, game_slug),
    INDEX idx_game_score (game_slug, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE game_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) NOT NULL UNIQUE,
    game_slug VARCHAR(40) NOT NULL,
    host_id INT NOT NULL,
    state JSON NOT NULL,
    players JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'waiting',
    version INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_status (status, game_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
