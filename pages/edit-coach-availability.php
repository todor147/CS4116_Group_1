<?php
// Temporary helper for direct database insertion (for testing)
// Add this to the top of the file before session_start()
if (isset($_GET['create_test_slots']) && $_GET['create_test_slots'] === 'yes') {
    require_once __DIR__ . '/../includes/db_connection.php';
    
    // First, clear existing slots for testing
    $stmt = $pdo->prepare("DELETE FROM CoachTimeSlots");
    $stmt->execute();
    
    // Create test slots for coach ID 1
    $coach_id = 1;
    $dates = [
        date('Y-m-d'), // Today
        date('Y-m-d', strtotime('+1 day')), // Tomorrow
        date('Y-m-d', strtotime('+2 day')), // Day after tomorrow
        date('Y-m-d', strtotime('+3 day')), // 3 days from now
        date('Y-m-d', strtotime('+4 day'))  // 4 days from now
    ];
    
    foreach ($dates as $date) {
        // Early morning slots (9 AM to 12 PM)
        for ($hour = 9; $hour < 12; $hour++) {
            $start_time = $date . ' ' . sprintf('%02d:00:00', $hour);
            $end_time = $date . ' ' . sprintf('%02d:00:00', $hour + 1);
            
            $stmt = $pdo->prepare("
                INSERT INTO CoachTimeSlots (coach_id, start_time, end_time, status)
                VALUES (?, ?, ?, 'available')
            ");
            $stmt->execute([$coach_id, $start_time, $end_time]);
        }
        
        // Afternoon slots (2 PM to 6 PM)
        for ($hour = 14; $hour < 18; $hour++) {
            $start_time = $date . ' ' . sprintf('%02d:00:00', $hour);
            $end_time = $date . ' ' . sprintf('%02d:00:00', $hour + 1);
            
            $stmt = $pdo->prepare("
                INSERT INTO CoachTimeSlots (coach_id, start_time, end_time, status)
                VALUES (?, ?, ?, 'available')
            ");
            $stmt->execute([$coach_id, $start_time, $end_time]);
        }
    }
    
    // Create slots for other coaches too (for better testing)
    $other_coaches = [2, 3, 4, 5];
    foreach ($other_coaches as $other_coach_id) {
        for ($i = 0; $i < 3; $i++) { // For the next 3 days
            $date = date('Y-m-d', strtotime("+$i day"));
            // Add 4 time slots per day per coach
            for ($hour = 10; $hour < 18; $hour += 2) {
                $start_time = $date . ' ' . sprintf('%02d:00:00', $hour);
                $end_time = $date . ' ' . sprintf('%02d:00:00', $hour + 1);
                
                $stmt = $pdo->prepare("
                    INSERT INTO CoachTimeSlots (coach_id, start_time, end_time, status)
                    VALUES (?, ?, ?, 'available')
                ");
                $stmt->execute([$other_coach_id, $start_time, $end_time]);
            }
        }
    }
    
    // Mark a few slots as booked for testing
    $booked_date = $dates[1]; // Tomorrow
    $booked_times = [
        $booked_date . ' 10:00:00',
        $booked_date . ' 11:00:00',
        $booked_date . ' 15:00:00'
    ];
    
    foreach ($booked_times as $booked_start_time) {
        $booked_end_time = date('Y-m-d H:i:s', strtotime($booked_start_time . ' +1 hour'));
        
        $stmt = $pdo->prepare("
            UPDATE CoachTimeSlots 
            SET status = 'booked' 
            WHERE coach_id = ? AND start_time = ? AND end_time = ?
        ");
        $stmt->execute([$coach_id, $booked_start_time, $booked_end_time]);
    }
    
    echo "Test time slots created successfully.";
    exit;
}

session_start();
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=edit-coach-availability.php");
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
$message = '';

// Handle add time slots submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_slots') {
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $time_from = $_POST['time_from'] ?? '';
    $time_to = $_POST['time_to'] ?? '';
    $slot_duration = (int)$_POST['slot_duration'] ?? 60;
    $days = $_POST['days'] ?? [];
    
    if (empty($date_from) || empty($date_to) || empty($time_from) || empty($time_to) || empty($days)) {
        $errors[] = "All fields are required";
    } else {
        try {
            // Convert inputs to DateTime objects
            $start_date = new DateTime($date_from);
            $end_date = new DateTime($date_to);
            $end_date->setTime(23, 59, 59); // Include the entire last day
            
            // Parse time inputs
            list($from_hours, $from_minutes) = explode(':', $time_from);
            list($to_hours, $to_minutes) = explode(':', $time_to);
            
            // Add a day to iterate through the date range
            $current_date = clone $start_date;
            $slots_added = 0;
            
            $pdo->beginTransaction();
            
            // Loop through each day in the range
            while ($current_date <= $end_date) {
                $current_day = strtolower($current_date->format('l')); // e.g., "monday"
                
                // Check if the current day is selected
                if (in_array($current_day, $days)) {
                    // Set the starting time for this day
                    $slot_start = clone $current_date;
                    $slot_start->setTime((int)$from_hours, (int)$from_minutes);
                    
                    // Set the ending time for this day
                    $day_end = clone $current_date;
                    $day_end->setTime((int)$to_hours, (int)$to_minutes);
                    
                    // Create time slots for this day
                    while ($slot_start < $day_end) {
                        $slot_end = clone $slot_start;
                        $slot_end->modify("+{$slot_duration} minutes");
                        
                        // If the slot end time exceeds the day end time, stop
                        if ($slot_end > $day_end) {
                            break;
                        }
                        
                        // Check if this slot already exists
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM CoachTimeSlots
                            WHERE coach_id = ? AND start_time = ? AND end_time = ?
                        ");
                        $stmt->execute([
                            $coach['coach_id'],
                            $slot_start->format('Y-m-d H:i:s'),
                            $slot_end->format('Y-m-d H:i:s')
                        ]);
                        
                        if ($stmt->fetchColumn() == 0) {
                            // Insert the time slot
                            $stmt = $pdo->prepare("
                                INSERT INTO CoachTimeSlots (coach_id, start_time, end_time, status)
                                VALUES (?, ?, ?, 'available')
                            ");
                            $stmt->execute([
                                $coach['coach_id'],
                                $slot_start->format('Y-m-d H:i:s'),
                                $slot_end->format('Y-m-d H:i:s')
                            ]);
                            
                            $slots_added++;
                        }
                        
                        // Move to the next slot
                        $slot_start = $slot_end;
                    }
                }
                
                // Move to the next day
                $current_date->modify('+1 day');
            }
            
            $pdo->commit();
            
            if ($slots_added > 0) {
                $success = true;
                $message = "{$slots_added} time slots have been added to your schedule.";
            } else {
                $message = "No new time slots were added. They may already exist.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating time slots: " . $e->getMessage();
        }
    }
}

// Handle delete slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_slot') {
    $slot_id = $_POST['slot_id'] ?? 0;
    
    if (!$slot_id) {
        $errors[] = "Invalid slot ID";
    } else {
        try {
            // Check if the slot belongs to this coach
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM CoachTimeSlots
                WHERE slot_id = ? AND coach_id = ?
            ");
            $stmt->execute([$slot_id, $coach['coach_id']]);
            
            if ($stmt->fetchColumn() > 0) {
                $stmt = $pdo->prepare("
                    DELETE FROM CoachTimeSlots
                    WHERE slot_id = ? AND status = 'available'
                ");
                $stmt->execute([$slot_id]);
                
                if ($stmt->rowCount() > 0) {
                    $success = true;
                    $message = "Time slot removed successfully.";
                } else {
                    $errors[] = "Cannot delete a booked time slot.";
                }
            } else {
                $errors[] = "Time slot not found or not yours.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error deleting time slot: " . $e->getMessage();
        }
    }
}

// Get the coach's time slots
try {
    // For the upcoming week
    $start_date = new DateTime();
    $end_date = clone $start_date;
    $end_date->modify('+30 days');
    
    $stmt = $pdo->prepare("
        SELECT * FROM CoachTimeSlots
        WHERE coach_id = ?
        AND start_time > NOW()
        AND start_time < ?
        ORDER BY start_time ASC
    ");
    $stmt->execute([
        $coach['coach_id'],
        $end_date->format('Y-m-d H:i:s')
    ]);
    
    $time_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group slots by date for displaying
    $grouped_slots = [];
    foreach ($time_slots as $slot) {
        $date = date('Y-m-d', strtotime($slot['start_time']));
        if (!isset($grouped_slots[$date])) {
            $grouped_slots[$date] = [];
        }
        $grouped_slots[$date][] = $slot;
    }
    
    // Sort dates
    ksort($grouped_slots);
} catch (PDOException $e) {
    $errors[] = "Error retrieving time slots: " . $e->getMessage();
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
                    <a href="edit-coach-availability.php" class="list-group-item list-group-item-action active">
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
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Manage Availability</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($message) ?>
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
                    
                    <p class="mb-4">Set your availability for coaching sessions by creating time slots that learners can book.</p>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0">Add Availability Slots</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" id="addSlotsForm">
                                <input type="hidden" name="action" value="add_slots">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="date_from" class="form-label">Date Range (Start)</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_to" class="form-label">Date Range (End)</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="time_from" class="form-label">Time Range (Start)</label>
                                        <input type="time" class="form-control" id="time_from" name="time_from" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="time_to" class="form-label">Time Range (End)</label>
                                        <input type="time" class="form-control" id="time_to" name="time_to" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="slot_duration" class="form-label">Slot Duration (minutes)</label>
                                    <select class="form-select" id="slot_duration" name="slot_duration" required>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60" selected>60 minutes (1 hour)</option>
                                        <option value="90">90 minutes (1.5 hours)</option>
                                        <option value="120">120 minutes (2 hours)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label d-block">Days of the Week</label>
                                    <div class="row">
                                        <?php
                                        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                        foreach ($weekdays as $day) {
                                            echo '<div class="col-auto">';
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="checkbox" name="days[]" value="' . $day . '" id="day_' . $day . '">';
                                            echo '<label class="form-check-label" for="day_' . $day . '">' . ucfirst($day) . '</label>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Time Slots</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0">Your Upcoming Availability</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($grouped_slots)): ?>
                                <p class="text-muted">You have no upcoming available time slots. Use the form above to add some.</p>
                            <?php else: ?>
                                <div class="accordion" id="availabilityAccordion">
                                    <?php $index = 0; foreach ($grouped_slots as $date => $slots): $index++; ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                                <button class="accordion-button <?= $index > 1 ? 'collapsed' : '' ?>" type="button" 
                                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" 
                                                        aria-expanded="<?= $index === 1 ? 'true' : 'false' ?>">
                                                    <?= date('l, F j, Y', strtotime($date)) ?> 
                                                    <span class="badge bg-primary ms-2"><?= count($slots) ?> slot<?= count($slots) !== 1 ? 's' : '' ?></span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?= $index ?>" 
                                                 class="accordion-collapse collapse <?= $index === 1 ? 'show' : '' ?>" 
                                                 aria-labelledby="heading<?= $index ?>">
                                                <div class="accordion-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Time</th>
                                                                    <th>Duration</th>
                                                                    <th>Status</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($slots as $slot): ?>
                                                                    <tr>
                                                                        <td>
                                                                            <?= date('g:i A', strtotime($slot['start_time'])) ?> - 
                                                                            <?= date('g:i A', strtotime($slot['end_time'])) ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            $start = new DateTime($slot['start_time']);
                                                                            $end = new DateTime($slot['end_time']);
                                                                            $interval = $start->diff($end);
                                                                            $minutes = $interval->h * 60 + $interval->i;
                                                                            echo $minutes . ' minutes';
                                                                            ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($slot['status'] === 'available'): ?>
                                                                                <span class="badge bg-success">Available</span>
                                                                            <?php elseif ($slot['status'] === 'booked'): ?>
                                                                                <span class="badge bg-danger">Booked</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-warning">Unavailable</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($slot['status'] === 'available'): ?>
                                                                                <form method="post" action="" class="d-inline">
                                                                                    <input type="hidden" name="action" value="delete_slot">
                                                                                    <input type="hidden" name="slot_id" value="<?= $slot['slot_id'] ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                                            onclick="return confirm('Are you sure you want to delete this time slot?')">
                                                                                        <i class="bi bi-trash"></i> Remove
                                                                                    </button>
                                                                                </form>
                                                                            <?php elseif ($slot['status'] === 'booked'): ?>
                                                                                <button class="btn btn-sm btn-secondary" disabled>
                                                                                    <i class="bi bi-lock"></i> Booked
                                                                                </button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date range validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
        if (dateTo.value && dateTo.value < this.value) {
            dateTo.value = this.value;
        }
    });
    
    // Time range validation
    const timeFrom = document.getElementById('time_from');
    const timeTo = document.getElementById('time_to');
    
    timeTo.addEventListener('change', function() {
        if (timeFrom.value && this.value <= timeFrom.value) {
            alert('End time must be after start time');
            this.value = '';
        }
    });
    
    timeFrom.addEventListener('change', function() {
        if (timeTo.value && timeTo.value <= this.value) {
            timeTo.value = '';
        }
    });
    
    // Form validation
    document.getElementById('addSlotsForm').addEventListener('submit', function(e) {
        const days = document.querySelectorAll('input[name="days[]"]:checked');
        if (days.length === 0) {
            e.preventDefault();
            alert('Please select at least one day of the week');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 