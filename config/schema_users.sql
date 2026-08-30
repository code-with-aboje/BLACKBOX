-- Users directory feature schema — run this AFTER schema.sql (users table must exist first)

-- Add a bio/about field to users if it doesn't already exist
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'about'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN about VARCHAR(280) DEFAULT NULL AFTER role',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,   -- the user doing the following
    followed_id INT NOT NULL,   -- the user being followed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
);
