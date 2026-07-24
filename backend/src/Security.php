<?php
/**
 * Small set of XSS/CSRF helpers shared by every admin/writer page.
 */
declare(strict_types=1);

/** Escape output for safe HTML rendering. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        http_response_code(403);
        exit('Invalid or expired form submission. Please go back and try again.');
    }
}

/** One-time flash message, e.g. for showing a generated password exactly once after a redirect. */
function flash_set(string $key, mixed $value): void
{
    $_SESSION['flash'][$key] = $value;
}

function flash_take(string $key): mixed
{
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}
