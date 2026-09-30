=== PikaCart (DigiMarket) ===
Contributors: digimarket
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.11.0
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

= 2.11.0 =
* New marketplace homepage design (orange), now the default. You can switch back in Storefront & SEO → Homepage design.
* Header: navy top bar (3 editable messages plus a support number), orange logo, wide search with an orange search button, Account / Wishlist / Cart, "All Categories" menu, category links and a red "Offers" pill.
* Homepage sections:
  * Full-width slider using "Main slider" banners, or automatic slides built from your products, website services and themes, with a condensed headline and an "Up to X% Off" badge.
  * Round category icons with "More Categories".
  * Flash Sale band with an hours/minutes/seconds countdown and product cards that show % OFF, price, stars and Add to Cart.
  * Best Sellers grid with green Bestseller badges.
  * Three promo cards (from "Promo card" banners, or automatic: Website Services, WordPress Themes, top category).
  * Trust row and customer reviews.
* Newsletter band ("Get Exclusive Offers & Updates") saves subscribers to Marketplace → Leads, with coloured social icons.
* New footer with Shop By Category, Customer Service, My Account and Contact Us columns. The "Download Our App" column with store badges appears only when an app link is set.
* The Etsy-style fonts and layer were removed. Poppins is back, and Barlow Condensed is used for slider headlines.

= 2.10.1 =
* WP Admin product editor: new "Product photos (4 needed)" box under the Featured image. The Featured image is photo 1. "Add photos" opens the media library with multi-select, each photo can be removed with ×, and a live "x of 4 photos added" counter shows progress. Saving a published product with fewer than 4 photos shows a reminder.
* Product tags are hidden from visitors by default: no #tag chips on product pages and no Tags filter in the shop. You can turn them back on in Storefront & SEO → "Show product tags".
* Hidden tags still work behind the scenes. Store search matches tags, "You may also like" shows products that share a tag first, and product schema lists the tags as keywords.

= 2.10.0 =
* Etsy-style look. New self-hosted fonts: Figtree for text and Source Serif for big headings (OFL licensed, preloaded, no Google requests).
* Black pill buttons: "Add to cart" is black and "Buy now" is outlined, no more capital letters. Pill search bar with a round search button. The header nav is plain text links with the trust icons removed.
* Homepage: a soft light hero with a dark serif headline, open sections without boxes, serif section titles, larger round category icons, plain black star ratings and softer badges.
* Dark mode keeps the same style with white buttons.

= 2.9.0 =
* Admin can see every seller's wallet. Marketplace → Overview shows "Seller wallets" with the total owed to sellers and each seller's balance, sales, commission + GST, refunds, earnings, amount paid and last payment (UTR). Payouts has a new "Seller wallets" tab, and "Pay now" jumps to that seller in Weekly settlement.
* Cleaner, Etsy-style storefront: white page, product cards without boxes, rounded photos with a soft hover shadow, lighter titles, and white cart / WhatsApp buttons.
* Products need 4 photos (a cover plus 3 more) before they can be published from the seller dashboard. The editor shows a live "x of 4 added" counter. Products already live get a reminder instead of being unpublished.
* Product cards turn through the 4 photos automatically like book pages, with page dots. Extra photos load only when the card is on screen, and a gentle fade replaces the page turn for visitors who prefer reduced motion.

= 2.8.0 =
* Seller applications: a professional "Apply now for a seller account" button, switched ON/OFF from Marketplace → Seller applications. While it is on, it appears in the top bar (with batch and closing date), the header ("Sell on PikaCart"), the mobile menu, the footer and anywhere you use [pikacart_apply_button].
* Large pop-up application form in 6 sections: personal details and address; shop and products (with sample links and course hosting); PAN and tax (GSTIN optional, not compulsory below ₹20 lakh yearly sales); bank details; PAN card and bank proof uploads (JPG/PNG/PDF, max 5 MB); declarations. No age is asked. Submits without reloading the page, with field-by-field errors.
* Security: PAN and account numbers are encrypted, documents are kept in the private folder (admin-only view), the form has a honeypot and a rate limit, and each email can apply only once per batch.
* Batches: Batch 1, Batch 2 and so on. "Start Batch N" opens a new batch. There is an optional closing date that hides the button automatically.
* Admin review screen: stats by status and category, filters (batch, status, category, date range, search), newest/oldest ("first come") sorting, CSV export, full detail with documents, a 10-point quality-check score, decisions (needs changes / waiting list / rejected) emailed to the applicant, private notes, and delete.
* "Approve & create seller" creates the account with the PAN, bank and address already saved as verified payout details. The seller only accepts the Seller Agreement and starts listing.
* Applicants get a confirmation email with their reference (e.g. PK-B1-0001), and the admin gets an alert.

