import re, json, subprocess, os, requests, html

BASE = 'http://localhost:8899'
S = os.path.dirname(os.path.abspath(__file__))
WP = ['php', S + '/wp-cli.phar', '--allow-root', '--path=' + S + '/wordpress']
FAIL, PASS = [], []


def check(cond, name, extra=''):
    (PASS if cond else FAIL).append(name)
    print(('  PASS ' if cond else '  FAIL ') + name + (('  -> ' + str(extra)[:300]) if (not cond and extra) else ''))


def wp(code):
    r = subprocess.run(WP + ['eval', code], capture_output=True, text=True)
    return (r.stdout + r.stderr).strip()


def get(path, **kw):
    return requests.get(path if path.startswith('http') else BASE + path, **kw)


def ld(html_text):
    out = []
    for m in re.findall(r'<script type="application/ld\+json">(.*?)</script>', html_text, re.S):
        out.append(json.loads(m))
    return out


def types(html_text):
    t = []
    for block in ld(html_text):
        for n in block.get('@graph', [block]):
            t.append(n.get('@type'))
    return t


def nonce_for(text, action):
    idx = text.find('name="dm_action" value="%s"' % action)
    if idx < 0:
        return None
    m = re.findall(r'name="_dmnonce" value="([^"]+)"', text[max(0, idx - 800):idx])
    return m[-1] if m else None


print('== Setup demo data')
admin = wp('echo get_user_by("login","admin")->ID;')
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=1; $s["whatsapp_number"]="919000000001"; update_option("dm_settings",$s);')
setup = wp(r'''
$cat = wp_insert_term("Study Notes V2","dm_category"); $cat = $cat["term_id"];
$cat2 = wp_insert_term("Web Services V2","dm_category"); $cat2 = $cat2["term_id"];
$mk = function($title,$price,$sale,$cat,$extra=array()) { $id = wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>$title,"post_author"=>1,"post_content"=>"<p>Body text for $title.</p>","post_excerpt"=>"Short $title")); wp_set_object_terms($id,array($cat),"dm_category"); update_post_meta($id,"_dm_price",$price); update_post_meta($id,"_dm_sale_price",$sale); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); foreach($extra as $k=>$v){update_post_meta($id,$k,$v);} dm_sync_effective_price($id); return $id; };
$d = $mk("Physics Notes V2",100,"",$cat,array("_dm_what_you_get"=>"PDF 80 pages\nSolved examples"));
$d2 = $mk("Maths Notes V2",200,"",$cat2);
$sv = $mk("School Site V2",5000,"",$cat2,array("_dm_service_mode"=>1,"_dm_faq"=>"How long? | One week.\nHosting? | We help you.","_dm_packages"=>array(array("name"=>"Basic","price"=>5000,"features"=>array("5 pages"),"popular"=>1))));
$af = $mk("Host Deal V2",69,"",$cat2,array("_dm_affiliate"=>1,"_dm_aff_url"=>"https://partner.example.com/aff?id=SECRET123","_dm_aff_slug"=>"hostv2"));
$sp = $mk("Sale Past V2",100,50,$cat,array("_dm_sale_to"=>wp_date("Y-m-d\TH:i", time()-3600)));
$sf = $mk("Sale Future V2",100,50,$cat,array("_dm_sale_from"=>wp_date("Y-m-d\TH:i", time()+86400)));
$sn = $mk("Sale Now V2",100,60,$cat,array("_dm_sale_to"=>wp_date("Y-m-d\TH:i", time()+86400)));
echo json_encode(compact("cat","cat2","d","d2","sv","af","sp","sf","sn"));
''')
ids = json.loads(setup.splitlines()[-1])
link = lambda pid: wp('echo get_permalink(%s);' % pid)

