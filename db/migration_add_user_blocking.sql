-- Migration script to add user blocking functionality for PostgreSQL
-- Run this script to update existing database

-- Add blocking columns to users table if they don't exist
DO $$
BEGIN
    -- Add blocked column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'blocked') THEN
        ALTER TABLE users ADD COLUMN blocked BOOLEAN DEFAULT FALSE;
    END IF;
    
    -- Add blocked_reason column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'blocked_reason') THEN
        ALTER TABLE users ADD COLUMN blocked_reason TEXT DEFAULT NULL;
    END IF;
    
    -- Add blocked_at column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'blocked_at') THEN
        ALTER TABLE users ADD COLUMN blocked_at TIMESTAMP DEFAULT NULL;
    END IF;
END $$;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_users_blocked ON users(blocked);

-- Update existing users to have blocked = false
UPDATE users SET blocked = FALSE WHERE blocked IS NULL;
