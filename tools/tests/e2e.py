import re, sys, io, json, hmac, hashlib, subprocess, os, time
import requests, html as H

BASE = 'http://localhost:8899'
S = os.path.dirname(os.path.abspath(__file__))
WP = ['php', S + '/wp-cli.phar', '--allow-root', '--path=' + S + '/wordpress']
FAIL = []
PASS = []


def check(cond, name, extra=''):
    (PASS if cond else FAIL).append(name)
    print(('  PASS ' if cond else '  FAIL ') + name + (('  -> ' + str(extra)[:300]) if (not cond and extra) else ''))


def wp(code):
    return subprocess.run(WP + ['eval', code], capture_output=True, text=True).stdout.strip()


def nonce_for(html, action):
    idx = html.find('name="dm_action" value="%s"' % action)
    if idx < 0:
        return None
    chunk = html[max(0, idx - 800):idx]
    m = re.findall(r'name="_dmnonce" value="([^"]+)"', chunk)
    return m[-1] if m else None


def ajax_nonce(html):
    m = re.search(r'"nonce":"([a-f0-9]+)"', html)
    return m.group(1) if m else None


def flashes(html):
    return re.findall(r'<div class="dm-notice dm-notice-(\w+)" role="alert">([^<]*)<', html)


class Client:
    def __init__(self, name):
        self.s = requests.Session()
        self.name = name

    def get(self, path, **kw):
        return self.s.get(BASE + path if path.startswith('/') else path, **kw)

    def form(self, page, action, data=None, files=None, allow_redirects=True):
        r = self.get(page)
        n = nonce_for(r.text, action)
        if not n:
            raise Exception('No nonce for %s on %s' % (action, page))
        d = {'dm_action': action, '_dmnonce': n}
        d.update(data or {})
        url = page if page.startswith('http') else BASE + page
        return self.s.post(url, data=d, files=files, headers={'Referer': url}, allow_redirects=allow_redirects)

    def ajax(self, page, action, data):
        r = self.get(page)
        d = {'action': action, 'nonce': ajax_nonce(r.text)}
        d.update(data)
        return self.s.post(BASE + '/wp-admin/admin-ajax.php', data=d).json()


PNG = open(S + '/../../../../../home/user/Android-App-Development/digimarket/screenshot.png', 'rb').read() if False else open('/home/user/Android-App-Development/digimarket/screenshot.png', 'rb').read()


def photos(img, extra=None):
    return [('thumbnail', ('t.png', img, 'image/png'))] + [('gallery[]', ('g%d.png' % i, img, 'image/png')) for i in range(3)] + list((extra or {}).items())

print('== Seller onboarding')
seller = Client('seller')
r = seller.form('/register/?intent=seller', 'register', {'name': 'Sara Seller', 'email': 'sara@example.com', 'phone': '+919876543210', 'password': 'secretpass1', 'agree': '1', 'intent': 'seller'})
check(r.url.rstrip('/').endswith('/sell'), 'register seller redirects to /sell', r.url)
check('Set up your shop' in r.text, 'shop setup step shown')
res = seller.ajax('/sell/', 'dm_check_slug', {'slug': 'Creative Templates Hub'})
check(res['success'] and res['data']['available'] and res['data']['slug'] == 'creative-templates-hub', 'slug availability AJAX', res)
res = seller.ajax('/sell/', 'dm_check_slug', {'slug': 'admin'})
check(res['data']['available'] is False, 'reserved slug rejected')
r = seller.form('/sell/', 'seller_shop', {'shop_name': 'Creative Templates Hub', 'shop_slug': 'creative-templates-hub', 'shop_bio': 'Templates for creators', 'shop_category': ''},
                files={'shop_logo': ('logo.png', PNG, 'image/png'), 'shop_banner': ('banner.png', PNG, 'image/png')})
