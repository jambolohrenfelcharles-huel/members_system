-- Add parent_id for replies to comments
ALTER TABLE news_feed_comments ADD COLUMN parent_id INT DEFAULT NULL;
ALTER TABLE news_feed_comments ADD CONSTRAINT fk_parent_comment FOREIGN KEY (parent_id) REFERENCES news_feed_comments(id) ON DELETE CASCADE;

-- Table for reactions to comments
CREATE TABLE IF NOT EXISTS news_feed_comment_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comment_reaction (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES news_feed_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