= 2.7.0 =
* Add sellers manually (Marketplace → Sellers → "Add a seller manually"): creates the account, shop and optional commission, and emails a set-password link. Works in single-seller mode, where public seller signup stays closed.
* Invited sellers add payout details and accept the Seller Agreement, then go live. Their products show "Sold by <shop>".
* Manual payouts: new setting "Seller payouts" (Automatic Route split / Manual). Single-seller mode and Cashfree always use manual payouts.
* New Payouts → Weekly settlement dashboard: each seller's sales per week (Mon–Sun), discount, refunds, commission, GST on commission, seller net, already paid and due now. It also shows full bank/UPI details, a UPI app link, CSV export, and "Mark paid" with the UTR, which emails the seller a payout statement.
* Seller wallet on the seller dashboard (Overview and Payouts): a large balance card showing what the store still owes. It drops to ₹0 after you mark a payment as paid. It also shows total sales, platform commission, GST on commission, refunds, TDS, earnings, amount paid and the last payment with its UTR. Earnings history can be filtered week-wise, month-wise or year-wise, followed by a list of payments received.
* Optional TDS (Sec. 194-O) deduction on payouts; a refund after a manual payout is recovered from the seller's next payout.
* Professional receipt email: PikaCart logo, receipt number, date, payment method and transaction ID, billed-to details, itemised table (price, discount, amount, sold by, GST), subtotal, discount, total paid and invoice link. All emails now use the logo header and a footer with the business address and policy links.

= 2.6.0 =
* New payment gateway: Cashfree Payments. Choose Demo / Razorpay / Cashfree in Marketplace → Settings (one active at a time). Sandbox and Production environments.
* Cashfree flow: order + payment session, Cashfree checkout, verification with Cashfree's API when the buyer returns, signed webhook (HMAC-SHA256) as a safety net, retry, and refunds. Amount mismatches are never fulfilled.
* Refunds always go through the gateway that took the payment, even after switching gateways.
* Checkout asks for a 10-digit mobile number when Cashfree is active and none is saved.
* Paid products are removed from the buyer's saved cart even when a webhook confirms the payment.
* Settings no longer print saved secret keys into the page (leave blank to keep).
* With Cashfree, other sellers' shares stay pending for manual payout (automatic split needs Cashfree Easy Split approval).

= 2.5.0 =
* Marketplace payouts: while the GST switch is ON, GST (default 18%, editable on the GST page) is charged on the platform commission and deducted from the seller's share; while OFF, only the commission is deducted. Shown in seller orders, admin transactions and CSV exports.
* Seller payout details: bank account + IFSC and full address (street, city, state from a list, 6-digit PIN) are now required so the Razorpay Route linked account can be created and every sale is paid out automatically. UPI is optional.
* Seller Agreement, welcome email and Start selling page mention GST on commission.

= 2.4.0 =
* New homepage layout like a professional store: main nav row (Home, Shop, Categories menu, Offers, Website Services, WordPress Themes, Articles, Contact) with trust points; Bestsellers panel + Shop by category + Limited time offer with Days/Hours/Mins/Secs countdown; uniform white panels; collections in equal boxes; "What our customers say" slider with real reviews and testimonials.
* New arrivals show digital products only. Existing sites get the new block order once (your on/off choices are kept).
* Portfolio is now labelled honestly: "Sample websites you can get", "Sample" badges and "View sample"; "Real client project" only when chosen. Existing items are marked as samples on upgrade.

= 2.3.0 =
* GST & tax: big GST ON/OFF switch (GST page, admin Overview, owner dashboard), store GSTIN/rate/state, financial-year turnover meter against the Rs 20 lakh limit, alerts by notification and email.
* Per-seller "GST required" toggle: the seller gets an email and a dashboard pop-up, and purchases/publishing pause until a valid GSTIN is added. Optional seller GSTIN with "show on receipts".
* Receipts: "Receipt" (GST not applicable) or "Tax Invoice" with taxable value and CGST/SGST; sellers download their own receipts from the new Receipts tab.
* Seller welcome email after registration with the legal record (agreement acceptance, key terms, GST rule, grievance contact). Seller Agreement and Terms include the GST clauses.

= 2.2.0 =
* Launch-ready legal pages (Terms, Privacy, Refund & Cancellation, Shipping & Delivery, Contact, About, Affiliate, Content & IP, Seller Agreement) with business email, address and Grievance Officer; seller agreement pop-up before shop launch; account menu styling restored.

= 2.1.3 =
* Fixed white text on white buttons: "Become a seller" in the footer, and white buttons/badges in dark mode.

= 2.1.2 =
* "Featured on homepage" now works: only featured services appear in the big Website services band (untick to remove it); featured products are pinned first in New arrivals.
* Saving storefront settings or a product purges the LiteSpeed page cache so the homepage updates immediately.

= 2.1.1 =
* The Website Services and WordPress Themes cards (and their menu links) now always show on the homepage, with a designed wallpaper until listings with images exist.

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