check('Payout details' in r.text, 'KYC step shown after shop', flashes(r.text))
r = seller.form('/sell/', 'seller_kyc', {'legal_name': 'Sara S', 'pan': 'abcde1234x', 'phone': '+919876543210', 'bank_account': '123', 'ifsc': 'X', 'business_type': 'individual'})
check(any('9–18' in m for t, m in flashes(r.text)), 'KYC validation errors', flashes(r.text))
r = seller.form('/sell/', 'seller_kyc', {'legal_name': 'Sara S', 'pan': 'ABCDE1234F', 'phone': '+919876543210', 'upi': 'sara@okhdfc', 'business_type': 'individual', 'addr_street': '1 MG Road', 'addr_city': 'Bengaluru', 'addr_state': 'Karnataka', 'addr_postal_code': '560001'})
check('bank account number and IFSC' in r.text, 'KYC needs a bank account (UPI alone is not enough)')
r = seller.form('/sell/', 'seller_kyc', {'legal_name': 'Sara S', 'pan': 'ABCDE1234F', 'phone': '+919876543210', 'bank_account': '123456789012', 'ifsc': 'HDFC0001234', 'business_type': 'individual'})
check('full address' in r.text and 'Choose your state' in r.text and 'PIN code' in r.text, 'KYC needs full address, state and PIN')
r = seller.form('/sell/', 'seller_kyc', {'legal_name': 'Sara S', 'pan': 'ABCDE1234F', 'phone': '+919876543210', 'bank_account': '123456789012', 'ifsc': 'HDFC0001234', 'business_type': 'individual', 'addr_street': '1 MG Road', 'addr_city': 'Bengaluru', 'addr_state': 'Karnataka', 'addr_postal_code': '560001'})
check('Review &amp; launch' in r.text or 'Review & launch' in r.text, 'review step shown', flashes(r.text))
pan_raw = wp('echo get_user_meta(get_user_by("email","sara@example.com")->ID,"dm_pan",true);')
check(pan_raw.startswith('s1:') and 'ABCDE' not in pan_raw, 'PAN encrypted at rest', pan_raw)
check(wp('echo dm_decrypt(get_user_meta(get_user_by("email","sara@example.com")->ID,"dm_pan",true));') == 'ABCDE1234F', 'PAN decrypts')
r = seller.form('/sell/', 'seller_submit', {'agree': '1', 'own_rights': '1'})
check('/dashboard' in r.url, 'launch redirects to dashboard', r.url)
check('Your shop is live' in r.text, 'auto-approved', flashes(r.text))
for tab in ['', 'products', 'orders', 'payouts', 'reviews', 'support', 'settings', 'edit']:
    rr = seller.get('/dashboard/%s/' % tab if tab else '/dashboard/')
    check(rr.status_code == 200 and 'dm-dash-main' in rr.text, 'dashboard tab ' + (tab or 'overview'), rr.status_code)
rr = seller.get('/store/creative-templates-hub/')
check(rr.status_code == 200 and 'Creative Templates Hub' in rr.text, 'public store page')

print('== Products')
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Notion Planner Pro', 'short_description': 'Plan your life', 'full_description': '<h2>Great</h2><p>Planner <script>alert(1)</script></p>', 'category': wp('echo get_term_by("name","Templates","dm_category")->term_id;'), 'tags': 'notion, planner', 'price': '499', 'sale_price': '299', 'delivery': 'file', 'download_limit': '3', 'access_days': '0', 'status': 'publish', 'meta_title': 'Planner SEO', 'meta_desc': 'desc'},
                files=photos(PNG, {'digital_file': ('planner.pdf', b'%PDF-1.4 test file content', 'application/pdf')}))
