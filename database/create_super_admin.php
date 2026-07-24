<?php
/**
 * One-time CLI script to create the first Super Admin account.
 * Run from the server shell only, e.g.:
 *   php create_super_admin.php <username> <full name>
 * A random password is generated and printed once — it is not stored
 * anywhere in plain text.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../backend/config/config.php';

$username = $argv[1] ?? null;
$fullName = $argv[2] ?? null;

if (!$username || !$fullName) {
    exit("Usage: php create_super_admin.php <username> <full name>\n");
}

$password = bin2hex(random_bytes(6)); // 12-character random password
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = get_db()->prepare(
    'INSERT INTO users (username, password_hash, full_name, role, status)
     VALUES (:username, :password_hash, :full_name, :role, :status)'
);

$stmt->execute([
    'username'      => $username,
    'password_hash' => $hash,
    'full_name'     => $fullName,
    'role'          => ROLE_SUPER_ADMIN,
    'status'        => 'active',
]);

echo "Super Admin created.\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
echo "Store this password securely — it will not be shown again.\n";
