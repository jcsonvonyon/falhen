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
                'name' => 'Oluwatosin King',
                'role' => 'Operations Specialist',
                'department' => 'Creative',
                'location' => 'Lagos',
                'experience' => '4+ years at Falhen',
                'image' => '/assets/img/portfolio/portfolio_halima.png',
                'bio' => "Bridging the gap between creative vision and seamless execution. With a proactive and friendly approach, Oluwatosin ensures that complex production workflows run with precision.",
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
                'full_name'     => 'Oluwatosin King',
                'username'      => 'oluwatosin.king',
                'email'         => 'oluwatosin@falhen.com',
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

    // Auto-sync missing/placeholder avatars with team members profile photos
    $teamMembers = getTeamMembers();
    $teamAvatarMap = [];
    foreach ($teamMembers as $tm) {
        if (!empty($tm['name']) && !empty($tm['image'])) {
            $nameKey = strtolower(trim($tm['name']));
            $teamAvatarMap[$nameKey] = $tm['image'];
        }
    }

    $keyed = [];
    $modified = false;
    foreach ($items as $idx => $item) {
        $id = (int)($item['id'] ?? 0);
        $nameLower = strtolower(trim($item['full_name'] ?? ''));
        $currentAvatar = $item['avatar'] ?? '';

        // If avatar is missing or set to a portfolio image placeholder, replace with team photo
        if (empty($currentAvatar) || str_contains($currentAvatar, '/assets/img/portfolio/')) {
            if (!empty($teamAvatarMap[$nameLower])) {
                $item['avatar'] = $teamAvatarMap[$nameLower];
                $items[$idx]['avatar'] = $teamAvatarMap[$nameLower];
                $modified = true;
            }
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
    if ($r === 'super admin' || $r === 'administrator' || $r === 'admin' || str_contains($r, 'admin') || str_contains($r, 'director') || str_contains($r, 'manager')) {
        return true;
    }

    return false;
}

function isAdminRole($role) {
    return isAdminUser($role);
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

    // Personal profile and settings are always accessible
    if ($section === 'profile' || $section === 'my_profile') {
        return true;
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
    if (isAdminUser($role, $email, $username)) {
        return 'hero';
    }
    return 'dashboard';
}

/**
 * Get Attendance Logs Repository
 */
function getAttendanceLogs($username = null) {
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

    if ($username !== null && !isAdminUser()) {
        $userLogs = array_filter($logs, function($item) use ($username) {
            return strtolower(trim($item['username'] ?? '')) === strtolower(trim($username));
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







