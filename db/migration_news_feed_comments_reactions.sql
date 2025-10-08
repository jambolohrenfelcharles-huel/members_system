-- News Feed Comments Table
CREATE TABLE IF NOT EXISTS news_feed_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_feed_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_feed_id) REFERENCES news_feed(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- News Feed Reactions Table
CREATE TABLE IF NOT EXISTS news_feed_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_feed_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type VARCHAR(20) NOT NULL, -- e.g., 'like', 'love', 'haha', etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (news_feed_id, user_id, reaction_type),
    FOREIGN KEY (news_feed_id) REFERENCES news_feed(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
