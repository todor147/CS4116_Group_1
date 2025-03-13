<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'regular';
$is_coach = ($user_type === 'business');

try {
    if ($is_coach) {
        // Get coach ID
        $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        $coach_id = $coach['coach_id'] ?? null;

        // Get all sessions for coach
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   u.username as learner_name,
                   st.name as service_name,
                   r.rating,
                   r.comment
            FROM Sessions s
            JOIN Users u ON s.learner_id = u.user_id
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            LEFT JOIN Reviews r ON s.session_id = r.session_id
            WHERE s.coach_id = ?
            ORDER BY s.scheduled_time DESC
        ");
        $stmt->execute([$coach_id]);
    } else {
        // Get all sessions for learner
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   u.username as coach_name,
                   st.name as service_name,
                   r.rating,
                   r.comment
            FROM Sessions s
            JOIN Coaches c ON s.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            LEFT JOIN Reviews r ON s.session_id = r.session_id
            WHERE s.learner_id = ?
            ORDER BY s.scheduled_time DESC
        ");
        $stmt->execute([$user_id]);
    }
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?= $is_coach ? 'Your Teaching Sessions' : 'Your Learning Sessions' ?></h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($sessions)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No sessions found.
                    <?php if (!$is_coach): ?>
                        <a href="search.php" class="btn btn-primary ms-3">Find a Coach</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th><?= $is_coach ? 'Learner' : 'Coach' ?></th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Review</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessions as $session): ?>
                                        <tr>
                                            <td><?= date('M d, Y h:i A', strtotime($session['scheduled_time'])) ?></td>
                                            <td>
                                                <?= htmlspecialchars($is_coach ? $session['learner_name'] : $session['coach_name']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($session['service_name']) ?></td>
                                            <td>
                                                <span class="badge <?= 
                                                    $session['status'] === 'scheduled' ? 'bg-warning' : 
                                                    ($session['status'] === 'completed' ? 'bg-success' : 'bg-danger') 
                                                ?>">
                                                    <?= ucfirst($session['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($session['status'] === 'completed'): ?>
                                                    <?php if (!$is_coach): ?>
                                                        <?php if (isset($session['rating'])): ?>
                                                            <div class="text-warning">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="bi bi-star<?= ($i <= $session['rating']) ? '-fill' : '' ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <a href="review.php?session_id=<?= $session['session_id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                Leave Review
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if (isset($session['rating'])): ?>
                                                            <div class="text-warning">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="bi bi-star<?= ($i <= $session['rating']) ? '-fill' : '' ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No review yet</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="session-details.php?id=<?= $session['session_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 