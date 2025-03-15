<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php?redirect=edit-coach-services.php");
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
$serviceTiers = [];

// Get coach's current service tiers
try {
    $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE coach_id = ? ORDER BY price ASC");
    $stmt->execute([$coach['coach_id']]);
    $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission for adding/editing service tier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new service tier
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            
            // Validate inputs
            if (empty($name)) {
                $errors[] = "Service name is required";
            }
            
            if (empty($description)) {
                $errors[] = "Service description is required";
            }
            
            if ($price <= 0) {
                $errors[] = "Price must be greater than zero";
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO ServiceTiers (coach_id, name, description, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$coach['coach_id'], $name, $description, $price]);
                    
                    $success = true;
                    $_SESSION['success_message'] = "Service tier added successfully";
                    
                    // Refresh service tiers
                    $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE coach_id = ? ORDER BY price ASC");
                    $stmt->execute([$coach['coach_id']]);
                    $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
        // Edit existing service tier
        elseif ($_POST['action'] === 'edit' && isset($_POST['tier_id'])) {
            $tierId = intval($_POST['tier_id']);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            
            // Validate inputs
            if (empty($name)) {
                $errors[] = "Service name is required";
            }
            
            if (empty($description)) {
                $errors[] = "Service description is required";
            }
            
            if ($price <= 0) {
                $errors[] = "Price must be greater than zero";
            }
            
            if (empty($errors)) {
                try {
                    // Verify the tier belongs to this coach
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM ServiceTiers 
                        WHERE tier_id = ? AND coach_id = ?
                    ");
                    $stmt->execute([$tierId, $coach['coach_id']]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $stmt = $pdo->prepare("
                            UPDATE ServiceTiers 
                            SET name = ?, description = ?, price = ?
                            WHERE tier_id = ?
                        ");
                        $stmt->execute([$name, $description, $price, $tierId]);
                        
                        $success = true;
                        $_SESSION['success_message'] = "Service tier updated successfully";
                        
                        // Refresh service tiers
                        $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE coach_id = ? ORDER BY price ASC");
                        $stmt->execute([$coach['coach_id']]);
                        $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $errors[] = "Invalid service tier";
                    }
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
        // Delete service tier
        elseif ($_POST['action'] === 'delete' && isset($_POST['tier_id'])) {
            $tierId = intval($_POST['tier_id']);
            
            try {
                // Verify the tier belongs to this coach
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM ServiceTiers 
                    WHERE tier_id = ? AND coach_id = ?
                ");
                $stmt->execute([$tierId, $coach['coach_id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    // Check if this tier is used in any sessions or inquiries
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM ServiceInquiries 
                        WHERE tier_id = ?
                    ");
                    $stmt->execute([$tierId]);
                    $inquiryCount = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM Sessions 
                        WHERE tier_id = ?
                    ");
                    $stmt->execute([$tierId]);
                    $sessionCount = $stmt->fetchColumn();
                    
                    if ($inquiryCount > 0 || $sessionCount > 0) {
                        $errors[] = "This service tier cannot be deleted because it is being used in active inquiries or sessions";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM ServiceTiers WHERE tier_id = ?");
                        $stmt->execute([$tierId]);
                        
                        $success = true;
                        $_SESSION['success_message'] = "Service tier deleted successfully";
                        
                        // Refresh service tiers
                        $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE coach_id = ? ORDER BY price ASC");
                        $stmt->execute([$coach['coach_id']]);
                        $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } else {
                    $errors[] = "Invalid service tier";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
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
                    <a href="edit-coach-profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Profile
                    </a>
                    <a href="edit-coach-skills.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-stars"></i> Skills & Expertise
                    </a>
                    <a href="edit-coach-availability.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Availability
                    </a>
                    <a href="edit-coach-services.php" class="list-group-item list-group-item-action active">
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Service Tiers</h2>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="bi bi-plus-circle"></i> Add Service
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success_message'] ?? 'Operation completed successfully' ?>
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
                    
                    <p class="mb-4">Create different service tiers to offer various coaching packages to your clients.</p>
                    
                    <?php if (empty($serviceTiers)): ?>
                        <div class="alert alert-info">
                            <p>You haven't created any service tiers yet. Click the "Add Service" button to create your first service tier.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($serviceTiers as $tier): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?= htmlspecialchars($tier['name']) ?></h5>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" id="dropdownMenuButton<?= $tier['tier_id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?= $tier['tier_id'] ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $tier['tier_id'] ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#previewServiceModal<?= $tier['tier_id'] ?>">
                                                            <i class="bi bi-eye"></i> Preview
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteServiceModal<?= $tier['tier_id'] ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">$<?= number_format($tier['price'], 2) ?></h6>
                                            <p class="card-text"><?= nl2br(htmlspecialchars($tier['description'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Service Modal -->
                                <div class="modal fade" id="editServiceModal<?= $tier['tier_id'] ?>" tabindex="-1" aria-labelledby="editServiceModalLabel<?= $tier['tier_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="tier_id" value="<?= $tier['tier_id'] ?>">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editServiceModalLabel<?= $tier['tier_id'] ?>">Edit Service Tier</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="edit_name<?= $tier['tier_id'] ?>" class="form-label">Service Name *</label>
                                                        <input type="text" class="form-control" id="edit_name<?= $tier['tier_id'] ?>" name="name" 
                                                               value="<?= htmlspecialchars($tier['name']) ?>" required>
                                                        <small class="text-muted">Choose a clear, descriptive name that highlights the value of this service.</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="edit_description<?= $tier['tier_id'] ?>" class="form-label">Description *</label>
                                                        <textarea class="form-control" id="edit_description<?= $tier['tier_id'] ?>" name="description" 
                                                                  rows="6" required><?= htmlspecialchars($tier['description']) ?></textarea>
                                                        <small class="text-muted">Be specific about what's included. Use bullet points (• item) for better readability.</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="edit_price<?= $tier['tier_id'] ?>" class="form-label">Price (USD) *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" class="form-control" id="edit_price<?= $tier['tier_id'] ?>" name="price" 
                                                                   step="0.01" min="1" value="<?= $tier['price'] ?>" required>
                                                        </div>
                                                        <small class="text-muted">Set competitive rates based on your experience level and market demand.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preview Service Modal -->
                                <div class="modal fade" id="previewServiceModal<?= $tier['tier_id'] ?>" tabindex="-1" aria-labelledby="previewServiceModalLabel<?= $tier['tier_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="previewServiceModalLabel<?= $tier['tier_id'] ?>">Service Preview</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="card">
                                                    <div class="card-header bg-light">
                                                        <h5 class="mb-0">Customer View</h5>
                                                        <small class="text-muted">This is how your service will appear to potential customers</small>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="service-preview p-4 border rounded">
                                                            <div class="row">
                                                                <div class="col-md-8">
                                                                    <h3 class="mb-3"><?= htmlspecialchars($tier['name']) ?></h3>
                                                                    <div class="description mb-4">
                                                                        <?= nl2br(htmlspecialchars($tier['description'])) ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4 text-center">
                                                                    <div class="price-tag p-3 mb-3 bg-light rounded">
                                                                        <h4 class="mb-0">$<?= number_format($tier['price'], 2) ?></h4>
                                                                        <small class="text-muted">per session</small>
                                                                    </div>
                                                                    <button class="btn btn-primary btn-lg w-100" disabled>Book Session</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-muted">
                                                    <p><strong>Tips for effective service descriptions:</strong></p>
                                                    <ul>
                                                        <li>Highlight unique benefits of this service tier</li>
                                                        <li>Be specific about session duration and frequency</li>
                                                        <li>Mention any special tools or resources included</li>
                                                        <li>Use bullet points for easy scanning</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $tier['tier_id'] ?>">Edit This Service</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Service Modal -->
                                <div class="modal fade" id="deleteServiceModal<?= $tier['tier_id'] ?>" tabindex="-1" aria-labelledby="deleteServiceModalLabel<?= $tier['tier_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="tier_id" value="<?= $tier['tier_id'] ?>">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteServiceModalLabel<?= $tier['tier_id'] ?>">Confirm Deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the service tier "<strong><?= htmlspecialchars($tier['name']) ?></strong>"?</p>
                                                    <p class="text-danger">This action cannot be undone. If this service tier is being used in any active inquiries or sessions, it cannot be deleted.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="" id="addServiceForm">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addServiceModalLabel">Add Service Tier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Creating Effective Service Tiers</h6>
                                <p class="mb-0">Consider offering 2-3 service tiers at different price points. This gives your customers options while highlighting the value of premium offerings.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Service Tier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="e.g., Basic Coaching, Standard Package, Premium Mentorship" required>
                                <small class="text-muted">Choose a clear, descriptive name that highlights the value of this service.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <div class="mb-2">
                                    <div class="btn-group description-template-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary description-template" data-template="basic">Basic Template</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary description-template" data-template="standard">Standard Template</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary description-template" data-template="premium">Premium Template</button>
                                    </div>
                                </div>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="8" placeholder="Describe what's included in this service tier..." required></textarea>
                                <small class="text-muted">Be specific about what's included. Use bullet points (• item) for better readability.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (USD) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="1" value="<?= $coach['hourly_rate'] ?? '40.00' ?>" required>
                                </div>
                                <small class="text-muted">Set competitive rates based on your experience level and market demand.</small>
                            </div>
                            
                            <div class="card mb-3 mt-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Tips for Success</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="small mb-0">
                                        <li>Focus on the benefits, not just features</li>
                                        <li>Include session duration and frequency</li>
                                        <li>Mention preparation/follow-up time</li>
                                        <li>Highlight tools or resources provided</li>
                                        <li>Consider adding tiered pricing structure</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enablePreview" checked>
                                <label class="form-check-label" for="enablePreview">
                                    Preview before saving
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" id="previewBeforeSave">Preview</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Before Save Modal -->
<div class="modal fade" id="previewBeforeSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Service Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Customer View</h5>
                        <small class="text-muted">This is how your service will appear to potential customers</small>
                    </div>
                    <div class="card-body">
                        <div class="service-preview p-4 border rounded">
                            <div class="row">
                                <div class="col-md-8">
                                    <h3 class="mb-3" id="preview-name"></h3>
                                    <div class="description mb-4" id="preview-description"></div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="price-tag p-3 mb-3 bg-light rounded">
                                        <h4 class="mb-0" id="preview-price"></h4>
                                        <small class="text-muted">per session</small>
                                    </div>
                                    <button class="btn btn-primary btn-lg w-100" disabled>Book Session</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back to Edit</button>
                <button type="button" class="btn btn-primary" id="confirmAndSave">Confirm & Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript for preview functionality and templates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Description templates
    const templates = {
        basic: "This basic coaching package includes:\n\n• One 45-minute coaching session\n• Personalized learning plan\n• Email support between sessions\n• Session notes and summary\n• Recommended resources and materials",
        standard: "Enhance your learning with our standard package:\n\n• One 60-minute comprehensive coaching session\n• Personalized learning plan and progress tracking\n• Practice exercises and homework assignments\n• Unlimited email support between sessions\n• Access to supplementary learning materials\n• Session recordings for review",
        premium: "Our premium coaching experience includes:\n\n• One 90-minute in-depth coaching session\n• Comprehensive assessment and personalized learning path\n• Unlimited email and chat support between sessions\n• Priority scheduling and flexible rescheduling\n• Customized learning materials and resources\n• Session recordings for future reference\n• Monthly progress report and performance review\n• Direct access to instructor for urgent questions"
    };
    
    // Add template buttons functionality
    document.querySelectorAll('.description-template').forEach(button => {
        button.addEventListener('click', function() {
            const templateName = this.dataset.template;
            document.getElementById('description').value = templates[templateName];
            
            // Visual feedback
            document.querySelectorAll('.description-template').forEach(btn => {
                btn.classList.remove('active', 'btn-secondary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary', 'active');
        });
    });
    
    // Preview functionality
    const previewButton = document.getElementById('previewBeforeSave');
    const confirmButton = document.getElementById('confirmAndSave');
    const serviceForm = document.getElementById('addServiceForm');
    
    // Update preview content
    function updatePreview() {
        const name = document.getElementById('name').value || '[Service Name]';
        const description = document.getElementById('description').value || '[Service Description]';
        const price = document.getElementById('price').value || '0.00';
        
        document.getElementById('preview-name').textContent = name;
        document.getElementById('preview-description').innerHTML = description.replace(/\n/g, '<br>');
        document.getElementById('preview-price').textContent = '$' + parseFloat(price).toFixed(2);
    }
    
    // Show preview modal
    previewButton.addEventListener('click', function() {
        updatePreview();
        
        // Hide current modal and show preview
        $('#addServiceModal').modal('hide');
        $('#previewBeforeSaveModal').modal('show');
    });
    
    // Confirm and submit form
    confirmButton.addEventListener('click', function() {
        $('#previewBeforeSaveModal').modal('hide');
        serviceForm.submit();
    });
    
    // Auto-preview if checkbox is enabled
    serviceForm.addEventListener('submit', function(e) {
        if (document.getElementById('enablePreview').checked) {
            e.preventDefault();
            updatePreview();
            $('#addServiceModal').modal('hide');
            $('#previewBeforeSaveModal').modal('show');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 