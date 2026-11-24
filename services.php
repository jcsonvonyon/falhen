<?php
$pageTitle = "Video Production & Media Services | Falhen Productions";
$pageDescription = "Award-winning commercial film production, multi-camera live streaming, editorial photography, and DaVinci Resolve post-production in Chicago & nationwide.";
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

$badgeMap = [
    'video-production'  => 'Commercial & Cinema',
    'live-streaming'    => 'Global 4K Broadcast',
    'photography'       => 'Editorial & Commercial',
    'wedding-events'    => 'Bespoke Cinematography',
    'creative-services' => 'Strategy & Creative Direction',
    'post-production'   => 'DaVinci HDR & Dolby Audio',
    'animation'         => '2D & 3D Motion Graphics'
];

$categoryMap = [
    'video-production'  => 'film',
    'live-streaming'    => 'broadcast',
    'photography'       => 'photo',
    'wedding-events'    => 'event',
    'creative-services' => 'creative',
    'post-production'   => 'post',
    'animation'         => 'post'
];

if (!empty($dynamicServices)) {
    $servicesList = array_map(function($item) use ($badgeMap, $categoryMap) {
        $slug = $item['slug'] ?? 'service';
        return [
            'id'          => $item['id'] ?? 0,
            'slug'        => $slug,
            'title'       => $item['title'] ?? 'Service',
            'icon'        => !empty($item['icon']) ? $item['icon'] : 'fa-solid fa-film',
            'image'       => getCloudinaryUrl($item['image'] ?? ''),
            'badge'       => $badgeMap[$slug] ?? 'Cinema & Media',
            'category'    => $categoryMap[$slug] ?? 'film',
            'desc'        => $item['short_description'] ?? '',
            'detail_desc' => $item['detail_description'] ?? '',
            'tags'        => $item['card_features'] ?? [],
            'features'    => $item['detail_features'] ?? $item['card_features'] ?? []
        ];
    }, $dynamicServices);
} else {
    $servicesList = [
        [
            'id'          => 1,
            'slug'        => 'video-production',
            'title'       => 'Video Production',
            'icon'        => 'fa-solid fa-film',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791294690/falhen/services/b6urkmrrjaqvwtovwtqz.jpg',
            'badge'       => 'Commercial & Cinema',
            'category'    => 'film',
            'desc'        => 'High-impact commercial films, brand documentaries, and 4K/8K cinema capture designed to scale brands.',
            'detail_desc' => 'Our full-service video production team handles every stage of the process — from initial creative briefs and scriptwriting through on-set filming with cinema-grade cameras to the final polished edit.',
            'tags'        => ['Commercial Films', '4K & 8K Cinema', 'Drone Aerials', 'Multi-Camera Rigs'],
            'features'    => ['4K & 8K Cinema Capture', 'Drone Aerial Cinematography', 'Multi-Camera Rigs', 'Scriptwriting & Storyboarding', 'Director & DP on Set', 'Color Grading & VFX Finishing']
        ],
        [
            'id'          => 2,
            'slug'        => 'live-streaming',
            'title'       => 'Live Streaming',
            'icon'        => 'fa-solid fa-tower-broadcast',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791369247/falhen/services/hf1q9mvxywinx4x83spq.jpg',
            'badge'       => 'Global 4K Broadcast',
            'category'    => 'broadcast',
            'desc'        => 'Multi-camera broadcast streaming for global events, keynotes, and concerts with redundant bonded uplinks.',
            'detail_desc' => 'Transform your corporate meetings, product launches, and keynote conferences into immersive digital experiences with end-to-end broadcast engineering.',
            'tags'        => ['Global Broadcast', 'Keynotes & Summits', 'Real-Time Graphics', 'Redundant Uplinks'],
            'features'    => ['YouTube, FB & Custom RTMP', 'Real-Time Lower-Thirds & Bugs', 'Redundant Failover Systems', 'Audience Interaction Tools', '24/7 Technical Monitoring', 'Stream Archive & VOD Delivery']
        ],
        [
            'id'          => 3,
            'slug'        => 'photography',
            'title'       => 'Photography',
            'icon'        => 'fa-solid fa-camera',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791369403/falhen/services/asbk4gks6mneafbizhxc.jpg',
            'badge'       => 'Editorial & Commercial',
            'category'    => 'photo',
            'desc'        => 'Editorial photography, executive headshots, corporate summits, and high-end commercial campaigns.',
            'detail_desc' => 'Every frame is intentionally composed, professionally lit on location or in-studio, and retouched to elevate your visual collateral across digital platforms and print media.',
            'tags'        => ['Commercial Campaigns', 'Executive Portraits', 'Corporate Events', 'Editorial Retouching'],
            'features'    => ['Medium & Full-Frame Systems', 'Studio & On-Location Shoots', 'Product & Lifestyle Campaigns', 'Executive Headshots', 'Event & Conference Coverage', 'Advanced Retouching & Delivery']
        ],
        [
            'id'          => 4,
            'slug'        => 'wedding-events',
            'title'       => 'Wedding & Events',
            'icon'        => 'fa-solid fa-heart',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791369429/falhen/services/vipj4ysloixrwvujfays.jpg',
            'badge'       => 'Bespoke Cinematography',
            'category'    => 'event',
            'desc'        => 'Cinematic wedding films and bespoke event cinematography crafted with storytelling artistry.',
            'detail_desc' => 'From intimate ceremonies to grand destination weddings and corporate galas, our team blends in discretely to capture every emotion and pivotal milestone.',
            'tags'        => ['Cinematic Wedding Films', 'Multi-Angle Coverage', 'Same-Day Highlight Reels', 'Drone Aerials'],
            'features'    => ['Cinematic Storytelling Films', 'Same-Day Highlight Reels', 'Drone Aerial Coverage', 'Multi-Angle Ceremony Capture', 'Documentary-Style Coverage', 'Ultra-HD Master Deliverables']
        ],
        [
            'id'          => 5,
            'slug'        => 'creative-services',
            'title'       => 'Creative Services',
            'icon'        => 'fa-solid fa-lightbulb',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791369463/falhen/services/ra3adgvaiudrvbk9tgqk.jpg',
            'badge'       => 'Strategy & Direction',
            'category'    => 'creative',
            'desc'        => 'Brand storytelling, creative direction, and full-funnel campaign content architecture built for growth.',
            'detail_desc' => 'We combine audience insight, cultural trends, and bold visual direction to build content libraries that turn viewers into committed brand advocates.',
            'tags'        => ['Creative Direction', 'Campaign Strategy', 'Brand Storytelling', 'Visual Identity'],
            'features'    => ['Audience & Market Research', 'Platform-Native Content Architecture', 'Multi-Format Content Kits', 'Brand Guidelines & Treatment', 'Performance Social Cuts', 'Creative A/B Testing']
        ],
        [
            'id'          => 6,
            'slug'        => 'post-production',
            'title'       => 'Post Production',
            'icon'        => 'fa-solid fa-sliders',
            'image'       => 'https://res.cloudinary.com/pnabfi91/image/upload/v1791369500/falhen/services/ey7ezm2nbuyegpeu5fmw.jpg',
            'badge'       => 'DaVinci HDR & Dolby Audio',
            'category'    => 'post',
            'desc'        => 'Precision offline editorial, DaVinci Resolve HDR color grading, motion graphics, and spatial sound design.',
            'detail_desc' => 'Great footage becomes great cinema in the edit suite. Broadcast-grade master finishing delivered on your production timeline.',
            'tags'        => ['Editorial Assembly', 'HDR Color Grading', 'Dolby Audio Mastering', 'Visual Effects'],
            'features'    => ['Premiere Pro & DaVinci Resolve', 'Hollywood-Grade Color Science', 'Dolby Atmos Sound Mixing', 'VFX & Motion Compositing', 'Proxy & Remote Workflows', 'Secure Cloud Asset Delivery']
        ]
    ];
}
?>

