<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$currentSlug = preg_replace('/\.php$/', '', $currentPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LJ91C0YXCH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-LJ91C0YXCH');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Falhen Media' : 'Falhen Video Production | Visual Storytelling'; ?></title>
    <meta name="description" content="Falhen is a full-service media production company specializing in video production, post-production, live streaming, animation, wedding coverage & content strategy.">
    <meta name="keywords" content="video production, photography, concert production, live streaming, animation motion graphics, wedding videography, creative services">
    <meta name="author" content="Falhen Media">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.falhen.com<?php echo htmlspecialchars(explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0]); ?>">

    <!-- Open Graph Metadata -->
    <?php
    $ogImg = !empty($heroMedia['poster_desktop']) ? $heroMedia['poster_desktop'] : 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto,w_1200,h_630,c_fill/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg';
    $ogTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Falhen Media' : 'Falhen Video Production | Visual Storytelling';
    $ogDesc = 'Award-winning visual storytelling and full-service media production for brands worldwide.';
    ?>
    <meta property="og:site_name" content="Falhen Media">
    <meta property="og:title" content="<?php echo $ogTitle; ?>">
    <meta property="og:description" content="<?php echo $ogDesc; ?>">
    <meta property="og:url" content="https://www.falhen.com<?php echo htmlspecialchars(explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0]); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImg); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card Metadata -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@falhenmedia">
    <meta name="twitter:title" content="<?php echo $ogTitle; ?>">
    <meta name="twitter:description" content="<?php echo $ogDesc; ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImg); ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "VideoProductionService",
          "@id": "https://www.falhen.com/#service",
          "name": "Falhen Media",
          "url": "https://www.falhen.com",
          "logo": "https://www.falhen.com/assets/img/icons/logo.png",
          "image": "https://res.cloudinary.com/pnabfi91/image/upload/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg",
          "description": "Full-service media production company specializing in commercial video, post-production, live streaming, event cinema & visual storytelling.",
          "telephone": "+1-312-555-0199",
          "email": "info@falhen.com",
          "areaServed": "Global"
        },
        {
          "@type": "WebSite",
          "@id": "https://www.falhen.com/#website",
          "url": "https://www.falhen.com",
          "name": "Falhen Media"
        }
      ]
    }
    </script>
    
    <!-- Resource Hints & Preloads for Instant Mobile/Slow-Network Paint -->
    <link rel="preconnect" href="https://res.cloudinary.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <?php if (!empty($heroMedia) && is_array($heroMedia)): ?>
        <?php if (($heroMedia['iframe_provider'] ?? '') === 'youtube'): ?>
            <link rel="preconnect" href="https://www.youtube-nocookie.com" crossorigin>
        <?php elseif (($heroMedia['iframe_provider'] ?? '') === 'vimeo'): ?>
            <link rel="preconnect" href="https://player.vimeo.com" crossorigin>
        <?php endif; ?>
        <link rel="preload" as="image" href="<?php echo htmlspecialchars($heroMedia['poster_mobile']); ?>" media="(max-width: 768px)" fetchpriority="high">
        <link rel="preload" as="image" href="<?php echo htmlspecialchars($heroMedia['poster_desktop']); ?>" media="(min-width: 769px)" fetchpriority="high">
    <?php endif; ?>

    <!-- Google Fonts & Icon Libraries -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : '2.2'; ?>">
</head>
<body>

