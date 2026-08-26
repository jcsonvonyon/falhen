<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
require_once __DIR__ . '/functions.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Falhen Media' : 'Falhen Video Production | Visual Storytelling'; ?></title>
    <meta name="description" content="Falhen is a full-service media production company specializing in video production, post-production, live streaming, animation, wedding coverage & content strategy.">
    <meta name="keywords" content="video production, photography, concert production, live streaming, animation motion graphics, wedding videography, creative services">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Falhen Media' : 'Falhen Media | Video Production Agency'; ?>">
    <meta property="og:description" content="Award-winning visual storytelling for brands worldwide.">
    <meta property="og:type" content="website">
    
    <!-- Google Fonts & Font Awesome 6 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">
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
                    <a href="/" class="nav-link <?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-house"></i></span>
                        <span class="nav-text">Home</span>
                        <?php if ($currentPage == 'index.php' || $currentPage == ''): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- Services Mega Dropdown -->
                <li class="nav-item dropdown">
                    <a href="/services.php" class="nav-link <?php echo ($currentPage == 'services.php') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-briefcase"></i></span>
                        <span class="nav-text">Services</span>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                        <?php if ($currentPage == 'services.php'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Mobile Services Accordion Sub-List -->
                    <ul class="mobile-sub-menu">
                        <li>
                            <a href="/services.php" class="mobile-sub-link" style="font-weight: 700; color: #ef4444;">
                                <i class="fa-solid fa-list-check mobile-sub-icon" style="color: #ef4444;"></i>
                                <span>All Services Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#video-production" class="mobile-sub-link">
                                <i class="fa-solid fa-play mobile-sub-icon"></i>
                                <span>Video Production</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#live-streaming" class="mobile-sub-link">
                                <i class="fa-solid fa-video mobile-sub-icon"></i>
                                <span>Live Streaming</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#post-production" class="mobile-sub-link">
                                <i class="fa-solid fa-pen mobile-sub-icon"></i>
                                <span>Post Production</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#animation" class="mobile-sub-link">
                                <i class="fa-solid fa-star mobile-sub-icon"></i>
                                <span>Animation & Motion Graphics</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#content-strategy" class="mobile-sub-link">
                                <i class="fa-solid fa-newspaper mobile-sub-icon"></i>
                                <span>Content Strategy</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#photography" class="mobile-sub-link">
                                <i class="fa-solid fa-camera mobile-sub-icon"></i>
                                <span>Photography</span>
                            </a>
                        </li>
                        <li>
                            <a href="/services.php#weddings" class="mobile-sub-link">
                                <i class="fa-solid fa-heart mobile-sub-icon"></i>
                                <span>Wedding & Events</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mega-dropdown-menu">
                        <div class="mega-dropdown-grid">
                            <!-- Left Column -->
                            <div class="mega-dropdown-col">
                                <a href="/services.php#video-production" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-play"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Video Production</div>
                                        <div class="mega-item-subtitle">Cinematic storytelling & commercials</div>
                                    </div>
                                </a>

                                <a href="/services.php#post-production" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-pen"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Post Production</div>
                                        <div class="mega-item-subtitle">Editing, grading & sound design</div>
                                    </div>
                                </a>

                                <a href="/services.php#content-strategy" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-newspaper"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Content Strategy</div>
                                        <div class="mega-item-subtitle">Brand storytelling & social media</div>
                                    </div>
                                </a>

                                <a href="/services.php#weddings" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-heart"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Wedding & Events</div>
                                        <div class="mega-item-subtitle">Cinematic films & full event coverage</div>
                                    </div>
                                </a>
                            </div>

                            <!-- Right Column -->
                            <div class="mega-dropdown-col">
                                <a href="/services.php#live-streaming" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-video"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Live Streaming</div>
                                        <div class="mega-item-subtitle">Multi-platform live broadcasts</div>
                                    </div>
                                </a>

                                <a href="/services.php#animation" class="mega-dropdown-item">
                                    <div class="mega-icon-box"><i class="fa-solid fa-star"></i></div>
                                    <div class="mega-item-text">
                                        <div class="mega-item-title">Animation & Motion Graphics</div>
                                        <div class="mega-item-subtitle">2D/3D animation & explainer videos</div>
                                    </div>
                                </a>

                                <a href="/services.php#photography" class="mega-dropdown-item">
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
                            <a href="/services.php" class="mega-footer-link">View all services <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="/portfolio.php" class="nav-link <?php echo ($currentPage == 'portfolio.php') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-images"></i></span>
                        <span class="nav-text">Portfolio</span>
                        <?php if ($currentPage == 'portfolio.php'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/blog.php" class="nav-link <?php echo ($currentPage == 'blog.php') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-newspaper"></i></span>
                        <span class="nav-text">Blog</span>
                        <?php if ($currentPage == 'blog.php'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/about.php" class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-user"></i></span>
                        <span class="nav-text">About</span>
                        <?php if ($currentPage == 'about.php'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">
                        <span class="mobile-nav-icon"><i class="fa-solid fa-envelope"></i></span>
                        <span class="nav-text">Contact</span>
                        <?php if ($currentPage == 'contact.php'): ?>
                            <span class="mobile-nav-badge"><i class="fa-solid fa-circle-check"></i></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- Mobile Drawer Bottom CTA -->
            <div class="mobile-menu-cta">
                <a href="/contact.php" class="btn btn-mobile-cta">
                    <i class="fa-solid fa-envelope"></i> Contact Us
                </a>
            </div>
        </nav>
        
        <!-- Header Right CTA Button -->
        <div class="nav-right">
            <a href="/contact.php" class="btn btn-contact-nav">
                <i class="fa-solid fa-envelope"></i> Contact Us
            </a>
        </div>
        
        <div class="nav-toggle" id="navToggle">
            <i class="fa-solid fa-bars"></i>
        </div>
    </div>
</header>
