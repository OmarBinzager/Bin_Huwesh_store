<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

$mimes = [
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'webp'  => 'image/webp',
    'ico'   => 'image/x-icon',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'eot'   => 'application/vnd.ms-fontobject',
    'json'  => 'application/json',
];

if (str_starts_with($uri, '/public/')) {
    $cleanPath = preg_replace('#^/public/#', '/', $uri);
    $fullPath = __DIR__ . '/public' . $cleanPath;
    if (file_exists($fullPath) && !is_dir($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($fullPath);
        exit;
    }
}

if (str_starts_with($uri, '/storage/app/public/')) {
    $cleanPath = preg_replace('#^/storage/app/public/#', '/', $uri);
    $fullPath = __DIR__ . '/storage/app/public' . $cleanPath;
    if (file_exists($fullPath) && !is_dir($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($fullPath);
        exit;
    }
}

require_once __DIR__.'/public/index.php';
