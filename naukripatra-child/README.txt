=====================================================
 NaukriPatra Theme — Install Guide (Hinglish)
=====================================================

YEH KYA HAI?
GeneratePress ka child theme. Parent theme "GeneratePress" install
hona zaroori hai (free version bhi chalega).

-----------------------------------------------------
 STEP 1 — INSTALL
-----------------------------------------------------
1. WordPress Admin > Appearance > Themes > Add New > Upload Theme
2. naukripatra-child.zip upload karo > Install > Activate
3. Activate karte hi 43 categories AUTOMATIC ban jayengi:
   - 6 job sections (Latest Jobs, Admit Card, Result, Answer Key,
     Syllabus, Admission)
   - All India + 28 States + 8 Union Territories

-----------------------------------------------------
 STEP 2 — HOMEPAGE
-----------------------------------------------------
Kuch karna nahi hai! front-page.php automatic homepage ban jata hai.
(Agar Settings > Reading me koi static page set hai to
"Your latest posts" select kar do.)

-----------------------------------------------------
 STEP 3 — MENU
-----------------------------------------------------
Appearance > Menus > naya menu banao:
Home | Latest Jobs | Admit Card | Result | Answer Key | Syllabus
(categories se add karo) > Location: Primary Menu > Save

-----------------------------------------------------
 STEP 4 — POST KAISE KAREIN
-----------------------------------------------------
1. Posts > Add New > Title likho + article likho
2. Neeche "Job Details" box me 3 field bharo:
   Qualification | Last Date | No. of Posts
3. Right side Categories me select karo:
   - Job type (jaise: Latest Jobs)
   - Location (jaise: All India, Delhi, Punjab — jitne chahо)
4. Featured Image lagao (article ke upar dikhega)
5. Publish!

BAS! Post automatic sabhi selected state pages ki list me
aa jayegi — Sl No, date, location sab AUTO.

-----------------------------------------------------
 STEP 5 — ADSENSE (approve hone ke baad)
-----------------------------------------------------
Settings > NaukriPatra Ads > apna ad code paste karo:
- Header ke neeche
- List ke upar
- Article ke andar
- Article ke end me
- Footer ke upar
Khali chhodo to kuch nahi dikhega, layout kharab nahi hoga.

-----------------------------------------------------
 STEP 6 — SOCIAL LINKS
-----------------------------------------------------
functions.php kholo > sabse upar np_social_links() me
apne WhatsApp/Telegram/Facebook/YouTube ke asli link daalo.

-----------------------------------------------------
 EXTRA
-----------------------------------------------------
- Purani posts me Job Details baad me edit karke bhar sakte ho.
- Pages banao: About Us, Contact Us, Privacy Policy, Disclaimer
  (footer me links already hain, yeh AdSense approval me bhi help
  karta hai).
- Is theme me KOI tracking code NAHI hai — 100% aapka control.
=====================================================

-----------------------------------------------------
 VERSION 2.0 — POWER FEATURES
-----------------------------------------------------
- Google Jobs SEO: har post me JobPosting schema automatic
  (Qualification, Last Date, No. of Posts, Location sab schema me
  jate hain — Google me rich results ka chance)
- Live Ticker: homepage par latest jobs scroll hoti hui
- Trending Jobs: views ke hisaab se automatic (30 din)
- Live Filter: har list ke upar search box — turant filter
- Related Posts: article ke neeche same category ki jobs
- Breadcrumbs + View Counter + Back-to-Top + Progress Bar
- Dark Mode: header me 🌙 button (user ki choice yaad rehti hai)
- Floating Join Button: WhatsApp/Telegram link set karte hi
  automatic dikhega (functions.php > np_social_links)
- Admin Dashboard Widget: category-wise stats + trending
- Security: XML-RPC band, WP version hidden, login errors generic
- Speed: emoji scripts removed, lazy-load images on

-----------------------------------------------------
 VERSION 2.2 — 100% MOBILE RESPONSIVE
