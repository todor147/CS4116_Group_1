<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
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

// Get coach's current availability
try {
    $stmt = $pdo->prepare("
        SELECT * FROM Coach_Availability 
        WHERE coach_id = ? 
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
    ");
    $stmt->execute([$coach['coach_id']]);
    $availabilityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by day of week
    $availability = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    foreach ($days as $day) {
        $availability[$day] = [
            'is_available' => false,
            'slots' => []
        ];
    }
    
    foreach ($availabilityRecords as $record) {
        $day = $record['day_of_week'];
        $availability[$day]['is_available'] = (bool)$record['is_available'];
        
        if ($record['is_available']) {
            $availability[$day]['slots'][] = [
                'id' => $record['availability_id'],
                'start_time' => $record['start_time'],
                'end_time' => $record['end_time']
            ];
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete all current availability records
        $stmt = $pdo->prepare("DELETE FROM Coach_Availability WHERE coach_id = ?");
        $stmt->execute([$coach['coach_id']]);
        
        // Insert new availability records
        $stmt = $pdo->prepare("
            INSERT INTO Coach_Availability (coach_id, day_of_week, start_time, end_time, is_available)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($days as $day) {
            $isAvailable = isset($_POST['available_days']) && in_array($day, $_POST['available_days']);
            
            if ($isAvailable && isset($_POST['time_slots'][$day]) && is_array($_POST['time_slots'][$day])) {
                foreach ($_POST['time_slots'][$day] as $slot) {
                    if (isset($slot['start']) && isset($slot['end']) && $slot['start'] && $slot['end']) {
                        $stmt->execute([
                            $coach['coach_id'],
                            $day,
                            $slot['start'],
                            $slot['end'],
                            true
                        ]);
                    }
                }
            } else {
                // Insert a placeholder record for unavailable days
                $stmt->execute([
                    $coach['coach_id'],
                    $day,
                    '00:00:00',
                    '00:00:00',
                    false
                ]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Set success flag
        $success = true;
        $_SESSION['success_message'] = "Your availability has been updated successfully.";
        
        // Refresh availability data
        $stmt = $pdo->prepare("
            SELECT * FROM Coach_Availability 
            WHERE coach_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
        ");
        $stmt->execute([$coach['coach_id']]);
        $availabilityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reset availability array
        foreach ($days as $day) {
            $availability[$day] = [
                'is_available' => false,
                'slots' => []
            ];
        }
        
        foreach ($availabilityRecords as $record) {
            $day = $record['day_of_week'];
            $availability[$day]['is_available'] = (bool)$record['is_available'];
            
            if ($record['is_available']) {
                $availability[$day]['slots'][] = [
                    'id' => $record['availability_id'],
                    'start_time' => $record['start_time'],
                    'end_time' => $record['end_time']
                ];
            }
        }
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
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
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Manage Availability</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> Your availability has been updated successfully.
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
                    
                    <p class="mb-4">Set your weekly availability for coaching sessions. You can add multiple time slots for each day.</p>
                    
                    <form method="post" action="" id="availabilityForm">
                        <div class="accordion" id="availabilityAccordion">
                            <?php foreach ($days as $index => $day): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $day ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $day ?>" 
                                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" 
                                                aria-controls="collapse<?= $day ?>">
                                            <div class="form-check form-switch me-2">
                                                <input class="form-check-input day-toggle" type="checkbox" 
                                                       id="available_<?= $day ?>" name="available_days[]" 
                                                       value="<?= $day ?>" <?= $availability[$day]['is_available'] ? 'checked' : '' ?>
                                                       onclick="event.stopPropagation();">
                                                <label class="form-check-label" for="available_<?= $day ?>"></label>
                                            </div>
                                            <?= $day ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $day ?>" 
                                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                         aria-labelledby="heading<?= $day ?>" 
                                         data-bs-parent="#availabilityAccordion">
                                        <div class="accordion-body">
                                            <div class="time-slots" id="slots_<?= $day ?>">
                                                <?php if (!empty($availability[$day]['slots'])): ?>
                                                    <?php foreach ($availability[$day]['slots'] as $index => $slot): ?>
                                                        <div class="row mb-3 time-slot">
                                                            <div class="col-md-5">
                                                                <label class="form-label">Start Time</label>
                                                                <input type="time" class="form-control start-time" 
                                                                       name="time_slots[<?= $day ?>][<?= $index ?>][start]" 
                                                                       value="<?= substr($slot['start_time'], 0, 5) ?>" 
                                                                       required>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label">End Time</label>
                                                                <input type="time" class="form-control end-time" 
                                                                       name="time_slots[<?= $day ?>][<?= $index ?>][end]" 
                                                                       value="<?= substr($slot['end_time'], 0, 5) ?>" 
                                                                       required>
                                                            </div>
                                                            <div class="col-md-2 d-flex align-items-end">
                                                                <button type="button" class="btn btn-outline-danger remove-slot mb-1">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="row mb-3 time-slot">
                                                        <div class="col-md-5">
                                                            <label class="form-label">Start Time</label>
                                                            <input type="time" class="form-control start-time" 
                                                                   name="time_slots[<?= $day ?>][0][start]" 
                                                                   value="09:00" required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label">End Time</label>
                                                            <input type="time" class="form-control end-time" 
                                                                   name="time_slots[<?= $day ?>][0][end]" 
                                                                   value="17:00" required>
                                                        </div>
                                                        <div class="col-md-2 d-flex align-items-end">
                                                            <button type="button" class="btn btn-outline-danger remove-slot mb-1">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary add-slot" data-day="<?= $day ?>">
                                                <i class="bi bi-plus-circle"></i> Add Time Slot
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Availability
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add time slot
    document.querySelectorAll('.add-slot').forEach(button => {
        button.addEventListener('click', function() {
            const day = this.getAttribute('data-day');
            const slotsContainer = document.getElementById('slots_' + day);
            const slotCount = slotsContainer.querySelectorAll('.time-slot').length;
            
            const newSlot = document.createElement('div');
            newSlot.className = 'row mb-3 time-slot';
            newSlot.innerHTML = `
                <div class="col-md-5">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-control start-time" 
                           name="time_slots[${day}][${slotCount}][start]" 
                           value="09:00" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">End Time</label>
                    <input type="time" class="form-control end-time" 
                           name="time_slots[${day}][${slotCount}][end]" 
                           value="17:00" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger remove-slot mb-1">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            
            slotsContainer.appendChild(newSlot);
            
            // Add event listener to the new remove button
            newSlot.querySelector('.remove-slot').addEventListener('click', removeSlot);
        });
    });
    
    // Remove time slot
    function removeSlot() {
        const slot = this.closest('.time-slot');
        const slotsContainer = slot.parentElement;
        
        // Don't remove if it's the only slot
        if (slotsContainer.querySelectorAll('.time-slot').length > 1) {
            slot.remove();
            
            // Reindex the remaining slots
            const day = slotsContainer.id.replace('slots_', '');
            const slots = slotsContainer.querySelectorAll('.time-slot');
            
            slots.forEach((slot, index) => {
                slot.querySelector('.start-time').name = `time_slots[${day}][${index}][start]`;
                slot.querySelector('.end-time').name = `time_slots[${day}][${index}][end]`;
            });
        }
    }
    
    // Add event listeners to existing remove buttons
    document.querySelectorAll('.remove-slot').forEach(button => {
        button.addEventListener('click', removeSlot);
    });
    
    // Toggle day availability
    document.querySelectorAll('.day-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const day = this.id.replace('available_', '');
            const slotsContainer = document.getElementById('slots_' + day);
            const inputs = slotsContainer.querySelectorAll('input');
            
            if (this.checked) {
                inputs.forEach(input => input.removeAttribute('disabled'));
                slotsContainer.classList.remove('disabled-slots');
                document.querySelector(`#collapse${day} .add-slot`).disabled = false;
            } else {
                inputs.forEach(input => input.setAttribute('disabled', 'disabled'));
                slotsContainer.classList.add('disabled-slots');
                document.querySelector(`#collapse${day} .add-slot`).disabled = true;
            }
        });
        
        // Initial state
        checkbox.dispatchEvent(new Event('change'));
    });
    
    // Form validation
    document.getElementById('availabilityForm').addEventListener('submit', function(event) {
        let valid = false;
        
        document.querySelectorAll('.day-toggle').forEach(checkbox => {
            if (checkbox.checked) {
                valid = true;
            }
        });
        
        if (!valid) {
            event.preventDefault();
            alert('Please select at least one day you are available.');
        }
    });
});
</script>

<style>
.disabled-slots {
    opacity: 0.5;
}
</style>

<?php include '../includes/footer.php'; ?> 