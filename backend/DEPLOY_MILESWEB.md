# Deploying to MilesWeb (cPanel shared hosting)

This lays the app out so `config/` and `src/` sit **outside** `public_html`
(not web-accessible) while everything that must be public — login pages,
the writer/admin panels, the API, and uploaded images — lives directly
inside `public_html`. No document-root change is required, which matters
because most shared MilesWeb plans don't let you point the domain's
document root at an arbitrary subfolder without a support ticket.

If `public_html` currently has anything in it (e.g. an old WordPress
install), back it up and empty it first — this is not a WordPress project
and the two should not be mixed in the same folder.

## 1. Create the database (cPanel → MySQL Databases)

1. Create a database, e.g. `yourcpaneluser_talpadar`.
2. Create a database user with a strong password.
3. Add that user to the database with **All Privileges**.
4. Note the DB host (almost always `localhost` on MilesWeb), name, user, password.

## 2. Import the schema (cPanel → phpMyAdmin)

1. Select the database you just created.
2. **Import** tab → choose `database/schema.sql` → Go.
3. **Import** tab again → choose `database/seed_blocks.sql` → Go.

This creates `blocks`, `users`, `articles`, `article_images`, and seeds the
12 Bargarh blocks. No users exist yet — that's step 5.

## 3. Upload the files (cPanel → File Manager, or FTP)

From this repo, upload:

| Repo source                  | Destination on the server              |
|-------------------------------|-----------------------------------------|
| `backend/public/*` (contents) | `public_html/` (directly, not a subfolder) |
| `backend/config/`             | `~/config/` (your home directory, sibling of `public_html`) |
| `backend/src/`                | `~/src/`                                 |

So on the server, `~/public_html/login.php`'s `require '../config/config.php'`
correctly resolves to `~/config/config.php` — same relative layout as the
repo, just with `public_html` standing in for `backend/public`.

Do **not** upload `backend/config/`, `backend/src/`, or `backend/.env` into
`public_html` — they must stay outside the web root.

## 4. Configure environment

1. Copy `backend/.env.example` to `~/.env` (home directory, next to `config/`
   and `src/` — **not** inside `public_html`).
2. Fill in `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from step 1.
3. Set `APP_URL` to your live domain, e.g. `https://talpadarnews.com` — the
   API uses this to build absolute image URLs for the Android app.
4. Confirm `SESSION_COOKIE_SECURE=1` (your domain must be served over HTTPS).

## 5. Create the first Super Admin

This needs shell access. If MilesWeb gives you SSH or the cPanel Terminal,
run this from `~` (the same directory as `config/`):

```bash
php -r "
require 'config/config.php';
\$username = 'admin';
\$fullName = 'Super Admin';
\$password = bin2hex(random_bytes(6));
\$hash = password_hash(\$password, PASSWORD_DEFAULT);
\$stmt = get_db()->prepare('INSERT INTO users (username, password_hash, full_name, role, status) VALUES (:u, :h, :f, :r, :s)');
\$stmt->execute(['u' => \$username, 'h' => \$hash, 'f' => \$fullName, 'r' => ROLE_SUPER_ADMIN, 's' => 'active']);
echo \"Username: \$username\nPassword: \$password\n\";
"
```

Change `$username`/`$fullName` first. **Copy the printed password
immediately** — it is never stored anywhere and won't be shown again.

If MilesWeb gives you no shell access at all, ask their support to enable
SSH for your account, or temporarily and carefully run equivalent SQL by
hand in phpMyAdmin (you'd need to compute a bcrypt hash yourself first,
which is why the shell route above is strongly preferred).

## 6. Verify

- `https://your-domain/login.php` should show the login form.
- Log in as the Super Admin you just created → should land on
  `/admin/dashboard.php`.
- `https://your-domain/api/v1/blocks` should return the 12 blocks as JSON.
- `https://your-domain/api/v1/news` should return `{"data":[],"meta":{...}}`
  (empty until an article is published).

## 7. File permission notes

- `public_html/uploads/articles/` must be writable by PHP (usually `755` is
  enough on MilesWeb; only raise to `775`/`777` if uploads fail with a
  permissions error).
- Never make `~/.env` web-accessible — it isn't, as long as it stays outside
  `public_html` per step 4.
