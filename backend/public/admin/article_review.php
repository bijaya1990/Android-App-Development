<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_SUPER_ADMIN);

$articleId = (int) ($_GET['id'] ?? 0);
$article = ArticleRepository::find($articleId);

if (!$article) {
    http_response_code(404);
    exit('Article not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $paths = ArticleRepository::delete($articleId);
        foreach ($paths as $path) {
            $file = __DIR__ . '/../' . ltrim($path, '/');
            if (is_file($file)) {
                unlink($file);
            }
        }
        header('Location: /admin/dashboard.php?tab=' . $article['status']);
        exit;
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $blockId = (int) ($_POST['block_id'] ?? 0);
    $content = Sanitizer::articleHtml((string) ($_POST['content'] ?? ''));
    $breaking = isset($_POST['breaking_news']);
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
        ArticleRepository::updateContent($articleId, $title, $blockId, $content);
        ArticleRepository::setBreakingNews($articleId, $breaking);

        foreach ((array) ($_POST['delete_image'] ?? []) as $imageId) {
            ArticleRepository::deleteImage((int) $imageId, $articleId);
        }

        $existingCount = ArticleRepository::imageCount($articleId);
        $newFiles = $_FILES['images'] ?? null;
        $uploadCount = 0;
        if ($newFiles) {
            foreach ($newFiles['error'] as $err) {
                if ($err !== UPLOAD_ERR_NO_FILE) {
                    $uploadCount++;
                }
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

        if (empty($errors)) {
            if ($action === 'publish') {
                if ($totalImages < ARTICLE_MIN_IMAGES || $totalImages > ARTICLE_MAX_IMAGES) {
                    $errors[] = 'An article needs between ' . ARTICLE_MIN_IMAGES . ' and ' . ARTICLE_MAX_IMAGES . ' images to publish.';
                } else {
                    ArticleRepository::publish($articleId, Auth::id());
                    header('Location: /admin/dashboard.php?tab=published');
                    exit;
                }
            } elseif ($action === 'return') {
                $reason = trim((string) ($_POST['return_reason'] ?? ''));
                if ($reason === '') {
                    $errors[] = 'A reason is required when returning an article.';
                } else {
                    ArticleRepository::returnToWriter($articleId, Auth::id(), $reason);
                    header('Location: /admin/dashboard.php?tab=returned');
                    exit;
                }
            } else {
                ArticleRepository::saveDraft($articleId);
                header('Location: /admin/dashboard.php?tab=draft');
                exit;
            }
        }

        $article = ArticleRepository::find($articleId);
    }
}

$blocks = ArticleRepository::blocks();
$images = ArticleRepository::imagesFor($articleId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Article - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = $article['status'] === 'published' ? 'published' : $article['status']; require __DIR__ . '/_nav.php'; ?>
<main>
    <div class="card">
        <span class="badge badge-<?= e($article['status']) ?>"><?= e(str_replace('_', ' ', $article['status'])) ?></span>
        <h1>Review Article</h1>

        <?php foreach ($errors as $error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" action="/admin/article_review.php?id=<?= $articleId ?>">
            <?= csrf_field() ?>

            <label for="title">News Title</label>
            <input type="text" id="title" name="title" maxlength="255" required value="<?= e($article['title']) ?>">

            <label for="block_id">Block</label>
            <select id="block_id" name="block_id" required>
                <?php foreach ($blocks as $block): ?>
                    <option value="<?= (int) $block['id'] ?>" <?= (int) $article['block_id'] === (int) $block['id'] ? 'selected' : '' ?>>
                        <?= e($block['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label><input type="checkbox" name="breaking_news" <?= $article['breaking_news'] ? 'checked' : '' ?>> Mark as Breaking News</label>

            <label>Images</label>
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
            <textarea id="content" name="content" style="display:none;"><?= e($article['content']) ?></textarea>

            <label for="return_reason">Return Reason (required only when returning)</label>
            <textarea id="return_reason" name="return_reason" rows="3"><?= e($article['return_reason'] ?? '') ?></textarea>

            <div class="actions">
                <button type="submit" name="action" value="save_draft">Save Draft</button>
                <button type="submit" name="action" value="publish">Publish</button>
                <button type="submit" name="action" value="return">Return</button>
                <button type="submit" name="action" value="delete" onclick="return confirm('Delete this article permanently?');">Delete</button>
            </div>
        </form>
    </div>
</main>
<script src="/assets/js/editor.js"></script>
</body>
</html>
