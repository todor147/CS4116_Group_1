<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4">Terms of Service</h1>
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4">1. Acceptance of Terms</h2>
                    <p>Welcome to EduCoach. By accessing or using our website, mobile applications, and services (collectively, the "Services"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, please do not use our Services.</p>
                    <p>These Terms constitute a legally binding agreement between you and EduCoach regarding your use of the Services. Please read them carefully.</p>

                    <h2 class="h4 mt-5 mb-4">2. Eligibility</h2>
                    <p>You must be at least 13 years old to use our Services. If you are under 18, you represent that you have your parent or guardian's permission to use the Services and that they have read and agreed to these Terms on your behalf.</p>
                    <p>By using our Services, you represent and warrant that you meet all eligibility requirements we outline in these Terms. We may still refuse to let certain people access or use the Services, however, and we reserve the right to change our eligibility criteria at any time.</p>

                    <h2 class="h4 mt-5 mb-4">3. Accounts and Registration</h2>
                    <p>To access certain features of our Services, you may be required to register for an account. When you register, you agree to:</p>
                    <ul>
                        <li>Provide accurate, current, and complete information</li>
                        <li>Maintain and promptly update your account information</li>
                        <li>Maintain the security of your account</li>
                        <li>Not share your account credentials with others</li>
                        <li>Promptly notify EduCoach if you discover or suspect any security breaches related to your account</li>
                    </ul>
                    <p>You are responsible for all activities that occur under your account, and EduCoach is not liable for any loss or damage arising from your failure to comply with the above requirements.</p>

                    <h2 class="h4 mt-5 mb-4">4. User Conduct</h2>
                    <p>When using our Services, you agree not to:</p>
                    <ul>
                        <li>Violate any applicable law, contract, intellectual property, or other third-party right</li>
                        <li>Engage in any harassing, threatening, intimidating, predatory, or stalking conduct</li>
                        <li>Use or attempt to use another user's account without authorization</li>
                        <li>Impersonate any person or entity or misrepresent your affiliation with a person or entity</li>
                        <li>Send spam or use automated methods to use our Services</li>
                        <li>Attempt to disrupt or tamper with our servers</li>
                        <li>Engage in any other conduct that restricts or inhibits anyone's use or enjoyment of the Services</li>
                    </ul>

                    <h2 class="h4 mt-5 mb-4">5. Coaching Services</h2>
                    <p><strong>5.1 Coach Qualifications</strong></p>
                    <p>While we strive to ensure that coaches on our platform are qualified, we do not guarantee the qualifications, expertise, or abilities of any coach. Users are responsible for verifying a coach's qualifications before engaging their services.</p>
                    
                    <p><strong>5.2 Scheduling and Cancellations</strong></p>
                    <p>Scheduling and cancellation policies may vary by coach. Users agree to abide by the specific policies set by the coaches they work with. In general, cancellations made within 24 hours of a scheduled session may be subject to cancellation fees.</p>
                    
                    <p><strong>5.3 Communication</strong></p>
                    <p>All communications between users and coaches should be conducted through our platform to ensure the safety and quality of interactions.</p>

                    <h2 class="h4 mt-5 mb-4">6. Payments and Fees</h2>
                    <p>Fees for coaching services are set by individual coaches and are displayed on their profiles. EduCoach charges a service fee for facilitating connections between learners and coaches.</p>
                    <p>By using our Services, you agree to pay all applicable fees and charges. All fees are non-refundable except as expressly provided in these Terms or as required by applicable law.</p>

                    <h2 class="h4 mt-5 mb-4">7. Intellectual Property Rights</h2>
                    <p>The Services and all content, features, and functionality thereof, including but not limited to text, graphics, logos, icons, images, audio clips, video clips, data compilations, and software, are the property of EduCoach or our licensors and are protected by copyright, trademark, and other intellectual property laws.</p>
                    <p>You may not reproduce, distribute, modify, create derivative works of, publicly display, publicly perform, republish, download, store, or transmit any of the material on our Services, except as generally and ordinarily permitted through the Services according to these Terms.</p>

                    <h2 class="h4 mt-5 mb-4">8. Disclaimer of Warranties</h2>
                    <p>THE SERVICES ARE PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED. TO THE FULLEST EXTENT PERMISSIBLE UNDER APPLICABLE LAW, EDUCOACH DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>

                    <h2 class="h4 mt-5 mb-4">9. Limitation of Liability</h2>
                    <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, EDUCOACH SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS, DATA, OR USE, ARISING OUT OF OR IN CONNECTION WITH THESE TERMS OR THE USE OR INABILITY TO USE THE SERVICES, EVEN IF EDUCOACH HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.</p>

                    <h2 class="h4 mt-5 mb-4">10. Changes to Terms</h2>
                    <p>We may revise these Terms from time to time. The most current version will always be posted on our website. If a revision, in our sole discretion, is material, we will notify you via email or through the Services. By continuing to access or use the Services after revisions become effective, you agree to be bound by the revised Terms.</p>

                    <h2 class="h4 mt-5 mb-4">11. Termination</h2>
                    <p>We may terminate or suspend your access to all or part of the Services, without prior notice or liability, for any reason, including if you breach these Terms. Upon termination, your right to use the Services will immediately cease.</p>

                    <h2 class="h4 mt-5 mb-4">12. Governing Law</h2>
                    <p>These Terms and your use of the Services shall be governed by and construed in accordance with the laws of the jurisdiction in which EduCoach is established, without regard to its conflict of law provisions.</p>

                    <h2 class="h4 mt-5 mb-4">13. Contact Information</h2>
                    <p>If you have any questions about these Terms, please contact us at:</p>
                    <address>
                        EduCoach<br>
                        Email: terms@educoach.com<br>
                        Address: 123 Learning Lane, Education City, EC 12345
                    </address>
                </div>
            </div>

            <div class="text-center mt-4 mb-5">
                <a href="privacy.php" class="btn btn-outline-primary">View Privacy Policy</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 