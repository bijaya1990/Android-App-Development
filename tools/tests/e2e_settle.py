"""Invited sellers in single-seller mode, weekly manual settlements and the receipt email."""
import re, json, subprocess, os, requests

BASE = 'http://localhost:8899'
S = os.path.dirname(os.path.abspath(__file__))
WP = ['php', S + '/wp-cli.phar', '--allow-root', '--path=' + S + '/wordpress']
MAIL = S + '/mail.log'
FAIL, PASS = [], []


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


def form(s, page, action, data):
    t = s.get(BASE + page).text
    d = {'dm_action': action, '_dmnonce': nonce_for(t, action)}
    d.update(data)
    return s.post(BASE + page, data=d, headers={'Referer': BASE + page})


def admin_form(s, page, do, data):
    t = s.get(BASE + '/wp-admin/admin.php?page=' + page).text
    i = t.find('name="do" value="%s"' % do)
    n = re.search(r'name="_wpnonce" value="([^"]+)"', t[i:i + 600]).group(1)
    d = {'action': 'dm_admin', 'do': do, '_wpnonce': n, '_wp_http_referer': '/wp-admin/admin.php?page=' + page}
    d.update(data)
    return s.post(BASE + '/wp-admin/admin-post.php', data=d, headers={'Referer': BASE + '/wp-admin/admin.php?page=' + page})


def mails():
    return open(MAIL).read() if os.path.exists(MAIL) else ''


def buy(b, slug, pid):
    form(b, '/product/%s/' % slug, 'cart_add', {'product_id': pid})
    r = form(b, '/checkout/', 'place_order', {'agree': '1'})
    oid = re.search(r'/checkout/pay/(\d+)/', r.url).group(1)
    form(b, '/checkout/pay/%s/' % oid, 'demo_pay', {'order_id': oid})
    return oid


print('== Setup: single seller mode, demo gateway')
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=1; $s["gateway"]="demo"; $s["require_email_verify"]=0; $s["commission_global"]=10; $s["commission_start_date"]=""; $s["payout_mode"]="auto"; $s["payout_tds_percent"]=0; update_option("dm_settings",$s);')
wp('$u=wp_create_user("stbuyer","stbuyer123","stbuyer@example.com"); update_user_meta($u,"dm_email_verified",1);')
ad = login('admin', 'admin123')
st = ad.get(BASE + '/wp-admin/admin.php?page=dm-sellers').text
check('Add a seller manually' in st and 'single-seller mode' in st, 'Sellers page is available in single-seller mode with an invite form')
check('page=dm-payouts' in ad.get(BASE + '/wp-admin/admin.php?page=dm-marketplace').text, 'Payouts menu visible in single-seller mode')

print('== Invite a seller')
before = len(mails())
r = admin_form(ad, 'dm-sellers', 'seller_invite', {'email': 'ravi@example.com', 'name': 'Ravi Kumar', 'shop': 'Ravi Notes', 'commission': '20'})
check('invite emailed' in requests.utils.unquote(r.url), 'invite accepted', r.url)
info = json.loads(wp('$u=get_user_by("email","ravi@example.com"); echo wp_json_encode(array("id"=>$u->ID,"st"=>dm_seller_status($u->ID),"slug"=>get_user_meta($u->ID,"dm_shop_slug",true),"c"=>get_user_meta($u->ID,"dm_commission_override",true),"step"=>dm_onboarding_step($u->ID)));'))
check(info['st'] == 'draft' and info['slug'] == 'ravi-notes' and float(info['c']) == 20 and info['step'] == 3, 'seller created as draft with shop and 20% commission, next step payout details', info)
m = mails()[before:]
check('You are now a seller' in m and '/reset/' in m and 'Set your password' in m, 'invite email with set-password link sent')
sid = info['id']
r = admin_form(ad, 'dm-sellers', 'seller_invite', {'email': 'ravi@example.com', 'name': 'Ravi', 'shop': 'Again'})
check('already a seller' in requests.utils.unquote(r.url), 'inviting the same person twice is refused')

