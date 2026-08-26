<?php
// faq.php - Frequently Asked Questions page with Answer Engine Optimization (AEO) and Schema.org FAQPage JSON-LD
$pageTitle = "Frequently Asked Questions — Video Production & Services | Falhen Media";

// 20 FAQs categorized across 5 areas matching falhen.com/faq
$faqCategories = [
    'all' => 'All (20)',
    'Services' => 'Services (5)',
    'Process' => 'Process (4)',
    'Pricing' => 'Pricing (4)',
    'Logistics' => 'Logistics (4)',
    'Deliverables' => 'Deliverables (3)'
];

$faqs = [
    // Services (5)
    [
        'id' => 1,
        'cat' => 'Services',
        'q' => 'What video production services does Falhen Media offer?',
        'a' => 'We offer end-to-end video production including commercial films, brand videos, corporate documentaries, event coverage, live streaming, post-production, animation and motion graphics, photography, and content strategy. Every project is handled by a dedicated team of producers, cinematographers, editors, and creative strategists.'
    ],
    [
        'id' => 2,
        'cat' => 'Services',
        'q' => 'Do you provide commercial photography alongside video production?',
        'a' => 'Yes, we offer full-scale commercial, editorial, portrait, and product photography. Our team can operate hybrid photo/video shoots to maximize cost efficiency and ensure visual consistency across all media channels.'
    ],
    [
        'id' => 3,
        'cat' => 'Services',
        'q' => 'Can Falhen handle live streaming for global keynotes and events?',
        'a' => 'Absolutely. We provide low-latency 4K multi-camera live broadcast setups with redundant SRT/RTMP streaming, real-time graphics switching, on-screen lower thirds, and bonded satellite/cellular internet connections.'
    ],
    [
        'id' => 4,
        'cat' => 'Services',
        'q' => 'What types of animation and motion graphics do you produce?',
        'a' => 'We create 2D and 3D motion design, kinetic typography, broadcast package stingers, UI product animations, and 3D product CAD renderings using Cinema 4D, After Effects, and Blender.'
    ],
    [
        'id' => 5,
        'cat' => 'Services',
        'q' => 'Do you offer standalone post-production and color grading services?',
        'a' => 'Yes. Clients can send raw footage for offline editing, DaVinci Resolve HDR color grading, Dolby Atmos audio mixing, sound design, and master formatting (DCP, ProRes, H.264).'
    ],

    // Process (4)
    [
        'id' => 6,
        'cat' => 'Process',
        'q' => 'How does the video production process work at Falhen Media?',
        'a' => 'Our process follows 4 structured phases: 1) Concept & Strategy (creative brief, scriptwriting, storyboards), 2) Pre-Production (location scouting, casting, scheduling), 3) Production (on-set filming with cinema gear), and 4) Post-Production (editing, color grading, sound design, and revisions).'
    ],
    [
        'id' => 7,
        'cat' => 'Process',
        'q' => 'What is the typical timeline for completing a video project?',
        'a' => 'Commercials and brand videos typically take 2-4 weeks from initial kickoff to final delivery. Rapid turnarounds (under 7 days) are available for time-sensitive event highlight reels and social media content.'
    ],
    [
        'id' => 8,
        'cat' => 'Process',
        'q' => 'How many rounds of revisions are included in a project?',
        'a' => 'Standard packages include 2 to 3 structured rounds of feedback and revisions during the rough cut and fine cut stages via our collaborative video review portal.'
    ],
    [
        'id' => 9,
        'cat' => 'Process',
        'q' => 'Can clients be involved during on-set filming and editing?',
        'a' => 'Yes! Clients can attend on-set filming in person or monitor real-time wireless video feeds remotely. During editing, we provide timestamped review links for seamless collaborative feedback.'
    ],

    // Pricing (4)
    [
        'id' => 10,
        'cat' => 'Pricing',
        'q' => 'How much does a video production project cost?',
        'a' => 'Project budgets vary based on shoot duration, crew size, equipment, talent, and post-production complexity. Short brand promos start around $5,000–$10,000, while full-scale commercial campaigns range from $15,000 to $50,000+. Contact us for a customized 24-hour proposal.'
    ],
    [
        'id' => 11,
        'cat' => 'Pricing',
        'q' => 'What payment terms do you offer for projects?',
        'a' => 'Our standard structure is 50% deposit upon project agreement and signature, and 50% upon final master video delivery. Custom milestone payment structures are available for enterprise retainers.'
    ],
    [
        'id' => 12,
        'cat' => 'Pricing',
        'q' => 'Are travel and location licensing costs included in quotes?',
        'a' => 'All potential travel expenses, per diems, location permits, and talent licensing fees are itemized transparently in your initial proposal with zero hidden charges.'
    ],
    [
        'id' => 13,
        'cat' => 'Pricing',
        'q' => 'Do you offer monthly creative retainers for recurring content?',
        'a' => 'Yes, we offer monthly retainers for brands requiring continuous video content, short-form reels, and ongoing social media marketing assets.'
    ],

    // Logistics (4)
    [
        'id' => 14,
        'cat' => 'Logistics',
        'q' => 'Where is Falhen Media located, and do you travel internationally?',
        'a' => 'Our headquarters is located in Oakbrook, IL (Greater Chicago Area). We frequently film on location across North America, Europe, Africa, and Asia, having completed projects in over 40 countries.'
    ],
    [
        'id' => 15,
        'cat' => 'Logistics',
        'q' => 'What cinema equipment and camera systems do you use?',
        'a' => 'We shoot on cinema-grade camera systems including RED V-Raptor, Sony FX6/FX9, Canon Cinema EOS, Zeiss/Cooke prime lenses, DJI Ronin gimbals, lighting trucks, and FAA-certified Part 107 drones.'
    ],
    [
        'id' => 16,
        'cat' => 'Logistics',
        'q' => 'How do you handle backup and data redundancy on set?',
        'a' => 'Safety is paramount. All footage is dual-card recorded on set and backed up to triple RAID storage arrays (onsite + cloud offsite backup) before cards are formatted.'
    ],
    [
        'id' => 17,
        'cat' => 'Logistics',
        'q' => 'Are your drone operators certified and insured?',
        'a' => 'Yes, all our aerial drone cinematographers hold FAA Part 107 certifications and are backed by full $5M aviation commercial liability insurance.'
    ],

    // Deliverables (3)
    [
        'id' => 18,
        'cat' => 'Deliverables',
        'q' => 'In what file formats and resolutions do you deliver final videos?',
        'a' => 'We deliver in native 4K/8K ProRes 422 HQ, H.264/H.265 MP4 formats, square (1:1), vertical (9:16) for TikTok/Reels, and Cinema DCP masters for theatrical screening.'
    ],
    [
        'id' => 19,
        'cat' => 'Deliverables',
        'q' => 'Who owns the final copyright and raw footage of the shoot?',
        'a' => 'You own full commercial rights to all delivered final master videos. Raw unedited camera files (B-roll/takes) can also be transferred upon request.'
    ],
    [
        'id' => 20,
        'cat' => 'Deliverables',
        'q' => 'How long do you archive completed project files and footage?',
        'a' => 'We maintain master project archives for a minimum of 5 years on cold LTO tape and cloud storage, allowing for future cutdowns, updates, or re-edits whenever needed.'
    ]
];

