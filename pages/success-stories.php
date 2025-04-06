<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Success Stories | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Coach Success Stories</h1>
        <p class="lead text-muted">Meet some of our top coaches and learn how they've built thriving practices on EduCoach</p>
    </div>

    <!-- Featured Success Story -->
    <div class="card mb-5 border-0 shadow">
        <div class="row g-0">
            <div class="col-md-5">
                <img src="https://source.unsplash.com/random/800x1000/?teacher" class="img-fluid rounded-start h-100 object-fit-cover" alt="Featured Coach">
            </div>
            <div class="col-md-7">
                <div class="card-body p-4 p-lg-5">
                    <h5 class="card-title display-6 mb-3">From Part-Time Tutor to Full-Time Coach</h5>
                    <p class="card-text lead mb-3">"EduCoach transformed my side hustle into a profitable full-time career. I now earn more than I did in my corporate job while helping students achieve their learning goals."</p>
                    <p class="card-text mb-4">Sarah Johnson started as a part-time math tutor using EduCoach to find a few extra students on weekends. Within 8 months, her student base grew to the point where she could leave her corporate job and coach full-time. She now specializes in advanced mathematics and test preparation for university entrance exams.</p>
                    <div class="d-flex align-items-center mb-4">
                        <div class="me-3">
                            <strong>Sarah Johnson</strong><br>
                            <span class="text-muted">Mathematics Coach</span>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-warning text-dark p-2">2 Years on EduCoach</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-dark p-2 me-2"><i class="bi bi-star-fill text-warning"></i> 4.9 Rating</span>
                            <span class="badge bg-light text-dark p-2"><i class="bi bi-people-fill text-primary"></i> 200+ Students</span>
                        </div>
                        <a href="#" class="btn btn-outline-primary">Read Full Story</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Stories Grid -->
    <h2 class="border-bottom pb-2 mb-4">More Success Stories</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4 mb-5">
        <!-- Success Story 1 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://source.unsplash.com/random/100x100/?man" class="rounded-circle me-3" width="60" height="60" alt="Coach portrait">
                        <div>
                            <h5 class="card-title mb-0">Michael Chen</h5>
                            <p class="text-muted mb-0">Language Coach</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark p-2 me-2"><i class="bi bi-star-fill text-warning"></i> 4.8 Rating</span>
                        <span class="badge bg-light text-dark p-2"><i class="bi bi-people-fill text-primary"></i> 150+ Students</span>
                    </div>
                    <p class="card-text">"After moving to a new country, I was struggling to find language students locally. EduCoach connected me with students worldwide, and I was able to create a stable coaching business within just 6 months!"</p>
                    <p class="card-text text-muted">Michael specializes in Mandarin Chinese and Japanese language coaching, focusing on business professionals and travelers.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read Full Story</a>
                </div>
            </div>
        </div>
        <!-- Success Story 2 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://source.unsplash.com/random/100x100/?woman" class="rounded-circle me-3" width="60" height="60" alt="Coach portrait">
                        <div>
                            <h5 class="card-title mb-0">Elena Rodriguez</h5>
                            <p class="text-muted mb-0">Music Coach</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark p-2 me-2"><i class="bi bi-star-fill text-warning"></i> 5.0 Rating</span>
                        <span class="badge bg-light text-dark p-2"><i class="bi bi-people-fill text-primary"></i> 75+ Students</span>
                    </div>
                    <p class="card-text">"EduCoach's platform allowed me to create specialized music courses that I can offer globally. I've tripled my income in just one year and have students from 12 different countries!"</p>
                    <p class="card-text text-muted">Elena teaches piano and music theory to students of all ages, specializing in classical and jazz techniques.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read Full Story</a>
                </div>
            </div>
        </div>
        <!-- Success Story 3 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://source.unsplash.com/random/100x100/?businessman" class="rounded-circle me-3" width="60" height="60" alt="Coach portrait">
                        <div>
                            <h5 class="card-title mb-0">David Patel</h5>
                            <p class="text-muted mb-0">Business Coach</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark p-2 me-2"><i class="bi bi-star-fill text-warning"></i> 4.7 Rating</span>
                        <span class="badge bg-light text-dark p-2"><i class="bi bi-people-fill text-primary"></i> 120+ Students</span>
                    </div>
                    <p class="card-text">"After 20 years in corporate management, I wanted to share my knowledge with others. EduCoach made the transition to coaching seamless, providing all the tools I needed to succeed."</p>
                    <p class="card-text text-muted">David coaches entrepreneurs and mid-level managers on leadership skills, strategic planning, and business development.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read Full Story</a>
                </div>
            </div>
        </div>
        <!-- Success Story 4 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://source.unsplash.com/random/100x100/?teacher" class="rounded-circle me-3" width="60" height="60" alt="Coach portrait">
                        <div>
                            <h5 class="card-title mb-0">Aisha Williams</h5>
                            <p class="text-muted mb-0">Science Coach</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark p-2 me-2"><i class="bi bi-star-fill text-warning"></i> 4.9 Rating</span>
                        <span class="badge bg-light text-dark p-2"><i class="bi bi-people-fill text-primary"></i> 90+ Students</span>
                    </div>
                    <p class="card-text">"As a former high school teacher, I found EduCoach to be the perfect platform to reach more students who need specialized science help. I now earn double my teaching salary with more flexibility."</p>
                    <p class="card-text text-muted">Aisha specializes in biology, chemistry, and physics coaching for high school and university students.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read Full Story</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Coach Metrics -->
    <div class="row text-center my-5">
        <div class="col-md-3 mb-4 mb-md-0">
            <h2 class="display-4 fw-bold text-primary">2,500+</h2>
            <p class="text-muted">Active Coaches</p>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <h2 class="display-4 fw-bold text-primary">€2.5M+</h2>
            <p class="text-muted">Coach Earnings 2023</p>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <h2 class="display-4 fw-bold text-primary">40+</h2>
            <p class="text-muted">Countries Represented</p>
        </div>
        <div class="col-md-3">
            <h2 class="display-4 fw-bold text-primary">4.8</h2>
            <p class="text-muted">Average Coach Rating</p>
        </div>
    </div>

    <!-- Become a Coach CTA -->
    <div class="card bg-primary text-white mt-5 border-0 shadow">
        <div class="card-body p-5 text-center">
            <h2 class="card-title mb-3">Ready to Start Your Success Story?</h2>
            <p class="card-text lead mb-4">Join thousands of coaches who have transformed their passion for teaching into a thriving online career.</p>
            <a href="become-coach.php" class="btn btn-light btn-lg">Become a Coach Today</a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?> 