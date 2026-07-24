<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_CONTENT_WRITER);

$writerId = Auth::id();
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$article = null;

if ($articleId !== null) {
    $article = ArticleRepository::findOwned($articleId, $writerId);
    if (!$article || !in_array($article['status'], ['draft', 'returned'], true)) {
        http_response_code(404);
        exit('Article not found or cannot be edited.');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $blockId = (int) ($_POST['block_id'] ?? 0);
    $content = Sanitizer::articleHtml((string) ($_POST['content'] ?? ''));
    $action = $_POST['action'] ?? 'save'; // 'save' or 'send'

    $validBlockIds = array_column(ArticleRepository::blocks(), 'id');

    if ($title === '' || mb_strlen($title) > 255) {
        $errors[] = 'Title is required (max 255 characters).';
    }
    if (!in_array($blockId, $validBlockIds, true)) {
        $errors[] = 'Please select a valid block.';
    }
    if (strip_tags($content) === '' && $content === '') {
        $errors[] = 'Content cannot be empty.';
    }

    if (empty($errors)) {
        if ($article === null) {
            $articleId = ArticleRepository::create($writerId, $title, $blockId, $content);
        } else {
            $articleId = (int) $article['id'];
            ArticleRepository::updateContent($articleId, $title, $blockId, $content);
        }

        // Remove images the writer unchecked.
        foreach ((array) ($_POST['delete_image'] ?? []) as $imageId) {
            ArticleRepository::deleteImage((int) $imageId, $articleId);
        }

        $existingCount = ArticleRepository::imageCount($articleId);
        $newFiles = $_FILES['images'] ?? null;
        $uploadCount = 0;
        if ($newFiles) {
            foreach ($newFiles['error'] as $index => $err) {
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $uploadCount++;
            }
        }

        if ($existingCount + $uploadCount > ARTICLE_MAX_IMAGES) {
            $errors[] = 'An article can have at most ' . ARTICLE_MAX_IMAGES . ' images.';
        } else {
            $sortOrder = $existingCount;
            if ($newFiles) {
                foreach ($newFiles['error'] as $index => $err) {
                    if ($err === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $file = [
                        'name'     => $newFiles['name'][$index],
                        'type'     => $newFiles['type'][$index],
                        'tmp_name' => $newFiles['tmp_name'][$index],
                        'error'    => $newFiles['error'][$index],
                        'size'     => $newFiles['size'][$index],
                    ];
                    $result = ImageUploader::store($file, $articleId);
                    if ($result['error']) {
                        $errors[] = $result['error'];
                        continue;
                    }
                    ArticleRepository::addImage($articleId, $result['path'], $sortOrder++);
                }
            }
        }

        $totalImages = ArticleRepository::imageCount($articleId);

        if (empty($errors) && $action === 'send') {
            if ($totalImages < ARTICLE_MIN_IMAGES || $totalImages > ARTICLE_MAX_IMAGES) {
                $errors[] = 'An article needs between ' . ARTICLE_MIN_IMAGES . ' and ' . ARTICLE_MAX_IMAGES . ' images to be sent for review.';
            } else {
                ArticleRepository::submitForReview($articleId);
                header('Location: /writer/dashboard.php?tab=sent');
                exit;
            }
        }

        if (empty($errors)) {
            header('Location: /writer/dashboard.php?tab=' . ($article !== null && $article['status'] === 'returned' ? 'returned' : 'draft'));
            exit;
        }

        // Re-fetch so the form below reflects what was actually saved.
        $article = ArticleRepository::find($articleId);
    }
}

$blocks = ArticleRepository::blocks();
$images = $articleId ? ArticleRepository::imagesFor($articleId) : [];
$title = $article['title'] ?? ($_POST['title'] ?? '');
$blockId = $article['block_id'] ?? ($_POST['block_id'] ?? '');
$content = $article['content'] ?? ($_POST['content'] ?? '');
$returnReason = $article['return_reason'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $article ? 'Edit Article' : 'New Article' ?> - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = 'new'; require __DIR__ . '/_nav.php'; ?>
<main>
    <div class="card">
        <h1><?= $article ? 'Edit Article' : 'New Article' ?></h1>

        <?php if ($returnReason): ?>
            <p class="error">Returned by Super Admin: <?= e($returnReason) ?></p>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" action="/writer/article_form.php<?= $articleId ? '?id=' . $articleId : '' ?>">
            <?= csrf_field() ?>

            <label for="title">News Title</label>
            <input type="text" id="title" name="title" maxlength="255" required value="<?= e($title) ?>">

            <label for="block_id">Block</label>
            <select id="block_id" name="block_id" required>
                <option value="">Select a block</option>
                <?php foreach ($blocks as $block): ?>
                    <option value="<?= (int) $block['id'] ?>" <?= (int) $blockId === (int) $block['id'] ? 'selected' : '' ?>>
                        <?= e($block['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Images (<?= ARTICLE_MIN_IMAGES ?>-<?= ARTICLE_MAX_IMAGES ?> required to send)</label>
            <?php foreach ($images as $image): ?>
                <div>
                    <img class="image-thumb" src="<?= e($image['file_path']) ?>" alt="">
                    <label><input type="checkbox" name="delete_image[]" value="<?= (int) $image['id'] ?>"> Remove</label>
                </div>
            <?php endforeach; ?>
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>

            <label for="content">Content</label>
            <div class="rte">
                <div class="editor-toolbar">
                    <button type="button" data-cmd="bold"><b>B</b></button>
                    <button type="button" data-cmd="italic"><i>I</i></button>
                    <button type="button" data-cmd="underline"><u>U</u></button>
                    <button type="button" data-cmd="insertUnorderedList">• List</button>
                    <button type="button" data-cmd="insertOrderedList">1. List</button>
                    <button type="button" data-cmd="formatBlock" data-value="h2">H2</button>
                    <button type="button" data-cmd="formatBlock" data-value="blockquote">Quote</button>
                </div>
                <div class="editor-content" contenteditable="true"></div>
            </div>
            <textarea id="content" name="content" style="display:none;"><?= e($content) ?></textarea>

            <div class="actions">
                <button type="submit" name="action" value="save">Save</button>
                <button type="submit" name="action" value="send">Send</button>
            </div>
        </form>
    </div>
</main>
<script src="/assets/js/editor.js"></script>
</body>
</html>
