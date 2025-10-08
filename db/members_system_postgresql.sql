-- PostgreSQL version of members_system.sql
-- Complete schema with all features from MySQL version

-- Users Table (With username, password, email, full_name, and role)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    full_name VARCHAR(255),
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('admin', 'member')),
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin (username: admin, password: 123)
-- Using simple SHA256 hash for compatibility with login.php
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'admin@smartunion.com', 'System Administrator', 'admin')
ON CONFLICT (username) DO UPDATE SET 
    password = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
    email = 'admin@smartunion.com',
    full_name = 'System Administrator';

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,         
    place VARCHAR(255) NOT NULL,        
    status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
    event_date TIMESTAMP NOT NULL,       
    description TEXT,
    region VARCHAR(100),
    organizing_club VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id SERIAL PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    club_position VARCHAR(50) NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_date DATE GENERATED ALWAYS AS (date::date) STORED
);

-- Members Table (equivalent to membership_monitoring in MySQL)
CREATE TABLE IF NOT EXISTS members (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    member_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    club_position VARCHAR(100),
    home_address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    phone VARCHAR(20),
    philhealth_number VARCHAR(50),
    pagibig_number VARCHAR(50),
    tin_number VARCHAR(50),
    birthdate DATE NOT NULL,
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    blood_type VARCHAR(5),
    religion VARCHAR(50),
    emergency_contact_person VARCHAR(255) NOT NULL,
    emergency_contact_number VARCHAR(20) NOT NULL,
    club_affiliation VARCHAR(255),
    region VARCHAR(100),
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    image_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    renewal_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- News Feed Table
CREATE TABLE IF NOT EXISTS news_feed (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    media_path VARCHAR(500),
    media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id SERIAL PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- News Feed Comments Table
CREATE TABLE IF NOT EXISTS news_feed_comments (
    id SERIAL PRIMARY KEY,
    news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    comment TEXT NOT NULL,
    parent_id INTEGER DEFAULT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- News Feed Reactions Table
CREATE TABLE IF NOT EXISTS news_feed_reactions (
    id SERIAL PRIMARY KEY,
    news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(news_feed_id, user_id, reaction_type)
);

-- News Feed Comment Reactions Table
CREATE TABLE IF NOT EXISTS news_feed_comment_reactions (
    id SERIAL PRIMARY KEY,
    comment_id INTEGER NOT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(comment_id, user_id)
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date);
CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_members_email ON members(email);
CREATE INDEX IF NOT EXISTS idx_members_status ON members(status);
CREATE INDEX IF NOT EXISTS idx_members_renewal ON members(renewal_date);
CREATE INDEX IF NOT EXISTS idx_news_feed_comments_post ON news_feed_comments(news_feed_id);
CREATE INDEX IF NOT EXISTS idx_news_feed_comments_parent ON news_feed_comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_news_feed_reactions_post ON news_feed_reactions(news_feed_id);
CREATE INDEX IF NOT EXISTS idx_news_feed_comment_reactions_comment ON news_feed_comment_reactions(comment_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read);
