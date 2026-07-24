<?php
/**
 * App bootstrap: environment, secure session, and class autoloading.
 * Every entry point in public/ must require this file first.
 */
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

const ROLE_SUPER_ADMIN   = 'super_admin';
const ROLE_CONTENT_WRITER = 'content_writer';

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => env('SESSION_COOKIE_SECURE', '1') === '1',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name('talpadar_session');
    session_start();
}

// Baseline hardening headers; the Android app never renders any of this
// HTML, but the admin/writer panels do, so protect them.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
