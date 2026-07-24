<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username !== '' && $password !== '' && Auth::attempt($username, $password)) {
        header('Location: /dashboard.php');
        exit;
    }

    // Deliberately generic message: never reveal whether the username exists.
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Talpadar TV News - Login</h1>

    <?php if ($error !== null): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/login.php">
        <?= csrf_field() ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Log In</button>
    </form>
</body>
</html>
