<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/validation_functions.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php?error=' . urlencode('You must be logged in to access this page.'));
    exit;
}

$user_id = $_SESSION['user_id'];

// First, verify that the user actually exists in the database
try {
    $userCheckStmt = $pdo->prepare("SELECT user_id, user_type FROM Users WHERE user_id = ?");
    $userCheckStmt->execute([$user_id]);
    $user = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User doesn't exist - invalidate session and redirect
        session_unset();
        session_destroy();
        header('Location: login.php?error=' . urlencode('Invalid session. Please log in again.'));
        exit;
    }
    
    // Check if user is already a coach
    $stmt = $pdo->prepare("SELECT * FROM Coaches WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existingCoach = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCoach) {
        header('Location: edit-coach-profile.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error checking user: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('A database error occurred. Please try again.'));
    exit;
}

// Get expertise categories for dropdown
try {
    $stmt = $pdo->prepare("SELECT category_id as id, category_name as name FROM Expertise_Categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $headline = trim($_POST['headline'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $hourly_rate = trim($_POST['hourly_rate'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    if (empty($headline)) {
        $errors[] = "Professional headline is required";
    } elseif (mb_strlen($headline) > 100) {
        $errors[] = "Headline must be 100 characters or less";
    }
    
    if (empty($about_me)) {
        $errors[] = "About me section is required";
    }
    
    if (empty($experience)) {
        $errors[] = "Experience is required";
    }
    
    if (empty($hourly_rate) || !is_numeric($hourly_rate) || $hourly_rate <= 0) {
        $errors[] = "Valid hourly rate is required";
    }
    
    // Video URL validation (optional field)
    if (!empty($video_url) && !filter_var($video_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL for your video";
    }
    
    if (!$category_id) {
        $errors[] = "Please select an expertise category";
    }
    
    // If no errors, insert coach profile
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Double-check the user exists before creating coach profile
            $userVerifyStmt = $pdo->prepare("SELECT user_id FROM Users WHERE user_id = ?");
            $userVerifyStmt->execute([$user_id]);
            if (!$userVerifyStmt->fetch()) {
                throw new Exception("User does not exist in the database.");
            }
            
            // Insert coach profile
            $stmt = $pdo->prepare("
                INSERT INTO Coaches (user_id, headline, about_me, experience, hourly_rate, video_url) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $headline,
                $about_me,
                $experience,
                $hourly_rate,
                $video_url
            ]);
            
            $coach_id = $pdo->lastInsertId();
            
            // Try to update the category_id (this might fail if the column doesn't exist yet)
            try {
                $stmt = $pdo->prepare("
                    UPDATE Coaches SET category_id = ? WHERE coach_id = ?
                ");
                $stmt->execute([$category_id, $coach_id]);
            } catch (PDOException $categoryErr) {
                // Log the error but continue - this is not critical
                error_log("Notice: Could not set category_id: " . $categoryErr->getMessage());
            }
            
            // Update user type to 'business'
            $stmt = $pdo->prepare("
                UPDATE Users SET user_type = 'business' WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            
            // Create default availability (all days, 9 AM to 5 PM, available)
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day) {
                $stmt = $pdo->prepare("
                    INSERT INTO Coach_Availability (coach_id, day_of_week, start_time, end_time, is_available)
                    VALUES (?, ?, '09:00:00', '17:00:00', 1)
                ");
                $stmt->execute([$coach_id, $day]);
            }
            
            // Create a default service tier
            try {
                // First try with duration_minutes (newer schema)
                $stmt = $pdo->prepare("
                    INSERT INTO ServiceTiers (coach_id, name, description, price, duration_minutes)
                    VALUES (?, 'Basic Consultation', 'One-on-one coaching session', ?, 60)
                ");
                $stmt->execute([$coach_id, $hourly_rate]);
            } catch (PDOException $e) {
                // If that fails, try without duration_minutes (older schema)
                if (strpos($e->getMessage(), 'duration_minutes') !== false) {
                    $stmt = $pdo->prepare("
                        INSERT INTO ServiceTiers (coach_id, name, description, price)
                        VALUES (?, 'Basic Consultation', 'One-on-one coaching session', ?)
                    ");
                    $stmt->execute([$coach_id, $hourly_rate]);
                    
                    // Log that we need to update the schema
                    error_log("Notice: ServiceTiers table is missing duration_minutes column. Please update the database schema.");
                } else {
                    // If it's another error, rethrow it
                    throw $e;
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Set success flag and update session
            $success = true;
            $_SESSION['user_type'] = 'business';
            
            // Redirect to dashboard
            header('Location: dashboard.php?success=' . urlencode('Your coach profile has been created!'));
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction and log error
            $pdo->rollBack();
            
            // Add debugging information
            error_log("Coach profile creation error for user_id: $user_id - " . $e->getMessage());
            error_log("Session data: " . print_r($_SESSION, true));
            
            // Check if user exists after error
            try {
                $debugStmt = $pdo->prepare("SELECT user_id FROM Users WHERE user_id = ?");
                $debugStmt->execute([$user_id]);
                $userExists = $debugStmt->fetch() ? 'YES' : 'NO';
                error_log("User exists check after error: $userExists");
            } catch (Exception $e2) {
                error_log("Error checking if user exists: " . $e2->getMessage());
            }
            
            // Display a more helpful error message if possible
            if (strpos($e->getMessage(), 'category_id') !== false) {
                $errors[] = "The system needs to be updated to support expertise categories. Please contact the administrator.";
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = "You already have a coach profile. Please try editing your existing profile instead.";
            } else {
                $errors[] = "An error occurred while creating your coach profile: " . $e->getMessage();
            }
        }
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Become a Coach</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4>Congratulations!</h4>
                            <p>Your coach profile has been created successfully. You can now set up your skills, availability and service tiers.</p>
                            <div class="d-flex justify-content-between mt-3">
                                <a href="edit-coach-profile.php" class="btn btn-primary">Set Up Your Profile</a>
                                <a href="coach-profile.php?id=<?= $coach_id ?>" class="btn btn-outline-primary">View Your Profile</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="headline" class="form-label">Professional Headline *</label>
                                <input type="text" class="form-control" id="headline" name="headline" 
                                       placeholder="e.g., Expert Math Tutor & Educator" required
                                       value="<?= isset($_POST['headline']) ? htmlspecialchars($_POST['headline']) : '' ?>">
                                <div class="form-text">A short professional title that describes your coaching expertise</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="about_me" class="form-label">About Me *</label>
                                <textarea class="form-control" id="about_me" name="about_me" rows="5" required
                                          placeholder="Describe your background, expertise, and coaching approach..."><?= isset($_POST['about_me']) ? htmlspecialchars($_POST['about_me']) : '' ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="experience" class="form-label">Experience *</label>
                                <input type="text" class="form-control" id="experience" name="experience" 
                                       placeholder="e.g., 5+ years" required
                                       value="<?= isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Hourly Rate (USD) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                                           step="0.01" min="5" required
                                           value="<?= isset($_POST['hourly_rate']) ? htmlspecialchars($_POST['hourly_rate']) : '40.00' ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="video_url" class="form-label">Introduction Video URL</label>
                                <input type="url" class="form-control" id="video_url" name="video_url" 
                                       placeholder="e.g., https://www.youtube.com/embed/xxxx"
                                       value="<?= isset($_POST['video_url']) ? htmlspecialchars($_POST['video_url']) : '' ?>">
                                <div class="form-text">Provide a YouTube/Vimeo embed URL for your introduction video (optional)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Expertise Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="alert alert-info">
                                <h5>What happens next?</h5>
                                <p>After creating your coach profile, you'll be able to:</p>
                                <ul>
                                    <li>Add your specific skills and expertise</li>
                                    <li>Set your weekly availability</li>
                                    <li>Create different service tiers with pricing</li>
                                    <li>Start receiving booking requests from learners</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Create Coach Profile</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 