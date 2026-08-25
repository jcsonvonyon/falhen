<?php
// about.php - About Us page matching falhen.com/about screenshots
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "About Us — Falhen Media";

// All 8 Team Members Data for the About Page grid
$rawMembers = getTeamMembers();
$teamMembers = [];
foreach ($rawMembers as $m) {
    $teamMembers[] = [
        'id' => $m['id'],
        'name' => $m['name'],
        'role' => $m['role'],
        'dept' => $m['department'],
        'location' => $m['location'],
        'exp' => $m['experience'],
        'bio' => $m['bio'],
        'img' => $m['image'],
        'skills' => $m['skills'] ?? []
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background-color: #030305;
            color: #d4d4d8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        .about-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
        }

        .about-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .about-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .about-back-link:hover {
            color: #ffffff;
        }

        .btn-contact-top {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.25s ease;
        }

        .btn-contact-top:hover {
            background: #ef4444;
        }

        /* Hero Stage Section */
        .about-hero-section {
            position: relative;
            padding: 90px 0 70px 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.75) 0%, rgba(3, 3, 5, 0.95) 100%), url('/assets/img/about.jpg') center/cover no-repeat;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .about-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .story-badge {
            display: inline-block;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ff4d4d;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 5px 18px;
            border-radius: 50px;
            margin-bottom: 8px;
        }

        .about-hero-title {
            font-size: 4.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 4px 0 14px 0;
            line-height: 1.1;
        }

        .about-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Section 2: Who We Are Split */
        .who-we-are-section {
            padding: 100px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .who-we-are-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .who-we-are-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 4px 0 16px 0;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .who-we-are-p {
            color: #a1a1aa;
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .action-btns-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 36px;
            flex-wrap: wrap;
        }

        .btn-work-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-work-red:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        .btn-dark-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-dark-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .who-we-are-img {
            width: 100%;
            height: 480px;
            object-fit: cover;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        /* Section 3: Core Values */
        .core-values-section {
            padding: 100px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .section-header-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 4px 0 10px 0;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .section-header-desc {
            color: #a1a1aa;
            font-size: 1.05rem;
            max-width: 620px;
            margin: 0 auto 60px auto;
            line-height: 1.65;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            text-align: left;
        }

        .value-card-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 36px 28px;
            transition: all 0.35s ease;
        }

        .value-card-box:hover {
            border-color: rgba(220, 38, 38, 0.45);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.12);
        }

        .value-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 24px;
            transition: all 0.35s ease;
        }

        .value-card-box:hover .value-icon-box {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
        }

        .value-card-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .value-card-desc {
            font-size: 0.92rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin: 0;
        }

        /* Section 4: Meet Our Team */
        .team-preview-section {
            padding: 100px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .about-team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 50px;
            text-align: left;
        }

        .about-team-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            transition: all 0.35s ease;
        }

        .about-team-card:hover {
            border-color: rgba(220, 38, 38, 0.4);
            transform: translateY(-4px);
        }

        .about-card-thumb {
            position: relative;
            height: 280px;
            overflow: hidden;
        }

        .about-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .about-team-card:hover .about-card-thumb img {
            transform: scale(1.06);
        }

        .team-tags-overlay {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 3;
            pointer-events: none;
        }

        .about-team-card:hover .team-tags-overlay,
        .page-team-card:hover .team-tags-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .team-skill-tag {
            background: rgba(12, 12, 16, 0.88);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .about-card-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .about-card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .about-card-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }

        .about-dept-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 50px;
        }

        .about-dept-creative {
            background: rgba(220, 38, 38, 0.18);
            color: #ef4444;
            border: 1px solid rgba(220, 38, 38, 0.35);
        }

        .about-dept-strategy {
            background: rgba(16, 185, 129, 0.18);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        .about-card-role {
            color: #ef4444;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .about-card-bio {
            color: #a1a1aa;
            font-size: 0.86rem;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .about-card-exp {
            color: #71717a;
            font-size: 0.78rem;
            font-weight: 600;
        }

        /* Section 5: Bottom Contact Section */
        .about-contact-section {
            padding: 50px 0 20px 0;
        }

        .about-contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr;
            gap: 60px;
            align-items: start;
        }

        .about-contact-title {
            font-size: 2.6rem;
            font-weight: 800;
            color: #ffffff;
            margin: 4px 0 10px 0;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .about-contact-desc {
            color: #a1a1aa;
            font-size: 1rem;
            line-height: 1.65;
            margin-bottom: 36px;
        }

        .about-contact-info-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .about-info-item {
            display: flex;
            align-items: center;
            gap: 18px;
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 18px 22px;
        }

        .about-info-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .about-info-lbl {
            font-size: 0.75rem;
            font-weight: 700;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .about-info-val {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        /* Form Card */
        .about-form-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 40px;
        }

        .form-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .about-form-field {
            margin-bottom: 20px;
            text-align: left;
        }

        .about-form-field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #a1a1aa;
            margin-bottom: 6px;
        }

        .about-form-field input,
        .about-form-field select,
        .about-form-field textarea {
            width: 100%;
            background: #030305;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 13px 16px;
            color: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            box-sizing: border-box;
        }

        .about-form-field input:focus,
        .about-form-field select:focus,
        .about-form-field textarea:focus {
            border-color: #dc2626;
        }

        .about-form-field select option {
            background: #0e0e12;
        }

        .btn-submit-about {
            width: 100%;
            background: #dc2626;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            padding: 16px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-submit-about:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Footer Bar */
        .about-footer-bar {
            padding: 18px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .about-footer-links {
            display: flex;
            gap: 24px;
        }

        .about-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .about-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 1100px) {
            .who-we-are-grid,
            .about-contact-grid {
                grid-template-columns: 1fr;
            }
            .values-grid,
            .about-team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .values-grid,
            .about-team-grid {
                grid-template-columns: 1fr;
            }
            .form-row-2col {
                grid-template-columns: 1fr;
            }
            .about-hero-title {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="about-nav-bar">
        <div class="about-nav-container">
            <a href="/" class="about-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <a href="/contact.php" class="btn-contact-top">Contact Us</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="about-hero-section">
        <div class="about-container">
            <div class="story-badge">Our Story</div>
            <h1 class="about-hero-title">About Falhen Media</h1>
            <p class="about-hero-subtitle">A passionate team of storytellers, creators, and innovators dedicated to transforming ideas into captivating visual experiences.</p>
        </div>
    </section>

    <!-- Section 2: Who We Are -->
    <section class="who-we-are-section">
        <div class="about-container">
            <div class="who-we-are-grid">
                <div>
                    <div class="story-badge">Who We Are</div>
                    <h2 class="who-we-are-title">Creating Impact Through Visual Storytelling</h2>
                    <p class="who-we-are-p">Founded with a vision to revolutionize digital media production, Falhen Media has grown into a leading creative agency specializing in video production, content creation, and brand storytelling. Our team combines technical expertise with artistic vision to deliver exceptional results.</p>
                    <p class="who-we-are-p">We believe that every brand has a unique story to tell, and our mission is to help you tell yours in the most compelling way possible. From concept development to final delivery, we work closely with our clients to ensure their vision comes to life.</p>
                    <p class="who-we-are-p">Our commitment to excellence, innovation, and client satisfaction has earned us recognition in the industry and the trust of brands worldwide.</p>

                    <div class="action-btns-row">
                        <a href="/contact.php" class="btn-work-red">Work With Us</a>
                        <a href="/portfolio.php" class="btn-dark-glass">View Portfolio</a>
                        <a href="/team.php" class="btn-dark-glass">View Team</a>
                    </div>
                </div>

                <div>
                    <img src="/assets/img/team/team.jpeg" alt="Falhen Production Studio" class="who-we-are-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Our Core Values -->
    <section class="core-values-section">
        <div class="about-container">
            <div class="story-badge">What Drives Us</div>
            <h2 class="section-header-title">Our Core Values</h2>
            <p class="section-header-desc">These principles guide every decision we make, every project we take on, and every relationship we build.</p>

            <div class="values-grid">
                <div class="value-card-box">
                    <div class="value-icon-box"><i class="fa-regular fa-eye"></i></div>
                    <h3 class="value-card-title">Vision-First</h3>
                    <p class="value-card-desc">Every project starts with understanding your vision. We listen deeply before we create.</p>
                </div>

                <div class="value-card-box">
                    <div class="value-icon-box"><i class="fa-solid fa-award"></i></div>
                    <h3 class="value-card-title">Excellence</h3>
                    <p class="value-card-desc">We hold ourselves to the highest standards in every frame, every edit, every delivery.</p>
                </div>

                <div class="value-card-box">
                    <div class="value-icon-box"><i class="fa-solid fa-people-group"></i></div>
                    <h3 class="value-card-title">Collaboration</h3>
                    <p class="value-card-desc">Great work is never made alone. We partner with clients as true creative collaborators.</p>
                </div>

                <div class="value-card-box">
                    <div class="value-icon-box"><i class="fa-regular fa-lightbulb"></i></div>
                    <h3 class="value-card-title">Innovation</h3>
                    <p class="value-card-desc">We stay ahead of the curve — embracing new tools, formats, and storytelling techniques.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Meet Our Team -->
    <section class="team-preview-section">
        <div class="about-container">
            <div class="story-badge">The People</div>
            <h2 class="section-header-title">Meet Our Team</h2>
            <p class="section-header-desc">Our talented team brings diverse skills and expertise to every project.</p>

            <div class="about-team-grid">
                <?php foreach ($teamMembers as $member): ?>
                    <a href="/team-single.php?id=<?php echo $member['id']; ?>" class="about-team-card">
                        <div class="about-card-thumb">
                            <img src="<?php echo htmlspecialchars($member['img']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            <?php if (!empty($member['skills'])): ?>
                                <div class="team-tags-overlay">
                                    <?php foreach ($member['skills'] as $s): ?>
                                        <span class="team-skill-tag"><?php echo htmlspecialchars($s); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="about-card-body">
                            <div>
                                <div class="about-card-title-row">
                                    <h3 class="about-card-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                    <span class="about-dept-badge <?php echo ($member['dept'] == 'Creative') ? 'about-dept-creative' : 'about-dept-strategy'; ?>">
                                        <?php echo htmlspecialchars($member['dept']); ?>
                                    </span>
                                </div>
                                <div class="about-card-role"><?php echo htmlspecialchars($member['role']); ?></div>
                                <div class="about-card-bio"><?php echo htmlspecialchars($member['bio']); ?></div>
                            </div>
                            <div class="about-card-exp"><?php echo htmlspecialchars($member['exp']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section 5: Get in Touch Form -->
    <section class="about-contact-section">
        <div class="about-container">
            <div class="about-contact-grid">
                <div style="text-align: left;">
                    <div class="story-badge">Get In Touch</div>
                    <h2 class="about-contact-title">Ready to Create Something Great?</h2>
                    <p class="about-contact-desc">Let's talk about your project. Fill in the form and we'll get back to you within 24 hours.</p>

                    <div class="about-contact-info-list">
                        <div class="about-info-item">
                            <div class="about-info-icon-box"><i class="fa-solid fa-envelope"></i></div>
                            <div>
                                <div class="about-info-lbl">EMAIL</div>
                                <div class="about-info-val">kim@falhen.com</div>
                            </div>
                        </div>

                        <div class="about-info-item">
                            <div class="about-info-icon-box"><i class="fa-solid fa-phone"></i></div>
                            <div>
                                <div class="about-info-lbl">PHONE</div>
                                <div class="about-info-val">+1 (331) 465-1119</div>
                            </div>
                        </div>

                        <div class="about-info-item">
                            <div class="about-info-icon-box"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <div class="about-info-lbl">LOCATION</div>
                                <div class="about-info-val">611 Enterprise Dr. Suite 4 Oakbrook IL 60523</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="about-form-card">
                    <form action="/api/contact_submit.php" method="POST" onsubmit="handleAboutSubmit(event)">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="form-row-2col">
                            <div class="about-form-field">
                                <label>Your Name *</label>
                                <input type="text" name="full_name" required placeholder="John Smith">
                            </div>
                            <div class="about-form-field">
                                <label>Email Address *</label>
                                <input type="email" name="email" required placeholder="john@company.com">
                            </div>
                        </div>

                        <div class="about-form-field">
                            <label>Service Interested In</label>
                            <select name="service_type">
                                <option value="">Select a service...</option>
                                <option value="Video Production">Video Production</option>
                                <option value="Live Streaming">Live Streaming</option>
                                <option value="Post Production">Post Production</option>
                                <option value="Animation & Motion Graphics">Animation & Motion Graphics</option>
                                <option value="Content Strategy">Content Strategy</option>
                                <option value="Photography">Photography</option>
                                <option value="Wedding & Events">Wedding & Events</option>
                            </select>
                        </div>

                        <div class="about-form-field">
                            <label>Message *</label>
                            <textarea name="project_details" id="aboutMsgTextarea" rows="4" maxlength="500" required placeholder="Tell us about your project..."></textarea>
                            <div style="font-size: 0.78rem; color: #71717a; margin-top: 6px; text-align: right;" id="aboutCharCounter">0/500</div>
                        </div>

                        <button type="submit" class="btn-submit-about"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                    </form>
                </div>
            </div>

            <!-- Footer Bar -->
            <div class="about-footer-bar" style="margin-top: 24px;">
                <div>© 2026 Falhen Media. All rights reserved.</div>
                <div class="about-footer-links">
                    <a href="/">Home</a>
                    <a href="/portfolio.php">Portfolio</a>
                    <a href="/portfolio.php">Blog</a>
                    <a href="/contact.php">Contact</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Script -->
    <script>
        const aboutTextarea = document.getElementById('aboutMsgTextarea');
        const aboutCharCounter = document.getElementById('aboutCharCounter');

        if (aboutTextarea && aboutCharCounter) {
            aboutTextarea.addEventListener('input', () => {
                aboutCharCounter.textContent = `${aboutTextarea.value.length}/500`;
            });
        }

        function handleAboutSubmit(e) {
            e.preventDefault();
            alert('Thank you! Your message has been sent successfully. We will get back to you within 24 hours.');
            e.target.reset();
            if (aboutCharCounter) aboutCharCounter.textContent = '0/500';
        }
    </script>

</body>
</html>
