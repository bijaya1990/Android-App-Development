"""Seller applications: on/off button, big form with documents, batches, admin review and approval."""
import re, json, subprocess, os, requests, datetime

BASE = 'http://localhost:8899'
S = os.path.dirname(os.path.abspath(__file__))
WP = ['php', S + '/wp-cli.phar', '--allow-root', '--path=' + S + '/wordpress']
MAIL = S + '/mail.log'
FAIL, PASS = [], []
PNG = open('/home/user/Android-App-Development/digimarket/assets/img/email-logo.png', 'rb').read()
PDF = b'%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n'


def check(cond, name, extra=''):
    (PASS if cond else FAIL).append(name)
    print(('  PASS ' if cond else '  FAIL ') + name + (('  -> ' + str(extra)[:300]) if (not cond and extra) else ''))


def wp(code):
    r = subprocess.run(WP + ['eval', code], capture_output=True, text=True)
    return (r.stdout + r.stderr).strip()


def login(user, pw):
    s = requests.Session()
    s.get(BASE + '/wp-login.php')
    s.post(BASE + '/wp-login.php', data={'log': user, 'pwd': pw, 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
    return s


def admin_form(s, page, do, data):
    t = s.get(BASE + '/wp-admin/admin.php?page=' + page).text
    i = t.find('name="do" value="%s"' % do)
    n = re.search(r'name="_wpnonce" value="([^"]+)"', t[i:i + 600]).group(1)
    d = {'action': 'dm_admin', 'do': do, '_wpnonce': n, '_wp_http_referer': '/wp-admin/admin.php?page=' + page}
    d.update(data)
    return s.post(BASE + '/wp-admin/admin-post.php', data=d, headers={'Referer': BASE + '/wp-admin/admin.php?page=' + page})


def mails():
    return open(MAIL).read() if os.path.exists(MAIL) else ''


GOOD = {
    'full_name': 'Anita Sahu', 'email': 'anita@example.com', 'phone': '+91 98765 43210', 'whatsapp': '',
    'address': '12 Station Road', 'city': 'Bargarh', 'state': 'Odisha', 'pin': '768028',
    'shop_name': 'Anita Study Notes', 'category': 'Ebooks', 'about': 'Teacher with 8 years of experience writing notes.',
    'products': 'Class 10 physics notes, chemistry notes, maths formula book.', 'ready': '3-5', 'price_range': '₹49 – ₹299',
    'samples': 'https://drive.google.com/sample1\nnot-a-link', 'hosting': '', 'other_sites': 'Instagram',
    'seller_type': 'individual', 'business_name': '', 'pan': 'abcde1234f', 'gstin': '',
    'acct_name': 'Anita Sahu', 'bank_name': 'SBI', 'acct': '123456789012', 'acct2': '123456789012', 'ifsc': 'sbin0001234', 'upi': 'anita@oksbi',
    'own_rights': '1', 'correct': '1', 'terms': '1', 'ajax': '1',
}


def apply(data, files=None, sess=None):
    s = sess or requests.Session()
    t = s.get(BASE + '/').text
    m = re.search(r'name="dm_action" value="seller_apply">\s*<input type="hidden" id="_dmnonce" name="_dmnonce" value="([^"]+)"', t)
    d = {'dm_action': 'seller_apply', '_dmnonce': m.group(1) if m else ''}
    d.update(data)
    f = files if files is not None else {'pan_doc': ('pan.png', PNG, 'image/png'), 'bank_doc': ('cheque.pdf', PDF, 'application/pdf')}
    return s.post(BASE + '/', data=d, files=f, headers={'Referer': BASE + '/'}, allow_redirects=False)


print('== Button is OFF by default')
wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=1; $s["gateway"]="demo"; $s["commission_global"]=6; $s["commission_start_date"]=""; update_option("dm_settings",$s); delete_option("dm_apply");')
h = requests.get(BASE + '/').text
check('dm-apply-bar' not in h and 'data-dm-apply' not in h and 'dm-apply-modal' not in h, 'no apply bar, button or form while switched off')
r = apply(GOOD)
check(r.status_code in (302, 422) and wp('global $wpdb; echo (int)$wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("applications"));') == '0', 'submitting while closed is refused', r.status_code)

print('== Admin switches it ON')
ad = login('admin', 'admin123')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications').text
check('Seller applications' in pg and 'Applications are CLOSED' in pg, 'admin page shows closed state')
closes = (datetime.date.today() + datetime.timedelta(days=10)).isoformat()
r = admin_form(ad, 'dm-applications', 'apply_settings', {'enabled': '1', 'batch': '1', 'closes': closes, 'headline': 'Now accepting sellers', 'categories': 'Ebooks\nThemes, software & plugins\nCourses\nGraphics'})
check('switched+ON' in r.url or 'switched ON' in requests.utils.unquote(r.url).replace('+', ' '), 'switched ON', r.url)
h = requests.get(BASE + '/').text
check('dm-apply-bar' in h and 'Batch 1' in h and 'Apply now' in h, 'top bar shows batch and Apply now')
check('dm-apply-pill' in h and 'Sell on PikaCart' in h, 'header shows Sell on PikaCart pill')
check('Apply now for a seller account' in h and 'dm-ft-apply' in h, 'footer band with the professional Apply now for a seller account button')
check('id="dm-apply-modal"' in h and 'name="pan_doc"' in h and 'name="bank_doc"' in h and 'name="ifsc"' in h, 'pop-up form with PAN, bank and document fields')
check('Only 6% commission' in h and 'GST registration is needed only once your yearly sales cross ₹20 lakh' in h, 'form shows 6% commission and GST not compulsory below ₹20 lakh')
check('date of birth' not in h.lower() and 'name="age"' not in h, 'no age / date of birth asked')
check('data-autoopen' in requests.get(BASE + '/?apply-seller=1').text, '?apply-seller=1 link opens the form automatically')

print('== Validation')
bad = dict(GOOD, pan='12345', ifsc='XX', acct2='111', phone='12345', samples='nothing here', state='Nowhere')
r = apply(bad, files={})
e = r.json().get('errors', {})
check(r.status_code == 422 and all(k in e for k in ['pan', 'ifsc', 'acct2', 'phone', 'samples', 'state', 'pan_doc', 'bank_doc']), 'bad PAN / IFSC / account / phone / links / state / missing documents rejected', e)
r = apply(GOOD, files={'pan_doc': ('pan.pdf', b'hello I am not a pdf', 'application/pdf'), 'bank_doc': ('cheque.pdf', PDF, 'application/pdf')})
check(r.status_code == 422 and 'pan_doc' in r.json()['errors'], 'fake PDF document rejected', r.text[:200])
r = apply(dict(GOOD, gstin='21ZZZZZ9999Z1Z5'))
check(r.status_code == 422 and 'gstin' in r.json()['errors'], 'GSTIN of a different PAN rejected')
r = apply(dict(GOOD, website_url='http://spam'))
check(r.status_code == 422, 'honeypot blocks bots')
r = apply(dict(GOOD, own_rights=''))
check(r.status_code == 422 and 'declaration' in r.json()['errors'], 'declarations required')

print('== Valid application')
before = len(mails())
r = apply(GOOD)
j = r.json()
check(r.status_code == 200 and j.get('success') and j.get('ref') == 'PK-B1-0001', 'application accepted with reference PK-B1-0001', r.text[:200])
row = json.loads(wp('global $wpdb; echo wp_json_encode($wpdb->get_row("SELECT * FROM ".dm_table("applications")." WHERE id=1"));'))
check(row['batch'] == '1' and row['status'] == 'new' and row['phone'] == '9876543210' and row['category'] == 'Ebooks', 'saved in batch 1 as new', row)
check('ABCDE1234F' not in row['data'] and '123456789012' not in row['data'] and '"pan_last4":"234F"' in row['data'], 'PAN and account number encrypted at rest')
check('https:\\/\\/drive.google.com\\/sample1' in row['data'] and 'not-a-link' not in row['data'], 'only valid sample links kept')
docs = json.loads(row['docs'])
exists = wp('$d=dm_private_dir()."/applications/"; echo file_exists($d."%s")&&file_exists($d."%s")?"yes":"no";' % (docs['pan_doc']['file'], docs['bank_doc']['file']))
check(exists == 'yes' and docs['pan_doc']['type'] == 'image/png' and docs['bank_doc']['type'] == 'application/pdf', 'documents stored in the private folder', docs)
m = mails()[before:]
check('We received your seller application (PK-B1-0001)' in m and '••••••234F' in m and 'ABCDE1234F' not in m, 'applicant gets a confirmation without full PAN')
check('New seller application PK-B1-0001' in m, 'admin gets an email alert')
r = apply(GOOD)
check(r.status_code == 422 and 'already applied' in r.json()['errors'].get('email', ''), 'same email cannot apply twice in a batch')
r = apply(dict(GOOD, email='ravi2@example.com', full_name='Ravi Das', shop_name='Ravi Themes', category='Themes, software & plugins', ajax=''))
check(r.status_code == 302, 'form also works without JavaScript (redirect + message)')

print('== Admin list, sorting, filters, CSV')
wp('global $wpdb; $wpdb->update(dm_table("applications"), array("created_at"=>"2026-01-05 10:00:00"), array("id"=>2));')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications').text
check('Anita Sahu' in pg and 'Ravi Das' in pg and 'PK-B1-0001' in pg, 'both applications listed')
check(pg.find('Anita Sahu') < pg.find('Ravi Das'), 'newest first by default')
pa = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&order=asc').text
check(pa.find('Ravi Das') < pa.find('Anita Sahu'), 'oldest first (first come) sorting')
pd = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&from=2026-01-01&to=2026-01-31').text
check('Ravi Das' in pd and 'Anita Sahu' not in pd, 'date range filter')
pc = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&category=Ebooks').text
check('Anita Sahu' in pc and 'Ravi Das' not in pc, 'category filter')
check('Ebooks: 0 approved / 1 applied' in pg, 'per-category counts')
n = re.search(r'do=apply_csv[^"]*?_wpnonce=([a-f0-9]+)', pg.replace('&#038;', '&').replace('&amp;', '&')).group(1)
csv = ad.get(BASE + '/wp-admin/admin-post.php?action=dm_admin&do=apply_csv&batch=1&_wpnonce=' + n).text
check('PK-B1-0001' in csv and 'Anita Sahu' in csv and '••••••234F' in csv and 'ABCDE1234F' not in csv, 'CSV export with masked PAN / account')

print('== Detail, documents, review')
dt = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&app=1').text
check('ABCDE1234F' in dt and '123456789012' in dt and 'SBIN0001234' in dt, 'admin sees full PAN, account and IFSC to verify')
check('Quality check' in dt and 'Approve &amp; create seller' in dt, 'review rubric and approve button')
link = re.search(r'href="([^"]*do=apply_doc[^"]*doc=pan_doc[^"]*)"', dt).group(1).replace('&#038;', '&').replace('&amp;', '&')
d = ad.get(link if link.startswith('http') else BASE + link)
check(d.status_code == 200 and d.content == PNG and d.headers.get('Content-Type') == 'image/png', 'PAN document opens for admin')
b = requests.Session()
d2 = b.get(link if link.startswith('http') else BASE + link)
check(d2.status_code in (403, 302) or d2.content != PNG, 'document not available to visitors')
prot = wp('$d=dm_private_dir(); echo (file_exists($d."/.htaccess") && strpos(file_get_contents($d."/.htaccess"),"Require all denied")!==false && file_exists($d."/applications/index.php") && strlen(basename($d))>20)?"ok":"no";')
check(prot == 'ok' and len(docs['pan_doc']['file']) > 30, 'documents in a deny-all folder with random unguessable names', prot)
before = len(mails())
admin_form(ad, 'dm-applications&app=2', 'apply_review', {'app': '2', 'score': '5', 'status': 'waitlist', 'message': 'Themes category is full.', 'note': 'ok', 'notify': '1'})
r2 = json.loads(wp('global $wpdb; echo wp_json_encode($wpdb->get_row("SELECT status,score FROM ".dm_table("applications")." WHERE id=2"));'))
check(r2 == {'status': 'waitlist', 'score': '5'}, 'waitlist + score saved', r2)
check('seller waiting list' in mails()[before:] and 'Themes category is full.' in mails()[before:], 'applicant emailed about the waiting list')

print('== Approve -> seller account with payout details ready')
before = len(mails())
r = admin_form(ad, 'dm-applications&app=1', 'apply_approve', {'app': '1', 'commission': ''})
check('Approved' in requests.utils.unquote(r.url).replace('+', ' '), 'approved', r.url)
info = json.loads(wp('$u=get_user_by("email","anita@example.com"); echo wp_json_encode(array("id"=>$u->ID,"st"=>dm_seller_status($u->ID),"step"=>dm_onboarding_step($u->ID),"kyc"=>get_user_meta($u->ID,"dm_kyc_status",true),"l4"=>get_user_meta($u->ID,"dm_bank_last4",true),"ifsc"=>get_user_meta($u->ID,"dm_ifsc",true),"pan"=>dm_decrypt(get_user_meta($u->ID,"dm_pan",true)),"shop"=>dm_shop_name($u->ID)));'))
check(info['st'] == 'draft' and info['step'] == 4 and info['kyc'] == 'verified' and info['l4'] == '9012' and info['ifsc'] == 'SBIN0001234' and info['pan'] == 'ABCDE1234F' and info['shop'] == 'Anita Study Notes', 'seller created with verified payout details; only the agreement is left', info)
m = mails()[before:]
check('You are now a seller' in m and 'payout details from your application are already saved' in m and 'Set your password' in m, 'welcome email with set-password link')
wp('wp_set_password("anita12345", %d);' % info['id'])
sl = login('anita@example.com', 'anita12345')
t = sl.get(BASE + '/sell/').text
check('name="dm_action" value="seller_submit"' in t, 'seller lands on the agreement step')
n = re.findall(r'name="_dmnonce" value="([^"]+)"', t[:t.find('value="seller_submit"')])[-1]
sl.post(BASE + '/sell/', data={'dm_action': 'seller_submit', '_dmnonce': n, 'agree': '1', 'own_rights': '1'}, headers={'Referer': BASE + '/sell/'})
check(wp('echo dm_seller_status(%d);' % info['id']) == 'active', 'seller is live after accepting the agreement')
pg = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications').text
check('Ebooks: 1 approved / 1 applied' in pg, 'approved count updates')
h = sl.get(BASE + '/').text
check('dm-apply-pill' not in h, 'sellers do not see the apply button')

print('== New batch')
r = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications').text
link = re.search(r'href="([^"]*do=apply_new_batch[^"]*)"', r).group(1).replace('&#038;', '&').replace('&amp;', '&')
ad.get(link if link.startswith('http') else BASE + link)
check(wp('echo dm_apply_opt("batch");') == '2' and 'Batch 2' in requests.get(BASE + '/').text, 'Batch 2 started and shown on the site')
r = apply(dict(GOOD, email='new2@example.com', full_name='Batch Two', shop_name='B2 Shop'))
check(r.json().get('ref') == 'PK-B2-0003', 'new applications go into batch 2', r.text[:200])
p2 = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications').text
check('Batch Two' in p2 and 'Anita Sahu' not in p2, 'list defaults to the current batch')
p1 = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&batch=1').text
check('Anita Sahu' in p1 and 'Batch Two' not in p1, 'older batch still viewable')
pall = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&batch=all').text
check('Anita Sahu' in pall and 'Batch Two' in pall, 'all batches view')

print('== Shortcode, closing date, OFF switch')
pid = wp('echo wp_insert_post(array("post_type"=>"post","post_status"=>"publish","post_title"=>"Become a seller","post_content"=>"[pikacart_apply_button]"));')
ph = requests.get(BASE + '/?p=' + pid).text
check('dm-apply-sc' in ph and 'Apply now for a seller account' in ph, 'shortcode shows the button in an article')
wp('dm_apply_update(array("closes"=>"2020-01-01"));')
h = requests.get(BASE + '/').text
check('dm-apply-bar' not in h and 'dm-apply-modal' not in h, 'closing date passed -> button hidden automatically')
check('applications are closed' in requests.get(BASE + '/?p=' + pid).text.lower(), 'shortcode shows closed message')
wp('dm_apply_update(array("closes"=>""));')
r = admin_form(ad, 'dm-applications', 'apply_settings', {'enabled': '0', 'batch': '2', 'closes': '', 'headline': 'Now accepting sellers', 'categories': 'Ebooks'})
h = requests.get(BASE + '/').text
check('dm-apply-bar' not in h and 'data-dm-apply' not in h, 'switch OFF hides everything')

print('== Delete')
ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&app=3')
dt = ad.get(BASE + '/wp-admin/admin.php?page=dm-applications&app=3').text
link = re.search(r'href="([^"]*do=apply_delete[^"]*)"', dt).group(1).replace('&#038;', '&').replace('&amp;', '&')
d3 = json.loads(wp('echo wp_json_encode(dm_apply_get(3)->docs);'))
ad.get(link if link.startswith('http') else BASE + link)
gone = wp('echo dm_apply_get(3)?"row":"none"; echo file_exists(dm_private_dir()."/applications/%s")?" file":" nofile";' % d3['pan_doc']['file'])
check(gone == 'none nofile', 'application and its documents deleted', gone)

wp('$s=get_option("dm_settings"); $s["single_seller_mode"]=0; update_option("dm_settings",$s); delete_option("dm_apply");')
print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