<!-- Header Navigation -->
<header class="header">
    <div class="container nav-container">
        <!-- Logo -->
        <a href="/" class="logo-mark" aria-label="Falhen Home">
            <img src="/assets/img/icons/logo.png" alt="Falhen Logo" class="logo-img" style="height: 48px; width: auto; object-fit: contain;">
        </a>
        
        <!-- Centered Navigation -->
        <nav class="nav-center">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/" class="nav-link <?php echo ($currentSlug == 'index' || $currentSlug == '') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-house"></i></span>
                        <span class="nav-text">Home</span>
                        <?php if ($currentSlug == 'index' || $currentSlug == ''): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- Services Mega Dropdown -->
                <li class="nav-item dropdown">
                    <a href="/services" class="nav-link <?php echo ($currentSlug == 'services' || $currentSlug == 'service-single') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-briefcase"></i></span>
                        <span class="nav-text">Services</span>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                        <?php if ($currentSlug == 'services' || $currentSlug == 'service-single'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Mobile Services Accordion Sub-List -->
                    <ul class="mobile-sub-menu">
                        <li>
                            <a href="/services" class="mobile-sub-link" style="font-weight: 700; color: #ef4444;">
                                <i class="fa-solid fa-list-check mobile-sub-icon" style="color: #ef4444;"></i>
                                <span>All Services Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#video-production" class="mobile-sub-link">
                                <i class="fa-solid fa-play mobile-sub-icon"></i>
                                <span>Video Production</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#live-streaming" class="mobile-sub-link">
                                <i class="fa-solid fa-video mobile-sub-icon"></i>
                                <span>Live Streaming</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#post-production" class="mobile-sub-link">
                                <i class="fa-solid fa-pen mobile-sub-icon"></i>
                                <span>Post Production</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#animation" class="mobile-sub-link">
                                <i class="fa-solid fa-star mobile-sub-icon"></i>
                                <span>Animation & Motion Graphics</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#content-strategy" class="mobile-sub-link">
                                <i class="fa-solid fa-newspaper mobile-sub-icon"></i>
                                <span>Content Strategy</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#photography" class="mobile-sub-link">
                                <i class="fa-solid fa-camera mobile-sub-icon"></i>
                                <span>Photography</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services#weddings" class="mobile-sub-link">
                                <i class="fa-solid fa-heart mobile-sub-icon"></i>
                                <span>Wedding & Events</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mega-dropdown-menu">
                        <div class="mega-dropdown-grid">
                            <!-- Left Column -->
                            <div class="mega-dropdown-col">
                                <a href="/services#video-production" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-play"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Video Production</div>
                                        <div class="mega-item-subtitle">Cinematic storytelling & commercials</div>
                                    </div>
                                </a>

                                <a href="/services#post-production" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-pen"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Post Production</div>
                                        <div class="mega-item-subtitle">Editing, grading & sound design</div>
                                    </div>
                                </a>

                                <a href="/services#content-strategy" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-newspaper"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Content Strategy</div>
                                        <div class="mega-item-subtitle">Brand storytelling & social media</div>
                                    </div>
                                </a>

                                <a href="/services#weddings" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-heart"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Wedding & Events</div>
                                        <div class="mega-item-subtitle">Cinematic films & full event coverage</div>
                                    </div>
                                </a>
                            </div>

                            <!-- Right Column -->
                            <div class="mega-dropdown-col">
                                <a href="/services#live-streaming" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-video"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Live Streaming</div>
                                        <div class="mega-item-subtitle">Multi-platform live broadcasts</div>
                                    </div>
                                </a>

                                <a href="/services#animation" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-star"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Animation & Motion Graphics</div>
                                        <div class="mega-item-subtitle">2D/3D animation & explainer videos</div>
                                    </div>
                                </a>

                                <a href="/services#photography" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-camera"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Photography</div>
                                        <div class="mega-item-subtitle">Events, products & corporate shoots</div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Mega Dropdown Footer -->
                        <div class="mega-dropdown-footer">
                            <span class="mega-footer-count">7 services available</span>
                            <a href="/services" class="mega-footer-link">View all services <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="/portfolio" class="nav-link <?php echo ($currentSlug == 'portfolio' || str_starts_with($currentSlug, 'portfolio-')) ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-images"></i></span>
                        <span class="nav-text">Portfolio</span>
                        <?php if ($currentSlug == 'portfolio' || str_starts_with($currentSlug, 'portfolio-')): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/blog" class="nav-link <?php echo ($currentSlug == 'blog' || $currentSlug == 'blog-single') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-newspaper"></i></span>
                        <span class="nav-text">Blog</span>
                        <?php if ($currentSlug == 'blog' || $currentSlug == 'blog-single'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/about" class="nav-link <?php echo ($currentSlug == 'about') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-user"></i></span>
                        <span class="nav-text">About</span>
                        <?php if ($currentSlug == 'about'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/contact" class="nav-link <?php echo ($currentSlug == 'contact') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-envelope"></i></span>
                        <span class="nav-text">Contact</span>
                        <?php if ($currentSlug == 'contact'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- Mobile Drawer Bottom CTA -->
            <div class="mobile-menu-cta">
                <a href="/contact" class="btn btn-mobile-cta">
                    <i class="fa-solid fa-envelope"></i> Contact Us
                </a>
            </div>
        </nav>
        
        <!-- Header Right CTA Button -->
        <div class="nav-right">
            <a href="/contact" class="btn btn-contact-nav">
                <i class="fa-solid fa-envelope"></i> Contact Us
            </a>
        </div>
        
        <div class="nav-toggle" id="navToggle">
            <i class="fa-solid fa-bars"></i>
        </div>
    </div>
</header>
