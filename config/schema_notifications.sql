-- Notifications feature schema — run this AFTER schema.sql, schema_blip.sql, and
-- schema_market.sql (users, blips, and market_tools tables must exist first)

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,              -- who receives this notification
    actor_id INT NOT NULL,             -- who caused it (the follower/liker/commenter/buyer)
    type VARCHAR(20) NOT NULL,         -- 'follow' | 'like' | 'comment' | 'tool_get'
    blip_id INT DEFAULT NULL,
    tool_id INT DEFAULT NULL,
    comment_text VARCHAR(160) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blip_id) REFERENCES blips(id) ON DELETE CASCADE,
    FOREIGN KEY (tool_id) REFERENCES market_tools(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
);