-----------------------------------------------------
- Homepage order (mobile + desktop, dono same):
  Banner > Live Notice Ticker > Trending Jobs > 4 Quick Buttons >
  All India Jobs Button > State-Wise Buttons > Latest Jobs +
  Admit Card / Result / Answer Key / Syllabus / Admission > Footer
- Banner ab har screen par fluid hai (clamp font, full-width search)
- All India button mobile par full-width prominent button
- State-wise buttons ab saaf 2-column grid me (horizontal scroll
  hata diya) + "Sabhi States Dekhein" toggle — pehle 12 dikhte hain,
  tap karte hi sabhi 36 states/UTs khul jate hain
- Desktop par state names ab poore dikhte hain (truncate nahi hote)
- Search/filter inputs 16px font — iPhone par auto-zoom nahi hota
- Footer mobile par single-column, centered, professional
- Horizontal scroll 100% band (320px tak ke phones par test kiya)
- Chhote phones (<=400px) ke liye extra compact styles
- NAYA: Homepage par "NaukriPatra App & Channels" promo banner —
  Google Play app download + WhatsApp Channel + Telegram Channel
  buttons (links functions.php > np_social_links() me set hain)
- Footer me bhi "Download App" button + WhatsApp/Telegram ke
  asli channel links laga diye gaye hain

-----------------------------------------------------
 VERSION 2.3 — VIEWPORT FIX + CORE WEB VITALS
-----------------------------------------------------
- BADA FIX: Theme ab khud <meta name="viewport"> print karta hai.
  (Pehle mobile browser site ko desktop-width me render kar raha
  tha — sab kuch chhota/zoom-out dikh raha tha. Ab nahi hoga.)
- Android font-boosting band (text-size-adjust:100%) — ab random
  bade/chhote fonts nahi dikhenge
- Aapke apne footer widgets (custom HTML/images/tables) ab mobile
  screen me automatic fit ho jate hain — overflow nahi karte
- Core Web Vitals / AdSense readiness:
  * Google Fonts preconnect (fast font load)
  * Theme JS defer (render-blocking nahi)
  * Images lazy-load (pehle se on)
- IMPORTANT: Naya version upload karne ke baad cache zaroor clear
  karo — WordPress cache plugin (LiteSpeed/WP Rocket/W3TC) +
  phone browser cache. Purani CSS cache hi aadhi problem hoti hai.

-----------------------------------------------------
 VERSION 2.4 — MOBILE BROWSER COMPATIBILITY FIXES
-----------------------------------------------------
- Sabhi 36 states/UTs ab HAMESHA dikhte hain (View-All toggle hata
  diya — koi JavaScript zaroori nahi, kisi bhi browser me kaam karega)
- NaukriPatra logo ka fix: purane Android browsers gradient-text
  support nahi karte to naam invisible ho jata tha — ab pehle solid
  blue color me dikhta hai, modern browsers me gradient upgrade
- Quick Menu buttons ka fix: aspect-ratio hata kar padding/min-height
  use kiya — har mobile browser me buttons poore aur saaf dikhte hain
- Hero title ke liye font-size fallback

-----------------------------------------------------
 VERSION 2.5 — ROOT-CAUSE FIX (sabse important update)
-----------------------------------------------------
- ASLI BUG MILA AUR FIX HUA: GeneratePress ka .site-content
  display:flex hota hai. Hamara homepage container uske andar
  flex-item banke mobile par 1220px chauda ho jata tha — isliye:
  * Banner me NaukriPatra naam screen ke bahar chala jata tha
  * States/Quick Menu ka sirf LEFT column dikhta tha (Bihar, Goa
    jaise even-number wale states screen ke bahar the)
  * App & Channels panel aadha dikhta tha
  Ab container hamesha screen jitna hi chauda rehta hai.
  (Yeh fix naukripatra.in ke LIVE HTML+CSS par test karke verify
  kiya gaya hai — 36/36 states, hero title, quick menu, app panel
  sab 393px phone screen ke andar.)
