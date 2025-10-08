-- Migration script to add email field to membership_monitoring table
-- Run this script to update existing database

USE members_system;

-- Add email field
ALTER TABLE membership_monitoring 
ADD COLUMN email VARCHAR(255) UNIQUE AFTER name;

-- Add index for email field for better performance
CREATE INDEX idx_member_email ON membership_monitoring(email);
