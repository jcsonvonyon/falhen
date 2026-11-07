<?php
// Built-in PHP development server router
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$filePath = __DIR__ . $uri;

// Redirect /index.php to /
if ($uri === '/index.php') {
    header("Location: /", true, 301);
    exit;
}

// Redirect direct .php requests to clean extensionless URLs (e.g. /contact.php -> /contact)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && preg_match('#^/(.+)\.php$#', $uri, $matches) && !str_starts_with($uri, '/api/')) {
    $cleanPath = '/' . $matches[1];
    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: " . $cleanPath . $queryString, true, 301);
    exit;
}

// 1. Serve static files directly if they exist
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 1b. Host-based routing for client.falhen.com subdomain & clean SEO album paths
$hostHeader = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($hostHeader === 'client.falhen.com' || str_starts_with($hostHeader, 'client.')) {
    if (preg_match('#^/(?:album|gallery)/([^/]+)/?$#', $uri, $matches)) {
        $_GET['slug'] = $matches[1];
        require __DIR__ . '/client-portal.php';
        return;
    }
    if (preg_match('#^/clients/([^/]+)/([^/]+)/?$#', $uri, $matches)) {
        $_GET['category'] = $matches[1];
        $_GET['slug'] = $matches[2];
        require __DIR__ . '/client-portal.php';
        return;
    }
    if ($uri === '/' || $uri === '/gallery' || $uri === '/gallery/' || $uri === '/clients' || $uri === '/clients/') {
        if ($uri === '/clients' || $uri === '/clients/') {
            $_GET['view'] = 'clients';
        }
        require __DIR__ . '/client-portal.php';
        return;
    }
}

// 2. Custom route aliases
if (preg_match('#^/album/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/client-portal.php';
    return;
}
if (preg_match('#^/clients/([^/]+)/([^/]+)/?$#', $uri, $matches)) {
    $_GET['category'] = $matches[1];
    $_GET['slug'] = $matches[2];
    require __DIR__ . '/client-portal.php';
    return;
}
if ($uri === '/clients' || $uri === '/clients/') {
    $_GET['view'] = 'clients';
    require __DIR__ . '/client-portal.php';
    return;
}
if ($uri === '/client-portal' || $uri === '/client-portal/') {
    require __DIR__ . '/client-portal.php';
    return;
}
if ($uri === '/dashboard' || $uri === '/dashboard/' || $uri === '/admin/login' || $uri === '/admin/login/') {
    require __DIR__ . '/admin/login.php';
    return;
}

if (preg_match('#^/services/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/service-single.php';
    return;
}

// 3. Extensionless PHP files (e.g., /about -> /about.php)
if (file_exists($filePath . '.php')) {
    require $filePath . '.php';
    return;
}

// 4. Directory index fallback
if (is_dir($filePath) && file_exists(rtrim($filePath, '/') . '/index.php')) {
    require rtrim($filePath, '/') . '/index.php';
    return;
}

// Fallback to default behavior
return false;
