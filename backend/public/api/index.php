<?php
/**
 * Public REST API front controller. Read-only, no authentication —
 * it must only ever expose 'published' articles (enforced in
 * ArticleRepository::publishedPaginated() / publishedFind()).
 *
 * Routes:
 *   GET /api/v1/news                       list published news (?block_id= or ?block=<name>, ?breaking=1, ?page=, ?per_page=)
 *   GET /api/v1/news/{id}                  single published article
 *   GET /api/v1/blocks                     the 12 Bargarh blocks
 *   GET /api/v1/blocks/{idOrName}/news     published news for one block — one button per block, filtered by that block's id or exact name
 *   GET /api/v1/districts                  V1 supports Bargarh only
 *   GET /api/v1/districts/bargarh/news     alias of /api/v1/news (explicit district scope)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed.', 405);
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($s) => $s !== ''));

if (($segments[0] ?? '') !== 'v1') {
    JsonResponse::error('Unknown API version.', 404);
}

function paginationParams(): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 20);
    $perPage = max(1, min(50, $perPage ?: 20));

    return [$page, $perPage];
}

/** Accepts either a numeric block id or an exact (case-insensitive) block name — e.g. what a block button's label already is. */
function resolveBlockToken(string $token): ?array
{
    return ctype_digit($token) ? ArticleRepository::blockById((int) $token) : ArticleRepository::blockByName($token);
}

/** Reads the block filter from the query string, accepting ?block_id= or ?block=<name>. */
function blockIdFromQuery(): ?int
{
    if (isset($_GET['block_id'])) {
        return (int) $_GET['block_id'];
    }
    if (isset($_GET['block']) && $_GET['block'] !== '') {
        $block = ArticleRepository::blockByName((string) $_GET['block']);
        return $block ? (int) $block['id'] : -1; // -1 matches nothing, so an unknown block name returns an empty list rather than all news
    }

    return null;
}

function respondNewsList(?int $blockId, ?bool $breakingOnly): never
{
    [$page, $perPage] = paginationParams();
    $result = ArticleRepository::publishedPaginated($blockId, $breakingOnly, $page, $perPage);

    JsonResponse::send([
        'data' => array_map([ArticleTransformer::class, 'toArray'], $result['items']),
        'meta' => [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $result['total'],
            'total_pages' => (int) ceil($result['total'] / $perPage),
        ],
    ]);
}

$resource = $segments[1] ?? null;

switch ($resource) {
    case 'news':
        if (count($segments) === 2) {
            $breaking = isset($_GET['breaking']) ? $_GET['breaking'] === '1' : null;
            respondNewsList(blockIdFromQuery(), $breaking);
        }

        if (count($segments) === 3 && ctype_digit($segments[2])) {
            $article = ArticleRepository::publishedFind((int) $segments[2]);
            if (!$article) {
                JsonResponse::error('Article not found.', 404);
            }
            JsonResponse::send(['data' => ArticleTransformer::toArray($article)]);
        }

        JsonResponse::error('Not found.', 404);
        // no break — JsonResponse::send/error never return

    case 'blocks':
        if (count($segments) === 2) {
            JsonResponse::send(['data' => ArticleRepository::blocks()]);
        }

        if (count($segments) === 4 && $segments[3] === 'news') {
            $block = resolveBlockToken($segments[2]);
            if (!$block) {
                JsonResponse::error('Block not found.', 404);
            }
            respondNewsList((int) $block['id'], null);
        }

        JsonResponse::error('Not found.', 404);

    case 'districts':
        if (count($segments) === 2) {
            JsonResponse::send(['data' => [['name' => 'Bargarh', 'blocks' => ArticleRepository::blocks()]]]);
        }

        if (count($segments) === 4 && $segments[2] === 'bargarh' && $segments[3] === 'news') {
            $breaking = isset($_GET['breaking']) ? $_GET['breaking'] === '1' : null;
            respondNewsList(blockIdFromQuery(), $breaking);
        }

        JsonResponse::error('Not found.', 404);

    default:
        JsonResponse::error('Not found.', 404);
}
