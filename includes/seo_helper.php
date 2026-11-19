<?php
/**
 * SEO & Structured Data Helper Engine
 * Brand: Falhen Productions
 * Domain: https://www.falhen.com
 */

if (!function_exists('getSiteBaseUrl')) {
    function getSiteBaseUrl() {
        return 'https://www.falhen.com';
    }
}

if (!function_exists('getCleanCanonicalUrl')) {
    function getCleanCanonicalUrl($customPath = null) {
        $baseUrl = getSiteBaseUrl();
        if ($customPath !== null) {
            $cleanPath = '/' . ltrim($customPath, '/');
            return $baseUrl . $cleanPath;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $pathOnly   = explode('?', $requestUri)[0];
        
        // Strip trailing .php and trailing slashes (except root /)
        $cleanPath = preg_replace('/\.php$/i', '', $pathOnly);
        $cleanPath = '/' . trim($cleanPath, '/');
        if ($cleanPath === '//' || $cleanPath === '') {
            $cleanPath = '/';
        }

        return $baseUrl . $cleanPath;
    }
}

if (!function_exists('getSeoConfig')) {
    function getSeoConfig($pageKey = '', $customData = []) {
        $brandName = 'Falhen Productions';
        $defaultImage = 'https://res.cloudinary.com/pnabfi91/image/upload/f_auto,q_auto,w_1200,h_630,c_fill/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg';
        
        $seoDefaults = [
            'home' => [
                'title'       => 'Falhen Productions | Video Production & Visual Storytelling',
                'description' => 'Full-service video production company specializing in commercial video, concert cinematography, live streaming, and 3D motion graphics. Create what the world watches. ✓',
                'canonical'   => getCleanCanonicalUrl('/'),
                'og_type'     => 'website',
                'keywords'    => 'video production, commercial videography, concert cinema, live streaming, animation, post production, brand storytelling, Chicago video production',
            ],
            'about' => [
                'title'       => 'About Falhen Productions | Vision, Team & Storytelling',
                'description' => 'Meet the directors, cinematographers, and creative producers behind Falhen Productions. Over 12 years of crafting high-impact visual narratives globally. Learn our story.',
                'canonical'   => getCleanCanonicalUrl('/about'),
                'og_type'     => 'website',
                'keywords'    => 'about Falhen Productions, video production team, creative directors, cinematography crew, film production company',
            ],
            'services' => [
                'title'       => 'Video Production Services | Falhen Productions',
                'description' => 'End-to-end media production services: commercial film, multi-camera live streaming, post-production, 3D motion design, and event cinematography. Discover our craft.',
                'canonical'   => getCleanCanonicalUrl('/services'),
                'og_type'     => 'website',
                'keywords'    => 'video production services, commercial video production, live streaming services, color grading, 3D animation, corporate media',
            ],
            'portfolio' => [
                'title'       => 'Portfolio & Work Showcase | Falhen Productions',
                'description' => 'Explore 12+ years of award-winning commercial video production, concert cinema, brand documentaries, and photography crafted by Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/portfolio'),
                'og_type'     => 'website',
                'keywords'    => 'video production portfolio, cinematography reel, concert film showcase, commercial film portfolio, Falhen Productions work',
            ],
            'blog' => [
                'title'       => 'Production Insights & Industry News | Falhen Productions',
                'description' => 'Behind-the-scenes insights, filmmaking gear breakdowns, live broadcast workflows, and creative direction trends from the team at Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/blog'),
                'og_type'     => 'website',
                'keywords'    => 'filmmaking blog, video production tips, camera gear reviews, live streaming tech, cinematic lighting',
            ],
            'careers' => [
                'title'       => 'Careers & Openings | Join the Falhen Productions Crew',
                'description' => 'Join our growing team of cinematographers, video editors, motion graphic artists, and live broadcast engineers. View our open creative opportunities.',
                'canonical'   => getCleanCanonicalUrl('/careers'),
                'og_type'     => 'website',
                'keywords'    => 'video production jobs, filmmaker careers, video editor openings, cinematography gigs, Falhen Productions jobs',
            ],
            'contact' => [
                'title'       => 'Contact Falhen Productions | Start Your Production Today',
                'description' => 'Ready to bring your vision to screen? Contact Falhen Productions for commercial video, multicam live streaming, or creative consultation. Inquire today.',
                'canonical'   => getCleanCanonicalUrl('/contact'),
                'og_type'     => 'website',
                'keywords'    => 'contact video production company, hire videographer, quote video production, production agency inquiry',
            ],
            'terms' => [
                'title'       => 'Terms of Service | Falhen Productions',
                'description' => 'Read the terms and conditions governing production agreements, intellectual property, licensing, and services provided by Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/terms'),
                'og_type'     => 'website',
                'robots'      => 'noindex, follow',
            ],
            'privacy' => [
                'title'       => 'Privacy Policy | Falhen Productions',
                'description' => 'Review our privacy policy regarding client data, consent, digital media storage, and online browsing privacy at Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/privacy'),
                'og_type'     => 'website',
                'robots'      => 'noindex, follow',
            ],
            'cookies' => [
                'title'       => 'Cookie Policy | Falhen Productions',
                'description' => 'Learn how Falhen Productions uses cookies, analytics tokens, and local preferences to provide optimal viewing experiences.',
                'canonical'   => getCleanCanonicalUrl('/cookies'),
                'og_type'     => 'website',
                'robots'      => 'noindex, follow',
            ],
            'faq' => [
                'title'       => 'Frequently Asked Questions | Falhen Productions',
                'description' => 'Everything you need to know about booking, production timelines, equipment capabilities, licensing, and deliverables with Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/faq'),
                'og_type'     => 'website',
            ],
            'dpa' => [
                'title'       => 'Data Processing Agreement (DPA) | Falhen Productions',
                'description' => 'Data Processing Agreement guidelines outlining compliance, digital asset retention, and GDPR/CCPA standards at Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/dpa'),
                'og_type'     => 'website',
                'robots'      => 'noindex, follow',
            ],
            '404' => [
                'title'       => 'Page Not Found (404) | Falhen Productions',
                'description' => 'The page you are looking for could not be found. Explore our video production showcase, services, or contact Falhen Productions.',
                'canonical'   => getCleanCanonicalUrl('/404'),
                'og_type'     => 'website',
                'robots'      => 'noindex, follow',
            ],
        ];

        $pageConfig = $seoDefaults[$pageKey] ?? [
            'title'       => (!empty($customData['title']) ? $customData['title'] . ' | ' . $brandName : $brandName . ' | Video Production & Visual Storytelling'),
            'description' => $customData['description'] ?? 'Award-winning visual storytelling and full-service media production by Falhen Productions.',
            'canonical'   => $customData['canonical'] ?? getCleanCanonicalUrl(),
            'og_type'     => $customData['og_type'] ?? 'website',
            'keywords'    => $customData['keywords'] ?? 'video production, cinematography, Falhen Productions',
        ];

        // Merge custom overrides
        if (!empty($customData['title'])) {
            $pageConfig['title'] = strpos($customData['title'], $brandName) !== false ? $customData['title'] : $customData['title'] . ' | ' . $brandName;
        }
        if (!empty($customData['description'])) {
            $pageConfig['description'] = $customData['description'];
        }
        if (!empty($customData['canonical'])) {
            $pageConfig['canonical'] = $customData['canonical'];
        }
        if (!empty($customData['robots'])) {
            $pageConfig['robots'] = $customData['robots'];
        } elseif (!isset($pageConfig['robots'])) {
            $pageConfig['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        }

        $pageConfig['image'] = !empty($customData['image']) ? $customData['image'] : $defaultImage;
        $pageConfig['brand'] = $brandName;

        return $pageConfig;
    }
}

