"""Marketplace (orange) homepage design: header, slider, categories, flash sale,
best sellers, promos, trust row, reviews, newsletter and footer."""
import re, json, subprocess, os, requests

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


wp('$s=get_option("dm_store",array()); unset($s["home_design"]); $s["social_links"]="https://facebook.com/pikacart\\nhttps://instagram.com/pikacart"; update_option("dm_store",$s);')
h = requests.get(BASE + '/').text

print('== Default design is the marketplace (orange) one')
check('dm-mk' in re.search(r'<body class="([^"]*)"', h).group(1), 'body has the marketplace design class')
check('class="mk-util"' in h and 'Instant download right after payment' in h, 'top utility bar')
check('pikacart-logo-orange.svg' in h, 'orange logo in the header')
check('All Categories' in h and 'mk-offers' in h and '>Offers<' in h, 'nav has All Categories and the red Offers pill')
check('>Account<' in h and '>Wishlist<' in h, 'header shows Account and Wishlist')

print('== Homepage sections')
check('class="mk-hero"' in h and 'mk-slide-h' in h and 'Shop Now' in h, 'full-width slider with headline and Shop Now')
check('Shop by Category' in h and h.count('class="mk-cat"') >= 3 and 'More Categories' in h, 'category circles with More Categories')
check('Flash Sale' in h and 'data-cd=' in h and 'data-cd-h' in h and 'View All Deals' in h, 'flash sale with hours/minutes/seconds countdown')
flash = h[h.find('mk-flash'):h.find('mk-best')]
check('% OFF' in flash and 'Add to Cart' in flash and 'mk-price' in flash, 'flash cards: % OFF badge, price and Add to Cart')
check('Best Sellers' in h and 'mk-grid6' in h and 'is-best' in h, 'best sellers grid with Bestseller badges')
check(h.count('class="mk-promo ') == 3 and 'Shop Now' in h, 'three promo cards')
check('Website' in h[h.find('mk-promo-row'):h.find('mk-trust')], 'promo cards include Website Services')
check('Instant Download' in h and 'Secure Payment' in h and 'Easy Refunds' in h and 'WhatsApp Support' in h, 'trust row with honest digital-store points')
check('Free Shipping' not in h, 'no physical-shipping claims')
check('What Our Customers Say' in h, 'customer reviews')

print('== Newsletter & footer')
check('Get Exclusive Offers' in h and 'name="dm_action" value="newsletter"' in h, 'newsletter band with form')
check('Shop By Category' in h and 'Customer Service' in h and 'My Account' in h, 'footer columns')
check('Download Our App' not in h and 'Contact Us' in h, 'no app column without app links (contact column instead)')
check('mk-soc' in h and 'aria-label="Facebook"' in h, 'coloured social icons')
wp('$s=get_option("dm_store",array()); $s["app_android"]="https://play.google.com/store/apps/details?id=in.pikacart"; update_option("dm_store",$s);')
h2 = requests.get(BASE + '/').text
check('Download Our App' in h2 and 'Google Play' in h2, 'app column appears once an app link is set')
s = requests.Session()
t = s.get(BASE + '/').text
n = re.search(r'value="newsletter"><input type="hidden" id="_dmnonce" name="_dmnonce" value="([^"]+)"', t)
r = s.post(BASE + '/', data={'dm_action': 'newsletter', '_dmnonce': n.group(1) if n else '', 'email': 'fan@example.com'}, headers={'Referer': BASE + '/'})
check('Thanks for subscribing' in r.text, 'subscribe shows a thank-you message')
check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("leads")." WHERE email=\'fan@example.com\' AND source=\'newsletter\'");') == '1', 'subscriber saved in Leads')
s.post(BASE + '/', data={'dm_action': 'newsletter', '_dmnonce': n.group(1) if n else '', 'email': 'fan@example.com'}, headers={'Referer': BASE + '/'})
check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".dm_table("leads")." WHERE email=\'fan@example.com\'");') == '1', 'same email not saved twice')

print('== Settings & classic switch')
wp('$s=get_option("dm_store",array()); $s["util_center"]="Diwali Mega Sale"; $s["hero_line1"]="Big Diwali"; update_option("dm_store",$s);')
h3 = requests.get(BASE + '/').text
check('Diwali Mega Sale' in h3 and 'Big Diwali' in h3, 'top bar and slider text editable')
ad = requests.Session()
ad.get(BASE + '/wp-login.php')
ad.post(BASE + '/wp-login.php', data={'log': 'admin', 'pwd': 'admin123', 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
st = ad.get(BASE + '/wp-admin/admin.php?page=dm-storefront').text
check('name="st[home_design]"' in st and 'Top bar' in st and 'Android app link' in st, 'Storefront settings has the design options')
wp('$s=get_option("dm_store",array()); $s["home_design"]="classic"; update_option("dm_store",$s);')
h4 = requests.get(BASE + '/').text
check('mk-hero' not in h4 and 'dm-mk' not in re.search(r'<body class="([^"]*)"', h4).group(1) and 'pikacart-logo.svg' in h4, 'classic design still available')
wp('$s=get_option("dm_store",array()); $s["home_design"]="market"; $s["app_android"]=""; update_option("dm_store",$s);')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
