<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Pricing | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Transparent Coach-Led Pricing</h1>
        <p class="lead text-muted">On EduCoach, each coach sets their own rates based on their experience, expertise, and teaching style</p>
    </div>

    <!-- How Pricing Works -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h2 class="mb-4">How Pricing Works on EduCoach</h2>
            <p class="lead">Unlike traditional platforms with fixed subscription fees, EduCoach puts pricing control in the hands of our qualified coaches.</p>
            <p>Each coach sets their own hourly rates based on:</p>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Their level of experience and qualifications</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Areas of expertise and specialization</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Lesson types (one-on-one, group sessions, etc.)</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Session duration and frequency options</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Package discounts for multiple sessions</li>
            </ul>
            <a href="coach-search.php" class="btn btn-primary">Find a Coach</a>
        </div>
        <div class="col-lg-6">
            <img src="https://source.unsplash.com/random/600x400/?teaching" class="img-fluid rounded shadow" alt="Coach teaching">
        </div>
    </div>

    <!-- Pricing Range -->
    <div class="bg-light p-5 rounded-3 mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2>Typical Price Ranges</h2>
                <p class="lead">Coaching rates typically range from €15 to €80 per hour depending on subject, experience level, and coaching format.</p>
                <div class="mt-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">€15-30</h5>
                                    <p class="card-text text-muted">Entry-level coaches and group sessions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">€30-50</h5>
                                    <p class="card-text text-muted">Experienced coaches and specialized subjects</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">€50-80+</h5>
                                    <p class="card-text text-muted">Expert coaches and premium specialized services</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <i class="bi bi-currency-euro text-primary" style="font-size: 8rem;"></i>
            </div>
        </div>
    </div>

    <!-- Coach Pricing Benefits -->
    <h2 class="mb-4">Benefits of Our Coach-Led Pricing Model</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-hand-thumbs-up text-primary me-2"></i>Fair Value for Everyone</h5>
                    <p class="card-text">Coaches set rates that reflect their true value and expertise, while learners can choose coaches that match their budget and needs.</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-graph-up text-primary me-2"></i>Pay For What You Need</h5>
                    <p class="card-text">No monthly subscriptions or hidden fees. Only pay for the sessions you book, with the flexibility to adjust frequency based on your learning goals.</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-stars text-primary me-2"></i>Quality Incentives</h5>
                    <p class="card-text">Our rating system and transparent reviews help top coaches build their reputation and adjust their rates according to market demand.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Finding the Right Coach -->
    <div class="card mb-5 border-0 shadow">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="mb-3">Finding the Right Coach at the Right Price</h3>
                    <p>Our advanced search filters allow you to:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-funnel-fill text-primary me-2"></i> Filter by price range</li>
                                <li class="mb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Find coaches by location</li>
                                <li class="mb-2"><i class="bi bi-calendar2-check text-primary me-2"></i> View availability in real-time</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-star-fill text-primary me-2"></i> Sort by ratings and reviews</li>
                                <li class="mb-2"><i class="bi bi-tags-fill text-primary me-2"></i> Compare package discounts</li>
                                <li class="mb-2"><i class="bi bi-mortarboard-fill text-primary me-2"></i> Verify credentials and experience</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="coach-search.php" class="btn btn-outline-primary">Search for Coaches</a>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <img src="https://source.unsplash.com/random/300x300/?search" class="img-fluid rounded" alt="Search">
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="mt-5 pt-3">
        <h2 class="text-center mb-5">Frequently Asked Questions</h2>
        <div class="accordion" id="pricingFAQ">
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Are there any platform fees for students?
                    </button>
                </h3>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#pricingFAQ">
                    <div class="accordion-body">
                        No, EduCoach doesn't charge any additional fees to students. The price you see on a coach's profile is exactly what you'll pay per session. All payment processing fees are covered by the platform.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        Do coaches offer package discounts?
                    </button>
                </h3>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#pricingFAQ">
                    <div class="accordion-body">
                        Yes, many coaches offer discounted rates when you book multiple sessions in advance. These package deals are set by individual coaches and will be clearly displayed on their profiles. Booking in packages often provides savings of 10-20% compared to single sessions.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        What is your refund policy?
                    </button>
                </h3>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#pricingFAQ">
                    <div class="accordion-body">
                        We offer a fair refund policy: cancellations more than 24 hours before the scheduled session receive a full refund. Cancellations less than 24 hours before receive a 50% refund. If you're not satisfied with your first session with a coach, we also offer a satisfaction guarantee - contact our support team within 24 hours of your first lesson to discuss your options.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        How do I know if a coach is worth their rate?
                    </button>
                </h3>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#pricingFAQ">
                    <div class="accordion-body">
                        Every coach profile includes verified reviews from past students, detailed information about their qualifications, experience, and teaching style. Many coaches also offer a brief introductory session at a reduced rate so you can determine if they're the right fit for your learning needs before committing to their full rate.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="card bg-primary text-white mt-5 border-0 shadow">
        <div class="card-body p-5 text-center">
            <h2 class="card-title mb-3">Ready to Find Your Perfect Coach?</h2>
            <p class="card-text lead mb-4">Browse through our extensive network of qualified coaches and find the perfect match for your learning goals and budget.</p>
            <a href="coach-search.php" class="btn btn-light btn-lg me-2">Find a Coach</a>
            <a href="become-coach.php" class="btn btn-outline-light btn-lg">Become a Coach</a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?> 