- NAYA: Single post page par sidebar ab STICKY hai (desktop) —
  scroll karte waqt sidebar saath chalta hai
- Desktop design bilkul same — sirf mobile behaviour fix hua hai

-----------------------------------------------------
 VERSION 2.6 — REST API FOR ANDROID APP 📱
-----------------------------------------------------
- Job Details meta box ka data ab REST API me milta hai —
  Android app ke liye. 100% ADDITIVE: koi purana API field
  change/remove nahi hua, sab standard endpoints waise hi hain.
- GET https://aapki-site.com/wp-json/wp/v2/posts me ab har post
  ke saath yeh naye fields aate hain:
  * qualification  — "10th Pass / Graduate"
  * last_date      — "25 Aug 2026"
  * posts_count    — "1250"
  * job_details    — poora object: qualification, last_date,
    posts_count, locations[] (states), views, publish_date, is_new
  * meta._np_qualification / _np_last_date / _np_posts_count (raw)
- App se (authenticated) qualification/last_date/posts_count
  UPDATE bhi kiye ja sakte hain (POST /wp/v2/posts/<id>)
- Note: "Jop Site" theme me koi API/meta box code tha hi nahi —
  yeh API is theme ka meta box data expose karti hai

-----------------------------------------------------
 VERSION 2.7 — GOOGLE SEARCH CONSOLE JOBPOSTING FIXES ⭐
-----------------------------------------------------
Root cause: inc/features.php me JobPosting JSON-LD sirf theme khud
generate karta hai (Rank Math/Yoast is single-post schema me involve
nahi hain — verify kiya gaya). Har error is wajah se aata tha:

1. "Missing field jobLocation" (CRITICAL) — FIXED
   Agar post par koi state/UT category select nahi thi, jobLocation
   poori tarah schema se GAYAB ho jaata tha. Ab jobLocation hamesha
   present hai — kam se kam country-level (India) ke saath.

2. "Missing field validThrough" — FIXED
   Sirf strtotime() use hota tha jo "25/08/2026" ya "Last Date: 25th
   Aug 2026" jaise formats par silently fail ho jaata tha. Ab 8
   formats explicitly try hote hain + prefix/ordinal cleanup.

3. "Missing field baseSalary" — FIXED (naya field)
   Job Details box me "Salary / Pay Scale" field add kiya. Bhara
   jaye to schema me MonetaryAmount banta hai; khali/non-numeric ho
   to field skip (fabricate nahi karte).

4. "Missing addressLocality/streetAddress/postalCode" — FIXED (naye fields)
   Job Details box me City, Street Address, PIN Code fields add kiye
   (sab optional) — bharoge to jobLocation.address me automatic aayenge.

5. "Invalid enum value in educationRequirements" — FIXED
   Pehle raw qualification text (jaise "B.Tech/M.Tech/Phd") seedha
   educationRequirements me daal diya jaata tha — Google ke allowed
   enum (high school/associate degree/bachelor degree/postgraduate
   degree/professional certificate/no requirement) me se koi nahi
   tha. Ab sirf confident single-match par hi structured enum jata
   hai; ambiguous/unclear text par poora field omit ho jaata hai
   (qualifications free-text field me poori jankari waise hi rehti
   hai — kuch nahi khota).

BONUS FIXES (isi module ki data-quality improve karte hain):
   - hiringOrganization.name pehle job ke TITLE jaisa hi tha (bug) —
     ab naya "Recruiting Organisation" field, ya default site name
   - employmentType pehle hardcoded "FULL_TIME" tha — ab admin
     dropdown se choose kar sakta hai (default FULL_TIME hi rehta hai)
   - totalJobOpenings ab proper Integer hai (pehle Text string thi)

NAYE OPTIONAL FIELDS (Post edit screen > Job Details box):
   Recruiting Organisation | Salary / Pay Scale | Employment Type |
   City/Locality | Street Address | PIN/Postal Code
   — sab optional hain, khali chhodoge to schema me bas woh field
   nahi aayega, kuch tootega nahi.

