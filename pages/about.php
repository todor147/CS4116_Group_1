<?php
require_once __DIR__ . '/../includes/db_connection.php';

$page_title = 'About — EduCoach';
include __DIR__ . '/../includes/header.php';

$team = [
    ['Todor Aleksandrov', 'System architecture & backend'],
    ['Rian Quinn', 'Frontend design & UX'],
    ['Fionn Clancy Molloy', 'Database design & APIs'],
    ['Darragh Kennedy', 'Testing & documentation'],
];

$mission = [
    ['bi-mortarboard', 'Personalised learning', 'Education tailored to each learner’s goals, level and preferred style.'],
    ['bi-globe2', 'Connecting people', 'A vibrant marketplace that links learners with the right coaches — anywhere.'],
    ['bi-patch-check', 'Quality first', 'Reviews and verification keep the bar high for every coaching experience.'],
];

$tech = [
    ['bi-code-slash', 'Technology stack', 'PHP & MySQL on the back end; HTML5, CSS3, Bootstrap 5 and vanilla JS on the front.'],
    ['bi-search', 'Advanced search', 'SQL-powered search with filters for expertise, price and coach ratings.'],
    ['bi-shield-check', 'Security focus', 'Prepared statements, input validation, CSRF protection and hashed passwords.'],
    ['bi-people', 'Built for both sides', 'Responsive, intuitive workflows for learners and coaches alike.'],
];
?>

<section class="hero section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="eyebrow mb-2">Our story</p>
                <h1 class="hero-title mb-3">Making great coaching <span class="text-gradient">easy to find</span></h1>
                <p class="lead text-muted mb-4">
                    EduCoach is a marketplace that connects curious learners with expert tutors and coaches.
                    It began as a CS4116 project at the University of Limerick — a real-world application of
                    software design, database engineering and modern web development.
                </p>
                <a href="coach-search.php" class="btn btn-primary me-2">Explore coaches</a>
                <a href="https://github.com/todor147/CS4116_Group_1" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="bi bi-github me-1"></i> View on GitHub
                </a>
            </div>
            <div class="col-lg-6">
                <div class="rounded-4 p-5 text-white text-center" style="background:linear-gradient(135deg,var(--ec-primary),var(--ec-accent))">
                    <i class="bi bi-mortarboard-fill" style="font-size:4rem"></i>
                    <div class="row mt-4 g-3">
                        <div class="col-4"><div class="fs-3 fw-bold">1k+</div><div class="small opacity-75">Coaches</div></div>
                        <div class="col-4"><div class="fs-3 fw-bold">12</div><div class="small opacity-75">Subjects</div></div>
                        <div class="col-4"><div class="fs-3 fw-bold">5★</div><div class="small opacity-75">Avg. review</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">What drives us</p>
            <h2>Our mission</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($mission as [$icon, $title, $text]): ?>
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
    </div>
</section>

<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">The people</p>
            <h2>Development team</h2>
            <p class="text-muted mb-0">Built under the supervision of Professor Conor Ryan, University of Limerick.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($team as [$name, $role]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <img src="https://ui-avatars.com/api/?background=eef2ff&color=4f46e5&size=96&name=<?= urlencode($name) ?>"
                                 alt="<?= e($name) ?>" class="rounded-circle mb-3" width="72" height="72">
                            <h3 class="h6 mb-1"><?= e($name) ?></h3>
                            <p class="small text-muted mb-0"><?= e($role) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Under the hood</p>
            <h2>Technical implementation</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($tech as [$icon, $title, $text]): ?>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="icon-circle flex-shrink-0"><i class="bi <?= $icon ?>"></i></span>
                        <div>
                            <h3 class="h5 mb-1"><?= $title ?></h3>
                            <p class="text-muted mb-0"><?= $text ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="coach-search.php" class="btn btn-primary btn-lg px-4">Try the platform</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
