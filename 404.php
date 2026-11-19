<?php
/**
 * 404 Not Found Page
 * Brand: Falhen Productions
 */

http_response_code(404);

require_once __DIR__ . '/includes/seo_helper.php';

$seo = getSeoConfig('404', [
    'title'       => 'Page Not Found (404)',
    'description' => 'The page you are looking for has been moved or does not exist. Explore video production services and portfolio by Falhen Productions.',
    'robots'      => 'noindex, follow'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    
    <?php renderSeoHeadTags($seo); ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        body {
            background-color: #09090b;
            color: #fafafa;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .error-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 24px;
            position: relative;
            overflow: hidden;
        }
        .error-card {
            max-width: 640px;
            width: 100%;
            background: rgba(24, 24, 27, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 56px 36px;
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 2;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
        }
        .error-code {
            font-size: clamp(4.5rem, 12vw, 8rem);
            font-weight: 900;
            line-height: 1;
            margin: 0 0 16px 0;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 50%, #7f1d1d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 800;
            margin-bottom: 16px;
            color: #ffffff;
        }
        .error-desc {
            font-size: 1.05rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin-bottom: 36px;
        }
        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
        }
        .btn-primary-glow {
            background: #ef4444;
            color: #ffffff;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary-glow:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 28px rgba(239, 68, 68, 0.6);
            color: #ffffff;
        }
        .btn-secondary-ghost {
            background: rgba(255, 255, 255, 0.05);
            color: #e4e4e7;
            font-weight: 600;
            padding: 14px 26px;
            border-radius: 9999px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-secondary-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <header style="padding: 24px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
        <a href="/" aria-label="Falhen Productions Home">
            <img src="/assets/img/icons/logo.png" alt="Falhen Productions Logo" style="height: 44px; width: auto;">
        </a>
    </header>

    <main class="error-stage">
        <div class="error-card">
            <div class="error-code">404</div>
            <h1 class="error-title">Frame Not Found</h1>
            <p class="error-desc">
                The visual story you're looking for was either moved, renamed, or never made the final cut. 
                Let's get you back on track to exploring our work.
            </p>
            <div class="error-actions">
                <a href="/" class="btn-primary-glow">
                    <i class="fa-solid fa-house"></i> Back to Home
                </a>
                <a href="/portfolio" class="btn-secondary-ghost">
                    <i class="fa-solid fa-film"></i> View Portfolio
                </a>
                <a href="/services" class="btn-secondary-ghost">
                    <i class="fa-solid fa-briefcase"></i> Explore Services
                </a>
                <a href="/contact" class="btn-secondary-ghost">
                    <i class="fa-solid fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </main>

    <footer style="padding: 24px; text-align: center; color: #71717a; font-size: 0.9rem; border-top: 1px solid rgba(255, 255, 255, 0.06);">
        &copy; <?php echo date('Y'); ?> Falhen Productions. All rights reserved.
    </footer>

</body>
</html>
