-- Migration: Rename title column to name in events table
-- This migration fixes the schema mismatch between title and name columns

-- Rename title column to name if it exists
ALTER TABLE events RENAME COLUMN title TO name;

-- Update any existing records that might have NULL name values
UPDATE events SET name = 'Untitled Event' WHERE name IS NULL OR name = '';
