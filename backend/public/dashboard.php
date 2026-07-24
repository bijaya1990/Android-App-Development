<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

// Placeholder only — Phase 3 replaces this with the Content Writer
// dashboard and Phase 4 with the Super Admin dashboard.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Welcome, <?= e($_SESSION['full_name']) ?></h1>
    <p>Role: <?= e(Auth::role()) ?></p>
    <p>This is a placeholder. The real Writer/Super Admin dashboards are built in the next phases.</p>
    <form method="post" action="/logout.php">
        <?= csrf_field() ?>
        <button type="submit">Log Out</button>
    </form>
</body>
</html>
