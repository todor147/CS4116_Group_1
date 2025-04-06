<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4">Privacy Policy</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4">1. Introduction</h2>
                    <p>Welcome to EduCoach ("we," "our," or "us"). At EduCoach, we respect your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services.</p>
                    <p>Please read this Privacy Policy carefully. If you do not agree with the terms of this Privacy Policy, please do not access the site or use our services.</p>

                    <h2 class="h4 mt-5 mb-4">2. Information We Collect</h2>
                    <p>We collect several types of information from and about users of our website, including:</p>
                    <ul>
                        <li><strong>Personal Information:</strong> This includes your name, email address, postal address, phone number, and other identifiers that you provide when registering for an account or using our services.</li>
                        <li><strong>Profile Information:</strong> Information you provide in your user profile, such as your biography, skills, education, and professional experience.</li>
                        <li><strong>Session Data:</strong> Information about coaching sessions, including scheduling details, session notes, and feedback.</li>
                        <li><strong>Communications:</strong> Records of your communications with coaches, other users, and our support team through our platform.</li>
                        <li><strong>Technical Data:</strong> Information about your browser, device, IP address, and how you interact with our website.</li>
                        <li><strong>Payment Information:</strong> Details necessary to process payments, such as credit card numbers (which are processed by our secure payment providers).</li>
                    </ul>

                    <h2 class="h4 mt-5 mb-4">3. How We Use Your Information</h2>
                    <p>We use the information we collect for various purposes, including:</p>
                    <ul>
                        <li>Providing, operating, and maintaining our website and services</li>
                        <li>Matching learners with appropriate coaches</li>
                        <li>Processing transactions and managing payments</li>
                        <li>Sending administrative information, such as updates, security alerts, and support messages</li>
                        <li>Responding to your comments, questions, and requests</li>
                        <li>Personalizing your experience on our platform</li>
                        <li>Analyzing usage patterns to improve our services</li>
                        <li>Protecting against fraudulent or illegal activity</li>
                    </ul>

                    <h2 class="h4 mt-5 mb-4">4. Information Sharing</h2>
                    <p>We may share your information in the following situations:</p>
                    <ul>
                        <li><strong>With Coaches and Learners:</strong> When you connect with a coach or learner, we share information necessary to facilitate the coaching relationship.</li>
                        <li><strong>Service Providers:</strong> We may share your information with third-party vendors who provide services on our behalf, such as payment processing, data analysis, email delivery, and customer service.</li>
                        <li><strong>Legal Requirements:</strong> We may disclose your information if required to do so by law or in response to valid requests by public authorities.</li>
                        <li><strong>Business Transfers:</strong> If we are involved in a merger, acquisition, or sale of all or a portion of our assets, your information may be transferred as part of that transaction.</li>
                    </ul>

                    <h2 class="h4 mt-5 mb-4">5. Data Security</h2>
                    <p>We implement appropriate technical and organizational measures to protect your personal information. However, no method of transmission over the Internet or electronic storage is 100% secure, so we cannot guarantee absolute security.</p>

                    <h2 class="h4 mt-5 mb-4">6. Your Privacy Rights</h2>
                    <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
                    <ul>
                        <li>The right to access and receive a copy of your personal information</li>
                        <li>The right to rectify or update your personal information</li>
                        <li>The right to request deletion of your personal information</li>
                        <li>The right to restrict or object to processing of your personal information</li>
                        <li>The right to data portability</li>
                    </ul>
                    <p>To exercise these rights, please contact us using the information provided in the "Contact Us" section below.</p>

                    <h2 class="h4 mt-5 mb-4">7. Children's Privacy</h2>
                    <p>Our services are not intended for individuals under the age of 13. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us.</p>

                    <h2 class="h4 mt-5 mb-4">8. Changes to This Privacy Policy</h2>
                    <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date at the top of this Privacy Policy.</p>

                    <h2 class="h4 mt-5 mb-4">9. Contact Us</h2>
                    <p>If you have any questions or concerns about this Privacy Policy, please contact us at:</p>
                    <address>
                        EduCoach<br>
                        Email: privacy@educoach.com<br>
                        Address: 123 Learning Lane, Education City, EC 12345
                    </address>
                </div>
            </div>

            <div class="text-center mt-4 mb-5">
                <a href="terms.php" class="btn btn-outline-primary">View Terms of Service</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 