print('== SEO head')
r = get('/')
check('<meta name="description"' in r.text, 'homepage meta description')
check('<link rel="canonical" href="%s/"' % BASE in r.text, 'homepage canonical')
check('property="og:title"' in r.text and 'property="og:site_name"' in r.text, 'open graph tags')
t = types(r.text)
check('Organization' in t and 'WebSite' in t, 'Organization + WebSite schema', t)
check(r.text.count('<h1') == 1, 'homepage has exactly one H1', r.text.count('<h1'))
pd = get(link(ids['d']))
check('Product' in types(pd.text), 'Product schema on digital product', types(pd.text))
check('BreadcrumbList' in types(pd.text), 'Breadcrumb schema on product')
prod = [n for b in ld(pd.text) for n in b.get('@graph', []) if n.get('@type') == 'Product'][0]
check(prod['offers']['price'] == '100.00' and prod['offers']['priceCurrency'] == 'INR', 'Product offer price/currency', prod['offers'])
check(re.search(r'<title>Physics Notes V2 – Study Notes V2 \| ', pd.text) is not None, 'auto SEO title formula', re.search(r'<title>(.*?)</title>', pd.text).group(1))
check('PDF 80 pages' in pd.text, 'What-you-get checklist rendered')
check(pd.text.count('<h1') == 1, 'product page has one H1')
sv = get(link(ids['sv']))
st = types(sv.text)
check('Service' in st and 'FAQPage' in st, 'Service + FAQPage schema on service', st)
check('Choose Basic' in sv.text and 'wa.me/919000000001' in sv.text, 'package WhatsApp button')
check('id="enquiry"' in sv.text, 'enquiry form on service page')
check('Add to cart' not in sv.text and 'Buy now' not in sv.text, 'no cart buttons on service')
cart = get('/cart/')
check('noindex' in cart.text, 'cart is noindex')
s_r = get('/?s=notes&post_type=dm_product')
check('noindex' in s_r.text, 'search results noindex')
f = get(link(ids['d']).replace(BASE, '') if False else '/category-products/study-notes-v2/?sort=price_asc')
check('noindex' in f.text, 'sorted/filtered catalogue noindex')
cp = get('/category-products/study-notes-v2/')
check('noindex' not in cp.text and 'CollectionPage' in types(cp.text), 'category indexable with CollectionPage schema')
rb = get('/robots.txt').text
check('Disallow: /cart/' in rb and 'Disallow: /go/' in rb, 'robots.txt disallows private paths', rb)
sm = get('/wp-sitemap.xml').text
check('dm_banner' not in sm and 'users' not in sm, 'sitemap excludes banners & users', sm[:400])
check('dm_product' in sm, 'sitemap includes products')

print('== Scheduled sale prices')
check(wp('echo dm_product_price(%s);' % ids['sp']) == '100', 'expired sale → regular price')
check(wp('echo dm_product_price(%s);' % ids['sf']) == '100', 'future sale → regular price')
check(wp('echo dm_product_price(%s);' % ids['sn']) == '60', 'active sale → sale price')
check(str(ids['sn']) in wp('echo implode(",", dm_sale_product_ids());') and str(ids['sp']) not in wp('echo implode(",", dm_sale_product_ids());'), 'sale list only has live sales')
sale = get('/sale/')
check('Sale Now V2' in sale.text and 'Sale Past V2' not in sale.text, '/sale/ page lists live sales')

print('== Affiliate partner offers')
ap = get(link(ids['af']))
check('SECRET123' not in ap.text, 'affiliate URL never printed in page')
check('/go/hostv2/' in ap.text and 'rel="sponsored nofollow noopener"' in ap.text, 'deal button uses /go/ + rel=sponsored')
check('affiliate links' in ap.text, 'affiliate disclosure shown')
g = get('/go/hostv2/', allow_redirects=False, headers={'User-Agent': 'Mozilla/5.0 test'})
check(g.status_code == 302 and 'SECRET123' in g.headers.get('Location', ''), '/go/ redirects to partner', (g.status_code, g.headers.get('Location')))
check('noindex' in g.headers.get('X-Robots-Tag', ''), '/go/ sends X-Robots-Tag noindex')
check(wp('echo dm_click_count("affiliate", %s);' % ids['af']) == '1', 'affiliate click logged')
s1 = requests.Session()
page = s1.get(link(ids['d']))
nonce = re.search(r'"nonce":"([a-f0-9]+)"', page.text).group(1)
res = s1.post(BASE + '/wp-admin/admin-ajax.php', data={'action': 'dm_cart_add', 'nonce': nonce, 'product_id': ids['af']}).json()
check(not res['success'], 'partner offer cannot be added to cart', res)
bot = get('/go/hostv2/', allow_redirects=False, headers={'User-Agent': 'Googlebot/2.1'})
check(wp('echo dm_click_count("affiliate", %s);' % ids['af']) == '1', 'bot clicks not counted')