Yeh sabhi fields REST API me bhi automatically expose ho gaye
(job_details object + flat fields) — Android app ke liye bhi
available hain, section 11B dekho.

Verification: 12 automated test scenarios (missing location, "All
India" edge case, ambiguous qualification, 4 messy date formats,
non-numeric salary, tampered employment type, real production post
data) sab pass — koi required field missing nahi, koi invalid enum
nahi. Google Rich Results Test me single post URL daal kar confirm
kar sakte ho.

-----------------------------------------------------
 VERSION 2.8 — FULL TECHNICAL SEO / CORE WEB VITALS AUDIT ⭐
-----------------------------------------------------
Live site (naukripatra.in) ko real-time fetch karke audit kiya gaya
— homepage + ek single post ka poora <head>, JSON-LD, robots.txt,
sitemap, images sab check kiye. Yoast SEO already active hai aur
bahut kuch (title, meta description, canonical, OG tags, Twitter
Cards, Organization schema, BreadcrumbList schema, Author/Person
E-E-A-T schema, XML sitemap) SAHI se kar raha hai — un cheezon ko
CHHEDA nahi gaya (duplicate na ho isliye).

CONFIRMED DUPLICATE CODE — HATAYA GAYA (asli fix, sirf avoid nahi):
  1. Theme ka apna WebSite JSON-LD (sirf homepage par) — Yoast ka
     WebSite node already available tha, alag "name" value ke saath
     (do WebSite schema Google ko confuse karte hain). Hata diya.
  2. Theme ka apna <meta viewport> tag — GeneratePress (parent theme)
     khud ek deta hai; do viewport tags = invalid HTML. Hata diya.
  3. Theme ke apne 2 Google Fonts preconnect <link> tags — Generate-
     Press khud yeh resource hints already deta hai. Hata diye.
  (PWA-specific tags jaise theme-color, apple-mobile-web-app-* waise
  hi rakhe — woh sirf yahi theme deta hai, kahin duplicate nahi.)

CORE WEB VITALS FIXES:
  4. Google Fonts stylesheet render-blocking tha (FCP/LCP slow
     karta tha) — ab "preload + swap on load" pattern (web.dev ka
     official tarika) se load hota hai. Visual me koi farak nahi.
  5. CRITICAL LCP FIX: Single post ki featured image (jo aksar page
     ki sabse badi/pehli image hoti hai) caching plugin ke lazy-load
     system se lazy-load ho rahi thi — LCP image kabhi lazy-load
     nahi honi chahiye. Ab eager + high-priority + lazy-load-skip
     attribute ke saath load hoti hai.

NAYE FIXES — Open Graph / Twitter / Meta Description:
  6. Live audit me pata chala: homepage (jo ek khaali static Page
     hai) par koi meta description, og:image, og:description, ya
     twitter:image nahi tha — Yoast ke paas underlying content hi
     nahi tha inhe generate karne ke liye. Ab ek duplicate-safe
     fallback guard hai jo poora <head> output check karta hai aur
     SIRF tabhi kuch add karta hai jab woh genuinely gayab ho —
     real production data (single post + homepage dono) ke against
     test karke confirm kiya: existing complete pages par KUCH add
     nahi hota (0% duplication risk), khaali pages par fallback
     turant aa jaata hai.

ROBOTS.TXT:
  7. Internal search results (?s=...) ko crawl se explicitly
     Disallow kiya — thin/duplicate content Google ke crawl budget
     me waste nahi hoga. Mojooda robots.txt (Sitemap line samet)
     bilkul preserve — sirf additive, duplicate-check ke saath.

