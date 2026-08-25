<?php
$pageTitle = "Our Services — End-to-End Media Agency | Falhen Media";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$settings = getSiteSettings();
$dynamicServices = $settings['services_items'] ?? [];

if (!empty($dynamicServices)) {
    $servicesList = array_map(function($item) {
        return [
            'slug' => $item['slug'] ?? 'service',
            'title' => $item['title'] ?? 'Service',
            'icon' => $item['icon'] ?? 'fa-solid fa-film',
            'image' => getCloudinaryUrl($item['image'] ?? ''),
            'badge' => 'Cinema & TV',
            'desc' => $item['short_description'] ?? '',
            'tags' => $item['card_features'] ?? []
        ];
    }, $dynamicServices);
} else {
    $servicesList = [
        [
            'slug' => 'video-production',
            'title' => 'Video Production',
            'icon' => 'ri-video-line',
            'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80',
            'badge' => 'Cinema & TV',
            'desc' => 'Professional video production services from concept to completion, including scripting, filming, and editing with state-of-the-art equipment.',
            'tags' => ['Corporate videos & commercials', '4K & 8K video capture', 'Drone cinematography', 'Multi-camera productions']
        ],
        [
            'slug' => 'live-streaming',
            'title' => 'Live Streaming',
            'icon' => 'ri-live-line',
            'image' => 'https://images.unsplash.com/photo-1518173946687-a4c8a383392e?auto=format&fit=crop&w=800&q=80',
            'badge' => 'Global Broadcast',
            'desc' => 'Seamless live streaming solutions for events, conferences, and broadcasts with professional-grade equipment and technical support.',
            'tags' => ['Multi-platform streaming', 'Real-time graphics & overlays', 'Interactive audience engagement', 'Technical support & monitoring']
        ],
        [
            'slug' => 'post-production',
            'title' => 'Post Production',
            'icon' => 'ri-edit-line',
            'image' => 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=800&q=80',
            'badge' => 'DaVinci Resolve HDR',
            'desc' => 'Comprehensive post-production services including editing, color grading, sound design, and visual effects to polish your content.',
            'tags' => ['Professional video editing', 'Color correction & grading', 'Sound design & mixing', 'Visual effects & compositing']
        ],
        [
            'slug' => 'animation',
            'title' => 'Animation & Motion Graphics',
            'icon' => 'ri-magic-line',
            'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80',
            'badge' => '2D & 3D Design',
            'desc' => 'Creative animation and motion graphics services to enhance your brand storytelling with engaging visual elements.',
            'tags' => ['2D & 3D animation', 'Motion graphics design', 'Logo animations', 'Explainer videos']
        ]
    ];
}
?>

<style>
    .services-hero-wrapper {
        padding-top: 140px;
        padding-bottom: 90px;
        background: radial-gradient(circle at 50% 10%, rgba(220, 38, 38, 0.15) 0%, transparent 60%);
    }

    .services-pro-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
        margin-top: 50px;
    }

    .pro-service-card {
        background: #0e0e12;
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 22px;
        overflow: hidden;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    .pro-service-card:hover {
        border-color: rgba(220, 38, 38, 0.5);
        transform: translateY(-8px);
        box-shadow: 0 20px 45px rgba(220, 38, 38, 0.25);
    }

    .pro-card-image-wrap {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
    }

    .pro-card-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .pro-service-card:hover .pro-card-image-wrap img {
        transform: scale(1.08);
    }

    .pro-card-gradient-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(3, 3, 5, 0.1) 0%, rgba(14, 14, 18, 0.6) 60%, rgba(14, 14, 18, 1) 100%);
        z-index: 1;
    }

    .pro-card-top-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(14, 14, 18, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #ffffff;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 50px;
        backdrop-filter: blur(8px);
        z-index: 2;
    }

    .pro-card-icon-floating {
        position: absolute;
        bottom: -20px;
        left: 24px;
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
        z-index: 3;
        transition: transform 0.35s ease;
    }

    .pro-service-card:hover .pro-card-icon-floating {
        transform: scale(1.1);
    }

    .pro-card-body {
        padding: 32px 24px 24px 24px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .pro-card-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 10px 0;
        line-height: 1.25;
        letter-spacing: -0.3px;
    }

    .pro-card-desc {
        font-size: 0.9rem;
        color: #a1a1aa;
        line-height: 1.6;
        margin: 0 0 20px 0;
        flex-grow: 1;
    }

    .pro-card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 24px;
    }

    .pro-tag {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #d4d4d8;
        font-size: 0.74rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .pro-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .pro-action-text {
        font-size: 0.88rem;
        font-weight: 700;
        color: #ffffff;
        transition: color 0.25s ease;
    }

    .pro-service-card:hover .pro-action-text {
        color: #ef4444;
    }

    .pro-arrow-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    .pro-service-card:hover .pro-arrow-circle {
        background: #dc2626;
        border-color: #dc2626;
        transform: translateX(4px);
        box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
    }

    @media (max-width: 992px) {
        .services-pro-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .services-pro-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="section services-hero-wrapper">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center;">
            <div class="badge" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;"><i class="fa-solid fa-clapperboard"></i> Full-Service Media Agency</div>
            <h2 class="section-title" style="font-size: 3rem; font-weight: 800; color: #ffffff; margin-bottom: 14px;">End-to-End <span style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Media Services</span></h2>
            <p class="section-subtitle" style="font-size: 1.1rem; color: #a1a1aa; max-width: 680px; margin: 0 auto;">We handle every stage of visual media creation — from concept development to red-carpet delivery.</p>
        </div>

        <div class="services-pro-grid">
            <?php foreach ($servicesList as $s): ?>
                <div class="pro-service-card" onclick="window.location.href='/service-single.php?slug=<?php echo htmlspecialchars($s['slug']); ?>'">
                    <div class="pro-card-image-wrap">
                        <img src="<?php echo htmlspecialchars($s['image']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>">
                        <div class="pro-card-gradient-overlay"></div>
                        <span class="pro-card-top-badge"><?php echo htmlspecialchars($s['badge']); ?></span>
                        <div class="pro-card-icon-floating">
                            <i class="<?php echo htmlspecialchars($s['icon']); ?>"></i>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <h3 class="pro-card-title"><?php echo htmlspecialchars($s['title']); ?></h3>
                        <p class="pro-card-desc"><?php echo htmlspecialchars($s['desc']); ?></p>
                        <div class="pro-card-tags">
                            <?php foreach ($s['tags'] as $tag): ?>
                                <span class="pro-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="pro-card-footer">
                            <span class="pro-action-text">View Service Details</span>
                            <div class="pro-arrow-circle">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
