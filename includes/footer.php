<!-- Lightbox Video Player Modal -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <span class="video-modal-close" id="modalClose">&times;</span>
        <iframe id="modalIframe" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
    </div>
</div>

<!-- Red Top Accent Line -->
<div class="footer-top-accent-line"></div>

<!-- Sleek Modern Horizontal Footer Bar (Matched to Reference Design) -->
<footer class="footer">
    <div class="container footer-container">
        <!-- Left: Logo & Social Buttons Group -->
        <div class="footer-left-group">
            <a href="/" class="footer-logo" aria-label="Falhen Home">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo" class="footer-logo-img">
            </a>
            <div class="footer-social-boxes">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>

        <!-- Center: Horizontal Main Nav -->
        <nav class="footer-center-nav" aria-label="Footer Navigation">
            <a href="/about">About</a>
            <a href="/services">Services</a>
            <a href="/portfolio">Portfolio</a>
            <a href="/blog">Blog</a>
            <a href="/contact">Contact</a>
        </nav>

        <!-- Right: Copyright & Legal / Admin Links -->
        <div class="footer-right-block">
            <div class="footer-copy-text">&copy; <?php echo date('Y'); ?> Falhen Media. All rights reserved.</div>
            <div class="footer-legal-links">
                <a href="/privacy">Privacy</a>
                <a href="/terms">Terms</a>
                <a href="/cookies" data-open-cookies="true" class="cookie-settings-link">Cookies</a>
                <a href="/dpa">DPA</a>
                <a href="/contact">Unsubscribe</a>
                <a href="/dashboard" class="dashboard-link">Dashboard</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll To Top Button -->
<button id="scrollTopBtn" class="scroll-top-btn" aria-label="Scroll to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- Fixed Mobile Floating Bottom Dock Navigation Widget -->
<?php
$footerSlug = isset($currentSlug) ? $currentSlug : preg_replace('/\.php$/', '', basename($_SERVER['PHP_SELF']));
?>
<nav class="mobile-bottom-dock">
    <a href="/" class="dock-item <?php echo ($footerSlug == 'index' || $footerSlug == '') ? 'active' : ''; ?>">
        <div class="dock-icon-box"><i class="fa-solid fa-house"></i></div>
        <span class="dock-label">HOME</span>
    </a>
    
    <a href="/services" class="dock-item <?php echo ($footerSlug == 'services' || $footerSlug == 'service-single') ? 'active' : ''; ?>">
        <div class="dock-icon-box"><i class="fa-solid fa-briefcase"></i></div>
        <span class="dock-label">SERVICES</span>
    </a>
    
    <a href="/portfolio" class="dock-item <?php echo ($footerSlug == 'portfolio' || str_starts_with($footerSlug, 'portfolio-')) ? 'active' : ''; ?>">
        <div class="dock-icon-box"><i class="fa-solid fa-film"></i></div>
        <span class="dock-label">WORK</span>
    </a>
    
    <a href="/contact" class="dock-item <?php echo ($footerSlug == 'contact') ? 'active' : ''; ?>">
        <div class="dock-icon-box"><i class="fa-solid fa-envelope"></i></div>
        <span class="dock-label">CONTACT</span>
    </a>
    
    <button type="button" class="dock-item dock-top-btn" id="dockScrollTop">
        <div class="dock-icon-box"><i class="fa-solid fa-arrow-up"></i></div>
        <span class="dock-label">TOP</span>
    </button>
</nav>

<?php require_once __DIR__ . '/cookie-banner.php'; ?>

<script src="/assets/js/main.js"></script>
</body>
</html>
