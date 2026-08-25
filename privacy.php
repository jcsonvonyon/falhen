<?php
// privacy.php - Privacy Policy Page matching falhen.com/privacy screenshots
$pageTitle = "Privacy Policy — Falhen Media";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Privacy Policy explaining how Falhen Media collects, uses, and protects personal data under GDPR, UK GDPR, CCPA/CPRA, and US privacy laws.">

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
            line-height: 1.7;
        }

        /* Top Nav Bar */
        .privacy-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .privacy-nav-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .privacy-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .privacy-back-link:hover {
            color: #ffffff;
        }

        .privacy-top-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .privacy-top-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .privacy-top-links a:hover {
            color: #ffffff;
        }

        /* Hero Stage */
        .privacy-hero-section {
            padding: 70px 0 50px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at 50% 10%, rgba(220, 38, 38, 0.15) 0%, transparent 60%);
        }

        .privacy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .privacy-badge-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .badge-red {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ef4444;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .badge-green {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .badge-grey {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .privacy-hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 16px 0;
            line-height: 1.1;
        }

        .privacy-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            margin: 0 0 28px 0;
        }

        .privacy-meta-row {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 0.85rem;
            color: #71717a;
            font-weight: 600;
            flex-wrap: wrap;
        }

        .privacy-meta-row span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .privacy-meta-row i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        /* Green Notice Banner */
        .compliance-notice-box {
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin: 40px 0 30px 0;
        }

        .compliance-icon {
            color: #10b981;
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .compliance-content h4 {
            color: #10b981;
            font-size: 0.98rem;
            font-weight: 800;
            margin: 0 0 6px 0;
        }

        .compliance-content p {
            color: #a1a1aa;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Table of Contents Box */
        .contents-box {
            background: rgba(14, 14, 18, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 32px;
            margin-bottom: 60px;
        }

        .contents-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #71717a;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .contents-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 30px;
        }

        .contents-link {
            color: #d4d4d8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .contents-link:hover {
            color: #ef4444;
        }

        .contents-link span.arrow {
            color: #71717a;
            font-size: 0.8rem;
        }

        /* Legal Content Sections */
        .legal-section-block {
            margin-bottom: 50px;
            scroll-margin-top: 100px;
        }

        .legal-section-heading {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 20px 0;
            letter-spacing: -0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 12px;
        }

        .legal-sub-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ef4444;
            margin: 20px 0 10px 0;
        }

        .legal-paragraph {
            color: #a1a1aa;
            font-size: 0.96rem;
            line-height: 1.75;
            margin-bottom: 16px;
        }

        /* DSAR Form Section Box */
        .dsar-section-box {
            background: linear-gradient(135deg, rgba(20, 20, 26, 0.95) 0%, rgba(12, 12, 16, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 40px;
            margin: 60px 0 40px 0;
        }

        .dsar-badge {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #71717a;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
            text-align: center;
        }

        .dsar-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .dsar-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .dsar-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .dsar-sub {
            color: #a1a1aa;
            font-size: 0.88rem;
            margin: 0;
        }

        .dsar-field-group {
            margin-bottom: 20px;
        }

        .dsar-field-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .dsar-field-label span.req {
            color: #ef4444;
        }

        /* Request Type Cards Selector */
        .dsar-types-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 24px;
        }

        .dsar-type-card {
            background: rgba(3, 3, 5, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .dsar-type-card:hover,
        .dsar-type-card.selected {
            border-color: rgba(220, 38, 38, 0.5);
            background: rgba(220, 38, 38, 0.06);
        }

        .dsar-type-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #a1a1aa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            flex-shrink: 0;
        }

        .dsar-type-card.selected .dsar-type-icon {
            background: #dc2626;
            color: #ffffff;
        }

        .dsar-type-name {
            font-size: 0.92rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .dsar-type-desc {
            font-size: 0.8rem;
            color: #71717a;
        }

        .dsar-input-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .dsar-input-control,
        .dsar-textarea-control {
            width: 100%;
            background: rgba(3, 3, 5, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 13px 16px;
            color: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.25s ease;
        }

        .dsar-input-control:focus,
        .dsar-textarea-control:focus {
            border-color: rgba(220, 38, 38, 0.5);
        }

        .dsar-field-hint {
            font-size: 0.78rem;
            color: #71717a;
            margin-top: 6px;
            line-height: 1.5;
        }

        .dsar-sla-notice {
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #a1a1aa;
            font-size: 0.84rem;
            line-height: 1.5;
            margin: 24px 0;
        }

        .dsar-sla-notice i {
            color: #10b981;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .btn-submit-dsar {
            width: 100%;
            background: #dc2626;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-submit-dsar:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Footer Bar */
        .privacy-footer-bar {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
            margin-top: 60px;
        }

        .privacy-footer-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .privacy-footer-left a {
            color: #a1a1aa;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.25s ease;
        }

        .privacy-footer-left a:hover {
            color: #ffffff;
        }

        .privacy-footer-left i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .contents-grid {
                grid-template-columns: 1fr;
            }
            .dsar-input-row {
                grid-template-columns: 1fr;
            }
            .privacy-hero-title {
                font-size: 2.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="privacy-nav-bar">
        <div class="privacy-nav-container">
            <a href="/" class="privacy-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <div class="privacy-top-links">
                <a href="/terms.php">Terms</a>
                <a href="/cookies.php">Cookies</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="privacy-hero-section">
        <div class="privacy-container">
            <div class="privacy-badge-group">
                <span class="badge-red">Legal</span>
                <span class="badge-green">GDPR Compliant</span>
                <span class="badge-grey">CCPA / CPRA</span>
            </div>

            <h1 class="privacy-hero-title">Privacy Policy</h1>
            <p class="privacy-hero-subtitle">This Privacy Policy explains how Falhen Media collects, uses, stores, and protects your personal information in compliance with the GDPR, UK GDPR, CCPA/CPRA, and applicable US privacy laws.</p>

            <div class="privacy-meta-row">
                <span><i class="fa-regular fa-calendar"></i> Effective: 1 January 2024</span>
                <span><i class="fa-solid fa-rotate-left"></i> Last updated: 28 April 2026</span>
                <span><i class="fa-solid fa-location-dot"></i> Jurisdiction: United States</span>
            </div>
        </div>
    </section>

    <main class="privacy-container">

        <!-- Green Notice Banner -->
        <div class="compliance-notice-box">
            <div class="compliance-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="compliance-content">
                <h4>GDPR & US Privacy Compliance</h4>
                <p>This policy is written in compliance with the General Data Protection Regulation (EU) 2016/679, the UK GDPR, the California Consumer Privacy Act (CCPA/CPRA), and applicable US federal and state privacy laws. Your rights are clearly set out in Section 8.</p>
            </div>
        </div>

        <!-- Table of Contents -->
        <div class="contents-box">
            <div class="contents-title">CONTENTS</div>
            <div class="contents-grid">
                <div>
                    <a href="#sec-1" class="contents-link"><span class="arrow">&gt;</span> 1. Introduction & Identity of the Data Controller</a>
                    <a href="#sec-3" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 3. Lawful Basis for Processing (GDPR Article 6)</a>
                    <a href="#sec-5" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 5. Data Sharing & Third-Party Processors</a>
                    <a href="#sec-7" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 7. Data Retention</a>
                    <a href="#sec-9" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 9. Security</a>
                    <a href="#sec-11" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 11. Changes to This Policy</a>
                </div>
                <div>
                    <a href="#sec-2" class="contents-link"><span class="arrow">&gt;</span> 2. Personal Data We Collect</a>
                    <a href="#sec-4" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 4. Purposes of Processing</a>
                    <a href="#sec-6" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 6. International Data Transfers</a>
                    <a href="#sec-8" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 8. Your Rights Under GDPR & US Privacy Laws</a>
                    <a href="#sec-10" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 10. Children's Privacy</a>
                    <a href="#sec-12" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 12. Contact & Data Protection Officer</a>
                </div>
            </div>
        </div>

        <!-- Section 1 -->
        <section class="legal-section-block" id="sec-1">
            <h2 class="legal-section-heading">1. Introduction & Identity of the Data Controller</h2>
            <div class="legal-sub-heading">1.1 Who We Are</div>
            <p class="legal-paragraph">Falhen Media ("we," "us," or "our") is the data controller responsible for the personal data collected through this website and in connection with our services. We are committed to protecting your privacy and processing your personal data in accordance with the General Data Protection Regulation (EU) 2016/679 ("GDPR"), the UK GDPR, the California Consumer Privacy Act (CCPA/CPRA), and all other applicable US federal and state privacy laws.</p>

            <div class="legal-sub-heading">1.2 Contact Details</div>
            <p class="legal-paragraph">Data Controller: Falhen Media. Email: <span style="color: #ef4444;">privacy@falhen.com</span>. For all data protection enquiries, please contact our Privacy Team at privacy@falhen.com. We aim to respond to all requests within 30 days.</p>

            <div class="legal-sub-heading">1.3 Scope</div>
            <p class="legal-paragraph">This Privacy Policy applies to all personal data we collect through our website (falhen.com), our client portal, and in the course of providing our professional media production services. It does not apply to third-party websites linked from our site.</p>
        </section>

        <!-- Section 2 -->
        <section class="legal-section-block" id="sec-2">
            <h2 class="legal-section-heading">2. Personal Data We Collect</h2>
            <div class="legal-sub-heading">2.1 Data You Provide Directly</div>
            <p class="legal-paragraph">We collect personal data you voluntarily provide when you: (a) submit a contact or enquiry form; (b) request a quote or book a discovery call; (c) subscribe to our newsletter or marketing communications; (d) engage us for services and sign a project agreement; (e) access a client gallery or portal. This data includes your full name, email address, phone number, company name, job title, billing address, and any project briefs or creative materials you share.</p>

            <div class="legal-sub-heading">2.2 Data Collected Automatically</div>
            <p class="legal-paragraph">When you visit our website, we automatically collect technical data including: IP address, browser type and version, operating system, device identifiers, referring URLs, pages visited, time on page, and click-stream data. This is collected via cookies and similar tracking technologies. Please see our Cookie Policy for full details.</p>

            <div class="legal-sub-heading">2.3 Communications Data</div>
            <p class="legal-paragraph">We retain records of all correspondence when you contact us by email, phone, or through our website forms, including message content and metadata such as timestamps and delivery status.</p>

            <div class="legal-sub-heading">2.4 Special Categories of Data</div>
            <p class="legal-paragraph">We do not intentionally collect special categories of personal data (such as health data, racial or ethnic origin, political opinions, religious beliefs, or biometric data). Please do not submit such data through our website or forms.</p>
        </section>

        <!-- Section 3 -->
        <section class="legal-section-block" id="sec-3">
            <h2 class="legal-section-heading">3. Lawful Basis for Processing (GDPR Article 6)</h2>
            <div class="legal-sub-heading">3.1 Contractual Necessity (Art. 6(1)(b))</div>
            <p class="legal-paragraph">We process your data where it is necessary to perform a contract with you or to take pre-contractual steps at your request — for example, when you request a quote, sign a project agreement, or access a client gallery.</p>

            <div class="legal-sub-heading">3.2 Legitimate Interests (Art. 6(1)(f))</div>
            <p class="legal-paragraph">We process data where it is in our legitimate business interests, provided those interests are not overridden by your fundamental rights. This includes: improving our website and services, preventing fraud, maintaining IT security, and conducting direct marketing to existing clients. We have conducted Legitimate Interests Assessments (LIAs) for each such processing activity.</p>

            <div class="legal-sub-heading">3.3 Consent (Art. 6(1)(a))</div>
            <p class="legal-paragraph">Where we rely on your consent — such as for newsletter subscriptions, non-essential cookies, or marketing to new prospects — you have the right to withdraw that consent at any time without affecting the lawfulness of processing before withdrawal. Withdrawal can be made via the unsubscribe link in any email or by contacting privacy@falhen.com.</p>

            <div class="legal-sub-heading">3.4 Legal Obligation (Art. 6(1)(c))</div>
            <p class="legal-paragraph">We process data where necessary to comply with applicable laws, including tax and accounting obligations, anti-money laundering requirements, and responses to lawful requests from regulatory authorities or courts.</p>
        </section>

        <!-- Section 4 -->
        <section class="legal-section-block" id="sec-4">
            <h2 class="legal-section-heading">4. Purposes of Processing</h2>
            <div class="legal-sub-heading">4.1 Service Delivery</div>
            <p class="legal-paragraph">To respond to enquiries, provide quotes, deliver contracted video production and media services, manage client relationships, and communicate project updates and deliverables.</p>

            <div class="legal-sub-heading">4.2 Business Operations</div>
            <p class="legal-paragraph">To process payments, maintain accounts, comply with legal obligations, prevent fraud, and enforce our Terms of Service.</p>

            <div class="legal-sub-heading">4.3 Marketing & Communications</div>
            <p class="legal-paragraph">To send newsletter updates, portfolio showcases, and promotional offers (with your explicit consent where required by law).</p>

            <div class="legal-sub-heading">4.4 Website Analytics & Improvement</div>
            <p class="legal-paragraph">To analyse usage patterns, identify technical issues, and improve the user experience. Analytics data is pseudonymised where possible.</p>

            <div class="legal-sub-heading">4.5 Security & Fraud Prevention</div>
            <p class="legal-paragraph">To monitor for and prevent unauthorised access, fraud, and other malicious activity on our systems.</p>
        </section>

        <!-- Section 5 -->
        <section class="legal-section-block" id="sec-5">
            <h2 class="legal-section-heading">5. Data Sharing & Third-Party Processors</h2>
            <div class="legal-sub-heading">5.1 Service Providers (Data Processors)</div>
            <p class="legal-paragraph">We share data with trusted third-party processors who assist us in operating our business. All processors are bound by Data Processing Agreements (DPAs) under GDPR Article 28 and are contractually required to process data only on our documented instructions. Processors include: cloud hosting and infrastructure providers (e.g., Supabase/AWS), email delivery services (e.g., Resend), analytics platforms (e.g., Google Analytics), and payment processors.</p>

            <div class="legal-sub-heading">5.2 Business Transfers</div>
            <p class="legal-paragraph">In the event of a merger, acquisition, or sale of all or part of our business, your personal data may be transferred to the acquiring entity. We will provide at least 30 days' notice before your data is transferred and becomes subject to a different privacy policy.</p>

            <div class="legal-sub-heading">5.3 Legal Disclosure</div>
            <p class="legal-paragraph">We may disclose your information where required by law, court order, or regulatory authority, or where necessary to protect the rights, property, or safety of Falhen Media, our clients, or others.</p>

            <div class="legal-sub-heading">5.4 No Sale of Personal Data</div>
            <p class="legal-paragraph">We do not sell, rent, or trade your personal information to third parties for their own marketing or commercial purposes. Under the CCPA/CPRA, we confirm we do not "sell" or "share" personal information as defined by California law.</p>
        </section>

        <!-- Section 6 -->
        <section class="legal-section-block" id="sec-6">
            <h2 class="legal-section-heading">6. International Data Transfers</h2>
            <div class="legal-sub-heading">6.1 Transfers Outside the EEA/UK</div>
            <p class="legal-paragraph">Where we transfer personal data to countries outside the European Economic Area (EEA) or the United Kingdom that do not benefit from an adequacy decision, we ensure appropriate safeguards are in place in accordance with GDPR Chapter V. These safeguards include: Standard Contractual Clauses (SCCs) approved by the European Commission, the UK International Data Transfer Agreement (IDTA), or binding corporate rules where applicable.</p>

            <div class="legal-sub-heading">6.2 US-Based Processing</div>
            <p class="legal-paragraph">Some of our service providers are based in the United States. Where data is transferred to the US, we rely on SCCs and, where applicable, the EU-US Data Privacy Framework. You may request a copy of the relevant transfer mechanism by contacting <span style="color: #ef4444;">privacy@falhen.com</span>.</p>
        </section>

        <!-- Section 7 -->
        <section class="legal-section-block" id="sec-7">
            <h2 class="legal-section-heading">7. Data Retention</h2>
            <div class="legal-sub-heading">7.1 Retention Periods</div>
            <p class="legal-paragraph">We retain personal data only for as long as necessary to fulfil the purposes for which it was collected, including satisfying legal, accounting, or reporting requirements. Our standard retention periods are: Client project data — 7 years from project completion (financial record-keeping); Marketing data — until you withdraw consent or opt out; Website analytics — 26 months (Google Analytics default); Enquiry/contact data — 2 years from last contact.</p>

            <div class="legal-sub-heading">7.2 Secure Deletion</div>
            <p class="legal-paragraph">When data is no longer required, we securely delete or irreversibly anonymise it in accordance with our data retention schedule. You may request deletion of your personal data at any time, subject to our legal obligations to retain certain records.</p>
        </section>

        <!-- Section 8 -->
        <section class="legal-section-block" id="sec-8">
            <h2 class="legal-section-heading">8. Your Rights Under GDPR & US Privacy Laws</h2>
            <div class="legal-sub-heading">8.1 Right of Access (GDPR Art. 15 / CCPA)</div>
            <p class="legal-paragraph">You have the right to request a copy of the personal data we hold about you, information about how we process it, and to receive it in a structured, commonly used, machine-readable format (data portability, Art. 20).</p>

            <div class="legal-sub-heading">8.2 Right to Rectification (Art. 16)</div>
            <p class="legal-paragraph">You have the right to request correction of inaccurate or incomplete personal data we hold about you without undue delay.</p>

            <div class="legal-sub-heading">8.3 Right to Erasure / "Right to be Forgotten" (Art. 17)</div>
            <p class="legal-paragraph">You have the right to request deletion of your personal data where: it is no longer necessary for the purpose it was collected; you withdraw consent and there is no other lawful basis; you object and there are no overriding legitimate grounds; the data has been unlawfully processed; or erasure is required by law.</p>

            <div class="legal-sub-heading">8.4 Right to Restriction of Processing (Art. 18)</div>
            <p class="legal-paragraph">You have the right to request that we restrict processing of your personal data in certain circumstances, such as while we verify the accuracy of data you have contested.</p>

            <div class="legal-sub-heading">8.5 Right to Object (Art. 21)</div>
            <p class="legal-paragraph">You have the right to object at any time to processing of your personal data based on legitimate interests (Art. 6(1)(f)) or for direct marketing purposes. Where you object to direct marketing, we will cease processing immediately.</p>

            <div class="legal-sub-heading">8.6 Rights Related to Automated Decision-Making (Art. 22)</div>
            <p class="legal-paragraph">We do not make decisions about you based solely on automated processing that produce legal or similarly significant effects. If this changes, we will update this policy and provide appropriate safeguards.</p>

            <div class="legal-sub-heading">8.7 California Privacy Rights (CCPA/CPRA)</div>
            <p class="legal-paragraph">California residents have additional rights including: the right to know what personal information is collected, used, shared, or sold; the right to delete personal information; the right to opt-out of the sale or sharing of personal information; the right to non-discrimination for exercising privacy rights; and the right to correct inaccurate personal information.</p>

            <div class="legal-sub-heading">8.8 How to Exercise Your Rights</div>
            <p class="legal-paragraph">To exercise any of these rights, please submit a written request to <span style="color: #ef4444;">privacy@falhen.com</span>. We will respond within 30 days (extendable by a further 60 days for complex requests, with notice). We may need to verify your identity before processing your request. There is no charge for exercising your rights unless requests are manifestly unfounded or excessive.</p>

            <div class="legal-sub-heading">8.9 Right to Lodge a Complaint</div>
            <p class="legal-paragraph">If you are located in the EEA or UK and believe we have not handled your personal data in accordance with applicable law, you have the right to lodge a complaint with your local supervisory authority. In the UK, this is the Information Commissioner's Office (ICO) at ico.org.uk. In the US, you may contact the Federal Trade Commission (FTC) or your state Attorney General.</p>
        </section>

        <!-- Section 9 -->
        <section class="legal-section-block" id="sec-9">
            <h2 class="legal-section-heading">9. Security</h2>
            <div class="legal-sub-heading">9.1 Technical & Organisational Measures</div>
            <p class="legal-paragraph">We implement appropriate technical and organisational measures (TOMs) to protect your personal data against unauthorised access, alteration, disclosure, or destruction, in accordance with GDPR Article 32. These include: TLS/SSL encryption in transit; encryption at rest for sensitive data; role-based access controls and least-privilege principles; regular security assessments and penetration testing; staff training on data protection; and incident response procedures.</p>

            <div class="legal-sub-heading">9.2 Personal Data Breach Notification</div>
            <p class="legal-paragraph">In the event of a personal data breach that is likely to result in a risk to your rights and freedoms, we will notify the relevant supervisory authority within 72 hours of becoming aware of the breach (GDPR Art. 33). Where the breach is likely to result in a high risk to your rights and freedoms, we will also notify you directly without undue delay (Art. 34).</p>
        </section>

        <!-- Section 10 -->
        <section class="legal-section-block" id="sec-10">
            <h2 class="legal-section-heading">10. Children's Privacy</h2>
            <div class="legal-sub-heading">10.1 Age Restriction</div>
            <p class="legal-paragraph">Our website and services are not directed to individuals under the age of 16 (or 13 in the United States under COPPA). We do not knowingly collect personal data from children. If you believe we have inadvertently collected data from a child, please contact us immediately at <span style="color: #ef4444;">privacy@falhen.com</span> and we will delete it promptly.</p>
        </section>

        <!-- Section 11 -->
        <section class="legal-section-block" id="sec-11">
            <h2 class="legal-section-heading">11. Changes to This Policy</h2>
            <div class="legal-sub-heading">11.1 Updates</div>
            <p class="legal-paragraph">We may update this Privacy Policy from time to time to reflect changes in our practices, technology, legal requirements, or other factors. We will notify you of material changes by posting the updated policy on our website with a revised "Last updated" date and, where required by law, by sending you direct notification. Your continued use of our services after the effective date of material changes constitutes acceptance of the updated policy.</p>
        </section>

        <!-- Section 12 -->
        <section class="legal-section-block" id="sec-12">
            <h2 class="legal-section-heading">12. Contact & Data Protection Officer</h2>
            <div class="legal-sub-heading">12.1 Contact Us</div>
            <p class="legal-paragraph">For all privacy-related enquiries, data subject requests, or concerns about this Privacy Policy, please contact: Privacy Team — <span style="color: #ef4444;">privacy@falhen.com</span> | General Enquiries — <span style="color: #ef4444;">hello@falhen.com</span>. We aim to respond to all requests within 30 days.</p>
        </section>

        <!-- DSAR Form Box ("EXERCISE YOUR RIGHTS") -->
        <div class="dsar-section-box" id="exercise-rights">
            <span class="dsar-badge">EXERCISE YOUR RIGHTS</span>
            <div class="dsar-header-left">
                <div class="dsar-icon-wrap"><i class="fa-solid fa-user-shield"></i></div>
                <div>
                    <h3 class="dsar-title">Submit a Data Subject Request</h3>
                    <p class="dsar-sub">Exercise your GDPR rights — we respond within 30 days</p>
                </div>
            </div>

            <form id="dsarForm" onsubmit="handleDsarSubmit(event)">
                <div class="dsar-field-group">
                    <label class="dsar-field-label">Request Type <span class="req">*</span></label>
                    <div class="dsar-types-grid">
                        
                        <div class="dsar-type-card selected" onclick="selectDsarType(this, 'Right of Access (Art. 15)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-eye"></i></div>
                            <div>
                                <div class="dsar-type-name">Right of Access (Art. 15)</div>
                                <div class="dsar-type-desc">Request a copy of all personal data we hold about you.</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'Right to Rectification (Art. 16)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                            <div>
                                <div class="dsar-type-name">Right to Rectification (Art. 16)</div>
                                <div class="dsar-type-desc">Request correction of inaccurate or incomplete data.</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'Right to Erasure (Art. 17)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-trash-can"></i></div>
                            <div>
                                <div class="dsar-type-name">Right to Erasure (Art. 17)</div>
                                <div class="dsar-type-desc">Request deletion of your personal data ("right to be forgotten").</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'Right to Restriction (Art. 18)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-ban"></i></div>
                            <div>
                                <div class="dsar-type-name">Right to Restriction (Art. 18)</div>
                                <div class="dsar-type-desc">Request that we restrict processing of your data.</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'Right to Portability (Art. 20)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-download"></i></div>
                            <div>
                                <div class="dsar-type-name">Right to Portability (Art. 20)</div>
                                <div class="dsar-type-desc">Request your data in a structured, machine-readable format.</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'Right to Object (Art. 21)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-hand"></i></div>
                            <div>
                                <div class="dsar-type-name">Right to Object (Art. 21)</div>
                                <div class="dsar-type-desc">Object to processing based on legitimate interests or direct marketing.</div>
                            </div>
                        </div>

                        <div class="dsar-type-card" onclick="selectDsarType(this, 'CCPA / CPRA Request (California)')">
                            <div class="dsar-type-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <div class="dsar-type-name">CCPA / CPRA Request (California)</div>
                                <div class="dsar-type-desc">Exercise your California privacy rights under CCPA/CPRA.</div>
                            </div>
                        </div>

                    </div>
                    <input type="hidden" name="request_type" id="selectedRequestTypeInput" value="Right of Access (Art. 15)">
                </div>

                <div class="dsar-input-row">
                    <div class="dsar-field-group">
                        <label class="dsar-field-label">Full Name <span class="req">*</span></label>
                        <input type="text" name="name" required class="dsar-input-control" placeholder="Jane Smith">
                    </div>
                    <div class="dsar-field-group">
                        <label class="dsar-field-label">Email Address <span class="req">*</span></label>
                        <input type="email" name="email" required class="dsar-input-control" placeholder="jane@example.com">
                    </div>
                </div>

                <div class="dsar-field-group">
                    <label class="dsar-field-label">Identity Verification <span class="req">*</span></label>
                    <div class="dsar-field-hint" style="margin-bottom: 8px;">To protect your data, we must verify your identity before processing your request (GDPR Art. 12(6)). Please describe how we can verify you — e.g. the email address you used to contact us, a project reference number, or other identifying information.</div>
                    <input type="text" name="verification_info" required class="dsar-input-control" placeholder="e.g. I contacted you in March 2025 about a wedding video project">
                </div>

                <div class="dsar-field-group">
                    <label class="dsar-field-label">Additional Details <span style="color:#71717a; font-weight:normal;">(optional, max 500 chars)</span></label>
                    <textarea name="details" rows="3" maxlength="500" class="dsar-textarea-control" placeholder="Any additional context about your request..."></textarea>
                </div>

                <div class="dsar-sla-notice">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>We will respond within <strong>30 days</strong> (extendable by 60 days for complex requests). There is no charge for exercising your rights. We process this data solely to handle your request under GDPR Art. 6(1)(c) (legal obligation).</div>
                </div>

                <button type="submit" class="btn-submit-dsar"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>

        <!-- Footer Bar -->
        <footer class="privacy-footer-bar">
            <div class="privacy-footer-left">
                <a href="/terms.php"><i class="fa-solid fa-file-contract"></i> Terms of Service</a>
                <a href="/cookies.php"><i class="fa-solid fa-cookie"></i> Cookie Policy</a>
                <a href="/contact.php"><i class="fa-regular fa-envelope"></i> Unsubscribe</a>
            </div>
            <div>
                <a href="/" style="color: #a1a1aa; text-decoration: none;"><i class="fa-solid fa-house"></i> Back to Home</a>
            </div>
        </footer>

    </main>

    <!-- Scripts -->
    <script>
        function selectDsarType(cardElement, typeName) {
            document.querySelectorAll('.dsar-type-card').forEach(c => c.classList.remove('selected'));
            cardElement.classList.add('selected');
            document.getElementById('selectedRequestTypeInput').value = typeName;
        }

        function handleDsarSubmit(e) {
            e.preventDefault();
            const type = document.getElementById('selectedRequestTypeInput').value;
            alert(`Thank you! Your Data Subject Request (${type}) has been submitted successfully. Our Privacy Team will respond within 30 days.`);
            e.target.reset();
            selectDsarType(document.querySelector('.dsar-type-card'), 'Right of Access (Art. 15)');
        }
    </script>

</body>
</html>
