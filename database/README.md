# Talpadar TV News — Database (Phase 1)

## Import order (MilesWeb / cPanel / phpMyAdmin)

1. Create a MySQL database and user in cPanel, then note the DB name,
   username, password, and host (usually `localhost` on MilesWeb).
2. Open phpMyAdmin, select the database, go to **Import**, and run:
   1. `schema.sql`
   2. `seed_blocks.sql`

## Tables

- **blocks** — the 12 fixed blocks of Bargarh district.
- **users** — Super Admin and Content Writer accounts.
- **articles** — news articles and their workflow status
  (`draft` → `pending_review` → `published`, or `returned` back to the writer).
- **article_images** — 2–4 images per article (enforced by the application, not the DB).

No Super Admin account is seeded yet — that lands in Phase 2 (Authentication),
where credentials are generated and password hashes created via PHP.
