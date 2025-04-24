<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Coach Community | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Coach Community</h1>
        <p class="lead text-muted">Connect, collaborate, and grow with fellow educators</p>
    </div>

    <!-- Community Highlights -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body py-4">
                    <i class="bi bi-people-fill text-primary mb-3" style="font-size: 3rem;"></i>
                    <h3 class="card-title">5,000+</h3>
                    <p class="text-muted">Active Members</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body py-4">
                    <i class="bi bi-globe text-primary mb-3" style="font-size: 3rem;"></i>
                    <h3 class="card-title">80+</h3>
                    <p class="text-muted">Countries Represented</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body py-4">
                    <i class="bi bi-calendar-event text-primary mb-3" style="font-size: 3rem;"></i>
                    <h3 class="card-title">50+</h3>
                    <p class="text-muted">Monthly Events</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How to Join -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h2 class="mb-4">How to Join Our Community</h2>
            <p class="lead">The EduCoach Community is exclusively available to verified coaches on our platform.</p>
            <p>Once you're approved as a coach, you'll automatically gain access to:</p>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Discussion forums and groups</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Monthly virtual meetups</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Resource sharing platform</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Mentor matching program</li>
                <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Professional development workshops</li>
            </ul>
            <a href="become-coach.php" class="btn btn-primary">Become a Coach</a>
        </div>
        <div class="col-lg-6">
            <p>Connect with thousands of coaches from around the world, share resources, attend virtual events, and grow together in our vibrant coaching community.</p>
            <p>Whether you're an experienced educator or just starting your coaching journey, the EduCoach community offers valuable connections and resources to help you succeed.</p>
            <img src="<?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/images/community-banner.jpg') ? '/assets/images/community-banner.jpg' : 'https://ui-avatars.com/api/?name=Community&size=400&background=random' ?>" class="img-fluid rounded shadow" alt="Community meeting">
        </div>
    </div>

    <!-- Community Features -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Community Benefits</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-chat-dots text-primary me-2"></i>Dedicated Forums</h5>
                        <p class="card-text">Exchange ideas, ask questions, and share experiences in specialized topic forums ranging from teaching methods to business growth.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-lightbulb text-primary me-2"></i>Peer Collaboration</h5>
                        <p class="card-text">Find collaboration partners for joint sessions, co-created courses, or cross-promotion opportunities with coaches in complementary fields.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-laptop text-primary me-2"></i>Virtual Events</h5>
                        <p class="card-text">Participate in weekly webinars, monthly panel discussions, and quarterly virtual conferences featuring industry experts.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-stars text-primary me-2"></i>Mentorship Program</h5>
                        <p class="card-text">Connect with experienced coaches as a mentee, or give back to the community by becoming a mentor for new coaches.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-book text-primary me-2"></i>Resource Library</h5>
                        <p class="card-text">Access community-created resources including session templates, assessment tools, and marketing materials.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-trophy text-primary me-2"></i>Recognition Program</h5>
                        <p class="card-text">Earn badges, awards, and special recognition for your contributions to the community and coaching excellence.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Community Groups -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Featured Community Groups</h2>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Language Coaches Circle</h5>
                        <span class="badge bg-primary mb-2">500+ Members</span>
                        <p class="card-text">A vibrant group for language tutors to share teaching methods, cultural insights, and resources for effective language acquisition.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="avatar-group">
                                <?php 
                                function getProfileImage($index) {
                                    $default = '/assets/images/profiles/default.jpg';
                                    // Use UI Avatars as reliable fallback
                                    return file_exists($_SERVER['DOCUMENT_ROOT'] . $default) ? 
                                        $default : 
                                        "https://ui-avatars.com/api/?name=User" . $index . "&background=random&size=100";
                                }
                                ?>
                                <img src="<?= getProfileImage(1) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(2) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(3) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <span class="rounded-circle bg-light text-dark d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">+497</span>
                            </div>
                            <span class="ms-auto badge bg-light text-dark">
                                <i class="bi bi-chat-text"></i> Active Now
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">STEM Educators Hub</h5>
                        <span class="badge bg-primary mb-2">750+ Members</span>
                        <p class="card-text">Connect with science, technology, engineering, and mathematics coaches to share interactive teaching tools and latest research.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="avatar-group">
                                <img src="<?= getProfileImage(4) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(5) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(6) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <span class="rounded-circle bg-light text-dark d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">+747</span>
                            </div>
                            <span class="ms-auto badge bg-light text-dark">
                                <i class="bi bi-chat-text"></i> Active Now
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Business & Career Coaches</h5>
                        <span class="badge bg-primary mb-2">600+ Members</span>
                        <p class="card-text">A professional network for coaches specializing in career development, entrepreneurship, and professional skills training.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="avatar-group">
                                <img src="<?= getProfileImage(7) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(8) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(9) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <span class="rounded-circle bg-light text-dark d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">+597</span>
                            </div>
                            <span class="ms-auto badge bg-light text-dark">
                                <i class="bi bi-chat-text"></i> Active Now
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Creative Arts Mentors</h5>
                        <span class="badge bg-primary mb-2">450+ Members</span>
                        <p class="card-text">A creative space for music, art, writing, and performing arts coaches to share inspiration and teaching methodologies.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="avatar-group">
                                <img src="<?= getProfileImage(10) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(11) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <img src="<?= getProfileImage(12) ?>" class="rounded-circle me-1" width="30" height="30" alt="Member" style="object-fit: cover;">
                                <span class="rounded-circle bg-light text-dark d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">+447</span>
                            </div>
                            <span class="ms-auto badge bg-light text-dark">
                                <i class="bi bi-chat-text"></i> Active Now
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Upcoming Community Events</h2>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 text-center" style="min-width: 60px;">
                                <div class="fw-bold">15</div>
                                <small>MAY</small>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Virtual Coffee Hour: Connecting Across Time Zones</h5>
                                <p class="text-muted mb-0"><i class="bi bi-clock me-1"></i> 3:00 PM - 4:00 PM (UTC)</p>
                            </div>
                        </div>
                        <p class="card-text">Join fellow coaches for a casual networking session designed to connect educators across different time zones. Share experiences, challenges, and success stories in a relaxed virtual environment.</p>
                        <span class="badge bg-light text-dark mb-2">Networking</span>
                        <span class="badge bg-light text-dark mb-2">Community Building</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 text-center" style="min-width: 60px;">
                                <div class="fw-bold">22</div>
                                <small>MAY</small>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Workshop: Effective Online Teaching Techniques</h5>
                                <p class="text-muted mb-0"><i class="bi bi-clock me-1"></i> 2:00 PM - 4:00 PM (UTC)</p>
                            </div>
                        </div>
                        <p class="card-text">Learn practical strategies for engaging students in virtual environments, managing online classroom dynamics, and utilizing digital tools to enhance learning outcomes.</p>
                        <span class="badge bg-light text-dark mb-2">Professional Development</span>
                        <span class="badge bg-light text-dark mb-2">Teaching Methods</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 text-center" style="min-width: 60px;">
                                <div class="fw-bold">29</div>
                                <small>MAY</small>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Panel Discussion: Growing Your Coaching Business</h5>
                                <p class="text-muted mb-0"><i class="bi bi-clock me-1"></i> 5:00 PM - 6:30 PM (UTC)</p>
                            </div>
                        </div>
                        <p class="card-text">Hear from successful coaches who have scaled their businesses on EduCoach. Topics include marketing strategies, client retention, and creating additional revenue streams through your expertise.</p>
                        <span class="badge bg-light text-dark mb-2">Business Growth</span>
                        <span class="badge bg-light text-dark mb-2">Marketing</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 text-center" style="min-width: 60px;">
                                <div class="fw-bold">5</div>
                                <small>JUN</small>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Webinar: Student Assessment in Virtual Environments</h5>
                                <p class="text-muted mb-0"><i class="bi bi-clock me-1"></i> 1:00 PM - 2:30 PM (UTC)</p>
                            </div>
                        </div>
                        <p class="card-text">Discover innovative approaches to assessing student progress and understanding in online coaching sessions. Learn about digital assessment tools and strategies for meaningful feedback.</p>
                        <span class="badge bg-light text-dark mb-2">Assessment</span>
                        <span class="badge bg-light text-dark mb-2">Teaching Tools</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="become-coach.php" class="btn btn-outline-primary">Join Community to See All Events</a>
        </div>
    </div>

    <!-- Community Testimonials -->
    <div class="mt-5">
        <h2 class="border-bottom pb-2 mb-4">Community Member Testimonials</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100 bg-light border-0">
                    <div class="card-body p-4">
                        <p class="card-text lead mb-4">"The EduCoach community has been instrumental in my growth as an online educator. The resources shared and connections made have helped me improve my teaching methods and grow my business."</p>
                        <div class="d-flex align-items-center">
                            <img src="<?= getProfileImage('maria') ?>" class="rounded-circle me-3" width="50" height="50" alt="Coach portrait" style="object-fit: cover;">
                            <div>
                                <h5 class="card-title mb-0">Maria Santos</h5>
                                <p class="text-muted mb-0">Language Coach, Member since 2021</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100 bg-light border-0">
                    <div class="card-body p-4">
                        <p class="card-text lead mb-4">"Being part of the STEM Educators Hub has connected me with brilliant minds across the globe. The collaborative projects and resource sharing have elevated my teaching to a whole new level."</p>
                        <div class="d-flex align-items-center">
                            <img src="<?= getProfileImage('david') ?>" class="rounded-circle me-3" width="50" height="50" alt="Coach portrait" style="object-fit: cover;">
                            <div>
                                <h5 class="card-title mb-0">David Chen</h5>
                                <p class="text-muted mb-0">Physics Coach, Member since 2020</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Join CTA -->
    <div class="card bg-primary text-white mt-5 border-0 shadow">
        <div class="card-body p-5 text-center">
            <h2 class="card-title mb-3">Ready to Join Our Community?</h2>
            <p class="card-text lead mb-4">Connect with fellow educators, share your expertise, and grow your coaching practice with the support of our global community.</p>
            <a href="become-coach.php" class="btn btn-light btn-lg">Become a Coach Today</a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?> 