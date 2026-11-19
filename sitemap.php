<?php
/**
 * Dynamic XML Sitemap Generator
 * Brand: Falhen Productions
 * URL: https://www.falhen.com/sitemap.xml
 */

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex, follow', true);

require_once __DIR__ . '/includes/functions.php';

$baseUrl = 'https://www.falhen.com';
$today   = date('Y-m-d');

// Core Static URLs
$urls = [
    [
        'loc'        => $baseUrl . '/',
        'lastmod'    => $today,
        'changefreq' => 'daily',
        'priority'   => '1.0'
    ],
    [
        'loc'        => $baseUrl . '/services',
        'lastmod'    => $today,
        'changefreq' => 'weekly',
        'priority'   => '0.9'
    ],
    [
        'loc'        => $baseUrl . '/portfolio',
        'lastmod'    => $today,
        'changefreq' => 'weekly',
        'priority'   => '0.9'
    ],
    [
        'loc'        => $baseUrl . '/about',
        'lastmod'    => $today,
        'changefreq' => 'monthly',
        'priority'   => '0.8'
    ],
    [
        'loc'        => $baseUrl . '/contact',
        'lastmod'    => $today,
        'changefreq' => 'monthly',
        'priority'   => '0.8'
    ],
    [
        'loc'        => $baseUrl . '/blog',
        'lastmod'    => $today,
        'changefreq' => 'weekly',
        'priority'   => '0.7'
    ],
    [
        'loc'        => $baseUrl . '/careers',
        'lastmod'    => $today,
        'changefreq' => 'monthly',
        'priority'   => '0.6'
    ],
    [
        'loc'        => $baseUrl . '/faq',
        'lastmod'    => $today,
        'changefreq' => 'monthly',
        'priority'   => '0.5'
    ],
    [
        'loc'        => $baseUrl . '/privacy',
        'lastmod'    => '2026-01-01',
        'changefreq' => 'yearly',
        'priority'   => '0.3'
    ],
    [
        'loc'        => $baseUrl . '/terms',
        'lastmod'    => '2026-01-01',
        'changefreq' => 'yearly',
        'priority'   => '0.3'
    ],
    [
        'loc'        => $baseUrl . '/cookies',
        'lastmod'    => '2026-01-01',
        'changefreq' => 'yearly',
        'priority'   => '0.3'
    ],
    [
        'loc'        => $baseUrl . '/dpa',
        'lastmod'    => '2026-01-01',
        'changefreq' => 'yearly',
        'priority'   => '0.3'
    ]
];

// Add Dynamic Services
if (function_exists('getServicesRepo')) {
    $services = getServicesRepo();
    foreach ($services as $slug => $service) {
        if (!empty($slug)) {
            $urls[] = [
                'loc'        => $baseUrl . '/services/' . rawurlencode($slug),
                'lastmod'    => $today,
                'changefreq' => 'monthly',
                'priority'   => '0.8'
            ];
        }
    }
}

// Add Dynamic Portfolio Projects
if (function_exists('getPortfolioRepo')) {
    $portfolio = getPortfolioRepo();
    foreach ($portfolio as $item) {
        $id = $item['id'] ?? null;
        if ($id) {
            $urls[] = [
                'loc'        => $baseUrl . '/portfolio-project?id=' . (int)$id,
                'lastmod'    => $today,
                'changefreq' => 'monthly',
                'priority'   => '0.7'
            ];
        }
    }
}

// Add Dynamic Blog Posts
if (function_exists('getBlogRepo')) {
    $posts = getBlogRepo();
    foreach ($posts as $post) {
        $slug = $post['slug'] ?? null;
        if ($slug) {
            $postDate = !empty($post['date']) ? date('Y-m-d', strtotime($post['date'])) : $today;
            $urls[] = [
                'loc'        => $baseUrl . '/blog-single?slug=' . rawurlencode($slug),
                'lastmod'    => $postDate,
                'changefreq' => 'monthly',
                'priority'   => '0.7'
            ];
        }
    }
}

echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<?php foreach ($urls as $entry): ?>
    <url>
        <loc><?php echo htmlspecialchars($entry['loc']); ?></loc>
        <lastmod><?php echo htmlspecialchars($entry['lastmod']); ?></lastmod>
        <changefreq><?php echo htmlspecialchars($entry['changefreq']); ?></changefreq>
        <priority><?php echo htmlspecialchars($entry['priority']); ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
