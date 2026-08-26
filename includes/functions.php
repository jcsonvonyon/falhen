<?php
/**
 * Global Helper Functions
 * Falhen Media
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/cloudinary_helper.php';

/**
 * Sanitize string input for XSS prevention
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Send JSON Response
 */
function sendJSONResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => $message
    ], $extra));
    exit;
}

/**
 * Check if Admin/Staff is Logged In
 */
function isAdminLoggedIn() {
    if (!empty($_SESSION['admin_user_id'])) {
        return true;
    }

    // Auto-login via remember_me cookie if set
    if (!empty($_COOKIE['falhen_remember'])) {
        $rememberToken = $_COOKIE['falhen_remember'];
        if (function_exists('getDBConnection')) {
            $pdo = getDBConnection();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `remember_token` = ? LIMIT 1");
                    $stmt->execute([$rememberToken]);
                    $user = $stmt->fetch();
                    if ($user) {
                        session_regenerate_id(true);
                        $_SESSION['admin_user_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_role'] = $user['role'] ?? 'Staff';
                        return true;
                    }
                } catch (Exception $e) {}
            }
        }
    }
    return false;
}

/**
 * Require Admin Login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Clear Admin Session and Cookies
 */
function clearAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear DB remember token if logged in
    if (!empty($_SESSION['admin_user_id']) && function_exists('getDBConnection')) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE `admin_users` SET `remember_token` = NULL WHERE `id` = ?");
                $stmt->execute([$_SESSION['admin_user_id']]);
            } catch (Exception $e) {}
        }
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    if (isset($_COOKIE['falhen_remember'])) {
        setcookie('falhen_remember', '', time() - 3600, '/', '', false, true);
    }
    session_destroy();
}

/**
 * Get Team Members Data
 */
function getTeamMembers() {
    $settings = getSiteSettings();
    $rawItems = $settings['team_items'] ?? [];

    if (empty($rawItems)) {
        $rawItems = [
            [
                'id' => 1,
                'number' => '01',
                'name' => 'Henry Falonipe',
                'role' => 'Creative Director',
                'department' => 'Creative',
                'location' => 'Chicago',
                'experience' => '15+ years at Falhen',
                'image' => '/assets/img/team/team_henry.png',
                'bio' => "Visionary Creative Director and founder of Falhen, where he leads high-end cinematic storytelling and media production. With over 15 years of experience, Henry has transformed a boutique studio into a recognized production house.",
                'skills' => ['Creative Direction', 'Brand Strategy', 'Concert Development']
            ],
            [
                'id' => 2,
                'number' => '02',
                'name' => 'Kingsley Falonipe',
                'role' => 'Operations Specialist',
                'department' => 'Operations',
                'location' => 'Lagos',
                'experience' => '4+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713360/falhen/team/ftmzlwwv1szlyurijyno.png',
                'bio' => "Bridging the gap between creative vision and seamless execution. With a proactive and friendly approach, Kingsley ensures that complex production workflows run with precision.",
                'skills' => ['Productions', 'Operational Management', 'Client Relations']
            ],
            [
                'id' => 3,
                'number' => '03',
                'name' => 'Lisa Okoli',
                'role' => 'Client Partnership & Head of Marketing',
                'department' => 'Creative',
                'location' => 'London',
                'experience' => '5+ years at Falhen',
                'image' => '/assets/img/portfolio/portfolio_wedding.png',
                'bio' => "Leading brand partnerships, campaign strategy, and client communications. Lisa connects world-class brands with Falhen's production engine.",
                'skills' => ['Client Partnerships', 'Marketing Strategy', 'Brand Development']
            ],
            [
                'id' => 4,
                'number' => '04',
                'name' => 'Mojisola Emjay',
                'role' => 'Resource Manager',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '10+ years at Falhen',
                'image' => '/assets/img/portfolio/portfolio_commercial.png',
                'bio' => "With over a decade of experience in the HR industry, Mojisola has a proven track record of enhancing operational efficiency and resource allocation.",
                'skills' => ['Resource Management', 'Talent Scheduling', 'Budget Optimization']
            ],
            [
                'id' => 5,
                'number' => '05',
                'name' => 'Daniel Ifeoluwa',
                'role' => 'Graphics Designer',
                'department' => 'Creative',
                'location' => 'Lagos',
                'experience' => '3+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714147/falhen/team/xba4ep38ewfdq3chfx1h.jpg',
                'bio' => "Combining technical precision with creative artistry to produce immersive visual assets that elevate brand storytelling.",
                'skills' => ['Graphics Design', 'Visual Assets', 'Brand Design']
            ],
            [
                'id' => 6,
                'number' => '06',
                'name' => 'Victoria Opemipo',
                'role' => 'Social Media Manager',
                'department' => 'Creative',
                'location' => 'Lagos',
                'experience' => '3+ years at Falhen',
                'image' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714208/falhen/team/ri0fc0rdmjfvyzx1lihn.jpg',
                'bio' => "Specialized in crafting digital narratives that amplify brand identity and foster genuine community engagement.",
                'skills' => ['Social Strategy', 'Audience Engagement Specialist', 'Visual Storytelling for Social']
            ],
            [
                'id' => 7,
                'number' => '07',
                'name' => 'Micheal Otuwho',
                'role' => 'Content Manager',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '4+ years at Falhen',
                'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=600&auto=format&fit=crop',
                'bio' => "Specialize in identifying emerging market trends and translating them into actionable growth opportunities for client brands.",
                'skills' => ['Content Strategy', 'Market Analysis', 'Growth Campaigns']
            ],
            [
                'id' => 8,
                'number' => '08',
                'name' => 'Ligali Oluwatosin',
                'role' => 'IT Support Specialist',
                'department' => 'Strategy',
                'location' => 'Lagos',
                'experience' => '5+ years at Falhen',
                'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=600&auto=format&fit=crop',
                'bio' => "Approach technical infrastructure with a proactive, solutions-driven mindset. My mission is to ensure smooth studio operations.",
                'skills' => ['IT Support', 'Technical Infrastructure', 'Systems Security']
            ]
        ];
    }

    $formatted = [];
    foreach ($rawItems as $idx => $item) {
        $id = (int)($item['id'] ?? ($idx + 1));
        $num = !empty($item['number']) ? $item['number'] : sprintf("%02d", $id);
        $skills = is_array($item['skills'] ?? null) ? $item['skills'] : array_filter(array_map('trim', explode(',', $item['skills'] ?? '')));
        $formatted[$id] = [
            'id' => $id,
            'number' => $num,
            'name' => $item['name'] ?? '',
            'role' => $item['role'] ?? '',
            'department' => $item['department'] ?? 'Creative',
            'location' => $item['location'] ?? '',
            'experience' => $item['experience'] ?? '',
            'image' => !empty($item['image']) ? $item['image'] : '/assets/img/team/team_henry.png',
            'bio' => $item['bio'] ?? '',
            'skills' => $skills
        ];
    }
    return $formatted;
}

function getTeamMemberById($id) {
    $members = getTeamMembers();
    $id = (int)$id;
    return $members[$id] ?? $members[1];
}

/**
 * Extract 11-character YouTube Video ID from any URL or ID string
 */
