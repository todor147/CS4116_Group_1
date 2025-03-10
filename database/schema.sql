-- EduCoach Database Schema
-- Created for CS4116 Group 1 Project

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS CoachCategories;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS CustomerInsightMessages;
DROP TABLE IF EXISTS CustomerInsightRequests;
DROP TABLE IF EXISTS Messages;
DROP TABLE IF EXISTS ReviewResponses;
DROP TABLE IF EXISTS Reviews;
DROP TABLE IF EXISTS Sessions;
DROP TABLE IF EXISTS ServiceInquiries;
DROP TABLE IF EXISTS ServiceTiers;
DROP TABLE IF EXISTS Coaches;
DROP TABLE IF EXISTS Users;

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
    is_banned BOOLEAN DEFAULT FALSE
);

-- Create Coaches table
CREATE TABLE Coaches (
    coach_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    expertise VARCHAR(255),
    availability TEXT,
    rating DECIMAL(3,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
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

-- Create ServiceInquiries table
CREATE TABLE ServiceInquiries (
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

-- Create Sessions table
CREATE TABLE Sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    inquiry_id INT,
    learner_id INT,
    coach_id INT,
    tier_id INT,
    scheduled_time DATETIME,
    duration INT, -- in minutes
    status ENUM('scheduled', 'completed', 'cancelled'),
    FOREIGN KEY (inquiry_id) REFERENCES ServiceInquiries(inquiry_id) ON DELETE SET NULL,
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
    FOREIGN KEY (session_id) REFERENCES Sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
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
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (inquiry_id) REFERENCES ServiceInquiries(inquiry_id) ON DELETE CASCADE
);

-- Create CustomerInsightRequests table
CREATE TABLE CustomerInsightRequests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT,
    verified_customer_id INT,
    coach_id INT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_customer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

-- Create CustomerInsightMessages table
CREATE TABLE CustomerInsightMessages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT,
    sender_id INT,
    receiver_id INT,
    content TEXT,
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
CREATE INDEX idx_sessions_status ON Sessions(status);
CREATE INDEX idx_reviews_rating ON Reviews(rating);

ALTER TABLE Users
DROP COLUMN IF EXISTS google_id,
DROP COLUMN IF EXISTS facebook_id,
DROP COLUMN IF EXISTS github_id; 