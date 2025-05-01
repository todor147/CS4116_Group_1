<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/notification_functions.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$selected_coach_id = $_GET['coach_id'] ?? null;
$selected_tier_id = $_GET['tier_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Handle calendar view parameters
$view_month = isset($_GET['view_month']) ? intval($_GET['view_month']) : null;
$view_year = isset($_GET['view_year']) ? intval($_GET['view_year']) : null;

// Set calendar display variables
if ($view_month !== null && $view_year !== null) {
    // Use the view parameters for calendar display
    $calendar_month = $view_month;
    $calendar_year = $view_year;
} else {
    // Use the selected date for calendar display
    $date_parts = explode('-', $selected_date);
    $calendar_year = intval($date_parts[0]);
    $calendar_month = intval($date_parts[1]) - 1; // Convert to 0-based month for JavaScript
}

$coach_details = null;
$service_details = null;
$available_time_slots = [];

try {
    // Validate and get coach details
    if ($selected_coach_id) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username as coach_name, u.email, u.bio
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

    // Get service tiers for this coach
    if ($selected_coach_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM ServiceTiers 
            WHERE coach_id = ?
            ORDER BY price ASC
        ");
        $stmt->execute([$selected_coach_id]);
        $service_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If a tier ID is selected, get its details
        if ($selected_tier_id) {
            foreach ($service_tiers as $tier) {
                if ($tier['tier_id'] == $selected_tier_id) {
                    $service_details = $tier;
                    break;
                }
            }
        
        if (!$service_details) {
                $error = 'Invalid service tier selected';
            $selected_tier_id = null;
            }
        }
    }

    // Get available time slots for the selected date
    if ($selected_coach_id && $selected_date) {
        $date_start = $selected_date . ' 00:00:00';
        $date_end = $selected_date . ' 23:59:59';
        
        $stmt = $pdo->prepare("
            SELECT * FROM CoachTimeSlots 
            WHERE coach_id = ?
            AND start_time BETWEEN ? AND ?
            AND status = 'available'
            ORDER BY start_time ASC
        ");
        $stmt->execute([$selected_coach_id, $date_start, $date_end]);
        $available_time_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug - Log query and results
        error_log("Time slots query for coach $selected_coach_id on date $selected_date");
        error_log("Found " . count($available_time_slots) . " available slots");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $slot_id = $_POST['slot_id'] ?? null;
        $coach_id = $_POST['coach_id'] ?? null;
        $tier_id = $_POST['tier_id'] ?? null;
        $learner_id = $_SESSION['user_id'];

        // Basic validation
        if (!$slot_id || !$coach_id || !$tier_id) {
            $error = 'Please select a time slot, coach, and service tier';
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Get slot details
            $stmt = $pdo->prepare("
                SELECT * FROM CoachTimeSlots
                WHERE slot_id = ? AND coach_id = ? AND status = 'available'
                FOR UPDATE
            ");
            $stmt->execute([$slot_id, $coach_id]);
            $slot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$slot) {
                throw new Exception('The selected time slot is no longer available');
            }
            
            // Update the slot to booked
            $stmt = $pdo->prepare("
                UPDATE CoachTimeSlots
                SET status = 'booked'
                WHERE slot_id = ?
            ");
            $stmt->execute([$slot_id]);
            
            // Insert the session
            $stmt = $pdo->prepare("
                INSERT INTO Sessions 
                (learner_id, coach_id, tier_id, scheduled_time, status, created_at)
                VALUES (?, ?, ?, ?, 'scheduled', NOW())
            ");
            $stmt->execute([
                $learner_id,
                $coach_id,
                $tier_id,
                $slot['start_time']
            ]);
            
            $session_id = $pdo->lastInsertId();
            
            // Commit transaction
            $pdo->commit();
            
            // Get coach and learner information for notifications
            $stmt = $pdo->prepare("
                SELECT c.coach_id, u_coach.username as coach_name, u_learner.username as learner_name, 
                       t.name as tier_name
                FROM Coaches c
                JOIN Users u_coach ON c.user_id = u_coach.user_id
                JOIN Users u_learner ON u_learner.user_id = ?
                JOIN ServiceTiers t ON t.tier_id = ?
                WHERE c.coach_id = ?
            ");
            $stmt->execute([$learner_id, $tier_id, $coach_id]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking_info) {
                // Create formatted date/time for notifications
                $session_date = date('l, F j, Y', strtotime($slot['start_time']));
                $session_time = date('g:i A', strtotime($slot['start_time']));
                
                // Notify the learner
                $title = "Session Booked";
                $message = "Your session with {$booking_info['coach_name']} ({$booking_info['tier_name']}) is scheduled for {$session_date} at {$session_time}";
                $link = "view-session.php?id={$session_id}";
                createNotification($pdo, $learner_id, $title, $message, $link, 'session');
                
                // Notify the coach
                $coach_user_id = null;
                $stmt = $pdo->prepare("SELECT user_id FROM Coaches WHERE coach_id = ?");
                $stmt->execute([$coach_id]);
                $coach = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($coach) {
                    $coach_user_id = $coach['user_id'];
                    $title = "New Session Booking";
                    $message = "{$booking_info['learner_name']} has booked a {$booking_info['tier_name']} session with you for {$session_date} at {$session_time}";
                    $link = "view-session.php?id={$session_id}";
                    createNotification($pdo, $coach_user_id, $title, $message, $link, 'session');
                }
            }
            
            $success = 'Session booked successfully!';
            
            // Redirect to the session details page after a successful booking
            header("Location: view-session.php?id=$session_id&success=1");
            exit;
        }
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error = 'Error: ' . $e->getMessage();
}

// Get available dates with slots
$available_dates = [];
if ($selected_coach_id) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(start_time) as date
        FROM CoachTimeSlots
        WHERE coach_id = ?
        AND status = 'available'
        AND start_time > NOW()
        ORDER BY date ASC
        LIMIT 30
    ");
    $stmt->execute([$selected_coach_id]);
    $available_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Left Column: Booking Form -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="card-title mb-0"><i class="bi bi-calendar-check me-2"></i>Book a Session</h4>
                </div>
                <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

                    <?php if ($coach_details): ?>
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <?php 
                                $profile_image = '/assets/images/profiles/default.jpg';
                                if (!empty($coach_details['profile_image'])) {
                                    $image_path = "/assets/images/profiles/" . $coach_details['profile_image'];
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
                                        $profile_image = $image_path;
                                    }
                                }
                                ?>
                                <img src="<?= $profile_image ?>" alt="<?= htmlspecialchars($coach_details['coach_name']) ?>" 
                                     class="rounded-circle me-3" style="width: 64px; height: 64px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($coach_details['coach_name']) ?></h5>
                                    <div class="text-warning mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= floor($coach_details['rating'])): ?>
                                                <i class="bi bi-star-fill"></i>
                                            <?php elseif ($i - 0.5 == $coach_details['rating']): ?>
                                                <i class="bi bi-star-half"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="text-muted ms-1"><?= number_format($coach_details['rating'], 1) ?></span>
                                    </div>
                    </div>
                            </div>
                            <p class="text-muted"><?= htmlspecialchars(substr($coach_details['bio'] ?? '', 0, 150)) ?><?= strlen($coach_details['bio'] ?? '') > 150 ? '...' : '' ?></p>
                </div>

                        <form method="POST" id="bookingForm">
                    <input type="hidden" name="coach_id" value="<?= htmlspecialchars($selected_coach_id) ?>">
                            
                            <!-- Service Tier Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Service Tier</label>
                                <?php if (empty($service_tiers)): ?>
                                    <p class="text-danger">This coach has no service tiers available.</p>
                                <?php else: ?>
                                    <div class="service-tiers-wrapper">
                                        <?php foreach ($service_tiers as $tier): ?>
                                            <div class="service-tier-card mb-3">
                                                <input type="radio" class="service-tier-input" name="tier_id" 
                                                       id="tier_<?= $tier['tier_id'] ?>" value="<?= $tier['tier_id'] ?>"
                                                       <?= ($selected_tier_id == $tier['tier_id']) ? 'checked' : '' ?>>
                                                <label class="service-tier-card-label" for="tier_<?= $tier['tier_id'] ?>">
                                                    <div class="tier-card-content">
                                                        <div class="tier-header">
                                                            <h5 class="tier-title"><?= htmlspecialchars($tier['name']) ?></h5>
                                                            <div class="tier-price">$<?= number_format($tier['price'], 2) ?></div>
                                                        </div>
                                                        <div class="tier-description">
                                                            <?= htmlspecialchars($tier['description']) ?>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Date Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Date</label>
                                <div class="input-group mb-3">
                                    <input type="date" class="form-control" id="date-picker" 
                                           value="<?= $selected_date ?>"
                                           min="<?= date('Y-m-d') ?>">
                                    <button type="button" class="btn btn-primary" id="load-date-btn">
                                        <i class="bi bi-calendar-date me-1"></i> Load Times
                                    </button>
                                </div>
                                
                                <?php if (!empty($available_dates)): ?>
                                    <label class="form-label">Quick Select:</label>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <?php 
                                        // Show next 5 dates or all if less than 5
                                        $dates_to_show = array_slice($available_dates, 0, 5);
                                        foreach ($dates_to_show as $date): 
                                            $formatted_date = date('M j', strtotime($date));
                                            $day_of_week = date('D', strtotime($date));
                                            $is_selected = ($date == $selected_date);
                                            $is_today = ($date == date('Y-m-d'));
                                        ?>
                                            <a href="?coach_id=<?= $selected_coach_id ?>&tier_id=<?= $selected_tier_id ?>&date=<?= $date ?>" 
                                               class="btn <?= $is_selected ? 'btn-primary' : 'btn-outline-primary' ?> <?= $is_today ? 'border-2' : '' ?>">
                                                <small class="d-block"><?= $day_of_week ?></small>
                                                <strong><?= $formatted_date ?></strong>
                                                <?php if ($is_today): ?><span class="badge bg-info text-white">Today</span><?php endif; ?>
                                            </a>
                            <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        No available dates found. Try selecting a date from the picker above.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Time Slot Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Time Slot</label>
                                <?php if (empty($available_time_slots)): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No available time slots for the selected date. Please select another date.
                                    </div>
                                <?php else: ?>
                                    <div class="time-slots-grid">
                                        <?php foreach ($available_time_slots as $slot): ?>
                                            <input type="radio" class="btn-check" name="slot_id" 
                                                   id="slot_<?= $slot['slot_id'] ?>" value="<?= $slot['slot_id'] ?>" required>
                                            <label class="btn btn-outline-primary time-slot-btn" for="slot_<?= $slot['slot_id'] ?>">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= date('g:i A', strtotime($slot['start_time'])) ?> - 
                                                <?= date('g:i A', strtotime($slot['end_time'])) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="timeSlotError" class="text-danger mt-2 d-none">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Please select a time slot
                            </div>
                        <?php endif; ?>
                    </div>

                            <div class="d-grid">
                                <button type="submit" id="book-submit-btn" class="btn btn-primary btn-lg" 
                                        <?= (empty($service_tiers) || empty($available_time_slots)) ? 'disabled' : '' ?>>
                                    <i class="bi bi-calendar-check me-2"></i> Book Session
                                </button>
                            </div>
                </form>
            <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Please select a coach to book a session.
                        </div>
                        <a href="coach-search.php" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Find a Coach
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Calendar View -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="card-title mb-0"><i class="bi bi-calendar3 me-2"></i>Coach's Calendar</h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Select a date from the calendar below to view available time slots. 
                        Times shown are in your local timezone.
                    </div>
                    
                    <h5 class="mb-3 date-heading">
                        <?= ($selected_date) ? date('l, F j, Y', strtotime($selected_date)) : 'Select a date' ?>
                    </h5>
                    
                    <!-- Custom mini calendar will be rendered here -->
                    <div id="mini-calendar" class="mb-4"></div>
                    
                    <?php if (!empty($available_time_slots)): ?>
                        <h5 class="mb-3">
                            <i class="bi bi-clock me-2"></i>
                            Available Times for <?= date('F j, Y', strtotime($selected_date)) ?>
                        </h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($available_time_slots as $slot): ?>
                                <button type="button" class="btn btn-outline-primary visual-time-slot" 
                                        data-slot-id="<?= $slot['slot_id'] ?>">
                                    <?= date('g:i A', strtotime($slot['start_time'])) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 small text-muted">
                            Click on a time to select it for booking. All sessions last for 
                            <?= !empty($selected_tier_id) && isset($service_details['name']) ? 
                                   htmlspecialchars($service_details['name']) : 'the duration specified in the service tier' ?>.
                        </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.time-slot-btn {
    width: 100%;
    text-align: center;
    transition: all 0.2s ease;
}

.time-slot-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.time-slot-btn.active {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.date-heading {
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.visual-time-slot {
    transition: all 0.2s ease;
}

.visual-time-slot:hover {
    transform: translateY(-2px);
}

.visual-time-slot.active {
    background-color: var(--bs-primary);
    color: white;
}

/* Calendar styling */
.calendar-grid {
    border-top: 1px solid #dee2e6;
    border-left: 1px solid #dee2e6;
    width: 100%;
}

.week-row {
    border-bottom: 1px solid #dee2e6;
    display: flex;
}

.calendar-day {
    border-right: 1px solid #dee2e6;
    min-height: 40px;
    position: relative;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}

.calendar-day.today {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.calendar-day.available .date-link {
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    color: var(--bs-primary);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-day.available .date-link:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.15);
}

.calendar-day.selected {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.calendar-day.empty {
    background-color: #f8f9fa;
}

.calendar-day .has-slots {
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background-color: var(--bs-primary);
}

/* Completely new service tier styling */
.service-tiers-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-tier-card {
    position: relative;
    margin: 0;
}

.service-tier-input {
    position: absolute;
    opacity: 0;
    z-index: -1;
}

.service-tier-card-label {
    display: block;
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 0;
    margin: 0;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.2s ease;
}

.tier-card-content {
    position: relative;
    padding: 1.25rem 1.25rem 1.25rem 3.5rem;
}

.tier-card-content:before {
    content: '';
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    background-color: #fff;
    transition: all 0.2s ease;
}

.service-tier-input:checked + .service-tier-card-label {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 1px var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.service-tier-input:checked + .service-tier-card-label .tier-card-content:before {
    border-color: var(--bs-primary);
    background-color: var(--bs-primary);
    box-shadow: inset 0 0 0 0.25rem #fff;
}

.service-tier-card-label:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.05);
    transform: translateY(-2px);
}

.tier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.tier-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
    color: #212529;
}

.tier-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.tier-description {
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing booking page scripts');
    
    // Handle direct date selection
    const datePicker = document.getElementById('date-picker');
    const loadDateBtn = document.getElementById('load-date-btn');
    
    if (loadDateBtn) {
        loadDateBtn.addEventListener('click', function() {
            if (datePicker && datePicker.value) {
                console.log('Loading times for date:', datePicker.value);
                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const coachId = urlParams.get('coach_id');
                const tierId = urlParams.get('tier_id');
                
                // Update URL with selected date
                window.location.href = `?coach_id=${coachId}&tier_id=${tierId}&date=${datePicker.value}`;
            }
        });
    }
    
    // Time slot selection
    const timeSlotBtns = document.querySelectorAll('.time-slot-btn');
    console.log('Found time slot buttons:', timeSlotBtns.length);
    
    timeSlotBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update visual feedback
            timeSlotBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update the visual time slots too
            const slotId = this.getAttribute('for').replace('slot_', '');
            updateVisualTimeSlots(slotId);
            
            // Hide error message if shown
            const errorElement = document.getElementById('timeSlotError');
            if (errorElement) {
                errorElement.classList.add('d-none');
            }
        });
    });
    
    // Update visual time slots
    function updateVisualTimeSlots(slotId) {
        console.log('Updating visual time slots:', slotId);
        const visualTimeSlots = document.querySelectorAll('.visual-time-slot');
        visualTimeSlots.forEach(slot => {
            slot.classList.remove('active');
            if (slot.getAttribute('data-slot-id') === slotId) {
                slot.classList.add('active');
            }
        });
    }
    
    // Visual time slot selection (from calendar view)
    const visualTimeSlots = document.querySelectorAll('.visual-time-slot');
    visualTimeSlots.forEach(slot => {
        slot.addEventListener('click', function() {
            const slotId = this.getAttribute('data-slot-id');
            if (!slotId) return;
            
            // Update visual slots
            visualTimeSlots.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            
            // Find and check the corresponding radio button
            const radioInput = document.getElementById('slot_' + slotId);
            if (radioInput) {
                radioInput.checked = true;
                
                // Update form time slot buttons
                timeSlotBtns.forEach(btn => btn.classList.remove('active'));
                const label = document.querySelector(`label[for="slot_${slotId}"]`);
                if (label) {
                    label.classList.add('active');
                }
            }
        });
    });
    
    // Form validation
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const selectedSlot = document.querySelector('input[name="slot_id"]:checked');
            if (!selectedSlot) {
                e.preventDefault();
                const errorElement = document.getElementById('timeSlotError');
                if (errorElement) {
                    errorElement.classList.remove('d-none');
                }
                return false;
            }
            return true;
        });
    }
    
    // Initialize mini calendar
    initializeCalendar();
    
    function initializeCalendar() {
        const miniCalendarDiv = document.getElementById('mini-calendar');
        if (!miniCalendarDiv) return;
        
        // Get available dates for highlighting
        const availableDates = <?= json_encode($available_dates ?? []) ?>;
        
        // Current date and selected date
        const currentDate = new Date();
        const selectedDate = '<?= $selected_date ?>';
        
        // Use PHP-provided calendar month/year if available
        const calendarMonth = <?= isset($calendar_month) ? $calendar_month : 'null' ?>;
        const calendarYear = <?= isset($calendar_year) ? $calendar_year : 'null' ?>;
        
        // Create calendar HTML
        let monthYear;
        if (calendarMonth !== null && calendarYear !== null) {
            monthYear = new Date(calendarYear, calendarMonth, 1);
        } else {
            monthYear = new Date(selectedDate || currentDate);
        }
        
        const month = monthYear.getMonth();
        const year = monthYear.getFullYear();
        
        // Calendar header
        let calendarHtml = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="prev-month">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <h5 class="mb-0">${monthYear.toLocaleString('default', { month: 'long' })} ${year}</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="next-month">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        `;
        
        // Days of week header
        calendarHtml += '<div class="d-flex mb-2">';
        ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(day => {
            calendarHtml += `<div class="flex-fill text-center fw-bold">${day}</div>`;
        });
        calendarHtml += '</div>';
        
        // Calendar grid
        calendarHtml += '<div class="calendar-grid">';
        
        // Get URL parameters for links
        const urlParams = new URLSearchParams(window.location.search);
        const baseUrl = window.location.pathname;
        
        // Get first day of month
        const firstDay = new Date(year, month, 1);
        const startingDay = firstDay.getDay();
        
        // Get number of days in month
        const lastDay = new Date(year, month + 1, 0);
        const monthLength = lastDay.getDate();
        
        // Calculate rows needed (don't show extra rows)
        const rows = Math.ceil((startingDay + monthLength) / 7);
        
        // Generate calendar cells
        let date = 1;
        for (let i = 0; i < rows; i++) {
            calendarHtml += '<div class="d-flex week-row">';
            
            // Create cells for each day of the week
            for (let j = 0; j < 7; j++) {
                if ((i === 0 && j < startingDay) || date > monthLength) {
                    // Empty cell
                    calendarHtml += '<div class="flex-fill p-1 text-center calendar-day empty"></div>';
                } else {
                    // Format date for comparison
                    const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                    const isToday = currentDate.getDate() === date && 
                                    currentDate.getMonth() === month && 
                                    currentDate.getFullYear() === year;
                    const isSelected = formattedDate === selectedDate;
                    const isAvailable = availableDates.includes(formattedDate);
                    
                    // Create day cell with appropriate classes
                    let cellClasses = 'flex-fill p-1 text-center calendar-day';
                    if (isToday) cellClasses += ' today';
                    if (isSelected) cellClasses += ' selected';
                    if (isAvailable) cellClasses += ' available';
                    
                    // Create link if day is available
                    if (isAvailable) {
                        // Create simple URL for date selection
                        const dateUrl = `${baseUrl}?coach_id=${urlParams.get('coach_id')}&tier_id=${urlParams.get('tier_id')}&date=${formattedDate}`;
                        
                        calendarHtml += `
                            <div class="${cellClasses}">
                                <a href="${dateUrl}" 
                                   class="d-block px-2 py-1 rounded date-link ${isSelected ? 'bg-primary text-white' : ''}"
                                   data-date="${formattedDate}">
                                    ${date}
                                </a>
                                <div class="has-slots"></div>
                            </div>
                        `;
                    } else {
                        calendarHtml += `
                            <div class="${cellClasses}">
                                <span class="d-block px-2 py-1 rounded text-muted">${date}</span>
                            </div>
                        `;
                    }
                    
                    date++;
                }
            }
            
            calendarHtml += '</div>';
        }
        
        calendarHtml += '</div>';
        
        // Add to page
        miniCalendarDiv.innerHTML = calendarHtml;
        
        // Add month navigation
        document.getElementById('prev-month').addEventListener('click', function() {
            // Get existing URL parameters
            const baseUrl = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            const coachId = urlParams.get('coach_id');
            const tierId = urlParams.get('tier_id');
            
            // Calculate previous month
            let prevMonth = month - 1;
            let prevYear = year;
            if (prevMonth < 0) {
                prevMonth = 11;
                prevYear--;
            }
            
            // Create new URL with updated parameters
            const newUrl = `${baseUrl}?coach_id=${coachId}&tier_id=${tierId}&view_month=${prevMonth}&view_year=${prevYear}`;
            window.location.href = newUrl;
        });
        
        document.getElementById('next-month').addEventListener('click', function() {
            // Get existing URL parameters
            const baseUrl = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            const coachId = urlParams.get('coach_id');
            const tierId = urlParams.get('tier_id');
            
            // Calculate next month
            let nextMonth = month + 1;
            let nextYear = year;
            if (nextMonth > 11) {
                nextMonth = 0;
                nextYear++;
            }
            
            // Create new URL with updated parameters
            const newUrl = `${baseUrl}?coach_id=${coachId}&tier_id=${tierId}&view_month=${nextMonth}&view_year=${nextYear}`;
            window.location.href = newUrl;
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 