<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Ensure only admins can access this page
if (!isAdmin()) {
    header('Location: login.php?error=' . urlencode('You must be an admin to access this page.'));
    exit;
}

$success_message = '';
$error_message = '';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Expertise_Categories WHERE category_id = ?");
        $stmt->execute([$_GET['delete']]);
        $success_message = "Category deleted successfully.";
    } catch (PDOException $e) {
        // Check if the error is due to foreign key constraint
        if ($e->getCode() == 23000) {
            $error_message = "Cannot delete this category because it is being used by coaches.";
        } else {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Handle form submission for adding or editing a category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } else {
        try {
            if ($category_id) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE Expertise_Categories SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->execute([$category_name, $description, $category_id]);
                $success_message = "Category updated successfully.";
            } else {
                // Add new category
                $stmt = $pdo->prepare("INSERT INTO Expertise_Categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$category_name, $description]);
                $success_message = "Category added successfully.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get category to edit if an ID is provided
$category_to_edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Expertise_Categories WHERE category_id = ?");
        $stmt->execute([$_GET['edit']]);
        $category_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get all categories
try {
    $stmt = $pdo->prepare("SELECT * FROM Expertise_Categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $categories = [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= $category_to_edit ? 'Edit Category' : 'Manage Expertise Categories' ?></h1>
                <?php if ($category_to_edit): ?>
                    <a href="manage-categories.php" class="btn btn-outline-primary">Back to Categories</a>
                <?php endif; ?>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?= $category_to_edit ? 'Edit Category' : 'Add New Category' ?></h4>
                </div>
                <div class="card-body">
                    <form method="post" action="manage-categories.php">
                        <?php if ($category_to_edit): ?>
                            <input type="hidden" name="category_id" value="<?= $category_to_edit['category_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name*</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required
                                   value="<?= $category_to_edit ? htmlspecialchars($category_to_edit['category_name']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= $category_to_edit ? htmlspecialchars($category_to_edit['description']) : '' ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?= $category_to_edit ? 'Update Category' : 'Add Category' ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if (!$category_to_edit): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Existing Categories</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted">No categories found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?= $category['category_id'] ?></td>
                                                <td><?= htmlspecialchars($category['category_name']) ?></td>
                                                <td><?= htmlspecialchars($category['description'] ?? '') ?></td>
                                                <td><?= $category['created_at'] ?></td>
                                                <td>
                                                    <a href="manage-categories.php?edit=<?= $category['category_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="manage-categories.php?delete=<?= $category['category_id'] ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 