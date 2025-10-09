-- Migration script to add event_id column to attendance table
-- Run this script to update existing database

-- Add event_id column to attendance table
ALTER TABLE attendance 
ADD COLUMN event_id INTEGER REFERENCES events(id) ON DELETE CASCADE;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_event_id ON attendance(event_id);
