=== DigiMarket ===
Contributors: digimarket
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete multi-vendor digital products marketplace theme with automatic Razorpay Route commission splits. No plugins required.

== Description ==

DigiMarket turns WordPress into a Gumroad + Etsy style marketplace:

* Sellers: 4-step onboarding (account → shop → payout KYC → launch), live shop-URL availability check, branded shop page (/store/your-shop), product CRUD (title, slug, short/full description, category, tags, price, discount price, thumbnail, 5 gallery images, file / license keys / external link delivery, download limits, time-limited access, SEO fields), duplicate, soft delete, bulk actions, orders with filters + CSV export, refunds, payouts, reviews (reply / flag), support tickets and shop settings.
* Buyers: one account for every shop, multi-seller cart, coupons, Razorpay Checkout (UPI, cards, netbanking, wallets), instant success page with download buttons, library of purchases with fresh expiring download links, order history with printable PDF invoices, wishlist, follow shops, notifications, reviews, support tickets, profile, data export and account deletion.
* Admin (wp-admin → Marketplace): overview with revenue / commission / growth charts and leaderboards, seller approval / rejection / suspension, per-seller commission override and Razorpay linked-account management, buyers, product moderation (reports, force-unpublish lock), full transaction ledger with search + CSV export + refunds, payout oversight (retry failed transfers), review moderation, coupons, support tickets, audit & download logs and settings.
* Commission engine: global %, per-category override, per-seller override, launch promotion (0% until a start date) — rate is snapshotted per order line so changes never affect past orders.
* Payments: Razorpay Orders + Checkout, signature verification, automatic Route transfers (one per seller per order), idempotent processing, webhooks (payment.captured, payment.failed, transfer.processed, transfer.failed, refund.processed, disputes) with signature verification and de-duplication. Refunds reverse the seller transfer and refund the buyer (both legs of the split) and revoke access.
* Security: server-side role checks on every action, nonces everywhere, login rate limiting / lockout, optional admin email OTP (2FA), admin idle auto-logout, encrypted PAN and bank account at rest, private file storage with HMAC-signed expiring links, download logging, audit log.
* Design: clean SaaS look, mobile-first, dark mode (auto / manual), skeleton-free fast server rendering, accessible markup, customizer options (brand colour, hero text & image, section toggles).

== Installation ==

1. Appearance → Themes → Add New → Upload Theme → choose digimarket.zip → Install → Activate.
2. On activation the theme creates its database tables, product categories, legal pages and sets pretty permalinks. If links 404, visit Settings → Permalinks and click Save once.
3. Go to Marketplace → Settings:
   * Set the global commission and (optionally) the "Commission starts on" date for a 0% launch promo.
   * Payments start in DEMO mode so you can test the full flow without money. For real payments select "Razorpay", enter your Key ID / Key Secret (test keys first), and in the Razorpay dashboard add a webhook to the URL shown in settings with a secret, then paste the same secret here.
   * Razorpay Route must be enabled on your Razorpay account (marketplace registration) before live split payments work.
4. Make sure your site can send email (use an SMTP plugin on most hosts) for verification, receipts and password resets.
5. Optional: Appearance → Customize → Marketplace Appearance for colours and homepage text; Appearance → Menus for extra menu links.

== Server notes ==

* Purchased files are stored in wp-content/uploads/dm-private-<random>/ with an .htaccess deny rule. On Nginx the folder name is random and unguessable; for extra safety add: location ~ /dm-private- { deny all; }
* Upload size is limited by PHP (upload_max_filesize / post_max_size) as well as the theme setting.

== Pages / URLs ==

/products, /shops, /store/{shop}, /cart, /checkout, /account, /dashboard, /sell, /login, /register, /forgot, /admin/login (→ wp-login with 2FA)

== Changelog ==

= 1.0.0 =
* Initial release.
