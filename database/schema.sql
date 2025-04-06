-- EduCoach Database Schema
-- Created for CS4116 Group 1 Project

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS ReviewResponses;
DROP TABLE IF EXISTS Reviews;
DROP TABLE IF EXISTS CustomerInsightMessages;
DROP TABLE IF EXISTS CustomerInsightRequests;
DROP TABLE IF EXISTS Messages;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS serviceinquiries;
DROP TABLE IF EXISTS ServiceTiers;
DROP TABLE IF EXISTS Coach_Availability;
DROP TABLE IF EXISTS Coach_Skills;
DROP TABLE IF EXISTS Skills;
DROP TABLE IF EXISTS Expertise_Categories;
DROP TABLE IF EXISTS CoachCategories;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS CoachTimeSlots;
DROP TABLE IF EXISTS Coaches;
DROP TABLE IF EXISTS UserPrivacySettings;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS BannedWords;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create Users table
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('regular', 'business', 'admin') DEFAULT 'regular',
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_banned BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL
);

-- Create UserPrivacySettings table
CREATE TABLE UserPrivacySettings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    allow_insight_requests TINYINT(1) NOT NULL DEFAULT 1,
    share_session_history TINYINT(1) NOT NULL DEFAULT 1,
    share_coach_ratings TINYINT(1) NOT NULL DEFAULT 1,
    public_profile TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create Coaches table
CREATE TABLE Coaches (
    coach_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    headline VARCHAR(255), -- Short professional headline
    about_me TEXT, -- Detailed coach description
    experience VARCHAR(50), -- Years of experience
    hourly_rate DECIMAL(8,2), -- Base hourly rate
    video_url VARCHAR(255), -- Intro video URL
    rating DECIMAL(3,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create Expertise Categories table
CREATE TABLE Expertise_Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Skills table
CREATE TABLE Skills (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE
);

-- Create Coach Skills table
CREATE TABLE Coach_Skills (
    coach_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    skill_id INT,
    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES Skills(skill_id) ON DELETE CASCADE
);

-- Create Coach Availability table
CREATE TABLE Coach_Availability (
    availability_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Create CoachTimeSlots table
CREATE TABLE CoachTimeSlots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Create ServiceTiers table
CREATE TABLE ServiceTiers (
    tier_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Create serviceinquiries table (lowercase to match existing)
CREATE TABLE serviceinquiries (
    inquiry_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    coach_id INT,
    tier_id INT,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
    FOREIGN KEY (tier_id) REFERENCES ServiceTiers(tier_id) ON DELETE SET NULL
);

-- Create sessions table (plural instead of singular)
CREATE TABLE sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    inquiry_id INT,
    learner_id INT,
    coach_id INT,
    tier_id INT,
    scheduled_time DATETIME,
    duration INT, -- in minutes
    status ENUM('scheduled', 'completed', 'cancelled'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES serviceinquiries(inquiry_id) ON DELETE SET NULL,
    FOREIGN KEY (learner_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
    FOREIGN KEY (tier_id) REFERENCES ServiceTiers(tier_id) ON DELETE SET NULL
);

-- Create Reviews table
CREATE TABLE Reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT,
    user_id INT,
    coach_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Drop BannedWords table if it exists
DROP TABLE IF EXISTS BannedWords;

-- Create BannedWords table
CREATE TABLE BannedWords (
    word_id INT PRIMARY KEY AUTO_INCREMENT,
    word VARCHAR(255) NOT NULL UNIQUE
);

-- Create ReviewResponses table
CREATE TABLE ReviewResponses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES Reviews(review_id) ON DELETE CASCADE
);

-- Create Messages table
CREATE TABLE Messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    inquiry_id INT,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (inquiry_id) REFERENCES serviceinquiries(inquiry_id) ON DELETE CASCADE
);

-- Create CustomerInsightRequests table
CREATE TABLE CustomerInsightRequests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT NOT NULL,
    verified_customer_id INT NOT NULL,
    coach_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_customer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Create CustomerInsightMessages table
CREATE TABLE CustomerInsightMessages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (request_id) REFERENCES CustomerInsightRequests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create Categories table
CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Create CoachCategories table
CREATE TABLE CoachCategories (
    coach_category_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    category_id INT,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_sessions_status ON sessions(status);
CREATE INDEX idx_reviews_rating ON Reviews(rating);
CREATE INDEX idx_coach_skills ON Coach_Skills(coach_id, skill_id);
CREATE INDEX idx_coach_availability ON Coach_Availability(coach_id, day_of_week);
CREATE INDEX idx_customer_insight_messages_request_id ON CustomerInsightMessages(request_id);
CREATE INDEX idx_customer_insight_messages_sender_receiver ON CustomerInsightMessages(sender_id, receiver_id);
CREATE INDEX idx_customer_insight_messages_is_read ON CustomerInsightMessages(is_read);
CREATE INDEX idx_coach_time_slots ON CoachTimeSlots(coach_id, status, start_time);

-- Check the resulting structure
SHOW CREATE TABLE sessions;

-- Add RescheduleRequests table
CREATE TABLE IF NOT EXISTS `RescheduleRequests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `new_time` datetime NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending', 'approved', 'rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `session_id` (`session_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `reschedule_requests_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `reschedule_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `Notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;