print('== Public signup stays closed')
b = login('stbuyer', 'stbuyer123')
r = b.get(BASE + '/sell/', allow_redirects=False)
check(r.status_code == 302, 'a normal customer cannot open /sell/ in single-seller mode', r.status_code)
check(wp('echo dm_seller_status(get_user_by("login","stbuyer")->ID);') == '', 'customer did not become a seller')

print('== Invited seller finishes onboarding')
wp('wp_set_password("ravi12345", %d);' % sid)
sl = login(wp('echo get_userdata(%d)->user_login;' % sid), 'ravi12345')
t = sl.get(BASE + '/sell/').text
check(nonce_for(t, 'seller_kyc') is not None, 'invited seller sees the payout-details step')
r = form(sl, '/sell/', 'seller_kyc', {'legal_name': 'Ravi Kumar', 'pan': 'ABCDE1234F', 'phone': '+919876543210', 'bank_account': '123456789012', 'ifsc': 'HDFC0001234', 'upi': 'ravi@okhdfc', 'business_type': 'individual', 'addr_street': '1 Main Rd', 'addr_city': 'Bargarh', 'addr_state': 'Odisha', 'addr_postal_code': '768028'})
r = form(sl, '/sell/', 'seller_submit', {'agree': '1', 'own_rights': '1'})
check('/dashboard' in r.url and wp('echo dm_seller_status(%d);' % sid) == 'active', 'invited seller goes live straight after the agreement', r.url)
check('Seller dashboard' in sl.get(BASE + '/').text, 'account menu shows Seller dashboard in single-seller mode')

pid = wp('''$id=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"Ravi Physics Notes","post_author"=>%d)); update_post_meta($id,"_dm_price",200); update_post_meta($id,"_dm_sale_price",150); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); dm_sync_effective_price($id); echo $id;''' % sid)
pid2 = wp('''$id=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"Ravi Chem Notes","post_author"=>%d)); update_post_meta($id,"_dm_price",100); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); dm_sync_effective_price($id); echo $id;''' % sid)
pp = requests.get(BASE + '/product/ravi-physics-notes/').text
check('Sold by Ravi Notes' in pp and '/store/ravi-notes' not in pp, 'product page says Sold by the seller without a shop link')
check(wp('$r=dm_can_purchase(%s); echo $r[0]?"yes":$r[1];' % pid) == 'yes', 'manual payouts: seller can sell without a Razorpay linked account')

print('== Purchase + receipt email')
before = len(mails())
oid = buy(b, 'ravi-physics-notes', pid)
o = json.loads(wp('echo wp_json_encode(dm_get_order(%s));' % oid))
check(o['payment_status'] == 'paid', 'order paid', o)
it = json.loads(wp('global $wpdb; echo wp_json_encode($wpdb->get_row("SELECT i.*, p.status pst, p.amount pamt FROM ".dm_table("order_items")." i JOIN ".dm_table("payouts")." p ON p.order_item_id=i.id WHERE i.order_id=%s"));' % oid))
check(it['transfer_status'] == 'pending' and it['pst'] == 'pending' and float(it['commission_amount']) == 30 and float(it['seller_net_amount']) == 120, 'seller share 120 (150 − 20% commission) held for manual payout', it)
m = mails()[before:]
rc = m[m.find('Receipt for your order'):]
check('Receipt for your order' in m, 'receipt email sent with receipt subject')
check('assets/img/email-logo.png' in rc and '.svg' not in rc.split('</table>')[0], 'receipt header uses the PNG PikaCart logo')
check('Payment receipt' in rc and 'Ravi Physics Notes' in rc and 'Sold by Ravi Notes' in rc, 'receipt lists the item and who sold it')
check('Subtotal (MRP)' in rc and '−₹50.00' in rc and 'Total paid' in rc and '₹150.00' in rc, 'receipt shows MRP, discount and total paid', rc[:200])
check('Billed to' in rc and 'stbuyer@example.com' in rc and 'Receipt no.' in rc and 'Transaction ID' in rc, 'receipt shows billing, number and transaction')
check('You made a sale!' in m, 'seller gets a sale email')
oid2 = buy(b, 'ravi-chem-notes', pid2)

