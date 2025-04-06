<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Coach FAQ | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Frequently Asked Questions for Coaches</h1>
        <p class="lead text-muted">Find answers to common questions about coaching on EduCoach</p>
    </div>

    <!-- Search Bar -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="faqSearch" placeholder="Search for a question...">
                <button class="btn btn-primary" type="button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- FAQ Categories -->
    <div class="d-flex justify-content-center flex-wrap mb-5">
        <button class="btn btn-outline-primary m-1 faq-category active" data-category="all">All</button>
        <button class="btn btn-outline-primary m-1 faq-category" data-category="getting-started">Getting Started</button>
        <button class="btn btn-outline-primary m-1 faq-category" data-category="payments">Payments</button>
        <button class="btn btn-outline-primary m-1 faq-category" data-category="scheduling">Scheduling</button>
        <button class="btn btn-outline-primary m-1 faq-category" data-category="technical">Technical</button>
        <button class="btn btn-outline-primary m-1 faq-category" data-category="policies">Policies</button>
    </div>

    <!-- FAQ Accordions -->
    <div class="accordion" id="faqAccordion">
        <!-- Getting Started Section -->
        <div class="faq-section" data-category="getting-started">
            <h2 class="border-bottom pb-2 mb-4">Getting Started</h2>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        How do I become a coach on EduCoach?
                    </button>
                </h3>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Becoming a coach on EduCoach is a straightforward process:</p>
                        <ol>
                            <li>Create an account or log in to your existing account</li>
                            <li>Click on "Become a Coach" in your dashboard</li>
                            <li>Complete your profile with your qualifications, experience, and expertise</li>
                            <li>Set your availability and pricing</li>
                            <li>Submit your profile for review</li>
                        </ol>
                        <p>Our team will review your application, and you'll typically receive a response within 2-3 business days.</p>
                        <a href="become-coach.php" class="btn btn-sm btn-primary mt-2">Apply Now</a>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        What qualifications do I need to become a coach?
                    </button>
                </h3>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>EduCoach welcomes coaches with various backgrounds and expertise levels. While formal qualifications can strengthen your profile, we also value practical experience and teaching ability.</p>
                        <p>We typically look for:</p>
                        <ul>
                            <li>Formal education or certification in your teaching area (when applicable)</li>
                            <li>Demonstrable expertise and experience in your field</li>
                            <li>Previous teaching or coaching experience (preferred but not always required)</li>
                            <li>Strong communication skills</li>
                        </ul>
                        <p>The most important factors are your expertise in your subject area and your ability to effectively teach others.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        How long does the approval process take?
                    </button>
                </h3>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Our team typically reviews coach applications within 2-3 business days. During peak periods, it may take up to 5 business days.</p>
                        <p>To speed up your approval process:</p>
                        <ul>
                            <li>Fill out your profile completely</li>
                            <li>Upload a professional profile photo</li>
                            <li>Provide detailed information about your expertise and experience</li>
                            <li>Include any relevant certifications or qualifications</li>
                        </ul>
                        <p>You'll receive an email notification once your application has been reviewed.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payments Section -->
        <div class="faq-section" data-category="payments">
            <h2 class="border-bottom pb-2 mb-4">Payments</h2>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        How and when do I get paid?
                    </button>
                </h3>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>EduCoach processes payments to coaches on a bi-weekly basis. Here's how it works:</p>
                        <ul>
                            <li>Sessions are paid for by students in advance</li>
                            <li>Funds are held securely until 24 hours after the session is completed</li>
                            <li>After the 24-hour period, the payment enters your available balance</li>
                            <li>Available balances are paid out every two weeks on Monday</li>
                            <li>You'll receive your funds via your chosen payment method (PayPal, bank transfer, etc.)</li>
                        </ul>
                        <p>You can view your upcoming payouts and payment history in your coach dashboard under "Earnings".</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        What fees does EduCoach charge?
                    </button>
                </h3>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>EduCoach charges a service fee on each session to cover platform maintenance, marketing, payment processing, and customer support.</p>
                        <p>The standard service fee is 15% of the session price. For example:</p>
                        <ul>
                            <li>If you charge €50 for a session, EduCoach's fee is €7.50</li>
                            <li>You receive €42.50 for the session</li>
                        </ul>
                        <p>Coaches with a high volume of sessions or who have been on the platform for an extended period may qualify for reduced fees. Contact our coach support team to learn more about our tiered fee structure.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        How do I set my rates?
                    </button>
                </h3>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>You have complete control over your coaching rates on EduCoach. When setting your rates, consider:</p>
                        <ul>
                            <li>Your expertise level and qualifications</li>
                            <li>Time and preparation required for each session</li>
                            <li>Market rates for your subject area</li>
                            <li>Your target student demographic</li>
                            <li>The 15% platform service fee</li>
                        </ul>
                        <p>You can set different rates for different session types (e.g., one-on-one, group sessions, specialized courses). You can also offer package rates for multiple sessions.</p>
                        <p>You can update your rates at any time through your coach dashboard, but rate changes won't affect already-booked sessions.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scheduling Section -->
        <div class="faq-section" data-category="scheduling">
            <h2 class="border-bottom pb-2 mb-4">Scheduling</h2>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        How do I set my availability?
                    </button>
                </h3>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Managing your availability on EduCoach is simple:</p>
                        <ol>
                            <li>Go to your Coach Dashboard</li>
                            <li>Click on "Availability" in the navigation menu</li>
                            <li>Set your regular weekly availability by selecting time slots</li>
                            <li>Add any exceptions for specific dates (holidays, time off, etc.)</li>
                            <li>Save your changes</li>
                        </ol>
                        <p>Students will only be able to book sessions during your specified available times. You can update your availability at any time, but changes won't affect already-booked sessions.</p>
                        <p>We recommend keeping your availability calendar up-to-date to maximize your booking potential and avoid scheduling conflicts.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        What happens if I need to cancel a session?
                    </button>
                </h3>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>We understand that emergencies and unexpected situations happen. If you need to cancel a session:</p>
                        <ol>
                            <li>Go to your Coach Dashboard</li>
                            <li>Navigate to "Upcoming Sessions"</li>
                            <li>Find the session you need to cancel</li>
                            <li>Click "Reschedule/Cancel"</li>
                            <li>Select "Cancel" and provide a reason for cancellation</li>
                            <li>The student will be notified automatically</li>
                        </ol>
                        <p><strong>Cancellation Policy:</strong></p>
                        <ul>
                            <li>Cancellations more than 24 hours before the session: No penalty</li>
                            <li>Cancellations less than 24 hours before the session: May affect your coach rating</li>
                            <li>Repeated last-minute cancellations: May result in account review</li>
                        </ul>
                        <p>Whenever possible, we recommend offering to reschedule rather than cancel completely.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        How far in advance can students book sessions?
                    </button>
                </h3>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>By default, students can book sessions up to 60 days in advance based on your availability calendar. You can customize this booking window in your coach settings:</p>
                        <ol>
                            <li>Go to your Coach Dashboard</li>
                            <li>Click on "Settings"</li>
                            <li>Navigate to "Booking Preferences"</li>
                            <li>Adjust the "Advance Booking Period" setting</li>
                            <li>Save your changes</li>
                        </ol>
                        <p>You can set your advance booking period anywhere from 7 to 90 days. Consider your scheduling needs and how far in advance you can reliably commit to sessions.</p>
                        <p>You can also set a minimum notice period (e.g., 24 hours) to prevent last-minute bookings if you need preparation time.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Technical Section -->
        <div class="faq-section" data-category="technical">
            <h2 class="border-bottom pb-2 mb-4">Technical Questions</h2>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingTen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                        What equipment do I need for online coaching?
                    </button>
                </h3>
                <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>To provide high-quality online coaching sessions, we recommend the following equipment:</p>
                        <p><strong>Essential equipment:</strong></p>
                        <ul>
                            <li>Reliable computer (desktop or laptop)</li>
                            <li>Stable internet connection (minimum 5 Mbps upload/download speed)</li>
                            <li>Webcam with 720p resolution or higher</li>
                            <li>Headset with microphone or separate microphone</li>
                            <li>Quiet, well-lit space</li>
                        </ul>
                        <p><strong>Recommended additional equipment:</strong></p>
                        <ul>
                            <li>Secondary display/monitor for viewing materials</li>
                            <li>Digital drawing tablet (for certain subjects)</li>
                            <li>Ring light or other supplemental lighting</li>
                            <li>USB or high-quality external microphone</li>
                        </ul>
                        <p>Our platform works best with Chrome, Firefox, Safari, or Edge browsers (latest versions).</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingEleven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEleven" aria-expanded="false" aria-controls="collapseEleven">
                        How do I conduct a coaching session on EduCoach?
                    </button>
                </h3>
                <div id="collapseEleven" class="accordion-collapse collapse" aria-labelledby="headingEleven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Conducting a session on EduCoach is straightforward:</p>
                        <ol>
                            <li><strong>Before the session:</strong>
                                <ul>
                                    <li>Prepare any necessary materials and upload them to the session page</li>
                                    <li>Test your equipment and internet connection</li>
                                    <li>Review any notes or requests from the student</li>
                                </ul>
                            </li>
                            <li><strong>Starting the session:</strong>
                                <ul>
                                    <li>Log in to your account 5-10 minutes before the scheduled time</li>
                                    <li>Go to your Dashboard and find the upcoming session</li>
                                    <li>Click "Start Session" when ready</li>
                                </ul>
                            </li>
                            <li><strong>During the session:</strong>
                                <ul>
                                    <li>Use our integrated video conferencing tool</li>
                                    <li>Share your screen to present materials when needed</li>
                                    <li>Utilize the interactive whiteboard for demonstrations</li>
                                    <li>Access shared documents in the session sidebar</li>
                                </ul>
                            </li>
                            <li><strong>Ending the session:</strong>
                                <ul>
                                    <li>Summarize key points and any homework or next steps</li>
                                    <li>Click "End Session" when finished</li>
                                    <li>Add session notes and recommendations for the student</li>
                                </ul>
                            </li>
                        </ol>
                        <p>For a detailed walkthrough, check out our <a href="#">Coach Video Guide</a>.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingTwelve">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwelve" aria-expanded="false" aria-controls="collapseTwelve">
                        What do I do if I experience technical issues during a session?
                    </button>
                </h3>
                <div id="collapseTwelve" class="accordion-collapse collapse" aria-labelledby="headingTwelve" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>If you encounter technical issues during a session:</p>
                        <ol>
                            <li><strong>For minor issues:</strong>
                                <ul>
                                    <li>Use the text chat feature to communicate with the student</li>
                                    <li>Try refreshing the page (inform the student first)</li>
                                    <li>Check your internet connection and restart your router if needed</li>
                                </ul>
                            </li>
                            <li><strong>For persistent issues:</strong>
                                <ul>
                                    <li>Try switching to our backup meeting link (available in the session controls)</li>
                                    <li>Suggest continuing the session via another platform temporarily (Zoom, Google Meet, etc.)</li>
                                    <li>If necessary, reschedule the session</li>
                                </ul>
                            </li>
                        </ol>
                        <p>After the session, report any technical issues to our support team via the "Report Issue" button on the session summary page. This helps us improve our platform.</p>
                        <p>For persistent technical problems, contact our coach support team at <a href="mailto:coach-support@educoach.com">coach-support@educoach.com</a>.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Policies Section -->
        <div class="faq-section" data-category="policies">
            <h2 class="border-bottom pb-2 mb-4">Policies</h2>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingThirteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThirteen" aria-expanded="false" aria-controls="collapseThirteen">
                        What is EduCoach's cancellation policy?
                    </button>
                </h3>
                <div id="collapseThirteen" class="accordion-collapse collapse" aria-labelledby="headingThirteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Our cancellation policy balances the needs of both coaches and students:</p>
                        <p><strong>For Coaches:</strong></p>
                        <ul>
                            <li>Cancellations more than 24 hours before the session: No penalty</li>
                            <li>Cancellations less than 24 hours before the session: May affect your coach rating</li>
                            <li>Repeated last-minute cancellations: May result in account review</li>
                        </ul>
                        <p><strong>For Students:</strong></p>
                        <ul>
                            <li>Cancellations more than 24 hours before the session: Full refund</li>
                            <li>Cancellations less than 24 hours before the session: 50% refund</li>
                            <li>No-shows: No refund</li>
                        </ul>
                        <p>Special circumstances (illness, emergencies, etc.) are handled on a case-by-case basis. Contact our support team if you need to cancel due to exceptional circumstances.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingFourteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFourteen" aria-expanded="false" aria-controls="collapseFourteen">
                        How does EduCoach handle disputes?
                    </button>
                </h3>
                <div id="collapseFourteen" class="accordion-collapse collapse" aria-labelledby="headingFourteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>EduCoach has a fair and transparent dispute resolution process:</p>
                        <ol>
                            <li><strong>Direct Resolution:</strong> We encourage coaches and students to resolve minor issues directly through our messaging system.</li>
                            <li><strong>Formal Dispute:</strong> If direct resolution isn't possible, either party can file a formal dispute through their dashboard.</li>
                            <li><strong>Review Process:</strong> Our support team will review the case, examining session logs, messages, and other relevant information.</li>
                            <li><strong>Mediation:</strong> For complex cases, our team may mediate a discussion between both parties.</li>
                            <li><strong>Resolution:</strong> We aim to resolve all disputes within 5-7 business days.</li>
                        </ol>
                        <p>To minimize disputes:</p>
                        <ul>
                            <li>Clearly communicate your teaching style and expectations</li>
                            <li>Keep all communication on the EduCoach platform</li>
                            <li>Document session activities and outcomes</li>
                            <li>Address concerns promptly and professionally</li>
                        </ul>
                        <p>Our goal is fair resolution that preserves positive relationships between coaches and students.</p>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item mb-3">
                <h3 class="accordion-header" id="headingFifteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFifteen" aria-expanded="false" aria-controls="collapseFifteen">
                        What are the coach quality standards at EduCoach?
                    </button>
                </h3>
                <div id="collapseFifteen" class="accordion-collapse collapse" aria-labelledby="headingFifteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>At EduCoach, we maintain high standards for our coaching community:</p>
                        <p><strong>Quality Expectations:</strong></p>
                        <ul>
                            <li>Maintain a minimum rating of 4.0/5.0 stars</li>
                            <li>Complete at least 80% of scheduled sessions</li>
                            <li>Respond to student inquiries within 24-48 hours</li>
                            <li>Provide accurate information about qualifications and expertise</li>
                            <li>Deliver sessions that align with described content and learning objectives</li>
                            <li>Maintain professional conduct and communication</li>
                        </ul>
                        <p><strong>Quality Monitoring:</strong></p>
                        <ul>
                            <li>Regular review of student feedback and ratings</li>
                            <li>Analysis of session completion rates and cancellations</li>
                            <li>Periodic review of coach profiles for accuracy</li>
                        </ul>
                        <p>Coaches who consistently fall below our standards may receive coaching, warnings, or in severe cases, account suspension. We provide resources and support to help all coaches meet and exceed our quality standards.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Still Have Questions -->
    <div class="card mt-5 bg-light border-0">
        <div class="card-body p-5 text-center">
            <h2 class="mb-4">Still Have Questions?</h2>
            <p class="lead mb-4">Our coach support team is here to help you succeed on EduCoach.</p>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-chat-dots text-primary mb-3" style="font-size: 2rem;"></i>
                            <h5>Coach Community</h5>
                            <p class="small">Connect with fellow coaches to share tips and advice</p>
                            <a href="community.php" class="btn btn-sm btn-outline-primary">Join Discussion</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-envelope text-primary mb-3" style="font-size: 2rem;"></i>
                            <h5>Email Support</h5>
                            <p class="small">Get personalized help from our coach success team</p>
                            <a href="mailto:coach-support@educoach.com" class="btn btn-sm btn-outline-primary">Contact Support</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-book text-primary mb-3" style="font-size: 2rem;"></i>
                            <h5>Resource Center</h5>
                            <p class="small">Access our comprehensive guides and tutorials</p>
                            <a href="coach-resources.php" class="btn btn-sm btn-outline-primary">View Resources</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('faqSearch');
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            accordionItems.forEach(item => {
                const questionText = item.querySelector('.accordion-button').textContent.toLowerCase();
                const answerText = item.querySelector('.accordion-body').textContent.toLowerCase();
                
                if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Category filtering
        const categoryButtons = document.querySelectorAll('.faq-category');
        const faqSections = document.querySelectorAll('.faq-section');
        
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update active button
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide relevant sections
                if (category === 'all') {
                    faqSections.forEach(section => section.style.display = 'block');
                } else {
                    faqSections.forEach(section => {
                        if (section.getAttribute('data-category') === category) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
            });
        });
    });
</script>

<?php include('../includes/footer.php'); ?> 