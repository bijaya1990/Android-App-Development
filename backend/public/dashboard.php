<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

header('Location: ' . (Auth::role() === ROLE_CONTENT_WRITER ? '/writer/dashboard.php' : '/admin/dashboard.php'));
exit;
