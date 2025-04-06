<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4">About EduCoach</h1>
            <p class="lead text-muted">A CS4116 University of Limerick Student Project</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="row">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <img src="../assets/images/about-team.jpg" alt="EduCoach Project" class="img-fluid rounded shadow-sm" 
                                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';">
                        </div>
                        <div class="col-md-6">
                            <h2 class="h3 mb-4">Project Background</h2>
                            <p>EduCoach is a student project developed for the CS4116 module at the University of Limerick under the supervision of Professor Conor Ryan. Our team aimed to create a comprehensive educational service marketplace that connects learners with expert coaches.</p>
                            <p>This platform represents our application of software development principles, database design, and web technologies to solve a real-world problem: making personalized education more accessible through a user-friendly online platform.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <h2 class="h3 text-center mb-4">Project Mission</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-mortarboard text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-4 mb-3">Personalized Learning</h5>
                            <p class="text-muted">We designed the platform to support education tailored to each learner's unique needs, learning style, and goals.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-globe text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-4 mb-3">Connecting People</h5>
                            <p class="text-muted">EduCoach breaks down barriers by connecting learners with coaches, creating a vibrant educational marketplace.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-star text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-4 mb-3">Quality Implementation</h5>
                            <p class="text-muted">We've implemented features like reviews and verification to ensure quality educational experiences on the platform.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <h2 class="h3 text-center mb-4">Development Team</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-circle text-primary mb-3" style="font-size: 4rem;"></i>
                            <h5>Todor Aleksandrov</h5>
                            <p class="text-muted mb-2">Team Member</p>
                            <p class="small text-muted">Contributed to system architecture and backend development</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-circle text-primary mb-3" style="font-size: 4rem;"></i>
                            <h5>Rian Quinn</h5>
                            <p class="text-muted mb-2">Team Member</p>
                            <p class="small text-muted">Focused on frontend design and user experience</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-circle text-primary mb-3" style="font-size: 4rem;"></i>
                            <h5>Fionn Clancy Molloy</h5>
                            <p class="text-muted mb-2">Team Member</p>
                            <p class="small text-muted">Specialized in database design and API implementation</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-circle text-primary mb-3" style="font-size: 4rem;"></i>
                            <h5>Darragh Kennedy</h5>
                            <p class="text-muted mb-2">Team Member</p>
                            <p class="small text-muted">Led testing and documentation efforts</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="text-muted">This project was developed under the supervision of <strong>Professor Conor Ryan</strong>, University of Limerick</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="h3 text-center mb-4">Technical Implementation</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-code-slash text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <h5>Technology Stack</h5>
                                    <p class="text-muted">Built using HTML5, CSS3, Bootstrap 5, vanilla JavaScript, PHP, and MySQL database.</p>
                                </div>
                            </div>
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-search text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <h5>Advanced Search</h5>
                                    <p class="text-muted">Implemented SQL-based search with filters for expertise, pricing, and coach ratings.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-shield-check text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <h5>Security Focus</h5>
                                    <p class="text-muted">Implemented input validation, prepared statements, and proper password hashing for security.</p>
                                </div>
                            </div>
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-people text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <h5>User Experience</h5>
                                    <p class="text-muted">Designed with responsive interfaces and intuitive workflows for both learners and coaches.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-8 mx-auto text-center">
            <h4>Core Features</h4>
            <p class="text-muted">This educational platform includes user registration, service listings, messaging, reviews, search capabilities, and more.</p>
            <div class="mt-4">
                <a href="coach-search.php" class="btn btn-primary me-2">Try the Platform</a>
                <a href="https://github.com/todor147/CS4116_Group_1" class="btn btn-outline-primary" target="_blank">View Project on GitHub</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 