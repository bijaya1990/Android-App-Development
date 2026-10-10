# Pikacart — Expert in ID Card Industry

Pikacart is a subscription website (pikacart.in) where schools, colleges, companies and other organisations design, manage and print ID cards.

It comes as **two ZIP files** you upload to WordPress:

| File | What it is |
|---|---|
| `dist/pikacart-core.zip` | **Plugin**: accounts, free trial, Razorpay payments, the organisation dashboard at `/app/`, and the Super Admin control room. Install this **first**. |
| `dist/pikacart-theme.zip` | **Theme**: the public website (homepage with the sliding design showcase, header, footer, pages). |

Your data lives in the plugin, so changing the theme later never loses anything.

---

## Install (about 5 minutes, no code)

1. Log in to WordPress (`yoursite/wp-admin`).
2. **Plugins → Add New → Upload Plugin** → choose `pikacart-core.zip` → **Install Now** → **Activate**.
3. **Appearance → Themes → Add New → Upload Theme** → choose `pikacart-theme.zip` → **Install Now** → **Activate**.
4. **Settings → Permalinks** → choose **Post name** → **Save**. (Needed for addresses like `/app/` and `/login/`.)
5. Open **Pikacart → Settings** in the left menu and fill in:
   - **General**: business name, support phone, address, GSTIN (optional), logo and brand colour.
   - **Razorpay**: follow the 4 steps shown on that page (keys, plan, webhook).
   - **Maintenance and data**: copy the cron command into cPanel (gives on-time reminder emails).

To update later, upload the new ZIP the same way. WordPress asks "Replace current with uploaded?" Click **Replace**. Your data is kept, and the database upgrades itself.

## Recommended on MilesWeb

- **Email delivery**: install the free **WP Mail SMTP** plugin and connect your `@pikacart.in` email, so emails don't land in spam.
- **Backups**: take a cPanel/JetBackup backup regularly and before every update.
- **SSL**: keep HTTPS on (free in cPanel). Razorpay needs it.

---

## New in version 2.2

- **Valid upto for all cards**: in a project's **Details** step, set one "Valid upto" (and optional "Valid from") date. Every card without its own date uses it, and changing it updates those cards. A person can still have their own date (Add person or the Excel).
- **Continue where you left off**: the dashboard shows your latest project and recent projects; each opens on the step where you stopped. The design gallery remembers the card type, orientation, style and colour you were looking at.
- **Fixes**: changes (font, size, colours) are always saved before the next step opens, so the new style shows everywhere; a deleted project disappears at once; Pikacart pages and data are never stored by LiteSpeed Cache or other cache plugins.

## New in version 2.1

- **Change design any time**: every project has a **Change design** button (in the project header and on each project in My Projects). People, details, size and colours are kept.
- **Blank Excel for each design**: **Blank Excel** downloads a ready file with exactly the columns that design needs (required ones marked *), a "How to fill" sheet, and a **Photo** column. Organisation details (name, logo, address, signature) come from the profile, so they are not asked again.
- **Upload it and the cards are ready**: the columns are matched automatically and photos are picked up from the Excel. Paste a photo into the Photo cell (Excel 365 "Place in Cell" or a picture placed on the cell), or type the photo file name and choose the photos or a ZIP in the same step, or name photos by the ID number. Then **Download the cards**.

## What's inside (version 2.0, all 5 phases)

**For your customers (the organisation dashboard at `/app/`)**
- Free forever with a "Made with www.pikacart.in" watermark (or a timed trial, your choice). ₹59/month removes the watermark.
- 1,500+ ready designs: 10 categories × 3 card types × 50 styles, simple to premium, portrait and landscape, 11 card sizes, colour palettes.
- Canva-style editor: move, resize, rotate, text, photos, logo, signature, seal, QR, barcode, undo/redo.
- People: add one by one, import Excel/CSV (with error report by row), bulk photos matched by ID number, self-fill link with approval, recycle bin, new session with class promotion.
- Downloads at 300 DPI: PDF, JPG, PNG (ZIP for many cards), print one per page, **print sheet maker** on A4, A3, A5, A6, 12×18, 13×19 inch or a custom paper, with cut marks.
- Real QR verification page `/verify/...` showing Valid, Expired or Cancelled, and a "Report this card" button.
- **My Designs**: upload your own front/back artwork and place the fields yourself, or send it to **Design on Demand** (live within 6 hours).
- Razorpay autopay or pay once (with **coupon codes**), GST invoices as PDF.
- Live support chat, notification bell, notices from you. Works on phone, tablet and computer.

