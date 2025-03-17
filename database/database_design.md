# EduCoach Database Design Documentation

## Overview
The EduCoach platform uses a relational database design to support a coaching/tutoring platform where coaches can offer their services to learners. The database is designed to handle user management, coach profiles, service offerings, session bookings, reviews, and messaging.

## Access Information

### Remote Database (Production)
- Host: sql8.freesqldatabase.com
- Port: 3306
- Database: sql8768039
- Username: sql8768039
- Password: 59k7waVH8w

### phpMyAdmin Access
- URL: http://www.phpmyadmin.co
- Use the remote database credentials above to login

### Local Development (XAMPP)
- Host: localhost
- Port: 3306
- Database: educoach
- Username: root
- Password: [empty]

### Connection String Examples

#### Production Environment
```php
$host = 'sql8.freesqldatabase.com';
$dbname = 'sql8768039';
$username = 'sql8768039';
$password = '59k7waVH8w';
```

#### Development Environment
```php
$host = 'localhost';
$dbname = 'educoach';
$username = 'root';
$password = '';
```

## Database Schema

### Core Tables
1. **Users**
   - Primary user information storage
   - Supports multiple user types (regular, business, admin)
   - Includes authentication and profile data

2. **Coaches**
   - Extended profile for business users who are coaches
   - Stores professional information, rates, and ratings
   - Links to Users table via user_id

3. **ServiceTiers**
   - Different service packages offered by coaches
   - Flexible pricing and description for each tier
   - Enables coaches to structure their offerings

### Skills and Expertise
4. **Expertise_Categories**
   - Main categories of expertise
   - Hierarchical organization of skills

5. **Skills**
   - Specific skills within categories
   - Detailed descriptions of each skill

6. **Coach_Skills**
   - Maps coaches to their skills
   - Includes proficiency levels

### Scheduling and Sessions
7. **Coach_Availability**
   - Weekly availability schedule
   - Time slots for each day

8. **ServiceInquiries**
   - Initial requests for coaching services
   - Status tracking of inquiries

9. **Sessions**
   - Scheduled coaching sessions
   - Links inquiries to actual meetings

### Feedback and Communication
10. **Reviews**
    - Session feedback and ratings
    - Maintains coach rating system

11. **ReviewResponses**
    - Coach responses to reviews
    - Enables professional interaction

12. **Messages**
    - Internal messaging system
    - Supports coach-learner communication

### Additional Features
13. **CustomerInsightRequests**
    - Peer review system
    - Verified customer feedback

14. **Categories & CoachCategories**
    - Additional categorization
    - Flexible coach categorization

## Key Features

1. **User Management**
   - Secure password hashing
   - Role-based access control
   - Profile management

2. **Coach Profiles**
   - Comprehensive profile system
   - Skill and expertise tracking
   - Availability management

3. **Service Management**
   - Flexible service tier system
   - Custom pricing and descriptions
   - Booking and inquiry handling

4. **Review System**
   - Rating and feedback mechanism
   - Response capability for coaches
   - Verified customer insights

5. **Communication**
   - Internal messaging system
   - Service inquiries
   - Customer insight requests

## Performance Optimizations

1. **Indexes**
   - Optimized queries for:
     - User email lookups
     - Session status checks
     - Review ratings
     - Coach skills and availability

2. **Relationships**
   - Proper foreign key constraints
   - Cascading deletes where appropriate
   - Null handling for optional relationships

## Security Features

1. **Authentication**
   - Secure password storage
   - Reset token functionality
   - Session management

2. **Authorization**
   - User type restrictions
   - Resource access control
   - Data isolation between users

## Recent Changes

1. **Service Analytics**
   - Added support for tracking service performance
   - Enhanced metrics for coach insights

2. **Review System**
   - Improved feedback mechanism
   - Added response capability

3. **Availability Management**
   - Enhanced scheduling system
   - Better time slot handling

## Setup Instructions

1. Install XAMPP
2. Start Apache and MySQL services
3. Import schema.sql to create tables
4. Import initial_data.sql for sample data
5. Configure connection in includes/db_connection.php

## Maintenance

Regular maintenance tasks:
1. Backup database daily
2. Monitor performance metrics
3. Update indexes as needed
4. Clean up expired sessions 