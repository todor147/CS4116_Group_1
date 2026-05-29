<?php
// Boots config (env, secure session, helpers) and provides $pdo.
require __DIR__ . '/../includes/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;
$is_coach = false;
$upcoming_sessions = [];
$next_session = null;

if ($user_id) {
    $is_coach = ($_SESSION['user_type'] ?? '') === 'business';
    try {
        if ($is_coach) {
            $stmt = $pdo->prepare("
                SELECT s.session_id, s.scheduled_time, u.username AS other_name, st.name AS tier_name
                FROM Sessions s
                JOIN Users u ON s.learner_id = u.user_id
                LEFT JOIN ServiceTiers st ON s.tier_id = st.tier_id
                WHERE s.coach_id = (SELECT coach_id FROM Coaches WHERE user_id = ?)
                  AND s.status = 'scheduled' AND s.scheduled_time > NOW()
                ORDER BY s.scheduled_time ASC LIMIT 3");
        } else {
            $stmt = $pdo->prepare("
                SELECT s.session_id, s.scheduled_time, u.username AS other_name, st.name AS tier_name
                FROM Sessions s
                JOIN Coaches c ON s.coach_id = c.coach_id
                JOIN Users u ON c.user_id = u.user_id
                LEFT JOIN ServiceTiers st ON s.tier_id = st.tier_id
                WHERE s.learner_id = ? AND s.status = 'scheduled' AND s.scheduled_time > NOW()
                ORDER BY s.scheduled_time ASC LIMIT 3");
        }
        $stmt->execute([$user_id]);
        $upcoming_sessions = $stmt->fetchAll();
        if ($upcoming_sessions) {
            $next_session = array_shift($upcoming_sessions);
        }
    } catch (PDOException $e) {
        error_log('Home: upcoming sessions query failed: ' . $e->getMessage());
    }
}

// Popular categories.
try {
    $stmt = $pdo->query("
        SELECT c.category_id, c.name, COUNT(cc.coach_id) AS coach_count
        FROM Categories c
        LEFT JOIN CoachCategories cc ON c.category_id = cc.category_id
        GROUP BY c.category_id, c.name
        ORDER BY coach_count DESC, c.name ASC LIMIT 12");
    $popular_categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $popular_categories = [];
}

// Featured coaches.
try {
    $stmt = $pdo->query("
        SELECT c.coach_id, c.headline, c.rating, u.username, u.profile_image
        FROM Coaches c
        JOIN Users u ON c.user_id = u.user_id
        WHERE u.is_banned = 0
        ORDER BY c.rating DESC, c.coach_id ASC LIMIT 4");
    $featured_coaches = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_coaches = [];
}

// Recent positive testimonials.
try {
    $stmt = $pdo->query("
        SELECT r.rating, r.comment, ul.username AS learner_name, uc.username AS coach_name
        FROM Reviews r
        JOIN Users ul ON r.user_id = ul.user_id
        JOIN Coaches c ON r.coach_id = c.coach_id
        JOIN Users uc ON c.user_id = uc.user_id
        WHERE r.rating >= 4 AND r.comment IS NOT NULL AND r.comment <> ''
        ORDER BY r.created_at DESC LIMIT 3");
    $testimonials = $stmt->fetchAll();
} catch (PDOException $e) {
    $testimonials = [];
}

/** Resolve a usable avatar URL with a friendly fallback. */
function avatar_url(?string $file, string $name): string
{
    if ($file) {
        $rel = 'assets/images/profiles/' . $file;
        if (is_file(APP_ROOT . '/' . $rel)) {
            return asset($rel);
        }
    }
    return 'https://ui-avatars.com/api/?background=4f46e5&color=fff&size=128&name=' . urlencode($name);
}

$page_title = 'EduCoach — find your perfect coach';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($next_session): ?>
    <?php
        $session_time = new DateTime($next_session['scheduled_time']);
        $hours_until = (new DateTime())->diff($session_time);
        $total_hours = ($hours_until->days * 24) + $hours_until->h;
        $alert = $total_hours < 1 ? 'alert-danger' : ($total_hours < 24 ? 'alert-warning' : 'alert-primary');
    ?>
    <div class="<?= $alert ?> mb-0 py-2 border-0 rounded-0">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <i class="bi bi-alarm me-1"></i>
                <strong>Next session</strong> with <?= e($next_session['other_name']) ?>
                <?php if (!empty($next_session['tier_name'])): ?>(<?= e($next_session['tier_name']) ?>)<?php endif; ?>
                — <?= e($session_time->format('D, M j')) ?> at <?= e($session_time->format('g:i A')) ?>
                <?php if ($total_hours < 24): ?>
                    <span class="badge bg-dark ms-1"><?= $total_hours < 1 ? 'Starting soon' : 'In ' . $total_hours . 'h' ?></span>
                <?php endif; ?>
            </div>
            <a href="view-session.php?id=<?= (int) $next_session['session_id'] ?>" class="btn btn-sm btn-light">View details</a>
        </div>
    </div>
<?php endif; ?>

<!-- Hero -->
<section class="hero section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="hero-eyebrow mb-3"><i class="bi bi-stars"></i> 1,000+ expert coaches</span>
                <h1 class="hero-title mb-3">Learn anything, from<br class="d-none d-md-block"> the <span class="text-gradient">perfect coach</span></h1>
                <p class="lead text-muted mb-4 mx-auto" style="max-width:42rem">
                    Maths, languages, music, coding and more — find a tutor who fits your goals,
                    your level and your schedule. Online or in person.
                </p>

                <form action="coach-search.php" method="get" class="hero-search mx-auto" style="max-width:42rem">
                    <div class="input-group input-group-lg flex-nowrap">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control" name="query" placeholder="Try “GCSE Maths”, “Spanish”, “guitar”…" aria-label="What do you want to learn?">
                        <button class="btn btn-primary px-4" type="submit">Search</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4 small text-muted">
                    <span><i class="bi bi-shield-check text-primary me-1"></i> Verified reviews</span>
                    <span><i class="bi bi-calendar2-check text-primary me-1"></i> Flexible scheduling</span>
                    <span><i class="bi bi-chat-heart text-primary me-1"></i> Message before you book</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($user_id && $upcoming_sessions): ?>
<section class="section-sm">
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0"><i class="bi bi-calendar-check text-primary me-2"></i>Your upcoming sessions</h2>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($upcoming_sessions as $s): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-semibold"><?= e($s['other_name']) ?></p>
                            <p class="mb-0 small text-muted"><?= e($s['tier_name'] ?? 'Session') ?></p>
                        </div>
                        <span class="badge bg-soft"><?= e(date('D, M j · g:i A', strtotime($s['scheduled_time']))) ?></span>
                        <a href="view-session.php?id=<?= (int) $s['session_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Popular subjects -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Explore</p>
            <h2 class="mb-2">Popular subjects</h2>
            <p class="text-muted mb-0">Browse the topics learners love most.</p>
        </div>
        <div class="row g-3 g-md-4 justify-content-center">
            <?php
            $icons = ['bi-calculator','bi-translate','bi-flask','bi-palette','bi-code-slash','bi-mortarboard','bi-briefcase','bi-music-note-beamed','bi-book','bi-globe','bi-clipboard-data','bi-pencil'];
            $tiles = $popular_categories ?: array_map(fn($n) => ['name' => $n, 'category_id' => null],
                ['Maths','Languages','Sciences','Art & Design','Coding','Test Prep','Business','Music','Humanities','Geography','Data','Writing']);
            foreach ($tiles as $i => $cat):
                $href = $cat['category_id']
                    ? 'coach-search.php?category=' . urlencode($cat['category_id'])
                    : 'coach-search.php?query=' . urlencode($cat['name']);
            ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?= e($href) ?>" class="subject-tile">
                        <span class="icon-circle"><i class="bi <?= $icons[$i % count($icons)] ?>"></i></span>
                        <p class="subject-name"><?= e($cat['name']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured coaches -->
<section class="section bg-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
            <div>
                <p class="eyebrow mb-2">Top rated</p>
                <h2 class="mb-1">Featured coaches</h2>
                <p class="text-muted mb-0">Hand-picked, highly rated experts ready to help.</p>
            </div>
            <a href="coach-search.php" class="btn btn-outline-primary">Browse all coaches</a>
        </div>
        <div class="row g-4">
            <?php if (!$featured_coaches): ?>
                <div class="col-12"><div class="alert alert-light border text-center mb-0">No coaches to show yet — be the first to <a href="become-coach.php">join as a coach</a>.</div></div>
            <?php else: foreach ($featured_coaches as $coach): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 hover-lift text-center">
                        <div class="card-body">
                            <img src="<?= e(avatar_url($coach['profile_image'] ?? null, $coach['username'])) ?>"
                                 alt="<?= e($coach['username']) ?>" class="rounded-circle mb-3"
                                 style="width:84px;height:84px;object-fit:cover">
                            <h3 class="h6 mb-1"><?= e($coach['username']) ?></h3>
                            <p class="small text-muted mb-2"><?= e($coach['headline'] ?? 'EduCoach coach') ?></p>
                            <div class="rating-stars mb-3">
                                <?php $r = (float) $coach['rating']; for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?= $i <= floor($r) ? 'bi-star-fill' : ($i - 0.5 <= $r ? 'bi-star-half' : 'bi-star') ?>"></i>
                                <?php endfor; ?>
                                <span class="text-muted small ms-1"><?= number_format($r, 1) ?></span>
                            </div>
                            <a href="coach-profile.php?id=<?= (int) $coach['coach_id'] ?>" class="btn btn-sm btn-outline-primary w-100">View profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Simple</p>
            <h2 class="mb-2">How EduCoach works</h2>
            <p class="text-muted mb-0">Three steps from “I want to learn” to your first session.</p>
        </div>
        <div class="row g-4">
            <?php
            $steps = [
                ['bi-search', 'Find your coach', 'Search by subject, filter by price and rating, and read verified reviews from real learners.'],
                ['bi-chat-dots', 'Connect & agree', 'Message a coach to discuss your goals, then choose a service tier that fits your budget.'],
                ['bi-mortarboard', 'Learn & grow', 'Book your session, meet online or in person, and track your progress over time.'],
            ];
            foreach ($steps as $n => [$icon, $title, $text]): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="icon-circle mb-3"><i class="bi <?= $icon ?>"></i></span>
                            <h3 class="h5"><?= ($n + 1) . '. ' . $title ?></h3>
                            <p class="text-muted mb-0"><?= $text ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Loved by learners</p>
            <h2 class="mb-2">The perfect match</h2>
            <p class="text-muted mb-0">Thousands of 5-star sessions and counting.</p>
        </div>
        <div class="row g-4">
            <?php
            $reviews = $testimonials ?: [
                ['rating' => 5, 'comment' => 'My coach tailored every session to exactly what I needed. I finally understand calculus!', 'learner_name' => 'Sarah', 'coach_name' => 'Michael'],
                ['rating' => 5, 'comment' => "Best tutoring I've had. Structured, patient and genuinely encouraging.", 'learner_name' => 'James', 'coach_name' => 'Emily'],
                ['rating' => 5, 'comment' => 'After years of trying, I can finally play my favourite songs on guitar.', 'learner_name' => 'David', 'coach_name' => 'Laura'],
            ];
            foreach ($reviews as $t): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="rating-stars mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?= $i <= (int) $t['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="mb-4">“<?= e($t['comment']) ?>”</p>
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar user-avatar--placeholder"><i class="bi bi-person"></i></span>
                                <div>
                                    <p class="mb-0 fw-semibold small"><?= e($t['learner_name']) ?></p>
                                    <p class="mb-0 text-muted small">with coach <?= e($t['coach_name']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="rounded-4 p-5 text-center text-white" style="background:linear-gradient(120deg,var(--ec-primary),var(--ec-primary-darker))">
            <h2 class="text-white mb-2">Ready to start learning?</h2>
            <p class="lead mb-4 opacity-75">Join EduCoach today and connect with a coach who gets you.</p>
            <?php if ($user_id): ?>
                <a href="coach-search.php" class="btn btn-light btn-lg px-4">Find a coach</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-light btn-lg px-4 me-2">Get started — it's free</a>
                <a href="become-coach.php" class="btn btn-outline-light btn-lg px-4">Become a coach</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