print('== Weekly settlement dashboard')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts').text
check('Weekly settlement' in pg and 'Ravi Notes' in pg, 'payouts page lists the seller in the unpaid view')
check('Mark ₹200.00 paid' in pg, 'due = 120 + 80 after commission', re.findall(r'Mark [^<]+paid', pg))
check('123456789012' in pg and 'HDFC0001234' in pg and 'ravi@okhdfc' in pg, 'full bank account, IFSC and UPI shown to pay the seller')
check('upi://pay?pa=ravi%40okhdfc' in pg and 'am=200.00' in pg, 'UPI app link prefilled with the amount')
check('Week-wise summary' in pg and 'Refund window open till' in pg, 'week-wise summary and refund-window warning shown')
wk = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts&week=' + wp('echo substr(dm_week_range(time())[0],0,10);')).text
check('Ravi Notes' in wk and '₹250.00' in wk, 'this week shows sales total 250', re.findall(r'₹[\d,.]+', wk)[:12])
last = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts&week=' + wp('echo substr(dm_week_range(time()-WEEK_IN_SECONDS)[0],0,10);')).text
check('No seller sales in this week' in last, 'last week is empty')
csv = ad.get(BASE + '/wp-admin/admin-post.php?action=dm_admin&do=settlement_csv&week=due&_wpnonce=' + re.search(r'do=settlement_csv&(?:#038;|amp;)?week=due&(?:#038;|amp;)?_wpnonce=([a-f0-9]+)', pg).group(1)).text
check('Ravi Notes' in csv and '123456789012' in csv and 'HDFC0001234' in csv, 'CSV export for bank upload', csv[:200])

