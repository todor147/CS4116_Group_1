<?php
require_once __DIR__ . '/../includes/db_connection.php';

$page_title = 'Pricing — EduCoach';
include __DIR__ . '/../includes/header.php';

$tiers = [
    ['€15–30', 'Getting started', 'Entry-level coaches and group sessions — perfect for building momentum.'],
    ['€30–50', 'Most popular', 'Experienced coaches and specialised subjects for steady progress.', true],
    ['€50–80+', 'Premium', 'Expert coaches and bespoke, high-intensity programmes.'],
];

$benefits = [
    ['bi-hand-thumbs-up', 'Fair value for everyone', 'Coaches price to reflect their expertise; learners choose what fits their budget.'],
    ['bi-graph-up-arrow', 'Pay for what you need', 'No subscriptions, no hidden fees — only pay for the sessions you book.'],
    ['bi-stars', 'Quality incentives', 'Transparent ratings reward the best coaches and keep pricing honest.'],
];

$faqs = [
    ['Are there any platform fees for learners?', "No. The price on a coach's profile is exactly what you pay per session — payment-processing fees are on us."],
    ['Do coaches offer package discounts?', 'Many do. Booking several sessions up front often saves 10–20% versus single sessions, and any deals are shown clearly on each profile.'],
    ['What is your refund policy?', 'Cancel more than 24 hours ahead for a full refund, or within 24 hours for 50%. Not happy with a first session? Contact support within 24 hours to discuss options.'],
    ['How do I know a coach is worth their rate?', 'Every profile shows verified reviews, qualifications and teaching style. Many coaches offer a short intro session at a reduced rate so you can check the fit first.'],
];
?>

<section class="hero section">
    <div class="container text-center">
        <p class="eyebrow mb-2">Pricing</p>
        <h1 class="hero-title mb-3">Transparent, <span class="text-gradient">coach-led</span> pricing</h1>
        <p class="lead text-muted mx-auto" style="max-width:42rem">
            No subscriptions. No surprises. Each coach sets their own rates based on experience,
            expertise and teaching style — you choose what fits.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($tiers as $t): ?>
                <div class="col-md-4">
                    <div class="card h-100 <?= !empty($t[3]) ? 'border-primary shadow' : 'border-0 shadow-sm' ?> hover-lift text-center">
                        <div class="card-body p-4">
                            <?php if (!empty($t[3])): ?><span class="badge bg-soft mb-2">Most chosen</span><?php endif; ?>
                            <p class="eyebrow mb-1"><?= e($t[1]) ?></p>
                            <div class="display-6 fw-bold mb-2"><?= e($t[0]) ?><span class="fs-6 text-muted fw-normal">/hr</span></div>
                            <p class="text-muted mb-0"><?= e($t[2]) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-3">Typical ranges across subjects, experience levels and formats.</p>
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="mb-3">How pricing works</h2>
                <p class="lead text-muted">Unlike platforms with fixed subscriptions, EduCoach puts pricing in the hands of qualified coaches. Each sets their rate based on:</p>
                <ul class="list-unstyled">
                    <?php foreach ([
                        'Experience and qualifications',
                        'Areas of expertise and specialisation',
                        'Lesson type — one-to-one, group and more',
                        'Session duration and frequency',
                        'Package discounts for multiple sessions',
                    ] as $point): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i><?= $point ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="coach-search.php" class="btn btn-primary mt-2">Find a coach</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-4">
                    <?php foreach ($benefits as [$icon, $title, $text]): ?>
                        <div class="col-12">
                            <div class="d-flex gap-3">
                                <span class="icon-circle flex-shrink-0"><i class="bi <?= $icon ?>"></i></span>
                                <div>
                                    <h3 class="h6 mb-1"><?= $title ?></h3>
                                    <p class="text-muted mb-0 small"><?= $text ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Questions</p>
            <h2>Frequently asked questions</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="pricingFAQ">
                    <?php foreach ($faqs as $i => [$q, $a]): ?>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"
                                        aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                                    <?= e($q) ?>
                                </button>
                            </h3>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#pricingFAQ">
                                <div class="accordion-body text-muted"><?= e($a) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="rounded-4 p-5 text-center text-white" style="background:linear-gradient(120deg,var(--ec-primary),var(--ec-primary-darker))">
            <h2 class="text-white mb-2">Ready to find your perfect coach?</h2>
            <p class="lead mb-4 opacity-75">Browse our network of qualified coaches and find your match.</p>
            <a href="coach-search.php" class="btn btn-light btn-lg me-2 px-4">Find a coach</a>
            <a href="become-coach.php" class="btn btn-outline-light btn-lg px-4">Become a coach</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
