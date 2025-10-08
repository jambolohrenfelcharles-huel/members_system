-- Migration script to add email field to users table
-- Run this script to update existing database

USE members_system;

-- Add email field
ALTER TABLE users 
ADD COLUMN email VARCHAR(255) UNIQUE AFTER username;

-- Add full_name field
ALTER TABLE users 
ADD COLUMN full_name VARCHAR(255) AFTER email;

-- Update existing admin user with default email
UPDATE users 
SET email = 'admin@smartunion.com', full_name = 'System Administrator'
WHERE username = 'admin' AND email IS NULL;