print('== Coupons')
wp(r'''global $wpdb; $t=dm_table("coupons");
$wpdb->insert($t,array("code"=>"CATONLY","discount_type"=>"percent","discount_value"=>50,"category_ids"=>"%d","active"=>1,"is_public"=>1,"created_at"=>dm_now()));
$wpdb->insert($t,array("code"=>"CAPPED","discount_type"=>"percent","discount_value"=>90,"max_discount"=>20,"active"=>1,"created_at"=>dm_now()));
$wpdb->insert($t,array("code"=>"FIRSTONLY","discount_type"=>"flat","discount_value"=>10,"first_order"=>1,"active"=>1,"created_at"=>dm_now()));''' % ids['cat'])
lines = lambda *pids: '[' + ','.join('array("pid"=>%d,"seller"=>1,"price"=>dm_product_price(%d))' % (p, p) for p in pids) + ']'
r1 = wp('$c=dm_validate_coupon("CATONLY", %s); echo is_wp_error($c)?"err":"ok";' % lines(ids['d2']))
check(r1 == 'err', 'category coupon rejected for other category', r1)
r2 = wp('$c=dm_validate_coupon("CATONLY", %s); echo is_wp_error($c)?"err":"ok";' % lines(ids['d']))
check(r2 == 'ok', 'category coupon accepted for its category', r2)
tot = wp('update_user_meta(1,"dm_cart_coupon","CAPPED"); wp_set_current_user(1); $t=dm_cart_totals(array(%d)); echo $t["discount"];' % ids['d2'])
check(tot == '20', 'max discount cap applied (90%% capped at ₹20)', tot)
best = wp('$b=dm_best_coupon_for(%d); echo $b?$b[0]:"none";' % ids['d'])
check(best == 'CATONLY', 'best public coupon hint for product', best)
check('Available offer' in get(link(ids['d'])).text, 'offer box on product page')
wp(r'''global $wpdb; $u=get_user_by("login","admin"); $wpdb->insert(dm_table("orders"),array("buyer_id"=>$u->ID,"buyer_email"=>"a@b.c","order_total"=>10,"payment_status"=>"paid","created_at"=>dm_now()));''')
r3 = wp('wp_set_current_user(1); $c=dm_validate_coupon("FIRSTONLY", %s); echo is_wp_error($c)?$c->get_error_code():"ok";' % lines(ids['d']))
check(r3 == 'first', 'first-order coupon rejected for repeat buyer', r3)
cs = requests.Session()
cr = cs.get(BASE + '/?coupon=capped', allow_redirects=False)
check(cr.status_code == 302 and 'coupon=' not in cr.headers.get('Location', ''), '?coupon= link saves code and drops param', cr.headers.get('Location'))
check(cs.cookies.get('dm_coupon') == 'CAPPED', 'coupon cookie set from link', cs.cookies.get('dm_coupon'))

print('== Enquiry leads')
es = requests.Session()
sp = es.get(link(ids['sv']))
n = nonce_for(sp.text, 'enquiry')
er = es.post(link(ids['sv']), data={'dm_action': 'enquiry', '_dmnonce': n, 'product_id': ids['sv'], 'name': 'Test School', 'phone': '9876543210', 'email': 't@x.in', 'site_type': 'School / College website', 'budget': 'Not sure yet', 'message': 'Need 6 pages'}, headers={'Referer': link(ids['sv'])})
check(er.status_code == 200 and 'Your enquiry is saved' in er.text, 'enquiry submitted with thank-you', er.status_code)
check('Continue on WhatsApp' in er.text and 'wa.me/' in er.text, 'thank-you offers WhatsApp continue')
check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("leads")." WHERE name=\\"Test School\\"");') == '1', 'lead stored')
sp = es.get(link(ids['sv']))
n = nonce_for(sp.text, 'enquiry')
es.post(link(ids['sv']), data={'dm_action': 'enquiry', '_dmnonce': n, 'name': 'Spam Bot', 'phone': '9876543210', 'website': 'http://spam'}, headers={'Referer': link(ids['sv'])})
check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("leads")." WHERE name=\\"Spam Bot\\"");') == '0', 'honeypot blocks spam lead')

