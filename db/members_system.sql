-- Create Database
CREATE DATABASE IF NOT EXISTS members_system;
USE members_system;

-- Users Table (With username, password, and role)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin (username: admin, password: 123)
INSERT INTO users (username, password, role) 
VALUES ('admin', SHA2('123', 256), 'admin')
ON DUPLICATE KEY UPDATE password=SHA2('123', 256);

-- Drop existing Events table if needed
DROP TABLE IF EXISTS events;

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,         
    place VARCHAR(255) NOT NULL,        
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    event_date DATETIME NOT NULL,       
    description TEXT,
    region VARCHAR(100),
    organizing_club VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    club_position VARCHAR(50) NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_date DATE GENERATED ALWAYS AS (DATE(date)) STORED,
    UNIQUE KEY unique_attendance (member_id, attendance_date)
);

-- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Membership Monitoring Table (Includes QR Code and Image Path)
CREATE TABLE IF NOT EXISTS membership_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    club_position VARCHAR(100),
    home_address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
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
    status ENUM('active', 'inactive') DEFAULT 'active',
    renewal_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- News Feed Table (for documentation & posts with media)
CREATE TABLE IF NOT EXISTS news_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                   
    title VARCHAR(255) NOT NULL,            
    description TEXT,                       
    media_path VARCHAR(255),                
    media_type ENUM('image','video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);



-- News Feed Table (for documentation & posts with media)
CREATE TABLE IF NOT EXISTS news_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                   -- Who posted
    title VARCHAR(255) NOT NULL,            -- Post title
    description TEXT,                       -- Post description/content
    media_path VARCHAR(255),                -- File path (image or video)
    media_type ENUM('image','video') NOT NULL, -- Media type
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