// Generate JSON-LD Schema.org FAQPage for Answer Engine Optimization (AEO)
$schemaEntities = [];
foreach ($faqs as $faq) {
    $schemaEntities[] = [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['a']
        ]
    ];
}
$jsonLdSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $schemaEntities
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Frequently asked questions about Falhen Media video production, commercial photography, pricing, production timeline, equipment, and deliverables.">

    <!-- Answer Engine Optimization (AEO) Structured Data Schema -->
    <script type="application/ld+json">
        <?php echo json_encode($jsonLdSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>

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

        /* Top Nav Bar */
        .faq-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .faq-nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .faq-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .faq-back-link:hover {
            color: #ffffff;
        }

        .btn-contact-top {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.25s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .btn-contact-top:hover {
            background: #ef4444;
        }

        /* Hero Stage */
        .faq-hero-section {
            position: relative;
            padding: 80px 0 60px 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.8) 0%, rgba(3, 3, 5, 0.96) 100%),
                        radial-gradient(circle at 50% 20%, rgba(220, 38, 38, 0.2) 0%, transparent 60%),
                        url('/assets/img/portfolio/portfolio-hero.jpg') center/cover no-repeat;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .faq-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .help-center-badge {
            display: inline-block;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #ff4d4d;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 5px 18px;
            border-radius: 50px;
            margin-bottom: 20px;
        }

        .faq-hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1.5px;
            margin: 0 0 16px 0;
            line-height: 1.1;
        }

        .faq-hero-title span {
            color: #ef4444;
        }

        .faq-hero-subtitle {
            font-size: 1.1rem;
            color: #a1a1aa;
            line-height: 1.65;
            max-width: 620px;
            margin: 0 auto 40px auto;
        }

        /* Controls Section */
        .faq-controls-wrap {
            padding: 30px 0 20px 0;
        }

        .faq-search-bar {
            width: 100%;
            position: relative;
            margin-bottom: 20px;
        }

        .faq-search-bar i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #71717a;
            font-size: 0.95rem;
        }

        .faq-search-input {
            width: 100%;
            background: rgba(18, 18, 24, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 14px 20px 14px 48px;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.25s ease;
            box-sizing: border-box;
        }

        .faq-search-input:focus {
            border-color: rgba(220, 38, 38, 0.5);
        }

        .faq-filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .faq-tab-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #d4d4d8;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .faq-tab-btn.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .showing-questions-text {
            color: #71717a;
            font-size: 0.88rem;
            margin: 24px 0 20px 0;
            font-weight: 500;
        }

        /* FAQ List Cards */
        .faq-list-wrap {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 80px;
        }

        .faq-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px 28px;
            transition: all 0.35s ease;
            cursor: pointer;
        }

        .faq-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .faq-card.open {
            border-color: rgba(220, 38, 38, 0.4);
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.12);
        }

        .faq-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .faq-title-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .faq-num-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.15);
            color: #ef4444;
            font-size: 0.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .faq-question-text {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1.3;
        }

        .faq-toggle-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a1a1aa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .faq-card.open .faq-toggle-btn {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            transform: rotate(180deg);
        }

        .faq-answer-body {
            display: none;
            padding-top: 18px;
            margin-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #a1a1aa;
            font-size: 0.98rem;
            line-height: 1.7;
            padding-left: 44px;
        }

        .faq-card.open .faq-answer-body {
            display: block;
        }

        /* Still Have Questions Callout */
        .faq-cta-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.9) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.25);
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            margin-bottom: 80px;
        }

        .faq-cta-title {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
        }

        .faq-cta-desc {
            color: #a1a1aa;
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto 28px auto;
            line-height: 1.6;
        }

        .btn-faq-cta {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 13px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-faq-cta:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Footer */
        .faq-footer-bar {
            padding: 18px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .faq-footer-links {
            display: flex;
            gap: 24px;
        }

        .faq-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .faq-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .faq-hero-title {
                font-size: 2.8rem;
            }
            .faq-answer-body {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="faq-nav-bar">
        <div class="faq-nav-container">
            <a href="/" class="faq-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <a href="/contact.php" class="btn-contact-top">Contact Us</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="faq-hero-section">
        <div class="faq-container">
            <div class="help-center-badge">Help Center</div>
            <h1 class="faq-hero-title">Frequently Asked <span>Questions</span></h1>
            <p class="faq-hero-subtitle">Everything you need to know about our video production services, pricing, and process.</p>
        </div>
    </section>

    <!-- Main FAQ Section -->
    <main class="faq-container">
        
        <!-- Controls Bar -->
        <div class="faq-controls-wrap">
            <div class="faq-search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="faq-search-input" class="faq-search-input" placeholder="Search questions...">
            </div>

            <div class="faq-filter-tabs">
                <button class="faq-tab-btn active" data-filter="all">All</button>
                <button class="faq-tab-btn" data-filter="Services">Services (5)</button>
                <button class="faq-tab-btn" data-filter="Process">Process (4)</button>
                <button class="faq-tab-btn" data-filter="Pricing">Pricing (4)</button>
                <button class="faq-tab-btn" data-filter="Logistics">Logistics (4)</button>
                <button class="faq-tab-btn" data-filter="Deliverables">Deliverables (3)</button>
            </div>
        </div>

        <div class="showing-questions-text" id="faq-counter">Showing 20 questions</div>

        <!-- FAQ Cards Accordion List -->
        <div class="faq-list-wrap" id="faq-list">
            <?php foreach ($faqs as $index => $f): ?>
                <article class="faq-card <?php echo $index === 0 ? 'open' : ''; ?>" data-cat="<?php echo htmlspecialchars($f['cat']); ?>" data-q="<?php echo htmlspecialchars(strtolower($f['q'] . ' ' . $f['a'])); ?>" onclick="toggleFaqCard(this)">
                    <div class="faq-header-row">
                        <div class="faq-title-left">
                            <div class="faq-num-badge"><?php echo $f['id']; ?></div>
                            <h2 class="faq-question-text"><?php echo htmlspecialchars($f['q']); ?></h2>
                        </div>
                        <div class="faq-toggle-btn">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>

                    <div class="faq-answer-body">
                        <p style="margin:0;"><?php echo htmlspecialchars($f['a']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Still Have Questions Callout -->
        <div class="faq-cta-card">
            <h3 class="faq-cta-title">Still have questions?</h3>
            <p class="faq-cta-desc">Cannot find the answer you are looking for? Reach out to our team directly and we will assist you.</p>
            <a href="/contact.php" class="btn-faq-cta"><i class="fa-regular fa-envelope"></i> Get in Touch</a>
        </div>

        <!-- Footer Bar -->
        <div class="faq-footer-bar">
            <div>© 2026 Falhen Media. All rights reserved.</div>
            <div class="faq-footer-links">
                <a href="/">Home</a>
                <a href="/about.php">About</a>
                <a href="/services.php">Services</a>
                <a href="/portfolio.php">Portfolio</a>
                <a href="/careers.php">Careers</a>
                <a href="/contact.php">Contact</a>
            </div>
        </div>

    </main>

    <!-- Scripts -->
    <script>
        function toggleFaqCard(card) {
            card.classList.toggle('open');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const filterBtns = document.querySelectorAll('.faq-tab-btn');
            const searchInput = document.getElementById('faq-search-input');
            const faqCards = document.querySelectorAll('.faq-card');
            const counterDisplay = document.getElementById('faq-counter');

            let currentFilter = 'all';
            let currentSearch = '';

            function updateFaqs() {
                let count = 0;
                faqCards.forEach(card => {
                    const cat = card.getAttribute('data-cat');
                    const text = card.getAttribute('data-q');

                    const matchesCat = (currentFilter === 'all' || cat.toLowerCase() === currentFilter.toLowerCase());
                    const matchesSearch = (!currentSearch || text.includes(currentSearch));

                    if (matchesCat && matchesSearch) {
                        card.style.display = 'block';
                        count++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (counterDisplay) {
                    counterDisplay.textContent = `Showing ${count} questions`;
                }
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    updateFaqs();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    currentSearch = e.target.value.toLowerCase().trim();
                    updateFaqs();
                });
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
