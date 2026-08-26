<?php
// blog-single.php - Blog Article Detail View matching falhen.com/blog/behind-scenes-award-winning-commercial
require_once __DIR__ . '/includes/functions.php';

$blogRepo = getBlogRepo();
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$article = getBlogPostById($articleId);
if (!$article && !empty($blogRepo)) {
    $article = reset($blogRepo);
}

$pageTitle = ($article ? $article['title'] : 'Article') . " — Falhen Media Blog";

$relatedArticles = array_filter($blogRepo, function($a) use ($article) {
    return $article && (int)$a['id'] !== (int)$article['id'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
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

        /* Top Sub-Nav Bar */
        .article-nav-bar {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .article-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .article-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .article-back-link:hover {
            color: #ffffff;
        }

        .article-logo-center img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .article-nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .read-time-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
        }

        .cat-pill-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 50px;
        }

        /* Hero Stage Backdrop */
        .article-hero-backdrop {
            position: relative;
            width: 100%;
            height: 480px;
            overflow: hidden;
            background: #0e0e12;
        }

        .article-hero-backdrop img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.4;
            filter: blur(2px);
        }

        .article-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.4) 0%, rgba(3, 3, 5, 0.95) 85%, #030305 100%);
            display: flex;
            align-items: flex-end;
            padding-bottom: 40px;
        }

        .article-hero-inner {
            max-width: 820px;
            margin: 0 auto;
            padding: 0 24px;
            width: 100%;
        }

        .article-hero-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            color: #a1a1aa;
        }

        .article-title-main {
            font-size: 3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 24px 0;
            line-height: 1.15;
            letter-spacing: -1px;
        }

        .article-author-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .author-box-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.25);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .social-share-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .share-icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .share-icon-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* Article Content Section */
        .article-body-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 24px 70px 24px;
        }

        .article-lead-box {
            font-size: 1.15rem;
            font-style: italic;
            color: #e4e4e7;
            line-height: 1.7;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .article-body-content p {
            font-size: 1.05rem;
            color: #d4d4d8;
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .article-body-content h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 42px 0 18px 0;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }

        .article-body-content h2,
        .article-body-content h3 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin: 36px 0 14px 0;
            letter-spacing: -0.4px;
            line-height: 1.35;
        }

        .article-body-content blockquote {
            background: linear-gradient(135deg, rgba(40, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border-left: 4px solid #dc2626;
            border-radius: 12px;
            padding: 22px 28px;
            margin: 32px 0;
            font-size: 1.1rem;
            font-style: italic;
            color: #ffffff;
            line-height: 1.65;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* Custom Red Checkmark Bullet & Numbered Lists */
        .article-body-content ul {
            list-style: none;
            padding-left: 0;
            margin: 28px 0;
        }

        .article-body-content ul li {
            position: relative;
            padding-left: 32px;
            margin-bottom: 14px;
            font-size: 1.05rem;
            color: #d4d4d8;
            line-height: 1.6;
        }

        .article-body-content ul li::before {
            content: "\f058"; /* FontAwesome fa-circle-check */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 2px;
            color: #ef4444;
            font-size: 1.15rem;
            filter: drop-shadow(0 2px 8px rgba(239, 68, 68, 0.45));
        }

        .article-body-content ol {
            padding-left: 24px;
            margin: 28px 0;
            color: #ef4444;
        }

        .article-body-content ol li {
            margin-bottom: 14px;
            font-size: 1.05rem;
            color: #d4d4d8;
            line-height: 1.6;
        }

        .article-body-content ol li::marker {
            font-weight: 800;
            color: #ef4444;
        }

        .article-quote-box {
            background: linear-gradient(135deg, rgba(40, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border-left: 3px solid #dc2626;
            border-radius: 12px;
            padding: 24px 30px;
            margin: 35px 0;
            font-size: 1.1rem;
            font-style: italic;
            color: #ffffff;
            line-height: 1.65;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .article-specs-list {
            list-style: none;
            padding: 0;
            margin: 25px 0;
        }

        .article-specs-list li {
            font-size: 1.02rem;
            color: #d4d4d8;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.6;
        }

        .article-specs-list li i {
            color: #ef4444;
            font-size: 1.1rem;
            margin-top: 3px;
        }

        /* Tags & Share Footer Row */
        .tags-share-footer {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .tags-left-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tag-pill-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 50px;
        }

        /* Discovery Callout Banner */
        .discovery-cta-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.95) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 18px;
            padding: 36px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        }

        .discovery-cta-card h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 8px 0;
        }

        .discovery-cta-card p {
            color: #a1a1aa;
            font-size: 0.95rem;
            margin: 0 0 24px 0;
        }

        .btn-discovery-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 800;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.5);
        }

        .btn-discovery-red:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Related Articles Section */
        .related-articles-section {
            max-width: 1240px;
            margin: 70px auto 40px auto;
            padding: 0 24px;
        }

        .related-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .related-header-row h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }

        .view-all-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.25s ease;
        }

        .view-all-link:hover {
            color: #ffffff;
        }

        .related-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .blog-card-item {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .blog-card-item:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
        }

        .blog-card-thumb {
            width: 100%;
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .blog-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.10);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .blog-card-item:hover .blog-card-thumb img {
            transform: scale(1.0);
        }

        .blog-card-content {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
        }

        .blog-card-title {
            color: #ffffff;
            line-height: 1.4;
            transition: color 0.25s ease;
        }

        .blog-card-item:hover .blog-card-title {
            color: #ef4444;
        }

        /* Bottom Nav & Footer Bar */
        .article-bottom-bar {
            max-width: 1240px;
            margin: 0 auto;
            padding: 30px 24px 50px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.86rem;
            color: #71717a;
        }

        .blog-footer-links {
            display: flex;
            gap: 20px;
        }

        .blog-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .blog-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .article-title-main { font-size: 2.2rem; }
            .related-grid-3 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .related-grid-3 { grid-template-columns: 1fr; }
            .article-title-main { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <!-- Top Sub-Nav Bar -->
    <header class="article-nav-bar">
        <div class="article-nav-container">
            <a href="/blog.php" class="article-back-link">
                <i class="fa-solid fa-arrow-left"></i> All Articles
            </a>
            <a href="/" class="article-logo-center">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
            <div class="article-nav-right">
                <span class="read-time-pill"><?php echo htmlspecialchars($article['read_time']); ?></span>
                <span class="cat-pill-red"><?php echo htmlspecialchars($article['category']); ?></span>
            </div>
        </div>
    </header>

    <!-- Hero Backdrop Header -->
    <section class="article-hero-backdrop">
        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
        <div class="article-hero-overlay">
            <div class="article-hero-inner">
                <div class="article-hero-meta">
                    <span class="cat-pill-red" style="padding:3px 12px; font-size:0.76rem;"><?php echo htmlspecialchars($article['category']); ?></span>
                    <span><?php echo htmlspecialchars($article['date']); ?></span>
                    <span>&bull;</span>
                    <span><?php echo htmlspecialchars($article['read_time']); ?></span>
                </div>

                <h1 class="article-title-main"><?php echo htmlspecialchars($article['title']); ?></h1>

                <div class="article-author-bar">
                    <div class="author-box-left">
                        <div class="author-avatar-badge"><i class="fa-regular fa-user"></i></div>
                        <div>
                            <h4 style="margin:0; font-size:0.92rem; font-weight:700; color:#fff;"><?php echo htmlspecialchars($article['author']); ?></h4>
                            <span style="font-size:0.78rem; color:#71717a;"><?php echo htmlspecialchars($article['role']); ?></span>
                        </div>
                    </div>
                    <div class="social-share-group">
                        <a href="#" class="share-icon-btn" title="Share on X" onclick="event.preventDefault(); shareArticle('twitter');">𝕏</a>
                        <a href="#" class="share-icon-btn" title="Share on LinkedIn" onclick="event.preventDefault(); shareArticle('linkedin');">in</a>
                        <a href="#" class="share-icon-btn" title="Copy Link" onclick="event.preventDefault(); shareArticle('copy');"><i class="fa-solid fa-link"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Body -->
    <main class="article-body-wrapper">
        
        <?php if (!empty($article['lead'])): ?>
            <div class="article-lead-box">
                <?php echo htmlspecialchars($article['lead']); ?>
            </div>
        <?php endif; ?>

        <div class="article-body-content">
            <?php echo $article['content']; ?>
        </div>

        <!-- Tags & Share Row -->
        <div class="tags-share-footer">
            <div class="tags-left-group">
                <span style="font-size:0.85rem; color:#71717a; font-weight:600;">Tags:</span>
                <?php 
                $articleTags = (!empty($article['tags']) && is_array($article['tags']))
                    ? $article['tags'] 
                    : (!empty($article['category']) ? [$article['category'], 'Falhen Media', 'Production'] : ['Falhen Media']);
                foreach ($articleTags as $tag): 
                ?>
                    <span class="tag-pill-item"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="social-share-group">
                <a href="#" class="share-icon-btn" onclick="event.preventDefault(); shareArticle('twitter');">𝕏</a>
                <a href="#" class="share-icon-btn" onclick="event.preventDefault(); shareArticle('linkedin');">in</a>
                <a href="#" class="share-icon-btn" onclick="event.preventDefault(); shareArticle('copy');"><i class="fa-solid fa-link"></i></a>
            </div>
        </div>

        <!-- Discovery Callout Card -->
        <div class="discovery-cta-card">
            <h3>Ready to create something great?</h3>
            <p>Book a free discovery call with the Falhen Media team.</p>
            <a href="/index.php#lets-talk" class="btn-discovery-red">Book a Discovery Call</a>
        </div>

    </main>

    <!-- Related Articles -->
    <?php if (!empty($relatedArticles)): ?>
        <section class="related-articles-section">
            <div class="related-header-row">
                <h3>Related Articles</h3>
                <a href="/blog.php" class="view-all-link">All articles &rarr;</a>
            </div>

            <div class="related-grid-3">
                <?php foreach (array_slice($relatedArticles, 0, 3) as $rel): ?>
                    <a href="/blog-single.php?id=<?php echo $rel['id']; ?>" class="blog-card-item" style="text-decoration:none;">
                        <div class="blog-card-thumb" style="height:180px;">
                            <img src="<?php echo htmlspecialchars($rel['image']); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>">
                        </div>
                        <div class="blog-card-content">
                            <div>
                                <span class="post-cat-badge" style="font-size:0.72rem; padding:2px 10px;"><?php echo htmlspecialchars($rel['category']); ?></span>
                                <h4 class="blog-card-title" style="font-size:0.98rem; margin:10px 0 6px 0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                            </div>
                            <div style="font-size:0.78rem; color:#71717a; margin-top:12px;">
                                <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($rel['read_time']); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Bottom Nav & Footer Bar -->
    <div class="article-bottom-bar">
        <a href="/blog.php" class="blog-pill-btn" style="text-decoration:none;">
            <i class="fa-solid fa-arrow-left"></i> All Articles
        </a>

        <div>&copy; 2026 Falhen Media. All rights reserved.</div>

        <div class="blog-footer-links">
            <a href="/">Home</a>
            <a href="/blog.php">Blog</a>
            <a href="/portfolio.php">Portfolio</a>
            <a href="/about.php">About</a>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function shareArticle(type) {
            if (type === 'copy') {
                navigator.clipboard.writeText(window.location.href);
                alert('Article link copied to clipboard!');
            } else if (type === 'twitter') {
                window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(document.title)}&url=${encodeURIComponent(window.location.href)}`, '_blank');
            } else if (type === 'linkedin') {
                window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`, '_blank');
            }
    </script>

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