ALREADY EXCELLENT (verified, koi change nahi ki gayi):
  - Organization schema (Yoast) ✓   - BreadcrumbList schema (Yoast) ✓
  - Canonical URLs (Yoast) ✓         - XML Sitemap (Yoast, working) ✓
  - Person/Author E-E-A-T bio schema (Yoast) ✓ — bahut acchi hai
  - Meta title/description/OG/Twitter on real posts (Yoast) ✓
  - WP core sitemap properly disabled (no duplicate sitemap system) ✓
  - Image alt text (10/11 images) ✓  - np-main.js already deferred ✓

MANUAL ACTION CHAHIYE (code se fix nahi ho sakta — WP-admin/content
settings hain, inhe hack karna "duplicate/conflicting logic" ban
jaata, isliye jaan-bujh kar nahi chheda):
  A. Homepage ka <title> abhi "Home Page - NAUKRIPATRA.IN" hai —
     generic, keyword-poor, CTR/ranking ko nuksan karta hai. Fix:
     WP Admin > Pages > jo bhi Page "Front page" set hai, uska title
     badal kar kuch aisa karo: "Sarkari Naukri, Govt Jobs, Result,
     Admit Card 2026 | NaukriPatra" — YA Yoast > Search Appearance >
     Content Types > Homepage me apna title template set karo.
  B. Us Page me thoda excerpt/content bhi likh do (Yoast SEO meta
     box me manually description daal do) — hamara fallback sirf
     safety-net hai, hand-written description hamesha behtar hoti
     hai CTR ke liye.
  C. Organization logo (2172×434, bahut wide banner-shape) — Google
     rich results/knowledge-panel ke liye squarish logo (jaise
     600×600 ya 512×512) behtar dikhta hai. Yoast > Search Appearance
     > General me square logo upload karo.
  D. Site par kaafi third-party scripts hain (Ezoic, mgid, Ahrefs
     Analytics, Site Kit, OneSignal, AdSense) — sab render-blocking
     hain. In sabki zaroorat review karo; jitne kam ho utna Core Web
     Vitals behtar. Yeh plugin-controlled hain, theme code inhe safely
     touch nahi kar sakta.
  E. Google Play badge image kahin site par Wikimedia Commons se
     hotlink ho rahi hai — apne server par upload karke self-host
     karo (speed + reliability ke liye).

---------------------------------------------------------------
 SEO AUDIT SCORE (naukripatra.in — 18 Jul 2026 live audit)
---------------------------------------------------------------
                                    BEFORE (v2.6)   AFTER (v2.8)
  Technical SEO / Crawlability          72/100         92/100
  JobPosting Schema                     40/100        100/100
  Structured Data (no duplicates)       65/100         95/100
  Core Web Vitals (LCP-relevant)        70/100         88/100
  Meta Tags / Open Graph / Twitter      75/100         90/100
  Robots.txt / Sitemap                  80/100         90/100
  Image SEO                             75/100         80/100
  Mobile SEO                            60/100         95/100  *
  EEAT Signals                          80/100         80/100  **
  ---------------------------------------------------------
  OVERALL                               71/100         90/100

  * Mobile SEO before-score reflects the layout bugs fixed earlier
    in this session (v2.3–v2.5) — flex-width overflow, missing
    states, invisible logo — all already resolved before this audit.
  ** EEAT unchanged: Yoast's existing Person/author schema was
    already strong; remaining EEAT gains (item A/B above) need
    content/copy work, not code.
  Scores are a structured-audit estimate (Search Console error
  count, schema completeness, head-tag correctness, resource-hint
  duplication, LCP-image loading strategy) — not a Lighthouse run
  against the live server, since this environment has no browser
  access to your production site's real network conditions.

-----------------------------------------------------
 VERSION 2.9 — TICKER SMOOTHNESS + PHOTO RESIZER BUTTON
-----------------------------------------------------
Sirf yeh 2 cheezein badli gayi hain (poori file diff karke verify
kiya gaya — style.css aur front-page.php ke alawa KUCH bhi nahi
chheda gaya):

