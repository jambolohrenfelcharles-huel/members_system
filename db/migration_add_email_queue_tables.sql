-- Migration: Add Email Queue Tables for Async Processing
-- This migration adds email queue tables for both MySQL and PostgreSQL

-- Email Queue Table (for async email processing)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP DEFAULT NULL
);

-- Email Queue Items Table (individual email recipients)
CREATE TABLE IF NOT EXISTS email_queue_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    member_id VARCHAR(50),
    member_name VARCHAR(255),
    member_email VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'sent', 'failed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    FOREIGN KEY (queue_id) REFERENCES email_queue(id) ON DELETE CASCADE
);

-- Indexes for email queue performance
CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status);
CREATE INDEX IF NOT EXISTS idx_email_queue_items_status ON email_queue_items(status);
CREATE INDEX IF NOT EXISTS idx_email_queue_items_queue_id ON email_queue_items(queue_id);