check('/dashboard/edit/' in r.url, 'product saved', flashes(r.text))
p1 = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
check(wp('echo get_post_status(%s);' % p1) == 'publish', 'product 1 published')
check('<script>' not in wp('echo get_post_field("post_content",%s);' % p1), 'rich text sanitised')
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Photo Editor License', 'category': wp('echo get_term_by("name","Software","dm_category")->term_id;'), 'price': '999', 'delivery': 'license_key', 'license_keys': 'KEY-AAA-111\nKEY-BBB-222\nKEY-CCC-333', 'status': 'publish'}, files=photos(PNG))
p2 = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
check(wp('echo get_post_status(%s);' % p2) == 'publish', 'license product published', flashes(r.text))
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Course Access', 'category': wp('echo get_term_by("name","Courses","dm_category")->term_id;'), 'price': '1500', 'delivery': 'external_link', 'external_url': 'https://example.com/course', 'status': 'publish', 'access_days': '30'}, files=photos(PNG))
p3 = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Free Icons', 'category': wp('echo get_term_by("name","Graphics","dm_category")->term_id;'), 'price': '0', 'delivery': 'file', 'status': 'publish'}, files=photos(PNG, {'digital_file': ('icons.zip', b'PK\x03\x04zip', 'application/zip')}))
p4 = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'One Photo Only', 'price': '10', 'delivery': 'file', 'status': 'publish'}, files={'thumbnail': ('t.png', PNG, 'image/png'), 'digital_file': ('x.pdf', b'%PDF-1.4 x', 'application/pdf')})
p_one = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
check(wp('echo get_post_status(%s);' % p_one) == 'draft' and any('4 photos' in m for t, m in flashes(r.text)), 'new product needs 4 photos to publish', flashes(r.text))
check('0 of 4 added' not in r.text and '1 of 4 added' in r.text and '4 required' in r.text, 'editor shows the photo counter')
pc = requests.get(BASE + '/?post_type=dm_product&s=Planner').text
check('data-flip' in pc and pc.count('dm-flip-page') >= 4 and 'data-src=' in pc, 'product card turns through 4 photos (extra pages lazy-loaded)')
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Missing file', 'price': '10', 'delivery': 'file', 'status': 'publish'})
p5 = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
check(wp('echo get_post_status(%s);' % p5) == 'draft', 'publish blocked without thumbnail/file', flashes(r.text))
r = seller.form('/dashboard/edit/', 'seller_product_save', {'product_id': '0', 'title': 'Bad file', 'price': '10', 'delivery': 'file', 'status': 'draft'}, files={'digital_file': ('evil.php', b'<?php echo 1;', 'text/plain')})
check(any('not allowed' in m for t, m in flashes(r.text)), 'php upload rejected', flashes(r.text))
r = seller.form('/dashboard/products/', 'seller_product_action', {'row': 'duplicate:%s' % p1})
check('(copy)' in r.text, 'duplicate product', r.url)
dup = re.search(r'/dashboard/edit/(\d+)/', r.url).group(1)
r = seller.form('/dashboard/products/', 'seller_product_action', {'row': 'delete:%s' % dup})
check(wp('echo get_post_status(%s);' % dup) == 'dm_deleted', 'soft delete')
r = seller.form('/dashboard/products/', 'seller_product_action', {'do': 'unpublish', 'ids[]': [p3]})
check(wp('echo get_post_status(%s);' % p3) == 'dm_unpublished', 'bulk unpublish')
r = seller.form('/dashboard/products/', 'seller_product_action', {'do': 'publish', 'ids[]': [p3, p5]})
check(wp('echo get_post_status(%s);' % p3) == 'publish' and wp('echo get_post_status(%s);' % p5) == 'draft', 'bulk publish respects requirements')
for path in ['/products/', '/products/?sort=price_asc', '/products/?pcat=templates', '/products/?min_price=100&max_price=400', '/?s=planner&post_type=dm_product', '/product-tag/notion/', '/category-products/templates/', '/']:
    rr = requests.get(BASE + path)
    check(rr.status_code == 200 and 'Notion Planner Pro' in rr.text, 'catalogue shows product: ' + path, rr.status_code)
rr = requests.get(BASE + '/products/?min_price=1000')
check('Notion Planner Pro' not in rr.text and 'Course Access' in rr.text, 'price filter works')
rr = requests.get(BASE + '/?s=Creative&post_type=dm_product')
check('Creative Templates Hub' in rr.text, 'search finds shops')
prod_url = wp('echo get_permalink(%s);' % p1)
rr = requests.get(prod_url)
check(rr.status_code == 200 and 'Buy now' in rr.text and 'Planner SEO' in rr.text and 'application/ld+json' in rr.text, 'product page with SEO + buy')
rr = requests.get(wp('echo get_permalink(%s);' % p5))
check(rr.status_code == 404, 'draft product hidden from public', rr.status_code)

print('== Buyer')
buyer = Client('buyer')
r = buyer.form('/register/', 'register', {'name': 'Bob Buyer', 'email': 'bob@example.com', 'password': 'buyerpass1', 'agree': '1'})
check('/account' in r.url, 'buyer registered', r.url)
res = buyer.ajax(prod_url, 'dm_cart_add', {'product_id': p1})
check(res['success'] and res['data']['count'] == 1, 'AJAX add to cart', res)
res = buyer.ajax(prod_url, 'dm_cart_add', {'product_id': p2})
res = buyer.ajax(prod_url, 'dm_cart_add', {'product_id': p3})
check(res['data']['count'] == 3, 'multi-seller cart count 3', res)
r = buyer.get('/cart/')
check('Notion Planner Pro' in r.text and 'Photo Editor License' in r.text, 'cart page lists items')
r = buyer.form('/checkout/', 'place_order', {'agree': '1'})
check(any('verify your email' in m for t, m in flashes(r.text)), 'unverified email blocked at checkout', flashes(r.text))
mail = open(S + '/mail.log').read()
link = re.findall(r'(http://localhost:8899/verify/\?u=\d+&amp;k=\w+|http://localhost:8899/verify/\?u=\d+&k=\w+)', mail)
bob_links = [l for l in link]
r = buyer.get(bob_links[-1].replace('&amp;', '&').replace('&#038;', '&'))
check('Email verified' in r.text, 'email verification link works', r.url)