print('== Admin: every seller wallet')
ov = ad.get(BASE + '/wp-admin/admin.php?page=dm-marketplace').text
check('Seller wallets' in ov and 'Total owed to sellers' in ov and 'Ravi Notes' in ov and '₹200.00' in ov, 'overview shows seller wallets and total owed')
wt = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts&view=wallets').text
check('Seller wallets' in wt and 'Ravi Notes' in wt and 'Pay now' in wt and '#seller-%d' % sid in wt, 'payouts has a Seller wallets tab with Pay now', re.findall(r'₹[\d,.]+', wt)[:8])
check('id="seller-%d"' % sid in ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts').text, 'Pay now jumps to the seller row in weekly settlement')

print('== TDS option')
wp('$s=get_option("dm_settings"); $s["payout_tds_percent"]=1; update_option("dm_settings",$s);')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts').text
check('Mark ₹197.50 paid' in pg and 'after ₹2.50 TDS' in pg, '1% TDS on 250 gross deducted from the due', re.findall(r'Mark [^<]+paid', pg))
wp('$s=get_option("dm_settings"); $s["payout_tds_percent"]=0; update_option("dm_settings",$s);')

print('== Mark paid')
before = len(mails())
r = admin_form(ad, 'dm-payouts', 'settle_seller', {'uid': sid, 'week': 'due', 'reference': '', 'method': 'bank'})
check('UTR' in requests.utils.unquote(r.url), 'reference is required')
r = admin_form(ad, 'dm-payouts', 'settle_seller', {'uid': sid, 'week': 'due', 'reference': 'UTR123456', 'method': 'bank'})
check('Marked ₹200 paid' in requests.utils.unquote(r.url).replace('+', ' ') or 'Marked' in requests.utils.unquote(r.url), 'marked as paid', r.url)
rows = json.loads(wp('global $wpdb; echo wp_json_encode($wpdb->get_results("SELECT status, reference FROM ".dm_table("payouts")." WHERE seller_id=%d"));' % sid))
check(all(x['status'] == 'settled' and x['reference'] == 'UTR123456' for x in rows) and len(rows) == 2, 'both payouts settled with the UTR', rows)
m = mails()[before:]
check('Payout sent: ₹200.00' in m and 'UTR123456' in m and 'Amount paid' in m, 'seller gets a payout statement email')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts').text
check('Nothing to pay' in pg, 'nothing left to pay')
sp = sl.get(BASE + '/dashboard/payouts/').text
check('UTR123456' in sp and 'bank transfer / UPI' in sp, 'seller sees the payout reference and manual-payout note')

wt = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts&view=wallets').text
check('UTR123456' in wt and 'Pay now' not in wt, 'admin wallet list shows 0 balance and last UTR after payment')

print('== Seller wallet')
ov = sl.get(BASE + '/dashboard/').text
check('Wallet balance' in ov and 'All settled' in ov and 'Platform commission' in ov, 'wallet on the seller overview shows 0 balance after payment')
check('Last payment received' in ov and 'UTR123456' in ov and '₹200.00' in ov, 'wallet shows the last payment and UTR')
check('Week-wise' in ov and 'Month-wise' in ov and 'Year-wise' in ov and 'Payments received' in ov, 'history filters and payments list shown')
mv = sl.get(BASE + '/dashboard/?wv=month').text
import datetime
check(datetime.date.today().strftime('%B %Y') in mv and '₹250.00' in mv, 'month-wise view shows this month sales', datetime.date.today().strftime('%B %Y'))
yv = sl.get(BASE + '/dashboard/payouts/?wv=year').text
check('Wallet balance' in yv and str(datetime.date.today().year) in yv and '₹200.00' in yv, 'year-wise view on the payouts tab')

print('== Refund after payout is recovered next time')
item = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM ".dm_table("order_items")." WHERE order_id=%s");' % oid2)
wp('dm_refund_item(%s,"test");' % item)
check(wp('global $wpdb; echo $wpdb->get_var("SELECT note FROM ".dm_table("payouts")." WHERE order_item_id=%s");' % item).startswith('Refunded after manual payout'), 'refund after payout flagged for recovery')
pid3 = wp('''$id=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>"Ravi Maths Notes","post_author"=>%d)); update_post_meta($id,"_dm_price",150); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); dm_sync_effective_price($id); echo $id;''' % sid)
buy(b, 'ravi-maths-notes', pid3)
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-payouts').text
wv = sl.get(BASE + '/dashboard/').text
check('₹40.00' in wv and 'waiting to be paid' in wv, 'wallet shows 40 pending before the payout')
check('Mark ₹40.00 paid' in pg and 'recovered' in pg, 'next due = 120 − 80 already paid for the refunded item', re.findall(r'Mark [^<]+paid', pg))
admin_form(ad, 'dm-payouts', 'settle_seller', {'uid': sid, 'week': 'due', 'reference': 'UTR2', 'method': 'upi'})
check(float(wp('echo dm_seller_recoverable(%d);' % sid)) == 0, 'recovered amount cleared after the next payout')
pre = sl.get(BASE + '/dashboard/').text
check('Wallet balance' in pre and 'UTR2' in pre, 'wallet updates after the second payout')

print('== Multi-vendor mode unchanged')
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=0; update_option("dm_settings",$s);')
check(wp('echo dm_manual_payouts("razorpay")?"manual":"auto";') == 'auto', 'multi-vendor + Razorpay keeps automatic Route split')
wp('$s=get_option("dm_settings"); $s["payout_mode"]="manual"; update_option("dm_settings",$s);')
check(wp('echo dm_manual_payouts("razorpay")?"manual":"auto";') == 'manual', 'manual payout setting works in multi-vendor too')
st = ad.get(BASE + '/wp-admin/admin.php?page=dm-settings').text
check('name="s[payout_mode]"' in st and 'payout_tds_percent' in st, 'settings show payout mode and TDS')
wp('$s=get_option("dm_settings"); $s["payout_mode"]="auto"; $s["single_seller_mode"]=0; update_option("dm_settings",$s);')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
