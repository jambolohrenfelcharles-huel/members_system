-- Migration: Add place column to events table
-- This migration adds the missing place column to the events table

-- Add place column to events table
ALTER TABLE events ADD COLUMN place VARCHAR(255) NOT NULL DEFAULT '';

-- Update existing records to have a default place if needed
UPDATE events SET place = 'TBA' WHERE place = '' OR place IS NULL;
