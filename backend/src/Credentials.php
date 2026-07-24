<?php
/**
 * Username/password generation for new Content Writer accounts.
 */
declare(strict_types=1);

final class Credentials
{
    public static function generateUsername(string $fullName): string
    {
        $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $fullName));
        $base = $base !== '' ? substr($base, 0, 15) : 'writer';

        do {
            $candidate = $base . random_int(100, 999);
        } while (UserRepository::usernameExists($candidate));

        return $candidate;
    }

    public static function generatePassword(): string
    {
        // 12 random bytes -> 16 base64url characters, no ambiguous separators.
        return rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
    }
}
