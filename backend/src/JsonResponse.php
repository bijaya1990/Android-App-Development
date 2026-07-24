<?php
/**
 * The Android app only ever receives JSON from this backend — never
 * HTML or CSS. This is the single place that writes a response.
 */
declare(strict_types=1);

final class JsonResponse
{
    public static function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400): never
    {
        self::send(['error' => $message], $status);
    }
}
