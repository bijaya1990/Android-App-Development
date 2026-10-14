"""
Nuakhai Bhetghat merit certificate in Sambalpuri Odia (A4 landscape).
Run:  python3 make_nuakhai.py <output folder> [watermark image URL]
Changes per person: {{name}}, {{course}} (competition), {{grade}} (position),
{{cert_date}}; per project: {{session}} (year).
"""
import json, math, os, sys
from lib import Page, design, blank
from make_ganesh_odia import ganesha, GOLD, GOLD_T

RED, RED2, MAROON, BLACK, INK, PADDY, CREAM = '#C62828', '#E53935', '#7A0E12', '#161616', '#2B1308', '#C9A227', '#FFF8EA'
CX = 148.5


def ikat_band_h(p, x0, x1, y, c1, c2, step=7.0, r=1.6):
    n = int((x1 - x0) / step)
    for i in range(n + 1):
        p.ikat(x0 + i * step, y, r, 2, f=c1 if i % 2 == 0 else c2)


def ikat_band_v(p, y0, y1, x, c1, c2, step=7.0, r=1.6):
    n = int((y1 - y0) / step)
    for i in range(n + 1):
        p.ikat(x, y0 + i * step, r, 2, f=c1 if i % 2 == 0 else c2)


def sash(p, cx, cy, sx, sy):
    """Diagonal Sambalpuri sash across a corner, with ikat motifs and a square medallion."""
    a, b, w = 22, 58, 11
    pts = [(cx + sx * a, cy), (cx + sx * b, cy), (cx, cy + sy * b), (cx, cy + sy * a)]
    p.poly(pts, f=BLACK)
    inner = [(cx + sx * (a + 2.2), cy), (cx + sx * (b - 2.2), cy), (cx, cy + sy * (b - 2.2)), (cx, cy + sy * (a + 2.2))]
    p.poly(inner, f={'g': 'lin', 'a': 135, 's': [[0, MAROON], [0.5, RED2], [1, MAROON]]})
    m = (a + b) / 2.0
    for k in range(-3, 4):
        t = k * 6.2
        x = cx + sx * (m / 2 + t / 2)
        y = cy + sy * (m / 2 - t / 2)
        p.ikat(x, y, 2.0, 2, f='#FFFFFF' if k % 2 else '#FFD54F')
    # square ikat medallion in the corner
    q = cx + sx * 9.5, cy + sy * 9.5
    p.diamond(q[0], q[1], 9.2, 9.2, f=BLACK, s='#FFD54F', sw=0.4)
    p.diamond(q[0], q[1], 7.2, 7.2, f=RED)
    p.ikat(q[0], q[1], 5.0, 3, f='#FFFFFF')
    p.ikat(q[0], q[1], 2.6, 2, f=BLACK)
    p.circle(q[0], q[1], 0.9, f='#FFD54F')


def paddy(p, x, y, s=1.0, flip=1):
    """Sheaf of ripe paddy: curved stalks with grains."""
    for k, ang in enumerate((-30, -12, 6, 22)):
        a = math.radians(-90 + ang * flip)
        L = (20 - abs(ang) * 0.12) * s
        tx, ty = x + L * math.cos(a), y + L * math.sin(a)
        cx1, cy1 = x + L * 0.5 * math.cos(a) - flip * 2 * s, y + L * 0.5 * math.sin(a)
        p.path(min(x, tx, cx1) - 0.5, min(y, ty, cy1) - 0.5, abs(max(x, tx, cx1) - min(x, tx, cx1)) + 1, abs(max(y, ty, cy1) - min(y, ty, cy1)) + 1,
               [('M', x, y), ('Q', cx1, cy1, tx, ty)], close=False, s='#8D6E1F', sw=0.35)
        for g in range(6):
            t = 0.45 + g * 0.1
            gx, gy = x + L * t * math.cos(a), y + L * t * math.sin(a)
            for side in (-1, 1):
                p.leaf(gx, gy, 2.3 * s, 0.75 * s, math.degrees(a) + side * 32, f=PADDY if g % 2 else '#E0B43A')
        p.leaf(tx, ty, 2.2 * s, 0.7 * s, math.degrees(a), f='#E0B43A')
    for side in (-1, 1):
        p.leaf(x, y, 15 * s, 1.6 * s, -90 + side * 40, f='#558B2F')
    p.rect(x - 2.4 * s, y - 2.2 * s, 4.8 * s, 2.2 * s, r=0.5, f=RED)


