<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$users = [];
$error = '';

if (!empty($search)) {
    try {
        // Search for users by username or email (excluding the current user)
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.email, u.profile_image, c.coach_id, c.headline
            FROM Users u
            LEFT JOIN Coaches c ON u.user_id = c.user_id
            WHERE u.user_id != ?  -- Exclude current user
            AND (u.username LIKE ? OR u.email LIKE ?)
            ORDER BY 
                CASE WHEN c.coach_id IS NOT NULL THEN 0 ELSE 1 END,  -- Coaches first
                u.username
            LIMIT 50
        ");
        $searchTerm = "%{$search}%";
        $stmt->execute([$user_id, $searchTerm, $searchTerm]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Search Users</h5>
                    <a href="messages.php" class="btn btn-light btn-sm">Back to Messages</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by username or email..." required>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>

                    <?php if (!empty($search) && empty($users)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No users found matching "<?= htmlspecialchars($search) ?>"</p>
                        </div>
                    <?php elseif (!empty($users)): ?>
                        <h6 class="mb-3">Found <?= count($users) ?> user(s) matching "<?= htmlspecialchars($search) ?>"</h6>
                        <div class="list-group">
                            <?php foreach ($users as $user): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <?php 
                                            $profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default.jpg';
                                            $image_path = "../assets/images/profiles/{$profile_image}";
                                            ?>
                                            <img src="<?= $image_path ?>" alt="Profile" class="rounded-circle" style="width: 50px; height: 50px;">
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?= htmlspecialchars($user['username']) ?></h6>
                                            <?php if (!empty($user['coach_id'])): ?>
                                                <span class="badge bg-success">Coach</span>
                                                <?php if (!empty($user['headline'])): ?>
                                                    <p class="mb-1 small"><?= htmlspecialchars($user['headline']) ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ms-auto">
                                            <a href="messages.php?user=<?= $user['user_id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-chat-dots-fill"></i> Message
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Search for users by username or email</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 