-- Sample data for EduCoach platform
-- Insert sample users (password is 'Password123!')
INSERT INTO Users (username, email, password_hash, user_type, bio) VALUES
('admin', 'admin@educoach.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System administrator'),
('john_teacher', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Experienced math tutor with 10 years of teaching experience.'),
('sara_coach', 'sara@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Certified language instructor specializing in Spanish and French.'),
('mike_science', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Physics and Chemistry expert with PhD in Physical Sciences.'),
('emily_art', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Professional artist offering painting and drawing lessons.'),
('david_code', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Software engineer teaching programming and web development.'),
('james_business', 'james@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Business leadership coach with 15 years executive experience.'),
('maria_lang', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Spanish language professor with cultural immersion methods.'),
('robert_music', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Professional musician offering guitar, piano, and music theory.'),
('lisa_fitness', 'lisa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Certified fitness trainer specializing in yoga and pilates.'),
('natalie_history', 'natalie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'History professor specializing in world history and cultural studies.'),
('kevin_test', 'kevin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', 'Test preparation expert focusing on SAT, GMAT, and standardized exams.'),
('student1', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'University student looking for math help.'),
('student2', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'High school student interested in science and arts.'),
('learner3', 'learner3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Professional looking to improve language skills.'),
('student4', 'student4@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Beginner programmer seeking coding tutorials.'),
('alex_learner', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Business professional learning Spanish for career advancement.'),
('sophia_student', 'sophia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Music enthusiast learning piano and guitar.'),
('tom_fitness', 'tom@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Fitness enthusiast looking for yoga and pilates instruction.'),
('emma_artist', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Aspiring artist seeking to improve painting and drawing skills.'),
('ryan_business', 'ryan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'Entrepreneur looking for business coaching and leadership skills.'),
('jasmine_science', 'jasmine@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', 'PhD student seeking advanced help with physics research.');

-- Insert sample privacy settings for users
INSERT INTO UserPrivacySettings (user_id, allow_insight_requests, share_session_history, share_coach_ratings, public_profile) VALUES
(7, 1, 1, 1, 1), -- student1 allows all sharing
(8, 1, 1, 0, 1), -- student2 allows insights but not ratings
(9, 0, 0, 0, 1), -- learner3 prefers privacy except public profile
(10, 1, 0, 1, 0), -- student4 mixed settings
(15, 1, 1, 1, 1), -- alex_learner allows all sharing
(16, 0, 1, 1, 1), -- sophia_student allows all except insight requests
(17, 1, 1, 0, 1), -- tom_fitness allows insights but not ratings
(18, 0, 0, 1, 1), -- emma_artist allows only ratings and public profile
(19, 1, 1, 1, 0), -- ryan_business allows all except public profile
(20, 0, 1, 0, 1); -- jasmine_science mixed settings

-- Insert coaches
INSERT INTO Coaches (user_id, headline, about_me, experience, hourly_rate, video_url, rating) VALUES
(2, 'Expert Math Tutor & Educator', 'I am a passionate math educator with extensive experience teaching students of all levels. I specialize in making complex concepts easy to understand and building strong mathematical foundations.', '10+ years', 45.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.8),
(3, 'Professional Language Instructor', 'Certified language teacher with expertise in Spanish and French. I focus on conversational fluency, proper grammar, and cultural understanding to help you truly master a new language.', '8 years', 40.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.5),
(4, 'PhD Science Educator', 'Physics and Chemistry expert with a doctorate in Physical Sciences. I break down complex scientific concepts into understandable pieces, focusing on both theory and practical applications.', '12 years', 55.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.9),
(5, 'Professional Artist & Teacher', 'Classically trained artist with experience in various mediums including oil painting, watercolor, and digital art. I help students develop their artistic eye and technical skills.', '7 years', 35.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.2),
(6, 'Software Engineer & Coding Coach', 'Full-stack developer with years of industry experience at top tech companies. I teach practical programming skills that will prepare you for real-world development work.', '9 years', 60.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.7),
(7, 'Executive Leadership Coach', 'Former Fortune 500 executive helping professionals develop leadership skills and advance their careers. I focus on practical strategies that drive real results in the workplace.', '15 years', 75.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.7),
(8, 'Spanish & Cultural Immersion Expert', 'University professor teaching Spanish through cultural immersion. My approach combines language instruction with deep cultural understanding for a comprehensive learning experience.', '14 years', 50.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 5.0),
(9, 'Professional Music Instructor', 'Conservatory-trained musician offering comprehensive music education. I teach guitar, piano, and music theory with emphasis on both technical skill and musical expression.', '11 years', 45.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.6),
(10, 'Certified Fitness & Wellness Coach', 'Holistic fitness trainer specializing in yoga, pilates, and mindfulness practices. I create personalized fitness plans that integrate physical training with mental wellbeing.', '8 years', 40.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.8),
(11, 'History Professor & Cultural Expert', 'Passionate history educator with expertise in world history and cultural studies. I help students understand historical context and make meaningful connections to contemporary issues.', '12 years', 50.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.7),
(12, 'Test Preparation Specialist', 'Dedicated exam preparation coach with a proven track record of helping students excel on standardized tests. I teach both content knowledge and test-taking strategies.', '9 years', 55.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4.9);

-- Insert expertise categories
INSERT INTO Expertise_Categories (category_name, description) VALUES
('Mathematics', 'All branches of mathematics including algebra, calculus, geometry, and statistics'),
('Languages', 'Foreign language instruction and linguistics'),
('Sciences', 'Natural sciences including physics, chemistry, biology, and environmental science'),
('Arts', 'Visual arts, music, performing arts, and art history'),
('Technology', 'Computer science, programming, and technology related skills'),
('Business', 'Business strategy, marketing, finance, and entrepreneurship'),
('Humanities', 'History, philosophy, literature, and other humanities subjects'),
('Test Preparation', 'Preparation for standardized tests and certifications'),
('Music', 'Instrumental instruction, music theory, and composition'),
('Fitness', 'Physical fitness, yoga, pilates, and wellness coaching');

-- Insert skills
INSERT INTO Skills (skill_id, category_id, skill_name, description) VALUES
-- Mathematics Skills
(1, 1, 'Algebra', 'Equations, functions, and algebraic structures'),
(2, 1, 'Calculus', 'Differential and integral calculus'),
(3, 1, 'Geometry', 'Euclidean geometry, trigonometry, and spatial reasoning'),
(4, 1, 'Statistics', 'Data analysis, probability, and statistical methods'),
-- Languages Skills
(5, 2, 'Spanish', 'Spanish language instruction from beginner to advanced'),
(6, 2, 'French', 'French language instruction from beginner to advanced'),
(7, 2, 'English', 'English language instruction for non-native speakers'),
(8, 2, 'Chinese', 'Mandarin and Cantonese instruction'),
-- Sciences Skills
(9, 3, 'Physics', 'Classical mechanics, electromagnetism, quantum physics'),
(10, 3, 'Chemistry', 'Organic and inorganic chemistry, biochemistry'),
(11, 3, 'Biology', 'Molecular biology, genetics, ecology'),
(12, 3, 'Astronomy', 'Stellar astronomy, cosmology, space science'),
-- Arts Skills
(13, 4, 'Painting', 'Oil painting, watercolor, acrylic techniques'),
(14, 4, 'Drawing', 'Sketching, figure drawing, perspective'),
(15, 4, 'Digital Art', 'Digital illustration, graphic design'),
(16, 4, 'Photography', 'Camera techniques, composition, editing'),
-- Technology Skills
(17, 5, 'JavaScript', 'Front-end web development with JavaScript'),
(18, 5, 'Python', 'Python programming for web, data science, and automation'),
(19, 5, 'Java', 'Object-oriented programming with Java'),
(20, 5, 'Web Development', 'Full-stack web development'),
-- Business Skills
(21, 6, 'Marketing', 'Digital marketing, SEO, and social media strategy'),
(22, 6, 'Finance', 'Financial planning, accounting, and investment'),
(23, 6, 'Entrepreneurship', 'Business strategy and startup guidance'),
(24, 6, 'Leadership', 'Executive leadership and team management'),
-- Humanities Skills
(25, 7, 'History', 'World history, historical analysis'),
(26, 7, 'Philosophy', 'Philosophical concepts and critical thinking'),
(27, 7, 'Literature', 'Literary analysis and creative writing'),
-- Test Preparation Skills
(28, 8, 'SAT Prep', 'Preparation for the SAT college entrance exam'),
(29, 8, 'GMAT Prep', 'Preparation for the GMAT business school exam'),
(30, 8, 'TOEFL Prep', 'Preparation for the TOEFL English proficiency test'),
-- Music Skills
(31, 9, 'Guitar', 'Acoustic and electric guitar instruction'),
(32, 9, 'Piano', 'Piano technique and performance'),
(33, 9, 'Music Theory', 'Understanding music structure and composition'),
(34, 9, 'Voice', 'Vocal training and performance techniques'),
-- Fitness Skills
(35, 10, 'Yoga', 'Yoga practice and instruction'),
(36, 10, 'Pilates', 'Pilates method and core strengthening'),
(37, 10, 'Mindfulness', 'Meditation and mindfulness practices'),
(38, 10, 'Nutrition', 'Dietary planning and nutritional coaching');

-- Associate coaches with skills
INSERT INTO Coach_Skills (coach_id, skill_id, proficiency_level) VALUES
-- John (Math)
(1, 1, 5), -- Algebra (Expert)
(1, 2, 5), -- Calculus (Expert)
(1, 3, 4), -- Geometry (Advanced)
(1, 4, 4), -- Statistics (Advanced)
-- Sara (Languages)
(2, 5, 5), -- Spanish (Expert)
(2, 6, 5), -- French (Expert)
(2, 7, 4), -- English (Advanced)
-- Mike (Sciences)
(3, 9, 5), -- Physics (Expert)
(3, 10, 5), -- Chemistry (Expert)
(3, 11, 3), -- Biology (Intermediate)
(3, 12, 4), -- Astronomy (Advanced)
-- Emily (Arts)
(4, 13, 5), -- Painting (Expert)
(4, 14, 5), -- Drawing (Expert)
(4, 15, 3), -- Digital Art (Intermediate)
-- David (Technology)
(5, 17, 5), -- JavaScript (Expert)
(5, 18, 4), -- Python (Advanced)
(5, 19, 3), -- Java (Intermediate)
(5, 20, 5), -- Web Development (Expert)
-- James (Business)
(6, 21, 4), -- Marketing (Advanced)
(6, 22, 5), -- Finance (Expert)
(6, 23, 5), -- Entrepreneurship (Expert)
(6, 24, 5), -- Leadership (Expert)
-- Maria (Languages)
(7, 5, 5), -- Spanish (Expert)
(7, 7, 4), -- English (Advanced)
(7, 27, 4), -- Literature (Advanced)
-- Robert (Music)
(8, 31, 5), -- Guitar (Expert)
(8, 32, 5), -- Piano (Expert)
(8, 33, 5), -- Music Theory (Expert)
-- Lisa (Fitness)
(9, 35, 5), -- Yoga (Expert)
(9, 36, 5), -- Pilates (Expert)
(9, 37, 4), -- Mindfulness (Advanced)
-- Natalie (History)
(10, 25, 5), -- History (Expert)
(10, 26, 4), -- Philosophy (Advanced)
(10, 27, 5), -- Literature (Expert)
-- Kevin (Test Prep)
(11, 28, 5), -- SAT Prep (Expert)
(11, 29, 5), -- GMAT Prep (Expert)
(11, 30, 4); -- TOEFL Prep (Advanced)

-- Add coach availability
INSERT INTO Coach_Availability (coach_id, day_of_week, start_time, end_time) VALUES
-- John's availability
(1, 'Monday', '16:00:00', '20:00:00'),
(1, 'Tuesday', '16:00:00', '20:00:00'),
(1, 'Wednesday', '16:00:00', '20:00:00'),
(1, 'Thursday', '16:00:00', '20:00:00'),
(1, 'Friday', '16:00:00', '20:00:00'),
(1, 'Saturday', '10:00:00', '16:00:00'),
(1, 'Sunday', '10:00:00', '16:00:00'),
-- Sara's availability
(2, 'Monday', '09:00:00', '17:00:00'),
(2, 'Tuesday', '09:00:00', '17:00:00'),
(2, 'Wednesday', '09:00:00', '17:00:00'),
(2, 'Thursday', '09:00:00', '17:00:00'),
(2, 'Friday', '09:00:00', '17:00:00'),
-- Mike's availability
(3, 'Tuesday', '18:00:00', '21:00:00'),
(3, 'Thursday', '18:00:00', '21:00:00'),
(3, 'Saturday', '13:00:00', '18:00:00'),
-- Emily's availability
(4, 'Saturday', '11:00:00', '19:00:00'),
(4, 'Sunday', '11:00:00', '19:00:00'),
-- David's availability
(5, 'Monday', '18:00:00', '22:00:00'),
(5, 'Wednesday', '18:00:00', '22:00:00'),
(5, 'Friday', '18:00:00', '22:00:00'),
(5, 'Saturday', '14:00:00', '20:00:00'),
-- James's availability
(6, 'Monday', '08:00:00', '12:00:00'),
(6, 'Tuesday', '08:00:00', '12:00:00'),
(6, 'Wednesday', '17:00:00', '21:00:00'),
(6, 'Friday', '17:00:00', '21:00:00'),
-- Maria's availability
(7, 'Monday', '10:00:00', '18:00:00'),
(7, 'Wednesday', '10:00:00', '18:00:00'),
(7, 'Friday', '10:00:00', '18:00:00'),
-- Robert's availability
(8, 'Tuesday', '14:00:00', '20:00:00'),
(8, 'Thursday', '14:00:00', '20:00:00'),
(8, 'Saturday', '09:00:00', '15:00:00'),
-- Lisa's availability
(9, 'Monday', '07:00:00', '11:00:00'),
(9, 'Wednesday', '07:00:00', '11:00:00'),
(9, 'Friday', '07:00:00', '11:00:00'),
(9, 'Saturday', '08:00:00', '12:00:00'),
-- Natalie's availability
(10, 'Monday', '13:00:00', '18:00:00'),
(10, 'Wednesday', '13:00:00', '18:00:00'),
(10, 'Friday', '13:00:00', '18:00:00'),
(10, 'Sunday', '14:00:00', '18:00:00'),
-- Kevin's availability
(11, 'Tuesday', '16:00:00', '21:00:00'),
(11, 'Thursday', '16:00:00', '21:00:00'),
(11, 'Saturday', '10:00:00', '16:00:00'),
(11, 'Sunday', '12:00:00', '18:00:00');

-- Insert categories
INSERT INTO Categories (name, description) VALUES
('Mathematics', 'All math-related subjects including algebra, calculus, and statistics'),
('Languages', 'Language learning and linguistic studies'),
('Sciences', 'Physical and natural sciences'),
('Arts', 'Visual arts, music, and creative subjects'),
('Computer Science', 'Programming, web development, and computer science theory'),
('Test Preparation', 'Preparation for standardized tests like SAT, GRE, TOEFL'),
('Business', 'Business studies, economics, and finance'),
('Humanities', 'History, philosophy, literature and other humanities'),
('Music Education', 'Instrumental instruction, music theory, and performance'),
('Health & Fitness', 'Physical fitness, wellness, and mindfulness practices');

-- Associate coaches with categories
INSERT INTO CoachCategories (coach_id, category_id) VALUES
(1, 1), -- John with Mathematics
(2, 2), -- Sara with Languages
(3, 3), -- Mike with Sciences
(4, 4), -- Emily with Arts
(5, 5), -- David with Computer Science
(6, 7), -- James with Business
(7, 2), -- Maria with Languages
(8, 9), -- Robert with Music Education
(9, 10), -- Lisa with Health & Fitness
(10, 8), -- Natalie with Humanities
(11, 6); -- Kevin with Test Preparation

-- Add sample time slots for coaches (using relative dates)
INSERT INTO CoachTimeSlots (coach_id, start_time, end_time, status) VALUES
-- John's time slots (Math tutor) - Today and next 7 days
(1, CONCAT(CURDATE(), ' 09:00:00'), CONCAT(CURDATE(), ' 10:00:00'), 'available'),
(1, CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 11:00:00'), 'available'),
(1, CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 15:00:00'), 'available'),
(1, CONCAT(CURDATE(), ' 15:00:00'), CONCAT(CURDATE(), ' 16:00:00'), 'available'),

(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 10:00:00'), 'available'),
(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), 'available'),
(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00'), 'available'),

(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), 'available'),
(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 15:00:00'), 'available'),
(1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 17:00:00'), 'booked'),

-- Sara's time slots (Language instructor)
(2, CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 11:00:00'), 'available'),
(2, CONCAT(CURDATE(), ' 11:00:00'), CONCAT(CURDATE(), ' 12:00:00'), 'available'),
(2, CONCAT(CURDATE(), ' 13:00:00'), CONCAT(CURDATE(), ' 14:00:00'), 'available'),

(2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), 'available'),
(2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00'), 'booked'),
(2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 11:00:00'), 'available'),

-- Mike's time slots (Science educator)
(3, CONCAT(CURDATE(), ' 16:00:00'), CONCAT(CURDATE(), ' 17:00:00'), 'available'),
(3, CONCAT(CURDATE(), ' 17:00:00'), CONCAT(CURDATE(), ' 18:00:00'), 'available'),
(3, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), 'available'),
(3, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:00:00'), 'available'),
(3, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), 'available'),

-- Emily's time slots (Artist)
(4, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 12:00:00'), 'available'),
(4, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 12:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 13:00:00'), 'available'),
(4, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 15:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 16:00:00'), 'available'),
(4, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 11:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 12:00:00'), 'available'),
(4, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 17:00:00'), 'booked'),

-- David's time slots (Coding coach)
(5, CONCAT(CURDATE(), ' 18:00:00'), CONCAT(CURDATE(), ' 19:00:00'), 'available'),
(5, CONCAT(CURDATE(), ' 19:00:00'), CONCAT(CURDATE(), ' 20:00:00'), 'available'),
(5, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 19:00:00'), 'available'),
(5, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 18:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 19:00:00'), 'available'),
(5, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), 'available'),
(5, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 16:00:00'), 'available'),

-- James's time slots (Business coach)
(6, CONCAT(CURDATE(), ' 08:00:00'), CONCAT(CURDATE(), ' 09:00:00'), 'available'),
(6, CONCAT(CURDATE(), ' 09:00:00'), CONCAT(CURDATE(), ' 10:00:00'), 'available'),
(6, CONCAT(CURDATE(), ' 17:00:00'), CONCAT(CURDATE(), ' 18:00:00'), 'available'),
(6, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 08:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), 'available'),
(6, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:00:00'), 'available'),
(6, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 08:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 09:00:00'), 'available'),
(6, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 17:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 18:00:00'), 'available'),

-- Maria's time slots (Spanish teacher)
(7, CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 11:00:00'), 'available'),
(7, CONCAT(CURDATE(), ' 11:00:00'), CONCAT(CURDATE(), ' 12:00:00'), 'available'),
(7, CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 15:00:00'), 'available'),
(7, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), 'available'),
(7, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 16:00:00'), 'available'),
(7, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 11:00:00'), 'available'),
(7, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), 'available'),

-- Robert's time slots (Music instructor)
(8, CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 15:00:00'), 'available'),
(8, CONCAT(CURDATE(), ' 15:00:00'), CONCAT(CURDATE(), ' 16:00:00'), 'available'),
(8, CONCAT(CURDATE(), ' 16:00:00'), CONCAT(CURDATE(), ' 17:00:00'), 'available'),
(8, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00'), 'available'),
(8, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), 'available'),
(8, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 11:00:00'), 'available'),
(8, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), 'available'),

-- Lisa's time slots (Fitness coach)
(9, CONCAT(CURDATE(), ' 07:00:00'), CONCAT(CURDATE(), ' 08:00:00'), 'available'),
(9, CONCAT(CURDATE(), ' 08:00:00'), CONCAT(CURDATE(), ' 09:00:00'), 'available'),
(9, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 07:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 08:00:00'), 'available'),
(9, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 08:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), 'available'),
(9, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 07:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 08:00:00'), 'available'),
(9, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 08:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 09:00:00'), 'available'),

-- Natalie's time slots (History professor) 
(10, CONCAT(CURDATE(), ' 13:00:00'), CONCAT(CURDATE(), ' 14:00:00'), 'available'),
(10, CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 15:00:00'), 'available'),
(10, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 13:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 14:00:00'), 'available'),
(10, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), 'available'),
(10, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 13:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 14:00:00'), 'available'),
(10, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 14:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00'), 'available'),

-- Kevin's time slots (Test preparation)
(11, CONCAT(CURDATE(), ' 16:00:00'), CONCAT(CURDATE(), ' 17:00:00'), 'available'),
(11, CONCAT(CURDATE(), ' 17:00:00'), CONCAT(CURDATE(), ' 18:00:00'), 'available'),
(11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), 'available'),
(11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:00:00'), 'available'),
(11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 11:00:00'), 'available'),
(11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 11:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 12:00:00'), 'available'),
(11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 17:00:00'), 'available');

-- Create service tiers for coaches
INSERT INTO ServiceTiers (coach_id, name, description, price, is_popular) VALUES
(1, 'Basic Math Tutoring', '60-minute one-on-one session covering basic concepts', 30.00, 0),
(1, 'Advanced Math Package', '90-minute session for advanced topics with practice problems', 45.00, 1),
(1, 'Exam Preparation', 'Intensive test preparation with mock exams and personalized feedback', 60.00, 0),
(2, 'Language Basics', 'Introduction to language fundamentals and basic conversation', 25.00, 0),
(2, 'Conversation Practice', 'Focused on improving speaking and listening skills', 35.00, 0),
(2, 'Fluency Program', 'Comprehensive language mastery program with cultural insights', 50.00, 1),
(3, 'Science Fundamentals', 'Covering basic scientific concepts and principles', 35.00, 0),
(3, 'Lab Preparation', 'Preparation for laboratory sessions and experiments', 45.00, 0),
(3, 'Advanced Theory', 'Deep dive into advanced scientific theories and research', 60.00, 1),
(4, 'Art Fundamentals', 'Basic techniques in drawing and color theory', 30.00, 0),
(4, 'Portfolio Development', 'Guidance in creating a professional art portfolio', 50.00, 0),
(5, 'Coding Basics', 'Introduction to programming concepts and basic syntax', 40.00, 0),
(5, 'Web Development', 'Building responsive websites with HTML, CSS, and JavaScript', 55.00, 1),
(5, 'Full-Stack Project', 'Comprehensive project-based learning for full-stack development', 75.00, 0),
(6, 'Leadership Fundamentals', 'Core leadership principles and management basics', 50.00, 0),
(6, 'Executive Coaching', 'Personalized coaching for executives and senior managers', 85.00, 1),
(6, 'Career Advancement', 'Strategic career planning and professional development', 65.00, 0),
(7, 'Spanish Basics', 'Fundamental Spanish language instruction for beginners', 35.00, 0),
(7, 'Cultural Immersion', 'Language learning through cultural context and real-world scenarios', 55.00, 1),
(7, 'Business Spanish', 'Spanish language skills for professional and business contexts', 60.00, 0),
(8, 'Music Fundamentals', 'Introduction to music theory and basic instrument skills', 30.00, 0),
(8, 'Instrument Mastery', 'Advanced instruction for guitar or piano', 50.00, 1),
(8, 'Performance Preparation', 'Coaching for recitals, auditions, and public performances', 60.00, 0),
(9, 'Fitness Foundations', 'Introduction to yoga, pilates, and core fitness principles', 30.00, 0),
(9, 'Wellness Program', 'Comprehensive fitness and wellbeing coaching with personalized routines', 45.00, 1),
(9, 'Mind-Body Balance', 'Integrating physical fitness with mindfulness and stress reduction', 50.00, 0),
(10, 'History Fundamentals', 'Overview of key historical periods and events', 40.00, 0),
(10, 'Cultural Analysis', 'Deep exploration of cultural contexts and historical significance', 55.00, 1),
(10, 'Research Methods', 'Historical research methodologies and academic writing', 60.00, 0),
(11, 'Test Basics', 'Introduction to standardized test formats and general strategies', 45.00, 0),
(11, 'Comprehensive Prep', 'Complete preparation for specific standardized tests', 65.00, 1),
(11, 'Intensive Review', 'Last-minute intensive review and strategy sessions', 75.00, 0);

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
INSERT INTO Messages (sender_id, receiver_id, inquiry_id, content, created_at, is_read, status) VALUES
-- Existing messages
(7, 1, 1, 'Hello, I''m looking forward to our session. Could we focus on integration techniques?', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE, 'approved'),
(1, 7, 1, 'Hi there! Absolutely, we can focus on integration techniques. Please prepare your specific questions beforehand.', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE, 'approved'),
(10, 5, 4, 'Hi David, I''m hoping to learn JavaScript from scratch. Is that possible?', DATE_SUB(NOW(), INTERVAL 4 DAY), TRUE, 'approved'),
(5, 10, 4, 'Hello! Yes, we can definitely start from the basics. I have a structured plan for beginners.', DATE_SUB(NOW(), INTERVAL 4 DAY), FALSE, 'approved'),

-- New test messages - Student 7 (student1) and Coach 3 (Mike - Physics)
(7, 4, NULL, 'Hello Mike, I''m interested in physics tutoring. What topics do you cover?', DATE_SUB(NOW(), INTERVAL 8 DAY), TRUE, 'approved'),
(4, 7, NULL, 'Hi! I cover mechanics, electromagnetism, thermodynamics, and quantum physics. What specific area are you struggling with?', DATE_SUB(NOW(), INTERVAL 8 DAY), TRUE, 'approved'),
(7, 4, NULL, 'I''m having trouble with electromagnetism, especially understanding Maxwell''s equations.', DATE_SUB(NOW(), INTERVAL 7 DAY), TRUE, 'approved'),
(4, 7, NULL, 'Maxwell''s equations can be challenging! I can definitely help you understand both the conceptual meaning and mathematical formulations.', DATE_SUB(NOW(), INTERVAL 7 DAY), TRUE, 'approved'),
(7, 4, NULL, 'Great! When are you available for tutoring sessions?', DATE_SUB(NOW(), INTERVAL 7 DAY), TRUE, 'approved'),
(4, 7, NULL, 'I have openings on Tuesday and Thursday evenings, and Saturday afternoons. Which works best for you?', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE, 'approved'),
(7, 4, NULL, 'Thursday evening would be perfect. What are your rates?', DATE_SUB(NOW(), INTERVAL 6 DAY), TRUE, 'approved'),
(4, 7, NULL, 'For electromagnetism tutoring, I recommend my "Science Fundamentals" tier at $35/hour. We can cover a lot in a 1-hour session.', DATE_SUB(NOW(), INTERVAL 5 DAY), FALSE, 'approved'),

-- Student 8 (student2) and Coach 2 (Sara - Languages)
(8, 3, NULL, 'Hi Sara, do you offer Spanish lessons for beginners?', DATE_SUB(NOW(), INTERVAL 10 DAY), TRUE, 'approved'),
(3, 8, NULL, 'Hello! Yes, I specialize in teaching beginners. We can start with basic conversation and build from there.', DATE_SUB(NOW(), INTERVAL 10 DAY), TRUE, 'approved'),
(8, 3, NULL, 'That sounds perfect. I''m planning a trip to Spain next summer and want to learn enough to get by.', DATE_SUB(NOW(), INTERVAL 9 DAY), TRUE, 'approved'),
(3, 8, NULL, 'That''s an excellent motivation! I can tailor our lessons to focus on travel-related vocabulary and common phrases you''ll need.', DATE_SUB(NOW(), INTERVAL 9 DAY), TRUE, 'approved'),
(8, 3, NULL, 'Great! How many sessions do you think I''ll need to become conversational?', DATE_SUB(NOW(), INTERVAL 9 DAY), TRUE, 'approved'),
(3, 8, 3, 'For basic travel conversation, I recommend at least 10-12 weekly sessions. We can reassess as we go.', DATE_SUB(NOW(), INTERVAL 8 DAY), FALSE, 'approved'),

-- Student 9 (learner3) and Coach 5 (David - Programming)
(9, 6, NULL, 'Hello David, I''ve heard great things about your JavaScript courses. I''m a complete beginner - is that ok?', DATE_SUB(NOW(), INTERVAL 5 DAY), TRUE, 'approved'),
(6, 9, NULL, 'Hi there! Absolutely, I love teaching beginners. Everyone has to start somewhere, and JavaScript is a great first programming language.', DATE_SUB(NOW(), INTERVAL 5 DAY), TRUE, 'approved'),
(9, 6, NULL, 'That''s reassuring! I''m interested in web development specifically. Is that something we would cover?', DATE_SUB(NOW(), INTERVAL 4 DAY), TRUE, 'approved'),
(6, 9, NULL, 'Yes, we''ll start with JavaScript fundamentals and then move into how it applies to web development. By the end, you''ll be building interactive websites!', DATE_SUB(NOW(), INTERVAL 4 DAY), TRUE, 'approved'),
(9, 6, NULL, 'Sounds perfect. One last question - do I need to install any software before our first lesson?', DATE_SUB(NOW(), INTERVAL 3 DAY), TRUE, 'approved'),
(6, 9, NULL, 'Just a modern web browser (Chrome or Firefox recommended) and a text editor. I suggest Visual Studio Code - it''s free and excellent for JavaScript.', DATE_SUB(NOW(), INTERVAL 3 DAY), FALSE, 'approved'),

-- Messages with pending moderation status
(10, 2, NULL, 'Hey John, I need help with my math homework. This stuff is so damn frustrating!', DATE_SUB(NOW(), INTERVAL 2 DAY), FALSE, 'pending'),
(8, 4, NULL, 'This physics textbook is complete garbage, could you recommend a better one?', DATE_SUB(NOW(), INTERVAL 1 DAY), FALSE, 'pending'),

-- Recent unread conversation - Student 10 and Coach 1 (John - Math)
(10, 2, NULL, 'Hi John, are you available for tutoring this weekend? I have an exam on Monday.', DATE_SUB(NOW(), INTERVAL 1 DAY), TRUE, 'approved'),
(2, 10, NULL, 'Hi there! Yes, I have availability on Saturday afternoon. What topics do you need help with?', DATE_SUB(NOW(), INTERVAL 23 HOUR), FALSE, 'approved'),
(10, 2, NULL, 'Great! I need help with calculus, specifically integration by parts and trig substitution.', DATE_SUB(NOW(), INTERVAL 22 HOUR), FALSE, 'approved'),
(2, 10, NULL, 'Perfect! Those are topics I cover frequently. Let''s schedule for Saturday at 2pm. I''ll prepare some practice problems.', DATE_SUB(NOW(), INTERVAL 21 HOUR), FALSE, 'approved'),
(10, 2, NULL, 'That works for me. Should I prepare any specific questions or examples from my textbook?', DATE_SUB(NOW(), INTERVAL 12 HOUR), FALSE, 'approved'),
(2, 10, NULL, 'Yes, please bring your textbook and any specific problems you''re struggling with. Also, review the basic integration rules beforehand if possible.', DATE_SUB(NOW(), INTERVAL 10 HOUR), FALSE, 'approved'),

-- Very recent messages for testing real-time updates
(7, 3, NULL, 'Hi Sara, I''m considering taking up French. How different is it from Spanish?', DATE_SUB(NOW(), INTERVAL 30 MINUTE), TRUE, 'approved'),
(3, 7, NULL, 'Hello! There are many similarities since both are Romance languages, but French pronunciation is quite different. Are you starting from zero or do you have some background?', DATE_SUB(NOW(), INTERVAL 25 MINUTE), FALSE, 'approved'),
(7, 3, NULL, 'I studied Spanish in high school, but that was years ago. I''m practically starting fresh.', DATE_SUB(NOW(), INTERVAL 20 MINUTE), FALSE, 'approved'),
(3, 7, NULL, 'That''s perfectly fine! The good news is that some concepts will feel familiar. My beginner course is designed for absolute beginners but moves at a comfortable pace.', DATE_SUB(NOW(), INTERVAL 15 MINUTE), FALSE, 'approved');

-- Create sample customer insight request
INSERT INTO CustomerInsightRequests (requester_id, verified_customer_id, coach_id, status, message, created_at) VALUES
(9, 8, 3, 'pending', 'Hi, I''m considering working with Mike for physics tutoring. Could you share your experience?', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Create sample customer insight messages
INSERT INTO CustomerInsightMessages (request_id, sender_id, receiver_id, content, created_at, is_read) VALUES
(1, 9, 8, 'I specifically want to know if his teaching style is good for visual learners? I struggle with abstract concepts.', DATE_SUB(NOW(), INTERVAL 2 DAY), 1);

-- Mark some existing tiers as popular
UPDATE ServiceTiers SET is_popular = 1 WHERE tier_id = 2; -- Advanced Math Package
UPDATE ServiceTiers SET is_popular = 1 WHERE tier_id = 6; -- Fluency Program
UPDATE ServiceTiers SET is_popular = 1 WHERE tier_id = 9; -- Advanced Theory
UPDATE ServiceTiers SET is_popular = 1 WHERE tier_id = 13; -- Web Development 