<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

header('Location: ' . (Auth::check() ? '/dashboard.php' : '/login.php'));
exit;
