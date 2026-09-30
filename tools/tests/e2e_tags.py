"""Tags hidden from visitors but still used for search, schema and related products."""
import json, subprocess, os, requests

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


ids = json.loads(wp(r'''
$admin=get_user_by("login","admin")->ID; $out=array();
foreach(array("Daily Study Planner"=>"zebraword, planner","Weekly Habit Sheet"=>"planner","Unrelated Poster"=>"") as $t=>$tags){ $id=wp_insert_post(array("post_type"=>"dm_product","post_status"=>"publish","post_title"=>$t,"post_content"=>"Plain description.","post_author"=>$admin)); update_post_meta($id,"_dm_price",99); update_post_meta($id,"_dm_delivery","file"); update_post_meta($id,"_dm_file","x.bin"); update_post_meta($id,"_dm_sales",0); dm_sync_effective_price($id); if($tags){ wp_set_object_terms($id, array_map("trim", explode(",", $tags)), "dm_tag"); } $out[]=$id; }
$s=get_option("dm_store",array()); unset($s["show_tags"]); update_option("dm_store",$s);
echo json_encode($out);''').splitlines()[-1])
url = wp('echo get_permalink(%d);' % ids[0])

print('== Hidden by default')
h = requests.get(url).text
check('#zebraword' not in h and '#planner' not in h, 'no #tag chips on the product page')
import re as _re
kw = _re.search(r'"keywords":"([^"]*)"', h)
check(kw and set(kw.group(1).split(', ')) == {'zebraword', 'planner'}, 'tags still in product schema (keywords)', kw.group(0) if kw else '')
sh = requests.get(BASE + '/products/').text
check('name="ptag"' not in sh, 'no Tags filter in the shop sidebar')

print('== Tags still work behind the scenes')
r = requests.get(BASE + '/', params={'s': 'zebraword', 'post_type': 'dm_product'}).text
check('Daily Study Planner' in r and 'Unrelated Poster' not in r, 'search finds a product by its hidden tag')
r2 = requests.get(BASE + '/', params={'s': 'Unrelated', 'post_type': 'dm_product'}).text
check('Unrelated Poster' in r2, 'normal title search still works')
check('Weekly Habit Sheet' in h.split('You may also like')[-1] if 'You may also like' in h else False, 'products sharing a tag shown in You may also like')

print('== Can be switched on')
wp('$s=get_option("dm_store",array()); $s["show_tags"]=1; update_option("dm_store",$s);')
h = requests.get(url).text
check('#zebraword' in h, 'Show product tags setting brings the chips back')
wp('$s=get_option("dm_store",array()); $s["show_tags"]=0; update_option("dm_store",$s);')

print('== wp-admin product editor: Product photos box')
import re, html as H
ad = requests.Session()
ad.get(BASE + '/wp-login.php')
ad.post(BASE + '/wp-login.php', data={'log': 'admin', 'pwd': 'admin123', 'wp-submit': 'Log In', 'testcookie': '1'}, cookies={'wordpress_test_cookie': 'WP Cookie check'})
imgs = json.loads(wp(r'''require_once ABSPATH."wp-admin/includes/image.php"; $out=array(); foreach(array(1,2,3,4) as $i){ $f=wp_upload_dir()["path"]."/t$i.png"; $im=imagecreatetruecolor(20,20); imagepng($im,$f); $id=wp_insert_attachment(array("post_mime_type"=>"image/png","post_title"=>"t$i","post_status"=>"inherit"),$f); wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$f)); $out[]=$id; } echo json_encode($out);''').splitlines()[-1])
pid = ids[2]
wp('set_post_thumbnail(%d, %d); delete_post_meta(%d, "_dm_gallery");' % (pid, imgs[0], pid))
ed = ad.get(BASE + '/wp-admin/post.php?post=%d&action=edit' % pid).text
check('Product photos (4 needed)' in ed and 'id="dm_gallery_ids"' in ed and '1 of 4 photos added' in ed and 'dm-gal-add' in ed, 'admin editor has the Product photos box with a counter')
form = ed[ed.find('<form name="post"'):]
form = form[:form.find('</form>')]
data = []
for m in re.finditer(r'<input([^>]*)>', form):
    a = m.group(1)
    n = re.search(r'name=["\']([^"\']+)["\']', a)
    if not n:
        continue
    t = re.search(r'type=["\']([^"\']+)["\']', a)
    t = t.group(1) if t else 'text'
    if t in ('checkbox', 'radio') and 'checked' not in a:
        continue
    if t in ('submit', 'button', 'file'):
        continue
    v = re.search(r'value=["\']([^"\']*)["\']', a)
    data.append((n.group(1), H.unescape(v.group(1)) if v else ''))
for m in re.finditer(r'<textarea[^>]*name="([^"]+)"[^>]*>(.*?)</textarea>', form, re.S):
    data.append((m.group(1), H.unescape(m.group(2))))
for m in re.finditer(r'<select[^>]*name="([^"]+)"[^>]*>(.*?)</select>', form, re.S):
    sel = re.search(r'<option[^>]*value="([^"]*)"[^>]*selected', m.group(2))
    data.append((m.group(1), sel.group(1) if sel else ''))
data = [(k, v) for k, v in data if k != 'dm_gallery_ids'] + [('dm_gallery_ids', '%d,%d,%d,999999' % (imgs[1], imgs[2], imgs[3])), ('save', 'Update')]
seen = set(); data = [(k, v) for k, v in data if not (k in ('_wpnonce', '_wp_http_referer', 'action') and (k in seen or seen.add(k)))]
r = ad.post(BASE + '/wp-admin/post.php', data=data, headers={'Referer': BASE + '/wp-admin/post.php?post=%d&action=edit' % pid})
gal = json.loads(wp('echo wp_json_encode(array_map("intval",(array)get_post_meta(%d,"_dm_gallery",true)));' % pid))
check(gal == imgs[1:], 'photos 2-4 saved from the admin box (invalid ids ignored)', gal)
check(wp('echo dm_product_photo_count(%d);' % pid) == '4', 'product now has 4 photos')
ed2 = ad.get(BASE + '/wp-admin/post.php?post=%d&action=edit' % pid).text
check('4 of 4 photos added' in ed2, 'counter shows 4 of 4 after saving')

print()
print('PASSED: %d  FAILED: %d' % (len(PASS), len(FAIL)))
for f in FAIL:
    print('  - ' + f)
