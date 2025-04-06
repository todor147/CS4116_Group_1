<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Coach Resources | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Coach Resources</h1>
        <p class="lead text-muted">Everything you need to succeed as a coach on EduCoach</p>
    </div>

    <!-- Resource Categories -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center py-4">
                    <i class="bi bi-book text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-4">Getting Started</h4>
                    <p class="text-muted">Essential guides for new coaches on the platform</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center py-4">
                    <i class="bi bi-graph-up text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-4">Growing Your Practice</h4>
                    <p class="text-muted">Strategies to attract more students and increase earnings</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center py-4">
                    <i class="bi bi-tools text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-4">Teaching Tools</h4>
                    <p class="text-muted">Recommended software and resources for effective coaching</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Getting Started Resources -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Getting Started</h2>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-text text-primary me-2"></i>Complete Profile Guide</h5>
                        <p class="card-text">Learn how to create a compelling coach profile that attracts students.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Download PDF</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-camera-video text-primary me-2"></i>Video Session Setup</h5>
                        <p class="card-text">Technical guide to setting up perfect video coaching sessions.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Watch Tutorial</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-check text-primary me-2"></i>Scheduling Best Practices</h5>
                        <p class="card-text">Optimize your availability to maximize bookings and manage your time.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read Article</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-cash-coin text-primary me-2"></i>Pricing Your Services</h5>
                        <p class="card-text">Guidelines for setting competitive yet profitable rates for your expertise.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">View Guide</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growing Your Practice -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Growing Your Practice</h2>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-star text-primary me-2"></i>Gathering Reviews</h5>
                        <p class="card-text">Strategies to encourage positive reviews from your students.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read Guide</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-megaphone text-primary me-2"></i>Marketing Yourself</h5>
                        <p class="card-text">Learn how to promote your coaching services online and offline.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Download Toolkit</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Reading Your Analytics</h5>
                        <p class="card-text">Understanding your performance metrics and using them to grow.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">View Tutorial</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people text-primary me-2"></i>Building Student Relationships</h5>
                        <p class="card-text">Tips for creating lasting relationships that lead to repeat bookings.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read Article</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teaching Tools -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Teaching Tools</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-laptop text-primary me-2"></i>Virtual Whiteboard</h5>
                        <p class="card-text">Free and premium whiteboard tools for interactive teaching.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">View Recommendations</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-pdf text-primary me-2"></i>Worksheet Templates</h5>
                        <p class="card-text">Downloadable worksheet templates for various subjects and learning goals.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Download Templates</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-headset text-primary me-2"></i>Audio Equipment</h5>
                        <p class="card-text">Recommendations for microphones and headsets for clear communication.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">View Guide</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-camera text-primary me-2"></i>Webcam Setup</h5>
                        <p class="card-text">Tips for optimal lighting and webcam positioning for professional sessions.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Watch Tutorial</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clock text-primary me-2"></i>Time Management Tools</h5>
                        <p class="card-text">Recommended apps and techniques for efficient session management.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">View Recommendations</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clipboard-check text-primary me-2"></i>Assessment Resources</h5>
                        <p class="card-text">Tools for creating quizzes and assessments to track student progress.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Explore Tools</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coach Community CTA -->
    <div class="bg-light p-5 rounded-3 mt-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2>Join Our Coach Community</h2>
                <p class="lead">Connect with fellow coaches, share experiences, and learn from each other in our dedicated community forums.</p>
                <a href="community.php" class="btn btn-primary">Join the Community</a>
            </div>
            <div class="col-lg-4 text-center">
                <i class="bi bi-people-fill text-primary" style="font-size: 8rem;"></i>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?> 