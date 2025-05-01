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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    // Build the query based on search and filter
    $query = "SELECT user_id, username, email, is_banned FROM Users WHERE 1=1";
    $params = [];
    
    // Add search condition if search term provided
    if (!empty($search)) {
        $query .= " AND (username LIKE ? OR email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Add status filter if not 'all'
    if ($status_filter === 'active') {
        $query .= " AND is_banned = 0";
    } elseif ($status_filter === 'banned') {
        $query .= " AND is_banned = 1";
    }
    
    // Add ordering
    $query .= " ORDER BY username ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/admin-header.php';
?>  

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Manage Users</h1>
        </div>
    </div>
    
    <!-- Search and Filter Form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Search and Filter</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="manage-users.php" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by username or email" 
                                       name="search" value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                                    <a href="manage-users.php" class="btn btn-outline-secondary">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group">
                                <a href="manage-users.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
                                   class="btn btn-outline-primary <?= $status_filter === 'all' ? 'active' : '' ?>">All Users</a>
                                <a href="manage-users.php?status=active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="btn btn-outline-success <?= $status_filter === 'active' ? 'active' : '' ?>">Active</a>
                                <a href="manage-users.php?status=banned<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="btn btn-outline-danger <?= $status_filter === 'banned' ? 'active' : '' ?>">Banned</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">User List <?php if (!empty($search)): ?><small class="text-white">(Search: "<?= htmlspecialchars($search) ?>")</small><?php endif; ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($users) > 0) {
                                foreach ($users as $user) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                    echo "<td>" . ($user['is_banned'] ? '<span class="badge bg-danger">Banned</span>' : '<span class="badge bg-success">Active</span>') . "</td>";
                                    echo "<td><form method='post' action='ban_control.php'><input type='hidden' name='user_id' value='" . $user['user_id'] . "'><input type='submit' class='btn btn-sm " . ($user['is_banned'] ? 'btn-success' : 'btn-danger') . "' value='" . ($user['is_banned'] ? 'Unban' : 'Ban') . "'></form></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No users found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <p>Showing <?= count($users) ?> user(s)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
