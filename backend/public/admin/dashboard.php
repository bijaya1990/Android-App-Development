<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_SUPER_ADMIN);

$validStatuses = ['pending_review', 'draft', 'published', 'returned'];
$tab = $_GET['tab'] ?? 'pending_review';
if (!in_array($tab, $validStatuses, true)) {
    $tab = 'pending_review';
}

$articles = ArticleRepository::listAllByStatus($tab);
$blocks = array_column(ArticleRepository::blocks(), 'name', 'id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = $tab; require __DIR__ . '/_nav.php'; ?>
<main>
    <div class="card">
        <h1><?= e(ucwords(str_replace('_', ' ', $tab))) ?></h1>

        <?php if (empty($articles)): ?>
            <p>No articles here.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr><th>Title</th><th>Block</th><th>Writer</th><th>Updated</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?= e($article['title']) ?><?= $article['breaking_news'] ? ' <strong>(Breaking)</strong>' : '' ?></td>
                        <td><?= e($blocks[$article['block_id']] ?? '') ?></td>
                        <td><?= e($article['writer_name']) ?></td>
                        <td><?= e($article['updated_at']) ?></td>
                        <td><a href="/admin/article_review.php?id=<?= (int) $article['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
