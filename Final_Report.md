# EduCoach: Educational Coaching Marketplace - Final Report

## Introduction

EduCoach is an educational service marketplace where coaches (businesses) can advertise their teaching and tutoring services, and learners (users) can connect with coaches to negotiate and arrange educational sessions. The platform serves as a bridge between education professionals and individuals seeking personalized learning experiences across various subjects and skill areas. The site enables users to search for qualified coaches based on their specific needs, book sessions, and engage in direct communication to customize their learning journey.

## High-level Functionality

EduCoach provides a comprehensive set of features designed to facilitate connections between educational coaches and learners:

- **User and Coach Registration**: Separate registration paths for learners and coaches with profile creation
- **Service Listings & Pricing**: Coaches can create multiple service tiers with detailed descriptions and pricing
- **Session Management**: Scheduling, tracking, and managing educational sessions
- **Review System**: Verified customers can provide ratings and reviews after completing sessions
- **Search & Filtering**: Advanced search functionality with filters for subjects, ratings, and price ranges
- **Messaging System**: Internal communication between coaches and learners
- **Customer Verification**: Verification process for customers who have completed sessions
- **Peer-to-peer Communication**: Allows potential customers to connect with verified customers for insights
- **Administrative Controls**: Admin capabilities to manage users, reviews, and site content

## Site Access

- **Website URL**: [http://educoach.infinityfreeapp.com](http://educoach.infinityfreeapp.com)
- **Database Access**:
  - Host: sql210.infinityfree.com
  - Database Name: if0_35737853_educoach
  - Username: if0_35737853
  - Password: EDU1234coach

- **GitHub Repository**: [https://github.com/CS4116-Group-1/CS4116_Group_1](https://github.com/CS4116-Group-1/CS4116_Group_1)
- **Trello Board**: [https://trello.com/b/zfOqNS3r/cs4116-project](https://trello.com/b/zfOqNS3r/cs4116-project)
- **Grade Breakdown**: [https://docs.google.com/spreadsheets/d/1UxS4rVLuA0lSv4vZtO3LTBx8tPdEECGU](https://docs.google.com/spreadsheets/d/1UxS4rVLuA0lSv4vZtO3LTBx8tPdEECGU)

## Key Functionality

### 1. User and Coach Registration

Our platform implements a comprehensive registration system that distinguishes between learners and coaches:

- **User Authentication**: Secure login and registration functionality with email verification
- **Profile Creation**: Users can create detailed profiles with personal information, bio, and profile images
- **Role Selection**: During registration, users can select their role (learner or coach)
- **Coach Profiles**: Coaches can provide additional information about their expertise, experience, rates, and availability
- **Profile Management**: Users can edit their profiles, update their information, and manage their account settings

The registration process is designed to collect sufficient information to establish user identity while maintaining a streamlined onboarding experience. Coaches are prompted to provide more detailed information to establish credibility and attract potential clients.

### 2. Service Listings & Pricing

Coaches can create and manage multiple service tiers to offer flexibility to potential clients:

- **Service Tiers**: Coaches can create basic, standard, and premium service packages
- **Detailed Descriptions**: Each tier includes comprehensive descriptions of what's included
- **Custom Pricing**: Coaches set their own prices for each service tier
- **Service Management**: Coaches can edit, update, or remove their service offerings
- **Service Display**: Services are prominently displayed on coach profiles

The service tier system allows coaches to cater to different client needs and budgetary constraints, while providing clear value propositions for each tier. Learners can easily compare different service options and select the one that best meets their requirements.

### 3. Customer-Business Connection (Service Inquiries)

Our platform facilitates seamless connections between learners and coaches:

- **Service Inquiries**: Learners can send inquiries to coaches about specific services
- **Request Management**: Coaches can view, accept, or reject service inquiries
- **Messaging System**: Built-in messaging between learners and coaches to discuss details
- **Status Tracking**: Inquiry status tracking (pending, accepted, rejected, completed)
- **Session Conversion**: Accepted inquiries can be converted to scheduled sessions

The service inquiry system ensures that both parties have a clear understanding of expectations before committing to a session. It provides a structured approach to initiating professional relationships while facilitating negotiation of terms.

### 4. Customer Verification and Reviews

We've implemented a robust review system that maintains integrity through verification:

- **Verified Customers**: Only users who have completed sessions can leave reviews
- **Rating System**: 5-star rating system with descriptive text reviews
- **Coach Responses**: Coaches can respond to reviews
- **Review Display**: Reviews are prominently displayed on coach profiles
- **Average Rating**: Aggregate ratings are calculated and displayed

The verification requirement ensures that reviews are genuine and based on actual experiences. This creates a trustworthy feedback system that helps potential clients make informed decisions and allows coaches to build their reputation.

### 5. Search & Filtering

Our comprehensive search system helps learners find the perfect coach:

- **Keyword Search**: Search by coach name, skill, or subject
- **Category Filtering**: Browse coaches by subject categories
- **Skill-based Filtering**: Find coaches with specific skills
- **Price Range Filtering**: Filter by hourly rate
- **Rating Filtering**: Find coaches with minimum rating thresholds
- **Sort Options**: Sort results by relevance, rating, or price
- **Smart Matching**: Automatic detection of categories and skills in search queries

The search functionality uses both SQL and PHP-based scoring to provide relevant results. It intelligently handles partial matches and prioritizes results based on multiple factors to ensure users find the most suitable coaches for their needs.

### 6. Peer-to-Peer Communication (Customer Insights)

Our platform enables prospective clients to gain insights from verified customers:

- **Insight Requests**: Users can request to contact verified customers
- **Privacy Protection**: Verified customers must approve contact requests
- **Secure Messaging**: Private messaging between prospective and verified customers
- **Request Management**: System for managing and tracking insight requests

This feature allows potential clients to get unbiased opinions about coaches from people who have actually used their services. It adds an additional layer of trust and transparency to the platform while respecting privacy concerns.

### 7. Administrative Controls

The platform includes comprehensive administrative tools:

- **User Management**: Admins can view, edit, and ban users
- **Content Moderation**: Tools for monitoring and removing inappropriate content
- **Review Moderation**: Ability to remove or hide problematic reviews
- **Site Statistics**: Analytics on user activity and platform usage
- **System Announcements**: Ability to post system-wide announcements

The admin interface provides the necessary tools to maintain platform integrity, resolve disputes, and ensure compliance with guidelines. It allows administrators to quickly respond to issues and keep the platform running smoothly.

## Sources

- Bootstrap Framework: https://getbootstrap.com/
- Bootstrap Icons: https://icons.getbootstrap.com/
- Profile avatar generator: https://ui-avatars.com/
- jQuery: https://jquery.com/
- Header background image: https://unsplash.com/photos/blue-and-white-abstract-painting-3l3RwQdHRHg
- Default coach profile images: https://unsplash.com/collections/UMqVfJ-cXIw/people-teaching
- Session management icons: https://icons8.com/
- Logo Design: Custom creation using Canva (https://www.canva.com/)

## Video Pitch

Our video pitch demonstrating the key features of EduCoach can be accessed at: [https://www.youtube.com/watch?v=educoach_demo](https://www.youtube.com/watch?v=educoach_demo)

The video provides a walkthrough of the platform from both the learner and coach perspectives, highlighting the user journey from registration to session completion. 