<?php
// blog.php - The Falhen Blog matching falhen.com/blog screenshots
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Blog — Falhen Media";

$blogPosts = getBlogRepo();
$blogCategories = getBlogCategories();

$filteredFeatured = array_filter($blogPosts, fn($p) => !empty($p['featured']));
$featuredPost = !empty($filteredFeatured) ? reset($filteredFeatured) : (!empty($blogPosts) ? reset($blogPosts) : null);
$gridPosts = array_filter($blogPosts, fn($p) => $featuredPost && $p['id'] !== $featuredPost['id']);
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
        .blog-nav-bar {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .blog-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .blog-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .blog-back-link:hover {
            color: #ffffff;
        }

        .blog-logo-center img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .blog-nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-manage-posts {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .btn-manage-posts:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .btn-blog-contact {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 22px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .btn-blog-contact:hover {
            background: #ef4444;
            transform: translateY(-1px);
        }

        /* Hero Header */
        .blog-hero-section {
            padding: 50px 24px 35px 24px;
            max-width: 1240px;
            margin: 0 auto;
        }

        .blog-hero-badge {
            display: inline-block;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #ef4444;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
            margin-bottom: 16px;
        }

        .blog-hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 12px 0;
            line-height: 1.1;
            letter-spacing: -1.5px;
        }

        .blog-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            margin: 0;
            max-width: 680px;
            line-height: 1.6;
        }

        /* Controls Row: Category Pills + Search Box */
        .blog-controls-row {
            max-width: 1240px;
            margin: 35px auto 40px auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .blog-pills-list {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .blog-pill-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #a1a1aa;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .blog-pill-btn:hover {
            border-color: rgba(255, 255, 255, 0.3);
            color: #ffffff;
        }

        .blog-pill-btn.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .blog-search-box {
            position: relative;
            min-width: 260px;
        }

        .blog-search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #71717a;
            font-size: 0.9rem;
        }

        .blog-search-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 9px 16px 9px 40px;
            color: #ffffff;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.25s ease;
            box-sizing: border-box;
        }

        .blog-search-input:focus {
            border-color: rgba(220, 38, 38, 0.6);
        }

        /* Main Container */
        .blog-main-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px 80px 24px;
        }

        /* Featured Highlight Hero Post Card */
        .featured-post-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 35% 65%;
            margin-bottom: 45px;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .featured-post-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
        }

        .featured-post-thumb {
            width: 100%;
            height: 380px;
            overflow: hidden;
            position: relative;
        }

        .featured-post-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.06);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .featured-post-card:hover .featured-post-thumb img {
            transform: scale(1.0);
        }

        .featured-post-body {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .post-meta-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.84rem;
            color: #71717a;
        }

        .post-cat-badge {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 50px;
        }

        .featured-post-title {
            font-size: 1.95rem;
            font-weight: 800;
            color: #ffffff;
            margin: 16px 0 12px 0;
            line-height: 1.25;
            letter-spacing: -0.5px;
            transition: color 0.25s ease;
        }

        .featured-post-card:hover .featured-post-title {
            color: #ef4444;
        }

        .featured-post-excerpt {
            font-size: 0.96rem;
            color: #a1a1aa;
            line-height: 1.6;
            margin: 0 0 24px 0;
        }

        .post-author-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .author-info-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .author-name-text {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .author-role-text {
            font-size: 0.78rem;
            color: #71717a;
            margin: 0;
        }

        .read-article-link {
            color: #ef4444;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.25s ease;
        }

        .featured-post-card:hover .read-article-link {
            transform: translateX(4px);
        }

        /* 3-Column Grid Layout */
        .blog-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
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
            height: 230px;
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
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .blog-card-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 14px 0 10px 0;
            line-height: 1.35;
            transition: color 0.25s ease;
        }

        .blog-card-item:hover .blog-card-title {
            color: #ef4444;
        }

        .blog-card-excerpt {
            font-size: 0.88rem;
            color: #a1a1aa;
            line-height: 1.55;
            margin: 0 0 20px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-card-footer {
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            color: #71717a;
        }

        .author-mini {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a1a1aa;
            font-weight: 600;
        }

        /* Post Reader Modal */
        .reader-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(14px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .reader-modal-backdrop.active {
            display: flex;
        }

        .reader-modal-box {
            max-width: 780px;
            width: 100%;
            max-height: 90vh;
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            overflow-y: auto;
            padding: 40px;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.95);
        }

        .reader-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.25s ease;
        }

        .reader-close-btn:hover {
            background: #dc2626;
        }

        .reader-header-cover {
            width: 100%;
            height: 320px;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .reader-header-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reader-body-text {
            font-size: 1.05rem;
            color: #d4d4d8;
            line-height: 1.8;
            margin-top: 20px;
        }

        /* Footer Bar */
        .blog-footer-bar {
            padding: 30px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            max-width: 1240px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            .featured-post-card {
                grid-template-columns: 1fr;
            }
            .featured-post-thumb {
                height: 260px;
            }
            .blog-posts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .blog-posts-grid {
                grid-template-columns: 1fr;
            }
            .blog-hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Top Sub-Nav Bar -->
    <header class="blog-nav-bar">
        <div class="blog-nav-container">
            <a href="/index.php" class="blog-back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
            <a href="/" class="blog-logo-center">
                <img src="/assets/img/icons/logo.png" alt="Falhen Logo">
            </a>
            <div class="blog-nav-right">
                <a href="/admin/index.php?section=blog" class="btn-manage-posts" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-regular fa-pen-to-square"></i> Manage Posts
                </a>
                <a href="/contact.php" class="btn-blog-contact">
                    Contact Us
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Header -->
    <section class="blog-hero-section">
        <span class="blog-hero-badge">Insights & Ideas</span>
        <h1 class="blog-hero-title">The Falhen Blog</h1>
        <p class="blog-hero-subtitle">Behind the lens, beyond the frame &mdash; production insights, strategy guides, and creative inspiration.</p>
    </section>

    <!-- Category Pills & Search Row -->
    <div class="blog-controls-row">
        <div class="blog-pills-list">
            <button class="blog-pill-btn active" data-cat="all" onclick="filterCategory('all', this)">All</button>
            <?php foreach ($blogCategories as $bCat): ?>
                <button class="blog-pill-btn" data-cat="<?php echo htmlspecialchars($bCat); ?>" onclick="filterCategory('<?php echo htmlspecialchars($bCat); ?>', this)"><?php echo htmlspecialchars($bCat); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="blog-search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="blog-search-input" id="blogSearchInput" placeholder="Search..." onkeyup="searchPosts()">
        </div>
    </div>

    <!-- Main Container -->
    <main class="blog-main-container">

        <!-- Featured Highlight Card (Top) -->
        <div class="featured-post-card" data-cat="<?php echo htmlspecialchars($featuredPost['category']); ?>" onclick="window.location.href='/blog-single.php?id=<?php echo $featuredPost['id']; ?>'">
            <div class="featured-post-thumb">
                <img src="<?php echo htmlspecialchars($featuredPost['image']); ?>" alt="<?php echo htmlspecialchars($featuredPost['title']); ?>">
            </div>
            <div class="featured-post-body">
                <div>
                    <div class="post-meta-row">
                        <span class="post-cat-badge"><?php echo htmlspecialchars($featuredPost['category']); ?></span>
                        <span>&bull;</span>
                        <span><?php echo htmlspecialchars($featuredPost['date']); ?></span>
                        <span>&bull;</span>
                        <span><?php echo htmlspecialchars($featuredPost['read_time']); ?></span>
                    </div>
                    <h2 class="featured-post-title"><?php echo htmlspecialchars($featuredPost['title']); ?></h2>
                    <p class="featured-post-excerpt"><?php echo htmlspecialchars($featuredPost['excerpt']); ?></p>
                </div>

                <div class="post-author-row">
                    <div class="author-info-left">
                        <div class="author-avatar-circle">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <div>
                            <h4 class="author-name-text"><?php echo htmlspecialchars($featuredPost['author']); ?></h4>
                            <p class="author-role-text"><?php echo htmlspecialchars($featuredPost['role']); ?></p>
                        </div>
                    </div>
                    <span class="read-article-link">Read article &rarr;</span>
                </div>
            </div>
        </div>

        <!-- 3-Column Blog Grid -->
        <div class="blog-posts-grid" id="blogGrid">
            <?php foreach ($gridPosts as $post): ?>
                <div class="blog-card-item" data-cat="<?php echo htmlspecialchars($post['category']); ?>" onclick="window.location.href='/blog-single.php?id=<?php echo $post['id']; ?>'">
                    <div class="blog-card-thumb">
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                    <div class="blog-card-content">
                        <div>
                            <div class="post-meta-row">
                                <span class="post-cat-badge"><?php echo htmlspecialchars($post['category']); ?></span>
                                <span><?php echo htmlspecialchars($post['date']); ?></span>
                            </div>
                            <h3 class="blog-card-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="blog-card-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        </div>

                        <div class="blog-card-footer">
                            <div class="author-mini">
                                <i class="fa-regular fa-user" style="color:#ef4444;"></i>
                                <span><?php echo htmlspecialchars($post['author']); ?></span>
                            </div>
                            <span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($post['read_time']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Article Reader Modal -->
    <div class="reader-modal-backdrop" id="readerModal">
        <div class="reader-modal-box">
            <button class="reader-close-btn" onclick="closeReaderModal()">&times;</button>
            <div class="reader-header-cover">
                <img id="readerCoverImg" src="" alt="Article Cover">
            </div>
            <div class="post-meta-row" style="margin-bottom:12px;">
                <span class="post-cat-badge" id="readerCategory"></span>
                <span id="readerDate"></span>
                <span>&bull;</span>
                <span id="readerTime"></span>
            </div>
            <h2 id="readerTitle" style="font-size:2.2rem; font-weight:800; color:#fff; margin:0 0 12px 0; line-height:1.2;"></h2>
            <div class="author-info-left" style="margin-bottom:24px;">
                <div class="author-avatar-circle"><i class="fa-regular fa-user"></i></div>
                <div>
                    <h4 class="author-name-text" id="readerAuthor"></h4>
                    <p class="author-role-text" id="readerRole"></p>
                </div>
            </div>
            <div class="reader-body-text" id="readerContent"></div>
        </div>
    </div>

    <!-- Manage Posts Modal -->
    <div class="reader-modal-backdrop" id="manageModal">
        <div class="reader-modal-box" style="max-width:540px;">
            <button class="reader-close-btn" onclick="closeManageModal()">&times;</button>
            <h3 style="font-size:1.4rem; font-weight:800; color:#fff; margin:0 0 8px 0;"><i class="fa-regular fa-pen-to-square" style="color:#ef4444;"></i> Manage Blog Posts</h3>
            <p style="color:#a1a1aa; font-size:0.88rem; margin:0 0 24px 0;">Create a new article or edit existing blog posts.</p>
            
            <form onsubmit="event.preventDefault(); alert('Blog post created successfully!'); closeManageModal();">
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#a1a1aa; margin-bottom:6px;">Article Title *</label>
                    <input type="text" required placeholder="e.g. 10 Essential Video Production Tips" style="width:100%; background:#030305; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:12px 16px; color:#fff; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#a1a1aa; margin-bottom:6px;">Category *</label>
                    <select required style="width:100%; background:#030305; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:12px 16px; color:#fff; box-sizing:border-box;">
                        <option value="Industry Trends">Industry Trends</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Case Study">Case Study</option>
                        <option value="Tutorial">Tutorial</option>
                        <option value="Strategy">Strategy</option>
                        <option value="Equipment">Equipment</option>
                    </select>
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#a1a1aa; margin-bottom:6px;">Excerpt *</label>
                    <textarea required rows="3" placeholder="Brief article summary..." style="width:100%; background:#030305; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:12px 16px; color:#fff; box-sizing:border-box;"></textarea>
                </div>
                <button type="submit" style="width:100%; background:#dc2626; color:#fff; border:none; padding:14px; border-radius:10px; font-weight:800; cursor:pointer;">Publish Article</button>
            </form>
        </div>
    </div>

    <!-- Footer Bar -->
    <div class="blog-footer-bar">
        <div>&copy; 2026 Falhen Media. All rights reserved.</div>
        <div class="blog-footer-links">
            <a href="/">Home</a>
            <a href="/about.php">About</a>
            <a href="/portfolio.php">Portfolio</a>
            <a href="/contact.php">Contact</a>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const blogPostsData = <?php echo json_encode($blogPosts); ?>;

        function filterCategory(cat, btn) {
            document.querySelectorAll('.blog-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const featCard = document.querySelector('.featured-post-card');
            if (featCard) {
                const featCat = featCard.getAttribute('data-cat');
                featCard.style.display = (cat === 'all' || featCat === cat) ? 'grid' : 'none';
            }

            document.querySelectorAll('.blog-card-item').forEach(card => {
                const cardCat = card.getAttribute('data-cat');
                cardCardDisplay = (cat === 'all' || cardCat === cat) ? 'flex' : 'none';
                card.style.display = cardCardDisplay;
            });
        }

        function searchPosts() {
            const query = document.getElementById('blogSearchInput').value.toLowerCase().trim();

            document.querySelectorAll('.blog-card-item').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? 'flex' : 'none';
            });

            const featCard = document.querySelector('.featured-post-card');
            if (featCard) {
                const featText = featCard.textContent.toLowerCase();
                featCard.style.display = featText.includes(query) ? 'grid' : 'none';
            }
        }

        function openReaderModal(id) {
            const post = blogPostsData[id];
            if (!post) return;

            document.getElementById('readerCoverImg').src = post.image;
            document.getElementById('readerCategory').textContent = post.category;
            document.getElementById('readerDate').textContent = post.date;
            document.getElementById('readerTime').textContent = post.read_time;
            document.getElementById('readerTitle').textContent = post.title;
            document.getElementById('readerAuthor').textContent = post.author;
            document.getElementById('readerRole').textContent = post.role;
            document.getElementById('readerContent').textContent = post.content || post.excerpt;

            document.getElementById('readerModal').classList.add('active');
        }

        function closeReaderModal() {
            document.getElementById('readerModal').classList.remove('active');
        }

        function openManageModal() {
            document.getElementById('manageModal').classList.add('active');
        }

        function closeManageModal() {
            document.getElementById('manageModal').classList.remove('active');
        }

        // Close on backdrop click
        document.getElementById('readerModal').addEventListener('click', function(e) {
            if (e.target === this) closeReaderModal();
        });
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
