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
}
