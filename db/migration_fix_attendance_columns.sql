-- Migration script to fix attendance table columns for PostgreSQL
-- Run this script to update existing database

-- Add missing columns to attendance table if they don't exist
DO $$
BEGIN
    -- Add status column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'status') THEN
        ALTER TABLE attendance ADD COLUMN status VARCHAR(20) DEFAULT 'present' CHECK (status IN ('present', 'absent', 'late'));
    END IF;
    
    -- Add event_name column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'event_name') THEN
        ALTER TABLE attendance ADD COLUMN event_name VARCHAR(255);
    END IF;
    
    -- Add semester column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'semester') THEN
        ALTER TABLE attendance ADD COLUMN semester INTEGER;
    END IF;
    
    -- Add schoolyear column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'schoolyear') THEN
        ALTER TABLE attendance ADD COLUMN schoolyear VARCHAR(20);
    END IF;
    
    -- Add dateadded column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'dateadded') THEN
        ALTER TABLE attendance ADD COLUMN dateadded TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;
    
    -- Ensure full_name column exists (should already exist but just in case)
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'full_name') THEN
        ALTER TABLE attendance ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '';
    END IF;
    
    -- Ensure club_position column exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'club_position') THEN
        ALTER TABLE attendance ADD COLUMN club_position VARCHAR(50) NOT NULL DEFAULT '';
    END IF;
END $$;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_full_name ON attendance(full_name);
CREATE INDEX IF NOT EXISTS idx_attendance_club_position ON attendance(club_position);
CREATE INDEX IF NOT EXISTS idx_attendance_status ON attendance(status);
