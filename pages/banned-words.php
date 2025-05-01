<?php
session_start();

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Check if user is admin (same check as in admin.php)
if ((!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) && 
    (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin')) {
    // Clear the session to ensure a clean state
    session_unset();
    session_destroy();
    
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new banned word
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $word = trim($_POST['word']);
        
        if (!empty($word)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO BannedWords (word) VALUES (?) ON DUPLICATE KEY UPDATE word = word");
                $stmt->execute([$word]);
                $success = "Word \"" . htmlspecialchars($word) . "\" has been added to the banned list.";
            } catch (PDOException $e) {
                $error = "Failed to add word: " . $e->getMessage();
            }
        } else {
            $error = "Please enter a word to ban.";
        }
    }
    
    // Delete banned word
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $word_id = $_POST['word_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM BannedWords WHERE word_id = ?");
            $stmt->execute([$word_id]);
            $success = "Word has been removed from the banned list.";
        } catch (PDOException $e) {
            $error = "Failed to remove word: " . $e->getMessage();
        }
    }
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch banned words
try {
    // Build the query based on search
    $query = "SELECT * FROM BannedWords WHERE 1=1";
    $params = [];
    
    // Add search condition if search term provided
    if (!empty($search)) {
        $query .= " AND word LIKE ?";
        $search_term = "%$search%";
        $params[] = $search_term;
    }
    
    // Add ordering
    $query .= " ORDER BY word ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bannedWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Banned Words Management</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Banned Word</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="banned-words.php">
                        <input type="hidden" name="action" value="add">
                        <div class="input-group">
                            <input type="text" class="form-control" name="word" placeholder="Enter word to ban" required>
                            <button type="submit" class="btn btn-primary">Add Word</button>
                        </div>
                        <small class="form-text text-muted">Words added here will be automatically filtered in reviews and messages.</small>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Search Banned Words</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="banned-words.php">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search banned words" value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-secondary">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="banned-words.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Banned Words List</h5>
                    <span class="badge bg-light text-dark"><?= count($bannedWords) ?> words</span>
                </div>
                <div class="card-body">
                    <?php if (count($bannedWords) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Word</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bannedWords as $word): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($word['word']) ?></td>
                                            <td>
                                                <form method="post" action="banned-words.php" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="word_id" value="<?= $word['word_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this word?')">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php if (!empty($search)): ?>
                                No banned words found matching "<?= htmlspecialchars($search) ?>".
                            <?php else: ?>
                                No banned words have been added yet.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">About Banned Words</h5>
                </div>
                <div class="card-body">
                    <p>Banned words are automatically filtered in the following areas:</p>
                    <ul>
                        <li><strong>Reviews</strong> - Reviews containing banned words will require moderation before being published.</li>
                        <li><strong>Messages</strong> - Messages containing banned words will be sent to the moderation queue for approval.</li>
                    </ul>
                    <p>The system uses exact matching (case-insensitive) to detect banned words. To ban phrases or variations, add each variation separately.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 