def nuakhai(bg):
    """Photo-rich layout: the artwork is one 300 dpi image (made from
    nuakhai_bg.html); only the changing details are drawn on top."""
    p = Page(prefix='nk')
    p.rect(0, 0, 297, 210, f='#3A1606')
    p.img(0, 0, 297, 210, bg, fit='cover')
    # logo + Ganesha in the two round badges
    p.img(23.5, 17.5, 19, 19, '{{logo}}', fit='contain', shape='circle')
    ganesha(p, 264, 28.6, 0.42)
    p.text(209, 36.5, 30, 10, '{{session}}', 5.4, font='Baloo Bhaina 2', wt=800, c='#FFE082', al='center', va='middle', min=0.5)
    # committee
    p.text(66, 52.3, 165, 8, '{{org_name}}', 5.0, font='Baloo Bhaina 2', wt=800, c=MAROON, al='center', min=0.5)
    p.text(66, 60.2, 165, 5, '{{org_address}}', 3.4, font='Noto Sans Oriya', wt=600, c='#5A3A20', al='center', min=0.6)
    # person
    p.text(66, 89, 165, 14, '{{name}}', 9.2, font='Baloo Bhaina 2', wt=800, c=RED, al='center', min=0.5)
    p.text(68, 106.5, 161, 23, '{{course}} ପ୍ରତିଯୋଗିତାରେ {{grade}} ଠାନ୍ ପାଇଛନ୍ । ଇତିର୍ ଲାଗି ଆମର୍ କମିଟି ତରଫ୍ ତାହାକୁ ଧନ୍ୟବାଦ୍ ଜଣଉଛୁ । ଆଏବା ଦିନ୍ ବେ ସେ ଏନ୍ତା ଆଉର୍ ଭି ପରତିଭାଥି ଆହୁରି ଆଗ୍‌କେ ଯିବେ ଆର୍ ନାଁ କରବେ ।',
           4.2, font='Noto Serif Oriya', c=INK, al='center', lines=4, lh=1.55, min=0.7)
    # position in the medal
    p.text(48.5, 87.6, 19, 8.5, '{{grade}}', 4.8, font='Baloo Bhaina 2', wt=800, c='#FFE082', al='center', va='middle', min=0.45)
    p.text(48.5, 95.6, 19, 5, 'ଠାନ୍', 2.9, font='Noto Sans Oriya', wt=700, c='#FFFFFF', al='center')
    # date, QR, signature
    p.text(124, 141, 49, 10, 'ତାରିଖ: {{cert_date}}', 3.8, font='Noto Sans Oriya', wt=700, c=MAROON, al='center', va='middle', min=0.6)
    p.qr(212.5, 140.5, 17, c=BLACK, bg='#FFFFFF')
    p.text(201, 159.8, 40, 4, 'No. {{id_no}}', 2.3, font='Montserrat', wt=700, c=MAROON, al='center')
    p.img(71, 164, 36, 11.5, '{{sign}}', fit='contain')
    targets = [['cert-festival', 'nuakhai'], ['cert-festival', 'festival'], ['cert-competition', 'winner'], ['cert-competition', 'participant']]
    return design('Nuakhai Bhetghat · Photo', 'premium', targets, landscape={'front': p.side('#3A1606'), 'back': blank()})


if __name__ == '__main__':
    out = sys.argv[1]
    wm = sys.argv[2] if len(sys.argv) > 2 else 'BACKGROUND_IMAGE_URL'
    os.makedirs(out, exist_ok=True)
    d = nuakhai(wm)
    json.dump(d, open(os.path.join(out, 'nuakhai.json'), 'w'), ensure_ascii=False, separators=(',', ':'))
    print(len(d['layout']['landscape']['front']['els']), 'elements')
