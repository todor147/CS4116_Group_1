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
    
    // Also get coach's custom skills
    try {
        $stmt = $pdo->prepare("
            SELECT custom_skill_id, category_id, skill_name, proficiency_level 
            FROM Coach_Custom_Skills 
            WHERE coach_id = ?
        ");
        $stmt->execute([$coach['coach_id']]);
        $customSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If table doesn't exist, create it but set customSkills to empty array
        if (strpos($e->getMessage(), "Base table or view not found: 'Coach_Custom_Skills'") !== false) {
            $pdo->exec("
                CREATE TABLE Coach_Custom_Skills (
                    custom_skill_id INT PRIMARY KEY AUTO_INCREMENT,
                    coach_id INT NOT NULL,
                    category_id INT NOT NULL,
                    skill_name VARCHAR(100) NOT NULL,
                    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE
                )
            ");
            $customSkills = [];
        } else {
            throw $e;
        }
    }
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
        }
        
        // Process custom skills
        if (isset($_POST['custom_skills']) && is_array($_POST['custom_skills'])) {
            try {
                // First clear existing custom skills
                $stmt = $pdo->prepare("DELETE FROM Coach_Custom_Skills WHERE coach_id = ?");
                $stmt->execute([$coach['coach_id']]);
                
                // Insert new custom skills
                $insertCustomSkillStmt = $pdo->prepare("
                    INSERT INTO Coach_Custom_Skills (coach_id, category_id, skill_name, proficiency_level) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($_POST['custom_skills'] as $customSkillId => $skillData) {
                    if (!isset($skillData['name']) || !isset($skillData['category_id']) || !isset($skillData['proficiency'])) {
                        continue; // Skip invalid data
                    }
                    
                    $skillName = trim($skillData['name']);
                    $categoryId = (int)$skillData['category_id'];
                    $proficiency = (int)$skillData['proficiency'];
                    
                    if (empty($skillName) || $categoryId <= 0 || $proficiency < 1 || $proficiency > 5) {
                        continue; // Skip invalid data
                    }
                    
                    // Store the custom skill directly in the Coach_Custom_Skills table
                    $insertCustomSkillStmt->execute([
                        $coach['coach_id'], 
                        $categoryId,
                        $skillName, 
                        $proficiency
                    ]);
                }
            } catch (PDOException $e) {
                // If table doesn't exist yet, create it
                if (strpos($e->getMessage(), "Base table or view not found: 'Coach_Custom_Skills'") !== false) {
                    $pdo->exec("
                        CREATE TABLE Coach_Custom_Skills (
                            custom_skill_id INT PRIMARY KEY AUTO_INCREMENT,
                            coach_id INT NOT NULL,
                            category_id INT NOT NULL,
                            skill_name VARCHAR(100) NOT NULL,
                            proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id) ON DELETE CASCADE,
                            FOREIGN KEY (category_id) REFERENCES Expertise_Categories(category_id) ON DELETE CASCADE
                        )
                    ");
                    
                    // Try again after creating the table
                    foreach ($_POST['custom_skills'] as $customSkillId => $skillData) {
                        if (!isset($skillData['name']) || !isset($skillData['category_id']) || !isset($skillData['proficiency'])) {
                            continue;
                        }
                        
                        $skillName = trim($skillData['name']);
                        $categoryId = (int)$skillData['category_id'];
                        $proficiency = (int)$skillData['proficiency'];
                        
                        if (empty($skillName) || $categoryId <= 0 || $proficiency < 1 || $proficiency > 5) {
                            continue;
                        }
                        
                        $insertCustomSkillStmt = $pdo->prepare("
                            INSERT INTO Coach_Custom_Skills (coach_id, category_id, skill_name, proficiency_level) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $insertCustomSkillStmt->execute([
                            $coach['coach_id'], 
                            $categoryId,
                            $skillName, 
                            $proficiency
                        ]);
                    }
                } else {
                    $errors[] = "Error saving custom skills: " . $e->getMessage();
                }
            }
        }
        
        $success = true;
        $_SESSION['success_message'] = "Your skills have been updated successfully.";
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
                                            <!-- Custom Skill Form -->
                                            <div class="card mb-4 border-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">Add Custom Skill to <?= htmlspecialchars($category['category_name']) ?></h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control custom-skill-name" 
                                                                   placeholder="Enter skill name (e.g., Chess Strategy, Advanced Calculus)" 
                                                                   data-category-id="<?= $category['category_id'] ?>">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select class="form-select custom-skill-level">
                                                                <option value="">Select proficiency</option>
                                                                <option value="1">1 - Basic</option>
                                                                <option value="2">2 - Intermediate</option>
                                                                <option value="3">3 - Advanced</option>
                                                                <option value="4">4 - Expert</option>
                                                                <option value="5">5 - Master</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="button" class="btn btn-primary btn-add-custom-skill">Add</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Custom Skill Form -->

                                            <div class="row" id="skills-container-<?= $category['category_id'] ?>">
                                                <?php 
                                                // Filter skills by category
                                                $categorySkills = array_filter($allSkills, function($skill) use ($category) {
                                                    return $skill['category_id'] == $category['category_id'];
                                                });
                                                
                                                // Also get custom skills for this category
                                                $categoryCustomSkills = array_filter($customSkills ?? [], function($skill) use ($category) {
                                                    return $skill['category_id'] == $category['category_id'];
                                                });
                                                
                                                if (empty($categorySkills) && empty($categoryCustomSkills)): 
                                                ?>
                                                    <div class="col-12 no-skills-message">
                                                        <p class="text-muted">No predefined skills available in this category. Use the form above to add your own.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php 
                                                    // Display standard skills first
                                                    foreach ($categorySkills as $skill): 
                                                    ?>
                                                        <div class="col-md-6 mb-3 skill-card">
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
                                                    
                                                    <!-- Display existing custom skills -->
                                                    <?php foreach ($categoryCustomSkills as $customSkill): ?>
                                                        <div class="col-md-6 mb-3 skill-card custom-skill">
                                                            <div class="card border-success">
                                                                <div class="card-body">
                                                                    <div class="d-flex justify-content-between">
                                                                        <h5 class="card-title h6"><?= htmlspecialchars($customSkill['skill_name']) ?></h5>
                                                                        <span class="badge bg-success">Custom</span>
                                                                    </div>
                                                                    <p class="card-text small">Custom skill added by you</p>
                                                                    
                                                                    <div class="mb-2">
                                                                        <label class="form-label small">Proficiency:</label>
                                                                        <div class="rating-display">
                                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                <i class="bi bi-star<?= $i <= $customSkill['proficiency_level'] ? '-fill' : '' ?> text-warning me-1"></i>
                                                                            <?php endfor; ?>
                                                                        </div>
                                                                        
                                                                        <!-- Hidden inputs to preserve this custom skill on form submit -->
                                                                        <input type="hidden" name="custom_skills[existing_<?= $customSkill['custom_skill_id'] ?>][name]" 
                                                                               value="<?= htmlspecialchars($customSkill['skill_name']) ?>">
                                                                        <input type="hidden" name="custom_skills[existing_<?= $customSkill['custom_skill_id'] ?>][category_id]" 
                                                                               value="<?= $customSkill['category_id'] ?>">
                                                                        <input type="hidden" name="custom_skills[existing_<?= $customSkill['custom_skill_id'] ?>][proficiency]" 
                                                                               value="<?= $customSkill['proficiency_level'] ?>">
                                                                    </div>
                                                                    
                                                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-custom-skill"
                                                                            data-skill-id="<?= $customSkill['custom_skill_id'] ?>">
                                                                        Remove
                                                                    </button>
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

                    <!-- Template for custom skill card -->
                    <template id="custom-skill-template">
                        <div class="col-md-6 mb-3 skill-card custom-skill">
                            <div class="card border-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title h6 skill-name"></h5>
                                        <span class="badge bg-success">Custom</span>
                                    </div>
                                    <p class="card-text small">Custom skill added by you</p>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Proficiency:</label>
                                        <div class="rating-display"></div>
                                        <input type="hidden" class="skill-value" name="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Handle adding custom skills
                            const addButtons = document.querySelectorAll('.btn-add-custom-skill');
                            const template = document.getElementById('custom-skill-template');
                            let customSkillCounter = 0;
                            
                            addButtons.forEach(button => {
                                button.addEventListener('click', function() {
                                    const parent = this.closest('.card-body');
                                    const nameInput = parent.querySelector('.custom-skill-name');
                                    const levelSelect = parent.querySelector('.custom-skill-level');
                                    const categoryId = nameInput.dataset.categoryId;
                                    const skillName = nameInput.value.trim();
                                    const proficiency = levelSelect.value;
                                    
                                    if (skillName === '') {
                                        alert('Please enter a skill name');
                                        return;
                                    }
                                    
                                    // Validate skill name - allow only letters, numbers, spaces and basic punctuation
                                    if (!/^[a-zA-Z0-9\s\-,.&+'()]+$/.test(skillName)) {
                                        alert('Skill name contains invalid characters. Please use only letters, numbers, spaces, and basic punctuation.');
                                        return;
                                    }
                                    
                                    // Check skill name length
                                    if (skillName.length > 100) {
                                        alert('Skill name is too long. Maximum 100 characters allowed.');
                                        return;
                                    }
                                    
                                    if (proficiency === '') {
                                        alert('Please select a proficiency level');
                                        return;
                                    }
                                    
                                    // Clone the template
                                    const clone = document.importNode(template.content, true);
                                    const skillId = 'custom_' + categoryId + '_' + (++customSkillCounter);
                                    
                                    // Update skill name
                                    clone.querySelector('.skill-name').textContent = skillName;
                                    
                                    // Create hidden input for the skill
                                    const hiddenInput = clone.querySelector('.skill-value');
                                    hiddenInput.name = `custom_skills[${skillId}][name]`;
                                    hiddenInput.value = skillName;
                                    
                                    // Create hidden input for category
                                    const categoryInput = document.createElement('input');
                                    categoryInput.type = 'hidden';
                                    categoryInput.name = `custom_skills[${skillId}][category_id]`;
                                    categoryInput.value = categoryId;
                                    clone.querySelector('.card-body').appendChild(categoryInput);
                                    
                                    // Create hidden input for proficiency
                                    const proficiencyInput = document.createElement('input');
                                    proficiencyInput.type = 'hidden';
                                    proficiencyInput.name = `custom_skills[${skillId}][proficiency]`;
                                    proficiencyInput.value = proficiency;
                                    clone.querySelector('.card-body').appendChild(proficiencyInput);
                                    
                                    // Display stars for proficiency
                                    const ratingDisplay = clone.querySelector('.rating-display');
                                    for (let i = 1; i <= 5; i++) {
                                        const star = document.createElement('i');
                                        star.className = i <= proficiency ? 'bi bi-star-fill text-warning me-1' : 'bi bi-star text-warning me-1';
                                        ratingDisplay.appendChild(star);
                                    }
                                    
                                    // Add a remove button
                                    const removeBtn = document.createElement('button');
                                    removeBtn.className = 'btn btn-sm btn-outline-danger mt-2';
                                    removeBtn.textContent = 'Remove';
                                    removeBtn.addEventListener('click', function() {
                                        this.closest('.skill-card').remove();
                                    });
                                    clone.querySelector('.card-body').appendChild(removeBtn);
                                    
                                    // Append to the container
                                    const container = document.getElementById(`skills-container-${categoryId}`);
                                    
                                    // Remove "no skills" message if it exists
                                    const noSkillsMsg = container.querySelector('.no-skills-message');
                                    if (noSkillsMsg) {
                                        noSkillsMsg.remove();
                                    }
                                    
                                    container.appendChild(clone);
                                    
                                    // Clear the inputs
                                    nameInput.value = '';
                                    levelSelect.selectedIndex = 0;
                                });
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 