print('== Admin setup')
admin = requests.Session()
admin.post(BASE + '/wp-login.php', data={'log': 'admin', 'pwd': 'admin123', 'rememberme': 'forever', 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
pages = ['dm-marketplace', 'dm-sellers', 'dm-buyers', 'dm-moderation', 'dm-transactions', 'dm-payouts', 'dm-reviews', 'dm-coupons', 'dm-tickets', 'dm-audit', 'dm-settings']
for pg in pages:
    rr = admin.get(BASE + '/wp-admin/admin.php?page=' + pg)
    check(rr.status_code == 200 and 'dm-admin' in rr.text and 'Fatal' not in rr.text, 'admin page ' + pg, rr.status_code)


def admin_post(page, do, data):
    rr = admin.get(BASE + '/wp-admin/admin.php?page=' + page)
    m = re.search(r'name="do" value="%s">.*?name="_wpnonce" value="([^"]+)"' % do, rr.text, re.S)
    d = {'action': 'dm_admin', 'do': do, '_wpnonce': m.group(1), '_wp_http_referer': '/wp-admin/admin.php?page=' + page}
    d.update(data)
    return admin.post(BASE + '/wp-admin/admin-post.php', data=d, headers={'Referer': BASE + '/wp-admin/admin.php?page=' + page})


r = admin_post('dm-coupons', 'coupon_create', {'code': 'SAVE10', 'discount_type': 'percent', 'discount_value': '10', 'seller_id': '0', 'min_order': '100', 'usage_limit': '5'})
check('Coupon created' in r.text or 'Coupon+created' in r.url, 'coupon created', r.url)

print('== Purchase')
r = buyer.form('/cart/', 'apply_coupon', {'coupon': 'save10'})
check('SAVE10' in r.text and 'Coupon applied' in r.text, 'coupon applied', flashes(r.text))
r = buyer.form('/checkout/', 'place_order', {'agree': '1'})
check('/checkout/pay/' in r.url and 'Complete demo payment' in r.text, 'order created -> pay page', r.url)
oid = re.search(r'/checkout/pay/(\d+)/', r.url).group(1)
total = wp('echo dm_get_order(%s)->order_total;' % oid)
check(abs(float(total) - (299 + 999 + 1500) * 0.9) < 0.02, 'coupon total correct', total)
r = buyer.form('/checkout/pay/%s/' % oid, 'demo_pay', {'order_id': oid, 'simulate_fail': '1'})
check('Retry payment' in r.text and wp('echo dm_get_order(%s)->payment_status;' % oid) == 'failed', 'failed payment shows retry')
r = buyer.form('/checkout/pay/%s/' % oid, 'retry_payment', {'order_id': oid})
r = buyer.form('/checkout/pay/%s/' % oid, 'demo_pay', {'order_id': oid})
check('/order-received/' in r.url and 'Payment successful' in r.text, 'demo payment success page', r.url)
check('KEY-AAA-111' in r.text, 'license key delivered on success page')
items = json.loads(wp('echo wp_json_encode(dm_get_order_items(%s));' % oid))
comm_ok = all(abs(float(i['commission_amount']) - round(float(i['price_at_purchase']) * 0.10, 2)) < 0.011 and abs(float(i['seller_net_amount']) + float(i['commission_amount']) - float(i['price_at_purchase'])) < 0.011 for i in items)
check(comm_ok, 'commission split 10% per item', [(i['price_at_purchase'], i['commission_amount'], i['seller_net_amount']) for i in items])
check(all(i['transfer_status'] == 'processed' for i in items), 'demo transfers processed')
check(wp('echo dm_get_order(%s)->payment_status;' % oid) == 'paid', 'order paid')
check(wp('global $wpdb; echo $wpdb->get_var("SELECT times_used FROM ".dm_table("coupons"));') == '1', 'coupon usage incremented')
check(buyer.get('/cart/').text.count('dm-cart-line') == 0, 'cart emptied after purchase')
# Idempotency
wp('dm_fulfill_order(%s, "dup");' % oid)
check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("payouts"));') == '3', 'fulfil is idempotent (3 payouts)')

print('== Downloads')
r = buyer.get('/account/purchases/')
dl = re.findall(r'href="([^"]*account/download/(\d+)/[^"]*)"', r.text)
check(len(dl) >= 2, 'purchases page has download buttons', len(dl))
file_item = [d for d in dl if wp('echo dm_get_order_item(%s)->product_id;' % d[1]) == p1][0]
r = buyer.get(file_item[0].replace('&amp;', '&').replace('&#038;', '&'), allow_redirects=False)
check(r.status_code == 302 and '/download/' in r.headers['Location'], 'download button -> signed link', r.status_code)
signed = r.headers['Location']
r2 = buyer.get(signed)
check(r2.status_code == 200 and r2.content == b'%PDF-1.4 test file content' and 'attachment' in r2.headers.get('Content-Disposition', ''), 'file streamed', r2.status_code)
r3 = requests.get(signed, allow_redirects=False)
check(r3.status_code == 302 and '/login/' in r3.headers['Location'], 'signed link requires same logged-in buyer')
tampered = signed[:-3] + 'abc/'
r4 = buyer.get(tampered)
check(r4.status_code == 403, 'tampered link rejected', r4.status_code)
ext_item = [d for d in dl if wp('echo dm_get_order_item(%s)->product_id;' % d[1]) == p3][0]
r = buyer.get(ext_item[0].replace('&amp;', '&').replace('&#038;', '&'), allow_redirects=False)
r = buyer.get(r.headers['Location'], allow_redirects=False)
check(r.status_code in (301, 302) and r.headers['Location'] == 'https://example.com/course', 'external link revealed after purchase', r.headers.get('Location'))
buyer.get(signed); buyer.get(dm := buyer.get(file_item[0].replace('&amp;', '&').replace('&#038;', '&'), allow_redirects=False).headers['Location'])
r = buyer.get(file_item[0].replace('&amp;', '&').replace('&#038;', '&'), allow_redirects=True)
check('download limit' in r.text, 'download limit enforced (3)', flashes(r.text))
priv = wp('echo dm_private_dir();')
check(os.path.exists(priv + '/.htaccess'), 'private dir protected by .htaccess')
rel = priv.split('/wp-content/')[1]
fname = wp('echo get_post_meta(%s,"_dm_file",true);' % p1)
r = requests.get(BASE + '/wp-content/' + rel + '/' + fname)
print('    (direct file URL on php -S returns %s; Apache/.htaccess denies it, name is random)' % r.status_code)

print('== Account pages')
for tab in ['purchases', 'orders', 'wishlist', 'following', 'reviews', 'notifications', 'support', 'profile']:
    rr = buyer.get('/account/%s/' % tab)
    check(rr.status_code == 200 and 'dm-dash-main' in rr.text, 'account tab ' + tab)
r = buyer.get('/invoice/%s/' % oid)
check(r.status_code == 200 and 'Receipt' in r.text and 'GST not applicable' in r.text and 'SAVE10' in r.text, 'invoice renders (GST off = Receipt)')
r = requests.get(BASE + '/invoice/%s/' % oid, allow_redirects=False)
check(r.status_code == 302, 'invoice requires login')
r = seller.get('/invoice/%s/?as=seller' % oid)
check(r.status_code == 200 and 'Receipt' in r.text, 'seller can download their own receipt', r.status_code)
wp('wp_create_user("stranger","stranger123","stranger@example.com");')
stranger = Client('stranger')
stranger.get('/wp-login.php')
stranger.s.post(BASE + '/wp-login.php', data={'log': 'stranger', 'pwd': 'stranger123', 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
r = stranger.get('/invoice/%s/' % oid)
check(r.status_code == 404, 'unrelated user cannot see invoice', r.status_code)
res = buyer.ajax('/account/', 'dm_wishlist', {'product_id': p4})
check(res['success'] and res['data']['on'], 'wishlist toggle')
check('Free Icons' in buyer.get('/account/wishlist/').text, 'wishlist page shows item')
sid = wp('echo get_user_by("email","sara@example.com")->ID;')
r = buyer.form('/store/creative-templates-hub/', 'follow', {'seller_id': sid})
check('Following' in r.text, 'follow shop')
r = buyer.form('/account/support/', 'open_ticket', {'order_id': oid, 'subject': 'Refund request', 'message': 'Please refund the course'})
check('Your request was sent' in r.text, 'support ticket opened')
r = buyer.form(prod_url, 'submit_review', {'product_id': p1, 'rating': '4', 'comment': 'Nice planner'})
check('Nice planner' in r.text and 'Verified buyer' in r.text, 'verified review posted')
check(wp('echo get_post_meta(%s,"_dm_rating_avg",true);' % p1) == '4', 'rating aggregate updated')
r = buyer.form(wp('echo get_permalink(%s);' % p4), 'cart_add', {'product_id': p4, 'buy_now': '1'})
r = buyer.form('/checkout/', 'place_order', {'agree': '1'})
check('/order-received/' in r.url, 'free product checkout skips payment', r.url)
check('You own this product' in buyer.get(prod_url).text, 'product page shows owned state')
res = buyer.ajax(prod_url, 'dm_cart_add', {'product_id': p1})
check(not res['success'] and 'already own' in res['data']['message'], 'cannot buy owned product again', res)
r = buyer.form('/account/profile/', 'update_profile', {'name': 'Bob B', 'email': 'bob@example.com', 'phone': '99999'})
check('Profile updated' in r.text, 'profile update')
r = buyer.form('/account/profile/', 'change_password', {'current_password': 'buyerpass1', 'new_password': 'buyerpass2', 'new_password2': 'buyerpass2'})
check('Password changed' in r.text, 'password change')
r = buyer.get('/account/export/?_wpnonce=bad')
r = buyer.s.get(re.search(r'href="([^"]*account/export/[^"]*)"', buyer.get('/account/profile/').text).group(1).replace('&amp;', '&').replace('&#038;', '&'))
check(r.headers.get('Content-Type', '').startswith('application/json') and 'orders' in r.json(), 'data export')

print('== Seller side after sale')
r = seller.get('/dashboard/')
check('Net earnings' in r.text and '<svg class="dm-chart"' in r.text, 'overview stats + chart')
r = seller.get('/dashboard/orders/')
check('Bob' in r.text, 'orders list shows buyer')
r = seller.s.get(re.search(r'href="([^"]*export=orders[^"]*)"', r.text).group(1).replace('&amp;', '&').replace('&#038;', '&'))
check('text/csv' in r.headers.get('Content-Type', '') and 'Notion Planner Pro' in r.text, 'orders CSV export')
rev_id = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM ".dm_table("reviews"));')
r = seller.form('/dashboard/reviews/', 'seller_review_reply', {'review_id': rev_id, 'reply': 'Thanks Bob!'})
check('Reply posted' in r.text, 'seller reply')
check('Thanks Bob!' in requests.get(prod_url).text, 'reply shown publicly')
r = seller.get('/dashboard/support/')
check('Refund request' in r.text, 'seller sees ticket')
course_item = ext_item[1]
r = seller.form('/dashboard/orders/?item=%s' % course_item, 'seller_refund', {'item_id': course_item, 'reason': 'Requested'})
check('Refund issued' in r.text, 'seller refund', flashes(r.text))
check(wp('echo dm_get_order_item(%s)->item_status;' % course_item) == 'refunded' and wp('echo dm_get_order(%s)->payment_status;' % oid) == 'partially_refunded', 'refund state updated')
fresh = re.findall(r'href="([^"]*account/download/%s/[^"]*)"' % course_item, buyer.get('/account/purchases/').text)
r = buyer.get(fresh[0].replace('&amp;', '&').replace('&#038;', '&')) if fresh else buyer.get('/account/purchases/')
check(('revoked' in r.text) or (not fresh and 'Refunded' in r.text), 'refunded item access revoked', flashes(r.text))
r = seller.get('/dashboard/edit/%s/' % p1)
r = seller.s.get(BASE + '/dashboard/file/%s/' % p1)
check(r.content == b'%PDF-1.4 test file content', 'seller can download own file')
r = buyer.get('/dashboard/')
check('/sell' in r.url, 'buyer cannot access seller dashboard')
r = buyer.form('/dashboard/edit/', 'seller_product_save', {'product_id': p1, 'title': 'hacked', 'price': '1'}) if nonce_for(buyer.get('/dashboard/edit/').text, 'seller_product_save') else None
check(wp('echo get_the_title(%s);' % p1) == 'Notion Planner Pro', 'buyer cannot edit seller product')

print('== Admin actions')
r = admin_post('dm-settings', 'save_settings', {'s[commission_global]': '12', 's[commission_start_date]': '2099-01-01', 's[gateway]': 'demo', 's[auto_approve_sellers]': '1', 's[require_email_verify]': '1', 's[sellers_can_refund]': '1', 's[link_expiry_minutes]': '30', 's[max_upload_mb]': '200', 's[currency_symbol]': '₹', 's[currency_code]': 'INR', 's[admin_idle_minutes]': '30', 's[login_max_attempts]': '5', 's[login_lockout_minutes]': '15', 's[refund_window_days]': '7', 's[allowed_extensions]': 'pdf,zip', 's[invoice_company]': 'Test Co', 's[order_prefix]': 'DM-'})
check('Settings saved' in r.text or 'Settings+saved' in r.url, 'settings saved', r.url)
check(wp('echo dm_commission_rate(%s,%s);' % (sid, p1)) == '0', 'launch promo = 0% before start date')
admin_post('dm-settings', 'save_settings', {'s[commission_global]': '12', 's[commission_start_date]': '', 's[gateway]': 'demo', 's[auto_approve_sellers]': '1', 's[require_email_verify]': '1', 's[sellers_can_refund]': '1', 's[allowed_extensions]': 'pdf,zip,png', 's[link_expiry_minutes]': '30', 's[max_upload_mb]': '200'})
check(wp('echo dm_commission_rate(%s,%s);' % (sid, p1)) == '12', 'global commission 12%')
tid = wp('echo get_term_by("name","Templates","dm_category")->term_id;')
admin_post('dm-settings', 'save_settings', {'s[commission_global]': '12', 's[gateway]': 'demo', 'cat_commission[%s]' % tid: '8', 's[allowed_extensions]': 'pdf,zip,png'})
check(wp('echo dm_commission_rate(%s,%s);' % (sid, p1)) == '8', 'category override 8%')
r = admin_post('dm-sellers&seller=%s' % sid, 'seller_update', {'uid': sid, 'status': 'active', 'commission': '5', 'kyc': 'verified', 'rzp_account': 'acc_TEST123', 'featured': '1'})
check(wp('echo dm_commission_rate(%s,%s);' % (sid, p1)) == '5', 'seller override 5%')
check(wp('echo wp_json_encode(dm_opt("featured_shops"));') == '[%s]' % sid, 'featured shop saved')
check(int(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("audit_logs")." WHERE action LIKE \'commission%\'");')) >= 2, 'commission changes audited')
old_item_rate = wp('echo dm_get_order_item(%s)->commission_percent_applied;' % items[0]['id'])
check(float(old_item_rate) == 10.0, 'past orders keep old rate')
r = admin.get(BASE + '/wp-admin/admin.php?page=dm-transactions&s=Notion')
check('Notion Planner Pro' in r.text, 'ledger search')
m = re.search(r'href="([^"]*do=export_ledger[^"]*)"', r.text)
rr = admin.get(m.group(1).replace('&amp;', '&').replace('&#038;', '&'))
check('text/csv' in rr.headers.get('Content-Type', ''), 'ledger CSV export')
m = re.search(r'href="([^"]*do=force_unpublish[^"]*pid=%s[^"]*)"' % p2, admin.get(BASE + '/wp-admin/admin.php?page=dm-moderation').text)
if not m:
    m = re.search(r'href="([^"]*do=force_unpublish&amp;pid=%s[^"]*)"' % p2, admin.get(BASE + '/wp-admin/admin.php?page=dm-moderation').text)
admin.get(m.group(1).replace('&amp;', '&').replace('&#038;', '&'), headers={'Referer': BASE + '/wp-admin/admin.php?page=dm-moderation'})
check(wp('echo get_post_status(%s);' % p2) == 'dm_unpublished', 'force unpublish')
r = seller.form('/dashboard/products/', 'seller_product_action', {'row': 'publish:%s' % p2})
check(wp('echo get_post_status(%s);' % p2) == 'dm_unpublished', 'seller cannot republish locked product')
r = admin.get(BASE + '/wp-admin/admin.php?page=dm-marketplace')
check('Commission earned' in r.text and 'Creative Templates Hub' in r.text, 'admin overview leaderboard')
m = re.search(r'href="([^"]*do=seller_status[^"]*to=suspended[^"]*)"', admin.get(BASE + '/wp-admin/admin.php?page=dm-sellers').text)
admin.get(m.group(1).replace('&amp;', '&').replace('&#038;', '&'), headers={'Referer': BASE + '/wp-admin/admin.php?page=dm-sellers'})
check(wp('echo get_post_status(%s);' % p1) == 'dm_unpublished' and requests.get(BASE + '/store/creative-templates-hub/').status_code == 404, 'suspend seller unpublishes products & hides shop')
m = re.search(r'href="([^"]*do=seller_status[^"]*to=active[^"]*)"', admin.get(BASE + '/wp-admin/admin.php?page=dm-sellers').text)
admin.get(m.group(1).replace('&amp;', '&').replace('&#038;', '&'), headers={'Referer': BASE + '/wp-admin/admin.php?page=dm-sellers'})
wp('wp_update_post(["ID"=>%s,"post_status"=>"publish"]);' % p1)

print('== Webhooks (Razorpay)')
wp('$s=get_option("dm_settings"); $s["rzp_webhook_secret"]="whsec"; $s["rzp_key_id"]="rzp_test_x"; $s["rzp_key_secret"]="sec"; update_option("dm_settings",$s);')
oid2 = wp('global $wpdb; $u=get_user_by("email","bob@example.com"); $wpdb->insert(dm_table("orders"),["buyer_id"=>$u->ID,"buyer_email"=>$u->user_email,"buyer_name"=>"Bob","subtotal"=>100,"order_total"=>100,"payment_status"=>"pending","gateway"=>"razorpay","razorpay_order_id"=>"order_T1","created_at"=>dm_now()]); $o=$wpdb->insert_id; $wpdb->insert(dm_table("order_items"),["order_id"=>$o,"product_id"=>%s,"seller_id"=>%s,"product_title"=>"X","list_price"=>100,"price_at_purchase"=>100,"commission_percent_applied"=>5,"commission_amount"=>5,"seller_net_amount"=>95,"item_status"=>"pending","transfer_status"=>"pending","created_at"=>dm_now()]); echo $o;' % (p4, sid))
body = json.dumps({'event': 'payment.captured', 'payload': {'payment': {'entity': {'id': 'pay_T1', 'order_id': 'order_T1', 'amount': 10000}}}})
bad = requests.post(BASE + '/wp-json/dm/v1/razorpay-webhook', data=body, headers={'Content-Type': 'application/json', 'X-Razorpay-Signature': 'bad'})
check(bad.status_code == 400, 'webhook bad signature rejected')
sig = hmac.new(b'whsec', body.encode(), hashlib.sha256).hexdigest()
ok = requests.post(BASE + '/wp-json/dm/v1/razorpay-webhook', data=body, headers={'Content-Type': 'application/json', 'X-Razorpay-Signature': sig, 'X-Razorpay-Event-Id': 'evt_1'})
check(ok.status_code == 200 and wp('echo dm_get_order(%s)->payment_status;' % oid2) == 'paid', 'webhook payment.captured marks paid', ok.text)
tstat = wp('$i=dm_get_order_items(%s); echo $i[0]->transfer_status." ".$i[0]->transfer_note;' % oid2)
print('    transfer after webhook (fake keys -> API error expected):', tstat)
check(tstat.startswith('failed'), 'transfer failure recorded for admin')
dup = requests.post(BASE + '/wp-json/dm/v1/razorpay-webhook', data=body, headers={'Content-Type': 'application/json', 'X-Razorpay-Signature': sig, 'X-Razorpay-Event-Id': 'evt_1'})
check(dup.json().get('duplicate') is True, 'duplicate webhook ignored')
wp('global $wpdb; $wpdb->update(dm_table("order_items"),["transfer_id"=>"trf_1","transfer_status"=>"pending"],["order_id"=>%s]);' % oid2)
body2 = json.dumps({'event': 'transfer.processed', 'payload': {'transfer': {'entity': {'id': 'trf_1'}}}})
requests.post(BASE + '/wp-json/dm/v1/razorpay-webhook', data=body2, headers={'Content-Type': 'application/json', 'X-Razorpay-Signature': hmac.new(b'whsec', body2.encode(), hashlib.sha256).hexdigest(), 'X-Razorpay-Event-Id': 'evt_2'})
check(wp('$i=dm_get_order_items(%s); echo $i[0]->transfer_status;' % oid2) == 'processed', 'transfer.processed webhook')
body3 = json.dumps({'event': 'refund.processed', 'payload': {'refund': {'entity': {'id': 'rfnd_1', 'payment_id': 'pay_T1', 'amount': 10000, 'notes': []}}}})
requests.post(BASE + '/wp-json/dm/v1/razorpay-webhook', data=body3, headers={'Content-Type': 'application/json', 'X-Razorpay-Signature': hmac.new(b'whsec', body3.encode(), hashlib.sha256).hexdigest(), 'X-Razorpay-Event-Id': 'evt_3'})
check(wp('echo dm_get_order(%s)->payment_status;' % oid2) == 'refunded', 'refund.processed webhook (full refund)')
wp('$s=get_option("dm_settings"); $s["gateway"]="demo"; update_option("dm_settings",$s);')

print('== Auth security')
lock = Client('lock')
for i in range(6):
    r = lock.form('/login/', 'login', {'log': 'sara@example.com', 'pwd': 'wrong'})
check(any('Too many failed attempts' in m for t, m in flashes(r.text)), 'login lockout after 5 failures', flashes(r.text))
r = lock.form('/forgot/', 'forgot', {'email': 'bob@example.com'})
check('reset link' in r.text, 'forgot password flow')
mail = open(S + '/mail.log').read()
rl = re.findall(r'(http://localhost:8899/reset/\?u=\d+(?:&amp;|&)k=\w+)', mail)[-1].replace('&amp;', '&').replace('&#038;', '&')
r = lock.get(rl)
check('Choose a new password' in r.text, 'reset link valid')
u = re.search(r'u=(\d+)', rl).group(1); k = re.search(r'k=(\w+)', rl).group(1)
r = lock.form(rl.replace(BASE, ''), 'reset', {'u': u, 'k': k, 'password': 'newpass123', 'password2': 'newpass123'})
check('Password updated' in r.text, 'password reset done')
b2 = Client('b2')
r = b2.form('/login/', 'login', {'log': 'bob@example.com', 'pwd': 'newpass123'})
check('/account' in r.url, 'login with new password', r.url)
r = requests.get(BASE + '/wp-admin/', allow_redirects=False)
r = b2.get('/wp-admin/', allow_redirects=False)
check(r.status_code == 302 and '/account' in r.headers['Location'], 'buyers kept out of wp-admin')
r = b2.get('/sell/')
check('Open your shop' in r.text or 'Set up your shop' in r.text, 'buyer can upgrade to seller (same account)')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
