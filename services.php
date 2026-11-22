<?php
$pageTitle = "Video Production Services | Falhen Productions";
$pageDescription = "End-to-end media production services: commercial film, multi-camera live streaming, post-production, 3D motion design, and event cinematography.";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$settings = getSiteSettings();
$dynamicServices = $settings['services_items'] ?? [];
if (!empty($dynamicServices)) {
    $dynamicServices = array_values(array_filter($dynamicServices, function($s) {
        return empty($s['hidden']);
    }));
}

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
            'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg',
            'badge' => 'Cinema & TV',
            'desc' => 'Commercial films, brand documentaries, and 4K/8K cinema production crafted for impact.',
            'tags' => ['Commercial Films', '4K & 8K Cinema', 'Drone Aerials', 'Multi-Camera']
        ],
        [
            'slug' => 'live-streaming',
            'title' => 'Live Streaming',
            'icon' => 'ri-live-line',
            'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg',
            'badge' => 'Global Broadcast',
            'desc' => 'Multi-camera broadcast streaming for global events, keynotes, and concerts with redundant uplinks.',
            'tags' => ['Global Broadcast', 'Keynotes & Concerts', 'Real-Time Graphics', 'Redundant Uplink']
        ],
        [
            'slug' => 'post-production',
            'title' => 'Post Production',
            'icon' => 'ri-edit-line',
            'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380765/falhen/bts/bqpxfuekjfuqrbq1sqed.jpg',
            'badge' => 'DaVinci Resolve HDR',
            'desc' => 'Precision offline editing, DaVinci Resolve HDR color grading, and spatial sound design.',
            'tags' => ['Editorial & Assembly', 'HDR Color Grading', 'Audio Mastering', 'Visual Effects']
        ],
        [
            'slug' => 'animation',
            'title' => 'Animation & Motion Graphics',
            'icon' => 'ri-magic-line',
            'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380709/falhen/bts/yqjuxtubd9ka0gyqw68c.jpg',
            'badge' => '2D & 3D Design',
            'desc' => 'Dynamic 2D/3D motion design, kinetic brand identity, 3D product renders, and title sequences.',
            'tags' => ['2D & 3D Motion', 'Brand Identity', 'Kinetic Typography', '3D Visuals']
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
        overflow: visible;
        z-index: 5;
    }

    .pro-card-img-inner {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        border-top-left-radius: 22px;
        border-top-right-radius: 22px;
    }

    .pro-card-img-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .pro-service-card:hover .pro-card-img-inner img {
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
        bottom: -22px;
        left: 24px;
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.45);
        z-index: 10;
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .pro-service-card:hover .pro-card-icon-floating {
        transform: scale(1.1);
        box-shadow: 0 10px 28px rgba(220, 38, 38, 0.65);
    }

    .pro-card-body {
        padding: 34px 24px 24px 24px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        position: relative;
        z-index: 2;
    }

    .pro-card-title {
        font-size: 1.35rem;
        font-weight: 700;
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

    @media (max-width: 768px) {
        .services-hero-wrapper {
            padding-top: 85px !important;
            padding-bottom: 24px !important;
        }
        .services-hero-badge {
            font-size: 0.72rem !important;
            padding: 4px 12px !important;
            margin-bottom: 10px !important;
        }
        .services-hero-title {
            font-size: 1.85rem !important;
            line-height: 1.15 !important;
            margin-bottom: 8px !important;
        }
        .services-hero-subtitle {
            font-size: 0.85rem !important;
            line-height: 1.45 !important;
            color: #a1a1aa !important;
            margin: 0 auto !important;
            padding: 0 10px !important;
            display: block !important;
        }
        .services-pro-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
            margin-top: 20px !important;
        }
        .pro-service-card {
            border-radius: 16px !important;
            background: #0b0b0e !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        .pro-card-image-wrap {
            height: 135px !important;
            overflow: visible !important;
        }
        .pro-card-img-inner {
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
        }
        .pro-card-top-badge {
            top: 10px !important;
            right: 10px !important;
            font-size: 0.66rem !important;
            padding: 3px 9px !important;
        }
        .pro-card-icon-floating {
            bottom: -16px !important;
            left: 16px !important;
            width: 38px !important;
            height: 38px !important;
            font-size: 1rem !important;
            border-radius: 10px !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.45) !important;
        }
        .pro-card-body {
            padding: 24px 14px 14px 14px !important;
        }
        .pro-card-title {
            font-size: 1.08rem !important;
            line-height: 1.25 !important;
            margin-bottom: 6px !important;
        }
        .pro-card-desc {
            font-size: 0.82rem !important;
            line-height: 1.45 !important;
            color: #a1a1aa !important;
            margin-bottom: 10px !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }
        .pro-card-tags {
            gap: 6px !important;
            margin-bottom: 12px !important;
        }
        .pro-tag {
            font-size: 0.68rem !important;
            padding: 2px 8px !important;
        }
        .pro-tag:nth-child(n+3) {
            display: none !important;
        }
        .pro-card-footer {
            padding-top: 10px !important;
        }
        .pro-action-text {
            font-size: 0.80rem !important;
        }
        .pro-arrow-circle {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.72rem !important;
        }
    }
</style>

<section class="section services-hero-wrapper">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center;">
            <div class="badge services-hero-badge" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px;"><i class="fa-solid fa-clapperboard"></i> Media Production</div>
            <h1 class="section-title services-hero-title" style="font-size: 2.85rem; font-weight: 800; color: #ffffff; letter-spacing: -0.8px; margin-bottom: 12px;">End-to-End <span style="color: #dc2626;">Media Services</span></h1>
            <p class="section-subtitle services-hero-subtitle" style="font-size: 1.05rem; color: #a1a1aa; max-width: 640px; margin: 0 auto;">Commercial film, multi-camera broadcasting, post-production, and motion design.</p>
        </div>

        <div class="services-pro-grid">
            <?php foreach ($servicesList as $s): ?>
                <div class="pro-service-card" onclick="window.location.href='/services/<?php echo htmlspecialchars($s['slug']); ?>'">
                    <div class="pro-card-image-wrap">
                        <div class="pro-card-img-inner">
                            <img src="<?php echo htmlspecialchars($s['image']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>">
                            <div class="pro-card-gradient-overlay"></div>
                            <span class="pro-card-top-badge"><?php echo htmlspecialchars($s['badge']); ?></span>
                        </div>
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
