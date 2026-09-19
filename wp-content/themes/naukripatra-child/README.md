# Naukripatra Skin — GeneratePress child theme

naukripatra.in ka **look-only** child theme. HTML, URL, REST API, SEO — kuch nahi
badalta. Sirf CSS (aur single post par ek chhota JS jo sirf class add karta hai).

---

## Install (5 minute)

1. **Backup lein** — UpdraftPlus → *Backup Now* (database + files). `docs/BACKUP-AND-STAGING.md` dekhiye.
2. **Appearance → Themes → Add New → Upload Theme** → `naukripatra-child.zip` → **Install** → **Activate**.
   (GeneratePress installed rehna chahiye — yeh uska child hai.)
3. Homepage aur ek job post kholkar dekh lein.
4. Pasand na aaye? **Appearance → Themes → GeneratePress → Activate.** Purana look turant wapas.

> Zaroori: activate karne ke baad apni purani CSS **mat hataiye**.
> Skin uske upar apne aap lag jati hai (`body.np-skin` scoping ki wajah se).
> Baad me chahein to purani np- CSS hata sakte hain — tab skin aur saaf dikhegi.

---

## Kya-kya style hota hai

**Homepage** — bilkul wahi sections, wahi order, sirf naya look:

| Aapka block | Naya look |
|---|---|
| `.np-hero` | Navy → blue gradient, gold glow, badi search bar |
| `.np-ticker` (🔴 LIVE) | Red label + white card, hover par ruk jata hai |
| `.npr-ticker` (LIVE RESULTS) | Green label, "CHECK RESULT" chip |
| `.np-trend-zone` | Trending Jobs + Quick Menu — do saaf cards |
| `.np-quick-btn` | Icon tiles, hover par lift + shadow |
| `.np-appbar` | Gradient band, Play/WhatsApp/Telegram buttons |
| `.np-states` | 36 state buttons, gold dot, hover highlight |
| `.np-sections` (`np-card-1…6`) | 6 colour-coded card panels, NEW badge, "View All" footer |
| `.np-float`, `.np-top`, `.np-sticky-menu` | Restyled, jagah wahi |

**Single job post — Naukri.com jaisa:**

- `⚡ Quick Details` box → gold-accent highlights strip
- Content ek white card me, h2 par gold accent bar
- Tables saaf (navy header, alternate rows, mobile par apna scroll — page side me nahi hilta)
- **Important Links** list → button grid (sirf class add hoti hai)
- **FAQ** ke `Q1.` heading + answer → accordion (text DOM me hi rehta hai)
- Mobile par neeche **sticky Apply bar**, page ke apne links se banti hai

---

## Colour badalna (ek jagah)

`style.css` ke top par `:root` block hai:

```css
--np-navy:#12307f;   /* main brand colour  */
--np-gold:#c08a12;   /* accent / CTA       */
--np-red:#c62828;    /* LIVE, NEW, last date */
--np-green:#0f7a4d;  /* results, Apply Now */
```

Inhe badaliye — poori site follow karegi. Editing: **Appearance → Theme File Editor → style.css**,
ya behtar: **Appearance → Customize → Additional CSS** me sirf yeh likh dijiye:

```css
:root{ --np-navy:#0b3fd8; --np-gold:#ff7a00; }
```

---

## Kaunsi screen se kya control hota hai

| Kya badalna hai | Kahan jaiye |
|---|---|
| Naya job post | **Posts → Add New** (bilkul jaise abhi karte hain) |
| Logo / site title | Appearance → Customize → Site Identity |
| Menu | Appearance → Menus |
| Widgets / ad slots | Appearance → Widgets |
| Homepage sections | Wahi page/blocks jo abhi edit karte hain |
| Push notification | OneSignal menu (untouched) |
| AdSense | Site Kit menu (untouched) |

---

## Switch off karne ke tarike (rollback)

`wp-config.php` me `/* That's all, stop editing! */` se upar:

```php
define( 'NP_SKIN', false );           // poori skin band
define( 'NP_SKIN_FONTS', false );     // Google Fonts band, baaki chalu
define( 'NP_SKIN_POST_JS', false );   // FAQ accordion + apply bar band
define( 'NP_SKIN_FAQ', false );       // sirf FAQ accordion band
define( 'NP_SKIN_APPLY_BAR', false ); // sirf mobile apply bar band
```

Ya theme hi switch kar dijiye — **Appearance → Themes → GeneratePress**.

---

## Guarantee (kya nahi chhua gaya)

- ❌ Koi URL, slug, permalink, redirect — nahi
- ❌ Koi custom post type, custom field, taxonomy, DB table — nahi
- ❌ `/wp-json` REST API — waisa ka waisa
- ❌ Sitemap, canonical, schema, SEO plugin output — waisa ka waisa
- ❌ Post ka content edit/delete — nahi (JS sirf class add karta hai)
- ❌ GeneratePress parent theme ya koi plugin file — nahi
- ✅ Deactivate = purana look, zero data loss

## Files

| File | Kaam |
|---|---|
| `style.css` | Poori skin (252 rules) + brand colour variables |
| `functions.php` | Parent + child CSS load, `np-skin` body class, switches |
| `assets/js/skin.js` | Sirf single post par: class add, table scroll wrap, FAQ accordion, apply bar |
| `screenshot.png` | Themes screen ka thumbnail |
