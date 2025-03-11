<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$user_type = $_SESSION['user_type'] ?? 'regular';
$is_coach = ($user_type === 'business');

// Initialize variables
$success_message = '';
$error_message = '';
$user = null;
$coach = null;

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user is a coach, get coach details
    if ($is_coach) {
        $stmt = $pdo->prepare("SELECT * FROM Coaches WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Error fetching user data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic profile update
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_bio = trim($_POST['bio']);
        
        try {
            // Update user information
            $stmt = $pdo->prepare("UPDATE Users SET username = ?, bio = ? WHERE user_id = ?");
            $stmt->execute([$new_username, $new_bio, $user_id]);
            
            // Update coach information if applicable
            if ($is_coach && isset($_POST['expertise']) && isset($_POST['availability'])) {
                $expertise = trim($_POST['expertise']);
                $availability = trim($_POST['availability']);
                
                $stmt = $pdo->prepare("UPDATE Coaches SET expertise = ?, availability = ? WHERE user_id = ?");
                $stmt->execute([$expertise, $availability, $user_id]);
            }
            
            // Update session variables
            $_SESSION['username'] = $new_username;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($is_coach) {
                $stmt = $pdo->prepare("SELECT * FROM Coaches WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $coach = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
    
    // Password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            $error_message = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            try {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$password_hash, $user_id]);
                $success_message = "Password changed successfully!";
            } catch (PDOException $e) {
                $error_message = "Error changing password: " . $e->getMessage();
            }
        }
    }
    
    // Profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        // Set the absolute path to the profiles directory
        $uploadDir = realpath(__DIR__ . '/../assets/images/profiles/') . '/';
        
        // Add debugging information
        error_log("Upload directory: " . $uploadDir);
        error_log("File info: " . print_r($_FILES['profile_image'], true));
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            error_log("Created directory: " . $uploadDir);
        }
        
        // Simpler filename to avoid long nested timestamps
        $imageFileType = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $user_id . '_' . time() . '.' . $imageFileType;
        $uploadFile = $uploadDir . $filename;
        
        error_log("Target file: " . $uploadFile);
        
        // Check if it's an image
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (!in_array($imageFileType, $allowed_extensions)) {
            $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            error_log("Invalid file type: " . $imageFileType);
        } elseif ($_FILES['profile_image']['size'] > 5000000) { // Limit to 5MB
            $error_message = "File is too large. Maximum size is 5MB.";
            error_log("File too large: " . $_FILES['profile_image']['size']);
        } else {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                error_log("File uploaded successfully to: " . $uploadFile);
                
                try {
                    // Update database
                    $stmt = $pdo->prepare("UPDATE Users SET profile_image = ? WHERE user_id = ?");
                    $stmt->execute([$filename, $user_id]);
                    
                    // Update session variable with detailed logging
                    error_log("UPLOADING PROFILE IMAGE: {$filename}");
                    error_log("BEFORE UPDATE: profile_image in session: " . (isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'not set'));
                    
                    // Clear the old session value and set the new one
                    unset($_SESSION['profile_image']);
                    $_SESSION['profile_image'] = $filename;
                    
                    // Force session write
                    session_write_close();
                    session_start();
                    
                    error_log("AFTER UPDATE: profile_image in session: " . (isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'not set'));
                    
                    $success_message = "Profile image uploaded successfully!";
                    
                    // Force a browser refresh to reload with the new image
                    echo "
                    <div>
                        <h3>Profile image uploaded successfully!</h3>
                        <p>Your new profile image should appear automatically.</p>
                        <script>
                            // Reload the page to show the new image
                            window.location.href = 'profile.php?update=success&t=" . time() . "';
                        </script>
                    </div>
                    ";
                    
                    exit;
                } catch (PDOException $e) {
                    $error_message = "Error updating profile image: " . $e->getMessage();
                    error_log("Database error: " . $e->getMessage());
                }
            } else {
                $error_message = "Failed to upload image. Please try again.";
                error_log("Failed to move uploaded file from " . $_FILES['profile_image']['tmp_name'] . " to " . $uploadFile);
                error_log("Upload error code: " . $_FILES['profile_image']['error']);
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="mb-4">Profile Management</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Image Section -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Profile Image</h5>
                </div>
                <div class="card-body text-center">
                    <?php 
                    // Improved profile image handling with debug output
                    $profile_image = $user['profile_image'] ?? 'default.jpg';
                    $image_path = "../assets/images/profiles/{$profile_image}";
                    $default_image = "../assets/images/profiles/default.jpg";
                    
                    // Check if file exists and is readable
                    $full_image_path = __DIR__ . "/../assets/images/profiles/{$profile_image}";
                    $full_default_path = __DIR__ . "/../assets/images/profiles/default.jpg";
                    
                    // Add debugging information (only visible in HTML source)
                    echo "<!-- Debug: profile_image='{$profile_image}', full_path='{$full_image_path}', exists=" . (file_exists($full_image_path) ? 'true' : 'false') . " -->";
                    
                    // If user image doesn't exist or fallback doesn't exist, use an external default
                    if (file_exists($full_image_path) && is_readable($full_image_path)) {
                        $display_image = $image_path;
                    } elseif (file_exists($full_default_path) && is_readable($full_default_path)) {
                        $display_image = $default_image;
                    } else {
                        // Fallback to a reliable external default avatar
                        $display_image = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&background=random&size=150";
                    }
                    ?>
                    
                    <img src="<?= $display_image ?>" 
                         alt="Profile" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <form action="profile.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Upload New Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, JPEG, PNG, GIF.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Profile Details Section -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Profile Details</h5>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="post">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <div class="form-text">Tell others about yourself.</div>
                        </div>
                        
                        <?php if ($is_coach): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expertise" class="form-label">Expertise</label>
                                <textarea class="form-control" id="expertise" name="expertise" rows="3"><?= htmlspecialchars($coach['expertise'] ?? '') ?></textarea>
                                <div class="form-text">List your skills and areas of expertise.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="availability" class="form-label">Availability</label>
                                <textarea class="form-control" id="availability" name="availability" rows="3"><?= htmlspecialchars($coach['availability'] ?? '') ?></textarea>
                                <div class="form-text">Specify your availability for coaching sessions.</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Password Change Section -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="post">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Minimum 8 characters.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 