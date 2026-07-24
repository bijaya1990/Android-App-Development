# Talpadar TV News — REST API (Phase 5)

Read-only, unauthenticated, JSON-only. Only `published` articles are ever
returned — draft, pending_review, and returned articles are excluded at the
query level (`ArticleRepository::publishedPaginated` / `publishedFind`), not
just filtered in the view layer.

Base URL: `https://<your-domain>/api`

## Endpoints

| Method | Path                                  | Description                                  |
|--------|---------------------------------------|-----------------------------------------------|
| GET    | `/v1/news`                            | All published news. `?block_id=` or `?block=<name>`, `?breaking=1`, `?page=`, `?per_page=` (max 50) |
| GET    | `/v1/news/{id}`                       | A single published article                    |
| GET    | `/v1/blocks`                          | The 12 Bargarh blocks                          |
| GET    | `/v1/blocks/{idOrName}/news`          | Published news for one block — pass the block's id, or its exact name (case-insensitive, same string as its button label) |
| GET    | `/v1/districts`                       | V1 scope: Bargarh only, with its blocks         |
| GET    | `/v1/districts/bargarh/news`          | Same as `/v1/news` — explicit district scope    |

### Block buttons -> filtered news

Each of the 12 block buttons the app shows (Bargarh, Attabira, Bhatli, Sohela,
Ambabhona, Bheden, Barpali, Bijepur, Gaisilet, Padampur, Paikmal, Jharbandh)
maps directly to one of these, no extra lookup needed:

```
GET /v1/blocks/Attabira/news
GET /v1/blocks/Sohela/news?page=2
```

An unrecognized block name returns `{"error": "Block not found.", ...}` with
a 404, so a typo in a button label fails loudly instead of silently
returning all-district news.

## Article JSON shape

```json
{
  "id": 12,
  "title": "News title",
  "content": "<p>Sanitized rich text HTML</p>",
  "district": "Bargarh",
  "block": "Attabira",
  "block_id": 3,
  "images": ["https://your-domain/uploads/articles/12/abc123.jpg"],
  "thumbnail": "https://your-domain/uploads/articles/12/abc123.jpg",
  "author": "Writer Full Name",
  "breaking_news": false,
  "published_date": "2026-07-20 10:15:00"
}
```

List endpoints wrap articles as `{"data": [...], "meta": {"page", "per_page", "total", "total_pages"}}`.
Single-article endpoints return `{"data": {...}}`. Errors return `{"error": "message"}`
with a matching HTTP status code.

The Android app renders everything from this data — fonts, spacing,
thumbnails, share/bookmark, related news, ads. The backend never sends HTML
or CSS through this API.
