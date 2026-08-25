<!-- Lightbox Video Player Modal -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <span class="video-modal-close" id="modalClose">&times;</span>
        <iframe id="modalIframe" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
    </div>
</div>

<!-- Red Top Accent Line -->
<div class="footer-top-accent-line"></div>

<!-- Footer (Exact Replica of falhen.com screenshots) -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <a href="/" class="logo-mark">
                    <img src="/assets/img/icons/logo.png" alt="Falhen Logo" class="logo-img" style="height: 54px; width: auto; object-fit: contain;">
                </a>
                <p class="footer-tagline">Creating what the world watches</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            
            <!-- Company Links -->
            <div class="footer-col">
                <h4 class="footer-title">Company</h4>
                <ul class="footer-links">
                    <li><a href="/about.php">About Us</a></li>
                    <li><a href="/team.php">Our Team</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/careers.php">Careers</a></li>
                </ul>
            </div>

            <!-- Services Links -->
            <div class="footer-col">
                <h4 class="footer-title">Services</h4>
                <ul class="footer-links">
                    <li><a href="/service-single.php?slug=video-production">Video Production</a></li>
                    <li><a href="/service-single.php?slug=creative-services">Content Strategy</a></li>
                    <li><a href="/service-single.php?slug=live-streaming">Live Streaming</a></li>
                    <li><a href="/service-single.php?slug=post-production">Post Production</a></li>
                </ul>
            </div>
            
            <!-- Resources Links -->
            <div class="footer-col">
                <h4 class="footer-title">Resources</h4>
                <ul class="footer-links">
                    <li><a href="/blog.php">Blog</a></li>
                    <li><a href="/portfolio.php">Portfolio</a></li>
                    <li><a href="/blog.php">Case Studies</a></li>
                    <li><a href="/faq.php">FAQs</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom Bar -->
        <div class="footer-bottom">
            <div class="footer-copy">&copy; <?php echo date('Y'); ?> Falhen Media. All rights reserved.</div>
            <div class="footer-legal">
                <a href="/privacy.php">Privacy</a>
                <a href="/terms.php">Terms</a>
                <a href="/cookies.php">Cookies</a>
                <a href="/dpa.php">DPA</a>
                <a href="/contact.php">Unsubscribe</a>
                <a href="/admin/login.php" class="dashboard-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll To Top Button -->
<button id="scrollTopBtn" class="scroll-top-btn" aria-label="Scroll to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- Cookie Consent Banner & Preferences Modal (Replicated from falhen.com screenshot) -->
<div class="cookie-banner" id="cookieBanner">
    <!-- View 1: Default Banner View -->
    <div class="cookie-view active" id="cookieMainView">
        <div class="cookie-header">
            <div class="cookie-icon-box">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="cookie-title-group">
                <h4 class="cookie-title">We use cookies</h4>
                <p class="cookie-desc">To improve your experience and analyse site traffic. <a href="/cookies.php" class="cookie-link">Learn more</a></p>
            </div>
        </div>
        <div class="cookie-actions">
            <button type="button" class="btn-cookie-reject" id="rejectCookiesBtn">Reject All</button>
            <button type="button" class="btn-cookie-accept" id="acceptCookiesBtn">Accept All</button>
        </div>
        <a href="#" class="cookie-manage-link" id="manageCookiesBtn">Manage preferences</a>
    </div>

    <!-- View 2: Cookie Preferences Modal View -->
    <div class="cookie-view" id="cookiePrefsView">
        <div class="cookie-prefs-header">
            <h4 class="cookie-title">Cookie preferences</h4>
            <button type="button" class="cookie-back-btn" id="cookieBackBtn"><i class="fa-solid fa-chevron-left"></i> Back</button>
        </div>

        <div class="cookie-options-list">
            <!-- Option 1: Strictly Necessary -->
            <div class="cookie-option-item">
                <div class="cookie-option-info">
                    <strong class="cookie-option-name">Strictly Necessary</strong>
                    <p class="cookie-option-desc">Always active — required for the site to work.</p>
                </div>
                <span class="cookie-always-on">Always on</span>
            </div>

            <!-- Option 2: Analytics -->
            <div class="cookie-option-item">
                <div class="cookie-option-info">
                    <strong class="cookie-option-name">Analytics</strong>
                    <p class="cookie-option-desc">Google Analytics — pseudonymised traffic data.</p>
                </div>
                <label class="cookie-switch">
                    <input type="checkbox" id="cookieAnalyticsToggle" checked>
                    <span class="cookie-slider"></span>
                </label>
            </div>

            <!-- Option 3: Functional -->
            <div class="cookie-option-item">
                <div class="cookie-option-info">
                    <strong class="cookie-option-name">Functional</strong>
                    <p class="cookie-option-desc">Language & theme preferences.</p>
                </div>
                <label class="cookie-switch">
                    <input type="checkbox" id="cookieFunctionalToggle" checked>
                    <span class="cookie-slider"></span>
                </label>
            </div>

            <!-- Option 4: Marketing -->
            <div class="cookie-option-item">
                <div class="cookie-option-info">
                    <strong class="cookie-option-name">Marketing</strong>
                    <p class="cookie-option-desc">Facebook Pixel, LinkedIn & Google Ads.</p>
                </div>
                <label class="cookie-switch">
                    <input type="checkbox" id="cookieMarketingToggle">
                    <span class="cookie-slider"></span>
                </label>
            </div>
        </div>

        <button type="button" class="btn-cookie-save" id="saveCookiesBtn">Save preferences</button>
    </div>
</div>

<script src="/assets/js/main.js"></script>
</body>
</html>
