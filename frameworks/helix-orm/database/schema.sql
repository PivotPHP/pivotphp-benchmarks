-- Database schema for benchmark tests

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for benchmarks
INSERT INTO users (name, email, created_at) VALUES
('User 1', 'user1@example.com', NOW()),
('User 2', 'user2@example.com', NOW()),
('User 3', 'user3@example.com', NOW()),
('User 4', 'user4@example.com', NOW()),
('User 5', 'user5@example.com', NOW());

-- Generate 1000 users for benchmark tests
INSERT INTO users (name, email, created_at)
SELECT 
    CONCAT('User ', n),
    CONCAT('user', n, '@example.com'),
    DATE_SUB(NOW(), INTERVAL n DAY)
FROM (
    SELECT a.n + b.n * 10 + c.n * 100 + 6 as n
    FROM 
        (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
    WHERE a.n + b.n * 10 + c.n * 100 + 6 <= 1000
) numbers;

-- Generate posts for users
INSERT INTO posts (user_id, title, content, created_at)
SELECT 
    u.id,
    CONCAT('Post ', p.n, ' by User ', u.id),
    CONCAT('Content of post ', p.n, ' created by user ', u.id),
    DATE_SUB(NOW(), INTERVAL p.n HOUR)
FROM users u
CROSS JOIN (
    SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) p
WHERE u.id <= 100;