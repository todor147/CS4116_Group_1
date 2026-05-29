<?php require_once __DIR__ . '/config.php'; // ensure helpers (asset, e) are available ?>
    </main><!-- /#main-content -->

    <footer class="app-footer">
        <div class="container">
            <div class="row g-4">
                <!-- Brand -->
                <div class="col-lg-4 col-md-12">
                    <a class="footer-brand d-inline-flex align-items-center gap-2 mb-3" href="home.php">
                        <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                        <span class="fs-5 fw-bold text-white">EduCoach</span>
                    </a>
                    <p class="footer-text">
                        Learn anything, from anyone. EduCoach connects curious learners with
                        expert tutors and coaches — across maths, languages, music, code and more.
                    </p>
                    <div class="footer-social mt-3">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <!-- For learners -->
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">For learners</h6>
                    <ul class="footer-links">
                        <li><a href="coach-search.php">Find a coach</a></li>
                        <li><a href="how-it-works.php">How it works</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- For coaches -->
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">For coaches</h6>
                    <ul class="footer-links">
                        <?php if (!empty($_SESSION['logged_in']) && ($_SESSION['user_type'] ?? '') === 'business'): ?>
                            <li><a href="edit-coach-profile.php">Coach dashboard</a></li>
                        <?php else: ?>
                            <li><a href="become-coach.php">Become a coach</a></li>
                        <?php endif; ?>
                        <li><a href="coach-resources.php">Resources</a></li>
                        <li><a href="success-stories.php">Success stories</a></li>
                        <li><a href="community.php">Community</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">Support</h6>
                    <ul class="footer-links">
                        <li><a href="help.php">Help centre</a></li>
                        <li><a href="contact.php">Contact us</a></li>
                        <li><a href="about.php">About</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">Legal</h6>
                    <ul class="footer-links">
                        <li><a href="privacy.php">Privacy policy</a></li>
                        <li><a href="terms.php">Terms of service</a></li>
                    </ul>
                </div>
            </div>

            <hr class="footer-divider">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="footer-text mb-0">© <?= date('Y') ?> EduCoach. Built as a CS4116 project.</p>
                <div class="footer-pay">
                    <i class="bi bi-credit-card-2-front"></i>
                    <i class="bi bi-paypal"></i>
                    <i class="bi bi-wallet2"></i>
                    <span class="small ms-1">Secure payments</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts (loaded once, at end of body) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('assets/js/scripts.js') ?>"></script>

    <script>
    // Subtle elevation on the sticky navbar once the page is scrolled.
    (function () {
        const navbar = document.getElementById('appNavbar');
        if (!navbar) return;
        const onScroll = () => navbar.classList.toggle('navbar-scrolled', window.scrollY > 10);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
    </script>

    <?php if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])): ?>
    <script>
    // Poll for new notifications and update the badge.
    (function () {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;
        const check = () => fetch('check_notifications.php')
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            })
            .catch(() => {});
        setInterval(check, 60000);
    })();
    </script>
    <?php endif; ?>
</body>
</html>
