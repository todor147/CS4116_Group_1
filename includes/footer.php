    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row">
                <!-- About Column -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">EduCoach</h5>
                    <p class="small">EduCoach connects learners with expert tutors and coaches across various disciplines, helping you achieve your educational and personal development goals.</p>
                    <div class="mt-4">
                        <a href="#" class="btn btn-outline-light btn-floating me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-light btn-floating me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-floating me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="btn btn-outline-light btn-floating"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                
                <!-- For Learners Column -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">For Learners</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="search.php" class="text-white text-decoration-none">Find a Coach</a></li>
                        <li class="mb-2"><a href="categories.php" class="text-white text-decoration-none">Browse Categories</a></li>
                        <li class="mb-2"><a href="how-it-works.php" class="text-white text-decoration-none">How It Works</a></li>
                        <li class="mb-2"><a href="pricing.php" class="text-white text-decoration-none">Pricing</a></li>
                        <li><a href="faq.php" class="text-white text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
                
                <!-- For Coaches Column -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">For Coaches</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="become-coach.php" class="text-white text-decoration-none">Become a Coach</a></li>
                        <li class="mb-2"><a href="coach-resources.php" class="text-white text-decoration-none">Coach Resources</a></li>
                        <li class="mb-2"><a href="success-stories.php" class="text-white text-decoration-none">Success Stories</a></li>
                        <li class="mb-2"><a href="coach-faq.php" class="text-white text-decoration-none">Coach FAQ</a></li>
                        <li><a href="community.php" class="text-white text-decoration-none">Coach Community</a></li>
                    </ul>
                </div>
                
                <!-- Support Column -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">Support</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="help.php" class="text-white text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white text-decoration-none">Contact Us</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-white text-decoration-none">Terms of Service</a></li>
                        <li><a href="about.php" class="text-white text-decoration-none">About Us</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Copyright -->
            <div class="row align-items-center">
                <div class="col-md-7 col-lg-8">
                    <p class="small mb-md-0">© <?php echo date('Y'); ?> EduCoach. All rights reserved.</p>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="text-end">
                        <!-- Payment icons as text links instead of image -->
                        <span class="text-white me-2"><i class="bi bi-credit-card"></i> Credit Card</span>
                        <span class="text-white me-2"><i class="bi bi-paypal"></i> PayPal</span>
                        <span class="text-white"><i class="bi bi-wallet2"></i> Stripe</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <?php
    // Determine if we're in a subdirectory
    $current_script = $_SERVER['SCRIPT_NAME'] ?? '';
    
    if (strpos($current_script, '/pages/') !== false) {
        // We're in a pages directory, need to go up one level
        echo '<script src="../assets/js/scripts.js"></script>';
    } else {
        // We're at root level
        echo '<script src="assets/js/scripts.js"></script>';
    }
    ?>
</body>
</html> 