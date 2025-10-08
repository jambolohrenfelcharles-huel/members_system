-- Migration script to add status and renewal_date fields to membership_monitoring table
-- Run this script to update existing database

USE members_system;

-- Add status field with default 'active'
ALTER TABLE membership_monitoring 
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER image_path;

-- Add renewal_date field
ALTER TABLE membership_monitoring 
ADD COLUMN renewal_date DATE AFTER status;

-- Update existing members to set renewal_date to 1 year from created_at
UPDATE membership_monitoring 
SET renewal_date = DATE_ADD(created_at, INTERVAL 1 YEAR)
WHERE renewal_date IS NULL;

-- Update members who are past their renewal date to inactive status
UPDATE membership_monitoring 
SET status = 'inactive' 
WHERE renewal_date < CURDATE() AND status = 'active';
