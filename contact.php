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

$pageTitle = "Contact Falhen Productions | Start Your Production Today";
$pageDescription = "Ready to bring your vision to screen? Contact Falhen Productions for commercial video, multicam live streaming, or creative consultation. Inquire today.";
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
        font-size: 56px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -1.5px;
        line-height: 1.1;
        margin: 0 0 14px 0;
    }

    .contact-hero-subtitle {
        font-size: 1.15rem;
        color: #a1a1aa;
        margin: 0 auto 16px auto;
        max-width: 580px;
        line-height: 1.5;
    }

    .contact-trust-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 28px;
        margin-top: 18px;
    }

    .contact-trust-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #d4d4d8;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .contact-trust-item i {
        color: #ef4444;
        font-size: 0.95rem;
    }

    /* Main Section */
    .contact-main-section {
        padding: 50px 0 80px 0;
    }

    .form-card-title {
        font-family: inherit;
        font-size: 1.25rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 18px 0;
        letter-spacing: -0.3px;
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
        gap: 10px 16px;
        align-items: center;
    }

    .service-tag-pill {
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        color: #94a3b8;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 2px 0 4px 0;
        text-decoration: none;
        transition: color 0.2s ease, border-color 0.2s ease;
        display: inline-block;
        cursor: pointer;
        font-family: inherit;
        line-height: 1.4;
        user-select: none;
        outline: none;
        border-radius: 0;
    }

    .service-tag-pill:hover {
        color: #ef4444;
    }

    .service-tag-pill.active {
        color: #ef4444 !important;
        background: transparent !important;
        border: none !important;
        border-bottom: 2px solid #ef4444 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
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
            padding: 105px 0 35px 0;
        }
        .contact-main-grid {
            grid-template-columns: 1fr;
            gap: 32px;
            margin-bottom: 50px;
        }
        .contact-hero-title {
            font-size: 2.6rem;
        }
        .contact-form-card {
            padding: 28px 24px;
        }
    }

    @media (max-width: 768px) {
        .contact-hero-stage {
            padding: 85px 0 24px 0 !important;
        }
        .contact-hero-title {
            font-size: 2.05rem !important;
            letter-spacing: -0.6px !important;
            margin-bottom: 8px !important;
            line-height: 1.15 !important;
        }
        .contact-hero-subtitle {
            font-size: 0.85rem !important;
            line-height: 1.4 !important;
            margin: 0 auto 12px auto !important;
            padding: 0 12px !important;
        }
        .contact-trust-row {
            gap: 14px !important;
            margin-top: 10px !important;
            flex-wrap: wrap !important;
        }
        .contact-trust-item {
            font-size: 0.80rem !important;
        }
        .contact-main-section {
            padding: 24px 0 45px 0 !important;
        }
        .contact-main-grid {
            gap: 22px !important;
            margin-bottom: 30px !important;
        }
        .conversation-heading {
            font-size: 1.35rem !important;
            margin-bottom: 6px !important;
        }
        .conversation-desc {
            font-size: 0.82rem !important;
            margin-bottom: 14px !important;
            line-height: 1.45 !important;
        }
        .contact-info-list {
            gap: 10px !important;
        }
        .contact-info-card {
            padding: 11px 14px !important;
            gap: 12px !important;
        }
        .contact-info-icon-box {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }
        .contact-info-label {
            font-size: 0.82rem;
        }
        .contact-info-val {
            font-size: 0.82rem;
        }
        .services-tag-box {
            padding: 14px 16px;
        }
        .services-tag-title {
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        .services-tag-cloud {
            gap: 7px 12px;
        }
        .service-tag-pill {
            font-size: 0.78rem;
            padding: 2px 0 3px 0;
            border-radius: 0;
        }
        .contact-form-card {
            padding: 20px 16px !important;
            border-radius: 14px !important;
        }
        .form-card-title {
            font-size: 1.15rem !important;
            margin-bottom: 14px !important;
        }
    }

    @media (max-width: 480px) {
        .contact-hero-stage {
            padding: 80px 0 20px 0 !important;
        }
        .contact-hero-title {
            font-size: 1.85rem !important;
            letter-spacing: -0.4px !important;
            margin-bottom: 6px !important;
            line-height: 1.18 !important;
        }
        .contact-trust-row {
            gap: 10px !important;
            margin-top: 8px !important;
        }
        .contact-trust-item {
            font-size: 0.74rem !important;
            gap: 5px;
        }
        .contact-main-grid {
            gap: 18px !important;
            margin-bottom: 25px !important;
        }
        .conversation-heading {
            font-size: 1.25rem !important;
            margin-bottom: 4px !important;
        }
        .conversation-desc {
            font-size: 0.80rem !important;
            margin-bottom: 12px !important;
        }
        .contact-info-card {
            padding: 10px 12px !important;
            gap: 10px !important;
            border-radius: 10px !important;
        }
        .contact-info-icon-box {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.85rem !important;
            border-radius: 8px !important;
        }
        .services-tag-box {
            padding: 12px 12px !important;
            border-radius: 10px !important;
        }
        .contact-form-card {
            padding: 18px 14px !important;
            border-radius: 12px !important;
        }
        .contact-form-row {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .contact-field-group {
            margin-bottom: 12px;
        }
        .contact-field-label {
            font-size: 0.78rem;
            margin-bottom: 5px;
        }
        .contact-field-control {
            padding: 10px 12px;
            font-size: 0.84rem;
            border-radius: 8px;
        }
        textarea.contact-field-control {
            min-height: 85px;
        }
        .btn-send-message {
            padding: 12px 16px;
            font-size: 0.88rem;
            border-radius: 9px;
            margin-top: 4px;
        }
    }
</style>

<!-- Hero Section -->
<section class="contact-hero-stage">
    <div class="container">
        <h1 class="contact-hero-title">Start a <span style="color: #dc2626;">Project</span></h1>
        <p class="contact-hero-subtitle">Oakbrook studio &amp; on-location productions across North America and worldwide.</p>
        
        <div class="contact-trust-row">
            <div class="contact-trust-item"><i class="fa-regular fa-clock"></i> 24-48hr Direct Response</div>
            <div class="contact-trust-item"><i class="fa-solid fa-location-dot"></i> Chicago &amp; Worldwide</div>
        </div>
    </div>
</section>

<!-- Main Inquiry Section -->
<section class="contact-main-section">
    <div class="container">
        <div class="contact-main-grid">
            
            <!-- Left Info Column -->
            <div class="contact-left-col">
                <div>
                    <h2 class="conversation-heading">Direct Coordinates</h2>
                    <p class="conversation-desc">Speak directly with our production team for commercial films, broadcasts, or photography bookings.</p>

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
                                <div class="contact-info-label">Studio</div>
                                <div class="contact-info-val">611 Enterprise Dr. Suite 4, Oakbrook IL 60523</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Tag Cloud -->
                <div class="services-tag-box">
                    <div class="services-tag-title">Select Service</div>
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
                <h3 class="form-card-title">Send an Inquiry</h3>
                <div id="contactFormAlert" style="display: none; margin-bottom: 20px; padding: 14px; border-radius: 10px; font-size: 0.92rem; text-align: left;"></div>

                <form id="contactPageForm" action="/api/contact_submit.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="contact-form-row">
                        <div class="contact-field-group">
                            <label class="contact-field-label" for="contactFullName">Full Name *</label>
                            <input type="text" name="full_name" id="contactFullName" class="contact-field-control" autocomplete="name" required placeholder="John Doe">
                        </div>

                        <div class="contact-field-group">
                            <label class="contact-field-label" for="contactEmail">Email Address *</label>
                            <input type="email" name="email" id="contactEmail" class="contact-field-control" autocomplete="email" required placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="contact-form-row">
                        <div class="contact-field-group">
                            <label class="contact-field-label" for="contactPhone">Phone Number</label>
                            <input type="tel" name="phone" id="contactPhone" class="contact-field-control" autocomplete="tel" placeholder="+1 (331) 465-1119">
                        </div>

                        <div class="contact-field-group">
                            <label class="contact-field-label" for="contactCompany">Company Name</label>
                            <input type="text" name="company" id="contactCompany" class="contact-field-control" autocomplete="organization" placeholder="Your Company">
                        </div>
                    </div>

                    <div class="contact-field-group" style="text-align: left;">
                        <label class="contact-field-label" for="serviceTypeSelect">Service Interested In *</label>
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
                        <label class="contact-field-label" for="projectDetailsTextarea">Project Details *</label>
                        <textarea name="project_details" id="projectDetailsTextarea" class="contact-field-control" rows="5" maxlength="500" required placeholder="Tell us about your project, timeline, and budget..."></textarea>
                        <div class="char-counter-text" id="charCounterText">0/500 characters</div>
                    </div>

                    <button type="submit" class="btn-send-message" id="contactSubmitBtn">
                        <i class="fa-solid fa-paper-plane"></i> Send Inquiry
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
