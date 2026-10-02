<?php
// router.php for local PHP built-in web server

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// 1. Serve static files directly if they exist (excluding directories and .php files)
if ($uri !== '/' && !preg_match('/\.php$/', $uri) && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// 2. Redirect old .php URLs to clean URLs (GET requests only to preserve POST payloads)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Redirect trailing slashes for clean URLs (excluding actual directories)
    if ($uri !== '/' && substr($uri, -1) === '/' && !is_dir(__DIR__ . $uri)) {
        $clean_uri = rtrim($uri, '/');
        $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: ' . $clean_uri . $query, true, 301);
        exit;
    }

    // Redirect /index.php to /
    if ($uri === '/index.php') {
        $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: /' . $query, true, 301);
        exit;
    }

    // Redirect /about.php, /about-us.php, and /about-us to /about
    if ($uri === '/about.php' || $uri === '/about-us.php' || $uri === '/about-us') {
        $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: /about' . $query, true, 301);
        exit;
    }

    // Redirect /admin/index.php and /admin/index to /admin
    if ($uri === '/admin/index.php' || $uri === '/admin/index') {
        $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: /admin' . $query, true, 301);
        exit;
    }

    // Redirect any other admin .php file to its clean hyphenated URL
    if (strncmp($uri, '/admin/', 7) === 0 && preg_match('/\.php$/', $uri)) {
        $clean_uri = preg_replace('/\.php$/', '', $uri);
        $clean_uri = str_replace('_', '-', $clean_uri);
        $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: ' . $clean_uri . $query, true, 301);
        exit;
    }

    // Redirect clean admin URLs containing underscores to hyphenated URLs
    if (strncmp($uri, '/admin/', 7) === 0 && str_contains($uri, '_')) {
        $hyphenated_uri = str_replace('_', '-', $uri);
        $file_underscore = __DIR__ . str_replace('-', '_', $uri) . '.php';
        $file_hyphen = __DIR__ . $uri . '.php';
        if (file_exists($file_underscore) || file_exists($file_hyphen)) {
            $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
            header('Location: ' . $hyphenated_uri . $query, true, 301);
            exit;
        }
    }

    // Redirect any other .php file to its clean URL
    if (preg_match('/\.php$/', $uri)) {
        $clean_uri = preg_replace('/\.php$/', '', $uri);
        if (file_exists(__DIR__ . $uri)) {
            $query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
            header('Location: ' . $clean_uri . $query, true, 301);
            exit;
        }
    }
}

// 3. Map clean URLs to PHP files
if ($uri === '/' || $uri === '') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
    chdir(__DIR__);
    require __DIR__ . '/index.php';
    exit;
}

if ($uri === '/about') {
    $_SERVER['SCRIPT_NAME'] = '/about-us.php';
    $_SERVER['PHP_SELF'] = '/about-us.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/about-us.php';
    chdir(__DIR__);
    require __DIR__ . '/about-us.php';
    exit;
}

if ($uri === '/complaint' || $uri === '/complaints' || $uri === '/suggestion' || $uri === '/suggestions' || $uri === '/complaint-suggestion') {
    $_SERVER['SCRIPT_NAME'] = '/complaint.php';
    $_SERVER['PHP_SELF'] = '/complaint.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/complaint.php';
    chdir(__DIR__);
    require __DIR__ . '/complaint.php';
    exit;
}

if ($uri === '/track-complaint' || $uri === '/track-ticket' || $uri === '/track-suggestion' || $uri === '/track') {
    $_SERVER['SCRIPT_NAME'] = '/track-complaint.php';
    $_SERVER['PHP_SELF'] = '/track-complaint.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/track-complaint.php';
    chdir(__DIR__);
    require __DIR__ . '/track-complaint.php';
    exit;
}

