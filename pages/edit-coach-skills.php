<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php?redirect=edit-coach-skills.php");
    exit();
}

// Check if user is a coach
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM Coaches WHERE user_id = ?");
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

// Get all expertise categories and skills
try {
    $stmt = $pdo->query("SELECT * FROM Expertise_Categories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM Skills ORDER BY skill_name");
    $allSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get coach's current skills
    $stmt = $pdo->prepare("SELECT skill_id, proficiency_level FROM Coach_Skills WHERE coach_id = ?");
    $stmt->execute([$coach['coach_id']]);
    $coachSkills = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear existing skills
    try {
        $stmt = $pdo->prepare("DELETE FROM Coach_Skills WHERE coach_id = ?");
        $stmt->execute([$coach['coach_id']]);
        
        // Add new skills
        if (isset($_POST['skills']) && is_array($_POST['skills'])) {
            $stmt = $pdo->prepare("INSERT INTO Coach_Skills (coach_id, skill_id, proficiency_level) VALUES (?, ?, ?)");
            
            foreach ($_POST['skills'] as $skillId => $proficiency) {
                if ($proficiency >= 1 && $proficiency <= 5) {
                    $stmt->execute([$coach['coach_id'], $skillId, $proficiency]);
                }
            }
            
            $success = true;
            $_SESSION['success_message'] = "Your skills have been updated successfully.";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
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
                    <a href="edit-coach-profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Profile
                    </a>
                    <a href="edit-coach-skills.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-stars"></i> Skills & Expertise
                    </a>
                    <a href="edit-coach-availability.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Availability
                    </a>
                    <a href="edit-coach-services.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-check"></i> Service Tiers
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Skills & Expertise</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> Your skills have been updated successfully.
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
                    
                    <p class="mb-4">Select your skills and set your proficiency level for each one. Your top skills will be highlighted on your profile.</p>
                    
                    <form method="post" action="">
                        <div class="accordion" id="skillsAccordion">
                            <?php foreach ($categories as $index => $category): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $category['category_id'] ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $category['category_id'] ?>" 
                                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" 
                                                aria-controls="collapse<?= $category['category_id'] ?>">
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $category['category_id'] ?>" 
                                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                         aria-labelledby="heading<?= $category['category_id'] ?>" 
                                         data-bs-parent="#skillsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php 
                                                // Filter skills by category
                                                $categorySkills = array_filter($allSkills, function($skill) use ($category) {
                                                    return $skill['category_id'] == $category['category_id'];
                                                });
                                                
                                                if (empty($categorySkills)): 
                                                ?>
                                                    <div class="col-12">
                                                        <p class="text-muted">No skills available in this category.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($categorySkills as $skill): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <h5 class="card-title h6"><?= htmlspecialchars($skill['skill_name']) ?></h5>
                                                                    <p class="card-text small"><?= htmlspecialchars($skill['description']) ?></p>
                                                                    
                                                                    <div class="mb-2">
                                                                        <label class="form-label small">Proficiency (1-5):</label>
                                                                        <div class="rating-input">
                                                                            <?php 
                                                                            $currentValue = $coachSkills[$skill['skill_id']] ?? 0;
                                                                            for ($i = 1; $i <= 5; $i++): 
                                                                            ?>
                                                                                <div class="form-check form-check-inline">
                                                                                    <input class="form-check-input" type="radio" 
                                                                                           name="skills[<?= $skill['skill_id'] ?>]" 
                                                                                           id="skill_<?= $skill['skill_id'] ?>_<?= $i ?>" 
                                                                                           value="<?= $i ?>" 
                                                                                           <?= $currentValue == $i ? 'checked' : '' ?>>
                                                                                    <label class="form-check-label" for="skill_<?= $skill['skill_id'] ?>_<?= $i ?>">
                                                                                        <?= $i ?>
                                                                                    </label>
                                                                                </div>
                                                                            <?php endfor; ?>
                                                                            
                                                                            <div class="form-check form-check-inline">
                                                                                <input class="form-check-input" type="radio" 
                                                                                       name="skills[<?= $skill['skill_id'] ?>]" 
                                                                                       id="skill_<?= $skill['skill_id'] ?>_0" 
                                                                                       value="0" 
                                                                                       <?= $currentValue == 0 ? 'checked' : '' ?>>
                                                                                <label class="form-check-label" for="skill_<?= $skill['skill_id'] ?>_0">
                                                                                    N/A
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Skills
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 