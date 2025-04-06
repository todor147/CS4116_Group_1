<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4">Frequently Asked Questions</h1>
            <p class="lead text-muted">Find answers to the most common questions about EduCoach</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="accordion" id="faqAccordion">
                <!-- General Questions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">General Questions</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="generalQuestions">
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        What is EduCoach?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#generalQuestions">
                                    <div class="accordion-body">
                                        EduCoach is an online platform that connects learners with expert coaches across various disciplines. Whether you're looking to improve in academics, professional skills, or personal development, we help you find and connect with qualified coaches who can guide you on your learning journey.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        How do I get started?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#generalQuestions">
                                    <div class="accordion-body">
                                        Getting started is easy! Simply register for an account, browse our selection of coaches, and submit an inquiry to the coach you're interested in working with. Once the coach accepts your inquiry, you can schedule your first session and begin your learning journey.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        What subjects or skills can I learn?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#generalQuestions">
                                    <div class="accordion-body">
                                        EduCoach offers a wide range of subjects including mathematics, languages, sciences, arts, computer science, test preparation, business skills, and humanities. Our network of coaches continues to grow, so if you don't find exactly what you're looking for, check back regularly or contact us with specific requests.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking and Sessions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Booking and Sessions</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="bookingQuestions">
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        How do I book a session?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#bookingQuestions">
                                    <div class="accordion-body">
                                        To book a session, first browse our coaches and select one who meets your needs. Then submit a service inquiry describing your learning goals. Once the coach accepts your inquiry, you can schedule a session at a mutually convenient time.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        How long are coaching sessions?
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#bookingQuestions">
                                    <div class="accordion-body">
                                        Session durations vary by coach and service tier, but typically range from 30 minutes to 2 hours. You can find the specific session length information on each coach's profile and service tier descriptions.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                        What if I need to reschedule a session?
                                    </button>
                                </h2>
                                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#bookingQuestions">
                                    <div class="accordion-body">
                                        You can request to reschedule a session through your session details page. Simply navigate to your scheduled session, click the "Request Reschedule" button, and propose a new time. Your coach will receive a notification and can approve or decline the request.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account and Payments -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Account and Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accountQuestions">
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                        How do I update my profile information?
                                    </button>
                                </h2>
                                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accountQuestions">
                                    <div class="accordion-body">
                                        You can update your profile information by logging into your account and navigating to the "Profile" section. From there, you can edit your personal details, update your profile picture, and modify your preferences.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                        What payment methods do you accept?
                                    </button>
                                </h2>
                                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accountQuestions">
                                    <div class="accordion-body">
                                        We accept major credit cards, PayPal, and Stripe for payment. All transactions are processed securely, and your payment information is never stored on our servers.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="headingNine">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                        What is your refund policy?
                                    </button>
                                </h2>
                                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#accountQuestions">
                                    <div class="accordion-body">
                                        If a session is cancelled by the coach, you will receive a full refund. If you need to cancel a session, our refund policy depends on how far in advance you cancel. Cancellations made more than 24 hours before the scheduled session time are eligible for a full refund, while those made within 24 hours may be subject to a cancellation fee.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <h5>Still have questions?</h5>
                <p class="text-muted">We're here to help! Contact our support team.</p>
                <a href="contact.php" class="btn btn-primary mt-2">Contact Us</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 