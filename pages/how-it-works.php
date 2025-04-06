<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4">How EduCoach Works</h1>
            <p class="lead text-muted">Connect with expert coaches in just a few simple steps</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="timeline">
                                <!-- Step 1 -->
                                <div class="timeline-item mb-5">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="timeline-badge bg-primary">
                                                <span class="text-white">1</span>
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <h4>Create Your Account</h4>
                                            <p>Sign up for EduCoach and create your personal profile. Tell us about your learning goals and preferences so we can match you with the right coaches.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2 -->
                                <div class="timeline-item mb-5">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="timeline-badge bg-primary">
                                                <span class="text-white">2</span>
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <h4>Browse Expert Coaches</h4>
                                            <p>Explore our diverse community of verified coaches. Filter by subject, skill level, price range, and availability to find your perfect match.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div class="timeline-item mb-5">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="timeline-badge bg-primary">
                                                <span class="text-white">3</span>
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <h4>Submit an Inquiry</h4>
                                            <p>Connect with potential coaches by submitting a service inquiry. Describe your goals and ask any questions you might have before committing to a session.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4 -->
                                <div class="timeline-item mb-5">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="timeline-badge bg-primary">
                                                <span class="text-white">4</span>
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <h4>Schedule Your Sessions</h4>
                                            <p>Once your inquiry is accepted, work with your coach to schedule sessions at convenient times. Our flexible scheduling system makes it easy to find times that work for both of you.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 5 -->
                                <div class="timeline-item">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="timeline-badge bg-primary">
                                                <span class="text-white">5</span>
                                            </div>
                                        </div>
                                        <div class="col-md-10">
                                            <h4>Learn and Grow</h4>
                                            <p>Attend your sessions and work directly with your coach to achieve your goals. After each session, you can provide feedback and track your progress over time.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Verified Coaches</h5>
                    <p class="text-muted">All our coaches go through a verification process to ensure quality instruction.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-chat-dots text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Direct Communication</h5>
                    <p class="text-muted">Message your coach directly to discuss your goals and ask questions.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-star text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Customer Insights</h5>
                    <p class="text-muted">Get insights from verified customers who have worked with coaches you're interested in.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-8 mx-auto text-center">
            <h4>Ready to start your learning journey?</h4>
            <p class="text-muted">Join thousands of learners who have achieved their goals with EduCoach</p>
            <a href="coach-search.php" class="btn btn-primary btn-lg mt-3">Find a Coach Now</a>
        </div>
    </div>
</div>

<style>
.timeline-badge {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}
.timeline-item {
    position: relative;
}
.timeline-item:not(:last-child):after {
    content: '';
    position: absolute;
    left: calc(8.33% + 25px);
    top: 70px;
    bottom: -20px;
    width: 2px;
    background-color: #e9ecef;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?> 