function extractYouTubeId($urlOrId) {
    $urlOrId = trim((string)$urlOrId);
    if (empty($urlOrId)) return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $urlOrId)) {
        return $urlOrId;
    }
    if (preg_match('/(?:v=|\/embed\/|\/watch\?v=|youtu\.be\/|\/v\/)([a-zA-Z0-9_-]{11})/i', $urlOrId, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Get YouTube Thumbnail URL for a video ID or URL
 */
function getYouTubeThumbnailUrl($urlOrId, $quality = 'hqdefault') {
    $id = extractYouTubeId($urlOrId);
    if (!empty($id)) {
        return "https://i.ytimg.com/vi/{$id}/{$quality}.jpg";
    }
    $url = trim((string)$urlOrId);
    if (preg_match('/^https?:\/\//i', $url) || preg_match('/^\//', $url)) {
        return $url;
    }
    return '/assets/img/hero.jpg';
}

/**
 * Get Site Settings from config/settings.json
 */
function getSiteSettings() {
    $file = __DIR__ . '/../config/settings.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [
        'hero_direct_video_url' => '',
        'hero_youtube_bg' => '',
        'hero_vimeo_bg' => '',
        'hero_poster_image' => 'https://storage.readdy-site.link/project_files/6c8ddf7b-6d98-436f-a5d0-36fd1d9fec3f/hero-fallback.jpg',
        'showreel_youtube_id' => 'Tf8rNMZ-Bw0',
        'hero_title' => 'Cinematic Storytelling & High-Impact Media Production',
        'hero_subtitle' => 'Falhen Media is a premier full-service media production studio crafting high-end corporate films, commercials, live broadcasts, and brand anthems.'
    ];
}

/**
 * Get Single Setting Value
 */
function getSiteSetting($key, $default = '') {
    $settings = getSiteSettings();
    return $settings[$key] ?? $default;
}

/**
 * Save Site Settings array to config/settings.json
 */
function saveSiteSettings($newSettings) {
    $file = __DIR__ . '/../config/settings.json';
    $current = getSiteSettings();
    $updated = array_merge($current, $newSettings);
    file_put_contents($file, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $updated;
}

/**
 * Get Dynamic Master Services Repository
 */
function getServicesRepo() {
    $settings = getSiteSettings();
    $items = $settings['services_items'] ?? [];
    
    if (empty($items)) {
        $items = [
            [
                'id' => 1,
                'slug' => 'video-production',
                'title' => 'Video Production',
                'icon' => 'fa-solid fa-film',
                'short_description' => 'Professional video production services from concept to completion, including scripting, filming, and editing with state-of-the-art equipment.',
                'detail_description' => 'Our full-service video production team handles every stage of the process — from initial creative briefs and scriptwriting through on-set filming with cinema-grade cameras to the final polished edit.',
                'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80',
                'card_features' => ['Corporate videos & commercials', '4K & 8K video capture', 'Drone cinematography', 'Multi-camera productions'],
                'detail_features' => ['4K & 8K Cinema Capture', 'Drone Aerial Cinematography', 'Multi-Camera Rigs', 'Scriptwriting & Storyboarding']
            ]
        ];
    }
    
    $repo = [];
    foreach ($items as $item) {
        $title = $item['title'] ?? 'Service';
        $slug = !empty($item['slug']) ? $item['slug'] : strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $img = getCloudinaryUrl($item['image'] ?? '');
        
        $cardFeatures = $item['card_features'] ?? [];
        $detailFeatures = $item['detail_features'] ?? [];
        
        $included = [];
        foreach ($detailFeatures as $df) {
            $included[] = ['icon' => 'fa-solid fa-check', 'text' => $df];
        }

        $repo[$slug] = [
            'id' => $item['id'] ?? 1,
            'slug' => $slug,
            'title' => $title,
            'icon' => !empty($item['icon']) ? $item['icon'] : 'fa-solid fa-film',
            'subtitle' => $item['short_description'] ?? '',
            'short_description' => $item['short_description'] ?? '',
            'detail_description' => $item['detail_description'] ?? '',
            'hero_image' => $img,
            'image' => $img,
            'about' => !empty($item['detail_description']) ? $item['detail_description'] : ($item['short_description'] ?? ''),
            'included' => $included,
            'specialisations' => $cardFeatures,
            'card_features' => $cardFeatures,
            'detail_features' => $detailFeatures,
            'badge' => 'Cinema & TV',
            'desc' => $item['short_description'] ?? '',
            'tags' => $cardFeatures
        ];
    }
    return $repo;
}

/**
 * Get Dynamic Testimonials Repository
 */
function getTestimonialsRepo() {
    $settings = getSiteSettings();
    $items = $settings['testimonials_items'] ?? [];

    if (empty($items)) {
        return [
            [
                'id' => 1,
                'name' => 'Marcus Webb',
                'role' => 'Head of Marketing',
                'company' => 'RedBull EMEA',
                'project' => 'Energy Unleashed Campaign',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'Falhen Media delivered a campaign that exceeded every benchmark we set. The team understood our brand DNA from day one and translated it into visuals that literally stopped people mid-scroll. Our engagement numbers tripled.'
            ],
            [
                'id' => 2,
                'name' => 'Serena Okafor',
                'role' => 'Creative Director',
                'company' => 'Nike Africa',
                'project' => 'Sprint Series Brand Film',
                'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => "Working with Falhen was genuinely one of the best creative decisions we made this year. They don't just shoot video — they engineer emotion. The final film had our entire team in tears. In the best way."
            ],
            [
                'id' => 3,
                'name' => 'James Thornton',
                'role' => 'VP of Content',
                'company' => 'HBO Original',
                'project' => 'Dark Horizons Series Teaser',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'The teaser Falhen produced for Dark Horizons generated more pre-release buzz than anything we\'d done in five years. Cinematic, punchy, and perfectly on brief. They are our go-to production partner now.'
            ],
            [
                'id' => 4,
                'name' => 'Priya Nair',
                'role' => 'Brand Manager',
                'company' => 'Sony Music',
                'project' => 'Next Generation Product Launch',
                'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'From briefing to final delivery, the Falhen team was exceptional. They pushed back on ideas that weren\'t strong enough and brought solutions we hadn\'t imagined. The launch film is still being shared organically two months later.'
            ],
            [
                'id' => 5,
                'name' => 'Luca Ferrari',
                'role' => 'Event Director',
                'company' => 'Ferrari Private Events',
                'project' => 'Annual Gala Highlight Reel',
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'The same-day highlight reel they produced for our annual gala was breathtaking. Guests were watching it at the closing dinner. I have never seen a production team move that fast without sacrificing a single frame of quality.'
            ],
            [
                'id' => 6,
                'name' => 'Aisha Diallo',
                'role' => 'Founder',
                'company' => 'Diallo Cosmetics',
                'project' => 'Brand Identity Video Series',
                'avatar' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?auto=format&fit=crop&w=150&q=80',
                'rating' => 5,
                'quote' => 'As a growing brand, we needed content that could compete with the big players. Falhen made us look like a global company on a startup budget. Our Instagram reels from that shoot still perform better than anything we\'ve posted since.'
            ]
        ];
    }

    return $items;
}

/**
 * Convert Google Drive share link to direct viewable image URL or format drive URL
 */
function convertGoogleDriveUrlToDirect($url) {
    $url = trim((string)$url);
    if (empty($url)) return '';
    if (preg_match('/(?:file\/d\/|id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        $fileId = $matches[1];
        return "https://lh3.googleusercontent.com/d/{$fileId}";
    }
    return $url;
}

/**
 * Get Dynamic Master Portfolio Repository
 */
function getPortfolioRepo() {
    $settings = getSiteSettings();
    $items = $settings['portfolio_items'] ?? [];

    if (empty($items)) {
        $items = [
            [
                'id' => 1,
                'title' => 'King David - Grad Shoot',
                'category' => 'Portrait',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'King David',
                'location' => 'Chicago',
                'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=800&auto=format&fit=crop',
                'desc' => 'Commemorative academic graduate portrait session capturing milestone achievements with editorial studio lighting.',
                'photosCount' => 30
            ],
            [
                'id' => 2,
                'title' => "Halima's 40th Birthday Shoot",
                'category' => 'Birthday',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'Halima Ogunde',
                'location' => 'Lagos',
                'image' => '/assets/img/portfolio/portfolio_halima.png',
                'desc' => 'A golden-hour pre-wedding & birthday session set in a lush garden estate. Every frame tells the story of elegance, captured with cinematic warmth.',
                'photosCount' => 30
            ],
            [
                'id' => 3,
                'title' => 'Demola Violinist - Tour',
                'category' => 'Wedding',
                'media_type' => 'photo',
                'featured' => true,
                'client' => 'Demola The Violinist',
                'location' => 'London',
                'image' => '/assets/img/portfolio/portfolio_wedding.png',
                'desc' => 'Live concert and tour performance capturing electric stage energy, dramatic lighting, and musical virtuosity.',
                'photosCount' => 30
            ],
            [
                'id' => 4,
                'title' => 'Dex Million - Surprise 50th Birthday',
                'category' => 'Birthday',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Dex Million',
                'location' => 'Atlanta',
                'image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop',
                'desc' => 'Surprise milestone celebration featuring high-profile guests, live music, and candid moment capture.',
                'photosCount' => 30
            ],
            [
                'id' => 5,
                'title' => 'Jussoul — Winery Music Tour',
                'category' => 'Event',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Jussoul',
                'location' => 'California',
                'image' => '/assets/img/portfolio/portfolio_award.png',
                'desc' => 'Acoustic live sessions and winery tour performance captured in intimate 4K resolution.',
                'photosCount' => 30
            ],
            [
                'id' => 6,
                'title' => 'Halima Ogunde',
                'category' => 'Event',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Halima Ogunde',
                'location' => 'Lagos',
                'image' => '/assets/img/portfolio/portfolio_halima.png',
                'desc' => 'High-fashion portrait and luxury event coverage.',
                'photosCount' => 30
            ],
            [
                'id' => 7,
                'title' => 'Darasinmi - Studio Session',
                'category' => 'Portrait',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Darasinmi',
                'location' => 'Oakbrook',
                'image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=800&auto=format&fit=crop',
                'desc' => 'Creative indoor studio portrait session with modern color tones and dramatic contrast.',
                'photosCount' => 30
            ],
            [
                'id' => 8,
                'title' => 'Darasinmi - 10th Birthday',
                'category' => 'Event',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Darasinmi Family',
                'location' => 'Chicago',
                'image' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?q=80&w=800&auto=format&fit=crop',
                'desc' => 'Vibrant milestone 10th birthday party photography and celebratory family moments.',
                'photosCount' => 30
            ],
            [
                'id' => 9,
                'title' => 'Adetutu - Burial Repass',
                'category' => 'Event',
                'media_type' => 'photo',
                'featured' => false,
                'client' => 'Adetutu Family',
                'location' => 'Lagos',
                'image' => '/assets/img/portfolio/portfolio_commercial.png',
                'desc' => 'Traditional cultural celebration and remembrance service photography.',
                'photosCount' => 30
            ],
            [
                'id' => 10,
                'title' => 'Luxe Fashion Week - Aftermovie',
                'category' => 'Commercial',
                'media_type' => 'video',
                'duration' => '02:30',
                'featured' => true,
                'client' => 'Falhen Runway',
                'location' => 'Paris',
                'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=800&auto=format&fit=crop',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'desc' => 'High-energy fashion week highlight reel featuring 4K slow-motion captures, backstage moments, and custom sound design.'
            ],
            [
                'id' => 11,
                'title' => 'Demola Live in Concert — Tour Film',
                'category' => 'Music Video',
                'media_type' => 'video',
                'duration' => '04:15',
                'featured' => true,
                'client' => 'Demola The Violinist',
                'location' => 'London',
                'image' => '/assets/img/portfolio/portfolio_wedding.png',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'desc' => 'Cinematic live performance video capturing multi-camera stage coverage, dramatic arena lighting, and musical virtuosity.'
            ],
            [
                'id' => 12,
                'title' => 'The Ogunde Royal Wedding Film',
                'category' => 'Wedding',
                'media_type' => 'video',
                'duration' => '05:40',
                'featured' => true,
                'client' => 'Halima & Tunde Ogunde',
                'location' => 'Cape Town',
                'image' => '/assets/img/portfolio/portfolio_halima.png',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                'desc' => 'Luxury destination wedding mini-documentary with aerial drone shots, emotional vows, and cinematic color grading.'
            ],
            [
                'id' => 13,
                'title' => 'Apex Tech Global Summit — 4K Stream',
                'category' => 'Event',
                'media_type' => 'video',
                'duration' => '03:10',
                'featured' => false,
                'client' => 'Apex Global',
                'location' => 'Tokyo',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=800&auto=format&fit=crop',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyflights.mp4',
                'desc' => 'Multi-camera live broadcast setup and highlight recap reel for an international corporate keynote.'
            ],
            [
                'id' => 14,
                'title' => 'Jussoul - Acoustic Sessions (Reel)',
                'category' => 'Reels',
                'media_type' => 'video',
                'duration' => '00:58',
                'featured' => false,
                'client' => 'Jussoul Media',
                'location' => 'Los Angeles',
                'image' => '/assets/img/portfolio/portfolio_award.png',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
                'desc' => 'Vertical 9:16 social media teaser reel optimized for Instagram and TikTok with dynamic typography.'
            ],
            [
                'id' => 15,
                'title' => 'Urban Stories — Short Documentary',
                'category' => 'Documentary',
                'media_type' => 'video',
                'duration' => '06:20',
                'featured' => false,
                'client' => 'Falhen Originals',
                'location' => 'Lagos',
                'image' => '/assets/img/portfolio/portfolio_commercial.png',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
                'desc' => 'Award-winning short documentary exploring street art, culture, and creative passion in modern urban cities.'
            ],
            [
                'id' => 16,
                'title' => 'Tech Innovation Summit 2024',
                'category' => 'Corporate',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'TechCorp International',
                'location' => 'Chicago',
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/tech-summit-2024',
                'desc' => 'Global technology conference keynote recording, stage visual direction, and corporate livestream coverage.'
            ],
            [
                'id' => 17,
                'title' => 'Luxury Brand Fashion Show',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Elegance Fashion House',
                'location' => 'Paris & Lagos',
                'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/fashion-show-2024',
                'desc' => 'High-fashion runway showcase, 4K slow-motion captures, and backstage model portraiture.'
            ],
            [
                'id' => 18,
                'title' => 'Electric Vehicle Launch Campaign',
                'category' => 'Commercials',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'EcoMotors',
                'location' => 'Los Angeles',
                'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/ecomotors-campaign',
                'desc' => 'Commercial launch campaign featuring cinematic night automotive tracking shots and digital ad spots.'
            ],
            [
                'id' => 19,
                'title' => 'Fitness Brand Social Campaign',
                'category' => 'Social',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'FitLife Athletics',
                'location' => 'New York',
                'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/fitlife-social',
                'desc' => 'High-intensity athletic promotional reels and vertical social video asset package.'
            ],
            [
                'id' => 20,
                'title' => 'SoundWave Music Festival',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'SoundWave Festival',
                'location' => 'Lagos',
                'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/soundwave-fest',
                'desc' => 'Multi-stage festival coverage, crowd energy captures, and mainstage headliner recap video.'
            ],
            [
                'id' => 21,
                'title' => 'Metro Morning News Show',
                'category' => 'Broadcast',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Metro News Network',
                'location' => 'London',
                'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/metro-news-show',
                'desc' => 'State-of-the-art studio set design, multi-camera live broadcast infrastructure, and news graphics package.'
            ],
            [
                'id' => 22,
                'title' => 'Coastal Wedding — Nichelle & Michael',
                'category' => 'Wedding',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Nichelle & Michael',
                'location' => 'Cape Town',
                'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/nichelle-michael-wedding',
                'desc' => 'Luxury oceanfront destination wedding documentary film and comprehensive photo archive.'
            ],
            [
                'id' => 23,
                'title' => 'Artisan Coffee — Brand Story',
                'category' => 'Commercials',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Roast & Ritual',
                'location' => 'Seattle',
                'image' => 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/roast-ritual-story',
                'desc' => 'Cinematic brand documentary profiling craft coffee roasters and ethical bean sourcing.'
            ],
            [
                'id' => 24,
                'title' => 'Falhen Brand Campaign 2026',
                'category' => 'Corporate',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Falhen Global',
                'location' => 'Lagos & Chicago',
                'image' => '/assets/img/portfolio/portfolio_award.png',
                'gdrive_url' => 'https://drive.google.com/drive/folders/falhen-brand-2026',
                'desc' => 'Complete visual rebrand, corporate media campaign, and multimedia brand guidelines production.'
            ],
            [
                'id' => 25,
                'title' => 'Demola World Tour Production',
                'category' => 'Events',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Demola Music',
                'location' => 'London & New York',
                'image' => '/assets/img/portfolio/portfolio_wedding.png',
                'gdrive_url' => 'https://drive.google.com/drive/folders/demola-tour-2026',
                'desc' => 'Stage LED visual curation, concert live recording setup, and international promotional assets.'
            ],
            [
                'id' => 26,
                'title' => 'Urban Culture Short Film',
                'category' => 'Documentary',
                'media_type' => 'project',
                'featured' => true,
                'client' => 'Falhen Originals',
                'location' => 'Lagos',
                'image' => '/assets/img/portfolio/portfolio_commercial.png',
                'gdrive_url' => 'https://drive.google.com/drive/folders/urban-culture-doc',
                'desc' => 'Award-winning short documentary exploring street art, urban music, and creative youth culture.'
            ],
            [
                'id' => 27,
                'title' => 'Global Apex Summit 2026',
                'category' => 'Broadcast',
                'media_type' => 'project',
                'featured' => false,
                'client' => 'Apex Tech',
                'location' => 'Tokyo',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=800&auto=format&fit=crop',
                'gdrive_url' => 'https://drive.google.com/drive/folders/apex-summit-2026',
                'desc' => 'International tech conference hybrid broadcast stream and multi-lingual video production.'
            ]
        ];
    }

    // Auto-repair & enforce unique IDs
    $seenIds = [];
    $hasDuplicates = false;
    $maxId = 0;
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
        if (empty($id) || isset($seenIds[$id])) {
            $hasDuplicates = true;
        } else {
            $seenIds[$id] = true;
        }
    }

    if ($hasDuplicates) {
        $used = [];
        foreach ($items as &$item) {
            $id = (int)($item['id'] ?? 0);
            if (empty($id) || isset($used[$id])) {
                $maxId++;
                $item['id'] = $maxId;
                $used[$maxId] = true;
            } else {
                $used[$id] = true;
            }
        }
        unset($item);
        $settings['portfolio_items'] = $items;
        saveSiteSettings($settings);
    }

    return $items;
}

/**
 * Get Team Locations list
 */
function getTeamLocations() {
    $settings = getSiteSettings();
    $defaults = ['USA', 'Nigeria', 'Canada', 'UK'];
    $saved = $settings['team_locations'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get Team Departments list
 */
function getTeamDepartments() {
    $settings = getSiteSettings();
    $defaults = ['Creative', 'Operations', 'Strategy'];
    $saved = $settings['team_departments'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get Blog Repository (Dynamic array from settings.json)
 */
function getBlogRepo() {
    $settings = getSiteSettings();
    $items = $settings['blog_items'] ?? null;

    if (empty($items) || !is_array($items)) {
        $items = [
            1 => [
                'id' => 1,
                'title' => '10 Essential Tips for Creating Engaging Social Media Videos',
                'category' => 'Social Media',
                'date' => 'March 12, 2024',
                'read_time' => '6 min read',
                'excerpt' => 'Learn the secrets to creating scroll-stopping social media content that captures attention and drives engagement.',
                'author' => 'Michael Chen',
                'role' => 'Creative Director',
                'image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop',
                'content' => 'Creating engaging video content for social media platforms requires a strategic blend of visual storytelling, hook optimization, and crisp pacing. In this guide, we break down 10 actionable strategies to elevate your brand\'s video performance across Instagram Reels, TikTok, YouTube Shorts, and LinkedIn.',
                'featured' => true
            ],
            2 => [
                'id' => 2,
                'title' => 'Behind the Scenes: Our Award-Winning Commercial Production',
                'category' => 'Case Study',
                'date' => 'March 8, 2024',
                'read_time' => '10 min read',
                'excerpt' => 'Take an exclusive look at the making of our latest award-winning commercial. Discover the creative process, technical challenges...',
                'author' => 'Emily Rodriguez',
                'role' => 'Lead Producer',
                'image' => '/assets/img/services/service_video.png',
                'content' => 'Step inside the production studio as we pull back the curtain on our award-winning commercial campaign. From concept development and storyboarding to multi-camera lighting and sound design, here is how our creative team brought cinematic vision to life.',
                'featured' => false
            ],
            3 => [
                'id' => 3,
                'title' => 'Mastering Color Grading: A Comprehensive Guide',
                'category' => 'Tutorial',
                'date' => 'March 5, 2024',
                'read_time' => '12 min read',
                'excerpt' => 'Dive deep into the art and science of color grading. Learn professional techniques to enhance mood, create visual consistency...',
                'author' => 'David Park',
                'role' => 'Colorist & Editor',
                'image' => '/assets/img/services/service_post.png',
                'content' => 'Color grading is the secret sauce behind cinematic film production. Understand color spaces, tone curves, LUT application, and skin tone protection to create distinct visual identities for your commercial projects.',
                'featured' => false
            ],
            4 => [
                'id' => 4,
                'title' => 'The Power of Storytelling in Brand Videos',
                'category' => 'Strategy',
                'date' => 'March 1, 2024',
                'read_time' => '7 min read',
                'excerpt' => 'Discover how compelling narratives can transform your brand videos from forgettable to unforgettable. Storytelling framework...',
                'author' => 'Jessica Thompson',
                'role' => 'Brand Strategist',
                'image' => '/assets/img/services/service_creative.png',
                'content' => 'Audiences remember stories, not sales pitches. Learn how to structure brand video narratives around human emotion, tension, and resolution to build lasting brand affinity.',
                'featured' => false
            ],
            5 => [
                'id' => 5,
                'title' => 'Choosing the Right Equipment for Your Video Project',
                'category' => 'Equipment',
                'date' => 'February 28, 2024',
                'read_time' => '9 min read',
                'excerpt' => 'Navigate the world of video production equipment with confidence. From cameras and lenses to lighting and audio...',
                'author' => 'Alex Johnson',
                'role' => 'Technical Director',
                'image' => '/assets/img/services/service_photo.png',
                'content' => 'Selecting the right camera package, prime lenses, wireless audio, and lighting modifiers can make or break production efficiency. Here is our recommended equipment guide for various budget tiers.',
                'featured' => false
            ],
            6 => [
                'id' => 6,
                'title' => 'The Future of Video Production: Trends to Watch in 2024',
                'category' => 'Industry Trends',
                'date' => 'March 15, 2024',
                'read_time' => '8 min read',
                'excerpt' => 'Explore the latest trends shaping the video production industry, from AI-powered editing tools to immersive 360-degree...',
                'author' => 'Sarah Mitchell',
                'role' => 'Innovation Lead',
                'image' => '/assets/img/services/service_anim.png',
                'content' => 'Artificial intelligence, virtual LED volumes, real-time rendering, and interactive broadcasting are reshaping the production landscape. Discover the key innovations driving the next era of filmmaking.',
                'featured' => false
            ]
        ];

        $settings['blog_items'] = array_values($items);
        saveSiteSettings($settings);
    }

    $keyed = [];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }
    return $keyed;
}

/**
 * Get Blog Categories list
 */
function getBlogCategories() {
    $settings = getSiteSettings();
    $defaults = ['Social Media', 'Case Study', 'Tutorial', 'Strategy', 'Equipment', 'Industry Trends'];
    $saved = $settings['blog_categories'] ?? [];

    if (!is_array($saved)) {
        $saved = [];
    }

    $all = array_values(array_unique(array_filter(array_map('trim', array_merge($defaults, $saved)))));
    return $all;
}

/**
 * Get single blog post by ID
 */
function getBlogPostById($id) {
    $repo = getBlogRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Get Careers & Job Openings Repository
 */
function getCareersRepo() {
    $settings = getSiteSettings();
    $items = $settings['job_openings'] ?? [];

    if (empty($items) || !is_array($items)) {
        $items = [
            [
                'id' => 1,
                'title' => 'Senior Video Producer',
                'dept' => 'Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Hybrid',
                'salary' => '$85,000 - $120,000',
                'overview' => 'Lead end-to-end commercial video productions from concept through final delivery. You will manage shoots, direct talent, and collaborate with editors and strategists to deliver premium content for top-tier brands.',
                'responsibilities' => "<ul><li>Lead and manage 8-12 video productions per month across commercial, brand, and social formats</li><li>Develop creative treatments, shot lists, and production schedules</li><li>Direct on-set talent, crew, and client stakeholders</li><li>Collaborate with post-production team on rough cuts, revisions, and final delivery</li><li>Manage production budgets and vendor relationships</li></ul>",
                'requirements' => "<ul><li>5+ years of professional video production experience in commercial or agency setting</li><li>Proficiency with professional cinema cameras (RED, Sony FX, Canon C-series)</li><li>Strong understanding of lighting, composition, and visual storytelling</li><li>Experience managing shoots with 5+ crew members</li></ul>",
                'benefits' => "<ul><li>Health, dental, and vision insurance</li><li>401(k) with 4% company match</li><li>Unlimited PTO (minimum 15 days encouraged)</li><li>Annual equipment stipend ($2,500)</li></ul>"
            ],
            [
                'id' => 2,
                'title' => 'Motion Graphics Designer',
                'dept' => 'Post Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Remote',
                'salary' => '$65,000 - $95,000',
                'overview' => 'Craft 2D/3D visual effects, kinetic typography, lower thirds, and animated brand identity assets for high-impact commercial campaigns.',
                'responsibilities' => "<ul><li>Design dynamic 2D and 3D motion graphic packages for broadcast and digital platforms</li><li>Develop 3D product animations and procedural render assets in Cinema 4D / Blender</li><li>Collaborate with video editors to integrate visual effects seamlessly</li></ul>",
                'requirements' => "<ul><li>3+ years of professional motion design experience</li><li>Expert mastery of Adobe After Effects, Illustrator, Photoshop, and Cinema 4D / Blender</li><li>Strong portfolio showcasing 3D animation, kinetic typography, and VFX compositing</li></ul>",
                'benefits' => "<ul><li>Health, dental, and vision insurance</li><li>Remote work flexibility</li><li>$2,000 annual home studio hardware budget</li></ul>"
            ],
            [
                'id' => 3,
                'title' => 'Content Strategist',
                'dept' => 'Strategy',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Hybrid',
                'salary' => '$70,000 - $100,000',
                'overview' => 'Develop multi-platform video distribution strategies, editorial storyboards, social media campaigns, and audience engagement architectures for global client brands.',
                'responsibilities' => "<ul><li>Formulate end-to-end video content strategies aligned with client KPIs</li><li>Conduct audience research, competitive analysis, and content performance audits</li><li>Write compelling creative briefs, story scripts, and video treatments</li></ul>",
                'requirements' => "<ul><li>4+ years experience in brand strategy, digital agency, or media planning</li><li>Deep understanding of YouTube, Instagram Reels, TikTok, and LinkedIn video algorithms</li></ul>",
                'benefits' => "<ul><li>Competitive health and dental coverage</li><li>Flexible hybrid work options</li><li>$3,000 annual learning & development stipend</li></ul>"
            ],
            [
                'id' => 4,
                'title' => 'Wedding & Events Cinematographer',
                'dept' => 'Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / On-Location',
                'salary' => '$60,000 - $85,000',
                'overview' => 'Capture luxury weddings, galas, and multi-cam live events with artistic framing, gimbal movements, and emotional depth.',
                'responsibilities' => "<ul><li>Operate main camera rigs on multi-camera event shoots</li><li>Set up portable lighting set-ups and wireless audio systems on location</li></ul>",
                'requirements' => "<ul><li>3+ years experience filming high-end weddings and live events</li><li>Mastery of camera stabilization (DJI Ronin), prime lenses, and audio recorders</li></ul>",
                'benefits' => "<ul><li>Travel per diem and luxury accommodations for destination events</li><li>Full health insurance & equipment maintenance support</li></ul>"
            ],
            [
                'id' => 5,
                'title' => 'Post-Production Editor',
                'dept' => 'Post Production',
                'type' => 'Full-time',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL / Remote',
                'salary' => '$55,000 - $80,000',
                'overview' => 'Cut documentary highlights, brand reels, and short-form social videos in Premiere Pro and DaVinci Resolve with precise pacing, sound design, and color grading.',
                'responsibilities' => "<ul><li>Assemble rough and fine cuts from multi-camera footage</li><li>Perform audio cleanup, sound design, and music mixing</li></ul>",
                'requirements' => "<ul><li>3+ years experience in video editing and post-production</li><li>Mastery of Adobe Premiere Pro, DaVinci Resolve, and Audition</li></ul>",
                'benefits' => "<ul><li>Remote editing rig provided by company</li><li>Flexible work hours and health benefits</li></ul>"
            ],
            [
                'id' => 6,
                'title' => 'Production Assistant (Intern)',
                'dept' => 'Production',
                'type' => 'Internship',
                'status' => 'open',
                'posted' => 'Recently',
                'location' => 'Oakbrook, IL',
                'salary' => '$18 - $22 / hour',
                'overview' => 'Assist on set with camera gear setup, lighting, media offloading, production logistics, and post-production organization.',
                'responsibilities' => "<ul><li>Support camera operators, gaffers, and sound engineers on set</li><li>Assist with equipment transport, inventory, and location setup</li></ul>",
                'requirements' => "<ul><li>Enrolled in or recent graduate of Film, Media, or Communications program</li><li>Strong passion for cinema, eagerness to learn, and reliable work ethic</li></ul>",
                'benefits' => "<ul><li>Direct mentorship from industry-leading producers and cinematographers</li><li>Paid internship with path to full-time career roles</li></ul>"
            ]
        ];

        $settings['job_openings'] = array_values($items);
        saveSiteSettings($settings);
    }

    $keyed = [];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }
    return $keyed;
}

/**
 * Get single career position by ID
 */
function getCareerById($id) {
    $repo = getCareersRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Get Job Applications Repository
 */
function getJobApplicationsRepo() {
    $settings = getSiteSettings();
    $items = $settings['job_applications'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }
    return array_values($items);
}

/**
 * Get Brand Logos Repository (Trusted by World-Class Brands)
 */
function getBrandLogosRepo() {
    $settings = getSiteSettings();
    $items = $settings['brand_logos_items'] ?? [];

    if (empty($items) || !is_array($items)) {
        $items = [
            ['id' => 1, 'name' => 'Universal', 'icon' => 'fa-solid fa-globe', 'image' => '', 'visible' => true],
            ['id' => 2, 'name' => 'ESPN', 'icon' => 'fa-solid fa-trophy', 'image' => '', 'visible' => true],
            ['id' => 3, 'name' => 'Warner', 'icon' => 'fa-solid fa-video', 'image' => '', 'visible' => true],
            ['id' => 4, 'name' => 'RedBull', 'icon' => 'fa-solid fa-mug-hot', 'image' => '', 'visible' => true],
            ['id' => 5, 'name' => 'Netflix', 'icon' => 'fa-solid fa-film', 'image' => '', 'visible' => true],
            ['id' => 6, 'name' => 'Nike', 'icon' => 'fa-solid fa-person-running', 'image' => '', 'visible' => true],
            ['id' => 7, 'name' => 'Spotify', 'icon' => 'fa-brands fa-spotify', 'image' => '', 'visible' => true],
            ['id' => 8, 'name' => 'Apple', 'icon' => 'fa-brands fa-apple', 'image' => '', 'visible' => true],
            ['id' => 9, 'name' => 'HBO', 'icon' => 'fa-solid fa-tv', 'image' => '', 'visible' => true],
            ['id' => 10, 'name' => 'Adidas', 'icon' => 'fa-solid fa-shirt', 'image' => '', 'visible' => true],
        ];

        $settings['brand_logos_items'] = array_values($items);
        saveSiteSettings($settings);
    }

    $keyed = [];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }
    return $keyed;
}

/**
 * Get Admin User Profile Settings
 */
function getAdminUserProfile() {
    $settings = getSiteSettings();
    $profile = $settings['admin_profile'] ?? [];

    if (empty($profile) || !is_array($profile)) {
        $profile = [
            'full_name'     => 'Henry Falonipe',
            'username'      => 'admin',
            'email'         => 'admin@falhen.com',
            'role'          => 'Creative Director & Administrator',
            'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg',
            'bio'           => 'Visionary Creative Director and founder of Falhen Media.',
            'password_hash' => password_hash('Password123#', PASSWORD_DEFAULT)
        ];

        $settings['admin_profile'] = $profile;
        saveSiteSettings($settings);
    }

    return $profile;
}

/**
 * Save Admin User Profile & Sync Active Session
 */
function saveAdminUserProfile($data) {
    $current = getAdminUserProfile();
    $updated = array_merge($current, $data);

    saveSiteSettings(['admin_profile' => $updated]);

    // Sync session variables
    $_SESSION['admin_username']  = $updated['username'];
    $_SESSION['admin_email']     = $updated['email'];
    $_SESSION['admin_role']      = $updated['role'];
    $_SESSION['admin_avatar']    = $updated['avatar'];
    $_SESSION['admin_full_name'] = $updated['full_name'];

    return $updated;
}

/**
 * Get Staff Accounts Repository (Includes entire company staff)
 */
function getStaffAccountsRepo() {
    $settings = getSiteSettings();
    $items = $settings['staff_accounts'] ?? [];

    if (empty($items) || !is_array($items)) {
        $defaultPasswordHash = password_hash('Password123#', PASSWORD_DEFAULT);
        $items = [
            [
                'id'            => 1,
                'full_name'     => 'Henry Falonipe',
                'username'      => 'admin',
                'email'         => 'admin@falhen.com',
                'role'          => 'Super Admin',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg',
                'created_at'    => '2024-01-10',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 2,
                'full_name'     => 'Kingsley Falonipe',
                'username'      => 'kingsley.falonipe',
                'email'         => 'kingsley@falhen.com',
                'role'          => 'Operations Specialist',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713360/falhen/team/ftmzlwwv1szlyurijyno.png',
                'created_at'    => '2024-02-01',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 3,
                'full_name'     => 'Lisa Okoli',
                'username'      => 'lisa.okoli',
                'email'         => 'lisa@falhen.com',
                'role'          => 'Head of Marketing & Client Partnerships',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713962/falhen/team/b5v6o8oirs95xihokmgc.jpg',
                'created_at'    => '2024-02-15',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 4,
                'full_name'     => 'Mojisola Emjay',
                'username'      => 'mojisola.emjay',
                'email'         => 'mojisola@falhen.com',
                'role'          => 'Resource & HR Manager',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg',
                'created_at'    => '2024-03-01',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 5,
                'full_name'     => 'Daniel Ifeoluwa',
                'username'      => 'daniel.ifeoluwa',
                'email'         => 'daniel@falhen.com',
                'role'          => 'Lead Graphics Designer',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714147/falhen/team/xba4ep38ewfdq3chfx1h.jpg',
                'created_at'    => '2024-03-10',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 6,
                'full_name'     => 'Victoria Opemipo',
                'username'      => 'victoria.opemipo',
                'email'         => 'victoria@falhen.com',
                'role'          => 'Social Media Manager',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714208/falhen/team/ri0fc0rdmjfvyzx1lihn.jpg',
                'created_at'    => '2024-04-05',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 7,
                'full_name'     => 'Micheal Otuwho',
                'username'      => 'micheal.otuwho',
                'email'         => 'micheal@falhen.com',
                'role'          => 'Content Manager',
                'status'        => 'active',
                'avatar'        => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=600&auto=format&fit=crop',
                'created_at'    => '2024-04-18',
                'password_hash' => $defaultPasswordHash
            ],
            [
                'id'            => 8,
                'full_name'     => 'Ligali Oluwatosin',
                'username'      => 'ligali.oluwatosin',
                'email'         => 'ligali@falhen.com',
                'role'          => 'IT Support Specialist',
                'status'        => 'active',
                'avatar'        => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786753671/falhen/team/sptg6oyc6wudqirgzqxc.png',
                'created_at'    => '2024-05-01',
                'password_hash' => $defaultPasswordHash
            ]
        ];

        $settings['staff_accounts'] = array_values($items);
        saveSiteSettings($settings);
    }

    // Direct synchronization of avatars from Team (Content) menu
    $teamMembers = getTeamMembers();
    $teamAvatarMap = [];
    foreach ($teamMembers as $tm) {
        if (!empty($tm['name']) && !empty($tm['image'])) {
            $fullNameLower = strtolower(trim($tm['name']));
            $firstNameLower = strtolower(explode(' ', trim($tm['name']))[0]);

            $teamAvatarMap[$fullNameLower] = $tm['image'];
            if (!isset($teamAvatarMap[$firstNameLower])) {
                $teamAvatarMap[$firstNameLower] = $tm['image'];
            }
        }
    }

    $keyed = [];
    $modified = false;
    foreach ($items as $idx => $item) {
        $id = (int)($item['id'] ?? 0);
        $nameLower = strtolower(trim($item['full_name'] ?? ''));
        $firstNameLower = strtolower(explode(' ', $nameLower)[0]);

        // Always sync avatar photo directly from Team (Content) roster
        $syncedAvatar = $teamAvatarMap[$nameLower] ?? ($teamAvatarMap[$firstNameLower] ?? ($item['avatar'] ?? ''));
        if (!empty($syncedAvatar) && $syncedAvatar !== ($item['avatar'] ?? '')) {
            $item['avatar'] = $syncedAvatar;
            $items[$idx]['avatar'] = $syncedAvatar;
            $modified = true;
        }

        if ($id > 0) {
            $keyed[$id] = $item;
        }
    }

    if ($modified) {
        $settings['staff_accounts'] = array_values($items);
        saveSiteSettings($settings);
    }

    return $keyed;
}

/**
 * Get Staff Account By ID
 */
function getStaffAccountById($id) {
    $repo = getStaffAccountsRepo();
    $id = (int)$id;
    return $repo[$id] ?? null;
}

/**
 * Synchronize Team (Content) Roster & Staff Accounts
 */
function syncTeamAndStaffAccounts() {
    $teamMembers = array_values(getTeamMembers());
    $staffAccounts = getStaffAccountsRepo();

    $addedCount = 0;
    $updatedCount = 0;

    $staffByName = [];
    foreach ($staffAccounts as $idx => $st) {
        $nameKey = strtolower(trim($st['full_name'] ?? ''));
        $firstNameKey = strtolower(explode(' ', $nameKey)[0]);
        $staffByName[$nameKey] = $st['id'];
        if (!isset($staffByName[$firstNameKey])) {
            $staffByName[$firstNameKey] = $st['id'];
        }
    }

    $defaultPasswordHash = password_hash('Password123#', PASSWORD_DEFAULT);

    foreach ($teamMembers as $tm) {
        $tmName = trim($tm['name'] ?? '');
        if (empty($tmName)) continue;

        $tmNameLower = strtolower($tmName);
        $tmFirstLower = strtolower(explode(' ', $tmNameLower)[0]);
        $tmImage = $tm['image'] ?? '';

        if (isset($staffByName[$tmNameLower])) {
            $stId = $staffByName[$tmNameLower];
            if (isset($staffAccounts[$stId])) {
                if (($staffAccounts[$stId]['avatar'] ?? '') !== $tmImage && !empty($tmImage)) {
                    $staffAccounts[$stId]['avatar'] = $tmImage;
                    $updatedCount++;
                }
            }
        } else if (isset($staffByName[$tmFirstLower])) {
            $stId = $staffByName[$tmFirstLower];
            if (isset($staffAccounts[$stId])) {
                if (($staffAccounts[$stId]['avatar'] ?? '') !== $tmImage && !empty($tmImage)) {
                    $staffAccounts[$stId]['avatar'] = $tmImage;
                    $updatedCount++;
                }
            }
        } else {
            // Auto-create matching staff account for team member
            $maxId = 0;
            foreach ($staffAccounts as $st) {
                $id = (int)($st['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $username = strtolower(str_replace(' ', '.', $tmName));
            $email = $tmFirstLower . '@falhen.com';

            $staffAccounts[$newId] = [
                'id'            => $newId,
                'full_name'     => $tmName,
                'username'      => $username,
                'email'         => $email,
                'role'          => $tm['role'] ?? 'Staff',
                'status'        => 'active',
                'avatar'        => $tmImage,
                'created_at'    => date('Y-m-d'),
                'password_hash' => $defaultPasswordHash
            ];
            $addedCount++;
        }
    }

    saveSiteSettings(['staff_accounts' => array_values($staffAccounts)]);
    return ['added' => $addedCount, 'updated' => $updatedCount];
}

/**
 * Check if a user account is an Admin / Super Admin
 * Evaluates role title, staff email address, and username.
 */
function isAdminUser($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    // Designated Admin Emails & Usernames
    $adminEmails = [
        'oluwatosin.ligali@falhenmedia.com',
        'ligali@falhen.com',
        'admin@falhen.com',
        'kim@falhen.com',
        'mail@falhenmedia.com'
    ];
    $adminUsernames = [
        'admin',
        'ligali.oluwatosin',
        'oluwatosin.ligali'
    ];

    if (in_array($e, $adminEmails, true) || in_array($u, $adminUsernames, true)) {
        return true;
    }

    // Role title checks
    if ($r === 'super admin' || $r === 'administrator' || $r === 'admin' || str_contains($r, 'admin') || str_contains($r, 'director') || str_contains($r, 'manager') || str_contains($r, 'editor')) {
        return true;
    }

    return false;
}

function isTalentManager($role = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    return strtolower(trim((string)$role)) === 'talent manager';
}

function isContentEditor($role = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    return strtolower(trim((string)$role)) === 'content editor';
}

function isAdminRole($role) {
    return isAdminUser($role);
}

function isSuperAdminUser($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }
    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    if ($r === 'super admin' || $r === 'administrator' || $u === 'admin' || $u === 'henry' || $e === 'mail@falhenmedia.com' || $e === 'admin@falhen.com') {
        return true;
    }
    return false;
}

/**
 * Role-Based Access Control (RBAC) Permission Registry
 * Maps role title and user credentials to authorized section keys.
 */
function getRolePermissions($role, $email = null, $username = null) {
    if (isAdminUser($role, $email, $username)) {
        return ['*'];
    }

    // Non-admin staff users get access to Employee Portal & Topbar Primary Menu sections
    return [
        'home',
        'dashboard',
        'mail',
        'comms',
        'assets',
        'onboarding',
        'directory',
        'announcements',
        'attendance',
        'leaves',
        'payslips',
        'profile',
        'my_profile'
    ];
}

/**
 * Check if a given user/role has permission to access a specific section
 */
function hasSectionAccess($section, $role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? 'Staff';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }
    
    $role = trim((string)$role);
    $section = strtolower(trim((string)$section));

    // Personal profile, settings, studio communications, and task board are always accessible to logged-in team members
    if ($section === 'profile' || $section === 'my_profile' || $section === 'comms' || $section === 'tasks' || $section === 'feeds' || $section === 'channels') {
        return true;
    }

    // Content Editor cannot access staff_accounts
    if (isContentEditor($role) && $section === 'staff_accounts') {
        return false;
    }

    if (isAdminUser($role, $email, $username)) {
        return true;
    }

    $permissions = getRolePermissions($role, $email, $username);
    if (in_array('*', $permissions, true)) {
        return true;
    }

    // Map aliases
    if ($section === 'home') {
        $section = 'dashboard';
    }
    if ($section === 'team') {
        $section = 'directory';
    }
    if ($section === 'blog') {
        return in_array('blog', $permissions, true) || in_array('announcements', $permissions, true);
    }

    return in_array($section, $permissions, true);
}

/**
 * Get default fallback section for a user account
 */
function getUserFirstAllowedSection($role = null, $email = null, $username = null) {
    if (isTalentManager($role)) {
        return 'dashboard';
    }
    if (isAdminUser($role, $email, $username)) {
        return 'hero';
    }
    return 'dashboard';
}

/**
 * Automatically clock out any attendance records from previous days that were left unclosed after midnight.
 */
function autoCloseStaleAttendanceLogs() {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    if (empty($logs)) return;

    $today = date('Y-m-d');
    $modified = false;

    foreach ($logs as $idx => &$entry) {
        $entryDate = $entry['date'] ?? '';
        $isUnclosed = empty($entry['clock_out']) || ($entry['clock_out'] === 'In Progress') || ($entry['status'] ?? '') === 'Clocked In' || ($entry['status'] ?? '') === 'On Break' || ($entry['duration'] ?? '') === 'In Progress';
        
        // If the record date is earlier than today and it was left unclosed after midnight
        if (!empty($entryDate) && $entryDate < $today && $isUnclosed) {
            $clockInTimeStr = !empty($entry['clock_in']) && $entry['clock_in'] !== 'In Progress' ? $entry['clock_in'] : '09:00 AM';
            $clockInTs = strtotime($entryDate . ' ' . $clockInTimeStr);
            $autoClockOutTs = strtotime($entryDate . ' 11:59 PM');

            if ($clockInTs && $autoClockOutTs && $autoClockOutTs > $clockInTs) {
                $diffMins = max(1, round(($autoClockOutTs - $clockInTs) / 60));
                $hrs = floor($diffMins / 60);
                $mins = $diffMins % 60;
                $durationStr = ($hrs > 0 ? $hrs . ' hrs ' : '') . $mins . ' mins';
            } else {
                $durationStr = '8 hrs 00 mins';
            }

            $entry['clock_out'] = '11:59 PM';
            $entry['duration'] = $durationStr;
            $entry['status'] = 'Present';
            $entry['work_state'] = 'completed';
            $entry['auto_clocked_out'] = true;
            $modified = true;
        }
    }

    if ($modified) {
        $settings['attendance_logs'] = array_values($logs);
        saveSiteSettings($settings);
    }
}

/**
 * Check if a user role/account is authorized to view attendance logs of all staff members
 */
function canViewAllAttendanceLogs($role = null, $email = null, $username = null) {
    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    if (isTalentManager($role)) {
        return true;
    }

    $r = strtolower(trim((string)$role));
    $e = strtolower(trim((string)$email));
    $u = strtolower(trim((string)$username));

    $adminEmails = [
        'oluwatosin.ligali@falhenmedia.com',
        'ligali@falhen.com',
        'admin@falhen.com',
        'kim@falhen.com',
        'mail@falhenmedia.com'
    ];
    $adminUsernames = [
        'admin',
        'ligali.oluwatosin',
        'oluwatosin.ligali'
    ];

    if (in_array($e, $adminEmails, true) || in_array($u, $adminUsernames, true)) {
        return true;
    }

    if ($r === 'super admin' || $r === 'administrator' || $r === 'admin') {
        return true;
    }

    return false;
}

/**
 * Get Attendance Logs Repository (Auto-cleans stale unclosed shifts)
 */
function getAttendanceLogs($username = null, $role = null, $email = null) {
    autoCloseStaleAttendanceLogs();
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];

    if (empty($logs)) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $prevDay = date('Y-m-d', strtotime('-2 days'));

        $logs = [
            [
                'id' => 1,
                'username' => 'daniel.ifeoluwa',
                'full_name' => 'Daniel Ifeoluwa',
                'date' => $yesterday,
                'clock_in' => '08:45 AM',
                'clock_out' => '05:30 PM',
                'duration' => '8 hrs 45 mins',
                'status' => 'Present'
            ],
            [
                'id' => 2,
                'username' => 'daniel.ifeoluwa',
                'full_name' => 'Daniel Ifeoluwa',
                'date' => $prevDay,
                'clock_in' => '08:50 AM',
                'clock_out' => '05:15 PM',
                'duration' => '8 hrs 25 mins',
                'status' => 'Present'
            ],
            [
                'id' => 3,
                'username' => 'admin',
                'full_name' => 'Oluwatosin Ligali',
                'date' => $yesterday,
                'clock_in' => '08:30 AM',
                'clock_out' => '06:00 PM',
                'duration' => '9 hrs 30 mins',
                'status' => 'Present'
            ]
        ];
        $settings['attendance_logs'] = $logs;
        saveSiteSettings($settings);
    }

    if ($role === null) {
        $role = $_SESSION['admin_role'] ?? '';
    }
    if ($email === null) {
        $email = $_SESSION['admin_email'] ?? '';
    }
    if ($username === null) {
        $username = $_SESSION['admin_username'] ?? '';
    }

    $canSeeAll = canViewAllAttendanceLogs($role, $email, $username);

    if (!$canSeeAll && !empty($username)) {
        $u = strtolower(trim((string)$username));
        $userLogs = array_filter($logs, function($item) use ($u) {
            return strtolower(trim($item['username'] ?? '')) === $u;
        });
        return array_values($userLogs);
    }

    return array_values($logs);
}

/**
 * Get today's attendance record for a specific user
 */
function getUserTodayAttendance($username) {
    $logs = getAttendanceLogs();
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));

    foreach ($logs as $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            return $entry;
        }
    }
    return null;
}

/**
 * Perform Clock In Action
 */
function recordClockIn($username, $fullName) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You are already clocked in for today (at ' . ($entry['clock_in'] ?? 'earlier') . ')!'];
            }
            
            // Re-open active shift session for today
            $logs[$idx]['clock_in'] = $now;
            $logs[$idx]['clock_out'] = null;
            $logs[$idx]['break_start'] = null;
            $logs[$idx]['break_end'] = null;
            $logs[$idx]['duration'] = 'In Progress';
            $logs[$idx]['status'] = 'Clocked In';
            $logs[$idx]['work_state'] = 'working';

            $settings['attendance_logs'] = array_values($logs);
            saveSiteSettings($settings);

            return ['success' => true, 'message' => 'Clock-In recorded successfully at ' . $now . '! Have a productive work session.'];
        }
    }

    $newId = count($logs) + 1;
    $newEntry = [
        'id' => $newId,
        'username' => $username,
        'full_name' => $fullName,
        'date' => $today,
        'clock_in' => $now,
        'clock_out' => null,
        'break_start' => null,
        'break_end' => null,
        'duration' => 'In Progress',
        'status' => 'Clocked In',
        'work_state' => 'working'
    ];

    array_unshift($logs, $newEntry);
    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Clock-In recorded successfully at ' . $now . '! Have a productive work session.'];
}

