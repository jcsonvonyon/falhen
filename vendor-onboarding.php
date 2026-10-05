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
            font-weight: 700;
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
            background: #10b981;
            color: var(--white);
            border-color: #10b981;
        }

        .step-item.completed .step-label {
            color: #10b981;
            font-weight: 600;
        }

        /* Final Step 5 ("Done") State - Always Green */
        .step-item#stepIndicator5.active .step-icon-circle,
        .step-item#stepIndicator5.completed .step-icon-circle {
            background: #10b981 !important;
            color: var(--white) !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.18) !important;
        }

        .step-item#stepIndicator5.active .step-label,
        .step-item#stepIndicator5.completed .step-label {
            color: #10b981 !important;
            font-weight: 700 !important;
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
            background: #10b981;
        }

        /* Step 2 Input Grid */
        .step2-input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 640px) {
            .step2-input-grid {
                grid-template-columns: 1fr;
            }
        }

        .section-subheading-caps {
            font-size: 0.76rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 14px;
            display: block;
        }

        .address-grid-3 {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .emergency-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        @media (max-width: 640px) {
            .address-grid-3, .emergency-grid-3 {
                grid-template-columns: 1fr;
            }
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
            padding-top: 16px;
            margin-top: 24px;
        }

        .btn-continue {
            background: #dc2626;
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
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .btn-continue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-back-step {
            background: transparent;
            color: #475569;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-back-step:hover {
            background: #f1f5f9;
            color: var(--dark);
            transform: translateX(-2px);
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
            background: #ecfdf5;
            color: #059669;
            font-family: monospace;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #a7f3d0;
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
        <div class="onboarding-intro" id="onboardingIntroHeader">
            <div class="badge-vendor" id="introBadge">
                <i class="fa-solid fa-building-user"></i> Vendor Onboarding
            </div>
            <h1 class="onboarding-title" id="introTitle">Join the Falhen Media Team</h1>
            <p class="onboarding-subtitle" id="introSubtitle">Complete this secure onboarding process to formalize your engagement. Takes about 5 minutes.</p>
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
                    <h2 class="step-title">Workplace Policy</h2>
                    <p class="step-desc">Read and acknowledge Falhen Media's intellectual property and conduct policy.</p>
                </div>

                <div class="policy-scroll-box">
                    <div style="font-weight: 700; color: #0f172a; margin-bottom: 14px; font-size: 0.95rem;">
                        Henad Innovations and Global Services LLC DBA Falhenmedia and Productions — Vendor Workplace Policy
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 12px; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>1. IP Ownership.</strong> All photographs, video footage, raw files, edited files, motion graphics, audio recordings, and any derivative works created during your engagement are the <strong>sole and exclusive property of Henad Innovations and Global Services LLC DBA Falhenmedia and Productions</strong>. You waive moral rights and may not use, reproduce, or distribute any such materials without written consent.
                        </p>
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 12px; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>2. Equipment.</strong> Henad Innovations and Global Services LLC DBA Falhenmedia and Productions equipment must be handled with care and returned in the same condition. Loss or damage due to negligence may result in liability.
                        </p>
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 12px; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>3. Conduct.</strong> All vendors, interns, and volunteers must maintain professional conduct on set, in client interactions, and on social media. Representing Henad Innovations and Global Services LLC DBA Falhenmedia and Productions without written approval is prohibited.
                        </p>
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 12px; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>4. Confidentiality.</strong> Client names, project details, rates, and internal processes are strictly confidential during and after your engagement.
                        </p>
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 12px; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>5. Deliverables.</strong> All deliverables must be submitted in the agreed format and timeline. Unauthorized retention of raw or edited files is prohibited.
                        </p>
                    </div>

                    <div class="policy-item">
                        <p style="margin-bottom: 0; font-size: 0.88rem; line-height: 1.6; color: #334155;">
                            <strong>6. Termination.</strong> Henad Innovations and Global Services LLC DBA Falhenmedia and Productions reserves the right to terminate any engagement at any time for policy violations. All materials must be surrendered immediately upon termination.
                        </p>
                    </div>
                </div>

                <!-- Input Fields for Full Legal Name & Email Address -->
                <div class="step2-input-grid">
                    <div>
                        <label class="form-label">Full Legal Name <span class="req">*</span></label>
                        <input type="text" id="step2LegalName" class="form-control" placeholder="Your legal name" oninput="syncFromStep2Name(this.value)">
                    </div>
                    <div>
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" id="step2Email" class="form-control" placeholder="you@email.com" oninput="syncFromStep2Email(this.value)">
                    </div>
                </div>

                <!-- Checkbox -->
                <div style="margin-bottom: 24px;">
                    <label class="compliance-check" style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; user-select: none;">
                        <input type="checkbox" id="checkPolicyAgreement" onchange="validateStep2()" style="margin-top: 3px; accent-color: var(--primary); width: 18px; height: 18px; flex-shrink: 0; cursor: pointer;">
                        <span class="compliance-label" style="font-size: 0.88rem; line-height: 1.55; color: var(--slate-700);">
                            I have read and agree to the Henad Innovations and Global Services LLC DBA Falhenmedia and Productions Workplace Policy. I acknowledge that all creative work during my engagement is the <strong>exclusive property of Henad Innovations and Global Services LLC DBA Falhenmedia and Productions</strong>.
                        </span>
                    </label>
                </div>

                <div class="step-action-bar">
                    <button type="button" class="btn-back-step" onclick="goToStep(1)">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-continue" id="btnStep2Continue" disabled onclick="goToStep(3)">
                        Sign &amp; Continue <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 3: LEGAL AGREEMENTS & NDA -->
            <div class="step-panel" id="stepPanel3">
                <div class="step-header">
                    <h2 class="step-title">Legal Agreements</h2>
                    <p class="step-desc">Read each agreement, check your acceptance, then draw your signature to legally bind it.</p>
                </div>

                <!-- ESIGN Act and UETA Notice Banner -->
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; display: flex; align-items: center; gap: 12px; color: #92400e; font-size: 0.88rem; line-height: 1.5;">
                    <i class="fa-solid fa-shield-halved" style="color: #d97706; font-size: 1.15rem; flex-shrink: 0;"></i>
                    <span>Your drawn signatures are legally binding under the <strong>ESIGN Act</strong> and <strong>UETA</strong>. Timestamp and metadata are recorded.</span>
                </div>

                <!-- Input Fields for Full Legal Name & Email Address -->
                <div class="step2-input-grid" style="margin-bottom: 22px;">
                    <div>
                        <label class="form-label">Full Legal Name <span class="req">*</span></label>
                        <input type="text" id="step3LegalName" class="form-control" placeholder="As on your government ID" oninput="syncFromStep3Name(this.value)">
                    </div>
                    <div>
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" id="step3Email" class="form-control" placeholder="you@email.com" oninput="syncFromStep3Email(this.value)">
                    </div>
                </div>

                <!-- Agreement Card 1: Non-Disclosure Agreement (NDA) -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 0.95rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <span style="font-weight: 700; font-size: 0.98rem; color: #0f172a;">Non-Disclosure Agreement (NDA)</span>
                        </div>
                        <span style="background: #ffedd5; color: #c2410c; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px;">
                            Confidentiality
                        </span>
                    </div>
                    <div style="padding: 22px 20px; color: #334155; font-size: 0.88rem; line-height: 1.65;">
                        All client identities, project briefs, production schedules, pricing, methodologies, and proprietary workflows disclosed during your engagement are strictly confidential. You agree not to disclose any such information to third parties without prior written consent. These obligations survive termination for three (3) years. Upon request or termination, you agree to promptly return or destroy all materials containing Confidential Information.
                    </div>
                    <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 20px;">
                        <label class="compliance-check" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="checkNdaAgreement" onchange="validateStep3()" style="accent-color: #dc2626; width: 18px; height: 18px; cursor: pointer;">
                            <span class="compliance-label" style="font-size: 0.86rem; color: #334155;">I have read and agree to be legally bound by this Non-Disclosure Agreement.</span>
                        </label>
                    </div>
                </div>

                <!-- Agreement Card 2: Master Service Agreement (MSA) -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 0.95rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <span style="font-weight: 700; font-size: 0.98rem; color: #0f172a;">Master Service Agreement (MSA)</span>
                        </div>
                        <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px;">
                            Service Terms
                        </span>
                    </div>
                    <div style="padding: 22px 20px; color: #334155; font-size: 0.88rem; line-height: 1.65; max-height: 140px; overflow-y: auto;">
                        You are engaged as an independent contractor, not an employee. Services are described in individual Statements of Work (SOWs). All work product, including raw and edited media, is assigned to Henad Innovations and Global Services LLC DBA Falhenmedia and Productions upon creation. You retain no license or ownership rights. Compensation is outlined in individual SOWs. You agree to indemnify Henad Innovations and Global Services LLC DBA Falhenmedia and Productions against claims arising from your negligence or breach of this Agreement.
                    </div>
                    <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 20px;">
                        <label class="compliance-check" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="checkMsaAgreement" onchange="validateStep3()" style="accent-color: #dc2626; width: 18px; height: 18px; cursor: pointer;">
                            <span class="compliance-label" style="font-size: 0.86rem; color: #334155;">I have read and agree to be legally bound by this Master Service Agreement.</span>
                        </label>
                    </div>
                </div>

                <!-- Signature Capture Block (Dual Mode: Typed Signature & Screen Signature) -->
                <div style="background: #ffffff; border: 1px solid var(--slate-200); border-radius: var(--radius-md); padding: 22px 24px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                        <span style="font-weight: 700; font-size: 0.94rem; color: var(--dark);"><i class="fa-solid fa-signature" style="color: var(--primary); margin-right: 6px;"></i> Legal E-Signature Attestation</span>
                        <span style="font-size: 0.8rem; color: var(--slate-500);">Date: <strong id="currentDateDisplay"><?php echo date('F j, Y'); ?></strong></span>
                    </div>

                    <!-- Signature Mode Tabs -->
                    <div class="sig-tabs-bar" style="display: flex; background: var(--slate-100); padding: 4px; border-radius: 10px; margin-bottom: 18px; width: fit-content; gap: 4px;">
                        <button type="button" id="btnSigModeType" class="sig-tab-btn" onclick="switchSignatureMode('type')" style="padding: 8px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: none; background: #ffffff; color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06); transition: var(--transition);">
                            <i class="fa-solid fa-keyboard"></i> As Typed Signature
                        </button>
                        <button type="button" id="btnSigModeDraw" class="sig-tab-btn" onclick="switchSignatureMode('draw')" style="padding: 8px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: none; background: transparent; color: var(--slate-500); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition);">
                            <i class="fa-solid fa-pen-nib"></i> Screen Signature
                        </button>
                    </div>

                    <!-- Hidden input to maintain compat if referenced -->
                    <input type="hidden" id="signatureNameInput">

                    <!-- OPTION 1: AS TYPED SIGNATURE -->
                    <div id="sigTypeModeContainer">
                        <!-- Calligraphic Signature Preview -->
                        <div class="sig-preview-card" style="margin-top: 0; margin-bottom: 18px;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--slate-400); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Typed Signature Preview</div>
                                <div class="sig-rendered-text" id="sigRenderedText">John Doe</div>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--success); font-weight: 700;">
                                    <i class="fa-solid fa-circle-check"></i> Verified Digital ID
                                </span>
                                <div style="font-size: 0.72rem; color: var(--slate-400); margin-top: 2px;">Falhen Media Digital Engine</div>
                            </div>
                        </div>
                    </div>

                    <!-- OPTION 2: NORMAL SCREEN SIGNATURE (CANVAS DRAWING) -->
                    <div id="sigDrawModeContainer" style="display: none; margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label class="form-label" style="margin-bottom: 0;">Sign on Screen (Mouse, Stylus, or Touch) <span class="req">*</span></label>
                            <button type="button" onclick="clearSignatureCanvas()" style="background: var(--slate-100); color: var(--slate-600); border: 1px solid var(--slate-200); padding: 5px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: var(--transition);">
                                <i class="fa-solid fa-rotate-left"></i> Clear Pad
                            </button>
                        </div>
                        <div style="background: #ffffff; border: 1.5px dashed var(--slate-300); border-radius: var(--radius-md); position: relative; overflow: hidden; touch-action: none;">
                            <canvas id="signatureCanvas" width="760" height="170" style="display: block; width: 100%; height: 170px; cursor: crosshair; background: #fafafa;"></canvas>
                            <div style="position: absolute; bottom: 8px; left: 16px; font-size: 0.75rem; color: var(--slate-400); pointer-events: none; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-pen"></i> Sign above using your mouse, stylus, or finger
                            </div>
                        </div>
                    </div>

                    <label class="compliance-check" style="margin-bottom: 0; display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="checkLegalAcceptance" onchange="validateStep3()" style="margin-top: 3px; accent-color: var(--primary); width: 18px; height: 18px; cursor: pointer;">
                        <span class="compliance-label">I certify that this digital signature is legally binding and has the full legal effect and enforceability of a physical handwritten signature.</span>
                    </label>
                </div>

                <div class="step-action-bar">
                    <button type="button" class="btn-back-step" onclick="goToStep(2)">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-continue" id="btnStep3Continue" disabled onclick="goToStep(4)">
                        Confirm &amp; Continue <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 4: TAX & INFO -->
            <div class="step-panel" id="stepPanel4">
                <div class="step-header">
                    <h2 class="step-title">Tax &amp; Personal Info</h2>
                    <p class="step-desc">Securely submit required documentation. All data is encrypted and handled in compliance with applicable privacy regulations.</p>
                </div>

                <form id="onboardingForm" onsubmit="submitOnboarding(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="role" id="formRoleInput" value="photographer_videographer">

                    <!-- PERSONAL INFORMATION -->
                    <span class="section-subheading-caps">PERSONAL INFORMATION</span>

                    <!-- Row 1: Full Legal Name & Email Address -->
                    <div class="step2-input-grid" style="margin-bottom: 18px;">
                        <div>
                            <label class="form-label">Full Legal Name <span class="req">*</span></label>
                            <input type="text" name="full_name" id="fieldFullName" class="form-control" placeholder="As on your government ID" required oninput="syncSignatureName(this.value)">
                        </div>
                        <div>
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" name="email" id="fieldEmail" class="form-control" placeholder="you@email.com" required oninput="syncFromFormEmail(this.value)">
                        </div>
                    </div>

                    <!-- Row 2: Phone Number & Street Address -->
                    <div class="step2-input-grid" style="margin-bottom: 18px;">
                        <div>
                            <label class="form-label">Phone Number <span class="req">*</span></label>
                            <input type="tel" name="phone" id="fieldPhone" class="form-control" placeholder="+1 (555) 000-0000" required>
                        </div>
                        <div>
                            <label class="form-label">Street Address <span class="req">*</span></label>
                            <input type="text" name="address" id="fieldAddress" class="form-control" placeholder="123 Main St" required>
                        </div>
                    </div>

                    <!-- Row 3: City, State, ZIP -->
                    <div class="address-grid-3">
                        <div>
                            <label class="form-label">City <span class="req">*</span></label>
                            <input type="text" name="city" id="fieldCity" class="form-control" placeholder="Miami" required>
                        </div>
                        <div>
                            <label class="form-label">State <span class="req">*</span></label>
                            <input type="text" name="state" id="fieldState" class="form-control" placeholder="FL" required>
                        </div>
                        <div>
                            <label class="form-label">ZIP <span class="req">*</span></label>
                            <input type="text" name="zip" id="fieldZip" class="form-control" placeholder="33101" required>
                        </div>
                    </div>

                    <!-- TAX INFORMATION -->
                    <span class="section-subheading-caps">TAX INFORMATION</span>

                    <div class="step2-input-grid" style="margin-bottom: 24px;">
                        <div>
                            <label class="form-label">Tax Form Type</label>
                            <select name="tax_form_type" id="fieldTaxFormType" class="form-control">
                                <option value="W-9">W-9</option>
                                <option value="W-8BEN (non-US contractor)">W-8BEN (non-US contractor)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">SSN / EIN (last 4 digits) <span class="req">*</span></label>
                            <input type="text" name="tax_id" id="fieldTaxId" class="form-control" placeholder="XXXX" maxlength="4" required>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 5px;">
                                Full forms are submitted via DocuSign. Only last 4 digits collected here.
                            </div>
                        </div>
                    </div>

                    <!-- Secure Direct Deposit via Stripe Callout Box -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px 20px; display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px;">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #dcfce7; display: flex; align-items: center; justify-content: center; color: #16a34a; font-size: 1.1rem; flex-shrink: 0;">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #15803d; font-size: 0.95rem; margin-bottom: 4px;">Secure Direct Deposit via Stripe</div>
                            <div style="color: #166534; font-size: 0.85rem; line-height: 1.55;">
                                No bank details are collected here. After your onboarding is approved, Falhen Media will email you a secure link to connect your bank account through <strong>Stripe</strong> — your routing and account numbers are tokenized and never stored on our systems.
                            </div>
                        </div>
                    </div>

                    <!-- EMERGENCY CONTACT -->
                    <span class="section-subheading-caps">EMERGENCY CONTACT</span>

                    <div class="emergency-grid-3">
                        <div>
                            <label class="form-label">Full Name <span class="req">*</span></label>
                            <input type="text" name="emergency_name" id="fieldEmergencyName" class="form-control" placeholder="Contact name" required>
                        </div>
                        <div>
                            <label class="form-label">Phone Number <span class="req">*</span></label>
                            <input type="tel" name="emergency_phone" id="fieldEmergencyPhone" class="form-control" placeholder="+1 (555) 000-0000" required>
                        </div>
                        <div>
                            <label class="form-label">Relationship <span class="req">*</span></label>
                            <input type="text" name="emergency_relationship" id="fieldEmergencyRel" class="form-control" placeholder="Spouse, Parent..." required>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div style="margin-bottom: 24px;">
                        <label class="form-label">Additional Notes <span style="font-size: 0.82rem; font-weight: 500; color: #64748b;">(optional)</span></label>
                        <textarea name="additional_notes" id="fieldNotes" class="form-control" rows="3" placeholder="Anything else Falhen Media should know..." maxlength="500" oninput="updateNotesCount(this.value)"></textarea>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 5px;" id="notesCounter">0/500 characters</div>
                    </div>

                    <div class="step-action-bar">
                        <button type="button" class="btn-back-step" onclick="goToStep(3)">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-continue" id="btnSubmitOnboarding" style="background: #dc2626; color: #ffffff;">
                            Submit &amp; Finish <i class="fa-solid fa-arrow-right"></i>
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
                            <span style="color: var(--success); font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Verified &amp; Active</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 26px;">
                        <a id="btnDownloadAgreement" href="#" target="_blank" class="btn-continue" style="background: var(--dark); text-decoration: none; margin: 0;">
                            <i class="fa-solid fa-file-pdf"></i> Download Signed Agreement
                        </a>
                        <a href="/" class="btn-back-step" style="text-decoration: none;">
                            <i class="fa-solid fa-house"></i> Return to Home
                        </a>
                        <a href="/admin/login.php?view=vendor" class="btn-back-step" style="text-decoration: none;">
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

        const stepDefaultIcons = [
            '',
            '<i class="fa-solid fa-user"></i>',
            '<i class="fa-solid fa-shield-halved"></i>',
            '<i class="fa-regular fa-file-lines"></i>',
            '<i class="fa-regular fa-id-card"></i>',
            '<i class="fa-regular fa-circle-check"></i>'
        ];

        // Stepper Navigation
        function goToStep(stepNum) {
            if (stepNum < 1 || stepNum > 5) return;

            // Step validations
            if (stepNum > 2 && !isStep2Valid()) {
                alert('Please enter your full legal name, email address, and agree to the Workplace Policy before proceeding.');
                return;
            }

            if (stepNum > 3 && !isStep3Valid()) {
                alert('Please confirm your legal name, email address, accept both agreements (NDA & MSA), and provide your signature to proceed.');
                return;
            }

            // Prefill Step 3 if entering from Step 2
            if (stepNum === 3) {
                const s2Name = document.getElementById('step2LegalName');
                const s3Name = document.getElementById('step3LegalName');
                if (s2Name && s2Name.value && s3Name && !s3Name.value) {
                    s3Name.value = s2Name.value;
                    updateSignaturePreview(s2Name.value);
                }
                const s2Email = document.getElementById('step2Email');
                const s3Email = document.getElementById('step3Email');
                if (s2Email && s2Email.value && s3Email && !s3Email.value) {
                    s3Email.value = s2Email.value;
                }
                validateStep3();
            }

            // Prefill Step 4 if entering from Step 3
            if (stepNum === 4) {
                const sName = (document.getElementById('step3LegalName') && document.getElementById('step3LegalName').value) ||
                              (document.getElementById('step2LegalName') && document.getElementById('step2LegalName').value);
                const fieldFull = document.getElementById('fieldFullName');
                if (sName && fieldFull && !fieldFull.value) {
                    fieldFull.value = sName;
                }
                const sEmail = (document.getElementById('step3Email') && document.getElementById('step3Email').value) ||
                               (document.getElementById('step2Email') && document.getElementById('step2Email').value);
                const fieldEmail = document.getElementById('fieldEmail');
                if (sEmail && fieldEmail && !fieldEmail.value) {
                    fieldEmail.value = sEmail;
                }
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
                    const iconBox = indicator.querySelector('.step-icon-circle');
                    if (currentStep === 5 && i === 5) {
                        indicator.classList.add('completed');
                        if (iconBox) iconBox.innerHTML = '<i class="fa-solid fa-check"></i>';
                    } else if (i === currentStep) {
                        indicator.classList.add('active');
                        if (iconBox && stepDefaultIcons[i]) iconBox.innerHTML = stepDefaultIcons[i];
                    } else if (i < currentStep) {
                        indicator.classList.add('completed');
                        if (iconBox) iconBox.innerHTML = '<i class="fa-solid fa-check"></i>';
                    } else {
                        if (iconBox && stepDefaultIcons[i]) iconBox.innerHTML = stepDefaultIcons[i];
                    }
                }

                if (line) {
                    line.classList.toggle('completed', i < currentStep);
                }
            }

            // Update page top header for Step 5 vs earlier steps
            const introBadge = document.getElementById('introBadge');
            const introTitle = document.getElementById('introTitle');
            const introSubtitle = document.getElementById('introSubtitle');

            if (currentStep === 5) {
                if (introBadge) introBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Onboarding Complete';
                if (introTitle) introTitle.innerText = 'Welcome to Falhen Media!';
                if (introSubtitle) introSubtitle.innerText = 'Your vendor profile has been recorded and your signed agreement is ready for download.';
                window.scrollTo({ top: 60, behavior: 'smooth' });
            } else {
                if (introBadge) introBadge.innerHTML = '<i class="fa-solid fa-building-user"></i> Vendor Onboarding';
                if (introTitle) introTitle.innerText = 'Join the Falhen Media Team';
                if (introSubtitle) introSubtitle.innerText = 'Complete this secure onboarding process to formalize your engagement. Takes about 5 minutes.';
                window.scrollTo({ top: 120, behavior: 'smooth' });
            }
        }

        // Two-way synchronization helpers between Step 2, Step 3, and Step 4
        function syncFromStep2Name(val) {
            const trimmed = val.trim();
            const step3Name = document.getElementById('step3LegalName');
            if (step3Name && step3Name.value !== val) {
                step3Name.value = val;
            }
            const sigInput = document.getElementById('signatureNameInput');
            if (sigInput) {
                sigInput.value = trimmed;
            }
            const fieldFull = document.getElementById('fieldFullName');
            if (fieldFull && fieldFull.value !== val) {
                fieldFull.value = val;
            }
            updateSignaturePreview(trimmed);
            validateStep2();
            validateStep3();
        }

        function syncFromStep2Email(val) {
            const trimmed = val.trim();
            const step3Email = document.getElementById('step3Email');
            if (step3Email && step3Email.value !== val) {
                step3Email.value = val;
            }
            const fieldEmail = document.getElementById('fieldEmail');
            if (fieldEmail && fieldEmail.value !== val) {
                fieldEmail.value = val;
            }
            validateStep2();
            validateStep3();
        }

        function syncFromStep3Name(val) {
            const trimmed = val.trim();
            const step2Name = document.getElementById('step2LegalName');
            if (step2Name && step2Name.value !== val) {
                step2Name.value = val;
            }
            const sigInput = document.getElementById('signatureNameInput');
            if (sigInput) {
                sigInput.value = trimmed;
            }
            const fieldFull = document.getElementById('fieldFullName');
            if (fieldFull && fieldFull.value !== val) {
                fieldFull.value = val;
            }
            updateSignaturePreview(trimmed);
            validateStep2();
            validateStep3();
        }

        function syncFromStep3Email(val) {
            const trimmed = val.trim();
            const step2Email = document.getElementById('step2Email');
            if (step2Email && step2Email.value !== val) {
                step2Email.value = val;
            }
            const fieldEmail = document.getElementById('fieldEmail');
            if (fieldEmail && fieldEmail.value !== val) {
                fieldEmail.value = val;
            }
            validateStep2();
            validateStep3();
        }

        function syncFromFormEmail(val) {
            const trimmed = val.trim();
            const s2Email = document.getElementById('step2Email');
            if (s2Email && s2Email.value !== val) {
                s2Email.value = val;
            }
            const s3Email = document.getElementById('step3Email');
            if (s3Email && s3Email.value !== val) {
                s3Email.value = val;
            }
            validateStep2();
            validateStep3();
        }

        function updateNotesCount(val) {
            const counter = document.getElementById('notesCounter');
            if (counter) {
                counter.innerText = val.length + '/500 characters';
            }
        }

        // Validate Step 2
        function validateStep2() {
            const nameEl = document.getElementById('step2LegalName');
            const emailEl = document.getElementById('step2Email');
            const chkEl = document.getElementById('checkPolicyAgreement');
            const btn = document.getElementById('btnStep2Continue');

            const name = nameEl ? nameEl.value.trim() : '';
            const email = emailEl ? emailEl.value.trim() : '';
            const checked = chkEl ? chkEl.checked : false;

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isValid = (name.length >= 2) && emailRegex.test(email) && checked;

            if (btn) {
                btn.disabled = !isValid;
            }
            return isValid;
        }

        function isStep2Valid() {
            return validateStep2();
        }

        // Dual Signature Mode State & Canvas Logic
        let currentSigMode = 'type';
        let hasDrawnSignature = false;

        function switchSignatureMode(mode) {
            currentSigMode = mode;
            const btnType = document.getElementById('btnSigModeType');
            const btnDraw = document.getElementById('btnSigModeDraw');
            const typeContainer = document.getElementById('sigTypeModeContainer');
            const drawContainer = document.getElementById('sigDrawModeContainer');

            if (mode === 'type') {
                btnType.style.background = '#ffffff';
                btnType.style.color = 'var(--primary)';
                btnType.style.boxShadow = '0 2px 6px rgba(15, 23, 42, 0.06)';
                btnDraw.style.background = 'transparent';
                btnDraw.style.color = 'var(--slate-500)';
                btnDraw.style.boxShadow = 'none';

                if (typeContainer) typeContainer.style.display = 'block';
                if (drawContainer) drawContainer.style.display = 'none';
            } else {
                btnDraw.style.background = '#ffffff';
                btnDraw.style.color = 'var(--primary)';
                btnDraw.style.boxShadow = '0 2px 6px rgba(15, 23, 42, 0.06)';
                btnType.style.background = 'transparent';
                btnType.style.color = 'var(--slate-500)';
                btnType.style.boxShadow = 'none';

                if (drawContainer) drawContainer.style.display = 'block';
                if (typeContainer) typeContainer.style.display = 'none';

                resizeSignatureCanvas();
            }
            validateStep3();
        }

        // Initialize Screen Signature Canvas
        function initSignatureCanvas() {
            const canvas = document.getElementById('signatureCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            function getCoords(e) {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                return {
                    x: (clientX - rect.left) * scaleX,
                    y: (clientY - rect.top) * scaleY
                };
            }

            function startDrawing(e) {
                isDrawing = true;
                const pos = getCoords(e);
                lastX = pos.x;
                lastY = pos.y;
            }

            function draw(e) {
                if (!isDrawing) return;
                if (e.touches && e.cancelable) e.preventDefault();
                const pos = getCoords(e);

                ctx.strokeStyle = '#0F172A';
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();

                lastX = pos.x;
                lastY = pos.y;
                hasDrawnSignature = true;
                validateStep3();
            }

            function stopDrawing() {
                if (isDrawing) {
                    isDrawing = false;
                    validateStep3();
                }
            }

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stopDrawing);

            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            window.addEventListener('touchend', stopDrawing);
        }

        function resizeSignatureCanvas() {
            const canvas = document.getElementById('signatureCanvas');
            if (!canvas) return;
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && canvas.width !== rect.width * 2) {
                const temp = canvas.toDataURL();
                canvas.width = rect.width * 2;
                canvas.height = 170 * 2;
                const ctx = canvas.getContext('2d');
                if (hasDrawnSignature) {
                    const img = new Image();
                    img.onload = () => ctx.drawImage(img, 0, 0);
                    img.src = temp;
                }
            }
        }

        function clearSignatureCanvas() {
            const canvas = document.getElementById('signatureCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawnSignature = false;
            validateStep3();
        }

        // Validate Step 3
        function validateStep3() {
            const nameEl = document.getElementById('step3LegalName') || document.getElementById('step2LegalName');
            const emailEl = document.getElementById('step3Email') || document.getElementById('step2Email');
            const name = nameEl ? nameEl.value.trim() : '';
            const email = emailEl ? emailEl.value.trim() : '';

            const ndaChecked = document.getElementById('checkNdaAgreement') ? document.getElementById('checkNdaAgreement').checked : false;
            const msaChecked = document.getElementById('checkMsaAgreement') ? document.getElementById('checkMsaAgreement').checked : false;
            const certChecked = document.getElementById('checkLegalAcceptance') ? document.getElementById('checkLegalAcceptance').checked : false;
            const btn = document.getElementById('btnStep3Continue');

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let sigValid = false;
            if (currentSigMode === 'type') {
                sigValid = (name.length >= 2);
            } else {
                sigValid = (hasDrawnSignature && name.length >= 2);
            }

            const isValid = (name.length >= 2 && emailRegex.test(email) && ndaChecked && msaChecked && certChecked && sigValid);
            if (btn) {
                btn.disabled = !isValid;
            }
            return isValid;
        }

        function isStep3Valid() {
            return validateStep3();
        }

        // Live Signature Text Preview
        function updateSignaturePreview(val) {
            const rendered = document.getElementById('sigRenderedText');
            if (rendered) {
                rendered.innerText = val.trim() || 'John Doe';
            }

            // Sync with Step 2 Legal Name
            const step2Name = document.getElementById('step2LegalName');
            if (step2Name && step2Name.value !== val) {
                step2Name.value = val;
            }

            // Sync with Step 3 Legal Name
            const step3Name = document.getElementById('step3LegalName');
            if (step3Name && step3Name.value !== val) {
                step3Name.value = val;
            }

            // Sync with form full name
            const fieldFull = document.getElementById('fieldFullName');
            if (fieldFull && fieldFull.value !== val) {
                fieldFull.value = val;
            }

            // Sync with hidden signatureNameInput
            const sigHidden = document.getElementById('signatureNameInput');
            if (sigHidden && sigHidden.value !== val) {
                sigHidden.value = val;
            }

            validateStep3();
            validateStep2();
        }

        function syncSignatureName(val) {
            const step2Name = document.getElementById('step2LegalName');
            if (step2Name) step2Name.value = val;
            const step3Name = document.getElementById('step3LegalName');
            if (step3Name) step3Name.value = val;
            const sigInput = document.getElementById('signatureNameInput');
            if (sigInput) sigInput.value = val;
            updateSignaturePreview(val);
            validateStep2();
            validateStep3();
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

            const legalName = (document.getElementById('signatureNameInput') && document.getElementById('signatureNameInput').value.trim()) ||
                              (document.getElementById('step3LegalName') && document.getElementById('step3LegalName').value.trim()) ||
                              (document.getElementById('step2LegalName') && document.getElementById('step2LegalName').value.trim()) ||
                              (document.getElementById('fieldFullName') && document.getElementById('fieldFullName').value.trim()) || 'Contractor';
            formData.append('signature_name', legalName);
            formData.append('signature_type', currentSigMode);

            if (currentSigMode === 'draw') {
                const canvas = document.getElementById('signatureCanvas');
                formData.append('signature_data', canvas ? canvas.toDataURL('image/png') : '');
            } else {
                formData.append('signature_data', legalName);
            }

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

                if (res && res.success) {
                    const info = res.data || res;
                    const refEl = document.getElementById('doneRefCode');
                    if (refEl) refEl.innerText = info.reference_code || '';
                    
                    const roleEl = document.getElementById('doneRoleLabel');
                    if (roleEl) roleEl.innerText = info.role_label || 'Vendor';

                    const nameEl = document.getElementById('doneFullName');
                    if (nameEl) nameEl.innerText = info.full_name || '';

                    const dlBtn = document.getElementById('btnDownloadAgreement');
                    if (dlBtn) dlBtn.href = info.download_url || '#';

                    goToStep(5);
                } else {
                    alert('Submission error: ' + ((res && res.message) || 'Please check your inputs and try again.'));
                }
            } catch (err) {
                overlay.classList.remove('active');
                console.error(err);
                alert('An error occurred while submitting onboarding: ' + (err.message || 'Please try again.'));
            }
        }

        // Initialize agreement text and canvas on load
        document.addEventListener('DOMContentLoaded', () => {
            renderLegalAgreement();
            updateSignaturePreview('');
            initSignatureCanvas();
            window.addEventListener('resize', resizeSignatureCanvas);
        });
    </script>
</body>
</html>
