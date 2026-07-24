<?php
/**
 * Session-based authentication for the two roles: super_admin, content_writer.
 */
declare(strict_types=1);

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;

    public static function attempt(string $username, string $password): bool
    {
        if (self::isLockedOut()) {
            return false;
        }

        $stmt = get_db()->prepare(
            'SELECT id, username, password_hash, full_name, role, status
             FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt();
            return false;
        }

        self::clearFailedAttempts();
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (self::role() !== $role) {
            http_response_code(403);
            exit('Forbidden: insufficient permissions.');
        }
    }

    private static function isLockedOut(): bool
    {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['login_last_attempt'] ?? 0;

        if ($attempts >= self::MAX_ATTEMPTS && (time() - $lastAttempt) < self::LOCKOUT_SECONDS) {
            return true;
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            self::clearFailedAttempts();
        }

        return false;
    }

    private static function registerFailedAttempt(): void
    {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_last_attempt'] = time();
    }

    private static function clearFailedAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
    }
}
