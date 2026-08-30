-- Blip feature schema — run this AFTER schema.sql (users table must exist first)

CREATE TABLE IF NOT EXISTS blips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blip_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blip_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (blip_id, user_id),
    FOREIGN KEY (blip_id) REFERENCES blips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blip_reblips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blip_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reblip (blip_id, user_id),
    FOREIGN KEY (blip_id) REFERENCES blips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blip_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blip_id INT NOT NULL,
    user_id INT NOT NULL,
    text VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blip_id) REFERENCES blips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Images attached to a blip (0-4 per blip, ordered by `position`)
CREATE TABLE IF NOT EXISTS blip_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blip_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    position TINYINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blip_id) REFERENCES blips(id) ON DELETE CASCADE
);
