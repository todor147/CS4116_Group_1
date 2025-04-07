-- EduCoach Database Schema
-- Created for CS4116 Group 1 Project
-- Modified for InfinityFree compatibility

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS RescheduleRequests;
DROP TABLE IF EXISTS Notifications;
DROP TABLE IF EXISTS Coach_Custom_Skills;
DROP TABLE IF EXISTS ReviewResponses;
DROP TABLE IF EXISTS Reviews;
DROP TABLE IF EXISTS CustomerInsightMessages;
DROP TABLE IF EXISTS CustomerInsightRequests;
DROP TABLE IF EXISTS Messages;
DROP TABLE IF EXISTS Sessions;
DROP TABLE IF EXISTS ServiceInquiries;
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

-- Create tables first without foreign keys

-- Create Users table
CREATE TABLE IF NOT EXISTS Users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create UserPrivacySettings table (without FK)
CREATE TABLE IF NOT EXISTS UserPrivacySettings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    allow_insight_requests TINYINT(1) NOT NULL DEFAULT 1,
    share_session_history TINYINT(1) NOT NULL DEFAULT 1,
    share_coach_ratings TINYINT(1) NOT NULL DEFAULT 1,
    public_profile TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Coaches table (without FK)
CREATE TABLE IF NOT EXISTS Coaches (
    coach_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    headline VARCHAR(255), -- Short professional headline
    about_me TEXT, -- Detailed coach description
    experience VARCHAR(50), -- Years of experience
    hourly_rate DECIMAL(8,2), -- Base hourly rate
    video_url VARCHAR(255), -- Intro video URL
    custom_category VARCHAR(100) NULL, -- Custom category for coaches
    rating DECIMAL(3,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Expertise Categories table
CREATE TABLE IF NOT EXISTS Expertise_Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Skills table (without FK)
CREATE TABLE IF NOT EXISTS Skills (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Coach Skills table (without FK)
CREATE TABLE IF NOT EXISTS Coach_Skills (
    coach_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    skill_id INT,
    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Coach Custom Skills table (without FK)
CREATE TABLE IF NOT EXISTS Coach_Custom_Skills (
    custom_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT NOT NULL,
    category_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Coach Availability table (without FK)
CREATE TABLE IF NOT EXISTS Coach_Availability (
    availability_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create CoachTimeSlots table (without FK)
CREATE TABLE IF NOT EXISTS CoachTimeSlots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ServiceTiers table (without FK)
CREATE TABLE IF NOT EXISTS ServiceTiers (
    tier_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    is_popular TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ServiceInquiries table (capitalized to match application code) (without FK)
CREATE TABLE IF NOT EXISTS ServiceInquiries (
    inquiry_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    coach_id INT,
    tier_id INT,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Sessions table (capitalized to match application code) (without FK)
CREATE TABLE IF NOT EXISTS Sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    inquiry_id INT,
    learner_id INT,
    coach_id INT,
    tier_id INT,
    scheduled_time DATETIME,
    duration INT, -- in minutes
    status ENUM('scheduled', 'completed', 'cancelled'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Reviews table (without FK)
CREATE TABLE IF NOT EXISTS Reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT,
    user_id INT,
    coach_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create BannedWords table
CREATE TABLE IF NOT EXISTS BannedWords (
    word_id INT PRIMARY KEY AUTO_INCREMENT,
    word VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ReviewResponses table (without FK)
CREATE TABLE IF NOT EXISTS ReviewResponses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Messages table (without FK)
CREATE TABLE IF NOT EXISTS Messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    inquiry_id INT,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create CustomerInsightRequests table (without FK)
CREATE TABLE IF NOT EXISTS CustomerInsightRequests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT NOT NULL,
    verified_customer_id INT NOT NULL,
    coach_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create CustomerInsightMessages table (without FK)
CREATE TABLE IF NOT EXISTS CustomerInsightMessages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Categories table
CREATE TABLE IF NOT EXISTS Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create CoachCategories table (without FK)
CREATE TABLE IF NOT EXISTS CoachCategories (
    coach_category_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    category_id INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add RescheduleRequests table (without FK)
CREATE TABLE IF NOT EXISTS RescheduleRequests (
  request_id INT NOT NULL AUTO_INCREMENT,
  session_id INT NOT NULL,
  requester_id INT NOT NULL,
  new_time DATETIME NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  responded_at DATETIME DEFAULT NULL,
  PRIMARY KEY (request_id),
  KEY session_id (session_id),
  KEY requester_id (requester_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Notifications table (without FK)
CREATE TABLE IF NOT EXISTS Notifications (
  notification_id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (notification_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Now add all foreign keys with ALTER TABLE statements

-- UserPrivacySettings foreign keys
ALTER TABLE UserPrivacySettings
ADD CONSTRAINT fk_user_privacy_user 
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE;

-- Coaches foreign keys
ALTER TABLE Coaches
ADD CONSTRAINT fk_coaches_user
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE;

-- Skills foreign keys
ALTER TABLE Skills
ADD CONSTRAINT fk_skills_category
FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE;

-- Coach_Skills foreign keys
ALTER TABLE Coach_Skills
ADD CONSTRAINT fk_coach_skills_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

ALTER TABLE Coach_Skills
ADD CONSTRAINT fk_coach_skills_skill
FOREIGN KEY (skill_id) REFERENCES Skills(skill_id) ON DELETE CASCADE;

-- Coach_Custom_Skills foreign keys
ALTER TABLE Coach_Custom_Skills
ADD CONSTRAINT fk_coach_custom_skills_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

ALTER TABLE Coach_Custom_Skills
ADD CONSTRAINT fk_coach_custom_skills_category
FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE;

-- Coach_Availability foreign keys
ALTER TABLE Coach_Availability
ADD CONSTRAINT fk_coach_availability_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

-- CoachTimeSlots foreign keys
ALTER TABLE CoachTimeSlots
ADD CONSTRAINT fk_coach_time_slots_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

-- ServiceTiers foreign keys
ALTER TABLE ServiceTiers
ADD CONSTRAINT fk_service_tiers_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

-- ServiceInquiries foreign keys
ALTER TABLE ServiceInquiries
ADD CONSTRAINT fk_service_inquiries_user
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE ServiceInquiries
ADD CONSTRAINT fk_service_inquiries_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

ALTER TABLE ServiceInquiries
ADD CONSTRAINT fk_service_inquiries_tier
FOREIGN KEY (tier_id) REFERENCES ServiceTiers(tier_id) ON DELETE SET NULL;

-- Sessions foreign keys
ALTER TABLE Sessions
ADD CONSTRAINT fk_sessions_inquiry
FOREIGN KEY (inquiry_id) REFERENCES ServiceInquiries(inquiry_id) ON DELETE SET NULL;

ALTER TABLE Sessions
ADD CONSTRAINT fk_sessions_learner
FOREIGN KEY (learner_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE Sessions
ADD CONSTRAINT fk_sessions_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

ALTER TABLE Sessions
ADD CONSTRAINT fk_sessions_tier
FOREIGN KEY (tier_id) REFERENCES ServiceTiers(tier_id) ON DELETE SET NULL;

-- Reviews foreign keys
ALTER TABLE Reviews
ADD CONSTRAINT fk_reviews_session
FOREIGN KEY (session_id) REFERENCES Sessions(session_id) ON DELETE CASCADE;

ALTER TABLE Reviews
ADD CONSTRAINT fk_reviews_user
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE Reviews
ADD CONSTRAINT fk_reviews_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

-- ReviewResponses foreign keys
ALTER TABLE ReviewResponses
ADD CONSTRAINT fk_review_responses_review
FOREIGN KEY (review_id) REFERENCES Reviews(review_id) ON DELETE CASCADE;

-- Messages foreign keys
ALTER TABLE Messages
ADD CONSTRAINT fk_messages_sender
FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE Messages
ADD CONSTRAINT fk_messages_receiver
FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE Messages
ADD CONSTRAINT fk_messages_inquiry
FOREIGN KEY (inquiry_id) REFERENCES ServiceInquiries(inquiry_id) ON DELETE CASCADE;

-- CustomerInsightRequests foreign keys
ALTER TABLE CustomerInsightRequests
ADD CONSTRAINT fk_customer_insight_requests_requester
FOREIGN KEY (requester_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE CustomerInsightRequests
ADD CONSTRAINT fk_customer_insight_requests_verified_customer
FOREIGN KEY (verified_customer_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE CustomerInsightRequests
ADD CONSTRAINT fk_customer_insight_requests_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

-- CustomerInsightMessages foreign keys
ALTER TABLE CustomerInsightMessages
ADD CONSTRAINT fk_customer_insight_messages_request
FOREIGN KEY (request_id) REFERENCES CustomerInsightRequests(request_id) ON DELETE CASCADE;

ALTER TABLE CustomerInsightMessages
ADD CONSTRAINT fk_customer_insight_messages_sender
FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE;

ALTER TABLE CustomerInsightMessages
ADD CONSTRAINT fk_customer_insight_messages_receiver
FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE;

-- CoachCategories foreign keys
ALTER TABLE CoachCategories
ADD CONSTRAINT fk_coach_categories_coach
FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE;

ALTER TABLE CoachCategories
ADD CONSTRAINT fk_coach_categories_category
FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE CASCADE;

-- RescheduleRequests foreign keys
ALTER TABLE RescheduleRequests
ADD CONSTRAINT fk_reschedule_requests_session
FOREIGN KEY (session_id) REFERENCES Sessions(session_id) ON DELETE CASCADE;

ALTER TABLE RescheduleRequests
ADD CONSTRAINT fk_reschedule_requests_requester
FOREIGN KEY (requester_id) REFERENCES Users(user_id) ON DELETE CASCADE;

-- Notifications foreign keys
ALTER TABLE Notifications
ADD CONSTRAINT fk_notifications_user
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE;

-- Indexes for better performance
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_sessions_status ON Sessions(status);
CREATE INDEX idx_reviews_rating ON Reviews(rating);
CREATE INDEX idx_coach_skills ON Coach_Skills(coach_id, skill_id);
CREATE INDEX idx_coach_availability ON Coach_Availability(coach_id, day_of_week);
CREATE INDEX idx_customer_insight_messages_request_id ON CustomerInsightMessages(request_id);
CREATE INDEX idx_customer_insight_messages_sender_receiver ON CustomerInsightMessages(sender_id, receiver_id);
CREATE INDEX idx_customer_insight_messages_is_read ON CustomerInsightMessages(is_read);
CREATE INDEX idx_coach_time_slots ON CoachTimeSlots(coach_id, status, start_time);