print('== Verified client reviews')
tok = wp(r'''global $wpdb; $t=strtolower(wp_generate_password(32,false,false)); $wpdb->insert(dm_table("review_requests"),array("token"=>$t,"product_id"=>%d,"client_name"=>"Sunrise School","created_at"=>dm_now(),"expires_at"=>gmdate("Y-m-d H:i:s", current_time("timestamp")+86400))); echo $t;''' % ids['sv'])
rs = requests.Session()
rp = rs.get(BASE + '/review/%s/' % tok)
check('Rate our work' in rp.text and 'noindex' in rp.text, 'review link page opens (noindex)')
n = nonce_for(rp.text, 'client_review')
rr = rs.post(BASE + '/review/%s/' % tok, data={'dm_action': 'client_review', '_dmnonce': n, 'token': tok, 'rating': '5', 'display_name': 'Sunrise School', 'title': 'Great work', 'comment': 'Loved it'})
check('Verified client' in rr.text and 'Great work' in rr.text, 'client review shows with Verified client badge')
check(wp('echo get_post_meta(%d,"_dm_rating_count",true);' % ids['sv']) == '1', 'service rating aggregated')
again = rs.get(BASE + '/review/%s/' % tok)
check('expired' in again.text, 'review link is single-use')

print('== Articles')
aid = wp('echo wp_insert_post(array("post_type"=>"post","post_status"=>"publish","post_title"=>"Guide V2","post_name"=>"guide-v2","post_content"=>"<h2>Part one</h2><p>x</p><h2>Part two</h2><p>y</p><h3>Detail</h3><p>z</p>"));')
check(link(aid).endswith('/articles/guide-v2/'), 'article permalink under /articles/', link(aid))
a = get('/articles/guide-v2/')
check(a.status_code == 200 and 'Table of contents' in a.text and 'id="part-one"' in a.text, 'article page with TOC', a.status_code)
check('BlogPosting' in types(a.text), 'BlogPosting schema')
old = get('/guide-v2/', allow_redirects=False)
check(old.status_code == 301 and '/articles/guide-v2/' in old.headers.get('Location', ''), 'old post URL 301s to /articles/', (old.status_code, old.headers.get('Location')))
al = get('/articles/')
check('Guide V2' in al.text and 'dm-arow' in al.text, '/articles/ list view shows article')
home = get('/')
check('Guide V2' not in home.text, 'articles not on homepage')

print('== Redirects & 404s')
wp('wp_update_post(array("ID"=>%d,"post_status"=>"dm_unpublished"));' % ids['d2'])
gone = get('/product/maths-notes-v2/', allow_redirects=False)
check(gone.status_code == 301 and 'web-services-v2' in gone.headers.get('Location', ''), 'unpublished product 301s to its category', (gone.status_code, gone.headers.get('Location')))
get('/totally-missing-page-xyz/')
check(wp('global $wpdb; echo $wpdb->get_var("SELECT hits FROM ".dm_table("not_found")." WHERE path=\\"/totally-missing-page-xyz/\\"");') == '1', '404 logged')
wp('global $wpdb; $wpdb->insert(dm_table("redirects"), array("source"=>"/old-offer/","target"=>"/sale/","created_at"=>dm_now()));')
rd = get('/old-offer/', allow_redirects=False)
check(rd.status_code == 301 and rd.headers.get('Location', '').endswith('/sale/'), 'custom 301 redirect', (rd.status_code, rd.headers.get('Location')))

