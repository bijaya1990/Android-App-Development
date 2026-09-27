=== PikaCart (DigiMarket) ===
Contributors: digimarket
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Flipkart-style storefront for digital products, WhatsApp-quoted website services and hosting partner offers — with built-in SEO, banners, coupons and articles. Also runs as a full multi-vendor marketplace with Razorpay Route splits. No plugins required.

== Description ==

DigiMarket turns WordPress into a Gumroad + Etsy style marketplace:

* Sellers: 4-step onboarding (account → shop → payout KYC → launch), live shop-URL availability check, branded shop page (/store/your-shop), product CRUD (title, slug, short/full description, category, tags, price, discount price, thumbnail, 5 gallery images, file / license keys / external link delivery, download limits, time-limited access, SEO fields), duplicate, soft delete, bulk actions, orders with filters + CSV export, refunds, payouts, reviews (reply / flag), support tickets and shop settings.
* Buyers: one account for every shop, multi-seller cart, coupons, Razorpay Checkout (UPI, cards, netbanking, wallets), instant success page with download buttons, library of purchases with fresh expiring download links, order history with printable PDF invoices, wishlist, follow shops, notifications, reviews, support tickets, profile, data export and account deletion.
* Admin (wp-admin → Marketplace): overview with revenue / commission / growth charts and leaderboards, seller approval / rejection / suspension, per-seller commission override and Razorpay linked-account management, buyers, product moderation (reports, force-unpublish lock), full transaction ledger with search + CSV export + refunds, payout oversight (retry failed transfers), review moderation, coupons, support tickets, audit & download logs and settings.
* Commission engine: global %, per-category override, per-seller override, launch promotion (0% until a start date) — rate is snapshotted per order line so changes never affect past orders.
* Payments: Razorpay Orders + Checkout, signature verification, automatic Route transfers (one per seller per order), idempotent processing, webhooks (payment.captured, payment.failed, transfer.processed, transfer.failed, refund.processed, disputes) with signature verification and de-duplication. Refunds reverse the seller transfer and refund the buyer (both legs of the split) and revoke access.
* Security: server-side role checks on every action, nonces everywhere, login rate limiting / lockout, optional admin email OTP (2FA), admin idle auto-logout, encrypted PAN and bank account at rest, private file storage with HMAC-signed expiring links, download logging, audit log.
* Design: clean SaaS look, mobile-first, dark mode (auto / manual), skeleton-free fast server rendering, accessible markup, customizer options (brand colour, hero text & image, section toggles).

== PikaCart storefront (2.0) — quick start ==

1. Marketplace → Settings: turn on Single seller mode, set your default WhatsApp number (with country code, e.g. 919XXXXXXXXX).
2. Marketplace → Banners → "Import demo banners" (optional) to fill the homepage instantly. Replace them with your own later. Sizes: hero card 1000×500, offer tile 600×600, campaign 1460×325 (+1080×540 mobile), cart strip 1200×150. Use Start/End times to schedule festival banners.
3. Marketplace → Categories: give each category an icon (emoji or image), colour, intro text, FAQ and SEO title/description.
4. Marketplace → All products → Add product: choose Digital product / Service / Partner offer.
   * Digital: price, optional discount + sale start/end, file, "What you get" list, age band (Kids).
   * Service: starting price, turnaround, "What's included", up to 3 packages, FAQ, WhatsApp message.
   * Partner offer: affiliate link (kept private), short link /go/your-slug/, pros/cons.
   * Fill the SEO box (focus keyword, title 50–60 chars, description 120–160). Left blank, it is generated automatically.
5. Marketplace → Portfolio and Testimonials: add real projects and client quotes (shown on service pages and the homepage).
6. Marketplace → Storefront & SEO: homepage block order, announcement bar with countdown, trust numbers, process steps, default FAQ, owner name/photo/bio (author box), homepage SEO title/description, Google Search Console code, social profiles.
7. Posts → Add New writes an Article. Articles live at /articles/ (menu only — never on the homepage).
8. After delivering a website: Marketplace → Request review creates a one-time link the client uses to leave a "Verified client" review.
9. Marketplace → Coupons: category/product scope, first order only, per-customer limit, max discount, "show as best offer". Share links look like /?coupon=CODE.
10. Reports: Leads (enquiry form), Clicks report (WhatsApp + partner clicks), SEO health (listing checks, redirects, 404 log).

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