<style>
    /* --- Main Page Creative Lighting & Atmosphere --- */
    .services-page-wrap {
        background-color: #040407;
        color: #f4f4f5;
        overflow-x: hidden;
        position: relative;
    }

    /* Ambient atmospheric backdrop glow */
    .services-ambient-glow {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 1400px;
        height: 650px;
        background: radial-gradient(ellipse at 50% 10%, rgba(220, 38, 38, 0.20) 0%, rgba(220, 38, 38, 0.05) 45%, transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    .services-hero-stage {
        position: relative;
        z-index: 1;
        padding-top: 140px;
        padding-bottom: 50px;
        text-align: center;
    }

    .services-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(220, 38, 38, 0.12);
        border: 1px solid rgba(220, 38, 38, 0.35);
        color: #ff5252;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        padding: 6px 18px;
        border-radius: 50px;
        margin-bottom: 20px;
        backdrop-filter: blur(8px);
        box-shadow: 0 4px 18px rgba(220, 38, 38, 0.18);
    }

    .services-main-title {
        font-size: 3.4rem;
        font-weight: 800;
        letter-spacing: -1.2px;
        line-height: 1.1;
        color: #ffffff;
        margin: 0 0 18px 0;
    }

    .services-main-title .gradient-accent {
        background: linear-gradient(135deg, #ffffff 30%, #ef4444 80%, #dc2626 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .services-main-subtext {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #d4d4d8;
        max-width: 680px;
        margin: 0 auto 36px auto;
        font-weight: 400;
    }

    /* Highlight Credentials Strip (Inspired by Lane MP & INDIRAP) */
    .services-cred-strip {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 32px;
        flex-wrap: wrap;
        padding: 16px 24px;
        background: rgba(18, 18, 24, 0.75);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 100px;
        max-width: 900px;
        margin: 0 auto 48px auto;
        backdrop-filter: blur(12px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    .cred-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.88rem;
        color: #e4e4e7;
        font-weight: 600;
    }

    .cred-item i {
        color: #ef4444;
        font-size: 1rem;
    }

    .cred-divider {
        width: 1px;
        height: 20px;
        background: rgba(255, 255, 255, 0.15);
    }

    /* --- Interactive Category Filter Bar --- */
    .services-filter-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 40px;
        overflow-x: auto;
        padding: 6px 12px 14px 12px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .services-filter-nav::-webkit-scrollbar {
        display: none;
    }

    .filter-pill-btn {
        background: rgba(24, 24, 32, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #a1a1aa;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 9px 20px;
        border-radius: 50px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .filter-pill-btn:hover {
        background: rgba(35, 35, 48, 0.95);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.25);
    }

    .filter-pill-btn.active {
        background: #dc2626;
        color: #ffffff;
        border-color: #ef4444;
        box-shadow: 0 4px 20px rgba(220, 38, 38, 0.45);
    }

    /* --- Creative Service Cards Grid --- */
    .services-creative-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
        margin-bottom: 90px;
    }

    .creative-card {
        background: linear-gradient(180deg, rgba(22, 22, 28, 0.88) 0%, rgba(13, 13, 17, 0.96) 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 22px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
        position: relative;
    }

    .creative-card:hover {
        border-color: rgba(220, 38, 38, 0.55);
        transform: translateY(-8px);
        box-shadow: 0 20px 48px rgba(220, 38, 38, 0.25), 0 0 0 1px rgba(220, 38, 38, 0.3);
    }

    /* 16:9 Cinematic Media Frame */
    .card-media-frame {
        position: relative;
        width: 100%;
        height: 230px;
        overflow: hidden;
        background: #000000;
    }

    .card-media-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        display: block;
    }

    .creative-card:hover .card-media-frame img {
        transform: scale(1.07);
    }

    .card-media-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(3, 3, 5, 0.1) 0%, rgba(14, 14, 18, 0.45) 50%, rgba(22, 22, 28, 1) 100%);
        z-index: 1;
    }

    .card-badge-floating {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(14, 14, 18, 0.88);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 50px;
        backdrop-filter: blur(10px);
        z-index: 2;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .card-icon-emblem {
        position: absolute;
        bottom: -22px;
        left: 24px;
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.55);
        z-index: 10;
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .creative-card:hover .card-icon-emblem {
        transform: scale(1.12) rotate(4deg);
        box-shadow: 0 10px 30px rgba(220, 38, 38, 0.75);
    }

    /* Card Content Body */
    .card-content-body {
        padding: 34px 24px 22px 24px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .card-heading-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 12px 0;
        line-height: 1.25;
        letter-spacing: -0.4px;
        transition: color 0.25s ease;
    }

    .creative-card:hover .card-heading-title {
        color: #ff6b6b;
    }

    .card-lead-desc {
        font-size: 0.92rem;
        color: #d4d4d8;
        line-height: 1.6;
        margin: 0 0 20px 0;
        flex-grow: 1;
    }

    /* Tags / Deliverables Chips */
    .card-tags-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }

    .tag-chip {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #e4e4e7;
        font-size: 0.76rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        transition: background 0.2s, border-color 0.2s;
    }

    .creative-card:hover .tag-chip {
        border-color: rgba(255, 255, 255, 0.22);
    }

    /* Interactive Expandable Scope Drawer */
    .card-scope-toggle-btn {
        background: rgba(255, 255, 255, 0.04);
        border: 1px dashed rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        width: 100%;
        padding: 9px 14px;
        color: #d4d4d8;
        font-size: 0.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 18px;
    }

    .card-scope-toggle-btn:hover {
        background: rgba(220, 38, 38, 0.1);
        border-color: rgba(220, 38, 38, 0.45);
        color: #ffffff;
    }

    .card-scope-drawer {
        display: none;
        background: rgba(10, 10, 14, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 18px;
        animation: scopeSlideDown 0.25s ease-out;
    }

    @keyframes scopeSlideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .scope-feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .scope-feature-item {
        font-size: 0.8rem;
        color: #e4e4e7;
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1.4;
    }

    .scope-feature-item i {
        color: #22c55e;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    /* Card Action Footer */
    .card-actions-footer {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-explore-detail {
        flex: 1;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #ffffff;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.25s ease;
    }

    .btn-explore-detail:hover {
        background: #ffffff;
        color: #0f172a;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(255, 255, 255, 0.2);
    }

    .btn-quote-inquire {
        background: #dc2626;
        color: #ffffff;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
        white-space: nowrap;
    }

    .btn-quote-inquire:hover {
        background: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.55);
    }

    /* --- Interactive Production Workflow ("The Falhen Standard") --- */
    .process-workflow-section {
        background: linear-gradient(180deg, rgba(10, 10, 14, 0.95) 0%, rgba(5, 5, 8, 1) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 28px;
        padding: 56px 48px;
        margin-bottom: 90px;
        position: relative;
        overflow: hidden;
    }

    .process-header-center {
        text-align: center;
        max-width: 700px;
        margin: 0 auto 40px auto;
    }

    .process-lead-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(220, 38, 38, 0.12);
        color: #ff5252;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 4px 14px;
        border-radius: 50px;
        margin-bottom: 12px;
    }

    .process-title-h2 {
        font-size: 2.4rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 12px 0;
        letter-spacing: -0.6px;
    }

    .process-subtitle-p {
        font-size: 1rem;
        color: #a1a1aa;
        margin: 0;
        line-height: 1.5;
    }

    .process-tabs-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 30px;
    }

    .process-tab-card {
        background: rgba(20, 20, 26, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 20px 18px;
        cursor: pointer;
        transition: all 0.25s ease;
        text-align: left;
    }

    .process-tab-card:hover {
        background: rgba(30, 30, 40, 0.85);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .process-tab-card.active {
        background: linear-gradient(135deg, rgba(35, 12, 16, 0.9) 0%, rgba(20, 20, 26, 0.95) 100%);
        border-color: #dc2626;
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.25);
    }

    .process-step-num {
        font-size: 0.75rem;
        font-weight: 800;
        color: #ef4444;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
        display: block;
    }

    .process-step-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .process-display-panel {
        background: rgba(14, 14, 20, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 18px;
        padding: 36px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 40px;
        min-height: 220px;
    }

    .process-panel-left {
        flex: 1;
    }

    .process-panel-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 12px 0;
    }

    .process-panel-desc {
        font-size: 0.95rem;
        color: #d4d4d8;
        line-height: 1.6;
        margin: 0 0 20px 0;
    }

    .process-deliverables-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .process-deliverable-chip {
        background: rgba(220, 38, 38, 0.15);
        border: 1px solid rgba(220, 38, 38, 0.35);
        color: #ffffff;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .process-deliverable-chip i {
        color: #ef4444;
    }

    .process-panel-right {
        width: 260px;
        flex-shrink: 0;
        text-align: center;
        background: rgba(22, 22, 30, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 24px;
    }

    .process-panel-metric {
        font-size: 2rem;
        font-weight: 800;
        color: #ef4444;
        display: block;
        margin-bottom: 4px;
    }

    .process-panel-metric-lbl {
        font-size: 0.8rem;
        color: #a1a1aa;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* --- Interactive Scope Estimator Widget (/generative_ui) --- */
    .services-estimator-wrap {
        background: radial-gradient(circle at 50% 0%, rgba(220, 38, 38, 0.18) 0%, rgba(14, 14, 20, 0.95) 70%);
        border: 1px solid rgba(220, 38, 38, 0.4);
        border-radius: 28px;
        padding: 50px 44px;
        margin-bottom: 90px;
        box-shadow: 0 16px 50px rgba(0, 0, 0, 0.6);
        text-align: center;
    }

    .estimator-pill-header {
        font-size: 0.82rem;
        font-weight: 800;
        color: #ef4444;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
        display: block;
    }

    .estimator-main-h2 {
        font-size: 2.3rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 12px 0;
        letter-spacing: -0.6px;
    }

    .estimator-sub-p {
        font-size: 1rem;
        color: #d4d4d8;
        max-width: 600px;
        margin: 0 auto 36px auto;
        line-height: 1.55;
    }

    .estimator-options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        max-width: 900px;
        margin: 0 auto 28px auto;
    }

    .estimator-chip-btn {
        background: rgba(20, 20, 28, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 14px;
        padding: 14px 16px;
        cursor: pointer;
        color: #d4d4d8;
        font-size: 0.9rem;
        font-weight: 700;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        transition: all 0.25s ease;
    }

    .estimator-chip-btn i {
        font-size: 1.3rem;
        color: #ef4444;
        transition: transform 0.25s ease;
    }

    .estimator-chip-btn:hover {
        background: rgba(35, 35, 48, 0.9);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .estimator-chip-btn.selected {
        background: #dc2626;
        border-color: #ef4444;
        color: #ffffff;
        box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
    }

    .estimator-chip-btn.selected i {
        color: #ffffff;
        transform: scale(1.1);
    }

    .estimator-summary-box {
        background: rgba(10, 10, 14, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        padding: 24px 30px;
        max-width: 800px;
        margin: 0 auto 28px auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        text-align: left;
    }

    .estimator-summary-text h4 {
        margin: 0 0 4px 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #ffffff;
    }

    .estimator-summary-text p {
        margin: 0;
        font-size: 0.86rem;
        color: #a1a1aa;
    }

    .btn-estimator-submit {
        background: #dc2626;
        color: #ffffff;
        font-size: 0.95rem;
        font-weight: 800;
        padding: 14px 28px;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.45);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-estimator-submit:hover {
        background: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(220, 38, 38, 0.65);
    }

    /* --- Cross-Link Showcase Footer Banner --- */
    .services-portfolio-banner {
        background: linear-gradient(135deg, rgba(20, 20, 26, 0.9) 0%, rgba(12, 12, 16, 0.95) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 22px;
        padding: 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 90px;
        flex-wrap: wrap;
    }

    .portfolio-banner-content h3 {
        font-size: 1.6rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 8px 0;
    }

    .portfolio-banner-content p {
        font-size: 0.95rem;
        color: #a1a1aa;
        margin: 0;
    }

    .btn-view-portfolio-work {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
        font-size: 0.92rem;
        font-weight: 700;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
    }

    .btn-view-portfolio-work:hover {
        background: #ffffff;
        color: #0f172a;
        transform: translateY(-2px);
    }

    /* --- MOBILE OPTIMIZATION (Responsive Breakpoints) --- */
    @media (max-width: 1024px) {
        .services-creative-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .process-tabs-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .process-display-panel {
            flex-direction: column;
            text-align: left;
            align-items: flex-start;
        }
        .process-panel-right {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .services-hero-stage {
            padding-top: 90px !important;
            padding-bottom: 30px !important;
        }
        .services-main-title {
            font-size: 2.1rem !important;
            line-height: 1.15 !important;
            margin-bottom: 12px !important;
        }
        .services-main-subtext {
            font-size: 0.92rem !important;
            padding: 0 10px !important;
            margin-bottom: 24px !important;
        }
        .services-cred-strip {
            gap: 14px !important;
            padding: 12px 16px !important;
            border-radius: 16px !important;
            margin-bottom: 30px !important;
            justify-content: flex-start !important;
        }
        .cred-item {
            font-size: 0.78rem !important;
        }
        .cred-divider {
            display: none !important;
        }
        .services-filter-nav {
            justify-content: flex-start !important;
            padding-bottom: 10px !important;
            margin-bottom: 24px !important;
        }
        .filter-pill-btn {
            font-size: 0.78rem !important;
            padding: 7px 15px !important;
        }
        .services-creative-grid {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
            margin-bottom: 60px !important;
        }
        .creative-card {
            border-radius: 18px !important;
        }
        .card-media-frame {
            height: 180px !important;
        }
        .card-content-body {
            padding: 28px 18px 18px 18px !important;
        }
        .card-heading-title {
            font-size: 1.25rem !important;
        }
        .card-lead-desc {
            font-size: 0.86rem !important;
            line-height: 1.5 !important;
            margin-bottom: 16px !important;
        }
        .process-workflow-section {
            padding: 36px 20px !important;
            border-radius: 20px !important;
            margin-bottom: 60px !important;
        }
        .process-title-h2 {
            font-size: 1.7rem !important;
        }
        .process-tabs-row {
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
        }
        .process-tab-card {
            padding: 14px 12px !important;
        }
        .process-step-name {
            font-size: 0.88rem !important;
        }
        .process-display-panel {
            padding: 24px 20px !important;
            gap: 24px !important;
        }
        .process-panel-title {
            font-size: 1.25rem !important;
        }
        .process-panel-desc {
            font-size: 0.86rem !important;
        }
        .services-estimator-wrap {
            padding: 36px 20px !important;
            border-radius: 20px !important;
            margin-bottom: 60px !important;
        }
        .estimator-main-h2 {
            font-size: 1.65rem !important;
        }
        .estimator-summary-box {
            flex-direction: column !important;
            align-items: stretch !important;
            text-align: center !important;
            padding: 20px !important;
        }
        .btn-estimator-submit {
            justify-content: center !important;
            width: 100% !important;
        }
        .services-portfolio-banner {
            padding: 28px 20px !important;
            text-align: center !important;
            justify-content: center !important;
            margin-bottom: 60px !important;
        }
        .btn-view-portfolio-work {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>

<div class="services-page-wrap">
    <!-- Ambient Atmospheric Glow -->
    <div class="services-ambient-glow"></div>

    <div class="container" style="position: relative; z-index: 2;">
        <!-- Hero Section -->
        <section class="services-hero-stage">
            <span class="services-badge-pill">
                <i class="fa-solid fa-clapperboard"></i> Full-Service Creative Media Agency
            </span>
            <h1 class="services-main-title">
                End-to-End <span class="gradient-accent">Media Production</span>
            </h1>
            <p class="services-main-subtext">
                From high-stakes commercial film sets and multi-camera live streams to editorial photography and DaVinci Resolve post-production. We turn creative vision into measurable cultural impact.
            </p>

            <!-- Verified Credentials Strip (Lane MP / INDIRAP Style) -->
            <div class="services-cred-strip">
                <div class="cred-item">
                    <i class="fa-solid fa-award"></i>
                    <span>12+ Years Experience</span>
                </div>
                <div class="cred-divider"></div>
                <div class="cred-item">
                    <i class="fa-solid fa-camera-retro"></i>
                    <span>4K &amp; 8K Cinema Capture</span>
                </div>
                <div class="cred-divider"></div>
                <div class="cred-item">
                    <i class="fa-solid fa-tower-broadcast"></i>
                    <span>Broadcast Redundant Uplinks</span>
                </div>
                <div class="cred-divider"></div>
                <div class="cred-item">
                    <i class="fa-solid fa-sliders"></i>
                    <span>DaVinci Resolve HDR Color</span>
                </div>
            </div>

            <!-- Interactive Category Filter Bar -->
            <div class="services-filter-nav" role="tablist" aria-label="Service Categories">
                <button type="button" class="filter-pill-btn active" data-filter="all" onclick="filterServices('all', this)">
                    <i class="fa-solid fa-layer-group"></i> All Capabilities (<?php echo count($servicesList); ?>)
                </button>
                <button type="button" class="filter-pill-btn" data-filter="film" onclick="filterServices('film', this)">
                    <i class="fa-solid fa-film"></i> Commercial Film
                </button>
                <button type="button" class="filter-pill-btn" data-filter="broadcast" onclick="filterServices('broadcast', this)">
                    <i class="fa-solid fa-tower-broadcast"></i> Live Streaming
                </button>
                <button type="button" class="filter-pill-btn" data-filter="photo" onclick="filterServices('photo', this)">
                    <i class="fa-solid fa-camera"></i> Photography
                </button>
                <button type="button" class="filter-pill-btn" data-filter="event" onclick="filterServices('event', this)">
                    <i class="fa-solid fa-heart"></i> Events &amp; Weddings
                </button>
                <button type="button" class="filter-pill-btn" data-filter="post" onclick="filterServices('post', this)">
                    <i class="fa-solid fa-sliders"></i> Post &amp; Color
                </button>
            </div>
        </section>

        <!-- Service Cards Grid -->
        <div class="services-creative-grid" id="servicesGrid">
            <?php foreach ($servicesList as $s): ?>
                <div class="creative-card service-filterable" data-category="<?php echo htmlspecialchars($s['category']); ?>">
                    <!-- 16:9 Media Frame -->
                    <div class="card-media-frame">
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($s['image'], 'f_auto,q_auto:good,w_700,h_400,c_fill')); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" loading="lazy">
                        <div class="card-media-overlay"></div>
                        <span class="card-badge-floating"><?php echo htmlspecialchars($s['badge']); ?></span>
                        <div class="card-icon-emblem">
                            <i class="<?php echo htmlspecialchars($s['icon']); ?>"></i>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-content-body">
                        <h2 class="card-heading-title"><?php echo htmlspecialchars($s['title']); ?></h2>
                        <p class="card-lead-desc"><?php echo htmlspecialchars($s['desc']); ?></p>

                        <!-- Deliverables Tags -->
                        <div class="card-tags-cloud">
                            <?php foreach ($s['tags'] as $tag): ?>
                                <span class="tag-chip"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Expandable Scope Drawer Toggle -->
                        <?php if (!empty($s['features'])): ?>
                            <button type="button" class="card-scope-toggle-btn" onclick="toggleCardScope(this, event)">
                                <span><i class="fa-solid fa-list-check" style="color: #ef4444; margin-right: 6px;"></i> What's Included &amp; Scope</span>
                                <i class="fa-solid fa-chevron-down arrow-icon" style="transition: transform 0.25s ease;"></i>
                            </button>
                            <div class="card-scope-drawer">
                                <ul class="scope-feature-list">
                                    <?php foreach ($s['features'] as $feat): ?>
                                        <li class="scope-feature-item">
                                            <i class="fa-solid fa-check"></i>
                                            <span><?php echo htmlspecialchars($feat); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Dual Action Conversion CTAs -->
                        <div class="card-actions-footer">
                            <a href="/services/<?php echo htmlspecialchars($s['slug']); ?>" class="btn-explore-detail">
                                <span>Explore Service</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="/contact?service=<?php echo urlencode($s['title']); ?>" class="btn-quote-inquire" title="Inquire about <?php echo htmlspecialchars($s['title']); ?>">
                                <span>Inquire</span>
                                <i class="fa-solid fa-paper-plane"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- The Falhen Standard: Interactive 4-Stage Production Workflow -->
        <section class="process-workflow-section">
            <div class="process-header-center">
                <span class="process-lead-pill"><i class="fa-solid fa-diagram-project"></i> How We Work</span>
                <h2 class="process-title-h2">The Falhen Standard</h2>
                <p class="process-subtitle-p">A battle-tested production methodology ensuring cinematic storytelling, transparent communication, and zero technical surprises.</p>
            </div>

            <!-- Clickable Process Tabs -->
            <div class="process-tabs-row" role="tablist">
                <div class="process-tab-card active" onclick="switchProcessTab(0, this)">
                    <span class="process-step-num">Stage 01</span>
                    <h3 class="process-step-name">Discovery &amp; Brief</h3>
                </div>
                <div class="process-tab-card" onclick="switchProcessTab(1, this)">
                    <span class="process-step-num">Stage 02</span>
                    <h3 class="process-step-name">Cinema Capture</h3>
                </div>
                <div class="process-tab-card" onclick="switchProcessTab(2, this)">
                    <span class="process-step-num">Stage 03</span>
                    <h3 class="process-step-name">HDR Post &amp; Color</h3>
                </div>
                <div class="process-tab-card" onclick="switchProcessTab(3, this)">
                    <span class="process-step-num">Stage 04</span>
                    <h3 class="process-step-name">Multi-Platform Delivery</h3>
                </div>
            </div>

            <!-- Dynamic Display Panel -->
            <div class="process-display-panel" id="processDisplayPanel">
                <div class="process-panel-left">
                    <h3 class="process-panel-title" id="processPanelTitle">01 Discovery, Concept &amp; Scripting</h3>
                    <p class="process-panel-desc" id="processPanelDesc">We begin by diagnosing your brand narrative, audience expectations, and core objective. Our directors draft comprehensive shot lists, storyboards, creative treatments, and scheduling call sheets before the camera rolls.</p>
                    <div class="process-deliverables-wrap" id="processDeliverablesWrap">
                        <span class="process-deliverable-chip"><i class="fa-solid fa-file-pen"></i> Creative Brief &amp; Script</span>
                        <span class="process-deliverable-chip"><i class="fa-solid fa-map-location-dot"></i> Location Scouting</span>
                        <span class="process-deliverable-chip"><i class="fa-solid fa-list-ol"></i> Storyboards &amp; Shot Lists</span>
                        <span class="process-deliverable-chip"><i class="fa-solid fa-calendar-days"></i> Master Call Sheet</span>
                    </div>
                </div>
                <div class="process-panel-right">
                    <span class="process-panel-metric" id="processPanelMetric">1-2 Wks</span>
                    <span class="process-panel-metric-lbl" id="processPanelMetricLbl">Typical Pre-Production</span>
                </div>
            </div>
        </section>

        <!-- Interactive Production Scope & Estimator Widget (/generative_ui) -->
        <section class="services-estimator-wrap" id="scopeEstimator">
            <span class="estimator-pill-header"><i class="fa-solid fa-calculator"></i> Interactive Scope Planner</span>
            <h2 class="estimator-main-h2">Plan Your Production Scope</h2>
            <p class="estimator-sub-p">Select the services you need to immediately configure a tailor-made production package and jump directly to booking.</p>

            <div class="estimator-options-grid">
                <div class="estimator-chip-btn selected" data-service="Video Production" onclick="toggleEstimatorChip(this)">
                    <i class="fa-solid fa-film"></i>
                    <span>Commercial Film</span>
                </div>
                <div class="estimator-chip-btn" data-service="Live Streaming" onclick="toggleEstimatorChip(this)">
                    <i class="fa-solid fa-tower-broadcast"></i>
                    <span>Live Broadcast</span>
                </div>
                <div class="estimator-chip-btn" data-service="Photography" onclick="toggleEstimatorChip(this)">
                    <i class="fa-solid fa-camera"></i>
                    <span>Photography</span>
                </div>
                <div class="estimator-chip-btn" data-service="Wedding &amp; Events" onclick="toggleEstimatorChip(this)">
                    <i class="fa-solid fa-heart"></i>
                    <span>Events &amp; Gala</span>
                </div>
                <div class="estimator-chip-btn" data-service="Post Production" onclick="toggleEstimatorChip(this)">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Post &amp; Color</span>
                </div>
            </div>

            <!-- Dynamic Estimated Summary Box -->
            <div class="estimator-summary-box">
                <div class="estimator-summary-text">
                    <h4 id="estimatorSummaryTitle">Selected: Commercial Film Production</h4>
                    <p id="estimatorSummaryDesc">Includes multi-camera 4K cinema capture, director &amp; DP crew, aerial drone cinematography, and DaVinci Resolve post-production master deliverables.</p>
                </div>
                <a href="/contact?service=Video+Production" class="btn-estimator-submit" id="estimatorSubmitBtn">
                    <span>Continue to Inquiry</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </section>

        <!-- Cross-Link Showcase Footer Banner -->
        <div class="services-portfolio-banner">
            <div class="portfolio-banner-content">
                <h3>See 12+ Years of Production in Action</h3>
                <p>Browse our client archives, concert cinematography, brand films, and proof deliveries.</p>
            </div>
            <a href="/portfolio" class="btn-view-portfolio-work">
                <span>View Full Portfolio Showcase</span>
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
</div>

<!-- Interactive Page Scripts -->
<script>
    // 1. Client-Side Category Filtering
    function filterServices(category, btn) {
        document.querySelectorAll('.filter-pill-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const cards = document.querySelectorAll('.service-filterable');
        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            if (category === 'all' || cardCat === category) {
                card.style.display = 'flex';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 10);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 250);
            }
        });
    }

    // 2. Expandable Scope Drawer
    function toggleCardScope(btn, e) {
        if (e) e.stopPropagation();
        const drawer = btn.nextElementSibling;
        const arrow = btn.querySelector('.arrow-icon');
        if (!drawer) return;

        if (drawer.style.display === 'block') {
            drawer.style.display = 'none';
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        } else {
            drawer.style.display = 'block';
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        }
    }

    // 3. Process Stages Interactive Tab Switcher
    const processData = [
        {
            title: "01 Discovery, Concept & Scripting",
            desc: "We diagnose your brand narrative, target audience, and campaign objective. Our directors draft comprehensive shot lists, storyboards, creative treatments, and scheduling call sheets before the camera rolls.",
            deliverables: [
                { icon: "fa-solid fa-file-pen", label: "Creative Brief & Script" },
                { icon: "fa-solid fa-map-location-dot", label: "Location Scouting" },
                { icon: "fa-solid fa-list-ol", label: "Storyboards & Shot Lists" },
                { icon: "fa-solid fa-calendar-days", label: "Master Call Sheet" }
            ],
            metric: "1-2 Wks",
            metricLbl: "Pre-Production Phase"
        },
        {
            title: "02 Cinema Field Production",
            desc: "Our on-set crew deploys cinema-grade systems (RED, Sony FX, Arri), prime glass, specialized lighting, multi-channel wireless audio, and licensed drone aerials to capture pristine raw media.",
            deliverables: [
                { icon: "fa-solid fa-video", label: "4K & 8K Cinema Capture" },
                { icon: "fa-solid fa-helicopter", label: "FAA Part 107 Drone Ops" },
                { icon: "fa-solid fa-lightbulb", label: "Aputure & ARRI Lighting" },
                { icon: "fa-solid fa-microphone-lines", label: "32-Bit Float Audio" }
            ],
            metric: "1-5 Days",
            metricLbl: "Field Production"
        },
        {
            title: "03 Post-Production, Color & Sound",
            desc: "Footage is ingested into our high-speed SAN edit suites. We craft the offline assembly, perform precision editorial cuts, grade in DaVinci Resolve with calibrated HDR monitors, and mix immersive Dolby sound.",
            deliverables: [
                { icon: "fa-solid fa-scissors", label: "Offline & Online Assembly" },
                { icon: "fa-solid fa-palette", label: "DaVinci Resolve HDR Color" },
                { icon: "fa-solid fa-volume-high", label: "Dolby 5.1 & Spatial Audio" },
                { icon: "fa-solid fa-wand-magic-sparkles", label: "Motion Titles & 3D VFX" }
            ],
            metric: "1-3 Wks",
            metricLbl: "Finishing & Revisions"
        },
        {
            title: "04 Multi-Platform Delivery & Archiving",
            desc: "We export uncompressed ProRes 422 HQ masters, web-optimized 4K files, 9:16 vertical cuts for TikTok/Reels, and store raw camera masters in our redundant cold-storage archive for future re-cuts.",
            deliverables: [
                { icon: "fa-solid fa-file-video", label: "ProRes 422 HQ Masters" },
                { icon: "fa-solid fa-mobile-screen-button", label: "9:16 Social Re-Cuts" },
                { icon: "fa-solid fa-cloud-arrow-down", label: "Client Portal Access" },
                { icon: "fa-solid fa-vault", label: "Permanent Cloud Backup" }
            ],
            metric: "24-48 Hrs",
            metricLbl: "Final Master Delivery"
        }
    ];

    function switchProcessTab(idx, tabEl) {
        document.querySelectorAll('.process-tab-card').forEach(t => t.classList.remove('active'));
        if (tabEl) tabEl.classList.add('active');

        const data = processData[idx];
        if (!data) return;

        const titleEl = document.getElementById('processPanelTitle');
        const descEl = document.getElementById('processPanelDesc');
        const wrapEl = document.getElementById('processDeliverablesWrap');
        const metricEl = document.getElementById('processPanelMetric');
        const metricLblEl = document.getElementById('processPanelMetricLbl');

        if (titleEl) titleEl.textContent = data.title;
        if (descEl) descEl.textContent = data.desc;
        if (metricEl) metricEl.textContent = data.metric;
        if (metricLblEl) metricLblEl.textContent = data.metricLbl;

        if (wrapEl) {
            wrapEl.innerHTML = data.deliverables.map(d => `
                <span class="process-deliverable-chip">
                    <i class="${d.icon}"></i> ${d.label}
                </span>
            `).join('');
        }
    }

    // 4. Interactive Scope Estimator Chip Selector
    const estimatorInfo = {
        'Video Production': {
            desc: "Includes multi-camera 4K cinema capture, director & DP crew, aerial drone cinematography, and DaVinci Resolve post-production master deliverables.",
            contactUrl: "/contact?service=Video+Production"
        },
        'Live Streaming': {
            desc: "Turnkey multi-camera live broadcast, real-time graphic overlays, bonded cellular uplinks, and immediate high-definition stream recording archives.",
            contactUrl: "/contact?service=Live+Streaming"
        },
        'Photography': {
            desc: "On-location and studio editorial photography, professional lighting rigs, corporate summit coverage, and commercial retouching.",
            contactUrl: "/contact?service=Photography"
        },
        'Wedding & Events': {
            desc: "Cinematic full-day multi-angle capture, drone aerials, same-day highlight reels, and archival delivery of speeches and celebrations.",
            contactUrl: "/contact?service=Wedding+%26+Events"
        },
        'Post Production': {
            desc: "Precision editorial assembly, 32-bit float DaVinci Resolve HDR color grading, Dolby spatial sound design, and custom motion graphics.",
            contactUrl: "/contact?service=Post+Production"
        }
    };

    function toggleEstimatorChip(btn) {
        document.querySelectorAll('.estimator-chip-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');

        const serviceName = btn.getAttribute('data-service');
        const titleEl = document.getElementById('estimatorSummaryTitle');
        const descEl = document.getElementById('estimatorSummaryDesc');
        const submitBtn = document.getElementById('estimatorSubmitBtn');

        const info = estimatorInfo[serviceName] || estimatorInfo['Video Production'];

        if (titleEl) titleEl.textContent = 'Selected: ' + serviceName;
        if (descEl) descEl.textContent = info.desc;
        if (submitBtn) submitBtn.href = info.contactUrl;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
