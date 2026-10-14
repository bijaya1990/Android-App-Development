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


def nuakhai(watermark):
    p = Page(prefix='nk')
    p.rect(0, 0, 297, 210, f=CREAM)
    # photo watermark + soft cream glow so text stays readable
    p.img(9, 9, 279, 192, watermark, fit='cover', op=0.95)
    p.add('ellipse', 6, 30, 285, 178, f={'g': 'rad', 's': [[0, 'rgba(255,250,238,0.9)'], [0.45, 'rgba(255,250,238,0.78)'], [0.75, 'rgba(255,250,238,0.4)'], [1, 'rgba(255,250,238,0)']]})
    # Sambalpuri border: red, black ikat band, gold rule
    p.frame(0, 0, 297, 210, 3, RED)
    p.frame(3, 3, 291, 204, 6, BLACK)
    ikat_band_h(p, 8, 289, 6, '#FFFFFF', RED2)
    ikat_band_h(p, 8, 289, 204, '#FFFFFF', RED2)
    ikat_band_v(p, 13, 197, 6, RED2, '#FFFFFF')
    ikat_band_v(p, 13, 197, 291, RED2, '#FFFFFF')
    p.frame(9, 9, 279, 192, 1.2, GOLD)
    p.rect(11.5, 11.5, 274, 187, s='rgba(122,14,18,0.5)', sw=0.25, dash=[1, 0.8])
    sash(p, 10.2, 10.2, 1, 1)
    sash(p, 286.8, 199.8, -1, -1)

    # invocation + title
    p.text(70, 13, 157, 7, 'ଓଁ ଓଁ ଶ୍ରୀ ଗଣେଶାୟ ନମଃ', 3.8, font='Noto Serif Oriya', wt=700, c=MAROON, al='center')
    p.text(70, 19, 157, 21, 'ନୂଆଖାଇ ଭେଟ୍‌ଘାଟ୍', 13.5, font='Baloo Bhaina 2', wt=800,
           c={'g': 'lin', 'a': 90, 's': [[0, RED2], [1, '#8E0E0E']]}, al='center', min=0.5)
    p.text(186, 37, 44, 9, '{{session}}', 6.0, font='Baloo Bhaina 2', wt=800, c=MAROON, al='right', min=0.5)
    # Ganesha medallion, top right
    p.circle(252, 33, 16.5, f=GOLD, sh=1.0)
    p.circle(252, 33, 14.8, f={'g': 'rad', 's': [[0, '#FFF6D8'], [0.7, '#FFE08A'], [1, '#F2A900']]}, s=MAROON, sw=0.6)
    ganesha(p, 252, 34.5, 0.46)
    # logo, top left (inside the sash corner)
    p.circle(66, 30, 9.5, f='#FFFFFF', s='#B8862F', sw=0.7, sh=0.6)
    p.img(58.5, 22.5, 15, 15, '{{logo}}', fit='contain', shape='circle')
    # committee
    p.text(40, 46.5, 217, 8, '{{org_name}}', 5.0, font='Baloo Bhaina 2', wt=700, c=INK, al='center', min=0.5)
    p.text(40, 54.5, 217, 6, '{{org_address}}', 3.6, font='Noto Sans Oriya', wt=600, c='#5A3A20', al='center', min=0.6)
    # ribbon: Yogyata Praman Patra
    for sx in (-1, 1):
        x = CX + sx * 52
        p.poly([(x, 64.5), (x + sx * 11, 64.5), (x + sx * 7, 69.5), (x + sx * 11, 74.5), (x, 74.5)], f='#8E0E0E')
    p.rect(CX - 52, 62, 104, 14, r=7, f={'g': 'lin', 'a': 90, 's': [[0, RED2], [1, '#9B0F14']]}, s=BLACK, sw=0.6, sh=0.8)
    p.rect(CX - 49.5, 63.6, 99, 10.8, r=5.4, s='#FFD54F', sw=0.35)
    p.text(CX - 48, 62.6, 96, 13, 'ଯୋଗ୍ୟତା ପ୍ରମାଣ ପତ୍ର', 6.6, font='Baloo Bhaina 2', wt=800, c='#FFFFFF', al='center', va='middle')

    # body
    p.text(40, 80, 217, 7, 'ମାଏନ୍ତା ଶ୍ରୀ / କୁମାରୀ', 4.2, font='Noto Serif Oriya', wt=600, c=INK, al='center')
    p.text(40, 86.5, 217, 14, '{{name}}', 9.0, font='Baloo Bhaina 2', wt=800, c=RED, al='center', min=0.5)
    p.rect(88, 101, 121, 0.5, f=GOLD)
    p.diamond(CX, 101.25, 2.0, 1.4, f=RED)
    p.text(64, 104.5, 169, 26, '{{course}} ପ୍ରତିଯୋଗିତାରେ {{grade}} ଠାନ୍ ପାଇଛନ୍ । ଇତିର୍ ଲାଗି ଆମର୍ କମିଟି ତରଫ୍ ତାହାକୁ ଧନ୍ୟବାଦ୍ ଜଣଉଛୁ । ଆଏବା ଦିନ୍ ବେ ସେ ଏନ୍ତା ଆଉର୍ ଭି ପରତିଭାଥି ଆହୁରି ଆଗ୍‌କେ ଯିବେ ଆର୍ ନାଁ କରବେ ।',
           4.3, font='Noto Serif Oriya', c=INK, al='center', lines=4, lh=1.55, min=0.7)
    p.text(40, 130.5, 217, 8, 'ଜଏ କୋଶଲ ।', 5.0, font='Baloo Bhaina 2', wt=800, c=RED, al='center')

    # position medal (left) and competition chip (right)
    for sx in (-1, 1):
        p.poly([(44 + sx * 2, 101), (44 + sx * 7, 116), (44 + sx * 4.3, 114.3), (44 + sx * 2.4, 117.5), (44 + sx * 0.2, 103)], f=RED if sx < 0 else BLACK)
    p.star(44, 94, 12.5, 10.8, 24, f=GOLD, sh=0.8)
    p.circle(44, 94, 9.6, f={'g': 'rad', 's': [[0, '#D32F2F'], [1, MAROON]]}, s='#FFE082', sw=0.5)
    p.text(34, 87.5, 20, 9, '{{grade}}', 5.0, font='Baloo Bhaina 2', wt=800, c='#FFE082', al='center', min=0.45)
    p.text(34, 96, 20, 5, 'ଠାନ୍', 3.0, font='Noto Sans Oriya', wt=700, c='#FFFFFF', al='center')
    paddy(p, 252, 112, 0.9, -1)

    # date + QR
    p.rect(CX - 26, 143, 52, 10, r=5, f='#FFFDF5', s='#B8862F', sw=0.6)
    p.text(CX - 25, 143.6, 50, 9, 'ତାରିଖ: {{cert_date}}', 3.8, font='Noto Sans Oriya', wt=700, c=MAROON, al='center', va='middle', min=0.6)
    p.rect(19, 146, 20, 20, r=1, f='#FFFFFF', s='#B8862F', sw=0.5)
    p.qr(20.5, 147.5, 17, c=BLACK, bg='#FFFFFF')
    p.text(11, 166.5, 38, 4, 'No. {{id_no}}', 2.3, font='Montserrat', wt=700, c=MAROON, al='center')

    # three signatures with red pill labels
    for i, label in enumerate(['ସଭାପତି', 'ମୁଖ୍ୟ ଅତିଥି', 'ସଂଯୋଜକ']):
        x = [72, CX, 225][i]
        if i == 0:
            p.img(x - 18, 163, 36, 11, '{{sign}}', fit='contain')
        p.rect(x - 20, 175, 40, 0.45, f=MAROON)
        p.rect(x - 15, 177.5, 30, 8, r=4, f={'g': 'lin', 'a': 90, 's': [[0, RED2], [1, '#9B0F14']]}, s='#FFFFFF', sw=0.5, sh=0.5)
        p.text(x - 15, 177.6, 30, 7.8, label, 3.6, font='Baloo Bhaina 2', wt=700, c='#FFFFFF', al='center', va='middle')

    targets = [['cert-festival', 'nuakhai'], ['cert-festival', 'festival'], ['cert-competition', 'winner'], ['cert-competition', 'participant']]
    return design('Nuakhai Bhetghat · Sambalpuri', 'premium', targets, landscape={'front': p.side(CREAM), 'back': blank()})


if __name__ == '__main__':
    out = sys.argv[1]
    wm = sys.argv[2] if len(sys.argv) > 2 else 'WATERMARK_IMAGE_URL'
    os.makedirs(out, exist_ok=True)
    d = nuakhai(wm)
    json.dump(d, open(os.path.join(out, 'nuakhai.json'), 'w'), ensure_ascii=False, separators=(',', ':'))
    print(len(d['layout']['landscape']['front']['els']), 'elements')
