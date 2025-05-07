<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php?redirect=edit-coach-profile.php");
    exit();
}

// Check if user is a coach
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.email, u.profile_image, u.bio FROM Coaches c JOIN Users u ON c.user_id = u.user_id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coach) {
        // User is not a coach, redirect to become-coach page
        $_SESSION['error_message'] = "You must be a coach to access this page";
        header("Location: become-coach.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $headline = trim($_POST['headline'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $video_url = trim($_POST['video_url'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validate inputs
    if (empty($headline)) {
        $errors[] = "Professional headline is required";
    } elseif (strlen($headline) > 255) {
        $errors[] = "Headline must be less than 255 characters";
    }
    
    if (empty($about_me)) {
        $errors[] = "About me section is required";
    }
    
    if (empty($experience)) {
        $errors[] = "Experience is required";
    }
    
    if ($hourly_rate <= 0) {
        $errors[] = "Hourly rate must be greater than zero";
    }
    
    // Process profile image upload if provided
    $profile_image = $coach['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = "File size too large. Maximum size is 5MB.";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $userId . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $new_filename;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Function to convert YouTube URL to embed format if needed
    function convertToEmbedUrl($url) {
        if (empty($url)) {
            return '';
        }
        
        // Regular expressions to match YouTube URLs
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        
        // Extract video ID and convert to embed URL
        if (preg_match($pattern, $url, $matches)) {
            $videoId = $matches[1];
            return "https://www.youtube.com/embed/{$videoId}";
        }
        
        // If not a YouTube URL or already an embed URL, return as is
        return $url;
    }
    
    // Convert YouTube URL to embed format
    $video_url = convertToEmbedUrl($video_url);
    
    // Process if no errors
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Update coach record
            $stmt = $pdo->prepare("
                UPDATE Coaches 
                SET headline = ?, about_me = ?, experience = ?, hourly_rate = ?, video_url = ?
                WHERE coach_id = ?
            ");
            $stmt->execute([$headline, $about_me, $experience, $hourly_rate, $video_url, $coach['coach_id']]);
            
            // Update user record
            $stmt = $pdo->prepare("
                UPDATE Users 
                SET bio = ?, profile_image = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$bio, $profile_image, $userId]);
            
            // Commit transaction
            $pdo->commit();
            
            // Set success flag
            $success = true;
            $_SESSION['success_message'] = "Your profile has been updated successfully.";
            
            // Refresh coach data
            $stmt = $pdo->prepare("SELECT c.*, u.username, u.email, u.profile_image, u.bio FROM Coaches c JOIN Users u ON c.user_id = u.user_id WHERE c.user_id = ?");
            $stmt->execute([$userId]);
            $coach = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Coach Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="edit-coach-profile.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person-badge"></i> Profile
                    </a>
                    <a href="edit-coach-skills.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-stars"></i> Skills & Expertise
                    </a>
                    <a href="edit-coach-availability.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Availability
                    </a>
                    <a href="edit-coach-services.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-check"></i> Service Tiers
                    </a>
                    <a href="service-analytics.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-graph-up"></i> Service Analytics
                    </a>
                    <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-eye"></i> View Public Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Edit Coach Profile</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> Your profile has been updated successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <?php 
                                $profile_image = $coach['profile_image'] ?? 'default.jpg';
                                $image_path = "/assets/images/profiles/{$profile_image}";
                                $default_image = "/assets/images/profiles/default.jpg";
                                
                                // Check if file exists and is readable
                                $full_image_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
                                $full_default_path = $_SERVER['DOCUMENT_ROOT'] . $default_image;
                                
                                // If user image doesn't exist or fallback doesn't exist, use an external default
                                if (file_exists($full_image_path) && is_readable($full_image_path)) {
                                    $display_image = $image_path;
                                } elseif (file_exists($full_default_path) && is_readable($full_default_path)) {
                                    $display_image = $default_image;
                                } else {
                                    // Fallback to a reliable external avatar generator
                                    $display_image = "https://ui-avatars.com/api/?name=" . urlencode($coach['username']) . "&background=random&size=150";
                                }
                                ?>
                                
                                <div class="mb-3">
                                    <img src="<?= $display_image ?>" alt="<?= htmlspecialchars($coach['username']) ?>" 
                                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" id="profile-preview">
                                    
                                    <div class="mt-2">
                                        <label for="profile_image" class="form-label">Profile Image</label>
                                        <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*">
                                        <div class="form-text">Max size: 5MB. Formats: JPG, PNG, GIF</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($coach['username']) ?>" disabled>
                                    <div class="form-text">To change your username, go to your account settings.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="headline" class="form-label">Professional Headline *</label>
                                    <input type="text" class="form-control" id="headline" name="headline" 
                                           value="<?= htmlspecialchars($coach['headline']) ?>" required>
                                    <div class="form-text">A short professional title that describes your coaching expertise</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Short Bio *</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="2" required><?= htmlspecialchars($coach['bio']) ?></textarea>
                                    <div class="form-text">A brief introduction that appears in search results (max 150 words)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="about_me" class="form-label">About Me *</label>
                                    <textarea class="form-control" id="about_me" name="about_me" rows="5" required><?= htmlspecialchars($coach['about_me']) ?></textarea>
                                    <div class="form-text">Detailed description of your background, expertise, and coaching approach</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="experience" class="form-label">Experience *</label>
                                        <input type="text" class="form-control" id="experience" name="experience" 
                                               value="<?= htmlspecialchars($coach['experience']) ?>" required>
                                        <div class="form-text">e.g., "5+ years", "10 years", etc.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="hourly_rate" class="form-label">Base Hourly Rate (USD) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                                                   step="0.01" min="5" value="<?= $coach['hourly_rate'] ?>" required>
                                        </div>
                                        <div class="form-text">Your default hourly rate for coaching services</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="video_url" class="form-label">Introduction Video URL</label>
                                    <input type="url" class="form-control" id="video_url" name="video_url" 
                                           value="<?= htmlspecialchars($coach['video_url']) ?>">
                                    <div class="form-text">Add a YouTube video URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview profile image before upload
    document.getElementById('profile_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('profile-preview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 