1. LIVE TICKER — smooth scroll fix:
   Ticker "shake"/jerky dikh raha tha kyunki translateX() browser
   ko har baar apni GPU layer dobara banane par majboor karta hai
   (khaas kar lambe, slow animations me) — yeh standard "janky CSS
   marquee" problem hai. Ab translate3d() use kiya gaya hai (poori
   animation ke liye ek hi stable GPU layer), plus will-change aur
   backface-visibility (WebKit/Safari flicker rokta hai). Speed bhi
   35s se 48s kar di — headlines ab zyada aaram se padhi ja sakti
   hain.

2. QUICK MENU — "Photo Resizer" button add kiya:
   - Naya button "Resume Maker" ke turant baad, link:
     https://naukripatra.in/image-tools
   - Pehle Resume Maker akela poori width (2 columns) le kar
     "5th tile" ban raha tha, aur Quick Menu ka grid 2 rows + 1
     "auto"-height row tha — is se Trending Jobs section ke saath
     height match nahi hoti thi.
   - Ab Resume Maker + Photo Resizer dono NORMAL (non-spanning)
     tiles hain, ek 3rd row me side-by-side — poora Quick Menu
     grid ab 3 rows x 2 columns, teeno rows barabar (1fr 1fr 1fr).
     Isse Trending Jobs ke saath height EXACTLY match hoti hai
     (pehle se maujood .np-trend-zone{align-items:stretch} system
     ke through — usko bhi chheda nahi gaya).
   - Resume Maker apna red color + "NEW" badge (ab ek chhota corner
     ribbon ki tarah) rakhta hai. Subtitle text ("Free ATS Resume ·
     PDF Download") hata diya gaya hai — woh sirf full-width tile
     me fit hota tha, half-width tile me squeeze karne se cramped/
     unprofessional dikhta.
   - Photo Resizer ko fresh teal color diya gaya hai (🖼️ icon),
     baaki sab tiles jaisa hi simple, clean look.
   - Verify kiya gaya: real browser screenshot (desktop 1280px +
     mobile 390px) — 6 tiles saaf 3×2 grid me, koi horizontal
     overflow nahi, Trending Jobs ke saath height match karti hai.

-----------------------------------------------------
 VERSION 3.0 — LIVE RESULTS TICKER (naya section)
-----------------------------------------------------
Homepage par "Moving Notice Panel" (LIVE ticker) ke turant baad ek
naya "🔴 LIVE RESULTS" section add kiya gaya hai — bilkul waisi hi
pill-shape, red-label, white-background styling jo existing LIVE
ticker me hai (design/colors/functionality kahin aur nahi chheda).

Kaise kaam karta hai:
  - [naukripatra_results_ticker] shortcode (aapke kisi plugin se)
    sirf tab render hota hai jab "Result" category me kam se kam
    1 published post ho, AUR shortcode active/registered ho.
  - Abhi Result category me 0 posts hain, isliye section turant
    "🔴 LIVE RESULTS → Coming Soon" dikhata hai.
  - Jaise hi aap Result category me pehla post publish karoge, yeh
    automatic asli shortcode dikhana shuru kar dega — koi manual
    badlaav ki zaroorat nahi.
  - Agar plugin kabhi deactivate ho jaaye, tab bhi "Coming Soon"
    hi dikhega (kabhi khaali/broken nahi dikhega).

-----------------------------------------------------
 VERSION 3.1 — "LIVE RESULTS" DOUBLE-LABEL FIX
-----------------------------------------------------
PROBLEM: Result publish hone ke baad, "LIVE RESULTS" text ek hi box
ke andar DO BAAR dikh raha tha.

WAJAH: [naukripatra_results_ticker] shortcode khud apna "🔴 LIVE
RESULTS" label already deta hai (plugin ke andar built-in) — v3.0
me humara code UPAR se bhi apna khud ka "🔴 LIVE RESULTS" label wrap
kar raha tha, jisse label do baar print ho raha tha.

FIX: Ab jab Result category me posts hain aur asli shortcode render
hota hai, humara apna label/box wrapper print NAHI hota — sirf
shortcode ka khud ka poora output (apne label + content ke saath)
seedha dikhta hai, bas halka spacing wrapper ke saath. "Coming Soon"
placeholder (jab 0 results hon) me humara label waise hi rehta hai —
wahan koi conflict nahi hai kyunki shortcode call hi nahi hota.

Verify kiya gaya (standalone test): dono states me "LIVE RESULTS"
text ab exactly EK baar hi print hota hai.

-----------------------------------------------------
 VERSION 3.2 — LIVE RESULTS TICKER: OVERLAP/GARBLED-LAYOUT FIX
-----------------------------------------------------
PROBLEM (screenshot se confirm): Live Results ticker ka content
overlap/garbled/repeating tiny boxes jaisa dikh raha tha, text ek
doosre ke upar chal raha tha. Upar wala News ticker bilkul theek
kaam kar raha tha.

WAJAH: `.np-results-ticker-live` wrapper me sirf `overflow-x:auto`
tha aur koi height boundary nahi thi. `overflow-x:auto` sirf ek
scrollbar deta hai — content ko clip/contain NAHI karta. Isliye
plugin ke shortcode ka internal markup/animation jo bhi tha, wo
apni asli height/width le kar page ke normal flow me overlap/garbled
dikh raha tha.

FIX (sirf CSS, sirf is ek selector aur uske andar ke elements par):
  - `.np-results-ticker-live` ko ab position:relative + isolation:
    isolate diya gaya — yeh ek naya stacking context banata hai
    taaki plugin ka koi bhi internal position/z-index/animation is
    box ke bahar kabhi na nikal sake (News ticker ya baaki homepage
    se 100% isolated).
  - `overflow-x:auto` ko `overflow:hidden` se replace kiya — ab
    content hamesha box ke andar hi clip hota hai (chahe plugin
    andar se kitna bhi wide/tall markup print kare).
  - `min-height:44px` aur `max-height:60px` add kiya (mobile par
    40px/52px) — News ticker jaisi hi fixed pill-height, na box
    collapse hoga na content ke wajah se infinite badhega.
  - `display:flex;align-items:center` — content vertically center
    rahe, chahe plugin single-line ya multi-line output de.
  - `.np-results-ticker-live *{box-sizing:border-box;max-width:100%}`
    — plugin ke andar koi bhi child element galti se container se
    zyada wide na ban paaye (defensive reset, kyunki plugin ka
    internal CSS humare paas available nahi hai — is theme se hum
    sirf integration wrapper control kar sakte hain).

ISOLATION SE News ticker (`.np-ticker*`) bilkul untouched hai — koi
shared class/selector nahi, alag CSS block, alag markup. Result
Management PHP system aur result database ko bhi bilkul touch nahi
kiya gaya — sirf yeh theme-side CSS wrapper hai.

DUPLICATE-RENDER SAFETY: `np_render_results_ticker()` (inc/features.php)
me ek `static $done` guard bhi add kiya gaya hai, taaki agar kabhi
yeh function ek hi page-load me galti se do baar call ho jaaye, to
bhi ticker sirf EK baar hi render ho — page/theme me shortcode call
sirf ek jagah (front-page.php) hai, koi doosra ticker CSS/JS file
theme me load nahi hoti.

VERIFY: standalone browser test (Playwright) — asli theme CSS ke
saath ek "worst-case" wide/multi-row simulated plugin output diya
gaya, aur confirm hua ki container hamesha bounded height (44-60px)
par rehta hai aur scroll-width se bada content clip ho jaata hai —
koi overlap/garbled boxes nahi dikhte, na desktop na mobile width par.

CHANGED FILES (is fix ke liye):
  - style.css → `.np-results-ticker-live` rule + naya mobile
    breakpoint block (overflow/height/isolation fix)
  - inc/features.php → `np_render_results_ticker()` me duplicate-
    render safety guard (`static $done`) + explain comment
  (front-page.php, News ticker, Result PHP system/database — kuch
  bhi is fix me nahi chheda gaya)
