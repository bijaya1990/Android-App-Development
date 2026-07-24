<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_CONTENT_WRITER);

$articleId = (int) ($_GET['id'] ?? 0);
$article = ArticleRepository::findOwned($articleId, Auth::id());

if (!$article || !in_array($article['status'], ['pending_review', 'published'], true)) {
    http_response_code(404);
    exit('Article not found.');
}

$blocks = array_column(ArticleRepository::blocks(), 'name', 'id');
$images = ArticleRepository::imagesFor($articleId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($article['title']) ?> - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = $article['status'] === 'published' ? 'published' : 'sent'; require __DIR__ . '/_nav.php'; ?>
<main>
    <div class="card">
        <span class="badge badge-<?= e($article['status']) ?>"><?= e(str_replace('_', ' ', $article['status'])) ?></span>
        <h1><?= e($article['title']) ?></h1>
        <p><strong>Block:</strong> <?= e($blocks[$article['block_id']] ?? '') ?></p>

        <?php foreach ($images as $image): ?>
            <img class="image-thumb" src="<?= e($image['file_path']) ?>" alt="">
        <?php endforeach; ?>

        <div><?= $article['content'] ?></div>
        <p><em>This article is read-only while it is <?= e(str_replace('_', ' ', $article['status'])) ?>.</em></p>
    </div>
</main>
</body>
</html>
