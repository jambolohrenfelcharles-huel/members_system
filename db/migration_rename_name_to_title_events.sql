-- Migration: Rename name column to title in events table
-- This migration fixes the schema mismatch between name and title columns

-- Rename name column to title if it exists
ALTER TABLE events RENAME COLUMN name TO title;

-- Update any existing records that might have NULL title values
UPDATE events SET title = 'Untitled Event' WHERE title IS NULL OR title = '';
