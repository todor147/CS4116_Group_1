<?php
session_start();
require_once('../includes/db_connection.php');
require_once('../includes/notification_functions.php');

$title = "Help Center | EduCoach";
include('../includes/header.php');
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Help Center</h1>
        <p class="lead text-muted">Find answers and support for your EduCoach experience</p>
    </div>

    <!-- Quick Support Options -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-4">How can we help you today?</h4>
                    <div class="input-group mb-4">
                        <input type="text" class="form-control form-control-lg" id="helpSearch" placeholder="Search for answers..." aria-label="Search help articles">
                        <button class="btn btn-primary" type="button" id="searchButton">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <div class="col">
                            <a href="#account-section" class="text-decoration-none">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-person-circle text-primary mb-3" style="font-size: 2rem;"></i>
                                        <h5 class="card-title">Account Help</h5>
                                        <p class="card-text text-muted small">Login issues, account settings, and profile management</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="#booking-section" class="text-decoration-none">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-calendar-check text-primary mb-3" style="font-size: 2rem;"></i>
                                        <h5 class="card-title">Booking & Sessions</h5>
                                        <p class="card-text text-muted small">Schedule sessions, manage bookings, and session details</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="#payment-section" class="text-decoration-none">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-credit-card text-primary mb-3" style="font-size: 2rem;"></i>
                                        <h5 class="card-title">Payments & Billing</h5>
                                        <p class="card-text text-muted small">Payment methods, invoices, and subscription information</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Help Topics -->
    <h2 class="border-bottom pb-2 mb-4">Popular Help Topics</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4 mb-5">
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-question-circle text-primary me-2"></i>How do I reschedule a session?</h5>
                    <p class="card-text">To reschedule a booked session, navigate to your dashboard, find the session in "Upcoming Sessions," and click "Reschedule." Select a new time from available slots, and confirm your changes.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-question-circle text-primary me-2"></i>How do refunds work?</h5>
                    <p class="card-text">Refunds are processed according to our cancellation policy. For cancellations more than 24 hours before the session, you'll receive a full refund. Less than 24 hours, a 50% refund may apply.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-question-circle text-primary me-2"></i>Technical requirements for sessions</h5>
                    <p class="card-text">For the best experience, use a device with a webcam and microphone, ensure a stable internet connection (minimum 5Mbps), and use an updated browser like Chrome, Firefox, Safari, or Edge.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-question-circle text-primary me-2"></i>How to become a coach</h5>
                    <p class="card-text">To become a coach, create an account, click "Become a Coach," complete your profile with qualifications and experience, set your availability and pricing, then submit for review.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Categories -->
    <div id="account-section" class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Account Help</h2>
        <div class="accordion" id="accountAccordion">
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        How do I reset my password?
                    </button>
                </h3>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accountAccordion">
                    <div class="accordion-body">
                        <p>To reset your password:</p>
                        <ol>
                            <li>Click on "Login" at the top right of the page</li>
                            <li>Select "Forgot Password" below the login form</li>
                            <li>Enter the email address associated with your account</li>
                            <li>Check your email for a password reset link</li>
                            <li>Click the link and follow the instructions to create a new password</li>
                        </ol>
                        <p>The password reset link expires after 24 hours. If you don't receive an email, check your spam folder or contact support.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        How do I update my profile information?
                    </button>
                </h3>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accountAccordion">
                    <div class="accordion-body">
                        <p>To update your profile information:</p>
                        <ol>
                            <li>Log in to your EduCoach account</li>
                            <li>Click on your profile picture in the top right corner</li>
                            <li>Select "Profile" from the dropdown menu</li>
                            <li>Click "Edit Profile" to make changes</li>
                            <li>Update your information and click "Save Changes"</li>
                        </ol>
                        <p>You can update your name, profile picture, bio, contact information, and other details. Some information (like your email address) may require verification before changes take effect.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        How do I delete my account?
                    </button>
                </h3>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accountAccordion">
                    <div class="accordion-body">
                        <p>To delete your EduCoach account:</p>
                        <ol>
                            <li>Log in to your account</li>
                            <li>Click on your profile picture in the top right corner</li>
                            <li>Select "Settings" from the dropdown menu</li>
                            <li>Scroll down to the bottom of the page</li>
                            <li>Click on "Delete Account"</li>
                            <li>Follow the confirmation steps to permanently delete your account</li>
                        </ol>
                        <p><strong>Important:</strong> Account deletion is permanent and cannot be undone. All your data, including session history, messages, and profile information will be removed. Any upcoming scheduled sessions will be canceled automatically.</p>
                        <p>If you have an active subscription, please cancel it before deleting your account to avoid future charges.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="booking-section" class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Booking & Sessions</h2>
        <div class="accordion" id="bookingAccordion">
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        How do I book a session with a coach?
                    </button>
                </h3>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#bookingAccordion">
                    <div class="accordion-body">
                        <p>To book a session with a coach:</p>
                        <ol>
                            <li>Browse coaches or use the search function to find a coach in your area of interest</li>
                            <li>Click on a coach's profile to view their details, specialties, and pricing</li>
                            <li>Click the "Book a Session" button on their profile</li>
                            <li>Select the type of session you want (if the coach offers multiple options)</li>
                            <li>Choose an available date and time from the coach's calendar</li>
                            <li>Add any specific information or questions for the coach in the notes section</li>
                            <li>Review your booking details and proceed to payment</li>
                            <li>Once payment is complete, your session will be confirmed</li>
                        </ol>
                        <p>You'll receive a confirmation email with session details and a calendar invitation. You can view all your upcoming sessions in your dashboard.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        How do I join a scheduled session?
                    </button>
                </h3>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#bookingAccordion">
                    <div class="accordion-body">
                        <p>To join your scheduled session:</p>
                        <ol>
                            <li>Log in to your EduCoach account</li>
                            <li>Navigate to your dashboard</li>
                            <li>Find the session under "Upcoming Sessions"</li>
                            <li>Click "Join Session" (this button will become active 5-10 minutes before the scheduled start time)</li>
                            <li>Allow access to your camera and microphone when prompted</li>
                            <li>Wait for your coach to join if they haven't already</li>
                        </ol>
                        <p>You'll also receive an email reminder 24 hours before your session and another reminder 15 minutes before the session starts, both containing a direct link to join.</p>
                        <p><strong>Troubleshooting:</strong> If you experience technical issues, try refreshing the page, checking your internet connection, or using a different browser. You can also contact support for immediate assistance.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        How do I cancel or reschedule a session?
                    </button>
                </h3>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#bookingAccordion">
                    <div class="accordion-body">
                        <p>To cancel or reschedule a session:</p>
                        <ol>
                            <li>Log in to your EduCoach account</li>
                            <li>Go to your dashboard</li>
                            <li>Find the session under "Upcoming Sessions"</li>
                            <li>Click "Manage Session" and select either "Cancel" or "Reschedule"</li>
                        </ol>
                        <p><strong>For cancellations:</strong></p>
                        <ul>
                            <li>If you cancel more than 24 hours before the session, you'll receive a full refund</li>
                            <li>If you cancel less than 24 hours before the session, you'll receive a 50% refund</li>
                            <li>No-shows are not eligible for refunds</li>
                        </ul>
                        <p><strong>For rescheduling:</strong></p>
                        <ul>
                            <li>Select a new date and time from the available slots</li>
                            <li>Confirm your new session time</li>
                            <li>You'll receive an email confirmation with the updated details</li>
                        </ul>
                        <p>Rescheduling is free if done more than 24 hours in advance. Late rescheduling (less than 24 hours before) may incur a small fee.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="payment-section" class="mb-5">
        <h2 class="border-bottom pb-2 mb-4">Payments & Billing</h2>
        <div class="accordion" id="paymentAccordion">
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        What payment methods do you accept?
                    </button>
                </h3>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#paymentAccordion">
                    <div class="accordion-body">
                        <p>EduCoach accepts the following payment methods:</p>
                        <ul>
                            <li><strong>Credit/Debit Cards:</strong> Visa, Mastercard, American Express, Discover</li>
                            <li><strong>Digital Payments:</strong> PayPal, Apple Pay, Google Pay</li>
                            <li><strong>Bank Transfers:</strong> Available in select countries</li>
                        </ul>
                        <p>All payments are processed securely through our payment partners. Your payment information is encrypted and never stored on our servers.</p>
                        <p>You can manage your payment methods in your account settings under "Payment Methods."</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        How do I update my billing information?
                    </button>
                </h3>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#paymentAccordion">
                    <div class="accordion-body">
                        <p>To update your billing information:</p>
                        <ol>
                            <li>Log in to your EduCoach account</li>
                            <li>Click on your profile picture in the top right corner</li>
                            <li>Select "Settings" from the dropdown menu</li>
                            <li>Click on "Payment Methods" in the sidebar</li>
                            <li>To update an existing payment method, click "Edit" next to it</li>
                            <li>To add a new payment method, click "Add Payment Method"</li>
                            <li>Enter your information and click "Save"</li>
                        </ol>
                        <p>You can also update your billing address and contact information in the "Billing Information" section of your payment settings.</p>
                        <p>If you have an active subscription, any changes to your payment methods will be applied to future payments automatically.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        How do subscriptions work?
                    </button>
                </h3>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#paymentAccordion">
                    <div class="accordion-body">
                        <p>EduCoach offers subscription plans that provide benefits like discounted session rates, priority booking, and access to premium features.</p>
                        <p><strong>Subscription Details:</strong></p>
                        <ul>
                            <li>Subscriptions are billed monthly or annually (with annual discounts)</li>
                            <li>Your subscription renews automatically until cancelled</li>
                            <li>You can upgrade, downgrade, or cancel your subscription at any time</li>
                            <li>Changes to your subscription take effect at the next billing cycle</li>
                        </ul>
                        <p><strong>To manage your subscription:</strong></p>
                        <ol>
                            <li>Log in to your account</li>
                            <li>Go to "Settings" > "Subscription"</li>
                            <li>View your current plan, next billing date, and payment history</li>
                            <li>Select "Change Plan" to upgrade or downgrade</li>
                            <li>Select "Cancel Subscription" to end your subscription</li>
                        </ol>
                        <p>If you cancel, you'll continue to have access to your subscription benefits until the end of your current billing period.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="card bg-light border-0 mt-5">
        <div class="card-body p-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-3">Still Need Help?</h2>
                    <p class="lead mb-4">Our support team is ready to assist you with any questions or issues you may have.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope-fill text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">Email Support</h5>
                                    <p class="mb-0"><a href="mailto:support@educoach.com" class="text-decoration-none">support@educoach.com</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-chat-dots-fill text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h5 class="mb-1">Live Chat</h5>
                                    <p class="mb-0">Available 24/7 in your dashboard</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="contact.php" class="btn btn-primary">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-center">
                    <img src="https://source.unsplash.com/random/300x300/?customer-service" class="img-fluid rounded-circle" alt="Customer Support">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add search functionality JavaScript before the footer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('helpSearch');
    const searchButton = document.getElementById('searchButton');
    const accordionItems = document.querySelectorAll('.accordion-item');
    const accordionSections = document.querySelectorAll('#account-section, #booking-section, #payment-section');
    const popularTopics = document.querySelectorAll('.row.row-cols-1.row-cols-md-2.g-4.mb-5 .card');
    const noResultsMessage = document.createElement('div');
    
    noResultsMessage.className = 'alert alert-info mt-4';
    noResultsMessage.innerHTML = '<i class="bi bi-info-circle me-2"></i>No results found. Please try different keywords or <a href="contact.php">contact our support team</a>.';
    noResultsMessage.style.display = 'none';
    document.querySelector('.container.py-5').appendChild(noResultsMessage);

    // Function to perform search
    function performSearch() {
        const query = searchInput.value.toLowerCase().trim();
        let resultsFound = false;
        
        if (query === '') {
            // If search is empty, show everything
            resetSearch();
            return;
        }
        
        // Hide all accordion sections initially
        accordionSections.forEach(section => {
            section.style.display = 'none';
        });
        
        // Search in accordion items
        accordionItems.forEach(item => {
            const title = item.querySelector('.accordion-header button').textContent.toLowerCase();
            const content = item.querySelector('.accordion-body').textContent.toLowerCase();
            const isMatch = title.includes(query) || content.includes(query);
            
            // Show/hide based on search
            item.style.display = isMatch ? 'block' : 'none';
            
            // Expand the accordion if it matches
            if (isMatch) {
                resultsFound = true;
                const collapseId = item.querySelector('.accordion-collapse').id;
                const myCollapse = new bootstrap.Collapse(document.getElementById(collapseId), {
                    show: true
                });
                
                // Make parent section visible
                const parentSection = item.closest('#account-section, #booking-section, #payment-section');
                if (parentSection) {
                    parentSection.style.display = 'block';
                }
                
                // Highlight matching text
                const body = item.querySelector('.accordion-body');
                const headerText = item.querySelector('.accordion-header button');
                headerText.innerHTML = highlightText(headerText.textContent, query);
                body.innerHTML = highlightText(body.innerHTML, query);
            }
        });
        
        // Search in popular topics
        popularTopics.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const content = card.querySelector('.card-text').textContent.toLowerCase();
            const isMatch = title.includes(query) || content.includes(query);
            
            // Show/hide based on search
            card.parentElement.style.display = isMatch ? 'block' : 'none';
            
            if (isMatch) {
                resultsFound = true;
                
                // Highlight matching text
                const cardTitle = card.querySelector('.card-title');
                const cardText = card.querySelector('.card-text');
                cardTitle.innerHTML = highlightText(cardTitle.textContent, query);
                cardText.innerHTML = highlightText(cardText.textContent, query);
            }
        });
        
        // Show no results message if needed
        noResultsMessage.style.display = resultsFound ? 'none' : 'block';
        
        // If results were found, scroll to the first result
        if (resultsFound) {
            const firstVisible = document.querySelector('.accordion-item[style="display: block"], .col .card:not([style="display: none"])');
            if (firstVisible) {
                firstVisible.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }
    
    // Function to highlight matching text
    function highlightText(text, query) {
        if (typeof text !== 'string') return text;
        const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
        return text.replace(regex, '<mark class="bg-warning">$1</mark>');
    }
    
    // Function to escape special regex characters
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Function to reset search and show everything
    function resetSearch() {
        accordionSections.forEach(section => {
            section.style.display = 'block';
        });
        
        accordionItems.forEach(item => {
            item.style.display = 'block';
            
            // Reset expanded state
            const collapseEl = item.querySelector('.accordion-collapse.collapse.show');
            if (collapseEl && !collapseEl.classList.contains('show')) {
                const myCollapse = new bootstrap.Collapse(collapseEl, {
                    hide: true
                });
            }
            
            // Remove highlighting
            const headerText = item.querySelector('.accordion-header button');
            const body = item.querySelector('.accordion-body');
            headerText.innerHTML = headerText.textContent;
            body.innerHTML = body.innerHTML.replace(/<mark class="bg-warning">(.*?)<\/mark>/g, '$1');
        });
        
        popularTopics.forEach(card => {
            card.parentElement.style.display = 'block';
            
            // Remove highlighting
            const cardTitle = card.querySelector('.card-title');
            const cardText = card.querySelector('.card-text');
            cardTitle.innerHTML = cardTitle.textContent;
            cardText.innerHTML = cardText.textContent;
        });
        
        noResultsMessage.style.display = 'none';
    }
    
    // Event listeners
    searchButton.addEventListener('click', performSearch);
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        } else if (searchInput.value.trim() === '') {
            resetSearch();
        }
    });
    
    // Clear search when user clicks on a link to a section
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function() {
            searchInput.value = '';
            resetSearch();
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?> 