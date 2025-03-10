USE cs4116_marketplace;

-- Sample data for EduCoach platform
-- Insert sample users (password is 'Password123!')
INSERT INTO Users (username, email, password_hash, user_type, bio) VALUES
('admin', 'admin@educoach.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System administrator'),
('john_teacher', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Experienced math tutor with 10 years of teaching experience.'),
('sara_coach', 'sara@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Certified language instructor specializing in Spanish and French.'),
('mike_science', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Physics and Chemistry expert with PhD in Physical Sciences.'),
('emily_art', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Professional artist offering painting and drawing lessons.'),
('david_code', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Software engineer teaching programming and web development.'),
('student1', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'University student looking for math help.'),
('student2', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'High school student interested in science and arts.'),
('learner3', 'learner3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Professional looking to improve language skills.'),
('student4', 'student4@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Beginner programmer seeking coding tutorials.');

-- Insert coaches
INSERT INTO Coaches (user_id, expertise, availability, rating) VALUES
(2, 'Mathematics, Algebra, Calculus', 'Weekdays 4pm-8pm, Weekends 10am-4pm', 4.8),
(3, 'Spanish, French, ESL', 'Monday-Friday 9am-5pm', 4.5),
(4, 'Physics, Chemistry, Biology', 'Tuesdays and Thursdays 6pm-9pm, Saturdays 1pm-6pm', 4.9),
(5, 'Painting, Drawing, Art History', 'Weekends only, 11am-7pm', 4.2),
(6, 'JavaScript, Python, Web Development', 'Flexible hours, please inquire', 4.7);

-- Insert categories
INSERT INTO Categories (name, description) VALUES
('Mathematics', 'All math-related subjects including algebra, calculus, and statistics'),
('Languages', 'Language learning and linguistic studies'),
('Sciences', 'Physical and natural sciences'),
('Arts', 'Visual arts, music, and creative subjects'),
('Computer Science', 'Programming, web development, and computer science theory'),
('Test Preparation', 'Preparation for standardized tests like SAT, GRE, TOEFL'),
('Business', 'Business studies, economics, and finance'),
('Humanities', 'History, philosophy, literature and other humanities');

-- Associate coaches with categories
INSERT INTO CoachCategories (coach_id, category_id) VALUES
(1, 1), -- John with Mathematics
(2, 2), -- Sara with Languages
(3, 3), -- Mike with Sciences
(4, 4), -- Emily with Arts
(5, 5); -- David with Computer Science

-- Create service tiers for coaches
INSERT INTO ServiceTiers (coach_id, name, description, price) VALUES
(1, 'Basic Math Tutoring', '60-minute one-on-one session covering basic concepts', 30.00),
(1, 'Advanced Math Package', '90-minute session for advanced topics with practice problems', 45.00),
(1, 'Exam Preparation', 'Intensive test preparation with mock exams and personalized feedback', 60.00),
(2, 'Language Basics', 'Introduction to language fundamentals and basic conversation', 25.00),
(2, 'Conversation Practice', 'Focused on improving speaking and listening skills', 35.00),
(2, 'Fluency Program', 'Comprehensive language mastery program with cultural insights', 50.00),
(3, 'Science Fundamentals', 'Covering basic scientific concepts and principles', 35.00),
(3, 'Lab Preparation', 'Preparation for laboratory sessions and experiments', 45.00),
(3, 'Advanced Theory', 'Deep dive into advanced scientific theories and research', 60.00),
(4, 'Art Fundamentals', 'Basic techniques in drawing and color theory', 30.00),
(4, 'Portfolio Development', 'Guidance in creating a professional art portfolio', 50.00),
(5, 'Coding Basics', 'Introduction to programming concepts and basic syntax', 40.00),
(5, 'Web Development', 'Building responsive websites with HTML, CSS, and JavaScript', 55.00),
(5, 'Full-Stack Project', 'Comprehensive project-based learning for full-stack development', 75.00);

-- Create sample service inquiries
INSERT INTO ServiceInquiries (user_id, coach_id, tier_id, status, message, created_at) VALUES
(7, 1, 1, 'accepted', 'I need help with calculus for my upcoming exam.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(8, 3, 7, 'completed', 'Looking for help with my physics homework.', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(9, 2, 4, 'pending', 'I want to improve my Spanish conversation skills.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 5, 12, 'accepted', 'Need to learn JavaScript basics for a project.', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Create sample sessions
INSERT INTO Sessions (inquiry_id, learner_id, coach_id, tier_id, scheduled_time, duration, status) VALUES
(1, 7, 1, 1, DATE_ADD(NOW(), INTERVAL 2 DAY), 60, 'scheduled'),
(2, 8, 3, 7, DATE_SUB(NOW(), INTERVAL 10 DAY), 60, 'completed'),
(4, 10, 5, 12, DATE_ADD(NOW(), INTERVAL 1 DAY), 90, 'scheduled');

-- Create sample reviews
INSERT INTO Reviews (session_id, user_id, coach_id, rating, comment, created_at) VALUES
(2, 8, 3, 5, 'Mike was extremely helpful and made physics concepts easy to understand. Highly recommend!', DATE_SUB(NOW(), INTERVAL 9 DAY));

-- Create sample review responses
INSERT INTO ReviewResponses (review_id, response, created_at) VALUES
(1, 'Thank you for your kind words! It was a pleasure helping you understand physics concepts. Looking forward to our next session!', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Create sample messages
INSERT INTO Messages (sender_id, receiver_id, inquiry_id, content, created_at, is_read) VALUES
(7, 1, 1, 'Hello, I''m looking forward to our session. Could we focus on integration techniques?', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE),
(1, 7, 1, 'Hi there! Absolutely, we can focus on integration techniques. Please prepare your specific questions beforehand.', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE),
(10, 5, 4, 'Hi David, I''m hoping to learn JavaScript from scratch. Is that possible?', DATE_SUB(NOW(), INTERVAL 4 DAY), TRUE),
(5, 10, 4, 'Hello! Yes, we can definitely start from the basics. I have a structured plan for beginners.', DATE_SUB(NOW(), INTERVAL 4 DAY), FALSE);

-- Create sample customer insight request
INSERT INTO CustomerInsightRequests (requester_id, verified_customer_id, coach_id, status, message, created_at) VALUES
(9, 8, 3, 'pending', 'Hi, I''m considering working with Mike for physics tutoring. Could you share your experience?', DATE_SUB(NOW(), INTERVAL 3 DAY)); 