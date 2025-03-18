<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$selected_coach_id = $_GET['coach_id'] ?? null;
$selected_tier_id = $_GET['tier_id'] ?? null;
$coach_details = null;
$service_details = null;
$available_slots = [];

try {
    // Validate and get coach details
    if ($selected_coach_id) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username as coach_name 
            FROM Coaches c
            JOIN Users u ON c.user_id = u.user_id
            WHERE c.coach_id = ?
        ");
        $stmt->execute([$selected_coach_id]);
        $coach_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coach_details) {
            $error = 'Invalid coach selected';
            $selected_coach_id = null;
        }
    }

    // Validate and get service details
    if ($selected_tier_id) {
        $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE tier_id = ?");
        $stmt->execute([$selected_tier_id]);
        $service_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service_details) {
            $error = 'Invalid service selected';
            $selected_tier_id = null;
        }
    }

    // Get coach's available time slots
    if ($selected_coach_id) {
        $stmt = $pdo->prepare("
            SELECT available_time 
            FROM CoachAvailability 
            WHERE coach_id = ?
            AND available_time > NOW()
            AND available_time NOT IN (
                SELECT scheduled_time 
                FROM Sessions 
                WHERE coach_id = ? 
                AND status IN ('scheduled', 'completed')
            )
            ORDER BY available_time
        ");
        $stmt->execute([$selected_coach_id, $selected_coach_id]);
        $available_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_slot = $_POST['selected_slot'] ?? null;
        $coach_id = $_POST['coach_id'] ?? null;
        $tier_id = $_POST['tier_id'] ?? null;

        // Basic validation
        if (!$selected_slot || !$coach_id || !$tier_id) {
            $error = 'Please select a time slot';
        } else {
            // Insert new session
            $stmt = $pdo->prepare("
                INSERT INTO Sessions (learner_id, coach_id, tier_id, scheduled_time, status)
                VALUES (?, ?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$_SESSION['user_id'], $coach_id, $tier_id, $selected_slot]);
            
            $success = 'Session booked successfully!';
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Book a Session</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($coach_details && $service_details): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Booking Details</h5>
                        <p class="mb-1"><strong>Coach:</strong> <?= htmlspecialchars($coach_details['coach_name']) ?></p>
                        <p class="mb-1"><strong>Package:</strong> <?= htmlspecialchars($service_details['name']) ?></p>
                        <p class="mb-0"><strong>Price:</strong> $<?= number_format($service_details['price'], 2) ?></p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="coach_id" value="<?= htmlspecialchars($selected_coach_id) ?>">
                    <input type="hidden" name="tier_id" value="<?= htmlspecialchars($selected_tier_id) ?>">

                    <div class="mb-3">
                        <label for="selected_slot" class="form-label">Available Time Slots</label>
                        <select class="form-select" id="selected_slot" name="selected_slot" required>
                            <option value="">Select a time slot</option>
                            <?php foreach ($available_slots as $slot): ?>
                                <option value="<?= $slot['available_time'] ?>">
                                    <?= date('l, F j g:i A', strtotime($slot['available_time'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($available_slots)): ?>
                            <div class="alert alert-warning mt-2">
                                This coach has no available time slots. Please check back later.
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Book Session</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Please select a valid coach and package</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 