/**
 * Perform Start Break Action
 */
function recordStartBreak($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (!empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You have already clocked out for today.'];
            }
            if (($entry['status'] ?? '') === 'On Break') {
                return ['success' => false, 'message' => 'You are already on break (started at ' . ($entry['break_start'] ?? 'earlier') . ').'];
            }

            $logs[$idx]['break_start'] = $now;
            $logs[$idx]['status'] = 'On Break';
            $logs[$idx]['work_state'] = 'on_break';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'Please clock in before starting a break.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Break started at ' . $now . '. Enjoy your rest!'];
}

/**
 * Perform End Break Action (Resume Work)
 */
function recordEndBreak($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (($entry['status'] ?? '') !== 'On Break' && empty($entry['break_start'])) {
                return ['success' => false, 'message' => 'You are not currently on break.'];
            }

            $logs[$idx]['break_end'] = $now;
            $logs[$idx]['status'] = 'Clocked In';
            $logs[$idx]['work_state'] = 'working';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'No active break record found for today.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Break ended at ' . $now . '. Welcome back to work!'];
}

/**
 * Perform Clock Out Action
 */
function recordClockOut($username) {
    $settings = getSiteSettings();
    $logs = $settings['attendance_logs'] ?? [];
    $today = date('Y-m-d');
    $userLower = strtolower(trim($username));
    $now = date('h:i A');

    $found = false;
    $durationStr = '8 hrs';

    foreach ($logs as $idx => $entry) {
        if (($entry['date'] ?? '') === $today && strtolower(trim($entry['username'] ?? '')) === $userLower) {
            if (!empty($entry['clock_out'])) {
                return ['success' => false, 'message' => 'You have already clocked out today at ' . $entry['clock_out'] . '.'];
            }

            $clockInTs = strtotime($today . ' ' . $entry['clock_in']);
            $clockOutTs = time();
            $diffMins = max(1, round(($clockOutTs - $clockInTs) / 60));
            $hrs = floor($diffMins / 60);
            $mins = $diffMins % 60;
            $durationStr = ($hrs > 0 ? $hrs . ' hrs ' : '') . $mins . ' mins';

            $logs[$idx]['clock_out'] = $now;
            $logs[$idx]['duration'] = $durationStr;
            $logs[$idx]['status'] = 'Present';
            $logs[$idx]['work_state'] = 'completed';
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['success' => false, 'message' => 'No active clock-in record found for today. Please clock in first.'];
    }

    $settings['attendance_logs'] = array_values($logs);
    saveSiteSettings($settings);

    return ['success' => true, 'message' => 'Clock-Out recorded successfully at ' . $now . '! Work session logged: ' . $durationStr . '.'];
}

/**
 * Get User Onboarding Data
 */
function getUserOnboardingData($username) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim($username));
    $allOnboarding = $settings['user_onboarding'] ?? [];
    
    $userData = $allOnboarding[$userLower] ?? null;

    if (!$userData) {
        $userData = [
            'bank_details' => [
                'status' => 'Pending',
                'account_number' => '',
                'bank_name' => '',
                'account_name' => ''
            ],
            'offer_letter' => [
                'status' => 'Approved',
                'accepted' => true,
                'document_url' => '/assets/docs/Offer_Letter_Falhen.pdf'
            ],
            'employment_agreement' => [
                'status' => 'Pending',
                'signed' => false,
                'document_url' => ''
            ],
            'reference_1' => [
                'status' => 'Pending',
                'ref_name' => '',
                'ref_contact' => '',
                'relationship' => ''
            ],
            'sop' => [
                'status' => 'Pending',
                'acknowledged' => false
            ],
            'staff_handbook' => [
                'status' => 'Pending',
                'acknowledged' => false
            ],
            'id_verification' => [
                'status' => 'Pending',
                'document_type' => 'Government ID / Tax ID',
                'file_url' => ''
            ]
        ];
    }

    return $userData;
}

