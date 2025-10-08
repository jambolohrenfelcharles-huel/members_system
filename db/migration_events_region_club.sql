-- Migration script to add region and organizing_club fields to events table
-- Run this script to update existing database

USE members_system;

-- Add region column
ALTER TABLE events 
ADD COLUMN region VARCHAR(100) AFTER description;

-- Add organizing_club column
ALTER TABLE events 
ADD COLUMN organizing_club VARCHAR(255) AFTER region;