if (!function_exists('renderSeoHeadTags')) {
    function renderSeoHeadTags($seoConfig = []) {
        $title       = htmlspecialchars($seoConfig['title'] ?? 'Falhen Productions');
        $description = htmlspecialchars($seoConfig['description'] ?? '');
        $keywords    = htmlspecialchars($seoConfig['keywords'] ?? '');
        $canonical   = htmlspecialchars($seoConfig['canonical'] ?? getCleanCanonicalUrl());
        $robots      = htmlspecialchars($seoConfig['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
        $image       = htmlspecialchars($seoConfig['image'] ?? '');
        $ogType      = htmlspecialchars($seoConfig['og_type'] ?? 'website');
        $brand       = htmlspecialchars($seoConfig['brand'] ?? 'Falhen Productions');

        // Optional Google Search Console Site Verification Tag
        $gscToken = getenv('GSC_VERIFICATION_TOKEN') ?: '';
        ?>
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $description; ?>">
    <?php if ($keywords !== ''): ?>
    <meta name="keywords" content="<?php echo $keywords; ?>">
    <?php endif; ?>
    <meta name="author" content="<?php echo $brand; ?>">
    <meta name="robots" content="<?php echo $robots; ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">

    <!-- Open Graph Metadata -->
    <meta property="og:site_name" content="<?php echo $brand; ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:type" content="<?php echo $ogType; ?>">
    <meta property="og:image" content="<?php echo $image; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Metadata -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@falhenproductions">
    <meta name="twitter:title" content="<?php echo $title; ?>">
    <meta name="twitter:description" content="<?php echo $description; ?>">
    <meta name="twitter:image" content="<?php echo $image; ?>">

    <?php if (!empty($gscToken)): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($gscToken); ?>">
    <?php endif; ?>
        <?php
    }
}

if (!function_exists('renderSchemaJsonLd')) {
    function renderSchemaJsonLd($schemaType = 'global', $customData = []) {
        $baseUrl = getSiteBaseUrl();
        $brandName = 'Falhen Productions';
        $logoUrl = $baseUrl . '/assets/img/icons/logo.png';
        $defaultImage = 'https://res.cloudinary.com/pnabfi91/image/upload/v1786578372/falhen/uploads/r96mxsji9cdogqrkxwoe.jpg';

        $graph = [
            [
                '@type' => ['Organization', 'VideoProductionService'],
                '@id' => $baseUrl . '/#organization',
                'name' => $brandName,
                'alternateName' => ['Falhen', 'Falhen Productions Production'],
                'url' => $baseUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                    'width' => 512,
                    'height' => 512,
                ],
                'image' => $defaultImage,
                'description' => 'Full-service video production company specializing in commercial film, multi-camera concert cinematography, live streaming, and 3D motion design.',
                'telephone' => '+1-312-555-0199',
                'email' => 'info@falhen.com',
                'priceRange' => '$$$',
                'areaServed' => [
                    [
                        '@type' => 'AdministrativeArea',
                        'name' => 'Chicago, IL',
                    ],
                    [
                        '@type' => 'Country',
                        'name' => 'United States',
                    ],
                    [
                        '@type' => 'Place',
                        'name' => 'Global',
                    ]
                ],
                'sameAs' => [
                    'https://www.instagram.com/falhenproductions',
                    'https://www.youtube.com/@falhenproductions',
                    'https://www.linkedin.com/company/falhen',
                    'https://www.facebook.com/falhenproductions'
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+1-312-555-0199',
                    'contactType' => 'customer support',
                    'email' => 'info@falhen.com',
                    'availableLanguage' => ['English']
                ]
            ],
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl . '/#website',
                'url' => $baseUrl,
                'name' => $brandName,
                'publisher' => [
                    '@id' => $baseUrl . '/#organization'
                ],
                'inLanguage' => 'en-US'
            ]
        ];

        // BreadcrumbList schema if provided
        if (!empty($customData['breadcrumbs']) && is_array($customData['breadcrumbs'])) {
            $itemListElement = [];
            $pos = 1;
            foreach ($customData['breadcrumbs'] as $bc) {
                $itemListElement[] = [
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'name' => $bc['name'],
                    'item' => $bc['url']
                ];
            }
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => ($customData['canonical'] ?? $baseUrl) . '/#breadcrumbs',
                'itemListElement' => $itemListElement
            ];
        }

        // Service Schema for Service Pages
        if ($schemaType === 'service' && !empty($customData['service'])) {
            $s = $customData['service'];
            $graph[] = [
                '@type' => 'Service',
                '@id' => ($customData['canonical'] ?? $baseUrl . '/services') . '/#service',
                'name' => $s['title'] ?? 'Media Production Service',
                'serviceType' => $s['category'] ?? 'Video Production',
                'description' => $s['desc'] ?? '',
                'provider' => [
                    '@id' => $baseUrl . '/#organization'
                ],
                'areaServed' => 'United States'
            ];
        }

        // BlogPosting Schema for Blog Post
        if ($schemaType === 'blog' && !empty($customData['post'])) {
            $p = $customData['post'];
            $graph[] = [
                '@type' => 'BlogPosting',
                '@id' => ($customData['canonical'] ?? $baseUrl . '/blog') . '/#article',
                'headline' => $p['title'] ?? '',
                'description' => $p['excerpt'] ?? ($customData['description'] ?? ''),
                'image' => !empty($p['image']) ? $p['image'] : $defaultImage,
                'datePublished' => !empty($p['date']) ? date('c', strtotime($p['date'])) : date('c'),
                'dateModified' => date('c'),
                'author' => [
                    '@type' => 'Person',
                    'name' => $p['author'] ?? 'Falhen Creative Team',
                    'jobTitle' => 'Filmmaker & Producer',
                    'worksFor' => [
                        '@id' => $baseUrl . '/#organization'
                    ]
                ],
                'publisher' => [
                    '@id' => $baseUrl . '/#organization'
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $customData['canonical'] ?? $baseUrl . '/blog'
                ]
            ];
        }

        // VideoObject Schema ONLY on genuine Video Watch Pages (Resolves GSC Video Indexing Warning!)
        if ($schemaType === 'video_watch' && !empty($customData['video'])) {
            $v = $customData['video'];
            $graph[] = [
                '@type' => 'VideoObject',
                '@id' => ($customData['canonical'] ?? $baseUrl) . '/#video',
                'name' => $v['title'] ?? 'Production Reel',
                'description' => $v['description'] ?? 'Cinematic production showcase by Falhen Productions.',
                'thumbnailUrl' => [
                    !empty($v['thumbnail']) ? $v['thumbnail'] : $defaultImage
                ],
                'uploadDate' => !empty($v['upload_date']) ? $v['upload_date'] : '2026-01-01T08:00:00+00:00',
                'embedUrl' => $v['embed_url'] ?? '',
                'contentUrl' => $v['content_url'] ?? '',
                'publisher' => [
                    '@id' => $baseUrl . '/#organization'
                ]
            ];
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => $graph
        ];

        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