if ($uri === '/feedback' || $uri === '/feedbacks' || $uri === '/employee-feedback' || $uri === '/member-feedback') {
    $_SERVER['SCRIPT_NAME'] = '/feedback.php';
    $_SERVER['PHP_SELF'] = '/feedback.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/feedback.php';
    chdir(__DIR__);
    require __DIR__ . '/feedback.php';
    exit;
}

if ($uri === '/track-feedback' || $uri === '/track-review') {
    $_SERVER['SCRIPT_NAME'] = '/track-feedback.php';
    $_SERVER['PHP_SELF'] = '/track-feedback.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/track-feedback.php';
    chdir(__DIR__);
    require __DIR__ . '/track-feedback.php';
    exit;
}

if ($uri === '/verify-doctor-certificate' || $uri === '/verify-doctor' || $uri === '/verify-empanelment') {
    $_SERVER['SCRIPT_NAME'] = '/verify-doctor-certificate.php';
    $_SERVER['PHP_SELF'] = '/verify-doctor-certificate.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/verify-doctor-certificate.php';
    chdir(__DIR__);
    require __DIR__ . '/verify-doctor-certificate.php';
    exit;
}

if ($uri === '/verify-sanstha-certificate' || $uri === '/verify-sanstha' || $uri === '/verify-authorization') {
    $_SERVER['SCRIPT_NAME'] = '/verify-sanstha-certificate.php';
    $_SERVER['PHP_SELF'] = '/verify-sanstha-certificate.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/verify-sanstha-certificate.php';
    chdir(__DIR__);
    require __DIR__ . '/verify-sanstha-certificate.php';
    exit;
}

if ($uri === '/join-us' || $uri === '/join' || $uri === '/join-foundation' || $uri === '/apply-now') {
    $_SERVER['SCRIPT_NAME'] = '/join-us.php';
    $_SERVER['PHP_SELF'] = '/join-us.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/join-us.php';
    chdir(__DIR__);
    require __DIR__ . '/join-us.php';
    exit;
}

if ($uri === '/admin' || $uri === '/admin/') {
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    $_SERVER['PHP_SELF'] = '/admin/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/admin/index.php';
    chdir(__DIR__ . '/admin');
    require __DIR__ . '/admin/index.php';
    exit;
}

// Map admin clean URLs to PHP files, resolving hyphens to underscores if needed
if (strncmp($uri, '/admin/', 7) === 0) {
    // 1. Check if the file exists exactly as requested (e.g. template-builder.php)
    $php_file = __DIR__ . $uri . '.php';
    if (file_exists($php_file)) {
        $_SERVER['SCRIPT_NAME'] = $uri . '.php';
        $_SERVER['PHP_SELF'] = $uri . '.php';
        $_SERVER['SCRIPT_FILENAME'] = $php_file;
        chdir(dirname($php_file));
        require $php_file;
        exit;
    }

    // 2. Check if replacing hyphens with underscores matches a file (e.g. document-studio -> document_studio.php)
    $path_with_underscores = str_replace('-', '_', $uri);
    $php_file_underscore = __DIR__ . $path_with_underscores . '.php';
    if (file_exists($php_file_underscore)) {
        $_SERVER['SCRIPT_NAME'] = $path_with_underscores . '.php';
        $_SERVER['PHP_SELF'] = $path_with_underscores . '.php';
        $_SERVER['SCRIPT_FILENAME'] = $php_file_underscore;
        chdir(dirname($php_file_underscore));
        require $php_file_underscore;
        exit;
    }
}

// Check if a corresponding PHP file exists (e.g. /projects -> projects.php)
$php_file = __DIR__ . $uri . '.php';
if (file_exists($php_file)) {
    $_SERVER['SCRIPT_NAME'] = $uri . '.php';
    $_SERVER['PHP_SELF'] = $uri . '.php';
    $_SERVER['SCRIPT_FILENAME'] = $php_file;
    chdir(dirname($php_file));
    require $php_file;
    exit;
}

// 4. Default fallback: return false so PHP built-in server outputs 404
return false;
