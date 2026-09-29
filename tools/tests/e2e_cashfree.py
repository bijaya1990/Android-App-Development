"""Cashfree gateway: checkout, return verification, signed webhooks, refunds, retry.
Needs mu-cashfree-mock.php in the test site's mu-plugins (fake sandbox API)."""
import re, json, subprocess, os, requests, hmac, hashlib, base64, time

BASE = 'http://localhost:8899'
S = os.path.dirname(os.path.abspath(__file__))
WP = ['php', S + '/wp-cli.phar', '--allow-root', '--path=' + S + '/wordpress']
FAIL, PASS = [], []
SECRET = 'cf_test_secret_value_123'


def check(cond, name, extra=''):
    (PASS if cond else FAIL).append(name)
    print(('  PASS ' if cond else '  FAIL ') + name + (('  -> ' + str(extra)[:300]) if (not cond and extra) else ''))


def wp(code):
    r = subprocess.run(WP + ['eval', code], capture_output=True, text=True)
    return (r.stdout + r.stderr).strip()


def nonce_for(text, action):
    idx = text.find('name="dm_action" value="%s"' % action)
    if idx < 0:
        return None
    m = re.findall(r'name="_dmnonce" value="([^"]+)"', text[max(0, idx - 800):idx])
    return m[-1] if m else None


