<?php
/**
 * Shapes a published-article DB row into the JSON contract the
 * Android app consumes. The app renders everything itself (fonts,
 * spacing, thumbnails, share/bookmark, ads) — this only ever sends data.
 */
declare(strict_types=1);

final class ArticleTransformer
{
    public static function toArray(array $article): array
    {
        $images = array_map(
            static fn (array $image): string => self::absoluteUrl($image['file_path']),
            ArticleRepository::imagesFor((int) $article['id'])
        );

        return [
            'id'             => (int) $article['id'],
            'title'          => $article['title'],
            'content'        => $article['content'],
            'district'       => 'Bargarh',
            'block'          => $article['block_name'],
            'block_id'       => (int) $article['block_id'],
            'images'         => $images,
            'thumbnail'      => $images[0] ?? null,
            'author'         => $article['author'],
            'breaking_news'  => (bool) $article['breaking_news'],
            'published_date' => $article['published_at'],
        ];
    }

    private static function absoluteUrl(string $path): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        return $base !== '' ? $base . $path : $path;
    }
}
