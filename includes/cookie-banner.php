<?php
// includes/cookie-banner.php - Cookie Consent Banner & Preference Center
?>
<!-- Cookie Consent Banner & Preferences Modal (Exact Replica of falhen.com screenshot) -->
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