print('== Search suggestions, banners, blocks')
sg = get('/wp-admin/admin-ajax.php?action=dm_suggest&q=physics').json()
check(sg['success'] and any('Physics Notes V2' in i['title'] for i in sg['data']['items']), 'search suggestions return products', sg)
bid = wp(r'''$img = wp_insert_attachment(array("post_mime_type"=>"image/webp","post_title"=>"b","post_status"=>"inherit"), DM_DIR."/assets/demo/hero-1-dussehra-sale.webp");
$b1 = wp_insert_post(array("post_type"=>"dm_banner","post_status"=>"publish","post_title"=>"Live V2")); set_post_thumbnail($b1,$img); update_post_meta($b1,"_dm_b_placement","hero"); update_post_meta($b1,"_dm_b_alt","LIVE BANNER ALT");
$b2 = wp_insert_post(array("post_type"=>"dm_banner","post_status"=>"publish","post_title"=>"Future V2")); set_post_thumbnail($b2,$img); update_post_meta($b2,"_dm_b_placement","hero"); update_post_meta($b2,"_dm_b_alt","FUTURE BANNER ALT"); update_post_meta($b2,"_dm_b_start", wp_date("Y-m-d\TH:i", time()+86400));
echo $b1;''')
h = get('/').text
check('LIVE BANNER ALT' in h and 'FUTURE BANNER ALT' not in h, 'scheduled banner hidden until start')
check('fetchpriority="high"' in h, 'first hero banner is high priority (LCP)')
wp('$s=get_option("dm_store",array()); $b=dm_store()["blocks"]; $b["hero"]["on"]=0; $s["blocks"]=$b; update_option("dm_store",$s);')
check('LIVE BANNER ALT' not in get('/').text, 'homepage block can be switched off')
wp('$s=get_option("dm_store",array()); $b=dm_store()["blocks"]; $b["hero"]["on"]=1; $s["blocks"]=$b; update_option("dm_store",$s);')

