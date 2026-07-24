<?php
/**
 * Data access for articles and their images. Every query is a
 * prepared statement — no user input is ever concatenated into SQL.
 */
declare(strict_types=1);

final class ArticleRepository
{
    public static function blocks(): array
    {
        return get_db()->query('SELECT id, name FROM blocks ORDER BY id')->fetchAll();
    }

    public static function blockById(int $id): ?array
    {
        $stmt = get_db()->prepare('SELECT id, name FROM blocks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $block = $stmt->fetch();

        return $block ?: null;
    }

    public static function create(int $writerId, string $title, int $blockId, string $content): int
    {
        $stmt = get_db()->prepare(
            'INSERT INTO articles (title, block_id, content, writer_id, status)
             VALUES (:title, :block_id, :content, :writer_id, \'draft\')'
        );
        $stmt->execute([
            'title'     => $title,
            'block_id'  => $blockId,
            'content'   => $content,
            'writer_id' => $writerId,
        ]);

        return (int) get_db()->lastInsertId();
    }

    public static function updateContent(int $id, string $title, int $blockId, string $content): void
    {
        $stmt = get_db()->prepare(
            'UPDATE articles SET title = :title, block_id = :block_id, content = :content
             WHERE id = :id'
        );
        $stmt->execute([
            'title'    => $title,
            'block_id' => $blockId,
            'content'  => $content,
            'id'       => $id,
        ]);
    }

    public static function submitForReview(int $id): void
    {
        $stmt = get_db()->prepare(
            "UPDATE articles SET status = 'pending_review', return_reason = NULL WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = get_db()->prepare('SELECT * FROM articles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();

        return $article ?: null;
    }

    /** Returns the article only if it belongs to this writer, otherwise null. */
    public static function findOwned(int $id, int $writerId): ?array
    {
        $article = self::find($id);
        return ($article && (int) $article['writer_id'] === $writerId) ? $article : null;
    }

    public static function listByWriterAndStatus(int $writerId, string $status): array
    {
        $stmt = get_db()->prepare(
            'SELECT id, title, block_id, status, breaking_news, updated_at
             FROM articles WHERE writer_id = :writer_id AND status = :status
             ORDER BY updated_at DESC'
        );
        $stmt->execute(['writer_id' => $writerId, 'status' => $status]);

        return $stmt->fetchAll();
    }

    public static function addImage(int $articleId, string $path, int $sortOrder): void
    {
        $stmt = get_db()->prepare(
            'INSERT INTO article_images (article_id, file_path, sort_order) VALUES (:article_id, :path, :sort_order)'
        );
        $stmt->execute(['article_id' => $articleId, 'path' => $path, 'sort_order' => $sortOrder]);
    }

    public static function imagesFor(int $articleId): array
    {
        $stmt = get_db()->prepare(
            'SELECT id, file_path, sort_order FROM article_images WHERE article_id = :article_id ORDER BY sort_order'
        );
        $stmt->execute(['article_id' => $articleId]);

        return $stmt->fetchAll();
    }

    public static function imageCount(int $articleId): int
    {
        $stmt = get_db()->prepare('SELECT COUNT(*) FROM article_images WHERE article_id = :article_id');
        $stmt->execute(['article_id' => $articleId]);

        return (int) $stmt->fetchColumn();
    }

    public static function deleteImage(int $imageId, int $articleId): void
    {
        $stmt = get_db()->prepare('DELETE FROM article_images WHERE id = :id AND article_id = :article_id');
        $stmt->execute(['id' => $imageId, 'article_id' => $articleId]);
    }

    /** All articles in a given status, across every writer — for the Super Admin dashboard. */
    public static function listAllByStatus(string $status): array
    {
        $stmt = get_db()->prepare(
            'SELECT a.id, a.title, a.block_id, a.status, a.breaking_news, a.updated_at, u.full_name AS writer_name
             FROM articles a
             JOIN users u ON u.id = a.writer_id
             WHERE a.status = :status
             ORDER BY a.updated_at DESC'
        );
        $stmt->execute(['status' => $status]);

        return $stmt->fetchAll();
    }

    public static function setBreakingNews(int $id, bool $breaking): void
    {
        $stmt = get_db()->prepare('UPDATE articles SET breaking_news = :breaking WHERE id = :id');
        $stmt->execute(['breaking' => $breaking ? 1 : 0, 'id' => $id]);
    }

    public static function saveDraft(int $id): void
    {
        $stmt = get_db()->prepare("UPDATE articles SET status = 'draft', return_reason = NULL WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function publish(int $id, int $reviewerId): void
    {
        $stmt = get_db()->prepare(
            "UPDATE articles SET status = 'published', published_at = NOW(), reviewed_by = :reviewer, return_reason = NULL
             WHERE id = :id"
        );
        $stmt->execute(['reviewer' => $reviewerId, 'id' => $id]);
    }

    public static function returnToWriter(int $id, int $reviewerId, string $reason): void
    {
        $stmt = get_db()->prepare(
            "UPDATE articles SET status = 'returned', reviewed_by = :reviewer, return_reason = :reason
             WHERE id = :id"
        );
        $stmt->execute(['reviewer' => $reviewerId, 'reason' => $reason, 'id' => $id]);
    }

    /** Deletes the article row (article_images cascade via FK) and returns the image paths for on-disk cleanup. */
    public static function delete(int $id): array
    {
        $paths = array_column(self::imagesFor($id), 'file_path');

        $stmt = get_db()->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $paths;
    }

    // ------------------------------------------------------------------
    // Public REST API reads. These NEVER return anything but 'published'
    // articles — draft/pending_review/returned must never leak here.
    // ------------------------------------------------------------------

    /**
     * @return array{items: array, total: int}
     */
    public static function publishedPaginated(?int $blockId, ?bool $breakingOnly, int $page, int $perPage): array
    {
        $where = ["a.status = 'published'"];
        $params = [];

        if ($blockId !== null) {
            $where[] = 'a.block_id = :block_id';
            $params['block_id'] = $blockId;
        }
        if ($breakingOnly === true) {
            $where[] = 'a.breaking_news = 1';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = get_db()->prepare("SELECT COUNT(*) FROM articles a WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = get_db()->prepare(
            "SELECT a.id, a.title, a.content, a.block_id, b.name AS block_name, a.breaking_news,
                    a.published_at, u.full_name AS author
             FROM articles a
             JOIN blocks b ON b.id = a.block_id
             JOIN users u ON u.id = a.writer_id
             WHERE {$whereSql}
             ORDER BY a.published_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function publishedFind(int $id): ?array
    {
        $stmt = get_db()->prepare(
            "SELECT a.id, a.title, a.content, a.block_id, b.name AS block_name, a.breaking_news,
                    a.published_at, u.full_name AS author
             FROM articles a
             JOIN blocks b ON b.id = a.block_id
             JOIN users u ON u.id = a.writer_id
             WHERE a.id = :id AND a.status = 'published'
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();

        return $article ?: null;
    }
}
