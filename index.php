<?php
$pageTitle = "Falhen Video Production | Visual Storytelling";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Fetch services from DB if available
$pdo = getDBConnection();
$services = [];
$portfolioItems = [];
$testimonials = [];

if ($pdo) {
    try {
        $services = $pdo->query("SELECT * FROM `services` ORDER BY `featured` DESC, `id` ASC LIMIT 6")->fetchAll();
        $portfolioItems = $pdo->query("SELECT * FROM `portfolio` ORDER BY `id` DESC LIMIT 6")->fetchAll();
        $testimonials = $pdo->query("SELECT * FROM `testimonials` ORDER BY `id` ASC LIMIT 3")->fetchAll();
    } catch (Exception $e) {}
}
?>

<!-- Hero Section (Dynamic Admin Settings Integration) -->
<?php 
require_once __DIR__ . '/includes/functions.php';
$settings = getSiteSettings();
$showreelId = $settings['showreel_youtube_id'] ?? 'Tf8rNMZ-Bw0';
if (preg_match('/(?:v=|\/embed\/|\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $showreelId, $matches)) {
    $showreelId = $matches[1];
}
$showreelEmbedUrl = "https://www.youtube.com/embed/" . htmlspecialchars($showreelId) . "?autoplay=1";
$rawPoster = !empty($settings['hero_poster_image']) ? $settings['hero_poster_image'] : '/assets/img/hero.jpg';
$heroPoster = getCloudinaryUrl($rawPoster);
$heroDirectVideo = $settings['hero_direct_video_url'] ?? '';
?>
<section class="hero-stage">
    <?php if ($heroDirectVideo): ?>
        <video class="hero-bg-animated" autoplay muted loop playsinline poster="<?php echo htmlspecialchars($heroPoster); ?>" style="object-fit: cover; width:100%; height:100%;">
            <source src="<?php echo htmlspecialchars($heroDirectVideo); ?>" type="video/mp4">
        </video>
    <?php else: ?>
        <div class="hero-bg-animated" style="background-image: url('<?php echo htmlspecialchars($heroPoster); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="container hero-container">
            <?php if (!empty($settings['hero_badge_label'])): ?>
                <div class="section-badge-pill" style="margin-bottom: 16px; display: inline-block;">
                    <?php echo htmlspecialchars($settings['hero_badge_label']); ?>
                </div>
            <?php endif; ?>
            <h1 class="hero-title">
                <?php 
                $line1 = htmlspecialchars($settings['hero_headline_line1'] ?? 'Creating what the');
                $line2 = htmlspecialchars($settings['hero_headline_line2'] ?? 'World Watches');
                echo $line1 . '<br><span class="hero-gradient-text">' . $line2 . '</span>';
                ?>
            </h1>
            <p class="hero-subtitle">
                <?php echo htmlspecialchars($settings['hero_tagline'] ?? "From cinematic campaigns to viral content — we craft the visual stories your audience can't look away from."); ?>
            </p>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars($settings['hero_primary_cta_url'] ?? '/portfolio.php'); ?>" class="btn btn-hero-red">
                    <?php echo htmlspecialchars($settings['hero_primary_cta_text'] ?? 'Explore Our Projects'); ?>
                </a>
                <a href="#" class="btn btn-hero-glass trigger-video" data-video="<?php echo $showreelEmbedUrl; ?>">
                    <span class="hero-play-icon"><i class="fa-solid fa-play"></i></span> <?php echo htmlspecialchars($settings['hero_secondary_cta_text'] ?? 'Watch Showreel'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Trusted Brands Section (Replicated from falhen.com) -->
<?php 
    $allBrandLogos = getBrandLogosRepo();
    $visibleBrands = array_values(array_filter($allBrandLogos, function($b) {
        return !empty($b['visible']);
    }));
    if (empty($visibleBrands)) {
        $visibleBrands = array_values($allBrandLogos);
    }
?>
<section class="brands-section">
    <div class="brands-title">TRUSTED BY WORLD-CLASS BRANDS</div>
    <div class="brands-ticker-wrapper">
        <div class="brands-ticker-track">
            <!-- First Set -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'])); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i> 
                        <?php echo htmlspecialchars($brand['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Duplicate Set for Seamless Infinite Ticker Loop -->
            <?php foreach ($visibleBrands as $brand): ?>
                <div class="brand-card">
                    <?php if (!empty($brand['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($brand['image'])); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" style="max-height: 26px; max-width: 110px; object-fit: contain;">
                    <?php else: ?>
                        <i class="<?php echo htmlspecialchars(!empty($brand['icon']) ? $brand['icon'] : 'fa-solid fa-star'); ?>"></i> 
                        <?php echo htmlspecialchars($brand['name']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Services Section (Replicated exactly from falhen.com screenshots) -->
<section class="section" id="services">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center;">
            <div class="section-badge-pill">What We Do</div>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">Our Services</h2>
            <p class="section-subtitle" style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">From concept to completion, we bring your vision to life with creativity and precision.</p>
        </div>
        
        <div class="services-grid">
            <?php 
            $homepageServices = getServicesRepo();
            foreach ($homepageServices as $sItem): 
            ?>
                <div class="service-card">
                    <div class="service-card-banner">
                        <img src="<?php echo htmlspecialchars($sItem['image']); ?>" alt="<?php echo htmlspecialchars($sItem['title']); ?>" onerror="this.src='/assets/img/hero.jpg';">
                        <div class="service-icon-box"><i class="<?php echo htmlspecialchars($sItem['icon']); ?>"></i></div>
                    </div>
                    <div class="service-card-body">
                        <h3 class="service-title"><?php echo htmlspecialchars($sItem['title']); ?></h3>
                        <p class="service-desc"><?php echo htmlspecialchars($sItem['short_description']); ?></p>
                        <ul class="service-checklist">
                            <?php foreach (array_slice($sItem['card_features'], 0, 3) as $feat): ?>
                                <li><span class="check-icon">✓</span> <?php echo htmlspecialchars($feat); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="/service-single.php?slug=<?php echo htmlspecialchars($sItem['slug']); ?>" class="service-learn-link">Learn More <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Carousel Navigation Controls -->
        <div class="services-mobile-controls">
            <button type="button" class="services-carousel-btn" id="servicesPrevBtn" aria-label="Previous Service">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="services-carousel-dots" id="servicesDots">
                <?php foreach ($homepageServices as $idx => $sItem): ?>
                    <span class="services-dot <?php echo ($idx === 0) ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                <?php endforeach; ?>
            </div>

            <button type="button" class="services-carousel-btn" id="servicesNextBtn" aria-label="Next Service">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- By the Numbers Section (Dynamic Admin Settings Integration) -->
<?php 
$statsBadge = !empty($settings['stats_badge_label']) ? $settings['stats_badge_label'] : 'By the Numbers';
$statsWhite = !empty($settings['stats_headline_white']) ? $settings['stats_headline_white'] : 'A Decade of';
$statsRed = !empty($settings['stats_headline_red']) ? $settings['stats_headline_red'] : 'Impact';
$statsDesc = !empty($settings['stats_description']) ? $settings['stats_description'] : "The numbers behind Falhen's reputation as one of Africa's most awarded and globally recognised production houses.";
$statsItems = $settings['stats_items'] ?? [
    ['number' => '250', 'suffix' => '+', 'prefix' => '', 'label' => 'Projects Delivered', 'sublabel' => 'Across commercial, film & events', 'icon' => 'ri-film-line'],
    ['number' => '12', 'suffix' => '+', 'prefix' => '', 'label' => 'Years Experience', 'sublabel' => 'Delivering world-class productions', 'icon' => 'ri-history-line'],
    ['number' => '7', 'suffix' => '+', 'prefix' => '', 'label' => 'Industries Served', 'sublabel' => 'From tech to luxury to social', 'icon' => 'ri-earth-line'],
    ['number' => '4', 'suffix' => '+', 'prefix' => '', 'label' => 'Industry Awards', 'sublabel' => 'SHH, Webby & more', 'icon' => 'ri-trophy-line']
];
?>
<section class="section" id="impact" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <div class="section-badge-pill"><?php echo htmlspecialchars($statsBadge); ?></div>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">
                <?php echo htmlspecialchars($statsWhite); ?> <span style="color: #ff4d4d;"><?php echo htmlspecialchars($statsRed); ?></span>
            </h2>
            <p class="section-subtitle" style="max-width: 650px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">
                <?php echo htmlspecialchars($statsDesc); ?>
            </p>
        </div>

        <div class="impact-grid">
            <?php foreach ($statsItems as $item): ?>
                <?php 
                $iconClass = 'fa-solid fa-film';
                $ic = $item['icon'] ?? '';
                if (strpos($ic, 'history') !== false) $iconClass = 'fa-solid fa-clock-rotate-left';
                if (strpos($ic, 'earth') !== false) $iconClass = 'fa-solid fa-globe';
                if (strpos($ic, 'trophy') !== false) $iconClass = 'fa-solid fa-trophy';
                if (strpos($ic, 'star') !== false) $iconClass = 'fa-solid fa-star';
                
                $numVal = preg_replace('/[^0-9]/', '', $item['number'] ?? '0');
                ?>
                <div class="impact-card">
                    <div class="impact-icon-box"><i class="<?php echo $iconClass; ?>"></i></div>
                    <div class="impact-number" data-count="<?php echo htmlspecialchars($numVal ?: '0'); ?>">
                        <?php echo htmlspecialchars($item['prefix'] ?? ''); ?>0<span class="impact-plus"><?php echo htmlspecialchars($item['suffix'] ?? '+'); ?></span>
                    </div>
                    <h3 class="impact-label"><?php echo htmlspecialchars($item['label'] ?? ''); ?></h3>
                    <p class="impact-sublabel"><?php echo htmlspecialchars($item['sublabel'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Work Section (Dynamically powered by Portfolio Showcase Video Reels) -->
<?php 
$allPortfolioRepo = getPortfolioRepo();
$portfolioVideos = array_values(array_filter($allPortfolioRepo, function($i) {
    return ($i['media_type'] ?? 'photo') === 'video';
}));

if (empty($portfolioVideos)) {
    $portfolioVideos = $allPortfolioRepo;
}

$featuredItems = [];
foreach ($portfolioVideos as $idx => $item) {
    $ytId = extractYouTubeId(!empty($item['video_url']) ? $item['video_url'] : ($item['image'] ?? ''));
    $thumb = !empty($item['image']) ? getCloudinaryUrl($item['image']) : getYouTubeThumbnailUrl($ytId);
    $featuredItems[] = [
        'id' => $item['id'] ?? ($idx + 1),
        'project_name' => $item['title'] ?? 'Featured Reel',
        'client' => $item['client'] ?? 'Falhen',
        'category' => strtoupper($item['category'] ?? 'General'),
        'duration' => !empty($item['duration']) ? $item['duration'] : '02:30',
        'youtube_id' => $ytId,
        'video_url' => $item['video_url'] ?? '',
        'thumbnail' => $thumb,
        'is_hero_featured' => !empty($item['featured']),
        'status' => 'live'
    ];
}

$heroItem = $featuredItems[0];
foreach ($featuredItems as $f) {
    if (!empty($f['is_hero_featured'])) {
        $heroItem = $f;
        break;
    }
}

$heroYtId = extractYouTubeId($heroItem['youtube_id']);
$heroImg = !empty($heroItem['thumbnail']) ? getCloudinaryUrl($heroItem['thumbnail']) : getYouTubeThumbnailUrl($heroYtId);
$heroVideoUrl = 'https://www.youtube.com/embed/' . $heroYtId;
?>
<section class="section" id="portfolio" style="background: #000000; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title-wrapper" style="margin-bottom: 30px;">
            <div class="section-badge-pill">FEATURED WORK</div>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">Stories We've <span style="color: #ff4d4d;">Told.</span></h2>
        </div>

        <!-- Dynamic Filter Pill Tabs -->
        <?php 
        $uniqueCategories = array_unique(array_filter(array_map(function($f) {
            return strtoupper($f['category']);
        }, $featuredItems)));
        ?>
        <div class="portfolio-filter-bar">
            <button class="filter-pill-btn active" data-filter="all">ALL</button>
            <?php foreach ($uniqueCategories as $catName): ?>
                <button class="filter-pill-btn" data-filter="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Main Featured Video Showcase Player -->
        <div class="featured-showcase-player">
            <div class="showcase-player-bg" id="showcase-player-bg" style="background-image: url('<?php echo htmlspecialchars($heroImg); ?>');">
                <div class="showcase-player-overlay"></div>
                <div class="showcase-counter-badge"><span id="showcase-current">1</span> / <span id="showcase-total"><?php echo count($featuredItems); ?></span></div>
                
                <div class="showcase-content">
                    <div class="showcase-meta">
                        <span class="showcase-badge" id="showcase-category"><?php echo htmlspecialchars($heroItem['category']); ?></span>
                        <span class="showcase-client" id="showcase-client"><?php echo htmlspecialchars($heroItem['client']); ?></span>
                        <span class="showcase-duration" id="showcase-time"><?php echo htmlspecialchars($heroItem['duration']); ?></span>
                    </div>
                    <h3 class="showcase-title" id="showcase-title"><?php echo htmlspecialchars($heroItem['project_name']); ?></h3>
                    <div class="showcase-actions">
                        <button class="btn btn-hero-red trigger-video" id="showcase-play-btn" data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
                            <i class="fa-solid fa-play"></i> Watch Film
                        </button>
                        <button class="showcase-fullscreen-btn trigger-video" id="showcase-full-btn" data-video="<?php echo htmlspecialchars($heroVideoUrl); ?>">
                            <i class="fa-solid fa-expand"></i> Full screen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thumbnail Carousel Track -->
        <div class="showcase-thumbs-wrapper">
            <div class="showcase-thumbs-track" id="showcase-thumbs-track">
                <?php foreach ($featuredItems as $idx => $item): ?>
                    <?php 
                    $ytId = extractYouTubeId($item['youtube_id']);
                    $itemThumb = !empty($item['thumbnail']) ? $item['thumbnail'] : getYouTubeThumbnailUrl($ytId);
                    $thumbImg = getCloudinaryUrl($itemThumb);
                    $videoEmbed = 'https://www.youtube.com/embed/' . $ytId;
                    $isActive = !empty($item['is_hero_featured']) || ($idx === 0 && empty($heroItem['is_hero_featured']));
                    ?>
                    <div class="thumb-card <?php echo $isActive ? 'active' : ''; ?>" 
                         data-index="<?php echo $idx; ?>" 
                         data-category="<?php echo htmlspecialchars($item['category']); ?>" 
                         data-client="<?php echo htmlspecialchars($item['client']); ?>" 
                         data-time="<?php echo htmlspecialchars($item['duration']); ?>" 
                         data-title="<?php echo htmlspecialchars($item['project_name']); ?>" 
                         data-img="<?php echo htmlspecialchars($thumbImg); ?>" 
                         data-video="<?php echo htmlspecialchars($videoEmbed); ?>">
                        <div class="thumb-badge-now">NOW</div>
                        <div class="thumb-img-wrapper">
                            <img src="<?php echo htmlspecialchars($thumbImg); ?>" alt="<?php echo htmlspecialchars($item['project_name']); ?>" onerror="this.src='https://i.ytimg.com/vi/<?php echo htmlspecialchars($ytId); ?>/hqdefault.jpg';">
                            <span class="thumb-time"><?php echo htmlspecialchars($item['duration']); ?></span>
                        </div>
                        <div class="thumb-info">
                            <span class="thumb-cat"><?php echo htmlspecialchars($item['category']); ?></span>
                            <h4 class="thumb-title"><?php echo htmlspecialchars($item['project_name']); ?></h4>
                            <small class="thumb-client"><?php echo htmlspecialchars($item['client']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Row Controls & Explore Action -->
        <div class="showcase-controls-bar">
            <div class="showcase-nav-btns">
                <button class="nav-arrow-btn" id="showcase-prev-btn" aria-label="Previous Showcase"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="nav-arrow-btn" id="showcase-next-btn" aria-label="Next Showcase"><i class="fa-solid fa-chevron-right"></i></button>
                <span class="auto-advance-label">Auto-advancing every 6s</span>
            </div>
            <a href="/services.php" class="explore-services-btn">Explore Services <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Client Stories Section (Replicated from falhen.com screenshots) -->
<section class="section" id="testimonials" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <div class="section-badge-pill">Client Stories</div>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">What Our Clients <span style="color: #ff4d4d;">Say</span></h2>
            <p class="section-subtitle" style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">Don't take our word for it — here's what the brands we've worked with have to say.</p>
        </div>

        <div class="testimonials-wrapper">
            <div class="testimonials-grid" id="testimonials-grid">
                <?php 
                $dynamicTestimonials = getTestimonialsRepo();
                foreach ($dynamicTestimonials as $idx => $tCard):
                ?>
                    <div class="testimonial-card <?php echo ($idx === 0) ? 'active' : ''; ?>">
                        <div class="testimonial-quote-icon">“</div>
                        <p class="testimonial-quote">"<?php echo htmlspecialchars($tCard['quote']); ?>"</p>
                        <?php if (!empty($tCard['project'])): ?>
                            <div class="testimonial-tag-pill"><?php echo htmlspecialchars($tCard['project']); ?></div>
                        <?php endif; ?>
                        <div class="testimonial-footer">
                            <div class="testimonial-author">
                                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($tCard['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80')); ?>" alt="<?php echo htmlspecialchars($tCard['name']); ?>" class="author-img" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';">
                                <div>
                                    <div class="author-name"><?php echo htmlspecialchars($tCard['name']); ?></div>
                                    <div class="author-role"><?php echo htmlspecialchars(($tCard['role'] ?? '') . (!empty($tCard['company']) ? ', ' . $tCard['company'] : '')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Carousel Navigation Bar -->
        <div class="testimonial-controls-bar">
            <div class="testimonial-nav-btns">
                <button class="nav-arrow-btn" id="testi-prev-btn" aria-label="Previous Testimonial"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="testimonial-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
                <button class="nav-arrow-btn" id="testi-next-btn" aria-label="Next Testimonial"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
</section>

<!-- The People Section (Replicated from falhen.com screenshots) -->
<section class="section" id="team" style="background: #000000; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header Flex -->
        <div class="team-header-flex">
            <div class="team-header-left">
                <div class="section-badge-pill">The People</div>
                <h2 class="section-title" style="font-size: 3.2rem; font-weight: 800; margin: 0; line-height: 1.1;">Meet the <span style="color: #ff4d4d;">Team</span></h2>
            </div>
            <div class="team-header-right">
                <p class="team-header-desc">8 specialists, one shared obsession — making content that the world can't stop watching. Combined 23+ skills across 3+ average years of experience.</p>
            </div>
        </div>

        <!-- Dynamic Team Grid -->
        <div class="team-grid">
            <?php 
            $homepageTeamMembers = getTeamMembers();
            foreach ($homepageTeamMembers as $tm): 
                $numBadge = !empty($tm['number']) ? $tm['number'] : sprintf("%02d", $tm['id']);
                $deptClass = strtolower($tm['department'] ?? 'creative');
                $imgUrl = getCloudinaryUrl(!empty($tm['image']) ? $tm['image'] : '/assets/img/team/team_henry.png');
            ?>
                <!-- Team Card -->
                <a href="/team-single.php?id=<?php echo $tm['id']; ?>" class="team-card">
                    <div class="team-card-thumb">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($tm['name']); ?>" onerror="this.src='/assets/img/team/team_henry.png';">
                        <span class="team-number-badge"><?php echo htmlspecialchars($numBadge); ?></span>
                        
                        <!-- Normal Overlay -->
                        <div class="team-card-overlay normal-overlay">
                            <div class="team-name-row">
                                <h3 class="team-name"><?php echo htmlspecialchars($tm['name']); ?></h3>
                                <span class="team-dept-pill <?php echo htmlspecialchars($deptClass); ?>"><?php echo htmlspecialchars($tm['department'] ?? 'Creative'); ?></span>
                            </div>
                            <div class="team-role"><?php echo htmlspecialchars(strtoupper($tm['role'])); ?></div>
                            <div class="team-meta">
                                <?php if (!empty($tm['location'])): ?>
                                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($tm['location']); ?> &nbsp;&nbsp;
                                <?php endif; ?>
                                <?php if (!empty($tm['experience'])): ?>
                                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($tm['experience']); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hover Profile Overlay -->
                        <div class="team-card-overlay hover-profile-overlay">
                            <div class="team-name-row">
                                <h3 class="team-name"><?php echo htmlspecialchars($tm['name']); ?></h3>
                                <span class="team-dept-pill <?php echo htmlspecialchars($deptClass); ?>"><?php echo htmlspecialchars($tm['department'] ?? 'Creative'); ?></span>
                            </div>
                            <div class="team-role"><?php echo htmlspecialchars(strtoupper($tm['role'])); ?></div>
                            <?php if (!empty($tm['skills']) && is_array($tm['skills'])): ?>
                                <div class="team-skills-pills">
                                    <?php foreach ($tm['skills'] as $sk): ?>
                                        <span class="skill-pill"><?php echo htmlspecialchars($sk); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($tm['bio'])): ?>
                        <div class="team-hover-bio-box">
                            <p>"<?php echo htmlspecialchars($tm['bio']); ?>"</p>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Team Carousel Navigation Controls -->
        <div class="team-mobile-controls">
            <button type="button" class="team-carousel-btn" id="teamPrevBtn" aria-label="Previous Team Member">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="team-carousel-dots" id="teamDots">
                <?php foreach ($homepageTeamMembers as $idx => $tm): ?>
                    <span class="team-dot <?php echo ($idx === 0) ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                <?php endforeach; ?>
            </div>

            <button type="button" class="team-carousel-btn" id="teamNextBtn" aria-label="Next Team Member">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Talent Recruitment Banner -->
        <div class="team-talent-banner">
            <div class="talent-banner-text">
                <h3>We're always looking for extraordinary talent.</h3>
                <p>If you live and breathe visual storytelling, we want to hear from you.</p>
            </div>
            <a href="/contact.php" class="talent-banner-btn">Work With Us</a>
        </div>
    </div>
</section>

<!-- Production BTS Section (Dynamic Admin Settings Integration) -->
<?php 
$btsBadge = !empty($settings['bts_badge_label']) ? $settings['bts_badge_label'] : 'Production BTS';
$btsWhite = !empty($settings['bts_headline_white']) ? $settings['bts_headline_white'] : 'Every Frame';
$btsRed = !empty($settings['bts_headline_red']) ? $settings['bts_headline_red'] : 'Speaks';
$btsDesc = !empty($settings['bts_description']) ? $settings['bts_description'] : 'A raw look at what it takes to create world-class content — from location scouts to post-production suites.';
$btsItems = $settings['bts_items'] ?? [
    ['id' => 1, 'title' => 'On set — RedBull Campaign, Dubai 2024', 'subtitle' => 'Director reviewing footage on set', 'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 2, 'title' => 'ARRI ALEXA Mini LF — lens prep', 'subtitle' => 'Camera rig setup', 'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 3, 'title' => 'Lighting rig — HBO Teaser, Cape Town', 'subtitle' => 'Lighting setup', 'image' => 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 4, 'title' => 'Aerial Drone Scout — Kenya Safari', 'subtitle' => '4K aerial footage capture', 'image' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 5, 'title' => 'Post-Production Suite — Color Grading', 'subtitle' => 'DaVinci Resolve studio session', 'image' => 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=800&q=80', 'visible' => true],
    ['id' => 6, 'title' => 'Wedding Cinema Shoot — Sunset', 'subtitle' => 'Romantic Golden Hour capture', 'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80', 'visible' => true]
];
?>
<section class="section" id="bts" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header Flex -->
        <div class="team-header-flex" style="margin-bottom: 40px;">
            <div class="team-header-left">
                <div class="section-badge-pill"><?php echo htmlspecialchars($btsBadge); ?></div>
                <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">
                    <?php echo htmlspecialchars($btsWhite); ?> <span style="color: #ff4d4d;"><?php echo htmlspecialchars($btsRed); ?></span>
                </h2>
            </div>
            <div class="team-header-right">
                <p class="team-header-desc"><?php echo htmlspecialchars($btsDesc); ?></p>
            </div>
        </div>

        <!-- BTS Grid (Dynamic Loop) -->
        <div class="bts-grid">
            <?php foreach (array_slice($btsItems, 0, 6) as $bts): ?>
                <?php 
                $imgCdn = getCloudinaryUrl($bts['image']);
                $captionText = !empty($bts['title']) ? $bts['title'] : ($bts['subtitle'] ?? 'Production BTS');
                ?>
                <div class="bts-card trigger-lightbox" data-img="<?php echo htmlspecialchars($imgCdn); ?>" data-caption="<?php echo htmlspecialchars($captionText); ?>">
                    <img src="<?php echo htmlspecialchars($imgCdn); ?>" alt="<?php echo htmlspecialchars($captionText); ?>" onerror="this.src='/assets/img/hero.jpg';">
                    <div class="bts-card-overlay">
                        <div class="bts-zoom-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <span class="bts-caption"><?php echo htmlspecialchars($captionText); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox Modal for BTS Gallery -->
<div class="bts-lightbox-modal" id="btsLightbox">
    <div class="bts-lightbox-overlay"></div>
    <div class="bts-lightbox-content">
        <button class="bts-lightbox-close" id="btsLightboxClose"><i class="fa-solid fa-xmark"></i></button>
        <img src="" alt="BTS Lightbox Image" id="btsLightboxImg">
        <div class="bts-lightbox-caption" id="btsLightboxCaption"></div>
    </div>
</div>

<!-- Insights / Latest from the Blog Section (Replicated from falhen.com screenshots) -->
<section class="section" id="blog" style="background: #000000; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header Flex -->
        <div class="insights-header-flex">
            <div class="insights-header-left">
                <div class="section-badge-pill">Insights</div>
                <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">Latest from the <span style="color: #ff4d4d;">Blog</span></h2>
            </div>
            <a href="/blog.php" class="insights-view-all">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <!-- 3-Card Blog Grid -->
        <div class="blog-grid">
            <?php 
            $homeBlogItems = array_slice(array_values(getBlogRepo()), 0, 3);
            foreach ($homeBlogItems as $hBlog): 
            ?>
                <article class="blog-card">
                    <div class="blog-card-thumb">
                        <img 
                            src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($hBlog['image']) ? $hBlog['image'] : '/assets/img/services/service_video.png')); ?>" 
                            alt="<?php echo htmlspecialchars($hBlog['title']); ?>"
                            onerror="this.src='/assets/img/services/service_video.png';"
                        >
                        <span class="blog-cat-pill"><?php echo htmlspecialchars($hBlog['category'] ?? 'Social Media'); ?></span>
                    </div>
                    <div class="blog-card-body">
                        <div class="blog-meta"><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($hBlog['date'] ?? ''); ?> &nbsp;·&nbsp; <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($hBlog['read_time'] ?? ''); ?></div>
                        <h3 class="blog-title"><a href="/blog-single.php?id=<?php echo $hBlog['id']; ?>"><?php echo htmlspecialchars($hBlog['title']); ?></a></h3>
                        <div class="blog-footer">
                            <span class="blog-author"><?php echo htmlspecialchars($hBlog['author'] ?? 'Falhen Team'); ?></span>
                            <a href="/blog-single.php?id=<?php echo $hBlog['id']; ?>" class="blog-read-link">Read <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Let's Talk / Book a Discovery Call Section (Replicated from falhen.com screenshots) -->
<section class="section" id="lets-talk" style="background: #09090b; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 50px;">
            <div class="section-badge-pill">Let's Talk</div>
            <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800;">Book a <span style="color: #ff4d4d;">Discovery Call</span></h2>
            <p class="section-subtitle" style="max-width: 620px; margin: 0 auto; color: var(--text-muted); font-size: 1.05rem;">30 minutes. No sales pitch. Just a candid conversation about your project.</p>
        </div>

        <!-- 2-Column Discovery Call Layout -->
        <div class="discovery-grid">
            <!-- Left Side: Hero Image & Feature Cards & Proof -->
            <div class="discovery-left">
                <!-- Top Hero Image Box with Available Badge -->
                <div class="discovery-hero-box">
                    <img src="https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?q=80&w=1000&auto=format&fit=crop" alt="Discovery Call Studio">
                    <div class="discovery-hero-overlay">
                        <span class="discovery-available-pill">
                            <span class="pulse-green-dot"></span> Available — Book your slot now
                        </span>
                    </div>
                </div>

                <!-- 2x2 Feature Cards Grid -->
                <div class="discovery-features-grid">
                    <!-- Feature 1 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-icon-box"><i class="fa-solid fa-clock"></i></div>
                        <h4 class="discovery-feature-title">30-Min Deep Dive</h4>
                        <p class="discovery-feature-desc">No fluff — just a focused conversation about your project and goals.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-icon-box"><i class="fa-solid fa-lightbulb"></i></div>
                        <h4 class="discovery-feature-title">Creative Direction</h4>
                        <p class="discovery-feature-desc">We'll give you initial ideas and directional thoughts on the spot.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-icon-box"><i class="fa-solid fa-file-lines"></i></div>
                        <h4 class="discovery-feature-title">Proposal in 48hrs</h4>
                        <p class="discovery-feature-desc">A full scope, timeline, and budget proposal delivered fast after the call.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="discovery-feature-card">
                        <div class="discovery-icon-box"><i class="fa-solid fa-lock"></i></div>
                        <h4 class="discovery-feature-title">Zero Commitment</h4>
                        <p class="discovery-feature-desc">No pressure, no contracts — just a conversation between creators.</p>
                    </div>
                </div>

                <!-- Bottom Social Proof Bar -->
                <div class="discovery-proof-bar">
                    <div class="discovery-avatars-group">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=100&auto=format&fit=crop" alt="Client 1">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=100&auto=format&fit=crop" alt="Client 2">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=100&auto=format&fit=crop" alt="Client 3">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=100&auto=format&fit=crop" alt="Client 4">
                    </div>
                    <div class="discovery-proof-text">
                        <strong>250+ calls booked</strong>
                        <small>with brands across country</small>
                    </div>
                </div>
            </div>

            <!-- Right Side: Booking Form Card -->
            <div class="discovery-form-card">
                <div class="discovery-form-header">
                    <h3 class="discovery-form-title">Your Details</h3>
                    <p class="discovery-form-subtitle">We'll confirm your slot within 2 hours.</p>
                </div>

                <div id="formFeedback" style="display: none; margin-bottom: 20px;"></div>

                <form id="inquiryForm" class="discovery-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="discovery-label">FULL NAME *</label>
                            <input type="text" name="full_name" class="discovery-input" placeholder="Jane Smith" required>
                        </div>
                        <div class="form-group">
                            <label class="discovery-label">COMPANY</label>
                            <input type="text" name="company" class="discovery-input" placeholder="Your Brand">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="discovery-label">EMAIL ADDRESS *</label>
                        <input type="email" name="email" class="discovery-input" placeholder="jane@yourbrand.com" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="discovery-label">SERVICE</label>
                            <select name="service_type" class="discovery-input">
                                <option value="">Select...</option>
                                <option value="Video Production">Video Production</option>
                                <option value="Post Production">Post Production</option>
                                <option value="Live Streaming">Live Streaming</option>
                                <option value="Animation & Motion">Animation & Motion</option>
                                <option value="Wedding & Event">Wedding & Event</option>
                                <option value="Commercial Photography">Commercial Photography</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="discovery-label">PREFERRED DATE</label>
                            <input type="date" name="preferred_date" class="discovery-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="discovery-label">ABOUT YOUR PROJECT</label>
                            <span class="char-count" id="charCount">0/500</span>
                        </div>
                        <textarea name="project_desc" id="projectDesc" class="discovery-input discovery-textarea" maxlength="500" placeholder="Brief description of your project..."></textarea>
                    </div>

                    <button type="submit" class="btn-discovery-submit" id="submitInquiryBtn">
                        <i class="fa-regular fa-calendar-check"></i> Book My Discovery Call
                    </button>

                    <p class="discovery-form-footer-note">We'll confirm within 2 hours. No spam, ever.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
