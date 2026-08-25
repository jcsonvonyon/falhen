<?php
// terms.php - Terms of Service page matching falhen.com/terms
$pageTitle = "Terms of Service — Falhen Media";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Terms of Service governing the use of Falhen Media website and engagement of video production, photography, and creative media services.">

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
        .terms-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .terms-nav-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .terms-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .terms-back-link:hover {
            color: #ffffff;
        }

        .terms-top-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .terms-top-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .terms-top-links a:hover {
            color: #ffffff;
        }

        /* Hero Stage */
        .terms-hero-section {
            padding: 70px 0 50px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at 50% 10%, rgba(220, 38, 38, 0.15) 0%, transparent 60%);
        }

        .terms-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .terms-badge-group {
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

        .badge-grey {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .terms-hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 16px 0;
            line-height: 1.1;
        }

        .terms-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            margin: 0 0 28px 0;
        }

        .terms-meta-row {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 0.85rem;
            color: #71717a;
            font-weight: 600;
            flex-wrap: wrap;
        }

        .terms-meta-row span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .terms-meta-row i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        /* Table of Contents Box */
        .contents-box {
            background: rgba(14, 14, 18, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 32px;
            margin: 50px 0 60px 0;
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

        /* Footer Bar */
        .terms-footer-bar {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
            margin-top: 60px;
        }

        .terms-footer-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .terms-footer-left a {
            color: #a1a1aa;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.25s ease;
        }

        .terms-footer-left a:hover {
            color: #ffffff;
        }

        .terms-footer-left i {
            color: #ef4444;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .contents-grid {
                grid-template-columns: 1fr;
            }
            .terms-hero-title {
                font-size: 2.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="terms-nav-bar">
        <div class="terms-nav-container">
            <a href="/" class="terms-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <div class="terms-top-links">
                <a href="/privacy.php">Privacy</a>
                <a href="/cookies.php">Cookies</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="terms-hero-section">
        <div class="terms-container">
            <div class="terms-badge-group">
                <span class="badge-red">Legal</span>
                <span class="badge-grey">US Jurisdiction</span>
            </div>

            <h1 class="terms-hero-title">Terms of Service</h1>
            <p class="terms-hero-subtitle">These Terms of Service govern your use of the Falhen Media website and your engagement of our professional media production services. Please read them carefully before using our services.</p>

            <div class="terms-meta-row">
                <span><i class="fa-regular fa-calendar"></i> Effective: 1 January 2024</span>
                <span><i class="fa-solid fa-rotate-left"></i> Last updated: 28 April 2026</span>
                <span><i class="fa-solid fa-location-dot"></i> Jurisdiction: United States</span>
            </div>
        </div>
    </section>

    <main class="terms-container">

        <!-- Table of Contents -->
        <div class="contents-box">
            <div class="contents-title">CONTENTS</div>
            <div class="contents-grid">
                <div>
                    <a href="#sec-1" class="contents-link"><span class="arrow">&gt;</span> 1. Acceptance of Terms</a>
                    <a href="#sec-3" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 3. Client Obligations</a>
                    <a href="#sec-5" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 5. Intellectual Property</a>
                    <a href="#sec-7" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 7. Cancellation & Termination</a>
                    <a href="#sec-9" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 9. Limitation of Liability</a>
                    <a href="#sec-11" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 11. Confidentiality</a>
                    <a href="#sec-13" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 13. Governing Law & Dispute Resolution</a>
                </div>
                <div>
                    <a href="#sec-2" class="contents-link"><span class="arrow">&gt;</span> 2. Services</a>
                    <a href="#sec-4" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 4. Fees & Payment</a>
                    <a href="#sec-6" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 6. Revisions & Change Requests</a>
                    <a href="#sec-8" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 8. Representations & Warranties</a>
                    <a href="#sec-10" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 10. Indemnification</a>
                    <a href="#sec-12" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 12. Data Protection</a>
                    <a href="#sec-14" class="contents-link" style="margin-top:10px;"><span class="arrow">&gt;</span> 14. General Provisions</a>
                </div>
            </div>
        </div>

        <!-- Section 1 -->
        <section class="legal-section-block" id="sec-1">
            <h2 class="legal-section-heading">1. Acceptance of Terms</h2>
            <div class="legal-sub-heading">1.1 Agreement</div>
            <p class="legal-paragraph">By accessing our website, requesting a quote, or engaging Falhen Media for any services, you agree to be bound by these Terms of Service and all applicable laws and regulations of the United States. If you do not agree with any part of these terms, you may not use our services.</p>

            <div class="legal-sub-heading">1.2 Capacity</div>
            <p class="legal-paragraph">By accepting these terms, you represent that you are at least 18 years of age and have the legal authority to enter into binding agreements on behalf of yourself or the organisation you represent.</p>

            <div class="legal-sub-heading">1.3 Updates to Terms</div>
            <p class="legal-paragraph">We reserve the right to update these Terms of Service at any time. Material changes will be communicated via email or a prominent notice on our website at least 14 days before taking effect. Your continued use of our services after the effective date constitutes acceptance of the revised terms.</p>
        </section>

        <!-- Section 2 -->
        <section class="legal-section-block" id="sec-2">
            <h2 class="legal-section-heading">2. Services</h2>
            <div class="legal-sub-heading">2.1 Scope of Services</div>
            <p class="legal-paragraph">Falhen Media provides professional video production, photography, live streaming, post-production, animation, content strategy, and related creative media services. The specific scope, deliverables, timeline, and fees for each engagement are defined in a separate Statement of Work (SOW) or project proposal agreed upon in writing.</p>

            <div class="legal-sub-heading">2.2 Service Modifications</div>
            <p class="legal-paragraph">We reserve the right to modify, suspend, or discontinue any service at any time with reasonable notice. We will not be liable to you or any third party for any modification, suspension, or discontinuation of services, except where such modification constitutes a material breach of an existing project agreement.</p>

            <div class="legal-sub-heading">2.3 Subcontractors</div>
            <p class="legal-paragraph">Falhen Media may engage qualified subcontractors or freelancers to assist in delivering services. We remain responsible for the quality and delivery of all work product regardless of whether subcontractors are used.</p>
        </section>

        <!-- Section 3 -->
        <section class="legal-section-block" id="sec-3">
            <h2 class="legal-section-heading">3. Client Obligations</h2>
            <div class="legal-sub-heading">3.1 Timely Cooperation</div>
            <p class="legal-paragraph">You agree to provide all necessary materials, approvals, access, and information required for us to deliver the agreed services in a timely manner. Delays caused by your failure to provide required inputs may result in revised timelines and additional costs, for which Falhen Media shall not be liable.</p>

            <div class="legal-sub-heading">3.2 Accuracy of Information</div>
            <p class="legal-paragraph">You are responsible for ensuring that all information, content, and materials you provide to us are accurate, complete, and do not infringe any third-party rights. You warrant that you have all necessary rights, licences, and permissions to use any materials you supply.</p>

            <div class="legal-sub-heading">3.3 Feedback & Approvals</div>
            <p class="legal-paragraph">You agree to review deliverables and provide consolidated, actionable feedback within the timeframes specified in the project agreement. Approval of a deliverable — whether express or by failure to respond within the agreed review period — constitutes acceptance of that stage of work.</p>

            <div class="legal-sub-heading">3.4 Lawful Use</div>
            <p class="legal-paragraph">You agree not to use our services or deliverables for any unlawful purpose, including but not limited to: defamation, harassment, infringement of third-party intellectual property rights, or violation of any applicable US federal or state law.</p>
        </section>

        <!-- Section 4 -->
        <section class="legal-section-block" id="sec-4">
            <h2 class="legal-section-heading">4. Fees & Payment</h2>
            <div class="legal-sub-heading">4.1 Pricing</div>
            <p class="legal-paragraph">All fees are as agreed in the project proposal or SOW. Prices are exclusive of applicable taxes (including US sales tax where applicable) unless stated otherwise. We reserve the right to adjust pricing for future projects with 30 days' written notice.</p>

            <div class="legal-sub-heading">4.2 Payment Terms</div>
            <p class="legal-paragraph">Unless otherwise agreed in writing, a deposit of 50% of the total project fee is due upon signing the project agreement, with the remaining balance due upon delivery of final files. Invoices are payable within 14 days of the invoice date.</p>

            <div class="legal-sub-heading">4.3 Late Payment</div>
            <p class="legal-paragraph">Invoices not paid within the agreed payment period may incur interest at a rate of 1.5% per month (18% per annum) on the outstanding balance, or the maximum rate permitted by applicable law, whichever is lower. We reserve the right to suspend work on any project where payment is overdue by more than 14 days.</p>

            <div class="legal-sub-heading">4.4 Expenses</div>
            <p class="legal-paragraph">Out-of-pocket expenses incurred in delivering your project — including travel, accommodation, equipment hire, location fees, talent fees, and licensing costs — will be charged at cost plus a 10% administration fee, unless included in the agreed project fee.</p>

            <div class="legal-sub-heading">4.5 Disputed Invoices</div>
            <p class="legal-paragraph">If you dispute any portion of an invoice, you must notify us in writing within 7 days of the invoice date, specifying the disputed amount and the reasons for the dispute. Undisputed portions of invoices remain due and payable by the original due date.</p>
        </section>

        <!-- Section 5 -->
        <section class="legal-section-block" id="sec-5">
            <h2 class="legal-section-heading">5. Intellectual Property</h2>
            <div class="legal-sub-heading">5.1 Ownership of Deliverables</div>
            <p class="legal-paragraph">Upon receipt of full and final payment, Falhen Media assigns to you all rights, title, and interest in the final deliverables produced specifically for your project under a work-for-hire arrangement to the extent permitted by US copyright law (17 U.S.C. § 101). This assignment does not include underlying tools, templates, pre-existing works, or third-party licensed elements.</p>

            <div class="legal-sub-heading">5.2 Licence Prior to Full Payment</div>
            <p class="legal-paragraph">Prior to receipt of full payment, Falhen Media grants you a limited, non-exclusive, non-transferable licence to use deliverables solely for internal review and approval purposes. No commercial use is permitted until full payment is received.</p>

            <div class="legal-sub-heading">5.3 Falhen Media Portfolio Rights</div>
            <p class="legal-paragraph">Unless you request otherwise in writing prior to project commencement, Falhen Media retains the right to display completed work in our portfolio, website, social media channels, and marketing materials for the purpose of promoting our services.</p>

            <div class="legal-sub-heading">5.4 Third-Party Content</div>
            <p class="legal-paragraph">Any music, stock footage, fonts, or other third-party content incorporated into deliverables is subject to the applicable third-party licence terms. You are responsible for ensuring continued compliance with those licences after delivery, including any renewal or expansion of usage rights.</p>

            <div class="legal-sub-heading">5.5 Pre-Existing IP</div>
            <p class="legal-paragraph">Each party retains ownership of all intellectual property owned or developed prior to or independently of the project. Nothing in these terms transfers ownership of pre-existing IP.</p>
        </section>

        <!-- Section 6 -->
        <section class="legal-section-block" id="sec-6">
            <h2 class="legal-section-heading">6. Revisions & Change Requests</h2>
            <div class="legal-sub-heading">6.1 Included Revisions</div>
            <p class="legal-paragraph">Each project includes a defined number of revision rounds as specified in the project proposal. Revisions are defined as minor amendments to approved work and do not include changes to the agreed creative direction, scope, or format.</p>

            <div class="legal-sub-heading">6.2 Additional Revisions & Change Orders</div>
            <p class="legal-paragraph">Revision requests beyond the agreed number, or requests that constitute a change in scope, will be documented in a written Change Order and billed separately at our standard hourly rate or as a fixed fee, agreed in writing before work commences. No additional work will begin without a signed Change Order.</p>
        </section>

        <!-- Section 7 -->
        <section class="legal-section-block" id="sec-7">
            <h2 class="legal-section-heading">7. Cancellation & Termination</h2>
            <div class="legal-sub-heading">7.1 Client Cancellation</div>
            <p class="legal-paragraph">If you cancel a project after work has commenced, you remain liable for all fees for work completed to the date of cancellation, plus any non-recoverable expenses incurred. Deposits are non-refundable unless Falhen Media is in material breach of the project agreement.</p>

            <div class="legal-sub-heading">7.2 Termination for Cause</div>
            <p class="legal-paragraph">Either party may terminate the project agreement immediately upon written notice if the other party: (a) materially breaches the agreement and fails to remedy that breach within 14 days of receiving written notice; (b) becomes insolvent or files for bankruptcy protection; or (c) engages in fraudulent or illegal conduct.</p>

            <div class="legal-sub-heading">7.3 Effect of Termination</div>
            <p class="legal-paragraph">Upon termination, all outstanding fees become immediately due and payable. Falhen Media will deliver all completed work product upon receipt of full payment for work performed to the date of termination.</p>
        </section>

        <!-- Section 8 -->
        <section class="legal-section-block" id="sec-8">
            <h2 class="legal-section-heading">8. Representations & Warranties</h2>
            <div class="legal-sub-heading">8.1 Mutual Warranties</div>
            <p class="legal-paragraph">Each party represents and warrants that: (a) it has the full right, power, and authority to enter into and perform its obligations under these terms; (b) its performance will not violate any applicable law or regulation; and (c) it will comply with all applicable US federal and state laws in connection with these terms.</p>

            <div class="legal-sub-heading">8.2 Disclaimer of Warranties</div>
            <p class="legal-paragraph">EXCEPT AS EXPRESSLY SET OUT IN THESE TERMS, FALHEN MEDIA PROVIDES ALL SERVICES "AS IS" AND DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT, TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW.</p>
        </section>

        <!-- Section 9 -->
        <section class="legal-section-block" id="sec-9">
            <h2 class="legal-section-heading">9. Limitation of Liability</h2>
            <div class="legal-sub-heading">9.1 Exclusion of Consequential Loss</div>
            <p class="legal-paragraph">TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, FALHEN MEDIA SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS, REVENUE, DATA, GOODWILL, OR BUSINESS OPPORTUNITIES, ARISING OUT OF OR IN CONNECTION WITH OUR SERVICES OR THESE TERMS, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.</p>

            <div class="legal-sub-heading">9.2 Cap on Liability</div>
            <p class="legal-paragraph">Our total aggregate liability to you for any claim arising out of or in connection with a project shall not exceed the total fees paid by you to Falhen Media for that specific project in the 12 months preceding the claim.</p>

            <div class="legal-sub-heading">9.3 Force Majeure</div>
            <p class="legal-paragraph">Neither party shall be liable for delays or failures in performance resulting from circumstances beyond their reasonable control, including natural disasters, pandemics, acts of government, power failures, internet outages, or labour disputes. The affected party must notify the other within 5 business days of the force majeure event.</p>

            <div class="legal-sub-heading">9.4 Essential Basis</div>
            <p class="legal-paragraph">The limitations of liability in this section reflect a fair allocation of risk between the parties and are an essential basis of the bargain between the parties. These limitations apply notwithstanding any failure of essential purpose of any limited remedy.</p>
        </section>

        <!-- Section 10 -->
        <section class="legal-section-block" id="sec-10">
            <h2 class="legal-section-heading">10. Indemnification</h2>
            <div class="legal-sub-heading">10.1 Client Indemnification</div>
            <p class="legal-paragraph">You agree to indemnify, defend, and hold harmless Falhen Media and its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses (including reasonable attorneys' fees) arising out of or in connection with: (a) your breach of these terms; (b) your use of our deliverables in violation of applicable law; (c) any materials you provide that infringe third-party rights; or (d) your negligence or wilful misconduct.</p>
        </section>

        <!-- Section 11 -->
        <section class="legal-section-block" id="sec-11">
            <h2 class="legal-section-heading">11. Confidentiality</h2>
            <div class="legal-sub-heading">11.1 Mutual Confidentiality</div>
            <p class="legal-paragraph">Both parties agree to keep confidential all non-public information disclosed by the other party in connection with the project ("Confidential Information"), and not to disclose such information to third parties without prior written consent, except as required by law or as necessary to deliver the services. This obligation survives termination of the project agreement for a period of 3 years.</p>

            <div class="legal-sub-heading">11.2 Exclusions</div>
            <p class="legal-paragraph">Confidentiality obligations do not apply to information that: (a) is or becomes publicly available through no fault of the receiving party; (b) was already known to the receiving party prior to disclosure; (c) is independently developed by the receiving party without use of the Confidential Information; or (d) is required to be disclosed by law or court order.</p>
        </section>

        <!-- Section 12 -->
        <section class="legal-section-block" id="sec-12">
            <h2 class="legal-section-heading">12. Data Protection</h2>
            <div class="legal-sub-heading">12.1 Personal Data</div>
            <p class="legal-paragraph">The collection and processing of personal data in connection with these terms is governed by our Privacy Policy, which is incorporated herein by reference. Both parties agree to comply with all applicable data protection laws, including the GDPR (where applicable), CCPA/CPRA, and other applicable US state privacy laws.</p>

            <div class="legal-sub-heading">12.2 Data Processing Agreement</div>
            <p class="legal-paragraph">Where Falhen Media processes personal data on your behalf as a data processor, the parties will enter into a separate Data Processing Agreement (DPA) in accordance with GDPR Article 28 and applicable US law requirements.</p>
        </section>

        <!-- Section 13 -->
        <section class="legal-section-block" id="sec-13">
            <h2 class="legal-section-heading">13. Governing Law & Dispute Resolution</h2>
            <div class="legal-sub-heading">13.1 Governing Law</div>
            <p class="legal-paragraph">These Terms of Service are governed by and construed in accordance with the laws of the United States and the State of Illinois, without regard to its conflict of law provisions.</p>

            <div class="legal-sub-heading">13.2 Informal Resolution</div>
            <p class="legal-paragraph">Before initiating formal legal proceedings, both parties agree to attempt to resolve any dispute through good-faith negotiation for a period of at least 30 days following written notice of the dispute.</p>

            <div class="legal-sub-heading">13.3 Binding Arbitration</div>
            <p class="legal-paragraph">If informal resolution fails, any dispute, claim, or controversy arising out of or relating to these terms shall be resolved by binding arbitration administered by the American Arbitration Association (AAA) under its Commercial Arbitration Rules. The arbitration shall be conducted in English. The arbitrator's decision shall be final and binding and may be entered as a judgment in any court of competent jurisdiction.</p>

            <div class="legal-sub-heading">13.4 Class Action Waiver</div>
            <p class="legal-paragraph">YOU AND FALHEN MEDIA AGREE THAT EACH MAY BRING CLAIMS AGAINST THE OTHER ONLY IN YOUR OR ITS INDIVIDUAL CAPACITY AND NOT AS A PLAINTIFF OR CLASS MEMBER IN ANY PURPORTED CLASS OR REPRESENTATIVE PROCEEDING.</p>

            <div class="legal-sub-heading">13.5 Exceptions</div>
            <p class="legal-paragraph">Notwithstanding the arbitration clause, either party may seek emergency injunctive or other equitable relief from a court of competent jurisdiction to prevent irreparable harm pending arbitration.</p>
        </section>

        <!-- Section 14 -->
        <section class="legal-section-block" id="sec-14">
            <h2 class="legal-section-heading">14. General Provisions</h2>
            <div class="legal-sub-heading">14.1 Entire Agreement</div>
            <p class="legal-paragraph">These Terms of Service, together with any project proposal, SOW, or DPA, constitute the entire agreement between you and Falhen Media with respect to the subject matter hereof and supersede all prior agreements, representations, and understandings.</p>

            <div class="legal-sub-heading">14.2 Severability</div>
            <p class="legal-paragraph">If any provision of these terms is found to be unenforceable by a court of competent jurisdiction, that provision shall be modified to the minimum extent necessary to make it enforceable, and the remaining provisions will continue in full force and effect.</p>

            <div class="legal-sub-heading">14.3 Waiver</div>
            <p class="legal-paragraph">Failure to enforce any provision of these terms shall not constitute a waiver of our right to enforce that provision in the future. No waiver shall be effective unless made in writing.</p>

            <div class="legal-sub-heading">14.4 Assignment</div>
            <p class="legal-paragraph">You may not assign or transfer your rights or obligations under these terms without our prior written consent. Falhen Media may assign these terms in connection with a merger, acquisition, or sale of all or substantially all of our assets.</p>

            <div class="legal-sub-heading">14.5 Contact</div>
            <p class="legal-paragraph">For questions about these Terms of Service, please contact us at: <span style="color: #ef4444;">legal@falhen.com</span> | <span style="color: #ef4444;">hello@falhen.com</span>.</p>
        </section>

        <!-- Footer Bar -->
        <footer class="terms-footer-bar">
            <div class="terms-footer-left">
                <a href="/privacy.php"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a>
                <a href="/cookies.php"><i class="fa-solid fa-cookie"></i> Cookie Policy</a>
            </div>
            <div>
                <a href="/" style="color: #a1a1aa; text-decoration: none;"><i class="fa-solid fa-house"></i> Back to Home</a>
            </div>
        </footer>

    </main>

</body>
</html>
