<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_CONTENT_WRITER);

$tabToStatus = [
    'draft'     => 'draft',
    'sent'      => 'pending_review',
    'published' => 'published',
    'returned'  => 'returned',
];

$tab = $_GET['tab'] ?? 'draft';
if (!isset($tabToStatus[$tab])) {
    $tab = 'draft';
}

$articles = ArticleRepository::listByWriterAndStatus(Auth::id(), $tabToStatus[$tab]);
$blocks = array_column(ArticleRepository::blocks(), 'name', 'id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Articles - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = $tab; require __DIR__ . '/_nav.php'; ?>
<main>
    <div class="card">
        <h1><?= e(ucfirst($tab)) ?> Articles</h1>

        <?php if (empty($articles)): ?>
            <p>No articles here yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr><th>Title</th><th>Block</th><th>Status</th><th>Updated</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?= e($article['title']) ?><?= $article['breaking_news'] ? ' <strong>(Breaking)</strong>' : '' ?></td>
                        <td><?= e($blocks[$article['block_id']] ?? '') ?></td>
                        <td><span class="badge badge-<?= e($article['status']) ?>"><?= e(str_replace('_', ' ', $article['status'])) ?></span></td>
                        <td><?= e($article['updated_at']) ?></td>
                        <td>
                            <?php if (in_array($article['status'], ['draft', 'returned'], true)): ?>
                                <a href="/writer/article_form.php?id=<?= (int) $article['id'] ?>">Edit</a>
                            <?php else: ?>
                                <a href="/writer/article_view.php?id=<?= (int) $article['id'] ?>">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