**For you (WordPress → Pikacart, the "control room")**
- Dashboard with money, accounts, conversions and charts.
- Accounts: suspend/reactivate, extend trial, free days, plan end date, reset link, **View as this customer** (read only), delete.
- Payments, **Finance** (monthly statement with PDF and Excel, expenses, coupons).
- **Templates**: thumbnails, filters, visual builder, duplicate, import/export JSON, publish/unpublish, feature.
- **Design requests** queue with countdown, builder on the customer's artwork, publish to the customer (and optionally to everyone), reject with reason.
- **Categories & sizes**: add/edit/hide/reorder categories and card types (new types get 50 designs automatically), default fields, SEO; card sizes; colour palettes.
- Support inbox, **Notices** (everyone or one account), **Reports** queue (cancel card or suspend account), **Activity log** with export.
- Settings: General, Free plan and Plans, Razorpay, Emails, Homepage (showcase, featured categories, FAQ, testimonials), SEO, Legal pages, Limits, Maintenance.

**Public website**
- Homepage with a hero of real designs and a **design showcase that slides category by category** (premium designs highlighted), category cards, features, pricing, FAQ, testimonials.
- Gallery pages for every category and card type, e.g. `/id-card/school/student/`: filters (orientation, style, colour), live colour preview, "Use this design" (goes to registration and straight into the chosen design).
- SEO built in: titles, meta descriptions, Open Graph, schema (Organization, SoftwareApplication with price, FAQ, Breadcrumbs), XML sitemap (`/wp-sitemap.xml`), robots rules, and an SEO box on every page. If Yoast or Rank Math is active, Pikacart leaves the meta tags to them.

## After installing: 5 things to set

1. **Pikacart → Settings → General**: business name, phone, address, GSTIN, logo.
2. **Razorpay**: keys in Test mode first, then the webhook (secret and events are listed on that page). Try one payment, then switch to Live.
3. **Homepage**: hero text, which categories to show, your FAQ, and real customer testimonials (the section stays hidden until you add some).
4. **SEO**: homepage title and description, share image, Search Console code, Analytics ID.
5. **Limits**: upload size, and the Design on Demand promise (6 hours) and the email that receives new requests.

## Full test checklist (click by click)

1. Open the homepage: designs slide in the showcase and switch category by themselves; click a category chip.
2. Click **Designs** in the menu → a category → change Portrait/Landscape, Premium, and a colour → open a design → **Use this design**.
3. Register a new organisation → finish the quick setup → the chosen design opens. Change size and colour.
4. **People** → add one person with a photo → **Blank Excel** → fill 50 rows (paste photos in the Photo column) → **Import Excel / CSV** → everyone appears with their photo. Then try **Change design**: the people stay.
5. **Download & print** → PDF, then JPG, then PNG: free cards carry the watermark.
6. **Open print sheet maker** → A4 → download the PDF and mark the cards as Printed.
7. Scan a card's QR with your phone → the green "Valid card" page opens.
8. **Subscription** → pay ₹59 in Razorpay **test mode** (or "Pay once" with a coupon) → download again: no watermark. Download the invoice.
9. **My Designs** → **Send my design** (Design on Demand). In WordPress → **Design requests** → **Open in builder** → **Publish to this organisation**. The customer sees it live and gets an email.
10. **Accounts** → open the account → **Suspend** (the verification page now shows Cancelled) → **Reactivate**.
11. **Finance** → this month's statement shows the payment → download PDF and Excel.

All of these steps were run automatically in a test browser before delivery and passed.

## Good to know

- Everything (cards, PDFs, ZIPs) is made in the customer's browser, so shared hosting stays fast. Very large batches are split into parts of 100 cards.
- Razorpay is the only outside service: its Checkout script loads from `checkout.razorpay.com` on the Subscription screen (Razorpay requires this). Google Analytics loads only if you add an ID.
- Pikacart must never be used for government IDs (Aadhaar, PAN, voter ID, licences, police/army cards). Reject such Design on Demand requests and use **Reports** to cancel abusive cards.

---

## For developers

- `pikacart-core/` plugin source, `pikacart/` theme source. `build.sh` creates the two ZIPs in `dist/`.
- REST namespace `pkc/v1`; tables prefixed `wp_pkc_`; schema version in option `pkc_db_version` (now 5), upgrades run automatically.
- Card engine: ES modules in `pikacart-core/assets/js/card/` (no build step). Built-in designs are recipes expanded in the browser; custom designs store full layouts.
- Libraries bundled locally: Chart.js 4.4.4, jsPDF 2.5.2, qrcode.js 1.4.4, JsBarcode 3.11.6, JSZip 3.10.1, SheetJS 0.18.5 (mini), Cropper.js 1.6.2, and OFL fonts.
- Theme override: copy `pikacart-core/templates/id-card.php` to the theme as `pikacart-id-card.php`.
