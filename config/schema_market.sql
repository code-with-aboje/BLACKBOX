-- Marketplace + tokens feature schema — run this AFTER schema.sql (users table must exist first)

-- Give every user a spendable token balance. New signups get 2000 by default.
-- Existing users who don't have a balance yet are backfilled to 2000 as well.
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'tokens'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN tokens INT NOT NULL DEFAULT 2000',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tools published to the Market by users (via the Uploads page)
CREATE TABLE IF NOT EXISTS market_tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(16) DEFAULT '🛠',
    icon_image VARCHAR(255) DEFAULT NULL,
    description VARCHAR(500) NOT NULL,
    price_tokens INT NOT NULL DEFAULT 0,
    version VARCHAR(20) DEFAULT '1.0',
    size_label VARCHAR(20) DEFAULT NULL,
    tool_url VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Backfill for databases that already ran this file before icon_image/tool_url existed
SET @icon_image_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'market_tools' AND COLUMN_NAME = 'icon_image'
);
SET @sql = IF(@icon_image_exists = 0,
    'ALTER TABLE market_tools ADD COLUMN icon_image VARCHAR(255) DEFAULT NULL AFTER icon',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tool_url_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'market_tools' AND COLUMN_NAME = 'tool_url'
);
SET @sql = IF(@tool_url_exists = 0,
    'ALTER TABLE market_tools ADD COLUMN tool_url VARCHAR(500) NOT NULL DEFAULT \'\' AFTER size_label',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Records who has "gotten" which tool (also doubles as the downloads count)
CREATE TABLE IF NOT EXISTS market_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_download (tool_id, user_id),
    FOREIGN KEY (tool_id) REFERENCES market_tools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
