-- PivotPHP Ecosystem Test Database Initialization
-- Database schema for Core + ReactPHP + Cycle ORM testing
-- Performance validation and integration testing

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ecosystem_test;
USE ecosystem_test;

-- Users table for ORM testing
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table for relationship testing
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table for nested relationships
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance metrics table for monitoring
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    configuration VARCHAR(100) NOT NULL,
    requests_per_second DECIMAL(10,2),
    avg_response_time_ms DECIMAL(10,4),
    memory_usage_mb DECIMAL(10,2),
    error_rate_percent DECIMAL(5,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    test_metadata JSON,
    INDEX idx_test_name (test_name),
    INDEX idx_configuration (configuration),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ecosystem health table
CREATE TABLE IF NOT EXISTS ecosystem_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component VARCHAR(100) NOT NULL,
    status ENUM('healthy', 'degraded', 'unhealthy') DEFAULT 'healthy',
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_time_ms DECIMAL(10,4),
    error_message TEXT,
    metadata JSON,
    INDEX idx_component (component),
    INDEX idx_status (status),
    INDEX idx_last_check (last_check)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test data for benchmarking

-- Test users
INSERT INTO users (name, email, password) VALUES
('Test User 1', 'user1@ecosystem.test', '$2y$10$example1'),
('Test User 2', 'user2@ecosystem.test', '$2y$10$example2'),
('Test User 3', 'user3@ecosystem.test', '$2y$10$example3'),
('Test User 4', 'user4@ecosystem.test', '$2y$10$example4'),
('Test User 5', 'user5@ecosystem.test', '$2y$10$example5'),
('Admin User', 'admin@ecosystem.test', '$2y$10$adminexample'),
('Demo User', 'demo@ecosystem.test', '$2y$10$demoexample'),
('Performance Tester', 'perf@ecosystem.test', '$2y$10$perfexample'),
('Load Tester', 'load@ecosystem.test', '$2y$10$loadexample'),
('Benchmark User', 'benchmark@ecosystem.test', '$2y$10$benchmarkexample');

-- Test posts
INSERT INTO posts (user_id, title, content, status, published_at) VALUES
(1, 'PivotPHP Core Performance', 'Testing the core framework performance with 6,227 req/sec validation.', 'published', NOW()),
(1, 'ReactPHP Integration Success', 'Achieved 19,707 req/sec with ReactPHP continuous runtime.', 'published', NOW()),
(2, 'Cycle ORM Benchmarks', 'Database operations reaching 457,870 ops/sec performance.', 'published', NOW()),
(2, 'Ecosystem Synergy Analysis', 'Full ecosystem performance with Core + ReactPHP + ORM integration.', 'draft', NULL),
(3, 'Production Deployment Guide', 'Best practices for deploying PivotPHP ecosystem in production.', 'published', NOW()),
(3, 'Performance Optimization Tips', 'Optimizing PHP 8.1+ with OPcache and JIT for maximum performance.', 'published', NOW()),
(4, 'Docker Container Setup', 'Standardized Docker environment for fair benchmarking.', 'published', NOW()),
(4, 'Load Testing Results', 'K6 stress testing results for ecosystem validation.', 'draft', NULL),
(5, 'Market Comparison Analysis', 'PivotPHP vs Slim 4, Lumen, and Flight performance comparison.', 'published', NOW()),
(5, 'Architectural Excellence', 'v1.1.4 architectural improvements and performance gains.', 'published', NOW());

-- Test comments
INSERT INTO comments (post_id, user_id, content, status) VALUES
(1, 2, 'Excellent performance results! Core framework is very competitive.', 'approved'),
(1, 3, 'The 6,227 req/sec in Docker is impressive for traditional PHP.', 'approved'),
(2, 1, 'ReactPHP extension really shines with 19,707 req/sec!', 'approved'),
(2, 4, 'Market leadership position confirmed with these numbers.', 'approved'),
(3, 5, 'Database performance is outstanding with Cycle ORM.', 'approved'),
(3, 1, '457,870 ops/sec shows excellent ORM optimization.', 'approved'),
(5, 2, 'Production deployment guide is very comprehensive.', 'approved'),
(6, 3, 'OPcache and JIT configuration tips are valuable.', 'approved'),
(7, 4, 'Docker setup ensures fair and reproducible benchmarks.', 'approved'),
(9, 5, 'Great competitive analysis against other frameworks.', 'approved');

-- Insert initial performance metrics
INSERT INTO performance_metrics (test_name, configuration, requests_per_second, avg_response_time_ms, memory_usage_mb, error_rate_percent, test_metadata) VALUES
('baseline_test', 'core_only', 6227.00, 0.32, 1.61, 0.00, '{"framework": "PivotPHP v1.1.4", "environment": "Docker"}'),
('reactphp_test', 'core_reactphp', 19707.00, 0.0507, 2.45, 0.00, '{"framework": "PivotPHP + ReactPHP", "runtime": "continuous"}'),
('orm_test', 'core_orm', 4500.00, 0.45, 3.20, 0.00, '{"framework": "PivotPHP + Cycle ORM", "database": "MySQL"}'),
('ecosystem_test', 'full_ecosystem', 12500.00, 0.18, 4.10, 0.00, '{"framework": "Full Ecosystem", "components": ["core", "reactphp", "orm"]}');

-- Insert ecosystem health status
INSERT INTO ecosystem_health (component, status, response_time_ms, metadata) VALUES
('pivotphp_core', 'healthy', 0.32, '{"version": "1.1.4", "performance": "6227 req/sec"}'),
('reactphp_extension', 'healthy', 0.0507, '{"version": "0.1.0", "performance": "19707 req/sec"}'),
('cycle_orm_extension', 'healthy', 0.45, '{"version": "1.0.1", "performance": "457870 ops/sec"}'),
('mysql_database', 'healthy', 1.20, '{"version": "8.0", "connections": 20}'),
('redis_cache', 'healthy', 0.15, '{"version": "7.0", "memory_usage": "50MB"}');

-- Create stored procedures for benchmark testing

DELIMITER ;;

-- Procedure to simulate database operations for ORM testing
CREATE PROCEDURE SimulateORMOperations(IN iterations INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE user_count INT;
    
    START TRANSACTION;
    
    WHILE i <= iterations DO
        -- Simulate user creation
        INSERT INTO users (name, email, password) 
        VALUES (CONCAT('Bench User ', i), CONCAT('bench', i, '@test.com'), 'benchpass');
        
        SET user_count = LAST_INSERT_ID();
        
        -- Simulate post creation
        INSERT INTO posts (user_id, title, content, status, published_at)
        VALUES (user_count, CONCAT('Benchmark Post ', i), CONCAT('Content for post ', i), 'published', NOW());
        
        SET i = i + 1;
    END WHILE;
    
    COMMIT;
END;;

-- Procedure to clean up benchmark data
CREATE PROCEDURE CleanupBenchmarkData()
BEGIN
    DELETE FROM comments WHERE post_id IN (SELECT id FROM posts WHERE title LIKE 'Benchmark Post%');
    DELETE FROM posts WHERE title LIKE 'Benchmark Post%';
    DELETE FROM users WHERE name LIKE 'Bench User%';
END;;

-- Function to calculate performance score
CREATE FUNCTION CalculatePerformanceScore(req_per_sec DECIMAL(10,2), response_time_ms DECIMAL(10,4), error_rate DECIMAL(5,2))
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE score DECIMAL(5,2);
    
    -- Score based on requests per second (0-40 points)
    SET score = LEAST(40, req_per_sec / 500);
    
    -- Bonus for low response time (0-30 points)
    SET score = score + GREATEST(0, 30 - response_time_ms);
    
    -- Penalty for errors (0-30 point deduction)
    SET score = score - (error_rate * 3);
    
    -- Ensure score is between 0 and 100
    SET score = GREATEST(0, LEAST(100, score));
    
    RETURN score;
END;;

DELIMITER ;

-- Create views for reporting

-- Performance summary view
CREATE VIEW performance_summary AS
SELECT 
    configuration,
    COUNT(*) as test_count,
    AVG(requests_per_second) as avg_req_per_sec,
    AVG(avg_response_time_ms) as avg_response_time,
    AVG(memory_usage_mb) as avg_memory_usage,
    AVG(error_rate_percent) as avg_error_rate,
    MAX(timestamp) as last_test
FROM performance_metrics
GROUP BY configuration;

-- Ecosystem health overview
CREATE VIEW ecosystem_health_overview AS
SELECT 
    component,
    status,
    response_time_ms,
    last_check,
    CASE 
        WHEN status = 'healthy' THEN 1.0
        WHEN status = 'degraded' THEN 0.5
        ELSE 0.0
    END as health_score
FROM ecosystem_health;

-- User activity summary
CREATE VIEW user_activity_summary AS
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(DISTINCT p.id) as post_count,
    COUNT(DISTINCT c.id) as comment_count,
    u.created_at
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
LEFT JOIN comments c ON u.id = c.user_id
GROUP BY u.id, u.name, u.email, u.created_at;

-- Grant permissions for benchmark user
CREATE USER IF NOT EXISTS 'benchmark'@'%' IDENTIFIED BY 'benchmark_password';
GRANT ALL PRIVILEGES ON ecosystem_test.* TO 'benchmark'@'%';
FLUSH PRIVILEGES;

-- Create indexes for optimal performance
CREATE INDEX idx_performance_test_config ON performance_metrics(test_name, configuration);
CREATE INDEX idx_health_component_status ON ecosystem_health(component, status);
CREATE INDEX idx_posts_user_status ON posts(user_id, status);
CREATE INDEX idx_comments_post_status ON comments(post_id, status);

-- Insert completion marker
INSERT INTO ecosystem_health (component, status, response_time_ms, metadata) 
VALUES ('database_init', 'healthy', 0.0, '{"initialization": "completed", "tables": 5, "test_data": "loaded"}');

-- Show initialization summary
SELECT 
    'Database Initialization Complete' as status,
    COUNT(DISTINCT table_name) as tables_created,
    (SELECT COUNT(*) FROM users) as test_users,
    (SELECT COUNT(*) FROM posts) as test_posts,
    (SELECT COUNT(*) FROM comments) as test_comments,
    NOW() as initialized_at
FROM information_schema.tables 
WHERE table_schema = 'ecosystem_test';