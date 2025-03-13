-- Insert test users (learner and coach)
INSERT INTO Users (username, email, password_hash, user_type, created_at)
VALUES 
('test_learner', 'learner@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'regular', NOW()),
('test_coach', 'coach@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business', NOW());

-- Insert coach profile
INSERT INTO Coaches (user_id, expertise, availability, rating)
SELECT user_id, 'Test Expertise', 'Available for testing', 0
FROM Users WHERE email = 'coach@test.com';

-- Insert service tier
INSERT INTO ServiceTiers (coach_id, name, description, price, duration)
SELECT c.coach_id, 'Test Service', 'Test service description', 50.00, 60
FROM Coaches c
JOIN Users u ON c.user_id = u.user_id
WHERE u.email = 'coach@test.com';

-- Insert completed session
INSERT INTO Sessions (learner_id, coach_id, tier_id, scheduled_time, duration, status)
SELECT 
    (SELECT user_id FROM Users WHERE email = 'learner@test.com'),
    c.coach_id,
    st.tier_id,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    60,
    'completed'
FROM Coaches c
JOIN Users u ON c.user_id = u.user_id
JOIN ServiceTiers st ON st.coach_id = c.coach_id
WHERE u.email = 'coach@test.com';

-- Note: The password for both test accounts is 'password' 