/products, /category-products/{slug}, /product/{slug}, /sale, /articles, /articles/{slug}, /portfolio, /go/{partner}, /review/{token}, /cart, /checkout, /account, /dashboard, /login, /register, /forgot, /admin/login (→ wp-login with 2FA). Marketplace mode also: /shops, /store/{shop}, /sell.

== Changelog ==

= 2.1.0 =
* Homepage: two big showcase cards below the banner carousel — Website Services ("See details & price") and WordPress Themes ("Buy now") — with images that change by themselves.
* New landing page /website-services/: every website type with rotating photos, what's included, price and packages, coupon code (added to the WhatsApp message), reviews, portfolio, FAQ, contact details and an "Order now" WhatsApp button.
* New landing page /wordpress-themes/: every theme with rotating screenshots, description, tags (filter chips), price and discount, best coupon, "Buy now" straight to Razorpay checkout with instant download, and "Live preview".
* Header, mobile menu and footer link to both pages. New "Live demo URL" field on products.
* Settings in Marketplace → Storefront & SEO → Landing pages. Both pages have their own SEO title/description, schema and sitemap entry.
* Palette PNG uploads are no longer converted to WebP (avoids a GD warning).

= 2.0.0 =
* New storefront design: Poppins typography, Flipkart-style header with live search suggestions, category icon row, card-style hero banner carousel, offer tiles, gradient category boxes, deals row with countdowns, services band, portfolio, testimonials, trust strip, dark footer, floating WhatsApp button, mobile bottom navigation and drawer menu, sticky mobile buy bar.
* New product/service/partner pages: gallery with zoom, % off and "You save", best-coupon offer box, "What you get" / "What's included" checklists, service packages (Basic/Standard/Premium) with per-package WhatsApp buttons, "How it works" steps, FAQ accordion, enquiry form, trust bar, rating breakdown with verified buyer/client badges.
* Banner manager: placements (hero, tile, campaign, category, articles, cart), mobile image, link, alt text, start/end scheduling, countdown, audience, views/clicks/CTR, one-click demo import.
* Listing types: Digital product, Service (WhatsApp) and Partner offer (affiliate) with private links, /go/ short links, click tracking, rel="sponsored", disclosure and "Recommended hosting" on services.
* Services toolkit: Portfolio and Testimonials, enquiry form → Leads (status, notes, CSV export, email alert, WhatsApp continue), one-time "Request review" links for verified client reviews.
* Built-in SEO: auto titles/descriptions, canonical tags, noindex for private/filtered pages, Open Graph (WhatsApp previews), JSON-LD (Organization, WebSite, Product, Service, FAQPage, BreadcrumbList, CollectionPage, BlogPosting), robots.txt rules, cleaner sitemaps, SEO panel with Google preview, keyword checks and duplicate detection, SEO health report, 301 redirects manager, 404 log, deleted products redirect to their category, IndexNow pings, WebP image sizes, deferred JS, self-hosted fonts, split/minified CSS.
* Articles under /articles/ (menu only): list view with thumbnails + View button, table of contents, reading time, author box, share buttons, related articles, "Products mentioned", [pikacart_product] and [pikacart_cta] shortcodes.
* Coupons: category/product scope, first order only, per-customer limit, max discount cap, shareable ?coupon= links, "best offer" hint, per-coupon report.
* Scheduled sale prices (start/end) and a /sale/ page; announcement bar with countdown; homepage block ordering.
* New pages created on update: About Us, Contact Us, Delivery & Service Policy, Affiliate Disclosure.
* Fixes: default categories are no longer re-created after you delete them; URL checks no longer need DNS (works on hosts with DNS timeouts); single-seller owners can use the front-end product editor.

= 1.1.0 =
* New: Single Seller Mode setting (Marketplace → Settings) for running as your own solo shop — hides seller signup, shop directory and per-seller Razorpay linked accounts; your own sales settle without needing Route.
* New: "Service" product type — a WhatsApp-enquiry button instead of Buy Now/cart, for custom work (website builds, etc.) priced after discussion. Set a default WhatsApp number in Settings, or per product.

= 1.0.1 =
* Fix: pages like /cart/ and /sell/ returning 404 on Apache/LiteSpeed hosts (.htaccess is now written on activation, with self-repair and an admin warning).

= 1.0.0 =
* Initial release.
