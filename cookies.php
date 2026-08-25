<?php
// cookies.php - Cookie Policy Page matching falhen.com screenshots exactly
$pageTitle = "Cookie Policy | Falhen Media";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
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

        .policy-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
        }

        .policy-nav-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .policy-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .policy-back-link:hover {
            color: #ffffff;
        }

        .policy-links-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .policy-links-right a {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.25s ease;
        }

        .policy-links-right a:hover {
            color: #ffffff;
        }

        .policy-hero {
            padding: 60px 0 40px 0;
        }

        .policy-container {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .policy-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .badge-legal {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .badge-gdpr {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .badge-eprivacy {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #a1a1aa;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .policy-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 20px 0;
            letter-spacing: -1px;
        }

        .policy-subtitle {
            font-size: 1.15rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .policy-meta-row {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 0.85rem;
            color: #71717a;
            flex-wrap: wrap;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .policy-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Consent Notice Box */
        .consent-notice-box {
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin: 40px 0;
        }

        .notice-icon {
            color: #10b981;
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .notice-content h4 {
            color: #10b981;
            font-size: 0.95rem;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .notice-content p {
            color: #a1a1aa;
            font-size: 0.88rem;
            line-height: 1.55;
            margin: 0;
        }

        /* Categories Cards */
        .categories-section {
            margin: 50px 0;
        }

        .category-card {
            background: rgba(14, 14, 18, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .category-header {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .category-title-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .category-icon {
            color: #ef4444;
            font-size: 1rem;
        }

        .category-name {
            font-size: 1rem;
            font-weight: 800;
            color: #ffffff;
        }

        .badge-always-active {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-requires-consent {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #f59e0b;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .category-header:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .category-body {
            display: none;
            padding: 0 24px 24px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .category-card.open .category-body {
            display: block;
        }

        .category-desc {
            font-size: 0.88rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin: 16px 0 20px 0;
        }

        /* Cookie Table */
        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }

        .cookie-table th {
            text-align: left;
            padding: 12px 16px;
            color: #71717a;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .cookie-table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #a1a1aa;
        }

        .cookie-table td.code-name,
        .code-name {
            color: #ff4d4d !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
        }

        /* Legal Article Sections */
        .article-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .article-heading {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .article-text {
            font-size: 0.92rem;
            color: #a1a1aa;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .policy-footer-bar {
            padding: 40px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .policy-footer-bar a {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.25s ease;
        }

        .policy-footer-bar a i {
            color: #ef4444 !important;
            margin-right: 6px;
            font-size: 0.9rem;
        }

        .policy-footer-bar a:hover {
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .policy-title {
                font-size: 2.5rem;
            }
            .policy-meta-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="policy-nav-bar">
        <div class="policy-nav-container">
            <a href="/" class="policy-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <div class="policy-links-right">
                <a href="/privacy.php">Privacy</a>
                <a href="/terms.php">Terms</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="policy-hero">
        <div class="policy-container">
            <!-- Badges -->
            <div class="policy-badges">
                <span class="badge-legal">Legal</span>
                <span class="badge-gdpr">GDPR Compliant</span>
                <span class="badge-eprivacy">ePrivacy Directive</span>
            </div>

            <!-- Title & Subtitle -->
            <h1 class="policy-title">Cookie Policy</h1>
            <p class="policy-subtitle">This Cookie Policy explains how Falhen Media uses cookies and similar tracking technologies on our website in compliance with the GDPR, ePrivacy Directive, and applicable US privacy laws.</p>

            <!-- Meta Information Row -->
            <div class="policy-meta-row">
                <span class="policy-meta-item"><i class="fa-regular fa-calendar"></i> Effective: 1 January 2024</span>
                <span>·</span>
                <span class="policy-meta-item"><i class="fa-solid fa-rotate"></i> Last updated: 28 April 2026</span>
                <span>·</span>
                <span class="policy-meta-item"><i class="fa-solid fa-location-dot"></i> Jurisdiction: United States</span>
            </div>

            <!-- Consent Notice Green Box -->
            <div class="consent-notice-box">
                <div class="notice-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="notice-content">
                    <h4>Your Consent Matters</h4>
                    <p>Under GDPR Article 7 and the ePrivacy Directive, we only place non-essential cookies after obtaining your freely given, specific, informed, and unambiguous consent. You can withdraw consent at any time. Strictly necessary cookies are exempt and do not require consent.</p>
                </div>
            </div>

            <!-- Cookie Categories Section -->
            <div class="categories-section">
                <h2 style="font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 24px;">Cookie Categories</h2>

                <!-- Category 1: Strictly Necessary (Open by Default) -->
                <div class="category-card open">
                    <div class="category-header">
                        <div class="category-title-left">
                            <i class="fa-solid fa-lock category-icon"></i>
                            <span class="category-name">Strictly Necessary</span>
                        </div>
                        <span class="badge-always-active">Always Active <i class="fa-solid fa-chevron-up accordion-arrow" style="font-size: 0.75rem;"></i></span>
                    </div>
                    <div class="category-body">
                        <p class="category-desc">These cookies are essential for the website to function and cannot be switched off in our systems. They are usually set in response to actions you take such as setting your privacy preferences, logging in, or filling in forms. Under GDPR, these cookies do not require your consent as they are strictly necessary for the provision of the service you have requested.</p>
                        <table class="cookie-table">
                            <thead>
                                <tr>
                                    <th>Cookie Name</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="code-name">session_id</td>
                                    <td>Maintains your authenticated session state across page requests</td>
                                    <td>Session</td>
                                </tr>
                                <tr>
                                    <td class="code-name">csrf_token</td>
                                    <td>Protects against cross-site request forgery (CSRF) attacks</td>
                                    <td>Session</td>
                                </tr>
                                <tr>
                                    <td class="code-name">consent_preferences</td>
                                    <td>Stores your cookie consent choices (GDPR Art. 7)</td>
                                    <td>12 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">sb-auth-token</td>
                                    <td>Supabase authentication token for client portal access</td>
                                    <td>Session</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category 2: Analytics & Performance -->
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-title-left">
                            <i class="fa-solid fa-chart-line category-icon"></i>
                            <span class="category-name">Analytics & Performance</span>
                        </div>
                        <span class="badge-requires-consent">Requires Consent <i class="fa-solid fa-chevron-down accordion-arrow" style="font-size: 0.75rem;"></i></span>
                    </div>
                    <div class="category-body">
                        <p class="category-desc">These cookies allow us to count visits and traffic sources so we can measure and improve the performance of our site. They help us know which pages are the most and least popular and see how visitors move around the site. All information these cookies collect is aggregated and therefore anonymous.</p>
                        <table class="cookie-table">
                            <thead>
                                <tr>
                                    <th>Cookie Name</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="code-name">_ga</td>
                                    <td>Google Analytics main cookie used to distinguish unique users</td>
                                    <td>2 years</td>
                                </tr>
                                <tr>
                                    <td class="code-name">_ga_P4L8E90W2</td>
                                    <td>Google Analytics session state persistence</td>
                                    <td>2 years</td>
                                </tr>
                                <tr>
                                    <td class="code-name">_gid</td>
                                    <td>Google Analytics cookie used to group user behavior patterns</td>
                                    <td>24 hours</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category 3: Functional -->
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-title-left">
                            <i class="fa-solid fa-sliders category-icon"></i>
                            <span class="category-name">Functional</span>
                        </div>
                        <span class="badge-requires-consent">Requires Consent <i class="fa-solid fa-chevron-down accordion-arrow" style="font-size: 0.75rem;"></i></span>
                    </div>
                    <div class="category-body">
                        <p class="category-desc">These cookies enable the website to provide enhanced functionality and personalisation such as remembering language choices, theme preferences, and custom media player settings. They may be set by us or by third party providers whose services we have added to our pages.</p>
                        <table class="cookie-table">
                            <thead>
                                <tr>
                                    <th>Cookie Name</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="code-name">lang_pref</td>
                                    <td>Remembers your preferred display language settings</td>
                                    <td>12 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">theme_preference</td>
                                    <td>Stores dark or light mode interface theme choice</td>
                                    <td>12 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">video_quality</td>
                                    <td>Remembers preferred video playback resolution (1080p / 4K)</td>
                                    <td>12 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">player_volume</td>
                                    <td>Saves your video player volume preference</td>
                                    <td>Persistent</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category 4: Marketing & Targeting -->
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-title-left">
                            <i class="fa-solid fa-bullhorn category-icon"></i>
                            <span class="category-name">Marketing & Targeting</span>
                        </div>
                        <span class="badge-requires-consent">Requires Consent <i class="fa-solid fa-chevron-down accordion-arrow" style="font-size: 0.75rem;"></i></span>
                    </div>
                    <div class="category-body">
                        <p class="category-desc">These cookies may be set through our site by our advertising partners. They may be used to build a profile of your interests and show you relevant adverts on other sites. Under GDPR, these cookies require your explicit prior consent (Art. 6(1)(a)) and may involve international data transfers. You may withdraw consent at any time.</p>
                        <table class="cookie-table">
                            <thead>
                                <tr>
                                    <th>Cookie Name</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="code-name">_fbp</td>
                                    <td>Facebook Pixel — tracks conversions from Facebook ads</td>
                                    <td>3 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">li_fat_id</td>
                                    <td>LinkedIn Insight Tag — conversion tracking and retargeting</td>
                                    <td>30 days</td>
                                </tr>
                                <tr>
                                    <td class="code-name">IDE</td>
                                    <td>Google DoubleClick — used for targeted advertising across the web</td>
                                    <td>13 months</td>
                                </tr>
                                <tr>
                                    <td class="code-name">NID</td>
                                    <td>Google — stores user preferences and personalises ads</td>
                                    <td>6 months</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Articles 1 to 11 -->
            <div class="article-section">
                <h3 class="article-heading">1. What Are Cookies?</h3>
                <p class="article-text">Cookies are small text files that are placed on your computer or mobile device when you visit a website. They are widely used to make websites work more efficiently, provide a better user experience, and give website owners information about how their site is being used. Cookies are not programs and cannot carry viruses or install malware on your device. Similar technologies such as web beacons, pixels, and local storage may also be used for the same purposes.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">2. Legal Basis Under GDPR</h3>
                <p class="article-text">Under the General Data Protection Regulation (EU) 2016/679 and the ePrivacy Directive (2002/58/EC as amended), we are required to obtain your prior, freely given, specific, informed, and unambiguous consent before placing non-essential cookies on your device. Strictly necessary cookies are exempt from this requirement under Recital 25 of the ePrivacy Directive. We record and store your consent choices in accordance with GDPR Article 7. You have the right to withdraw consent at any time, and withdrawal will not affect the lawfulness of processing based on consent before withdrawal.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">3. How We Use Cookies</h3>
                <p class="article-text">Falhen Media uses cookies and similar tracking technologies to: (a) operate and secure our website; (b) remember your preferences and settings; (c) analyse traffic patterns and improve site performance; (d) deliver relevant advertising where you have consented; and (e) enable third-party integrations such as embedded video content. We use both session cookies (which expire when you close your browser) and persistent cookies (which remain on your device for a set period or until you delete them).</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">4. Third-Party Cookies & International Transfers</h3>
                <p class="article-text">Some cookies on our website are set by third-party services, including Google Analytics (Google LLC, USA), Facebook Pixel (Meta Platforms, Inc., USA), LinkedIn Insight Tag (LinkedIn Corporation, USA), and YouTube (Google LLC, USA). These third parties may transfer data to servers in the United States and other countries. Where such transfers occur, we ensure appropriate safeguards are in place, including Standard Contractual Clauses (SCCs) approved by the European Commission. These third parties have their own privacy policies and cookie practices, which we encourage you to review. We do not control third-party cookies once set.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">5. Managing Your Cookie Preferences</h3>
                <p class="article-text">You can control and manage cookies in several ways: (a) Cookie Preference Centre — use our consent banner to accept or reject non-essential cookie categories at any time; (b) Browser Settings — most browsers allow you to refuse or accept cookies, delete existing cookies, and set preferences for certain websites; (c) Google Analytics Opt-Out — install the Google Analytics Opt-out Browser Add-on at tools.google.com/dlpage/gaoptout; (d) Advertising Opt-Out — opt out via the Digital Advertising Alliance (DAA) at optout.aboutads.info, the Network Advertising Initiative (NAI) at optout.networkadvertising.org, or the European Interactive Digital Advertising Alliance (EDAA) at youronlinechoices.eu. Please note that disabling certain cookies may affect the functionality of our website.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">6. Browser Settings</h3>
                <p class="article-text">You can set your browser to refuse all or some browser cookies, or to alert you when websites set or access cookies. If you disable or refuse cookies, please note that some parts of this website may become inaccessible or not function properly. For guidance on managing cookies in your specific browser, please visit: Chrome — support.google.com/chrome/answer/95647 | Firefox — support.mozilla.org/kb/cookies | Safari — support.apple.com/guide/safari | Edge — support.microsoft.com/microsoft-edge.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">7. Do Not Track (DNT)</h3>
                <p class="article-text">Some browsers include a "Do Not Track" (DNT) feature that signals to websites that you do not want to have your online activity tracked. Our website does not currently respond to DNT signals, as there is no legally recognised or industry-standard interpretation of how to respond to such signals. We will update this policy if a binding standard is established by applicable law or regulatory guidance.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">8. Retention of Cookie Data</h3>
                <p class="article-text">Cookie data is retained for the duration specified in the cookie table above. Analytics data collected via Google Analytics is retained for 26 months by default. You may request deletion of any personal data associated with cookies by contacting privacy@falhen.com. Please see our Privacy Policy for full details of our data retention practices.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">9. Your Rights</h3>
                <p class="article-text">Under GDPR and applicable US privacy laws, you have the right to: access personal data collected via cookies; withdraw consent at any time; request deletion of cookie-related personal data; object to processing based on legitimate interests; and lodge a complaint with your supervisory authority. To exercise these rights, please contact privacy@falhen.com. For California residents, please see our Privacy Policy for your CCPA/CPRA rights.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">10. Updates to This Policy</h3>
                <p class="article-text">We may update this Cookie Policy from time to time to reflect changes in technology, regulation (including updates to GDPR guidance from the European Data Protection Board), or our business practices. When we make material changes, we will update the "Last updated" date at the top of this page and, where required, re-obtain your consent. We encourage you to review this policy periodically.</p>
            </div>

            <div class="article-section">
                <h3 class="article-heading">11. Contact Us</h3>
                <p class="article-text">If you have any questions about our use of cookies or this Cookie Policy, or to exercise your data subject rights, please contact our Privacy Team at: <strong>privacy@falhen.com</strong> | <strong>hello@falhen.com</strong>.</p>
            </div>

            <!-- Policy Footer Bar -->
            <div class="policy-footer-bar">
                <div style="display: flex; gap: 20px;">
                    <a href="/privacy.php"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a>
                    <a href="/terms.php"><i class="fa-solid fa-file-contract"></i> Terms of Service</a>
                </div>
                <a href="/"><i class="fa-solid fa-house"></i> Back to Home</a>
            </div>

        </div>
    </main>

    <script>
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', () => {
                const card = header.closest('.category-card');
                card.classList.toggle('open');
                const arrow = header.querySelector('.accordion-arrow');
                if (arrow) {
                    if (card.classList.contains('open')) {
                        arrow.classList.remove('fa-chevron-down');
                        arrow.classList.add('fa-chevron-up');
                    } else {
                        arrow.classList.remove('fa-chevron-up');
                        arrow.classList.add('fa-chevron-down');
                    }
                }
            });
        });
    </script>
</body>
</html>