print('== Front editor listing types')
wp('$u=get_user_by("login","admin"); update_user_meta($u->ID,"dm_seller_status","active");')
ad = requests.Session()
ad.get(BASE + '/wp-login.php')
ad.post(BASE + '/wp-login.php', data={'log': 'admin', 'pwd': 'admin123', 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
ed = ad.get(BASE + '/dashboard/edit/')
n = nonce_for(ed.text, 'seller_product_save')
check(n is not None, 'front editor reachable for owner')
if n:
    r = ad.post(BASE + '/dashboard/edit/', data={'dm_action': 'seller_product_save', '_dmnonce': n, 'product_id': '0', 'title': 'Aff Missing Url', 'price': '10', 'category': ids['cat'], 'status': 'publish', 'listing_type': 'affiliate', 'aff_url': ''})
    check('valid affiliate link' in r.text, 'affiliate requires a link', r.url)
    ed = ad.get(BASE + '/dashboard/edit/')
    n = nonce_for(ed.text, 'seller_product_save')
    png = open('/home/user/Android-App-Development/digimarket/screenshot.png', 'rb').read()
    r = ad.post(BASE + '/dashboard/edit/', data={'dm_action': 'seller_product_save', '_dmnonce': n, 'product_id': '0', 'title': 'Partner Tool', 'price': '99', 'category': ids['cat2'], 'status': 'publish', 'listing_type': 'affiliate', 'aff_url': 'https://tool.example.com/?r=1', 'aff_suffix': '/month', 'what_you_get': 'x'}, files=[('thumbnail', ('t.png', png, 'image/png'))] + [('gallery[]', ('g%d.png' % i, png, 'image/png')) for i in range(3)])
    m = re.search(r'/dashboard/edit/(\d+)/', r.url)
    pid = m.group(1) if m else '0'
    check(wp('echo get_post_status(%s)."|".get_post_meta(%s,"_dm_affiliate",true)."|".get_post_meta(%s,"_dm_aff_slug",true);' % (pid, pid, pid)) == 'publish|1|partner-tool', 'affiliate published with auto short link', r.url)
    check(wp('echo get_post_meta(%s,"_dm_meta_title",true);' % pid).startswith('Partner Tool'), 'SEO title auto-filled on publish')

print('== Landing pages')
lp = json.loads(wp(r"""
$t = wp_insert_term("WordPress Themes V2","dm_category"); $t = $t["term_id"];
$id = wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"Temple Theme V2","post_author"=>1,"post_content"=>"<p>Theme body.</p>","post_excerpt"=>"A lovely theme"));
wp_set_object_terms($id,array($t),"dm_category"); wp_set_object_terms($id,array("Temple","Events"),"dm_tag");
update_post_meta($id,"_dm_price",1999); update_post_meta($id,"_dm_sale_price",999); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin");
update_post_meta($id,"_dm_demo_url","https://demo.example.com/temple"); dm_sync_effective_price($id);
$id2 = wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"Shop Theme V2","post_author"=>1,"post_content"=>"<p>x</p>"));
wp_set_object_terms($id2,array($t),"dm_category"); wp_set_object_terms($id2,array("Shop"),"dm_tag"); update_post_meta($id2,"_dm_price",1499); update_post_meta($id2,"_dm_delivery","file"); update_post_meta($id2,"_dm_file","x.bin"); dm_sync_effective_price($id2);
echo json_encode(array("thm"=>$id,"thm2"=>$id2,"tcat"=>$t));"""))
h = get('/').text
check('class="dm-show-card is-svc"' in h and '/website-services/' in h, 'homepage shows Website services card')
check('class="dm-show-card is-thm"' in h and '/wordpress-themes/' in h, 'homepage shows WordPress themes card')
check('>Website Services<' in h, 'header menu links to website services')
r = get('/website-services/')
t = r.text
check(r.status_code == 200 and 'School Site V2' in t, 'services landing lists service', r.status_code)
check('Physics Notes V2' not in t and 'Temple Theme V2' not in t, 'services landing shows only websites')
check('Order now' in t and 'wa.me/919000000001' in t, 'Order now opens WhatsApp')
rob = re.search(r'<meta name=.robots.[^>]*>', t)
check(not rob or 'noindex' not in rob.group(0), 'services landing indexable')
check('rel="canonical" href="%s/website-services/"' % BASE in t, 'services landing canonical')
check('ItemList' in json.dumps(ld(t)) and 'FAQPage' in types(t), 'services landing schema (ItemList + FAQ)')
check('dm-lp-coupon' not in t, 'no coupon strip until a code is set')
wp('$s=get_option("dm_store",array()); $s["lp_svc_coupon"]="FIRSTSITE"; update_option("dm_store",$s);')
t = get('/website-services/').text
check('dm-lp-coupon' in t and 'FIRSTSITE' in t, 'services coupon strip shows code')
check(re.search(r'wa\.me/\d+\?text=[^"]*FIRSTSITE', t) is not None, 'coupon code goes into the WhatsApp order message')
check('Contact details' in t and 'name="dm_action" value="enquiry"' in t, 'contact details + enquiry form on services landing')
r = get('/wordpress-themes/')
t = r.text
check(r.status_code == 200 and 'Temple Theme V2' in t and 'Shop Theme V2' in t, 'themes landing lists themes', r.status_code)
grid = t.split('class="dm-lp-themes"')[1].split('</section>')[0] if 'class="dm-lp-themes"' in t else ''
check(grid and 'School Site V2' not in grid and 'Physics Notes V2' not in grid, 'themes grid excludes services and other products')
check('data-tag="temple"' in t and 'data-tags="events temple"' in t, 'tag chips + card tags for filtering')
check('https://demo.example.com/temple' in t and 'Live preview' in t, 'Live preview button uses demo URL')
check('value="cart_add"' in t and 'name="buy_now"' in t, 'Buy now posts to cart/checkout')
check('<del>' in t and '50% off' in t, 'theme sale price and discount shown')
check('"@type":"Product"' in json.dumps(ld(t), separators=(',', ':')), 'themes landing Product schema')
sm = get('/wp-sitemap-dmpages-1.xml').text
check('/wordpress-themes/' in sm and '/website-services/' in sm, 'both landings in sitemap')
wp('global $wpdb; $wpdb->insert(dm_table("coupons"),array("code"=>"THEME20","discount_type"=>"percent","discount_value"=>20,"active"=>1,"is_public"=>1,"created_at"=>dm_now()));')
t = get('/wordpress-themes/').text
check('THEME20' in t and 'Best price' in t, 'best public coupon shown on themes')
b = requests.Session()
page = b.get(BASE + '/wordpress-themes/').text
n = nonce_for(page, 'cart_add')
r = b.post(BASE + '/wordpress-themes/', data={'dm_action': 'cart_add', '_dmnonce': n, 'product_id': str(lp['thm']), 'buy_now': '1'}, allow_redirects=False)
loc = r.headers.get('Location', '')
check(r.status_code in (302, 303) and '/login/' in loc and 'checkout' in loc, 'guest Buy now -> login -> checkout', loc)
check('Temple Theme V2' in b.get(BASE + '/cart/').text, 'theme is in the cart after Buy now')
check('dm-demo-btn' in get('/product/temple-theme-v2/').text, 'product page shows Live preview')
wp('$s=get_option("dm_store",array()); $b=dm_store()["blocks"]; $b["landing"]["on"]=0; $s["blocks"]=$b; update_option("dm_store",$s);')
check('dm-show-card' not in get('/').text, 'landing cards block can be switched off')
wp('$s=get_option("dm_store",array()); $b=dm_store()["blocks"]; $b["landing"]["on"]=1; $s["blocks"]=$b; update_option("dm_store",$s);')

print('== Featured controls the services band')
sv = str(ids['sv'])
wp('update_post_meta(%s,"_dm_featured",0);' % sv)
check('class="dm-svcband"' not in get('/').text, 'services band hidden when no service is featured')
wp('update_post_meta(%s,"_dm_featured",1);' % sv)
check('class="dm-svcband"' in get('/').text, 'services band shows a featured service')
wp('update_post_meta(%s,"_dm_featured",0);' % sv)
check('class="dm-svcband"' not in get('/').text, 'unticking Featured removes the band')

print('== Legal pages & business details')
lp_urls = json.loads(wp('$o=array(); foreach(get_option("dm_legal_pages") as $k=>$id){ $o[$k]=get_permalink($id); } echo json_encode($o);'))
for k in ('terms', 'privacy', 'refund', 'delivery', 'contact', 'about'):
    r = get(lp_urls[k])
    check(r.status_code == 200 and 'contact@pikacart.in' in r.text and '[pikacart_' not in r.text, 'legal page %s live with business email' % k, r.status_code)
t = get(lp_urls['terms']).text
check('Bijaya Nanda' in t and '768032' in t and '48 hours' in t, 'terms name grievance officer, address and timelines')
check('Shipping &amp; Delivery' in get(lp_urls['delivery']).text or 'Shipping & Delivery' in get(lp_urls['delivery']).text, 'shipping & delivery policy page')
check('starter template' not in t, 'no starter-template text left')
h = get('/').text
check('Patharla, Bijepur, Bargarh, Odisha 768032' in h and 'mailto:contact@pikacart.in' in h, 'footer shows business address + email')
check('"postalCode":"768032"' in h, 'organisation schema has postal address')
check(wp('echo get_option("wp_page_for_privacy_policy") == get_option("dm_legal_pages")["privacy"] ? "y" : "n";') == 'y', 'WordPress privacy page points to our policy')

print('== GST switch, receipts, seller GST control, welcome email')
def admin_do(page, do, data):
    t = ad.get(BASE + page).text
    m = re.search(r'name="do" value="%s">\s*<input type="hidden" id="_wpnonce" name="_wpnonce" value="([^"]+)"' % do, t)
    if not m:
        return None
    d = {'action': 'dm_admin', 'do': do, '_wpnonce': m.group(1)}
    d.update(data)
    return ad.post(BASE + '/wp-admin/admin-post.php', data=d, headers={'Referer': BASE + page})
g = ad.get(BASE + '/wp-admin/admin.php?page=dm-gst').text
check('GST is OFF' in g and 'Turn GST ON' in g, 'GST page shows the OFF switch')
check('GST is OFF' in ad.get(BASE + '/wp-admin/admin.php?page=dm-marketplace').text, 'GST switch card on admin Overview')
r = admin_do('/wp-admin/admin.php?page=dm-gst', 'gst_toggle', {'to': '1'})
check(r is not None and wp('echo dm_gst_enabled() ? "on" : "off";') == 'off', 'cannot turn GST on without a GSTIN')
admin_do('/wp-admin/admin.php?page=dm-gst', 'gst_save', {'g[gstin]': 'BADGSTIN', 'g[rate]': '18', 'g[state]': 'Odisha', 'g[threshold]': '2000000', 'g[warn_pct]': '80'})
check(wp('echo dm_store_gstin();') == '', 'invalid GSTIN rejected')
admin_do('/wp-admin/admin.php?page=dm-gst', 'gst_save', {'g[gstin]': '21abcde1234f1z5', 'g[rate]': '18', 'g[state]': 'Odisha', 'g[threshold]': '2000000', 'g[warn_pct]': '80'})
check(wp('echo dm_store_gstin();') == '21ABCDE1234F1Z5', 'valid GSTIN saved (uppercased)')
oid = wp(r'''global $wpdb; $u=get_user_by("login","admin"); $wpdb->insert(dm_table("orders"),array("buyer_id"=>$u->ID,"buyer_name"=>"Tax Buyer","buyer_email"=>"t@b.c","subtotal"=>118,"order_total"=>118,"payment_status"=>"paid","paid_at"=>dm_now(),"created_at"=>dm_now())); $o=$wpdb->insert_id; $wpdb->insert(dm_table("order_items"),array("order_id"=>$o,"product_id"=>0,"seller_id"=>$u->ID,"product_title"=>"Tax Test Item","list_price"=>118,"price_at_purchase"=>118,"item_status"=>"paid","created_at"=>dm_now())); echo $o;''')
t = ad.get(BASE + '/invoice/%s/' % oid).text
check('Receipt' in t and 'GST not applicable' in t, 'GST off: receipt says GST not applicable')
admin_do('/wp-admin/admin.php?page=dm-gst', 'gst_toggle', {'to': '1'})
check(wp('echo dm_gst_enabled() ? "on" : "off";') == 'on', 'GST switch turns ON')
t = ad.get(BASE + '/invoice/%s/' % oid).text
check('Tax Invoice' in t and '21ABCDE1234F1Z5' in t and 'CGST' in t and '100.00' in t and '9.00' in t, 'GST on: tax invoice with GSTIN and CGST/SGST split')
terms = get(json.loads(wp('$o=array(); foreach(get_option("dm_legal_pages") as $k=>$id){ $o[$k]=get_permalink($id); } echo json_encode($o);'))['terms']).text
check('Our GSTIN is 21ABCDE1234F1Z5' in terms, 'Terms follow the GST switch')
res = wp('$s=get_user_by("login","admin")->ID; $u=wp_create_user("commseller","commseller123","cs@example.com"); echo dm_commission_gst(6,$u)."|".dm_commission_gst(6,$s);')
check(res == '1.08|0', 'GST ON: 18% GST on 6% commission charged to sellers, never on own sales', res)
admin_do('/wp-admin/admin.php?page=dm-gst', 'gst_toggle', {'to': '0'})
check(wp('echo dm_commission_gst(6, get_user_by("login","commseller")->ID);') == '0', 'GST OFF: no GST on commission')
check(wp('echo dm_gst_enabled() ? "on" : "off";') == 'off', 'GST switch turns OFF')
res = wp(r'''$s=get_option("dm_settings"); $s["single_seller_mode"]=0; update_option("dm_settings",$s);
$u=wp_create_user("gstseller","gstseller123","gs@example.com"); update_user_meta($u,"dm_seller_status","active"); update_user_meta($u,"dm_shop_slug","gst-shop"); update_user_meta($u,"dm_shop_name","GST Shop"); update_user_meta($u,"dm_kyc_status","verified");
$p=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"GST Seller Item","post_author"=>$u)); update_post_meta($p,"_dm_price",100); update_post_meta($p,"_dm_delivery","file"); update_post_meta($p,"_dm_file","x.bin");
$a=dm_can_purchase($p)[0]?1:0; update_user_meta($u,"dm_gst_required",1); $b=dm_can_purchase($p)[0]?1:0; update_user_meta($u,"dm_gstin","21ABCDE1234F1Z5"); $c=dm_can_purchase($p)[0]?1:0;
echo $a.$b.$c;''')
check(res == '101', 'GST required without GSTIN pauses purchases; valid GSTIN reopens', res)
res = wp(r'''update_option("dm_gst", array_merge(dm_gst(), array("threshold"=>100))); delete_option("dm_gst_alert"); dm_gst_check_thresholds(0); echo get_option("dm_gst_alert");''')
check(res.endswith(':over'), 'turnover alert fires when the limit is crossed', res)
res = wp(r'''$u=get_user_by("login","gstseller"); update_user_meta($u->ID,"dm_agreement_accepted",array("time"=>time(),"version"=>"v1","ip"=>"1.2.3.4")); echo dm_email_seller_welcome($u->ID,"active") ? "sent" : "fail";''')
ml = open(S + '/mail.log').read() if os.path.exists(S + '/mail.log') else ''
check(res == 'sent' and 'seller registration successful' in ml, 'seller welcome email sent', res)
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=1; update_option("dm_settings",$s); update_option("dm_gst", array_merge(dm_gst(), array("threshold"=>2000000)));')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
