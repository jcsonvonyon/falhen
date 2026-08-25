<?php
// careers.php - Careers Page matching falhen.com/careers screenshots with rich expandable job details drawers
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Careers & Openings — Falhen Media";

// Fetch Job Openings Dynamically from Repository
$jobOpeningsMap = getCareersRepo();
$jobOpenings = array_values(array_filter($jobOpeningsMap, function($j) {
    return ($j['status'] ?? 'open') === 'open';
}));
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

        .careers-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
        }

        .careers-nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .careers-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .careers-back-link:hover {
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
        .careers-hero-section {
            position: relative;
            padding: 80px 0 60px 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.75) 0%, rgba(3, 3, 5, 0.95) 100%), url('/assets/img/team/career.jpeg') center/cover no-repeat;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .careers-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .hiring-badge {
            display: inline-block;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ff4d4d;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 5px 18px;
            border-radius: 50px;
            margin-bottom: 20px;
        }

        .careers-hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 16px 0;
        }

        .careers-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            max-width: 580px;
            margin: 0 auto 50px auto;
        }

        /* Metrics Bar */
        .careers-metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .metric-card {
            background: rgba(18, 18, 24, 0.75);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
        }

        .metric-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.25);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .metric-val {
            font-size: 1rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .metric-lbl {
            font-size: 0.8rem;
            color: #71717a;
            font-weight: 600;
        }

        /* Controls Section */
        .careers-controls-wrap {
            padding: 40px 0 20px 0;
        }

        .careers-search-bar {
            width: 100%;
            position: relative;
            margin-bottom: 20px;
        }

        .careers-search-bar i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #71717a;
            font-size: 0.95rem;
        }

        .careers-search-input {
            width: 100%;
            background: rgba(18, 18, 24, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 14px 20px 14px 48px;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.25s ease;
            box-sizing: border-box;
        }

        .careers-search-input:focus {
            border-color: rgba(220, 38, 38, 0.5);
        }

        .careers-filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .career-tab-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #d4d4d8;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .career-tab-btn.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .career-tab-count {
            background: rgba(255, 255, 255, 0.15);
            font-size: 0.72rem;
            padding: 2px 7px;
            border-radius: 50px;
        }

        .showing-openings-text {
            color: #71717a;
            font-size: 0.88rem;
            margin: 24px 0 20px 0;
            font-weight: 500;
        }

        /* Openings List Cards */
        .openings-list-wrap {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 80px;
        }

        .job-opening-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 28px 32px;
            transition: all 0.35s ease;
        }

        .job-opening-card:hover {
            border-color: rgba(255, 255, 255, 0.25);
        }

        .job-opening-card.open {
            border-color: rgba(220, 38, 38, 0.5);
            box-shadow: 0 10px 35px rgba(220, 38, 38, 0.15);
        }

        .job-card-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .job-badges-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .job-dept-pill {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 50px;
        }

        .job-meta-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a1a1aa;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: 50px;
        }

        .job-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
        }

        .job-details-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #a1a1aa;
            font-size: 0.88rem;
        }

        .job-action-side {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-apply-job {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-apply-job:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        .btn-toggle-job-details {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a1a1aa;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-toggle-job-details:hover,
        .job-opening-card.open .btn-toggle-job-details {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
        }

        /* Expanded Job Details Styling matching Screenshots */
        .job-expanded-details {
            display: none;
            padding-top: 24px;
            margin-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #d4d4d8;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .job-opening-card.open .job-expanded-details {
            display: block;
        }

        .job-overview-text {
            color: #d4d4d8;
            font-size: 0.98rem;
            line-height: 1.65;
            margin-bottom: 24px;
        }

        .job-section-block {
            margin-top: 24px;
        }

        .job-section-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .job-section-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .job-section-list li {
            font-size: 0.93rem;
            color: #d4d4d8;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }

        .job-section-list li i.check-icon {
            color: #10b981;
            font-size: 0.95rem;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .job-section-html ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .job-section-html ul li {
            font-size: 0.93rem;
            color: #d4d4d8;
            position: relative;
            padding-left: 26px;
            line-height: 1.55;
        }

        .job-section-html ul li::before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 2px;
            color: #ef4444;
            font-size: 0.95rem;
        }

        .job-action-footer-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-job-action {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-job-action:hover {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Why Work Here Section */
        .why-work-section {
            padding: 60px 0 80px 0;
            text-align: center;
        }

        .why-work-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 12px 0 12px 0;
            letter-spacing: -0.5px;
        }

        .why-work-subtitle {
            color: #a1a1aa;
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto 50px auto;
            line-height: 1.65;
        }

        .perks-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            text-align: left;
        }

        .perk-card-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 32px;
            transition: border-color 0.3s ease;
        }

        .perk-card-box:hover {
            border-color: rgba(220, 38, 38, 0.35);
        }

        .perk-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 20px;
        }

        .perk-card-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 10px;
        }

        .perk-card-desc {
            font-size: 0.92rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin: 0;
        }

        /* Not Right Role Callout Card */
        .not-right-role-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.9) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.25);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            margin-bottom: 80px;
        }

        .not-right-role-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 20px;
        }

        .not-right-role-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
        }

        .not-right-role-desc {
            color: #a1a1aa;
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto 28px auto;
            line-height: 1.6;
        }

        .not-right-role-btns {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-send-resume {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-send-resume:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        .btn-email-direct {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-email-direct:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Application Modal */
        .apply-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .apply-modal-box {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            max-width: 540px;
            width: 100%;
            padding: 36px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
        }

        .apply-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a1a1aa;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .apply-modal-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px 0;
        }

        .apply-modal-role-name {
            color: #ff4d4d;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 24px;
        }

        .form-group-field {
            margin-bottom: 18px;
        }

        .form-group-field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #a1a1aa;
            margin-bottom: 6px;
        }

        .form-group-field input,
        .form-group-field textarea {
            width: 100%;
            background: rgba(18, 18, 24, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ffffff;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            box-sizing: border-box;
        }

        .form-group-field input:focus,
        .form-group-field textarea:focus {
            border-color: rgba(220, 38, 38, 0.5);
        }

        /* Footer */
        .careers-footer-bar {
            padding: 18px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .careers-footer-links {
            display: flex;
            gap: 24px;
        }

        .careers-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .careers-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .careers-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .perks-grid {
                grid-template-columns: 1fr;
            }
            .careers-hero-title {
                font-size: 3rem;
            }
        }

        @media (max-width: 600px) {
            .careers-metrics-grid {
                grid-template-columns: 1fr;
            }
            .job-card-top-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="careers-nav-bar">
        <div class="careers-nav-container">
            <a href="/" class="careers-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <a href="/contact.php" class="btn-contact-top">Contact Us</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="careers-hero-section">
        <div class="careers-container">
            <div class="hiring-badge">We Are Hiring</div>
            <h1 class="careers-hero-title">Join the <span style="color: #ff4d4d;">Team</span></h1>
            <p class="careers-hero-subtitle">Build your career with an award-winning video production studio. Create what the world watches.</p>

            <!-- Metrics Bar -->
            <div class="careers-metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon-box"><i class="fa-solid fa-briefcase"></i></div>
                    <div>
                        <div class="metric-val">6</div>
                        <div class="metric-lbl">Open Roles</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon-box"><i class="fa-solid fa-location-dot"></i></div>
                    <div>
                        <div class="metric-val">Hybrid & Remote</div>
                        <div class="metric-lbl">Work Style</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon-box"><i class="fa-solid fa-trophy"></i></div>
                    <div>
                        <div class="metric-val">15+ Years</div>
                        <div class="metric-lbl">Industry Experience</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon-box"><i class="fa-solid fa-globe"></i></div>
                    <div>
                        <div class="metric-val">40+ Countries</div>
                        <div class="metric-lbl">Projects Delivered</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Openings Section -->
    <main class="careers-container">
        
        <!-- Controls Bar -->
        <div class="careers-controls-wrap">
            <div class="careers-search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="job-search-input" class="careers-search-input" placeholder="Search roles...">
            </div>

            <div class="careers-filter-tabs">
                <button class="career-tab-btn active" data-filter="all">All</button>
                <button class="career-tab-btn" data-filter="Production">
                    <i class="fa-solid fa-gear"></i> Production <span class="career-tab-count">3</span>
                </button>
                <button class="career-tab-btn" data-filter="Post Production">
                    <i class="fa-solid fa-scissors"></i> Post Production <span class="career-tab-count">2</span>
                </button>
                <button class="career-tab-btn" data-filter="Strategy">
                    <i class="fa-solid fa-chart-line"></i> Strategy <span class="career-tab-count">1</span>
                </button>
            </div>
        </div>

        <div class="showing-openings-text" id="openings-counter">Showing 6 openings</div>

        <!-- Openings List -->
        <div class="openings-list-wrap" id="openings-list">
            <?php foreach ($jobOpenings as $job): ?>
                <div class="job-opening-card" data-dept="<?php echo htmlspecialchars($job['dept']); ?>" data-title="<?php echo htmlspecialchars(strtolower($job['title'])); ?>">
                    <div class="job-card-top-row">
                        <div>
                            <div class="job-badges-group">
                                <span class="job-dept-pill"><?php echo htmlspecialchars($job['dept']); ?></span>
                                <span class="job-meta-pill"><?php echo htmlspecialchars($job['type']); ?></span>
                                <span class="job-meta-pill"><?php echo htmlspecialchars($job['posted']); ?></span>
                            </div>
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <div class="job-details-meta">
                                <span><i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span><i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i> <?php echo htmlspecialchars($job['salary']); ?></span>
                            </div>
                        </div>
                        <div class="job-action-side">
                            <button class="btn-apply-job" onclick="openApplyModal('<?php echo htmlspecialchars(addslashes($job['title'])); ?>')">Apply</button>
                            <button class="btn-toggle-job-details" onclick="toggleJobCard(this)"><i class="fa-solid fa-chevron-down"></i></button>
                        </div>
                    </div>

                    <!-- Rich Expandable Details Drawer matching Screenshots -->
                    <div class="job-expanded-details">
                        <p class="job-overview-text"><?php echo htmlspecialchars($job['overview']); ?></p>

                        <?php if (!empty($job['responsibilities'])): ?>
                            <div class="job-section-block">
                                <div class="job-section-heading">🧰 Responsibilities</div>
                                <?php if (is_array($job['responsibilities'])): ?>
                                    <ul class="job-section-list">
                                        <?php foreach ($job['responsibilities'] as $item): ?>
                                            <li><i class="fa-solid fa-circle-check check-icon"></i> <?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="job-section-html">
                                        <?php echo $job['responsibilities']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['requirements'])): ?>
                            <div class="job-section-block">
                                <div class="job-section-heading">🛡️ Requirements</div>
                                <?php if (is_array($job['requirements'])): ?>
                                    <ul class="job-section-list">
                                        <?php foreach ($job['requirements'] as $item): ?>
                                            <li><i class="fa-solid fa-circle-check check-icon"></i> <?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="job-section-html">
                                        <?php echo $job['requirements']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['niceToHave'])): ?>
                            <div class="job-section-block">
                                <div class="job-section-heading">⭐ Nice to Have</div>
                                <?php if (is_array($job['niceToHave'])): ?>
                                    <ul class="job-section-list">
                                        <?php foreach ($job['niceToHave'] as $item): ?>
                                            <li><i class="fa-solid fa-circle-check check-icon"></i> <?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="job-section-html">
                                        <?php echo $job['niceToHave']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($job['benefits'])): ?>
                            <div class="job-section-block">
                                <div class="job-section-heading">🎁 Benefits</div>
                                <?php if (is_array($job['benefits'])): ?>
                                    <ul class="job-section-list">
                                        <?php foreach ($job['benefits'] as $item): ?>
                                            <li><i class="fa-solid fa-circle-check check-icon"></i> <?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="job-section-html">
                                        <?php echo $job['benefits']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="job-action-footer-row">
                            <a href="mailto:careers@falhen.com?subject=Application%20for%20<?php echo rawurlencode($job['title']); ?>" class="btn-job-action"><i class="fa-regular fa-envelope"></i> Email Us</a>
                            <button class="btn-job-action" onclick="shareJob('<?php echo htmlspecialchars(addslashes($job['title'])); ?>')"><i class="fa-solid fa-share-nodes"></i> Share</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Why Work Here Perks -->
        <section class="why-work-section">
            <div class="hiring-badge">Life at Falhen</div>
            <h2 class="why-work-title">Why Work Here?</h2>
            <p class="why-work-subtitle">We built Falhen around the idea that great work comes from great people who are supported, challenged, and inspired.</p>

            <div class="perks-grid">
                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-gamepad"></i></div>
                    <h3 class="perk-card-title">Creative Freedom</h3>
                    <p class="perk-card-desc">Pitch your own ideas. Own your projects from concept to delivery.</p>
                </div>

                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-people-group"></i></div>
                    <h3 class="perk-card-title">Collaborative Culture</h3>
                    <p class="perk-card-desc">Work alongside editors, strategists, and producers who push each other to be better.</p>
                </div>

                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-rocket"></i></div>
                    <h3 class="perk-card-title">Cutting-Edge Gear</h3>
                    <p class="perk-card-desc">Access RED, Sony FX, DJI drones, and a fully equipped post-production suite.</p>
                </div>

                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-globe"></i></div>
                    <h3 class="perk-card-title">Travel Opportunities</h3>
                    <p class="perk-card-desc">Film on location across the US and internationally — over 40 countries covered.</p>
                </div>

                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-heart"></i></div>
                    <h3 class="perk-card-title">Work-Life Balance</h3>
                    <p class="perk-card-desc">Flexible hours, unlimited PTO, and a culture that respects your time off.</p>
                </div>

                <div class="perk-card-box">
                    <div class="perk-icon-wrap"><i class="fa-solid fa-seedling"></i></div>
                    <h3 class="perk-card-title">Growth Mindset</h3>
                    <p class="perk-card-desc">$3,000 annual development budget. Conferences, courses, and certifications.</p>
                </div>
            </div>
        </section>

        <!-- Do Not See Right Role Callout -->
        <div class="not-right-role-card">
            <div class="not-right-role-icon"><i class="fa-solid fa-envelope"></i></div>
            <h3 class="not-right-role-title">Do not see the right role?</h3>
            <p class="not-right-role-desc">We are always looking for talented people. Send us your resume and we will reach out when something opens up.</p>
            <div class="not-right-role-btns">
                <a href="javascript:void(0)" onclick="openApplyModal('General Application')" class="btn-send-resume"><i class="fa-solid fa-paper-plane"></i> Send Your Resume</a>
                <a href="mailto:careers@falhen.com" class="btn-email-direct"><i class="fa-solid fa-envelope"></i> Email Us Directly</a>
            </div>
        </div>

        <!-- Footer Bar -->
        <div class="careers-footer-bar">
            <div>© 2026 Falhen Media. All rights reserved.</div>
            <div class="careers-footer-links">
                <a href="/">Home</a>
                <a href="/index.php#about">About</a>
                <a href="/services.php">Services</a>
                <a href="/portfolio.php">Portfolio</a>
                <a href="/contact.php">Contact</a>
            </div>
        </div>

    </main>

    <!-- Application Modal -->
    <div class="apply-modal-backdrop" id="applyModal">
        <div class="apply-modal-box">
            <button class="apply-modal-close" onclick="closeApplyModal()"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="apply-modal-title">Apply for Position</h3>
            <div class="apply-modal-role-name" id="modalRoleName">Senior Video Producer</div>

            <form id="careerApplicationForm" onsubmit="handleApplySubmit(event)">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="job_title" id="modalPositionInput" value="">
                
                <div class="form-group-field">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="John Doe">
                </div>

                <div class="form-group-field">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group-field">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="+1 (555) 000-0000">
                </div>

                <div class="form-group-field">
                    <label>Portfolio / Showreel URL</label>
                    <input type="url" name="portfolio_url" placeholder="https://vimeo.com/yourportfolio">
                </div>

                <div class="form-group-field">
                    <label>LinkedIn Profile URL</label>
                    <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/yourprofile">
                </div>

                <div class="form-group-field">
                    <label>Cover Note / Pitch *</label>
                    <textarea name="cover_note" rows="4" required placeholder="Tell us why you would be a great fit for Falhen..."></textarea>
                </div>

                <div id="applyStatusMsg" style="display: none; margin-bottom: 14px; padding: 10px 14px; border-radius: 8px; font-size: 0.88rem; font-weight: 600;"></div>

                <button type="submit" id="btnSubmitApply" class="btn-send-resume" style="width: 100%; justify-content: center; margin-top: 10px;">Submit Application</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleJobCard(btn) {
            const card = btn.closest('.job-opening-card');
            card.classList.toggle('open');
            const icon = btn.querySelector('i');
            if (card.classList.contains('open')) {
                icon.className = 'fa-solid fa-chevron-up';
            } else {
                icon.className = 'fa-solid fa-chevron-down';
            }
        }

        function openApplyModal(roleName) {
            document.getElementById('modalRoleName').textContent = roleName;
            document.getElementById('modalPositionInput').value = roleName;
            const statusMsg = document.getElementById('applyStatusMsg');
            if (statusMsg) statusMsg.style.display = 'none';
            document.getElementById('applyModal').style.display = 'flex';
        }

        function closeApplyModal() {
            document.getElementById('applyModal').style.display = 'none';
        }

        function handleApplySubmit(e) {
            e.preventDefault();
            const form = document.getElementById('careerApplicationForm');
            const btn = document.getElementById('btnSubmitApply');
            const msgBox = document.getElementById('applyStatusMsg');
            const formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            msgBox.style.display = 'none';

            fetch('/api/submit_application.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = 'Submit Application';
                if (data.success) {
                    msgBox.style.background = '#f0fdf4';
                    msgBox.style.border = '1px solid #bbf7d0';
                    msgBox.style.color = '#166534';
                    msgBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
                    msgBox.style.display = 'block';
                    form.reset();
                    setTimeout(() => {
                        closeApplyModal();
                    }, 2500);
                } else {
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.border = '1px solid #fecaca';
                    msgBox.style.color = '#991b1b';
                    msgBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.message;
                    msgBox.style.display = 'block';
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = 'Submit Application';
                msgBox.style.background = '#fef2f2';
                msgBox.style.border = '1px solid #fecaca';
                msgBox.style.color = '#991b1b';
                msgBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> An error occurred. Please try again.';
                msgBox.style.display = 'block';
            });
        }

        function shareJob(title) {
            if (navigator.share) {
                navigator.share({
                    title: title + ' at Falhen Media',
                    url: window.location.href
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Job link copied to clipboard!');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const filterBtns = document.querySelectorAll('.career-tab-btn');
            const searchInput = document.getElementById('job-search-input');
            const jobCards = document.querySelectorAll('.job-opening-card');
            const counterDisplay = document.getElementById('openings-counter');

            let currentFilter = 'all';
            let currentSearch = '';

            function updateJobs() {
                let count = 0;
                jobCards.forEach(card => {
                    const dept = card.getAttribute('data-dept');
                    const title = card.getAttribute('data-title');

                    const matchesDept = (currentFilter === 'all' || dept.toLowerCase() === currentFilter.toLowerCase());
                    const matchesSearch = (!currentSearch || title.includes(currentSearch));

                    if (matchesDept && matchesSearch) {
                        card.style.display = 'block';
                        count++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (counterDisplay) {
                    counterDisplay.textContent = `Showing ${count} openings`;
                }
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    updateJobs();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    currentSearch = e.target.value.toLowerCase().trim();
                    updateJobs();
                });
            }
        });
    </script>

</body>
</html>
