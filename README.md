# Pikacart — Expert in ID Card Industry

Pikacart is a subscription website (pikacart.in) where schools, colleges, companies and other organisations design, manage and print ID cards.

It comes as **two ZIP files** you upload to WordPress:

| File | What it is |
|---|---|
| `dist/pikacart-core.zip` | **Plugin**: accounts, free trial, Razorpay payments, the organisation dashboard at `/app/`, and the Super Admin control room. Install this **first**. |
| `dist/pikacart-theme.zip` | **Theme**: the public website (homepage, header, footer, pages). |

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

## What Phase 1 contains

- Register (organisation, contact person, email, mobile, password, "I am authorised" box), login, email verification, forgot/reset/change password.
- Anti-spam: hidden honeypot field plus a time check; limits on login, registration and password reset attempts.
- **2-hour free trial** (editable) with a live countdown in the dashboard; one trial per email and per mobile number.
- Account states: Trial, Active, Expired, Suspended, Cancelled, all checked on the server.
- **Razorpay**: ₹59/month autopay (UPI/card) plus a one-time 30-day payment, test/live switch, signature checks, webhook handling where a repeated event never extends twice, auto-numbered invoices (PDF download), cancel autopay.
- **10 emails**, all editable: verify, welcome, trial ending, trial ended, payment success, payment failed, expiring soon, suspended, reactivated, reset password.
- **Organisation dashboard** at `/app/`: Dashboard, onboarding (category → details → logo, signature, seal), Organisation profile with verification privacy, Subscription, Account, Support. Works on phones.
- **Super Admin control room** (Pikacart menu): money/accounts/health stats with charts; Accounts (search, filter, CSV export, suspend/reactivate, extend trial, free days, change plan end, reset link, delete); Payments (filters, date range, CSV export); Settings with 9 tabs.
- Legal pages created automatically (About, Contact with a working form, Pricing, Privacy, Terms, Refund, Acceptable Use). Razorpay needs these.
- All 22 database tables, starting data (10 categories × 3 sub-types, 11 card sizes, 12 colour palettes, Monthly plan).

**Coming next:** Phase 2 (card designer and templates), Phase 3 (members, Excel import, downloads, print sheets), Phase 4 (Design on Demand, finance, coupons, support inbox), Phase 5 (full homepage, galleries, SEO). The menu items for those screens already exist and show a clear "coming" message.

---

## Phase 1 test checklist (click by click)

**A. Install**
1. Install and activate the plugin, then the theme (steps above). Set Permalinks to *Post name*.
2. Visit your homepage. You should see the Pikacart header, hero with 3 sample cards, categories, pricing and footer.
3. In wp-admin, click **Pikacart**. The control room dashboard opens.

**B. Razorpay (test mode)**
1. In the Razorpay Dashboard switch to **Test Mode**. Create API keys, a Plan (monthly, ₹59) and a Webhook as shown in **Pikacart → Settings → Razorpay**.
2. Paste the keys, webhook secret and Plan ID. Keep the mode on **Test**. Save.

**C. Register and trial**
1. Open your site in a private/incognito window. Click **Start Free Trial**.
2. Fill the form, tick the authorised box, click **Create account**. You land on the Welcome setup.
3. Pick a category → fill details → upload a logo (PNG) → **Finish setup**.
4. Check the top bar shows **Trial ends in 1:59:xx**, counting down.
5. Check your email for the *Verify* and *Welcome* emails. Click the verify link. The blue "verify" banner disappears after a refresh.
6. Try registering again with the same mobile number: you should see "already exists".

**D. Pay (test mode)**
1. Go to **Subscription** → **Subscribe with autopay** → pay with a Razorpay test card or test UPI (`success@razorpay`).
2. You should see "Payment successful", status **Active**, and a row in Payment history. Click the invoice button: a PDF downloads.
3. Try the **Pay once for 30 days** button too (the date moves forward 30 more days).
4. Click **Cancel autopay** → confirm. Status shows **Cancelled** with the end date; access continues.

**E. Trial expiry**
1. In wp-admin set **Pikacart → Settings → Trial and Plans → Free trial length** to `3` minutes. Register a second test account in a private window.
2. When the timer reaches zero, the dashboard shows "Your free trial has ended" with the Subscribe button. You get the *trial ended* email.
3. Set the trial length back to `120`.

**F. Super Admin**
1. **Pikacart → Dashboard**: money and account numbers match what you did; charts show data.
2. **Accounts** → search for your test account → open it → **Extend trial**, **Grant free days**, **Suspend** (with a reason).
3. In the incognito window try to log in: you see "This account is suspended. Please contact contact@pikacart.in."
4. Back in wp-admin click **Reactivate**. The customer can log in again (and gets an email).
5. **Payments**: filter by date; click **Export to Excel (CSV)**.
6. **Settings**: change the brand colour, save, refresh the site. Buttons change colour.

**G. Phone**
1. Open `/app/` on your mobile. Open the menu (☰), visit every screen. Nothing should need sideways scrolling.

When everything works, reply **"next"** for Phase 2.

---

## For developers

- `pikacart-core/` plugin source, `pikacart/` theme source.
- `build.sh` creates the two ZIPs in `dist/`.
- REST namespace `pkc/v1`; tables prefixed `wp_pkc_`; schema version in option `pkc_db_version`.
- Libraries bundled locally: Chart.js 4.4.4, jsPDF 2.5.2, Inter and Plus Jakarta Sans fonts (OFL). Razorpay Checkout must load from `checkout.razorpay.com` (Razorpay requires this). It loads only on the Subscription screen.
