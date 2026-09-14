<?php
/**
 * Falhen Media - Vendor & Contractor Onboarding Portal
 * Multi-Step Onboarding Process (Role, Policy, Legal Agreements, Tax & Info, Done)
 */
require_once __DIR__ . '/includes/functions.php';

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Onboarding | Falhen Media</title>
    <meta name="description" content="Complete your secure onboarding process to formalize your engagement with Falhen Media.">
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #E11D48;
            --primary-hover: #BE123C;
            --primary-light: #FFF1F2;
            --primary-border: #FECDD3;
            --dark: #0F172A;
            --slate-800: #1E293B;
            --slate-600: #475569;
            --slate-500: #64748B;
            --slate-400: #94A3B8;
            --slate-200: #E2E8F0;
            --slate-100: #F1F5F9;
            --slate-50: #F8FAFC;
            --white: #FFFFFF;
            --success: #10B981;
            --success-light: #ECFDF5;
            --radius-lg: 20px;
            --radius-md: 14px;
            --radius-sm: 8px;
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #F8FAFC;
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px 20px 60px 20px;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Bar & Brand */
        .onboarding-brand-bar {
            max-width: 860px;
            margin: 0 auto 24px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .brand-logo img {
            height: 42px;
            width: auto;
            object-fit: contain;
        }

        .back-site-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--slate-600);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .back-site-link:hover {
            color: var(--primary);
        }

        /* Main Container */
        .onboarding-wrapper {
            max-width: 860px;
            width: 100%;
            margin: 0 auto;
        }

        /* Header Intro */
        .onboarding-intro {
            text-align: center;
            margin-bottom: 28px;
        }

        .badge-vendor {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid var(--primary-border);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: 0.3px;
        }

        .onboarding-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .onboarding-subtitle {
            font-size: 0.98rem;
            color: var(--slate-500);
            max-width: 580px;
            margin: 0 auto;
            line-height: 1.55;
        }

        /* Card Container */
        .onboarding-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05), 0 4px 12px -2px rgba(15, 23, 42, 0.02);
            padding: 38px 44px;
            position: relative;
        }

        @media (max-width: 768px) {
            .onboarding-card {
                padding: 24px 20px;
            }
            .onboarding-title {
                font-size: 1.8rem;
            }
        }

        /* Stepper Navigation */
        .stepper-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-bottom: 38px;
            padding: 0 10px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            cursor: default;
        }

        .step-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            background: var(--slate-100);
            color: var(--slate-400);
            border: 1.5px solid var(--slate-200);
            transition: var(--transition);
            margin-bottom: 8px;
        }

        .step-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--slate-400);
            transition: var(--transition);
            white-space: nowrap;
        }

        /* Active & Completed Stepper States */
        .step-item.active .step-icon-circle {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(225, 29, 72, 0.15);
        }

        .step-item.active .step-label {
            color: var(--primary);
            font-weight: 700;
        }

        .step-item.completed .step-icon-circle {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .step-item.completed .step-label {
            color: var(--dark);
            font-weight: 600;
        }

        /* Connecting lines */
        .stepper-line {
            flex: 1;
            height: 2px;
            background: var(--slate-200);
            margin: -24px 8px 0 8px;
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .stepper-line.completed {
            background: var(--primary);
        }

        /* Step Section Headers */
        .step-header {
            margin-bottom: 24px;
        }

        .step-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .step-desc {
            font-size: 0.92rem;
            color: var(--slate-500);
        }

        /* Role Cards Grid (Step 1) */
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 34px;
        }

        @media (max-width: 768px) {
            .role-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .role-card {
            border: 1.5px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 24px 20px;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .role-card:hover {
            border-color: var(--slate-300);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.04);
        }

        .role-card.selected {
            border-color: var(--primary);
            background: #FFFBFB;
            box-shadow: 0 0 0 1px var(--primary), 0 8px 20px -4px rgba(225, 29, 72, 0.08);
        }

        .role-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background: var(--slate-100);
            color: var(--slate-500);
            margin-bottom: 18px;
            transition: var(--transition);
        }

        .role-card.selected .role-icon-box {
            background: var(--primary-light);
            color: var(--primary);
        }

        .role-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            transition: var(--transition);
        }

        .role-card.selected .role-card-title {
            color: var(--primary);
        }

        .role-card-desc {
            font-size: 0.84rem;
            color: var(--slate-500);
            line-height: 1.5;
        }

        /* Bottom Action Buttons */
        .step-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--slate-200);
            padding-top: 24px;
            margin-top: 28px;
        }

        .btn-continue {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            margin-left: auto;
        }

        .btn-continue:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3);
        }

        .btn-continue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-back-step {
            background: transparent;
            color: var(--slate-600);
            border: 1px solid var(--slate-200);
            padding: 13px 22px;
            border-radius: 10px;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-back-step:hover {
            background: var(--slate-100);
            color: var(--dark);
        }

        /* Policy & Agreement Box (Step 2 & 3) */
        .policy-scroll-box {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 22px 24px;
            max-height: 280px;
            overflow-y: auto;
            margin-bottom: 22px;
            font-size: 0.88rem;
            line-height: 1.65;
            color: var(--slate-800);
        }

        .policy-item {
            margin-bottom: 18px;
        }

        .policy-item:last-child {
            margin-bottom: 0;
        }

        .policy-item-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .policy-item-title i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        /* Custom Checkbox */
        .compliance-check {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            cursor: pointer;
            user-select: none;
        }

        .compliance-check input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: var(--primary);
            cursor: pointer;
            flex-shrink: 0;
        }

        .compliance-label {
            font-size: 0.88rem;
            color: var(--slate-800);
            font-weight: 500;
            line-height: 1.45;
        }

        /* Form Inputs (Step 4) */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        @media (max-width: 680px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--slate-800);
            margin-bottom: 6px;
        }

        .form-label .req {
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--slate-200);
            background: var(--white);
            font-size: 0.92rem;
            color: var(--dark);
            transition: var(--transition);
            outline: none;
            font-family: inherit;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
        }

        .form-section-divider {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dark);
            margin: 24px 0 14px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-divider i {
            color: var(--primary);
        }

        /* Digital Signature Preview */
        .sig-preview-card {
            background: var(--slate-50);
            border: 1px dashed var(--slate-300);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin: 16px 0 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sig-rendered-text {
            font-family: 'Dancing Script', cursive;
            font-size: 2.2rem;
            color: var(--dark);
            min-height: 48px;
            display: flex;
            align-items: center;
        }

        /* Step 5 Done View */
        .done-container {
            text-align: center;
            padding: 20px 10px 10px 10px;
        }

        .done-icon-box {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: var(--success-light);
            color: var(--success);
            font-size: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.12);
            animation: pulse-success 2s infinite ease-in-out;
        }

        @keyframes pulse-success {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.2); }
            70% { box-shadow: 0 0 0 16px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .done-ref-card {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 20px;
            max-width: 520px;
            margin: 24px auto;
            text-align: left;
        }

        .done-ref-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--slate-200);
        }

        .done-ref-row:last-child {
            border-bottom: none;
        }

        .done-ref-label {
            color: var(--slate-500);
            font-weight: 600;
        }

        .done-ref-val {
            font-weight: 700;
            color: var(--dark);
        }

        .badge-ref-code {
            background: var(--primary-light);
            color: var(--primary);
            font-family: monospace;
            font-size: 0.92rem;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--primary-border);
        }

        /* Step Panels */
        .step-panel {
            display: none;
        }

        .step-panel.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Loading Spinner Overlay */
        .loading-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(2px);
            border-radius: var(--radius-lg);
            z-index: 50;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 44px;
            height: 44px;
            border: 4px solid var(--slate-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- BRAND BAR -->
    <div class="onboarding-brand-bar">
        <a href="/" class="brand-logo" aria-label="Falhen Media">
            <img src="/assets/img/icons/logo.png" alt="Falhen Media" onerror="this.src='/assets/falhenmedia_logo.png'">
        </a>
        <a href="/" class="back-site-link">
            <i class="fa-solid fa-arrow-left"></i> Back to website
        </a>
    </div>

    <!-- MAIN ONBOARDING CARD -->
    <div class="onboarding-wrapper">
        <div class="onboarding-intro">
            <div class="badge-vendor">
                <i class="fa-solid fa-building-user"></i> Vendor Onboarding
            </div>
            <h1 class="onboarding-title">Join the Falhen Media Team</h1>
            <p class="onboarding-subtitle">Complete this secure onboarding process to formalize your engagement. Takes about 5 minutes.</p>
        </div>

        <div class="onboarding-card">
            <!-- LOADING OVERLAY -->
            <div id="loadingOverlay" class="loading-overlay">
                <div class="spinner"></div>
                <div style="font-size: 0.95rem; font-weight: 700; color: var(--dark);">Submitting your vendor onboarding...</div>
                <div style="font-size: 0.82rem; color: var(--slate-500);">Securing legal agreement records</div>
            </div>

            <!-- STEPPER -->
            <div class="stepper-container">
                <!-- Step 1 -->
                <div class="step-item active" id="stepIndicator1">
                    <div class="step-icon-circle">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="step-label">Your Role</span>
                </div>
                <div class="stepper-line" id="stepLine1"></div>

                <!-- Step 2 -->
                <div class="step-item" id="stepIndicator2">
                    <div class="step-icon-circle">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span class="step-label">Workplace Policy</span>
                </div>
                <div class="stepper-line" id="stepLine2"></div>

                <!-- Step 3 -->
                <div class="step-item" id="stepIndicator3">
                    <div class="step-icon-circle">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <span class="step-label">Legal Agreements</span>
                </div>
                <div class="stepper-line" id="stepLine3"></div>

                <!-- Step 4 -->
                <div class="step-item" id="stepIndicator4">
                    <div class="step-icon-circle">
                        <i class="fa-regular fa-id-card"></i>
                    </div>
                    <span class="step-label">Tax &amp; Info</span>
                </div>
                <div class="stepper-line" id="stepLine4"></div>

                <!-- Step 5 -->
                <div class="step-item" id="stepIndicator5">
                    <div class="step-icon-circle">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>
                    <span class="step-label">Done</span>
                </div>
            </div>

            <!-- STEP 1: YOUR ROLE -->
            <div class="step-panel active" id="stepPanel1">
                <div class="step-header">
                    <h2 class="step-title">What will you be doing?</h2>
                    <p class="step-desc">Select the role that best describes your engagement with Falhen Media.</p>
                </div>

                <div class="role-cards-grid">
                    <!-- Card 1: Photographer / Videographer (Default Selected) -->
                    <div class="role-card selected" onclick="selectRole('photographer_videographer', this)">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <h3 class="role-card-title">Photographer / Videographer</h3>
                        <p class="role-card-desc">Independent contractor providing photo or video services for Falhen Media.</p>
                    </div>

                    <!-- Card 2: Intern -->
                    <div class="role-card" onclick="selectRole('intern', this)">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h3 class="role-card-title">Intern</h3>
                        <p class="role-card-desc">Student or early-career professional joining for a fixed term.</p>
                    </div>

                    <!-- Card 3: Volunteer -->
                    <div class="role-card" onclick="selectRole('volunteer', this)">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                        <h3 class="role-card-title">Volunteer</h3>
                        <p class="role-card-desc">Unpaid contributor supporting Falhen Media productions.</p>
                    </div>
                </div>

                <div class="step-action-bar">
                    <button type="button" class="btn-continue" onclick="goToStep(2)">
                        Continue <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: WORKPLACE POLICY -->
            <div class="step-panel" id="stepPanel2">
                <div class="step-header">
                    <h2 class="step-title">Workplace Policy &amp; On-Set Code of Conduct</h2>
                    <p class="step-desc">Please review our standards for safety, on-set conduct, media handling, and professional ethics.</p>
                </div>

                <div class="policy-scroll-box">
                    <div class="policy-item">
                        <div class="policy-item-title"><i class="fa-solid fa-shield-virus"></i> 1. On-Set Safety &amp; Zero-Tolerance Conduct</div>
                        <p>Falhen Media sets are professional, creative, and safe environments. All crew, contractors, interns, and volunteers must strictly comply with safety protocols surrounding lighting fixtures, heavy grip gear, electrical lines, and drone operations. We maintain strict zero-tolerance for harassment, discrimination, intoxication, or unsafe equipment handling.</p>
                    </div>

                    <div class="policy-item">
                        <div class="policy-item-title"><i class="fa-solid fa-camera-rotate"></i> 2. Equipment Care &amp; Responsibility</div>
                        <p>High-end cinema packages (RED, Sony FX cinema line, prime cinema lenses, wireless video links) must be handled with utmost diligence. Immediately report any gear irregularities, damaged cables, or malfunctioning gimbal motors to the Lead Producer.</p>
                    </div>

                    <div class="policy-item">
                        <div class="policy-item-title"><i class="fa-solid fa-hard-drive"></i> 3. Media Asset Security &amp; SD Card Ingestion</div>
                        <p>Captured footage represents significant client investment. Never format an SD/CFexpress card until footage has been dual-verified and offloaded to primary and backup production arrays. Transferring raw client footage to personal cloud storage is strictly prohibited.</p>
                    </div>

                    <div class="policy-item">
                        <div class="policy-item-title"><i class="fa-solid fa-lock"></i> 4. Client Confidentiality &amp; Social Media Embargo</div>
                        <p>Unreleased commercial projects, brand treatments, and celebrity talent shoots are strictly embargoed. You may not post behind-the-scenes clips, photos, or teaser stories to Instagram, TikTok, or personal websites until the project has officially premiered.</p>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label class="compliance-check">
                        <input type="checkbox" id="checkPolicyCode" onchange="validateStep2()">
                        <span class="compliance-label">I have read, understood, and agree to uphold the Falhen Media Workplace Policies &amp; On-Set Safety Guidelines.</span>
                    </label>

                    <label class="compliance-check">
                        <input type="checkbox" id="checkPolicyConfidentiality" onchange="validateStep2()">
                        <span class="compliance-label">I commit to absolute media confidentiality and agree not to publish unreleased client assets or behind-the-scenes materials without prior written consent.</span>
                    </label>
                </div>

                <div class="step-action-bar">
                    <button type="button" class="btn-back-step" onclick="goToStep(1)">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-continue" id="btnStep2Continue" disabled onclick="goToStep(3)">
                        Continue <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 3: LEGAL AGREEMENTS & NDA -->
            <div class="step-panel" id="stepPanel3">
                <div class="step-header">
                    <h2 class="step-title">Legal Agreements &amp; Non-Disclosure (NDA)</h2>
                    <p class="step-desc">Review and digitally execute your tailored engagement agreement as a <strong id="roleBadgeText" style="color: var(--primary);">Photographer / Videographer</strong>.</p>
                </div>

                <div class="policy-scroll-box" id="legalAgreementTextContainer">
                    <!-- Dynamic Agreement Content Injected via JS based on selected role -->
                </div>

                <!-- Signature Capture Block -->
                <div style="background: #ffffff; border: 1px solid var(--slate-200); border-radius: var(--radius-md); padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <span style="font-weight: 700; font-size: 0.92rem; color: var(--dark);"><i class="fa-solid fa-signature" style="color: var(--primary); margin-right: 6px;"></i> Legal E-Signature Attestation</span>
                        <span style="font-size: 0.8rem; color: var(--slate-500);">Date: <strong id="currentDateDisplay"><?php echo date('F j, Y'); ?></strong></span>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Full Legal Name (as typed signature) <span class="req">*</span></label>
                        <input type="text" id="signatureNameInput" class="form-control" placeholder="e.g. John Doe" oninput="updateSignaturePreview(this.value)">
                    </div>

                    <!-- Calligraphic Signature Preview -->
                    <div class="sig-preview-card">
                        <div>
                            <div style="font-size: 0.75rem; color: var(--slate-400); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">E-Signature Preview</div>
                            <div class="sig-rendered-text" id="sigRenderedText">John Doe</div>
                        </div>
                        <div style="text-align: right;">
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--success); font-weight: 700;">
                                <i class="fa-solid fa-circle-check"></i> Verified Digital ID
                            </span>
                            <div style="font-size: 0.72rem; color: var(--slate-400); margin-top: 2px;">Falhen Media Digital Engine</div>
                        </div>
                    </div>

                    <label class="compliance-check" style="margin-bottom: 0;">
                        <input type="checkbox" id="checkLegalAcceptance" onchange="validateStep3()">
                        <span class="compliance-label">I certify that this digital signature is legally binding and has the full legal effect and enforceability of a physical handwritten signature.</span>
                    </label>
                </div>

                <div class="step-action-bar">
                    <button type="button" class="btn-back-step" onclick="goToStep(2)">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-continue" id="btnStep3Continue" disabled onclick="goToStep(4)">
                        Continue <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 4: TAX & INFO -->
            <div class="step-panel" id="stepPanel4">
                <div class="step-header">
                    <h2 class="step-title">Personal Information &amp; Payout Details</h2>
                    <p class="step-desc">Provide your contact, identification, and bank remittance information for record-keeping and project remuneration.</p>
                </div>

                <form id="onboardingForm" onsubmit="submitOnboarding(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="role" id="formRoleInput" value="photographer_videographer">

                    <!-- Personal Information -->
                    <div class="form-section-divider">
                        <i class="fa-solid fa-user-check"></i> Personal &amp; Contact Details
                    </div>

                    <div class="form-grid">
                        <div>
                            <label class="form-label">Full Legal Name <span class="req">*</span></label>
                            <input type="text" name="full_name" id="fieldFullName" class="form-control" placeholder="First &amp; Last Name" required oninput="syncSignatureName(this.value)">
                        </div>

                        <div>
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" name="email" id="fieldEmail" class="form-control" placeholder="you@falhenmedia.com" required>
                        </div>

                        <div>
                            <label class="form-label">Phone Number (WhatsApp) <span class="req">*</span></label>
                            <input type="tel" name="phone" id="fieldPhone" class="form-control" placeholder="+234 802 748 7449" required>
                        </div>

                        <div>
                            <label class="form-label">Residential Address / City</label>
                            <input type="text" name="address" id="fieldAddress" class="form-control" placeholder="e.g. Lagos, Nigeria">
                        </div>
                    </div>

                    <!-- Identification & Tax -->
                    <div class="form-section-divider">
                        <i class="fa-solid fa-id-card"></i> Identification &amp; Tax Information
                    </div>

                    <div class="form-grid">
                        <div>
                            <label class="form-label">Identification Document Type</label>
                            <select name="id_type" class="form-control">
                                <option value="National ID / NIN">National Identity Card / NIN</option>
                                <option value="Driver's License">Driver's License</option>
                                <option value="International Passport">International Passport</option>
                                <option value="Voter's Card">Voter's Card</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Tax ID / NIN Number</label>
                            <input type="text" name="tax_id" class="form-control" placeholder="e.g. 11-digit NIN or Tax ID">
                        </div>
                    </div>

                    <!-- Payout & Bank Info (For Contractors & Interns) -->
                    <div id="payoutSectionBlock">
                        <div class="form-section-divider">
                            <i class="fa-solid fa-building-columns"></i> Remuneration &amp; Bank Settlement Account
                        </div>

                        <div class="form-grid">
                            <div>
                                <label class="form-label">Preferred Currency</label>
                                <select name="currency" class="form-control">
                                    <option value="NGN">NGN - Nigerian Naira (₦)</option>
                                    <option value="USD">USD - US Dollar ($)</option>
                                    <option value="GBP">GBP - British Pound (£)</option>
                                    <option value="EUR">EUR - Euro (€)</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Bank / Provider Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="e.g. Access Bank, Zenith, Raenest, Wise">
                            </div>

                            <div>
                                <label class="form-label">Account Number / IBAN</label>
                                <input type="text" name="account_number" class="form-control" placeholder="10-digit Account No or IBAN">
                            </div>

                            <div>
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" name="account_name" class="form-control" placeholder="Name on Bank Account">
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="form-section-divider">
                        <i class="fa-solid fa-phone-volume"></i> Emergency Contact
                    </div>

                    <div class="form-grid">
                        <div>
                            <label class="form-label">Emergency Contact Name</label>
                            <input type="text" name="emergency_name" class="form-control" placeholder="Contact Full Name">
                        </div>

                        <div>
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_relationship" class="form-control" placeholder="e.g. Spouse, Parent, Sibling, Colleague">
                        </div>

                        <div class="form-group-full">
                            <label class="form-label">Emergency Contact Phone Number</label>
                            <input type="tel" name="emergency_phone" class="form-control" placeholder="Emergency Phone Number">
                        </div>
                    </div>

                    <div class="step-action-bar">
                        <button type="button" class="btn-back-step" onclick="goToStep(3)">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-continue" id="btnSubmitOnboarding">
                            <i class="fa-solid fa-circle-check"></i> Complete Onboarding
                        </button>
                    </div>
                </form>
            </div>

            <!-- STEP 5: DONE -->
            <div class="step-panel" id="stepPanel5">
                <div class="done-container">
                    <div class="done-icon-box">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2 class="onboarding-title" style="font-size: 2rem;">Onboarding Complete!</h2>
                    <p class="onboarding-subtitle">Welcome to the Falhen Media team. Your vendor profile and signed agreements have been officially recorded.</p>

                    <div class="done-ref-card">
                        <div class="done-ref-row">
                            <span class="done-ref-label">Reference Code</span>
                            <span class="badge-ref-code" id="doneRefCode">FAL-VND-2026-0000</span>
                        </div>
                        <div class="done-ref-row">
                            <span class="done-ref-label">Engagement Role</span>
                            <span class="done-ref-val" id="doneRoleLabel">Photographer / Videographer</span>
                        </div>
                        <div class="done-ref-row">
                            <span class="done-ref-label">Vendor Name</span>
                            <span class="done-ref-val" id="doneFullName">John Doe</span>
                        </div>
                        <div class="done-ref-row">
                            <span class="done-ref-label">Status</span>
                            <span style="color: var(--success); font-weight: 800;"><i class="fa-solid fa-circle-check"></i> Verified &amp; Active</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 26px;">
                        <a id="btnDownloadAgreement" href="#" target="_blank" class="btn-continue" style="background: var(--dark); text-decoration: none; margin: 0;">
                            <i class="fa-solid fa-file-pdf"></i> Download Signed Agreement
                        </a>
                        <a href="/" class="btn-back-step" style="text-decoration: none;">
                            <i class="fa-solid fa-house"></i> Return to Home
                        </a>
                        <a href="/admin/login.php" class="btn-back-step" style="text-decoration: none;">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Portal Login
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let currentStep = 1;
        let selectedRole = 'photographer_videographer';

        const roleAgreements = {
            'photographer_videographer': `
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-file-contract"></i> 1. Master Services Agreement (MSA) &bull; Independent Contractor Terms</div>
                    <p>This Master Services Agreement governs all cinematography, photography, lighting, and post-production engagements commissioned by Falhen Media Ltd. The Contractor acts strictly as an independent service provider and is responsible for professional deliverable quality, adherence to shoot call-sheets, and prompt footage delivery.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-certificate"></i> 2. Intellectual Property &amp; Work-Made-For-Hire Assignment</div>
                    <p>All RAW camera media, photographs, B-roll reels, audio stems, and project files captured during production are "Work Made for Hire". Falhen Media Ltd and its clients retain exclusive worldwide copyright, commercial distribution rights, and synchronization licenses across all media platforms in perpetuity.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-lock"></i> 3. Comprehensive Non-Disclosure Agreement (NDA)</div>
                    <p>Contractor agrees to hold in strictest confidence all unreleased commercial treatments, corporate film footage, client contact details, and project budgets for a minimum period of two (2) years following project completion.</p>
                </div>
            `,
            'intern': `
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-graduation-cap"></i> 1. Internship Mentorship &amp; Practical Training Terms</div>
                    <p>This engagement is established to provide structured experiential training in video production, camera operation, audio engineering, and studio operations under the direct supervision of Falhen Media Senior Producers.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-certificate"></i> 2. Intellectual Property &amp; Deliverables Assignment</div>
                    <p>All creative works, edit cuts, and project collateral developed during the internship term shall be the sole property of Falhen Media Ltd, with reasonable portfolio attribution granted upon formal approval.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-lock"></i> 3. Non-Disclosure &amp; Studio Confidentiality</div>
                    <p>The Intern agrees to safeguard proprietary studio workflows, client assets, and production schedules with absolute confidentiality.</p>
                </div>
            `,
            'volunteer': `
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-heart"></i> 1. Volunteer Production Contributor Terms</div>
                    <p>The Volunteer acknowledges that this engagement is voluntary community and creative production support, provided without expectation of regular employment wages. Falhen Media provides on-set catering, safety gear, and assigned travel stipends as applicable.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-certificate"></i> 2. Media Release &amp; Creative Attribution</div>
                    <p>The Volunteer grants Falhen Media permission to record, photograph, and include volunteer contributions in company documentary and behind-the-scenes materials.</p>
                </div>
                <div class="policy-item">
                    <div class="policy-item-title"><i class="fa-solid fa-lock"></i> 3. Non-Disclosure &amp; Set Confidentiality</div>
                    <p>The Volunteer agrees not to disclose unreleased client footage, scripts, or project treatments to external third parties or public social channels.</p>
                </div>
            `
        };

        const roleLabels = {
            'photographer_videographer': 'Photographer / Videographer',
            'intern': 'Intern',
            'volunteer': 'Volunteer'
        };

        // Select Role Function
        function selectRole(roleKey, cardEl) {
            selectedRole = roleKey;
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
            cardEl.classList.add('selected');

            document.getElementById('formRoleInput').value = roleKey;
            document.getElementById('roleBadgeText').innerText = roleLabels[roleKey] || 'Vendor';

            // Show / hide payout section if volunteer
            const payoutBlock = document.getElementById('payoutSectionBlock');
            if (payoutBlock) {
                payoutBlock.style.display = (roleKey === 'volunteer') ? 'none' : 'block';
            }

            renderLegalAgreement();
        }

        function renderLegalAgreement() {
            const container = document.getElementById('legalAgreementTextContainer');
            if (container) {
                container.innerHTML = roleAgreements[selectedRole] || roleAgreements['photographer_videographer'];
            }
        }

        // Stepper Navigation
        function goToStep(stepNum) {
            if (stepNum < 1 || stepNum > 5) return;

            // Step validations
            if (stepNum > 2 && !isStep2Valid()) {
                alert('Please accept the workplace policies and confidentiality terms before proceeding.');
                return;
            }

            if (stepNum > 3 && !isStep3Valid()) {
                alert('Please enter your legal name and accept the e-signature terms to proceed.');
                return;
            }

            currentStep = stepNum;

            // Toggle panels
            for (let i = 1; i <= 5; i++) {
                const panel = document.getElementById('stepPanel' + i);
                const indicator = document.getElementById('stepIndicator' + i);
                const line = document.getElementById('stepLine' + i);

                if (panel) {
                    panel.classList.toggle('active', i === currentStep);
                }

                if (indicator) {
                    indicator.classList.remove('active', 'completed');
                    if (i === currentStep) {
                        indicator.classList.add('active');
                    } else if (i < currentStep) {
                        indicator.classList.add('completed');
                    }
                }

                if (line) {
                    line.classList.toggle('completed', i < currentStep);
                }
            }

            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        // Validate Step 2
        function validateStep2() {
            const c1 = document.getElementById('checkPolicyCode').checked;
            const c2 = document.getElementById('checkPolicyConfidentiality').checked;
            const btn = document.getElementById('btnStep2Continue');
            btn.disabled = !(c1 && c2);
        }

        function isStep2Valid() {
            return document.getElementById('checkPolicyCode').checked && 
                   document.getElementById('checkPolicyConfidentiality').checked;
        }

        // Validate Step 3
        function validateStep3() {
            const name = document.getElementById('signatureNameInput').value.trim();
            const checked = document.getElementById('checkLegalAcceptance').checked;
            const btn = document.getElementById('btnStep3Continue');
            btn.disabled = !(name.length > 2 && checked);
        }

        function isStep3Valid() {
            const name = document.getElementById('signatureNameInput').value.trim();
            const checked = document.getElementById('checkLegalAcceptance').checked;
            return name.length > 2 && checked;
        }

        // Live Signature Text Preview
        function updateSignaturePreview(val) {
            const rendered = document.getElementById('sigRenderedText');
            rendered.innerText = val.trim() || 'Your Signature';

            // Also sync to form full name if full name is currently empty
            const fieldFull = document.getElementById('fieldFullName');
            if (fieldFull && !fieldFull.value.trim()) {
                fieldFull.value = val.trim();
            }
            validateStep3();
        }

        function syncSignatureName(val) {
            const sigInput = document.getElementById('signatureNameInput');
            if (sigInput && !sigInput.value.trim()) {
                sigInput.value = val;
                updateSignaturePreview(val);
            }
        }

        // Final Form Submission
        async function submitOnboarding(e) {
            e.preventDefault();

            const form = document.getElementById('onboardingForm');
            const formData = new FormData(form);

            // Add agreement acknowledgements
            formData.append('workplace_policy_agreed', '1');
            formData.append('nda_agreed', '1');
            formData.append('msa_agreed', '1');
            formData.append('signature_name', document.getElementById('signatureNameInput').value.trim());
            formData.append('signature_date', '<?php echo date('Y-m-d'); ?>');

            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            try {
                const response = await fetch('/api/submit_vendor_onboarding.php', {
                    method: 'POST',
                    body: formData
                });

                const res = await response.json();
                overlay.classList.remove('active');

                if (res.success) {
                    // Populate Step 5 confirmation
                    document.getElementById('doneRefCode').innerText = res.data.reference_code;
                    document.getElementById('doneRoleLabel').innerText = res.data.role_label;
                    document.getElementById('doneFullName').innerText = res.data.full_name;
                    document.getElementById('btnDownloadAgreement').href = res.data.download_url;

                    goToStep(5);
                } else {
                    alert('Submission error: ' + (res.message || 'Please check your inputs and try again.'));
                }
            } catch (err) {
                overlay.classList.remove('active');
                console.error(err);
                alert('A network error occurred while submitting onboarding. Please try again.');
            }
        }

        // Initialize agreement text on load
        document.addEventListener('DOMContentLoaded', () => {
            renderLegalAgreement();
            updateSignaturePreview('');
        });
    </script>
</body>
</html>
