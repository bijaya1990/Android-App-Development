import re, subprocess, os, requests

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


def nonce_for(html, action):
    idx = html.find('name="dm_action" value="%s"' % action)
    if idx < 0:
        return None
    chunk = html[max(0, idx - 800):idx]
    m = re.findall(r'name="_dmnonce" value="([^"]+)"', chunk)
    return m[-1] if m else None


class Client:
    def __init__(self):
        self.s = requests.Session()

    def get(self, path):
        return self.s.get(path if path.startswith('http') else BASE + path)

    def form(self, page, action, data=None, files=None):
        r = self.get(page)
        n = nonce_for(r.text, action)
        if not n:
            raise Exception('no nonce for %s on %s' % (action, page))
        d = {'dm_action': action, '_dmnonce': n}
        d.update(data or {})
        return self.s.post(BASE + page, data=d, files=files, headers={'Referer': BASE + page})


PNG = open('/home/user/Android-App-Development/digimarket/screenshot.png', 'rb').read()

print('== Baseline: multi-vendor visible by default')
r = requests.get(BASE + '/')
check('Become a seller' in r.text, 'seller CTA visible when single_seller_mode off')
r = requests.get(BASE + '/shops/')
check(r.status_code == 200, '/shops/ reachable when off', r.status_code)

print('== Turn on single seller mode')
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=1; $s["whatsapp_number"]="919776144085"; update_option("dm_settings",$s);')
check(wp('echo dm_single_seller_mode()?"1":"0";') == '1', 'setting saved')
r = requests.get(BASE + '/')
check('Become a seller' not in r.text and 'Start selling' not in r.text, 'seller CTAs hidden on homepage')
r = requests.get(BASE + '/shops/', allow_redirects=False)
check(r.status_code == 302, '/shops/ redirects away', r.status_code)
r = requests.get(BASE + '/sell/', allow_redirects=False)
check(r.status_code == 302, '/sell/ redirects away', r.status_code)
r = requests.get(BASE + '/store/creative-templates-hub/', allow_redirects=False)
check(r.status_code == 302, 'store page redirects away', r.status_code)
r = requests.get(BASE + '/')
check('All shops' not in r.text, 'footer All-shops link hidden')
check('Chat on WhatsApp' in r.text, 'whatsapp footer CTA shown instead')

print('== Admin sells own product: no Route needed')
admin_id = int(wp('$u=get_user_by("login","admin"); echo $u->ID;'))
check(admin_id > 0, 'resolved admin id', admin_id)
pid = wp('''
$u = get_user_by("login","admin");
$pid = wp_insert_post(["post_type"=>"dm_product","post_title"=>"Admin Direct Product","post_status"=>"publish","post_author"=>$u->ID]);
update_post_meta($pid,"_dm_price",50); update_post_meta($pid,"_dm_effective_price",50); update_post_meta($pid,"_dm_delivery","file");
update_post_meta($pid,"_dm_file","x.pdf.bin"); update_post_meta($pid,"_dm_file_name","x.pdf");
file_put_contents(dm_private_dir()."/x.pdf.bin","hello admin file");
echo $pid;
''')
oid = wp('''
global $wpdb;
$u = get_user_by("email","bob@example.com");
$wpdb->insert(dm_table("orders"),["buyer_id"=>$u->ID,"buyer_email"=>$u->user_email,"buyer_name"=>"Bob","subtotal"=>50,"order_total"=>50,"payment_status"=>"pending","gateway"=>"demo","created_at"=>dm_now()]);
$o=$wpdb->insert_id;
$wpdb->insert(dm_table("order_items"),["order_id"=>$o,"product_id"=>%s,"seller_id"=>%s,"product_title"=>"Admin Direct Product","list_price"=>50,"price_at_purchase"=>50,"commission_percent_applied"=>10,"commission_amount"=>5,"seller_net_amount"=>45,"item_status"=>"pending","transfer_status"=>"pending","created_at"=>dm_now()]);
echo $o;
''' % (pid, admin_id))
wp('dm_fulfill_order(%s, "demo_admin_test");' % oid)
tstat = wp('$i=dm_get_order_items(%s); echo $i[0]->transfer_status;' % oid)
check(tstat == 'not_required', 'admin-authored sale skips Route transfer entirely', tstat)
pstat = wp('global $wpdb; echo $wpdb->get_var("SELECT status FROM ".dm_table("payouts")." WHERE order_item_id = (SELECT id FROM ".dm_table("order_items")." WHERE order_id = %s)");' % oid)
check(pstat == 'settled', 'payout auto-settled for admin sale', pstat)

print('== Service product type')
cat = wp('echo get_term_by("name","Software","dm_category")->term_id;')
seller = Client()
r = seller.form('/login/', 'login', {'log': 'sara@example.com', 'pwd': 'secretpass1'})
check('/dashboard' in r.url, 'seller login ok', r.url)
r = seller.form('/dashboard/edit/', 'seller_product_save', {
    'product_id': '0', 'title': 'School Website Package', 'price': '5000', 'category': cat,
    'status': 'publish', 'delivery': 'file', 'service_mode': '1',
    'service_whatsapp': '919776144085', 'service_message': 'Hi, School Website enquiry',
}, files=[('thumbnail', ('t.png', PNG, 'image/png'))] + [('gallery[]', ('g%d.png' % i, PNG, 'image/png')) for i in range(3)])
m = re.search(r'/dashboard/edit/(\d+)/', r.url)
check(bool(m), 'service product created without needing a file', r.url)
spid = m.group(1) if m else None
if spid:
    check(wp('echo get_post_status(%s);' % spid) == 'publish', 'service product published despite no digital file')
    prod_url = wp('echo get_permalink(%s);' % spid)
    r = requests.get(prod_url)
    check('Starting from' in r.text, 'product page shows Starting-from price')
    check('Enquire on WhatsApp' in r.text, 'WhatsApp CTA shown')
    check('wa.me/919776144085' in r.text, 'whatsapp link uses set number')
    check('Buy now' not in r.text and 'Add to cart' not in r.text, 'buy/cart buttons hidden for service')
    buyer = Client()
    buyer.form('/login/', 'login', {'log': 'bob@example.com', 'pwd': 'buyerpass2'})
    rr = buyer.get(prod_url)
    nonce = re.search(r'"nonce":"([a-f0-9]+)"', rr.text).group(1)
    res = buyer.s.post(BASE + '/wp-admin/admin-ajax.php', data={'action': 'dm_cart_add', 'nonce': nonce, 'product_id': spid}).json()
    check((not res['success']) and 'WhatsApp' in res['data']['message'], 'cart_add rejects service product', res)

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
