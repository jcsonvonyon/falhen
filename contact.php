<?php
// contact.php - Contact page matching falhen.com/contact screenshots
require_once __DIR__ . '/includes/functions.php';

$rawService = trim($_GET['service'] ?? '');
$serviceSlugMap = [
    'video-production' => 'Video Production',
    'live-streaming' => 'Live Streaming',
    'post-production' => 'Post Production',
    'animation-motion-graphics' => 'Animation & Motion Graphics',
    'animation-and-motion-graphics' => 'Animation & Motion Graphics',
    'content-strategy' => 'Content Strategy',
    'creative-services' => 'Content Strategy',
    'commercial-photography' => 'Photography',
    'photography' => 'Photography',
    'wedding-events' => 'Wedding & Events',
    'wedding-and-events' => 'Wedding & Events'
];
$selectedService = $serviceSlugMap[strtolower($rawService)] ?? $rawService;

$servicesList = [
    'Video Production',
    'Live Streaming',
    'Post Production',
    'Animation & Motion Graphics',
    'Content Strategy',
    'Photography',
    'Wedding & Events'
];

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
        /* border-bottom: 1px solid rgba(255, 255, 255, 0.08); */
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
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 90px;
        align-items: start;
    }

    .contact-left-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
        text-align: left;
        min-width: 0;
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
        gap: 8px 10px;
        align-items: center;
    }

    .service-tag-pill {
        background: transparent;
        /* border: 1px solid transparent; */
        color: #94a3b8;
        font-size: 0.84rem;
        font-weight: 500;
        /* padding: 5px 12px; */
        /* border-radius: 6px; */
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-block;
        cursor: pointer;
        font-family: inherit;
        line-height: 1.4;
        user-select: none;
        outline: none;
    }

    .service-tag-pill:hover {
        color: #ef4444;
        background: rgba(220, 38, 38, 0.06);
    }

    .service-tag-pill.active {
        color: #ffffff !important;
        background: rgba(220, 38, 38, 0.12) !important;
        border: 1px solid #ef4444 !important;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
    }

    .service-preselected-notice {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #ef4444;
        margin-top: 8px;
        animation: fadeInNotice 0.25s ease;
    }

    @keyframes fadeInNotice {
        from { opacity: 0; transform: translateY(-3px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Contact Form Card */
    .contact-form-card {
        background: #0e0e12;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 38px 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        min-width: 0;
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
        .contact-hero-stage {
            padding: 120px 0 45px 0;
        }
        .contact-main-grid {
            grid-template-columns: 1fr;
            gap: 36px;
            margin-bottom: 60px;
        }
        .contact-hero-title {
            font-size: 2.8rem;
        }
        .contact-form-card {
            padding: 30px 28px;
        }
    }

    @media (max-width: 768px) {
        .contact-hero-stage {
            padding: 105px 0 32px 0;
        }
        .contact-hero-title {
            font-size: 2.3rem;
            letter-spacing: -0.8px;
            margin-bottom: 18px;
            line-height: 1.15;
        }
        .contact-trust-row {
            gap: 18px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .contact-trust-item {
            font-size: 0.84rem;
        }
        .contact-subheader-section {
            padding: 28px 0 12px 0;
        }
        .contact-sub-desc {
            font-size: 0.95rem;
            margin: 0 auto 30px auto;
            padding: 0 12px;
            line-height: 1.55;
        }
        .contact-main-grid {
            gap: 28px;
            margin-bottom: 45px;
        }
        .conversation-heading {
            font-size: 1.85rem;
            margin-bottom: 10px;
        }
        .conversation-desc {
            font-size: 0.88rem;
            margin-bottom: 18px;
            line-height: 1.55;
        }
        .contact-info-list {
            gap: 12px;
        }
        .contact-info-card {
            padding: 13px 16px;
            gap: 14px;
        }
        .contact-info-icon-box {
            width: 38px;
            height: 38px;
            font-size: 0.95rem;
        }
        .contact-info-label {
            font-size: 0.88rem;
        }
        .contact-info-val {
            font-size: 0.85rem;
        }
        .services-tag-box {
            padding: 18px 18px;
        }
        .services-tag-title {
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        .services-tag-cloud {
            gap: 7px 8px;
        }
        .service-tag-pill {
            font-size: 0.8rem;
            padding: 5px 11px;
        }
        .contact-form-card {
            padding: 24px 20px;
            border-radius: 16px;
        }
    }

    @media (max-width: 480px) {
        .contact-hero-stage {
            padding: 95px 0 26px 0;
        }
        .contact-hero-title {
            font-size: 1.95rem;
            letter-spacing: -0.5px;
            margin-bottom: 14px;
            line-height: 1.18;
        }
        .contact-trust-row {
            gap: 14px;
            margin-top: 14px;
        }
        .contact-trust-item {
            font-size: 0.78rem;
            gap: 6px;
        }
        .contact-subheader-section {
            padding: 20px 0 8px 0;
        }
        .contact-sub-desc {
            font-size: 0.88rem;
            margin: 0 auto 24px auto;
            padding: 0 4px;
            line-height: 1.5;
        }
        .contact-main-grid {
            gap: 24px;
            margin-bottom: 35px;
        }
        .conversation-heading {
            font-size: 1.6rem;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .conversation-desc {
            font-size: 0.84rem;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .contact-info-list {
            gap: 10px;
        }
        .contact-info-card {
            padding: 11px 13px;
            gap: 12px;
            border-radius: 12px;
        }
        .contact-info-icon-box {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
            border-radius: 10px;
        }
        .contact-info-label {
            font-size: 0.82rem;
        }
        .contact-info-val {
            font-size: 0.82rem;
        }
        .services-tag-box {
            padding: 14px 14px;
            border-radius: 12px;
        }
        .services-tag-title {
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        .services-tag-cloud {
            gap: 6px 6px;
        }
        .service-tag-pill {
            font-size: 0.76rem;
            padding: 4px 9px;
            border-radius: 5px;
        }
        .contact-form-card {
            padding: 20px 15px;
            border-radius: 14px;
        }
        .contact-form-row {
            grid-template-columns: 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        .contact-field-group {
            margin-bottom: 14px;
        }
        .contact-field-label {
            font-size: 0.8rem;
            margin-bottom: 6px;
        }
        .contact-field-control {
            padding: 11px 13px;
            font-size: 0.86rem;
            border-radius: 9px;
        }
        select.contact-field-control {
            background-size: 14px;
            padding-right: 36px;
        }
        textarea.contact-field-control {
            min-height: 90px;
        }
        .char-counter-text {
            font-size: 0.72rem;
            margin-top: 5px;
        }
        .btn-send-message {
            padding: 13px 18px;
            font-size: 0.92rem;
            border-radius: 10px;
            margin-top: 6px;
        }
        .service-preselected-notice {
            font-size: 0.75rem;
            margin-top: 6px;
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
                    <div class="services-tag-cloud" id="servicesTagCloud">
                        <?php foreach ($servicesList as $srv): 
                            $isActive = (strcasecmp($selectedService, $srv) === 0);
                        ?>
                            <button type="button" class="service-tag-pill <?php echo $isActive ? 'active' : ''; ?>" data-service="<?php echo htmlspecialchars($srv); ?>">
                                <?php echo htmlspecialchars($srv); ?>
                            </button>
                        <?php endforeach; ?>
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
                        <select name="service_type" id="serviceTypeSelect" class="contact-field-control" required>
                            <option value="">Select a service</option>
                            <?php foreach ($servicesList as $srv): 
                                $isOptSelected = (strcasecmp($selectedService, $srv) === 0);
                            ?>
                                <option value="<?php echo htmlspecialchars($srv); ?>" <?php echo $isOptSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($srv); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="servicePreselectedNotice" class="service-preselected-notice" style="<?php echo !empty($selectedService) ? 'display: flex;' : 'display: none;'; ?>">
                            <i class="fa-solid fa-check"></i> Service pre-selected
                        </div>
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
                        if (serviceNotice) serviceNotice.style.display = 'none';
                        serviceButtons.forEach(btn => btn.classList.remove('active'));
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

        // Service Selection Sync Logic
        const serviceSelect = document.getElementById('serviceTypeSelect');
        const serviceNotice = document.getElementById('servicePreselectedNotice');
        const serviceButtons = document.querySelectorAll('.service-tag-pill');

        function updateServiceSelection(chosenValue, shouldScroll = false) {
            if (!serviceSelect) return;

            let matched = false;
            for (let i = 0; i < serviceSelect.options.length; i++) {
                if (serviceSelect.options[i].value && serviceSelect.options[i].value.toLowerCase() === chosenValue.toLowerCase()) {
                    serviceSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }

            if (matched && serviceSelect.value) {
                if (serviceNotice) serviceNotice.style.display = 'flex';
                serviceButtons.forEach(btn => {
                    const btnSrv = btn.getAttribute('data-service') || '';
                    if (btnSrv.toLowerCase() === serviceSelect.value.toLowerCase()) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                if (shouldScroll && window.innerWidth <= 992) {
                    serviceSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                serviceSelect.value = '';
                if (serviceNotice) serviceNotice.style.display = 'none';
                serviceButtons.forEach(btn => btn.classList.remove('active'));
            }
        }

        serviceButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const service = this.getAttribute('data-service') || '';
                updateServiceSelection(service, true);
            });
        });

        if (serviceSelect) {
            serviceSelect.addEventListener('change', function() {
                if (this.value) {
                    updateServiceSelection(this.value, false);
                } else {
                    if (serviceNotice) serviceNotice.style.display = 'none';
                    serviceButtons.forEach(btn => btn.classList.remove('active'));
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
