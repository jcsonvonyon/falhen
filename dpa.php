<?php
// dpa.php - Data Processing Agreement matching falhen.com/dpa
$pageTitle = "Data Processing Agreement (DPA) — Falhen Media";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Data Processing Agreement (DPA) governing how Falhen Media processes personal data under GDPR Article 28, UK GDPR, and US privacy laws.">

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
        .dpa-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dpa-nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dpa-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .dpa-back-link:hover {
            color: #ffffff;
        }

        .dpa-top-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .dpa-top-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .dpa-top-links a:hover {
            color: #ffffff;
        }

        /* Hero Stage */
        .dpa-hero-section {
            padding: 70px 0 50px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at 50% 10%, rgba(220, 38, 38, 0.15) 0%, transparent 60%);
        }

        .dpa-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .dpa-badge-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
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

        .dpa-hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 16px 0;
            line-height: 1.1;
        }

        .dpa-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            margin: 0 0 28px 0;
            max-width: 800px;
        }

        .dpa-meta-row {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 0.85rem;
            color: #71717a;
            font-weight: 600;
            flex-wrap: wrap;
        }

        .dpa-meta-row span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dpa-meta-row i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        /* Callout Card */
        .dpa-cta-banner {
            background: linear-gradient(135deg, rgba(20, 20, 26, 0.95) 0%, rgba(12, 12, 16, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 24px 32px;
            margin: 50px 0 40px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .dpa-cta-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .dpa-cta-icon {
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

        .dpa-cta-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .dpa-cta-desc {
            color: #a1a1aa;
            font-size: 0.88rem;
            margin: 0;
        }

        .btn-dpa-request {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dpa-request:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* 4 Key Feature Cards */
        .compliance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 50px;
        }

        .compliance-card {
            background: rgba(18, 18, 24, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }

        .compliance-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.25);
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .compliance-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .compliance-subtitle {
            font-size: 0.78rem;
            color: #71717a;
            font-weight: 600;
            margin: 0;
        }

        /* Table of Contents Grid */
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
            font-size: 1.1rem;
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

        /* Execution Section Box */
        .execution-box {
            background: linear-gradient(135deg, rgba(20, 20, 26, 0.95) 0%, rgba(12, 12, 16, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 40px;
            margin: 60px 0;
        }

        .execution-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #71717a;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .execution-desc {
            color: #a1a1aa;
            font-size: 0.92rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .sig-card {
            background: rgba(3, 3, 5, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 24px;
        }

        .sig-role-badge {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #71717a;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .sig-party-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .sig-party-sub {
            font-size: 0.85rem;
            color: #a1a1aa;
            margin-bottom: 24px;
        }

        .sig-line {
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
            padding-top: 8px;
            font-size: 0.78rem;
            color: #71717a;
            font-weight: 600;
        }

        /* Footer Bar */
        .dpa-footer-bar {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
            margin-top: 40px;
        }

        .dpa-footer-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .dpa-footer-left a {
            color: #a1a1aa;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.25s ease;
        }

        .dpa-footer-left a:hover {
            color: #ffffff;
        }

        .dpa-footer-left i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        @media (max-width: 992px) {
            .compliance-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .contents-grid {
                grid-template-columns: 1fr;
            }
            .signatures-grid {
                grid-template-columns: 1fr;
            }
            .dpa-cta-banner {
                flex-direction: column;
                align-items: flex-start;
            }
            .dpa-hero-title {
                font-size: 2.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="dpa-nav-bar">
        <div class="dpa-nav-container">
            <a href="/" class="dpa-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <div class="dpa-top-links">
                <a href="/privacy.php">Privacy</a>
                <a href="/terms.php">Terms</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="dpa-hero-section">
        <div class="dpa-container">
            <div class="dpa-badge-group">
                <span class="badge-red">Legal</span>
                <span class="badge-green">GDPR Art. 28</span>
                <span class="badge-grey">Enterprise</span>
            </div>

            <h1 class="dpa-hero-title">Data Processing Agreement</h1>
            <p class="dpa-hero-subtitle">This Data Processing Agreement (DPA) governs how Falhen Media processes personal data on behalf of enterprise clients in compliance with GDPR Article 28, the UK GDPR, and applicable US privacy laws.</p>

            <div class="dpa-meta-row">
                <span><i class="fa-regular fa-calendar"></i> Effective: 1 January 2024</span>
                <span><i class="fa-solid fa-rotate-left"></i> Last updated: 28 April 2026</span>
                <span><i class="fa-solid fa-location-dot"></i> Jurisdiction: United States</span>
            </div>
        </div>
    </section>

    <main class="dpa-container">

        <!-- Callout Banner ("Need a countersigned DPA?") -->
        <div class="dpa-cta-banner">
            <div class="dpa-cta-left">
                <div class="dpa-cta-icon"><i class="fa-solid fa-file-contract"></i></div>
                <div>
                    <h3 class="dpa-cta-title">Need a countersigned DPA?</h3>
                    <p class="dpa-cta-desc">Enterprise clients can request a countersigned copy for their compliance records within 5 business days.</p>
                </div>
            </div>
            <a href="/contact.php" class="btn-dpa-request">
                <i class="fa-solid fa-file-signature"></i> Request Signed DPA
            </a>
        </div>

        <!-- 4 Key Compliance Cards -->
        <div class="compliance-grid">
            <div class="compliance-card">
                <div class="compliance-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h4 class="compliance-title">GDPR Art. 28</h4>
                <p class="compliance-subtitle">Fully compliant</p>
            </div>
            <div class="compliance-card">
                <div class="compliance-icon"><i class="fa-solid fa-clock"></i></div>
                <h4 class="compliance-title">48-hr Breach Notice</h4>
                <p class="compliance-subtitle">Notification SLA</p>
            </div>
            <div class="compliance-card">
                <div class="compliance-icon"><i class="fa-solid fa-server"></i></div>
                <h4 class="compliance-title">4 Sub-Processors</h4>
                <p class="compliance-subtitle">Listed & auditable</p>
            </div>
            <div class="compliance-card">
                <div class="compliance-icon"><i class="fa-solid fa-globe"></i></div>
                <h4 class="compliance-title">SCC Transfers</h4>
                <p class="compliance-subtitle">EU-US safeguards</p>
            </div>
        </div>

        <!-- Table of Contents -->
        <div class="contents-box">
            <div class="contents-title">CONTENTS</div>
            <div class="contents-grid">
                <div>
                    <a href="#sec-1" class="contents-link"><span class="arrow">&gt;</span> 1. Definitions</a>
                    <a href="#sec-3" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 3. Nature & Purpose of Processing</a>
                    <a href="#sec-5" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 5. Processor Obligations</a>
                    <a href="#sec-7" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 7. International Data Transfers</a>
                    <a href="#sec-9" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 9. Data Return & Deletion</a>
                    <a href="#sec-11" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 11. Liability & Indemnification</a>
                    <a href="#sec-13" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 13. How to Execute This DPA</a>
                </div>
                <div>
                    <a href="#sec-2" class="contents-link"><span class="arrow">&gt;</span> 2. Subject Matter & Duration</a>
                    <a href="#sec-4" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 4. Types of Personal Data & Data Subjects</a>
                    <a href="#sec-6" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 6. Sub-Processors</a>
                    <a href="#sec-8" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 8. Personal Data Breach Notification</a>
                    <a href="#sec-10" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 10. Audit Rights</a>
                    <a href="#sec-12" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 12. Governing Law & Jurisdiction</a>
                </div>
            </div>
        </div>

        <!-- Section 1 -->
        <section class="legal-section-block" id="sec-1">
            <h2 class="legal-section-heading">1. Definitions</h2>
            <div class="legal-sub-heading">1.1 Key Terms</div>
            <p class="legal-paragraph"><strong>"Controller"</strong> means the entity that determines the purposes and means of processing personal data (the Client). <strong>"Processor"</strong> means Falhen Media, which processes personal data on behalf of the Controller. <strong>"Data Subject"</strong> means any identified or identifiable natural person whose personal data is processed. <strong>"Personal Data,"</strong> <strong>"Processing,"</strong> <strong>"Supervisory Authority,"</strong> and <strong>"Data Breach"</strong> have the meanings given in the GDPR. <strong>"GDPR"</strong> means Regulation (EU) 2016/679 of the European Parliament and of the Council. <strong>"Services"</strong> means the media production and related services provided by Falhen Media under the main service agreement.</p>
        </section>

        <!-- Section 2 -->
        <section class="legal-section-block" id="sec-2">
            <h2 class="legal-section-heading">2. Subject Matter & Duration</h2>
            <div class="legal-sub-heading">2.1 Subject Matter</div>
            <p class="legal-paragraph">This Data Processing Agreement ("DPA") governs the processing of personal data by Falhen Media (Processor) on behalf of the Client (Controller) in connection with the provision of Services under the main service agreement between the parties.</p>

            <div class="legal-sub-heading">2.2 Duration</div>
            <p class="legal-paragraph">This DPA is effective from the date the main service agreement is signed and remains in force for the duration of the Services. Upon termination or expiry of the main service agreement, this DPA shall automatically terminate, subject to the data deletion and return obligations in Section 9.</p>
        </section>

        <!-- Section 3 -->
        <section class="legal-section-block" id="sec-3">
            <h2 class="legal-section-heading">3. Nature & Purpose of Processing</h2>
            <div class="legal-sub-heading">3.1 Nature of Processing</div>
            <p class="legal-paragraph">Falhen Media processes personal data solely to the extent necessary to deliver the agreed Services, which may include: storing and managing client contact information; processing talent, crew, and vendor personal data for production purposes; managing client gallery access and authentication; and communicating project updates and deliverables.</p>

            <div class="legal-sub-heading">3.2 Purpose Limitation</div>
            <p class="legal-paragraph">Falhen Media shall process personal data only for the specific purposes set out in this DPA and the main service agreement, and for no other purpose, unless required by applicable law. Falhen Media shall promptly notify the Controller if, in its opinion, an instruction from the Controller infringes applicable data protection law.</p>
        </section>

        <!-- Section 4 -->
        <section class="legal-section-block" id="sec-4">
            <h2 class="legal-section-heading">4. Types of Personal Data & Data Subjects</h2>
            <div class="legal-sub-heading">4.1 Categories of Personal Data</div>
            <p class="legal-paragraph">The personal data processed under this DPA may include: identification data (name, job title, company); contact data (email address, phone number, postal address); authentication data (usernames, hashed passwords, access tokens); project-related data (creative briefs, feedback, approvals); financial data (billing address, invoice records); and any other personal data contained in materials provided by the Controller.</p>

            <div class="legal-sub-heading">4.2 Categories of Data Subjects</div>
            <p class="legal-paragraph">Data subjects include: Controller's employees, representatives, and contractors; talent and actors engaged in media productions; and end-users accessing client video content or proofing galleries.</p>
        </section>

        <!-- Section 5 -->
        <section class="legal-section-block" id="sec-5">
            <h2 class="legal-section-heading">5. Processor Obligations</h2>
            <div class="legal-sub-heading">5.1 Confidentiality</div>
            <p class="legal-paragraph">Falhen Media shall ensure that all personnel authorized to process personal data have committed themselves to confidentiality or are under an appropriate statutory obligation of confidentiality.</p>

            <div class="legal-sub-heading">5.2 Security Measures</div>
            <p class="legal-paragraph">Falhen Media shall implement appropriate technical and organizational measures to ensure a level of security appropriate to the risk, including encryption in transit and at rest, access controls, vulnerability management, and regular security assessments.</p>
        </section>

        <!-- Section 6 -->
        <section class="legal-section-block" id="sec-6">
            <h2 class="legal-section-heading">6. Sub-Processors</h2>
            <div class="legal-sub-heading">6.1 Authorization</div>
            <p class="legal-paragraph">The Controller provides general authorization for Falhen Media to engage third-party sub-processors to assist in delivering the Services. Current sub-processors include cloud hosting providers (AWS, Google Cloud), media asset management systems, and payment processors.</p>

            <div class="legal-sub-heading">6.2 Sub-Processor Obligations</div>
            <p class="legal-paragraph">Falhen Media shall impose data protection terms on any sub-processor it engages that provide at least the same level of protection as those set out in this DPA.</p>
        </section>

        <!-- Section 7 -->
        <section class="legal-section-block" id="sec-7">
            <h2 class="legal-section-heading">7. International Data Transfers</h2>
            <div class="legal-sub-heading">7.1 Transfer Safeguards</div>
            <p class="legal-paragraph">Where personal data originating in the European Economic Area (EEA), the UK, or Switzerland is transferred outside these regions, Falhen Media shall rely on valid transfer mechanisms, such as the EU Standard Contractual Clauses (SCCs) or the UK International Data Transfer Addendum (IDTA).</p>
        </section>

        <!-- Section 8 -->
        <section class="legal-section-block" id="sec-8">
            <h2 class="legal-section-heading">8. Personal Data Breach Notification</h2>
            <div class="legal-sub-heading">8.1 Notification SLA</div>
            <p class="legal-paragraph">Falhen Media shall notify the Controller without undue delay, and in any event within 48 hours, after becoming aware of a personal data breach affecting the Controller's personal data.</p>
        </section>

        <!-- Section 9 -->
        <section class="legal-section-block" id="sec-9">
            <h2 class="legal-section-heading">9. Data Return & Deletion</h2>
            <div class="legal-sub-heading">9.1 Deletion or Return</div>
            <p class="legal-paragraph">Upon termination of the Services, Falhen Media shall, at the choice of the Controller, delete or return all personal data to the Controller, unless applicable law requires continued storage.</p>
        </section>

        <!-- Section 10 -->
        <section class="legal-section-block" id="sec-10">
            <h2 class="legal-section-heading">10. Audit Rights</h2>
            <div class="legal-sub-heading">10.1 Compliance Audits</div>
            <p class="legal-paragraph">Falhen Media shall make available to the Controller all information necessary to demonstrate compliance with GDPR Article 28 and allow for and contribute to audits conducted by the Controller or an independent auditor.</p>
        </section>

        <!-- Section 11 -->
        <section class="legal-section-block" id="sec-11">
            <h2 class="legal-section-heading">11. Liability & Indemnification</h2>
            <div class="legal-sub-heading">11.1 Liability</div>
            <p class="legal-paragraph">Each party's liability under this DPA shall be subject to the limitations of liability set out in the main service agreement.</p>
        </section>

        <!-- Section 12 -->
        <section class="legal-section-block" id="sec-12">
            <h2 class="legal-section-heading">12. Governing Law & Jurisdiction</h2>
            <div class="legal-sub-heading">12.1 Governing Law</div>
            <p class="legal-paragraph">This DPA shall be governed by the law governing the main service agreement, or where GDPR compliance mandates, the laws of an EU Member State.</p>
        </section>

        <!-- Section 13 -->
        <section class="legal-section-block" id="sec-13">
            <h2 class="legal-section-heading">13. How to Execute This DPA</h2>
            <div class="legal-sub-heading">13.1 Execution Process</div>
            <p class="legal-paragraph">Enterprise clients requiring a countersigned DPA for their compliance records should contact <span style="color: #ef4444;">legal@falhen.com</span> with the subject line <strong>"DPA Request — [Company Name]"</strong>. We will provide a countersigned copy within 5 business days. This page constitutes the standard terms of our DPA; individual enterprise agreements may include additional or modified terms by mutual written agreement.</p>

            <div class="legal-sub-heading">13.2 Effective Date</div>
            <p class="legal-paragraph">This DPA is effective as of the date the main service agreement between the parties is signed, or the date the Client first engages Falhen Media's services, whichever is earlier.</p>
        </section>

        <!-- Execution Box -->
        <div class="execution-box">
            <div class="execution-title">EXECUTION</div>
            <p class="execution-desc">This DPA is incorporated into and forms part of the main service agreement between Falhen Media and the Client. By signing the main service agreement, both parties agree to be bound by the terms of this DPA. For a countersigned standalone copy, contact <span style="color: #ef4444;">legal@falhen.com</span>.</p>

            <div class="signatures-grid">
                <div class="sig-card">
                    <div class="sig-role-badge">DATA PROCESSOR</div>
                    <h3 class="sig-party-name">Falhen Media</h3>
                    <p class="sig-party-sub">legal@falhen.com</p>
                    <div class="sig-line">Authorised Signatory</div>
                </div>

                <div class="sig-card">
                    <div class="sig-role-badge">DATA CONTROLLER</div>
                    <h3 class="sig-party-name">Client / Enterprise Partner</h3>
                    <p class="sig-party-sub">As named in the service agreement</p>
                    <div class="sig-line">Authorised Signatory</div>
                </div>
            </div>
        </div>

        <!-- Footer Bar -->
        <footer class="dpa-footer-bar">
            <div class="dpa-footer-left">
                <a href="/privacy.php"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a>
                <a href="/terms.php"><i class="fa-solid fa-shield-halved"></i> Terms of Service</a>
                <a href="/cookies.php"><i class="fa-solid fa-shield-halved"></i> Cookie Policy</a>
            </div>
            <div>
                <a href="/" style="color: #a1a1aa; text-decoration: none;"><i class="fa-solid fa-house"></i> Back to Home</a>
            </div>
        </footer>

    </main>

    <!-- Fixed Mobile Floating Bottom Dock Navigation Widget -->
    <nav class="mobile-bottom-dock">
        <a href="/" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-house"></i></div>
            <span class="dock-label">HOME</span>
        </a>
        <a href="/services.php" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-briefcase"></i></div>
            <span class="dock-label">SERVICES</span>
        </a>
        <a href="/portfolio.php" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-film"></i></div>
            <span class="dock-label">WORK</span>
        </a>
        <a href="/contact.php" class="dock-item">
            <div class="dock-icon-box"><i class="fa-solid fa-envelope"></i></div>
            <span class="dock-label">CONTACT</span>
        </a>
        <button type="button" class="dock-item dock-top-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <div class="dock-icon-box"><i class="fa-solid fa-arrow-up"></i></div>
            <span class="dock-label">TOP</span>
        </button>
    </nav>
</body>
</html>