def login(user, pw):
    s = requests.Session()
    s.get(BASE + '/wp-login.php')
    s.post(BASE + '/wp-login.php', data={'log': user, 'pwd': pw, 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
    return s


def form(s, page, action, data, **kw):
    t = s.get(BASE + page).text
    n = nonce_for(t, action)
    d = {'dm_action': action, '_dmnonce': n}
    d.update(data)
    return s.post(BASE + page, data=d, headers={'Referer': BASE + page}, **kw)


def webhook(payload, secret=SECRET, ts=None):
    raw = json.dumps(payload, separators=(',', ':'))
    ts = ts or str(int(time.time() * 1000))
    sig = base64.b64encode(hmac.new(secret.encode(), (ts + raw).encode(), hashlib.sha256).digest()).decode()
    return requests.post(BASE + '/wp-json/dm/v1/cashfree-webhook', data=raw, headers={'Content-Type': 'application/json', 'x-webhook-timestamp': ts, 'x-webhook-signature': sig})


def order(oid):
    return json.loads(wp('echo wp_json_encode(dm_get_order(%s));' % oid))


print('== Setup')
ids = json.loads(wp(r'''
$s=get_option("dm_settings"); $s["gateway"]="cashfree"; $s["cf_app_id"]="TEST_APP_ID"; $s["cf_secret"]="%s"; $s["cf_env"]="sandbox"; $s["single_seller_mode"]=1; $s["require_email_verify"]=0; update_option("dm_settings",$s);
delete_option("dm_cf_mock_log"); delete_option("dm_cf_mock_status"); delete_option("dm_cf_mock_amount_override");
$admin=get_user_by("login","admin")->ID; $out=array();
foreach(array("CF Notes A"=>100,"CF Notes B"=>50,"CF Notes C"=>70,"CF Notes D"=>40) as $t=>$p){ $id=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>$t,"post_author"=>$admin)); update_post_meta($id,"_dm_price",$p); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); dm_sync_effective_price($id); $out[]=$id; }
$u=wp_create_user("cfbuyer","cfbuyer123","cfbuyer@example.com"); update_user_meta($u,"dm_email_verified",1);
echo json_encode(array("p"=>$out,"u"=>$u));''' % SECRET).splitlines()[-1])
p = ids['p']
b = login('cfbuyer', 'cfbuyer123')

print('== Checkout with Cashfree')
form(b, '/product/cf-notes-a/', 'cart_add', {'product_id': p[0]})
co = b.get(BASE + '/checkout/').text
check('name="phone"' in co, 'checkout asks for a mobile number when none is saved')
r = form(b, '/checkout/', 'place_order', {'agree': '1', 'phone': '12345'})
check('valid 10-digit mobile' in r.text, 'invalid mobile number rejected')
r = form(b, '/checkout/', 'place_order', {'agree': '1', 'phone': '+91 98765 43210'})
m = re.search(r'/checkout/pay/(\d+)/', r.url)
check(m is not None, 'order placed, redirected to payment page', r.url)
oid = m.group(1) if m else '0'
log = json.loads(wp('echo wp_json_encode(get_option("dm_cf_mock_log"));'))
create = [x for x in log if x['method'] == 'POST' and x['path'] == '/orders']
check(create and create[-1]['body']['order_amount'] == 100 and create[-1]['body']['customer_details']['customer_phone'] == '9876543210', 'Cashfree order created with amount and 10-digit phone', create[-1]['body'] if create else log)
check(create and create[-1]['headers'].get('x-api-version') == '2025-01-01' and create[-1]['headers'].get('x-client-id') == 'TEST_APP_ID', 'API version and client id headers sent')
check(create and '/checkout/cf-return/%s/' % oid in create[-1]['body']['order_meta']['return_url'], 'return_url points back to the store')
pay = r.text
check('id="dm-cf-pay"' in pay and 'data-session="session_test_' in pay and 'data-mode="sandbox"' in pay, 'pay page shows Cashfree button with session')
check('sdk.cashfree.com/js/v3/cashfree.js' in pay and 'Cashfree Payments' in pay, 'Cashfree SDK loaded and gateway named')

print('== Return from Cashfree')
r = b.get(BASE + '/checkout/cf-return/%s/' % oid)
check('/checkout/pay/%s/' % oid in r.url and order(oid)['payment_status'] == 'pending', 'unpaid return keeps the order pending', r.url)
wp('update_option("dm_cf_mock_amount_override", 1);update_option("dm_cf_mock_status","PAID");')
b.get(BASE + '/checkout/cf-return/%s/' % oid)
check(order(oid)['payment_status'] == 'failed', 'amount mismatch is never fulfilled')
wp('delete_option("dm_cf_mock_amount_override");')
r = form(b, '/checkout/pay/%s/' % oid, 'retry_payment', {'order_id': oid})
o = order(oid)
check(o['payment_status'] == 'paid' and o['razorpay_payment_id'] == '555001', 'retry first re-checks Cashfree and fulfils a paid order', o)
check('/order-received/%s/' % oid in r.url, 'buyer lands on order confirmation', r.url)
check('CF Notes A' in b.get(BASE + '/account/purchases/').text, 'product appears in My Purchases')

print('== Webhooks')
wp('update_option("dm_cf_mock_status","ACTIVE");')
form(b, '/product/cf-notes-b/', 'cart_add', {'product_id': p[1]})
r = form(b, '/checkout/', 'place_order', {'agree': '1'})
oid2 = re.search(r'/checkout/pay/(\d+)/', r.url).group(1)
ref2 = order(oid2)['razorpay_order_id']
check(order(oid2)['pay_session'].startswith('session_test_'), 'saved phone reused; session stored')
bad = webhook({'type': 'PAYMENT_SUCCESS_WEBHOOK', 'data': {'order': {'order_id': ref2, 'order_amount': 50}, 'payment': {'cf_payment_id': 777, 'payment_status': 'SUCCESS', 'payment_amount': 50}}}, secret='wrong')
check(bad.status_code == 400 and order(oid2)['payment_status'] == 'pending', 'webhook with bad signature rejected')
good = {'type': 'PAYMENT_SUCCESS_WEBHOOK', 'data': {'order': {'order_id': ref2, 'order_amount': 50}, 'payment': {'cf_payment_id': 777, 'payment_status': 'SUCCESS', 'payment_amount': 50}}}
r = webhook(good, ts='1700000000000')
check(r.status_code == 200 and order(oid2)['payment_status'] == 'paid' and order(oid2)['razorpay_payment_id'] == '777', 'signed success webhook fulfils the order', r.text)
r = webhook(good, ts='1700000000000')
check(r.json().get('duplicate') is True, 'repeated webhook delivery ignored')
form(b, '/product/cf-notes-c/', 'cart_add', {'product_id': p[2]})
oid3 = re.search(r'/checkout/pay/(\d+)/', form(b, '/checkout/', 'place_order', {'agree': '1'}).url).group(1)
ref3 = order(oid3)['razorpay_order_id']
webhook({'type': 'PAYMENT_SUCCESS_WEBHOOK', 'data': {'order': {'order_id': ref3, 'order_amount': 1}, 'payment': {'cf_payment_id': 888, 'payment_status': 'SUCCESS', 'payment_amount': 1}}})
check(order(oid3)['payment_status'] == 'pending', 'webhook with wrong amount not fulfilled')
webhook({'type': 'PAYMENT_FAILED_WEBHOOK', 'data': {'order': {'order_id': ref3}, 'payment': {'payment_status': 'FAILED', 'payment_message': 'Bank declined'}}})
check(order(oid3)['payment_status'] == 'failed', 'failed webhook marks the order failed')
form(b, '/checkout/pay/%s/' % oid3, 'retry_payment', {'order_id': oid3})
o3 = order(oid3)
check(o3['payment_status'] == 'pending' and o3['razorpay_order_id'] != ref3 and o3['pay_session'], 'retry creates a fresh Cashfree order', o3)

print('== Refunds')
item = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM ".dm_table("order_items")." WHERE order_id=%s");' % oid2)
res = wp('$r=dm_refund_item(%s,"test"); echo is_wp_error($r)?$r->get_error_message():"ok";' % item)
log = json.loads(wp('echo wp_json_encode(get_option("dm_cf_mock_log"));'))
ref = [x for x in log if x['method'] == 'POST' and x['path'].endswith('/refunds')]
check(res == 'ok' and ref and ref[-1]['path'] == '/orders/%s/refunds' % ref2 and ref[-1]['body']['refund_id'] == 'dmref%s' % item and ref[-1]['body']['refund_amount'] == 50, 'refund sent to Cashfree for the right order and amount', (res, ref[-1] if ref else log))
check(wp('echo dm_get_order_item(%s)->item_status;' % item) == 'refunded', 'item marked refunded and access removed')
wp('$s=get_option("dm_settings"); $s["gateway"]="razorpay"; update_option("dm_settings",$s);')
item1 = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM ".dm_table("order_items")." WHERE order_id=%s");' % oid)
res = wp('$r=dm_refund_item(%s,"after switch"); echo is_wp_error($r)?$r->get_error_message():"ok";' % item1)
check(res == 'ok', 'Cashfree orders still refund after switching the gateway to Razorpay', res)
wp('$s=get_option("dm_settings"); $s["gateway"]="cashfree"; update_option("dm_settings",$s);')

print('== Admin settings')
ad = login('admin', 'admin123')
st = ad.get(BASE + '/wp-admin/admin.php?page=dm-settings').text
check('value="cashfree"' in st and 'TEST_APP_ID' in st and 'cashfree-webhook' in st, 'settings show Cashfree gateway, App ID and webhook URL')
check(SECRET not in st, 'saved Secret Key is never printed in the page')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
