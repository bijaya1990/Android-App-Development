<?php
/**
 * Validates and stores article image uploads. Files are renamed to a
 * random name so the original filename never reaches the filesystem
 * or a URL.
 */
declare(strict_types=1);

final class ImageUploader
{
    private const ALLOWED_MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{path: string, error: null}|array{path: null, error: string}
     */
    public static function store(array $file, int $articleId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Upload failed.'];
        }

        if (($file['size'] ?? 0) > ARTICLE_MAX_IMAGE_BYTES) {
            return ['path' => null, 'error' => 'Image exceeds the 5MB limit.'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['path' => null, 'error' => 'Invalid upload.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME_TO_EXT[$mime])) {
            return ['path' => null, 'error' => 'Only JPEG, PNG, or WebP images are allowed.'];
        }

        $dir = article_upload_dir() . '/' . $articleId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => 'Could not create upload directory.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME_TO_EXT[$mime];
        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => 'Could not save the uploaded file.'];
        }

        return ['path' => ARTICLE_UPLOAD_URL_PREFIX . '/' . $articleId . '/' . $filename, 'error' => null];
    }
}
