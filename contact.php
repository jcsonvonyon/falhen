<?php
// contact.php - Contact page matching falhen.com/contact screenshots
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Contact Us — Falhen Media";
require_once __DIR__ . '/includes/header.php';
?>

<style>
    body {
        background-color: #030305;
        color: #d4d4d8;
    }

    /* Hero Section */
    .contact-hero-stage {
        position: relative;
        padding: 150px 0 60px 0;
        background: linear-gradient(180deg, rgba(3, 3, 5, 0.75) 0%, rgba(3, 3, 5, 0.95) 100%), url('/assets/img/contact.jpeg') center/cover no-repeat;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        text-align: center;
    }

    .contact-hero-badge {
        display: inline-block;
        background: rgba(220, 38, 38, 0.18);
        border: 1px solid rgba(220, 38, 38, 0.35);
        color: #ff4d4d;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 6px 20px;
        border-radius: 50px;
        margin-bottom: 24px;
    }

    .contact-hero-title {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 60px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -1.5px;
        line-height: 1.1;
        margin: 0 0 24px 0;
    }

    .contact-trust-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 32px;
        margin-top: 24px;
    }

    .contact-trust-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #a1a1aa;
        font-size: 0.92rem;
        font-weight: 600;
    }

    .contact-trust-item i {
        color: #ef4444;
        font-size: 1rem;
    }

    /* Sub-Header Banner */
    .contact-subheader-section {
        padding: 50px 0 20px 0;
        text-align: center;
    }

    .contact-sub-badge {
        display: inline-block;
        background: rgba(220, 38, 38, 0.18);
        border: 1px solid rgba(220, 38, 38, 0.35);
        color: #ff4d4d;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 5px 18px;
        border-radius: 50px;
        margin-bottom: 8px;
    }

    .contact-sub-desc {
        color: #a1a1aa;
        font-size: 1.1rem;
        line-height: 1.65;
        max-width: 580px;
        margin: 0 auto 50px auto;
    }

    /* Main 2-Column Section */
    .contact-main-grid {
        display: grid;
        grid-template-columns: 1fr 1.22fr;
        gap: 40px;
        margin-bottom: 90px;
        align-items: start;
    }

    .contact-left-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
        text-align: left;
    }

    .conversation-heading {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 2.3rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 14px 0;
        line-height: 1.15;
        letter-spacing: -0.5px;
    }

    .conversation-desc {
        color: #a1a1aa;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    /* Info Cards */
    .contact-info-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 0;
    }

    .contact-info-card {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #0e0e12;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        padding: 16px 20px;
        transition: all 0.25s ease;
    }

    .contact-info-card:hover {
        border-color: rgba(220, 38, 38, 0.35);
        background: rgba(220, 38, 38, 0.04);
    }

    .contact-info-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(220, 38, 38, 0.12);
        border: 1px solid rgba(220, 38, 38, 0.25);
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .contact-info-label {
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 2px;
    }

    .contact-info-val {
        font-size: 0.9rem;
        color: #a1a1aa;
        text-decoration: none;
        word-break: break-word;
        transition: color 0.25s ease;
    }

    .contact-info-val:hover {
        color: #ffffff;
    }

    /* Services Tag Cloud Box */
    .services-tag-box {
        background: #0e0e12;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 22px 24px;
        text-align: left;
    }

    .services-tag-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 14px;
    }

    .services-tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .service-tag-pill {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.25s ease;
        display: inline-block;
    }

    .service-tag-pill:hover {
        background: rgba(220, 38, 38, 0.15);
        border-color: rgba(220, 38, 38, 0.35);
        color: #ef4444;
    }

    /* Contact Form Card */
    .contact-form-card {
        background: #0e0e12;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 38px 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }

    .contact-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 20px;
    }

    .contact-field-group {
        margin-bottom: 20px;
        text-align: left;
    }

    .contact-form-row .contact-field-group {
        margin-bottom: 0;
    }

    .contact-field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 8px;
    }

    .contact-field-control {
        width: 100%;
        background: #030305;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 13px 16px;
        color: #ffffff;
        font-size: 0.92rem;
        font-family: inherit;
        outline: none;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
        box-sizing: border-box;
    }

    .contact-field-control::placeholder {
        color: #52525b;
    }

    .contact-field-control:focus {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
    }

    select.contact-field-control {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a1a1aa'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
        padding-right: 42px;
        cursor: pointer;
    }

    .contact-field-control option {
        background: #0e0e12;
        color: #ffffff;
    }

    textarea.contact-field-control {
        resize: vertical;
        min-height: 110px;
    }

    .char-counter-text {
        font-size: 0.8rem;
        color: #71717a;
        margin-top: 8px;
        text-align: right;
    }

    .btn-send-message {
        width: 100%;
        background: #dc2626;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 700;
        padding: 15px 24px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 25px rgba(220, 38, 38, 0.35);
        margin-top: 10px;
    }

    .btn-send-message:hover {
        background: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(220, 38, 38, 0.55);
    }

    @media (max-width: 992px) {
        .contact-main-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .contact-hero-title {
            font-size: 3rem;
        }
    }

    @media (max-width: 640px) {
        .contact-form-row {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .contact-form-card {
            padding: 24px 20px;
        }
    }
</style>

<!-- Hero Section -->
<section class="contact-hero-stage">
    <div class="container">
        <h1 class="contact-hero-title">Let's Create Something<br><span style="color: #dc2626;">Extraordinary</span></h1>
        
        <div class="contact-trust-row">
            <div class="contact-trust-item"><i class="fa-regular fa-clock"></i> 48hr Response</div>
            <div class="contact-trust-item"><i class="fa-solid fa-shield-halved"></i> No Commitment</div>
        </div>
    </div>
</section>

<!-- Sub-Header Section -->
<section class="contact-subheader-section">
    <div class="container">
        <p class="contact-sub-desc">Ready to bring your vision to life? Tell us about your project and we'll get back to you within 48 hours.</p>

        <!-- Main 2-Column Section -->
        <div class="contact-main-grid">
            
            <!-- Left Info Column -->
            <div class="contact-left-col">
                <div>
                    <h2 class="conversation-heading">Let's Start a Conversation</h2>
                    <p class="conversation-desc">Whether you have a specific project in mind or just want to explore possibilities, we're here to help. Reach out and our team will respond promptly.</p>

                    <!-- Contact Info List -->
                    <div class="contact-info-list">
                        <div class="contact-info-card">
                            <div class="contact-info-icon-box"><i class="fa-solid fa-envelope"></i></div>
                            <div style="text-align: left;">
                                <div class="contact-info-label">Email Us</div>
                                <a href="mailto:creativeservices@falhen.com" class="contact-info-val">creativeservices@falhen.com</a>
                            </div>
                        </div>

                        <div class="contact-info-card">
                            <div class="contact-info-icon-box"><i class="fa-solid fa-phone"></i></div>
                            <div style="text-align: left;">
                                <div class="contact-info-label">Call Us</div>
                                <a href="tel:+13314651119" class="contact-info-val">+1 (331) 465-1119</a>
                            </div>
                        </div>

                        <div class="contact-info-card">
                            <div class="contact-info-icon-box"><i class="fa-solid fa-location-dot"></i></div>
                            <div style="text-align: left;">
                                <div class="contact-info-label">Visit Us</div>
                                <div class="contact-info-val">611 Enterprise Dr. Suite 4 Oakbrook IL 60523</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Tag Cloud -->
                <div class="services-tag-box">
                    <div class="services-tag-title">Our Services</div>
                    <div class="services-tag-cloud">
                        <a href="/service-single.php?slug=video-production" class="service-tag-pill">Video Production</a>
                        <a href="/service-single.php?slug=live-streaming" class="service-tag-pill">Live Streaming</a>
                        <a href="/service-single.php?slug=post-production" class="service-tag-pill">Post Production</a>
                        <a href="/service-single.php?slug=animation-motion-graphics" class="service-tag-pill">Animation & Motion Graphics</a>
                        <a href="/service-single.php?slug=creative-services" class="service-tag-pill">Content Strategy</a>
                        <a href="/service-single.php?slug=commercial-photography" class="service-tag-pill">Photography</a>
                        <a href="/services.php" class="service-tag-pill">Wedding & Events</a>
                    </div>
                </div>
            </div>

            <!-- Right Form Column -->
            <div class="contact-form-card">
                <div id="contactFormAlert" style="display: none; margin-bottom: 20px; padding: 14px; border-radius: 10px; font-size: 0.92rem; text-align: left;"></div>

                <form id="contactPageForm" action="/api/contact_submit.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="contact-form-row">
                        <div class="contact-field-group">
                            <label class="contact-field-label">Full Name *</label>
                            <input type="text" name="full_name" class="contact-field-control" required placeholder="John Doe">
                        </div>

                        <div class="contact-field-group">
                            <label class="contact-field-label">Email Address *</label>
                            <input type="email" name="email" class="contact-field-control" required placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="contact-form-row">
                        <div class="contact-field-group">
                            <label class="contact-field-label">Phone Number</label>
                            <input type="tel" name="phone" class="contact-field-control" placeholder="+1 (331) 465-1119">
                        </div>

                        <div class="contact-field-group">
                            <label class="contact-field-label">Company Name</label>
                            <input type="text" name="company" class="contact-field-control" placeholder="Your Company">
                        </div>
                    </div>

                    <div class="contact-field-group" style="text-align: left;">
                        <label class="contact-field-label">Service Interested In *</label>
                        <select name="service_type" class="contact-field-control" required>
                            <option value="">Select a service</option>
                            <option value="Video Production">Video Production</option>
                            <option value="Live Streaming">Live Streaming</option>
                            <option value="Post Production">Post Production</option>
                            <option value="Animation & Motion Graphics">Animation & Motion Graphics</option>
                            <option value="Content Strategy">Content Strategy</option>
                            <option value="Photography">Photography</option>
                            <option value="Wedding & Events">Wedding & Events</option>
                        </select>
                    </div>

                    <div class="contact-field-group" style="text-align: left;">
                        <label class="contact-field-label">Project Details *</label>
                        <textarea name="project_details" id="projectDetailsTextarea" class="contact-field-control" rows="5" maxlength="500" required placeholder="Tell us about your project, timeline, and budget..."></textarea>
                        <div class="char-counter-text" id="charCounterText">0/500 characters</div>
                    </div>

                    <button type="submit" class="btn-send-message" id="contactSubmitBtn">
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<!-- Character Counter & AJAX Form Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const textarea = document.getElementById('projectDetailsTextarea');
        const charCounter = document.getElementById('charCounterText');
        const form = document.getElementById('contactPageForm');
        const alertBox = document.getElementById('contactFormAlert');
        const submitBtn = document.getElementById('contactSubmitBtn');

        if (textarea && charCounter) {
            textarea.addEventListener('input', () => {
                const len = textarea.value.length;
                charCounter.textContent = `${len}/500 characters`;
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

                const formData = new FormData(form);

                fetch('/api/contact_submit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alertBox.style.display = 'block';
                    if (data.status === 'success') {
                        alertBox.style.background = 'rgba(16, 185, 129, 0.15)';
                        alertBox.style.border = '1px solid rgba(16, 185, 129, 0.4)';
                        alertBox.style.color = '#34d399';
                        alertBox.textContent = data.message || 'Thank you! Your message has been sent successfully. We will contact you within 48 hours.';
                        form.reset();
                        if (charCounter) charCounter.textContent = '0/500 characters';
                    } else {
                        alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                        alertBox.style.border = '1px solid rgba(239, 68, 68, 0.4)';
                        alertBox.style.color = '#fca5a5';
                        alertBox.textContent = data.message || 'An error occurred while submitting your message. Please try again.';
                    }
                })
                .catch(() => {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    alertBox.style.border = '1px solid rgba(239, 68, 68, 0.4)';
                    alertBox.style.color = '#fca5a5';
                    alertBox.textContent = 'An unexpected error occurred. Please try emailing us directly at creativeservices@falhen.com.';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Message';
                });
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
