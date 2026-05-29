<?php
require_once __DIR__ . '/../includes/db_connection.php';

$page_title = 'How it works — EduCoach';
include __DIR__ . '/../includes/header.php';

$steps = [
    ['bi-person-plus', 'Create your account', "Sign up in seconds and tell us what you'd like to learn. It's free, and you only pay when you book a session."],
    ['bi-search', 'Browse expert coaches', 'Filter by subject, level, price and rating. Read verified reviews and watch intro videos to find your match.'],
    ['bi-chat-dots', 'Send an inquiry', 'Message a coach about your goals and ask anything before you commit — no pressure, no obligation.'],
    ['bi-calendar2-check', 'Schedule your sessions', 'Once your inquiry is accepted, pick times that suit you both with our flexible scheduling.'],
    ['bi-mortarboard', 'Learn and grow', 'Meet online or in person, leave a review afterwards, and watch your progress build over time.'],
];

$features = [
    ['bi-shield-check', 'Verified coaches', 'Every coach builds a detailed profile so you know exactly who you’re learning from.'],
    ['bi-chat-heart', 'Direct communication', 'Message coaches directly to discuss goals and questions before booking.'],
    ['bi-people', 'Customer insights', 'Hear from verified learners who’ve already worked with the coaches you’re considering.'],
];
?>

<section class="hero section-sm">
    <div class="container text-center">
        <p class="eyebrow mb-2">Get started</p>
        <h1 class="hero-title mb-3">How EduCoach works</h1>
        <p class="lead text-muted mx-auto" style="max-width:40rem">From “I’d like to learn” to your first session in five simple steps.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <ol class="ec-timeline list-unstyled mb-0">
                    <?php foreach ($steps as $n => [$icon, $title, $text]): ?>
                        <li class="ec-timeline__item">
                            <span class="ec-timeline__dot"><i class="bi <?= $icon ?>"></i></span>
                            <div class="ec-timeline__body">
                                <h2 class="h5 mb-1"><?= ($n + 1) . '. ' . $title ?></h2>
                                <p class="text-muted mb-0"><?= $text ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($features as [$icon, $title, $text]): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <span class="icon-circle mb-3 mx-auto"><i class="bi <?= $icon ?>"></i></span>
                            <h3 class="h5"><?= $title ?></h3>
                            <p class="text-muted mb-0"><?= $text ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <h2 class="h4">Ready to start your learning journey?</h2>
            <p class="text-muted">Join thousands of learners achieving their goals with EduCoach.</p>
            <a href="coach-search.php" class="btn btn-primary btn-lg mt-2 px-4">Find a coach now</a>
        </div>
    </div>
</section>

<style>
.ec-timeline { position: relative; padding-left: 0; }
.ec-timeline__item { position: relative; display: flex; gap: 1.25rem; padding-bottom: 2rem; }
.ec-timeline__item:last-child { padding-bottom: 0; }
.ec-timeline__dot {
    flex: 0 0 auto; display: inline-grid; place-items: center;
    width: 52px; height: 52px; border-radius: 16px; z-index: 1;
    background: var(--ec-primary-soft); color: var(--ec-primary); font-size: 1.4rem;
}
.ec-timeline__item:not(:last-child) .ec-timeline__dot::after {
    content: ''; position: absolute; left: 25px; top: 52px; bottom: 0; width: 2px;
    background: var(--ec-line);
}
.ec-timeline__body { padding-top: .35rem; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
