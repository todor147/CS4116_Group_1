﻿# CS4116_Group_1 - EduCoach: Educational Coaching Marketplace

## 1. Introduction
EduCoach is an educational service marketplace where coaches (businesses) can advertise their teaching and tutoring services, and learners (users) can connect with coaches to negotiate and arrange educational sessions. The platform is built using HTML, CSS, Bootstrap, PHP, and MySQL.

## 2. Technical Specification

### 2.1 Technology Stack
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS for interactivity
- **Backend**: PHP with MySQL
- **Search**: SQL-based search with filters for expertise, pricing, and ratings
- **Email**: PHP mail() function for notifications

### 2.2 Database Schema
```sql
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

CREATE TABLE Expertise_Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Skills (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE
);

CREATE TABLE Coach_Skills (
    coach_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    skill_id INT,
    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES Skills(skill_id) ON DELETE CASCADE
);

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

CREATE TABLE ServiceTiers (
    tier_id INT PRIMARY KEY AUTO_INCREMENT,
    coach_id INT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE
);

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

CREATE TABLE ReviewResponses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES Reviews(review_id) ON DELETE CASCADE
);

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

CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

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
CREATE INDEX idx_coach_skills ON Coach_Skills(coach_id, skill_id);
CREATE INDEX idx_coach_availability ON Coach_Availability(coach_id, day_of_week);
```



### 2.3 Core Features Implementation

#### 2.3.1 User and Coach Registration
- User registration with role selection (learner/coach)
- Coach profile creation with expertise, availability, and pricing
- Profile management system
- Profile images and detailed bios

#### 2.3.2 Service Tiers & Pricing
- Multiple service tiers per coach (e.g., Basic, Standard, Premium)
- Detailed descriptions for each tier
- Custom pricing for each service tier

#### 2.3.3 Service Inquiries
- Inquiry submission system for interested learners
- Status tracking (pending, accepted, rejected, completed)
- Conversion of inquiries to scheduled sessions

#### 2.3.4 Search and Filtering
- Advanced search by keywords, expertise, and categories
- Filtering by price range and rating
- Results ranked by relevance and rating

#### 2.3.5 Messaging System
- Internal messaging between learners and coaches
- Notifications for new messages
- Message history tracking

#### 2.3.6 Session Management
- Scheduling system with calendar integration
- Session status tracking
- Post-session verification system

#### 2.3.7 Review and Rating System
- Star ratings (1-5) with detailed comments
- Coach responses to reviews
- Review verification (only from completed sessions)

#### 2.3.8 Peer-to-Peer Communication
- Request system for contacting verified customers
- Privacy-respecting messaging between potential and verified customers
- Insight request management

#### 2.3.9 Administrative Controls
- Admin dashboard for site management
- User and coach banning functionality
- Content moderation tools for reviews and messages

#### 2.3.10 Authentication and Security
- Secure login with email and password
- Session management with PHP sessions
- Password hashing using PHP's password_hash()
- CSRF protection

### 2.4 Security Measures
- Input validation and sanitization
- Prepared statements for database queries
- Secure password storage

### 2.5 Testing Requirements
- Functional testing of all core features
- Cross-browser compatibility testing
- Security testing for common vulnerabilities
- Database integrity and performance testing

## 3. Project Structure
     ```
     /project
     ├── assets/
     │   ├── css/
     │   ├── js/
     │   └── images/
     ├── includes/
│   ├── auth_functions.php
│   ├── db_connection.php
│   ├── header.php
│   ├── footer.php
│   └── validation_functions.php
     ├── pages/
│   ├── home.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── coach-profile.php
│   ├── search.php
│   ├── messages.php
│   ├── inquiries.php
│   ├── sessions.php
│   ├── reviews.php
│   └── admin/
│       ├── dashboard.php
│       ├── users.php
│       ├── reviews.php
│       └── messages.php
     └── index.php
     ```

## 4. Deployment Guidelines
1. Set up PHP environment with MySQL
2. Import database schema
3. Configure connection settings
4. Upload files to web server
5. Set appropriate file permissions
6. Configure error logging

### 5. Commit Guidelines
- Use descriptive commit messages
- Reference issue numbers when applicable
- Keep commits focused on single changes
- Push regularly to remote branches

## 6. Additional Features (If Time Permits)
- Real-time messaging with WebSockets
- Calendar integration for scheduling
- Payment system integration
- Mobile application version
- Analytics dashboard for coaches

## 7. Project Requirements Checklist
- [x] User and Coach Registration
- [x] Service Listings & Pricing Tiers
- [x] Customer-Business Connection (Service Inquiries)
- [x] Customer Verification and Reviews
- [x] Search & Filtering
- [x] Peer-to-Peer Communication
- [x] Administrative Controls
- [x] Responsive Design
- [x] Security Implementation
