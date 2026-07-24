<?php
/**
 * Data access for user accounts (Content Writer management).
 */
declare(strict_types=1);

final class UserRepository
{
    public static function listWriters(): array
    {
        $stmt = get_db()->query(
            "SELECT id, username, full_name, status, created_at FROM users
             WHERE role = 'content_writer' ORDER BY created_at DESC"
        );

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = get_db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function usernameExists(string $username): bool
    {
        $stmt = get_db()->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);

        return (bool) $stmt->fetchColumn();
    }

    public static function createWriter(string $username, string $fullName, string $passwordHash): int
    {
        $stmt = get_db()->prepare(
            "INSERT INTO users (username, password_hash, full_name, role, status)
             VALUES (:username, :password_hash, :full_name, 'content_writer', 'active')"
        );
        $stmt->execute([
            'username'      => $username,
            'password_hash' => $passwordHash,
            'full_name'     => $fullName,
        ]);

        return (int) get_db()->lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = get_db()->prepare("UPDATE users SET status = :status WHERE id = :id AND role = 'content_writer'");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function resetPassword(int $id, string $passwordHash): void
    {
        $stmt = get_db()->prepare("UPDATE users SET password_hash = :hash WHERE id = :id AND role = 'content_writer'");
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    public static function deleteWriter(int $id): void
    {
        $stmt = get_db()->prepare("DELETE FROM users WHERE id = :id AND role = 'content_writer'");
        $stmt->execute(['id' => $id]);
    }
}
