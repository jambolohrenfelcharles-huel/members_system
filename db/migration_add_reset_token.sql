-- Add columns for password reset functionality
ALTER TABLE users
ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL,
ADD COLUMN reset_expires DATETIME DEFAULT NULL;