/**
 * Save User Onboarding Data Section
 */
function saveUserOnboardingSection($username, $sectionKey, $sectionData) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim($username));
    $allOnboarding = $settings['user_onboarding'] ?? [];

    $userData = getUserOnboardingData($username);
    $userData[$sectionKey] = array_merge($userData[$sectionKey] ?? [], $sectionData);

    $allOnboarding[$userLower] = $userData;
    $settings['user_onboarding'] = $allOnboarding;
    saveSiteSettings($settings);

    return $userData;
}

/**
 * Calculate Onboarding Progress Metrics (X/7)
 */
function getOnboardingProgress($username) {
    $data = getUserOnboardingData($username);
    $completedCount = 0;
    $totalTasks = 7;

    if (($data['bank_details']['status'] ?? '') === 'Approved' || ($data['bank_details']['status'] ?? '') === 'Submitted' || !empty($data['bank_details']['account_number'])) {
        $completedCount++;
    }
    if (!empty($data['offer_letter']['accepted']) || ($data['offer_letter']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }
    if (!empty($data['employment_agreement']['signed']) || ($data['employment_agreement']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }
    if (($data['reference_1']['status'] ?? '') === 'Approved' || ($data['reference_1']['status'] ?? '') === 'Submitted' || !empty($data['reference_1']['ref_name'])) {
        $completedCount++;
    }
    if (!empty($data['sop']['acknowledged']) || ($data['sop']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }
    if (!empty($data['staff_handbook']['acknowledged']) || ($data['staff_handbook']['status'] ?? '') === 'Approved') {
        $completedCount++;
    }
    if (!empty($data['id_verification']['file_url']) || ($data['id_verification']['status'] ?? '') === 'Approved' || ($data['id_verification']['status'] ?? '') === 'Submitted') {
        $completedCount++;
    }

    $percent = round(($completedCount / $totalTasks) * 100);

    return [
        'completed' => $completedCount,
        'total' => $totalTasks,
        'percent' => $percent
    ];
}

/**
 * Update Onboarding Task Status & Admin Action (HR & Admin Feature)
 */
function updateUserOnboardingTaskStatus($username, $taskKey, $newStatus, $extraData = []) {
    $settings = getSiteSettings();
    $userLower = strtolower(trim($username));
    $allOnboarding = $settings['user_onboarding'] ?? [];

    $userData = getUserOnboardingData($username);
    if (!isset($userData[$taskKey])) {
        $userData[$taskKey] = [];
    }

    $userData[$taskKey]['status'] = $newStatus;
    if (!empty($extraData)) {
        $userData[$taskKey] = array_merge($userData[$taskKey], $extraData);
    }

    $allOnboarding[$userLower] = $userData;
    $settings['user_onboarding'] = $allOnboarding;
    saveSiteSettings($settings);

    return $userData;
}

/**
 * Get Onboarding Overview for All Staff Members (HR & Admin View)
 */
function getAllStaffOnboardingSummary() {
    $staffList = getStaffAccountsRepo();
    $summary = [];

    foreach ($staffList as $st) {
        $stUsername = $st['username'] ?? '';
        $progress = getOnboardingProgress($stUsername);
        $summary[] = [
            'username' => $stUsername,
            'full_name' => $st['full_name'] ?? 'Staff',
            'role' => $st['role'] ?? 'Staff',
            'email' => $st['email'] ?? '',
            'avatar' => $st['avatar'] ?? '',
            'completed' => $progress['completed'],
            'total' => $progress['total'],
            'percent' => $progress['percent']
        ];
    }

    return $summary;
}

/**
 * Get Site Announcements with Optional Category Filtering
 */
function getSiteAnnouncements($category = 'all') {
    $settings = getSiteSettings();
    $announcements = $settings['site_announcements'] ?? [
        [
            'id' => 1,
            'title' => 'Falhen Q3 Production Roadmap & 8K Cinema Gear Launch',
            'category' => 'Important',
            'posted_by' => 'Henry Falonipe (Creative Director)',
            'date_str' => 'Posted Today at 09:00 AM',
            'content' => 'We are excited to announce the arrival of our new RED V-Raptor and ARRI Alexa Mini LF camera packages. All cinematographers and production crews are requested to complete the gear orientation briefing before booking equipment for upcoming commercial shoots.'
        ],
        [
            'id' => 2,
            'title' => 'Updated Hybrid Work Policy & Health Stipend',
            'category' => 'General',
            'posted_by' => 'Resource & HR Management',
            'date_str' => 'Posted Yesterday',
            'content' => 'The monthly health & wellness reimbursement portal is now open for August submissions. Please ensure all receipts are uploaded under your profile before the 25th of the month.'
        ],
        [
            'id' => 3,
            'title' => 'Annual Studio End-of-Summer Gala & Awards',
            'category' => 'Events',
            'posted_by' => 'Studio Operations',
            'date_str' => 'Posted 3 days ago',
            'content' => 'Save the date! Falhen Media will host our annual End-of-Summer Gala on August 30th at the Main Soundstage. Details and RSVP forms will be shared shortly.'
        ]
    ];

    if ($category !== 'all' && !empty($category)) {
        $categoryLower = strtolower(trim($category));
        $announcements = array_values(array_filter($announcements, function($item) use ($categoryLower) {
            return strtolower(trim($item['category'] ?? '')) === $categoryLower;
        }));
    }

    return $announcements;
}

/**
 * Post New Site Announcement (HR & Admin)
 */
function postSiteAnnouncement($data) {
    $settings = getSiteSettings();
    $announcements = getSiteAnnouncements('all');
    $newId = count($announcements) + 1;
    
    $newAnnouncement = [
        'id' => $newId,
        'title' => trim($data['title'] ?? 'Company Update'),
        'category' => ucfirst(trim($data['category'] ?? 'General')),
        'posted_by' => trim($data['posted_by'] ?? 'HR & Studio Ops'),
        'date_str' => 'Posted Today at ' . date('h:i A'),
        'content' => trim($data['content'] ?? '')
    ];

    array_unshift($announcements, $newAnnouncement);
    $settings['site_announcements'] = $announcements;
    saveSiteSettings($settings);

    return $newAnnouncement;
}

/**
 * Get all leave requests from JSON storage
 */
function getLeaveRequests() {
    $filePath = __DIR__ . '/../config/leave_requests.json';
    if (!file_exists($filePath)) {
        // Default initial seed data if file does not exist yet
        $initialData = [
            [
                'id' => 1,
                'username' => 'mojisola',
                'staff_name' => 'Mojisola Emjay',
                'staff_role' => 'Talent Manager',
                'type' => 'Annual Leave',
                'dates' => 'Sep 01, 2026 – Sep 03, 2026 (3 Days)',
                'reason' => 'End of Summer Studio Break',
                'status' => 'Pending HR Approval',
                'status_type' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'username' => 'mojisola',
                'staff_name' => 'Mojisola Emjay',
                'staff_role' => 'Talent Manager',
                'type' => 'Annual Leave',
                'dates' => 'Aug 10, 2026 – Aug 12, 2026 (3 Days)',
                'reason' => 'Family Vacation & Personal Time',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'username' => 'victoria',
                'staff_name' => 'Victoria Opemipo',
                'staff_role' => 'Creative Director',
                'type' => 'Casual Leave',
                'dates' => 'Aug 28, 2026 (1 Day)',
                'reason' => 'Personal Errand & Administrative Work',
                'status' => 'Pending HR Approval',
                'status_type' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'username' => 'oluwatosin',
                'staff_name' => 'Oluwatosin Ligali',
                'staff_role' => 'Lead Developer',
                'type' => 'Annual Leave',
                'dates' => 'Sep 15, 2026 – Sep 20, 2026 (5 Days)',
                'reason' => 'Annual Recess & Rest',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 5,
                'username' => 'daniel.ifeoluwa',
                'staff_name' => 'Daniel Ifeoluwa',
                'staff_role' => 'Staff',
                'type' => 'Sick Leave',
                'dates' => 'Jul 04, 2026 (1 Day)',
                'reason' => 'Medical Checkup',
                'status' => 'Approved',
                'status_type' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        @file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }

    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Submit a new leave request with custom Start & End Dates
 */
function submitLeaveRequest($username, $fullName, $role, $type, $startDateInput = '', $endDateInput = '', $duration = 1, $reason = '') {
    $requests = getLeaveRequests();
    
    $startTs = !empty($startDateInput) ? strtotime($startDateInput) : strtotime('+1 day');
    $days = max(1, (int)$duration);

    if (!empty($endDateInput)) {
        $endTs = strtotime($endDateInput);
        if ($endTs < $startTs) {
            $endTs = $startTs;
        }
        $calcDays = max(1, round(($endTs - $startTs) / 86400) + 1);
        $days = (int)$calcDays;
    } else {
        $endTs = strtotime('+' . ($days - 1) . ' days', $startTs);
    }

    $startStr = date('M d, Y', $startTs);
    $endStr = date('M d, Y', $endTs);

    $dateStr = ($startStr === $endStr || $days === 1) 
        ? "{$startStr} (1 Day)" 
        : "{$startStr} – {$endStr} ({$days} Days)";

    $newRequest = [
        'id' => time() . rand(100, 999),
        'username' => strtolower(trim($username)),
        'staff_name' => $fullName,
        'staff_role' => $role,
        'type' => trim($type),
        'start_date' => date('Y-m-d', $startTs),
        'end_date' => date('Y-m-d', $endTs),
        'duration' => $days,
        'dates' => $dateStr,
        'reason' => trim($reason),
        'status' => 'Pending HR Approval',
        'status_type' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Unshift so new request appears at top of history
    array_unshift($requests, $newRequest);

    $filePath = __DIR__ . '/../config/leave_requests.json';
    @file_put_contents($filePath, json_encode($requests, JSON_PRETTY_PRINT));
    return $newRequest;
}

/**
 * Update Leave Request Status (HR & Admin)
 */
function updateLeaveRequestStatus($requestId, $newStatus) {
    $requests = getLeaveRequests();
    $updated = false;

    foreach ($requests as &$req) {
        if ((string)($req['id'] ?? '') === (string)$requestId) {
            $statusClean = ucfirst(strtolower(trim($newStatus)));
            if ($statusClean === 'Approved') {
                $req['status'] = 'Approved';
                $req['status_type'] = 'approved';
            } elseif ($statusClean === 'Rejected') {
                $req['status'] = 'Rejected';
                $req['status_type'] = 'rejected';
            } else {
                $req['status'] = $statusClean;
                $req['status_type'] = strtolower($statusClean);
            }
            $req['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    unset($req);

    if ($updated) {
        $filePath = __DIR__ . '/../config/leave_requests.json';
        @file_put_contents($filePath, json_encode($requests, JSON_PRETTY_PRINT));
    }

    return $updated;
}

/**
 * Calculate dynamic leave balance statistics for a user
 */
function getUserLeaveStats($username) {
    $allRequests = getLeaveRequests();
    $userRequests = array_filter($allRequests, function($req) use ($username) {
        return strtolower(trim($req['username'] ?? '')) === strtolower(trim($username));
    });

    $allowanceMap = [
        'Annual Leave' => 20,
        'Sick Leave' => 7,
        'Casual Leave' => 5
    ];

    $usedMap = [
        'Annual Leave' => 0,
        'Sick Leave' => 0,
        'Casual Leave' => 0
    ];

    foreach ($userRequests as $req) {
        $statusType = strtolower(trim($req['status_type'] ?? ''));
        // Include approved and pending requests in used calculation
        if ($statusType === 'approved' || $statusType === 'pending') {
            $type = trim($req['type'] ?? '');
            $dur = max(1, intval($req['duration'] ?? 1));

            if (isset($usedMap[$type])) {
                $usedMap[$type] += $dur;
            } else if (stripos($type, 'annual') !== false) {
                $usedMap['Annual Leave'] += $dur;
            } else if (stripos($type, 'sick') !== false) {
                $usedMap['Sick Leave'] += $dur;
            } else if (stripos($type, 'casual') !== false) {
                $usedMap['Casual Leave'] += $dur;
            }
        }
    }

    $annualRemaining = max(0, $allowanceMap['Annual Leave'] - $usedMap['Annual Leave']);
    $sickRemaining = max(0, $allowanceMap['Sick Leave'] - $usedMap['Sick Leave']);
    $casualRemaining = max(0, $allowanceMap['Casual Leave'] - $usedMap['Casual Leave']);

    return [
        'annual' => [
            'total' => $allowanceMap['Annual Leave'],
            'used' => $usedMap['Annual Leave'],
            'remaining' => $annualRemaining
        ],
        'sick' => [
            'total' => $allowanceMap['Sick Leave'],
            'used' => $usedMap['Sick Leave'],
            'remaining' => $sickRemaining
        ],
        'casual' => [
            'total' => $allowanceMap['Casual Leave'],
            'used' => $usedMap['Casual Leave'],
            'remaining' => $casualRemaining
        ]
    ];
}

/**
 * Fetch persistent team comms messages
 */
function getCommsMessages($channel = 'general') {
    $filePath = __DIR__ . '/../config/comms_messages.json';
    if (!file_exists($filePath)) {
        $initialData = [
            [
                'id' => 1,
                'channel' => 'general',
                'username' => 'henry',
                'sender_name' => 'Henry Falonipe',
                'sender_role' => 'Creative Director',
                'message' => 'Good morning team! Please remember to submit your weekly production briefing updates.',
                'time_str' => '09:00 AM',
                'created_at' => date('Y-m-d 09:00:00')
            ],
            [
                'id' => 2,
                'channel' => 'general',
                'username' => 'victoria',
                'sender_name' => 'Victoria Opemipo',
                'sender_role' => 'Creative Director',
                'message' => 'Reviewing the latest color grade drafts for the RED V-Raptor footage now.',
                'time_str' => '09:15 AM',
                'created_at' => date('Y-m-d 09:15:00')
            ],
            [
                'id' => 3,
                'channel' => 'general',
                'username' => 'daniel',
                'sender_name' => 'Daniel Ifeoluwa',
                'sender_role' => 'Operations Manager',
                'message' => 'Call sheets for next week\'s commercial shoot have been uploaded under Operations.',
                'time_str' => '10:30 AM',
                'created_at' => date('Y-m-d 10:30:00')
            ],
            [
                'id' => 4,
                'channel' => 'production',
                'username' => 'oluwatosin',
                'sender_name' => 'Oluwatosin Ligali',
                'sender_role' => 'Lead Developer',
                'message' => 'Gear orientation briefing scheduled for tomorrow at 2 PM.',
                'time_str' => '11:00 AM',
                'created_at' => date('Y-m-d 11:00:00')
            ],
            [
                'id' => 5,
                'channel' => 'hr-helpdesk',
                'username' => 'mojisola',
                'sender_name' => 'Mojisola Emjay',
                'sender_role' => 'Talent Manager',
                'message' => 'Monthly health reimbursement claims are open until the 25th.',
                'time_str' => '11:30 AM',
                'created_at' => date('Y-m-d 11:30:00')
            ]
        ];
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }

    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Send a new comms message
 */
function sendCommsMessage($username, $senderName, $senderRole, $channel, $messageText) {
    $messages = getCommsMessages();
    $newMsg = [
        'id' => time() . rand(100, 999),
        'channel' => strtolower(trim($channel)),
        'username' => strtolower(trim($username)),
        'sender_name' => trim($senderName),
        'sender_role' => trim($senderRole),
        'message' => trim($messageText),
        'time_str' => date('h:i A'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    $messages[] = $newMsg;

    $filePath = __DIR__ . '/../config/comms_messages.json';
    @file_put_contents($filePath, json_encode($messages, JSON_PRETTY_PRINT));
    return $newMsg;
}

/**
 * Studio Calls Repository Functions
 */
function getStudioCallsFile() {
    return __DIR__ . '/../config/studio_calls.json';
}

function getActiveStudioCalls() {
    $file = getStudioCallsFile();
    if (!file_exists($file)) return [];
    $data = json_decode(@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveActiveStudioCalls($calls) {
    $file = getStudioCallsFile();
    @file_put_contents($file, json_encode(array_values($calls), JSON_PRETTY_PRINT));
}

function initiateStudioCallState($callerUser, $callerName, $callerAvatar, $receiverUser, $receiverName, $receiverAvatar, $callType = 'audio') {
    $calls = getActiveStudioCalls();
    $callId = 'call_' . time() . '_' . rand(100, 999);
    
    $now = time();
    $calls = array_filter($calls, fn($c) => ($now - ($c['created_at'] ?? 0)) < 300);

    $newCall = [
        'call_id' => $callId,
        'caller_username' => getCanonicalUsername($callerUser),
        'caller_name' => $callerName,
        'caller_avatar' => $callerAvatar,
        'receiver_username' => getCanonicalUsername($receiverUser),
        'receiver_name' => $receiverName,
        'receiver_avatar' => $receiverAvatar,
        'type' => $callType,
        'status' => 'ringing',
        'created_at' => $now
    ];

    $calls[] = $newCall;
    saveActiveStudioCalls($calls);
    return $newCall;
}

function updateStudioCallStatus($callId, $status) {
    $calls = getActiveStudioCalls();
    $updated = null;
    foreach ($calls as &$c) {
        if (($c['call_id'] ?? '') === $callId) {
            $c['status'] = $status;
            $updated = $c;
            break;
        }
    }
    saveActiveStudioCalls($calls);
    return $updated;
}

function checkUserStudioCallState($username) {
    $userCanon = getCanonicalUsername($username);
    if (empty($userCanon)) return null;

    $calls = getActiveStudioCalls();
    $now = time();
    
    // Check for incoming call targeted at this user
    foreach ($calls as $c) {
        if (($c['receiver_username'] ?? '') === $userCanon && ($c['status'] ?? '') === 'ringing' && ($now - ($c['created_at'] ?? 0)) < 60) {
            return [
                'role' => 'receiver',
                'call' => $c
            ];
        }
    }

    // Check for caller's active call status update
    foreach ($calls as $c) {
        if (($c['caller_username'] ?? '') === $userCanon && ($now - ($c['created_at'] ?? 0)) < 300) {
            return [
                'role' => 'caller',
                'call' => $c
            ];
        }
    }

    return null;
}

/**
 * Get Canonical Short Username for Consistent Channel Mapping
 */
function getCanonicalUsername($username) {
    $u = strtolower(trim((string)$username));
    if (empty($u)) return '';

    // If username is "admin", inspect active session full_name & email
    if ($u === 'admin') {
        $sessName = strtolower($_SESSION['admin_full_name'] ?? $_SESSION['admin_name'] ?? '');
        $sessEmail = strtolower($_SESSION['admin_email'] ?? '');
        if (str_contains($sessName, 'oluwatosin') || str_contains($sessName, 'ligali') || str_contains($sessEmail, 'ligali') || str_contains($sessEmail, 'oluwatosin')) {
            return 'oluwatosin';
        }
        if (str_contains($sessName, 'mojisola') || str_contains($sessEmail, 'mojisola')) {
            return 'mojisola';
        }
        if (str_contains($sessName, 'daniel') || str_contains($sessEmail, 'daniel')) {
            return 'daniel';
        }
        if (str_contains($sessName, 'victoria') || str_contains($sessEmail, 'victoria')) {
            return 'victoria';
        }
        if (str_contains($sessName, 'henry') || str_contains($sessEmail, 'henry')) {
            return 'henry';
        }
    }

    $aliasMap = [
        'kingsley.falonipe' => 'kingsley',
        'kingsley'          => 'kingsley',
        'mojisola.emjay'   => 'mojisola',
        'mojisola'         => 'mojisola',
        'henry'            => 'henry',
        'henry.falonipe'   => 'henry',
        'daniel.ifeoluwa'  => 'daniel',
        'daniel'           => 'daniel',
        'victoria.opemipo' => 'victoria',
        'victoria'         => 'victoria',
        'ligali.oluwatosin'=> 'oluwatosin',
        'oluwatosin.king'  => 'oluwatosin',
        'oluwatosin'       => 'oluwatosin',
        'micheal.otuwho'   => 'micheal',
        'micheal'          => 'micheal',
        'lisa.okoli'       => 'lisa',
        'lisa'             => 'lisa'
    ];

    if (isset($aliasMap[$u])) {
        return $aliasMap[$u];
    }

    if (str_contains($u, '.')) {
        return explode('.', $u)[0];
    }

    return $u;
}

/**
 * Helper to generate consistent channel key for direct messages between two users
 */
function getDmChannelKey($user1, $user2) {
    $c1 = getCanonicalUsername($user1);
    $c2 = getCanonicalUsername($user2);
    $arr = [$c1, $c2];
    sort($arr);
    return 'dm_' . implode('_', $arr);
}

/**
 * Dynamic calculation of staff contacts sorted by DM frequency and recency
 */
function getSortedRecentDmContacts($currentUsername) {
    $repo = getStaffAccountsRepo();
    $allMessages = getCommsMessages();
    $userLower = strtolower(trim($currentUsername));
    $userEmail = strtolower(trim($_SESSION['admin_email'] ?? ''));
    $userFullName = strtolower(trim($_SESSION['admin_name'] ?? ''));

    // Extract current user's first name
    $currentUserFirstName = '';
    if (!empty($userFullName)) {
        $currentUserFirstName = strtolower(explode(' ', $userFullName)[0]);
    }

    $contactsMap = [];
    foreach ($repo as $staff) {
        $stUsername = strtolower(trim($staff['username'] ?? ''));
        $stEmail = strtolower(trim($staff['email'] ?? ''));
        $stFullName = strtolower(trim($staff['full_name'] ?? ''));
        
        if (empty($stUsername)) continue;

        // Extract staff's first name
        $rawFullName = trim($staff['full_name'] ?? 'Staff Member');
        $nameParts = explode(' ', $rawFullName);
        $firstName = $nameParts[0] ?? $rawFullName;
        $stFirstNameLower = strtolower($firstName);

        // 1. Skip if username matches
        if ($stUsername === $userLower) continue;

        // 2. Skip if email matches
        if (!empty($userEmail) && $stEmail === $userEmail) continue;

        // 3. Skip if full name matches
        if (!empty($userFullName) && $stFullName === $userFullName) continue;

        // 4. Skip if first name matches current user's first name (prevents self-chatting under name variations)
        if (!empty($currentUserFirstName) && $stFirstNameLower === $currentUserFirstName) continue;

        $u1 = getCanonicalUsername($userLower);
        $u2 = getCanonicalUsername($stUsername);
        $dmKey = getDmChannelKey($userLower, $stUsername);

        // Calculate total messages exchanged & latest timestamp
        $msgCount = 0;
        $latestTime = 0;

        foreach ($allMessages as $msg) {
            $ch = strtolower(trim($msg['channel'] ?? ''));
            $isMatch = false;

            if ($ch === $dmKey) {
                $isMatch = true;
            } elseif (str_starts_with($ch, 'dm_')) {
                $parts = explode('_', substr($ch, 3));
                if (count($parts) === 2) {
                    $c1 = getCanonicalUsername($parts[0]);
                    $c2 = getCanonicalUsername($parts[1]);
                    if (($c1 === $u1 && $c2 === $u2) || ($c1 === $u2 && $c2 === $u1)) {
                        $isMatch = true;
                    }
                }
            }

            if ($isMatch) {
                $msgCount++;
                $ts = strtotime($msg['created_at'] ?? 'now');
                if ($ts > $latestTime) {
                    $latestTime = $ts;
                }
            }
        }

        $contactsMap[$stUsername] = [
            'username' => $stUsername,
            'first_name' => $firstName,
            'full_name' => $rawFullName,
            'role' => $staff['role'] ?? 'Staff',
            'avatar' => $staff['avatar'] ?? '',
            'msg_count' => $msgCount,
            'latest_time' => $latestTime
        ];
    }

    // Sort staff contacts: Most messages first, then latest message time, then name
    usort($contactsMap, function($a, $b) {
        if ($a['msg_count'] !== $b['msg_count']) {
            return $b['msg_count'] <=> $a['msg_count'];
        }
        if ($a['latest_time'] !== $b['latest_time']) {
            return $b['latest_time'] <=> $a['latest_time'];
        }
        return strcmp($a['first_name'], $b['first_name']);
    });

    return $contactsMap;
}

/**
 * Studio Tasks (Zoho Style Kanban Board) Repository Functions
 */
function getStudioTasksFile() {
    return __DIR__ . '/../config/studio_tasks.json';
}

function getStudioTasksRepo() {
    $filePath = getStudioTasksFile();
    if (!file_exists($filePath)) {
        $initialTasks = [
            [
                'id' => 'task_1',
                'title' => 'Scout Locations',
                'description' => 'Find 2 indoor studio options with high ceilings and sound proofing.',
                'assignee_username' => 'oluwatosin',
                'assignee_name' => 'Oluwatosin Ligali',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'concept',
                'due_date' => '2026-07-10',
                'due_date_str' => 'Jul 10',
                'priority' => 'High',
                'tags' => ['Location Scout', 'Indoor Studio'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Inspect Soundstage A ceiling height', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Verify acoustic dampening', 'completed' => false]
                ],
                'comments_count' => 0,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_2',
                'title' => 'Draft Logo Concepts',
                'description' => 'Create 3 variations based on brief.',
                'assignee_username' => 'oluwatosin',
                'assignee_name' => 'Oluwatosin Ligali',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'primary',
                'due_date' => '2026-07-05',
                'due_date_str' => 'Jul 5',
                'priority' => 'Medium',
                'tags' => ['Branding', 'Vector'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Monochrome sketch draft', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Full color presentation deck', 'completed' => true]
                ],
                'comments_count' => 0,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_3',
                'title' => 'RED V-Raptor Gear Orientation Briefing',
                'description' => 'Review camera rig calibration and lens packages.',
                'assignee_username' => 'victoria',
                'assignee_name' => 'Victoria Opemipo',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto/v1786714111/falhen/team/q4atws8zhxaogyzm8bgw.png',
                'stage' => 'ongoing',
                'due_date' => '2026-08-28',
                'due_date_str' => 'Aug 28',
                'priority' => 'Urgent',
                'tags' => ['Camera Gear', 'RED V-Raptor', '8K Cinema'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Calibrate V-Mount batteries', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Mount anamorphic prime lenses', 'completed' => false],
                    ['id' => 'item_3', 'text' => 'Test wireless follow focus unit', 'completed' => false]
                ],
                'comments_count' => 2,
                'attachments_count' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_4',
                'title' => 'Q3 Commercial Showreel Final Cut Sync',
                'description' => 'Master sound mixing and color grade output.',
                'assignee_username' => 'victoria',
                'assignee_name' => 'Victoria Opemipo',
                'assignee_avatar' => '',
                'stage' => 'pending_review',
                'due_date' => '2026-09-02',
                'due_date_str' => 'Sep 02',
                'priority' => 'Medium',
                'tags' => ['Showreel', 'Post Production'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Export ProRes 422 Master', 'completed' => false]
                ],
                'comments_count' => 1,
                'attachments_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'task_5',
                'title' => 'August Health Stipend Audit & Verification',
                'description' => 'Verify receipts and disburse health reimbursements.',
                'assignee_username' => 'mojisola',
                'assignee_name' => 'Mojisola Emjay',
                'assignee_avatar' => 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg',
                'stage' => 'completed',
                'due_date' => '2026-08-25',
                'due_date_str' => 'Aug 25',
                'priority' => 'Low',
                'tags' => ['HR', 'Finance'],
                'checklist' => [
                    ['id' => 'item_1', 'text' => 'Audit 12 submitted receipts', 'completed' => true],
                    ['id' => 'item_2', 'text' => 'Process payroll transfers', 'completed' => true]
                ],
                'comments_count' => 4,
                'attachments_count' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        $dir = dirname($filePath);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @file_put_contents($filePath, json_encode($initialTasks, JSON_PRETTY_PRINT));
        return $initialTasks;
    }
    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveStudioTasksRepo($tasks) {
    $filePath = getStudioTasksFile();
    @file_put_contents($filePath, json_encode(array_values($tasks), JSON_PRETTY_PRINT));
}

function createStudioTask($data) {
    $tasks = getStudioTasksRepo();
    $id = 'task_' . time() . '_' . rand(10, 99);
    $dueDateStr = !empty($data['due_date']) ? date('M d', strtotime($data['due_date'])) : 'No date';
    
    // Parse tags (comma separated)
    $tagsRaw = trim($data['tags'] ?? '');
    $tags = [];
    if (!empty($tagsRaw)) {
        $parts = explode(',', $tagsRaw);
        foreach ($parts as $p) {
            $t = trim($p);
            if (!empty($t)) $tags[] = $t;
        }
    }

    // Parse checklist (newline separated lines or array)
    $checklistRaw = $data['checklist'] ?? '';
    $checklist = [];
    if (is_array($checklistRaw)) {
        $checklist = $checklistRaw;
    } else if (is_string($checklistRaw) && !empty(trim($checklistRaw))) {
        $lines = explode("\n", $checklistRaw);
        $idx = 1;
        foreach ($lines as $ln) {
            $txt = trim($ln);
            if (!empty($txt)) {
                $checklist[] = [
                    'id' => 'item_' . $idx++,
                    'text' => $txt,
                    'completed' => false
                ];
            }
        }
    }

    $attachments = [];
    if (!empty($data['attachment_url'])) {
        $attachments[] = [
            'name' => $data['attachment_name'] ?? 'Task Attachment',
            'url' => $data['attachment_url'],
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
    }

    $assignees = $data['assignees'] ?? [];
    $primaryUser = !empty($assignees) ? ($assignees[0]['username'] ?? '') : ($data['assignee_username'] ?? '');
    $primaryName = !empty($assignees) ? implode(', ', array_column($assignees, 'name')) : ($data['assignee_name'] ?? 'Unassigned');
    $primaryAvatar = !empty($assignees) ? ($assignees[0]['avatar'] ?? '') : ($data['assignee_avatar'] ?? '');

    $newTask = [
        'id' => $id,
        'title' => trim($data['title'] ?? 'Untitled Task'),
        'client_org' => trim($data['client_org'] ?? ''),
        'description' => trim($data['description'] ?? ''),
        'assignees' => $assignees,
        'assignee_username' => $primaryUser,
        'assignee_name' => $primaryName,
        'assignee_avatar' => $primaryAvatar,
        'stage' => strtolower(trim($data['stage'] ?? 'ideas')),
        'due_date' => trim($data['due_date'] ?? date('Y-m-d')),
        'due_date_str' => $dueDateStr,
        'priority' => trim($data['priority'] ?? 'Medium'),
        'tags' => $tags,
        'checklist' => $checklist,
        'attachments' => $attachments,
        'comments_count' => 0,
        'attachments_count' => count($attachments),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    array_unshift($tasks, $newTask);
    saveStudioTasksRepo($tasks);
    return $newTask;
}

function toggleTaskChecklistItem($taskId, $itemId) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId && !empty($t['checklist'])) {
            foreach ($t['checklist'] as &$item) {
                if (($item['id'] ?? '') === $itemId) {
                    $item['completed'] = !($item['completed'] ?? false);
                    $updated = $t;
                    break 2;
                }
            }
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function updateStudioTask($taskId, $data) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    
    // Parse tags
    $tagsRaw = trim($data['tags'] ?? '');
    $tags = [];
    if (!empty($tagsRaw)) {
        $parts = explode(',', $tagsRaw);
        foreach ($parts as $p) {
            $t = trim($p);
            if (!empty($t)) $tags[] = $t;
        }
    }

    // Parse checklist items
    $checklistRaw = $data['checklist'] ?? '';
    $checklist = [];
    if (is_array($checklistRaw)) {
        $checklist = $checklistRaw;
    } else if (is_string($checklistRaw) && !empty(trim($checklistRaw))) {
        $lines = explode("\n", $checklistRaw);
        $idx = 1;
        foreach ($lines as $ln) {
            $txt = trim($ln);
            if (!empty($txt)) {
                $checklist[] = [
                    'id' => 'item_' . $idx++,
                    'text' => $txt,
                    'completed' => false
                ];
            }
        }
    }

    $dueDateStr = !empty($data['due_date']) ? date('M d', strtotime($data['due_date'])) : 'No date';

    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId) {
            $t['title'] = trim($data['title'] ?? $t['title']);
            if (isset($data['client_org'])) {
                $t['client_org'] = trim($data['client_org']);
            }
            $t['description'] = trim($data['description'] ?? $t['description']);
            $t['stage'] = strtolower(trim($data['stage'] ?? $t['stage']));
            $t['priority'] = trim($data['priority'] ?? $t['priority']);
            $t['due_date'] = trim($data['due_date'] ?? $t['due_date']);
            $t['due_date_str'] = $dueDateStr;
            $t['tags'] = $tags;
            $t['checklist'] = $checklist;
            if (!empty($data['attachment_url'])) {
                if (!isset($t['attachments']) || !is_array($t['attachments'])) {
                    $t['attachments'] = [];
                }
                $t['attachments'][] = [
                    'name' => $data['attachment_name'] ?? 'Task Attachment',
                    'url' => $data['attachment_url'],
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
            }
            $t['attachments_count'] = count($t['attachments'] ?? []);
            if (isset($data['assignees'])) {
                $assignees = $data['assignees'];
                $t['assignees'] = $assignees;
                $t['assignee_username'] = !empty($assignees) ? implode(',', array_column($assignees, 'username')) : '';
                $t['assignee_name'] = !empty($assignees) ? implode(', ', array_column($assignees, 'name')) : 'Unassigned';
                $t['assignee_avatar'] = !empty($assignees) ? ($assignees[0]['avatar'] ?? '') : '';
            } else if (isset($data['assignee_username'])) {
                $t['assignee_username'] = getCanonicalUsername($data['assignee_username']);
                $t['assignee_name'] = trim($data['assignee_name'] ?? $t['assignee_name']);
                $t['assignee_avatar'] = trim($data['assignee_avatar'] ?? $t['assignee_avatar']);
            }
            $updated = $t;
            break;
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function updateStudioTaskStage($taskId, $newStage) {
    $tasks = getStudioTasksRepo();
    $updated = null;
    foreach ($tasks as &$t) {
        if (($t['id'] ?? '') === $taskId) {
            $t['stage'] = strtolower(trim($newStage));
            $updated = $t;
            break;
        }
    }
    saveStudioTasksRepo($tasks);
    return $updated;
}

function deleteStudioTask($taskId) {
    $tasks = getStudioTasksRepo();
    $filtered = array_filter($tasks, fn($t) => ($t['id'] ?? '') !== $taskId);
    saveStudioTasksRepo($filtered);
    return true;
}

function getStudioTaskStagesFile() {
    return __DIR__ . '/../config/studio_task_stages.json';
}

function getStudioTaskStagesRepo() {
    $filePath = getStudioTaskStagesFile();
    if (file_exists($filePath)) {
        $json = @file_get_contents($filePath);
        $data = json_decode($json, true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }
    return [
        ['key' => 'ideas', 'title' => 'Ideas', 'color' => '#a855f7', 'is_default' => true],
        ['key' => 'concept', 'title' => 'Concept Development', 'color' => '#0ea5e9', 'is_default' => true],
        ['key' => 'primary', 'title' => 'Primary Development', 'color' => '#f59e0b', 'is_default' => true],
        ['key' => 'ongoing', 'title' => 'On Going', 'color' => '#3b82f6', 'is_default' => true],
        ['key' => 'pending_review', 'title' => 'Pending Review', 'color' => '#ec4899', 'is_default' => true],
        ['key' => 'completed', 'title' => 'Completed', 'color' => '#22c55e', 'is_default' => true]
    ];
}

function saveStudioTaskStagesRepo($stages) {
    $filePath = getStudioTaskStagesFile();
    @file_put_contents($filePath, json_encode(array_values($stages), JSON_PRETTY_PRINT));
}

function createStudioTaskStage($title, $color = '#3b82f6') {
    $stages = getStudioTaskStagesRepo();
    $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($title)));
    if (empty($key)) $key = 'stage_' . time();
    
    foreach ($stages as $s) {
        if (($s['key'] ?? '') === $key) {
            $key .= '_' . rand(10, 99);
            break;
        }
    }

    $newStage = [
        'key' => $key,
        'title' => trim($title),
        'color' => !empty($color) ? $color : '#3b82f6',
        'is_default' => false
    ];

    $stages[] = $newStage;
    saveStudioTaskStagesRepo($stages);
    return $newStage;
}

function deleteStudioTaskStage($stageKey) {
    $stages = getStudioTaskStagesRepo();
    $filtered = [];
    foreach ($stages as $s) {
        if (($s['key'] ?? '') !== $stageKey) {
            $filtered[] = $s;
        }
    }
    saveStudioTaskStagesRepo($filtered);
    return true;
}

function updateStudioTaskStageLabel($stageKey, $title, $color = null) {
    $stages = getStudioTaskStagesRepo();
    $updated = null;
    foreach ($stages as &$s) {
        if (($s['key'] ?? '') === $stageKey) {
            if (!empty($title)) {
                $s['title'] = trim($title);
            }
            if (!empty($color)) {
                $s['color'] = trim($color);
            }
            $updated = $s;
            break;
        }
    }
    saveStudioTaskStagesRepo($stages);
    return $updated;
}
