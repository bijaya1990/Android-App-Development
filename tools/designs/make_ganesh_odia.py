"""
Ganesh Puja competition certificate in Odia (A4 landscape).
Run:  python3 make_ganesh_odia.py <output folder>
Changes per person: {{name}}, {{address}} (village), {{course}} (competition),
{{grade}} (position), {{cert_date}}; per project: {{session}} (year).
"""
import json, math, os, sys
from lib import Page, design, blank

GOLD = {'g': 'lin', 'a': 90, 's': [[0, '#9A6B1F'], [0.5, '#F6DC8C'], [1, '#9A6B1F']]}
GOLD_T = {'g': 'lin', 'a': 0, 's': [[0, '#8C5A12'], [0.45, '#D9A43A'], [0.55, '#F2D27A'], [1, '#8C5A12']]}
MAROON, RED, SAFFRON, MARI, GREEN, INK, BROWN = '#6E0F14', '#B3121B', '#F26A00', '#FFB300', '#2E7D32', '#2B1308', '#6B3E1E'
CX = 148.5


def konark(p, cx, cy, r, c, op):
    """Konark wheel watermark: rim, beads, 24 spokes, hub."""
    p.circle(cx, cy, r, s=c, sw=1.4, op=op)
    p.circle(cx, cy, r - 3.2, s=c, sw=0.6, op=op)
    for i in range(48):
        a = 2 * math.pi * i / 48
        p.circle(cx + (r - 1.6) * math.cos(a), cy + (r - 1.6) * math.sin(a), 0.55, f=c, op=op)
    for i in range(12):
        p.rect(cx - (r - 3.2), cy - 0.45, 2 * (r - 3.2), 0.9 if i % 2 == 0 else 0.5, f=c, op=op, rot=i * 15)
    p.circle(cx, cy, r * 0.22, s=c, sw=0.8, op=op)
    p.circle(cx, cy, r * 0.1, f=c, op=op)


def temple(p, x, base, w, h, c, op):
    """Rekha deula (Odisha temple) silhouette."""
    m = x + w / 2
    pts = [('M', x, base), (x, base - h * 0.18), (x + w * 0.08, base - h * 0.18), (x + w * 0.08, base - h * 0.30),
           ('C', x + w * 0.10, base - h * 0.62, x + w * 0.24, base - h * 0.80, m - w * 0.12, base - h * 0.84),
           (m - w * 0.16, base - h * 0.88), (m + w * 0.16, base - h * 0.88), (m + w * 0.12, base - h * 0.84),
           ('C', x + w * 0.76, base - h * 0.80, x + w * 0.90, base - h * 0.62, x + w * 0.92, base - h * 0.30),
           (x + w * 0.92, base - h * 0.18), (x + w, base - h * 0.18), (x + w, base)]
    p.path(x, base - h * 0.88, w, h * 0.88, pts, f=c, op=op)
    p.circle(m, base - h * 0.91, w * 0.12, f=c, op=op)          # amalaka
    p.rect(m - w * 0.012, base - h, w * 0.024, h * 0.07, f=c, op=op)  # flag pole
    p.poly([(m + w * 0.012, base - h), (m + w * 0.14, base - h * 0.975), (m + w * 0.012, base - h * 0.95)], f=c, op=op)
    for k in range(5):
        p.rect(x + w * 0.1, base - h * (0.34 + k * 0.1), w * 0.8, h * 0.012, f='#FFFFFF', op=op * 0.8)


def ganesha(p, cx, cy, s=1.0):
    """Stylised Lord Ganesha bust (about 34 x 40 mm at s=1)."""
    S = lambda v: v * s
    SKIN = {'g': 'rad', 's': [[0, '#FFD3A1'], [1, '#F29A55']]}
    EAR = {'g': 'rad', 's': [[0, '#FFC7A0'], [1, '#EE8A5C']]}
    # shoulders + dhoti cloth
    sh = [('M', cx - S(15), cy + S(21)), ('C', cx - S(15), cy + S(11), cx - S(9), cy + S(6), cx, cy + S(6)),
          ('C', cx + S(9), cy + S(6), cx + S(15), cy + S(11), cx + S(15), cy + S(21))]
    p.path(cx - S(15), cy + S(6), S(30), S(15), sh, f={'g': 'lin', 'a': 90, 's': [[0, '#E53935'], [1, '#8E0E0E']]})
    p.path(cx - S(11), cy + S(8), S(22), S(9), [('M', cx - S(11), cy + S(9)), ('Q', cx, cy + S(21), cx + S(11), cy + S(9))], close=False, s='#FFD54F', sw=0.7)
    p.path(cx - S(9), cy + S(8.5), S(18), S(7), [('M', cx - S(9), cy + S(9.5)), ('Q', cx, cy + S(18.5), cx + S(9), cy + S(9.5))], close=False, s='#FFFFFF', sw=0.35, dash=[0.4, 0.5])
    p.circle(cx, cy + S(15.2), S(1.3), f=RED, s='#FFD54F', sw=0.3)
    # ears
    for sx in (-1, 1):
        ex = cx + sx * S(10.5)
        p.add('ellipse', ex - S(7.5), cy - S(10), S(15), S(17.5), f=EAR, s='#C77A2E', sw=0.5)
        p.add('ellipse', ex - S(5.2), cy - S(7.5), S(10.4), S(12.5), f='#F7A37C', op=0.85)
        p.circle(ex + sx * S(4.6), cy + S(4.5), S(1.0), f='#FFD54F', s='#B8860B', sw=0.2)
    # head
    p.add('ellipse', cx - S(8), cy - S(11), S(16), S(17), f=SKIN, s='#C77A2E', sw=0.4)
    # crown (mukut)
    p.poly([(cx - S(7.4), cy - S(8)), (cx + S(7.4), cy - S(8)), (cx + S(6), cy - S(14)), (cx + S(3.2), cy - S(20)),
            (cx, cy - S(27)), (cx - S(3.2), cy - S(20)), (cx - S(6), cy - S(14))], f=GOLD, s='#8C5A12', sw=0.3)
    p.rect(cx - S(7.8), cy - S(9.4), S(15.6), S(2.2), r=0.6, f=GOLD_T, s='#8C5A12', sw=0.25)
    for k in (-5, -2.5, 0, 2.5, 5):
        p.circle(cx + S(k), cy - S(8.3), S(0.55), f=RED if k % 5 == 0 else '#1B8E3E')
    p.diamond(cx, cy - S(15), S(1.6), S(2.4), f=RED, s='#FFE082', sw=0.25)
    p.circle(cx, cy - S(21), S(0.9), f='#1B8E3E', s='#FFE082', sw=0.2)
    p.star(cx, cy - S(27.6), S(1.6), S(0.7), 6, f='#FFE082')
    # tilak + eyes
    p.path(cx - S(1.6), cy - S(7), S(3.2), S(3.4), [('M', cx - S(1.6), cy - S(7)), ('Q', cx, cy - S(2.4), cx + S(1.6), cy - S(7))], close=False, s='#D32F2F', sw=0.45)
    p.add('ellipse', cx - S(0.5), cy - S(6.4), S(1), S(2.4), f='#D32F2F')
    for sx in (-1, 1):
        p.add('ellipse', cx + sx * S(3.6) - S(1.7), cy - S(4.6), S(3.4), S(2.0), f='#FFFFFF', s='#3E2723', sw=0.25)
        p.circle(cx + sx * S(3.6), cy - S(3.6), S(0.75), f='#1A0F08')
    # tusk
    p.poly([(cx + S(3.3), cy + S(0.5)), (cx + S(7.5), cy + S(3.2)), (cx + S(3.6), cy + S(2.4))], f='#FFF8E7', s='#BFA17A', sw=0.2)
    # trunk, curling to the left
    t = [('M', cx - S(3.2), cy - S(1.5)),
         ('C', cx - S(3.6), cy + S(5), cx - S(2.4), cy + S(10), cx - S(5.5), cy + S(12.5)),
         ('C', cx - S(8), cy + S(14.5), cx - S(10.5), cy + S(12), cx - S(9), cy + S(9.6)),
         ('C', cx - S(8.2), cy + S(10.8), cx - S(6.4), cy + S(11.2), cx - S(4.6), cy + S(9.6)),
         ('C', cx - S(2.2), cy + S(7.4), cx + S(2.4), cy + S(4.4), cx + S(3.2), cy - S(1.5))]
    p.path(cx - S(10.6), cy - S(1.6), S(13.9), S(16), t, f=SKIN, s='#C77A2E', sw=0.35)
    for k in range(4):
        y = cy + S(1.5 + k * 2.2)
        p.rect(cx - S(2.6) - S(k * 0.35), y, S(4.6), S(0.25), r=0.1, f='#C77A2E', op=0.55)
    # modak in the trunk curl
    p.circle(cx - S(9.6), cy + S(8.2), S(1.6), f='#FFF3D6', s='#C9A15A', sw=0.25)


def lotus_ring(p, cx, cy, r, n, c1, c2):
    for i in range(n):
        a = 360.0 * i / n
        p.leaf(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a)), 4.6, 1.7, a, f=c1 if i % 2 else c2)


def corner(p, cx, cy):
    """Mandala rosette for the corners."""
    p.star(cx, cy, 11, 7.5, 16, f=GOLD, op=0.95)
    p.flower(cx, cy, 8, 8, '#E64A19', MAROON)
    p.flower(cx, cy, 4.2, 8, MARI, '#C2410C')
    p.circle(cx, cy, 1.4, f='#FFF3C4')


def ganesh_odia():
    p = Page(prefix='go')
    # parchment
    p.rect(0, 0, 297, 210, f={'g': 'rad', 's': [[0, '#FFF8E6'], [0.7, '#F7E6BE'], [1, '#E9CB8E']]})
    p.add('pattern', 0, 0, 297, 210, kind='dots', gap=p.u(3.2), size=0.12, c='rgba(140,90,18,0.07)')
    # watermarks: Konark wheel + two temples
    konark(p, CX, 122, 52, '#8C5A12', 0.07)
    temple(p, 30, 192, 34, 62, '#8C5A12', 0.08)
    temple(p, 233, 192, 34, 62, '#8C5A12', 0.08)
    # border: maroon-saffron band, gold rules, dotted inner line
    p.frame(0, 0, 297, 210, 7.5, {'g': 'lin', 'a': 135, 's': [[0, MAROON], [0.5, RED], [1, MAROON]]})
    for i in range(38):
        x = 11 + i * 7.5
        p.diamond(x, 3.75, 1.4, 1.9, f=MARI); p.diamond(x, 206.25, 1.4, 1.9, f=MARI)
    for i in range(27):
        y = 9 + i * 7.3
        p.diamond(3.75, y, 1.9, 1.4, f=MARI); p.diamond(293.25, y, 1.9, 1.4, f=MARI)
    p.frame(7.5, 7.5, 282, 195, 1.6, GOLD)
    p.rect(11, 11, 275, 188, s='#9A6B1F', sw=0.35)
    p.rect(12.6, 12.6, 271.8, 184.8, s='rgba(110,15,20,0.45)', sw=0.2, dash=[0.9, 0.7])
    for (x, y) in [(11, 11), (286, 11), (11, 199), (286, 199)]:
        corner(p, x, y)
    # marigold garlands hanging from the top corners
    for side in (-1, 1):
        for i in range(7):
            t = i / 6.0
            x = CX + side * (128 - 66 * t)
            y = 15 + 10 * math.sin(t * math.pi) * 0.6 + t * 2
            if i % 3 == 2:
                p.leaf(x, y + 1.5, 6, 1.8, 90, f=GREEN)
            p.flower(x, y, 2.6, 9, MARI if i % 2 else SAFFRON, '#C2410C')

    # header: committee + address (both from the organisation)
    p.circle(42, 47, 12.5, f='#FFFFFF', s='#B8862F', sw=0.9, sh=0.8)
    p.img(32, 37, 20, 20, '{{logo}}', fit='contain', shape='circle')
    p.text(34, 14, 229, 12, '{{org_name}}', 7.6, font='Baloo Bhaina 2', wt=800, c=MAROON, al='center', min=0.5)
    p.text(40, 25.6, 217, 6, '{{org_address}}', 3.5, font='Noto Sans Oriya', wt=600, c=BROWN, al='center', min=0.6)
    # Ganesha medallion
    p.circle(CX, 57, 20.5, f=GOLD, sh=1.2)
    lotus_ring(p, CX, 57, 19.6, 24, '#E65100', '#C62828')
    p.circle(CX, 57, 17.8, f={'g': 'rad', 's': [[0, '#FFF6D8'], [0.65, '#FFD978'], [1, '#F2A900']]}, s=MAROON, sw=0.7)
    p.star(CX, 57, 16.9, 14.6, 36, f='rgba(255,255,255,0.35)')
    ganesha(p, CX, 58.2, 0.58)
    # position medal (right)
    for sx in (-1, 1):
        p.poly([(255 + sx * 2, 54), (255 + sx * 7.5, 70), (255 + sx * 4.5, 68), (255 + sx * 2.5, 71.5), (255 + sx * 0.2, 56)], f=RED if sx < 0 else '#1565C0')
    p.star(255, 47, 13, 11.2, 24, f=GOLD, sh=0.8)
    p.circle(255, 47, 10, f={'g': 'rad', 's': [[0, '#B71C1C'], [1, MAROON]]}, s='#FFE082', sw=0.5)
    p.text(244.5, 40.5, 21, 9, '{{grade}}', 5.2, font='Baloo Bhaina 2', wt=800, c='#FFE082', al='center', min=0.45)
    p.text(244.5, 49.2, 21, 5, 'ସ୍ଥାନ', 3.0, font='Noto Sans Oriya', wt=700, c='#FFFFFF', al='center')

    # title + body
    p.text(40, 80, 217, 14, 'Certificate of Merit', 10.6, font='Great Vibes', c=GOLD_T, al='center')
    p.rect(98, 94, 40, 0.5, f=GOLD); p.diamond(CX, 94.25, 2.2, 1.6, f=RED); p.rect(159, 94, 40, 0.5, f=GOLD)
    p.text(30, 97, 237, 7, 'ଏତଦ୍ ଦ୍ୱାରା ପ୍ରମାଣିତ କରାଯାଉଛି ଯେ, ଶ୍ରୀ / କୁମାରୀ', 4.1, font='Noto Serif Oriya', c=INK, al='center')
    p.text(30, 104, 237, 13.5, '{{name}}', 8.6, font='Baloo Bhaina 2', wt=800, c=RED, al='center', min=0.5)
    p.rect(83, 117.5, 131, 0.5, f=GOLD)
    p.text(30, 120, 237, 21, 'ଗ୍ରାମ {{address}}, {{session}} ମସିହାରେ ଅନୁଷ୍ଠିତ ଶ୍ରୀ ଶ୍ରୀ ଗଣେଶ ପୂଜା ଉପଲକ୍ଷେ ଆୟୋଜିତ {{course}} ପ୍ରତିଯୋଗିତାରେ ଭାଗନେଇ {{grade}} ସ୍ଥାନ ଅଧିକାର କରିଛନ୍ତି। ଆମେ ଆପଣଙ୍କର ଉଜ୍ଜ୍ୱଳ ଭବିଷ୍ୟତ କାମନା କରୁଛୁ।',
           4.3, font='Noto Serif Oriya', c=INK, al='center', lines=3, lh=1.55, min=0.7)

    # QR (left) and date box (right)
    p.rect(22, 140, 22, 22, r=1, f='#FFFFFF', s='#B8862F', sw=0.5)
    p.qr(23.5, 141.5, 19, c=MAROON, bg='#FFFFFF')
    p.text(14, 162.5, 38, 4, 'No. {{id_no}}', 2.4, font='Montserrat', wt=700, c=MAROON, al='center')
    p.rect(222, 145, 56, 12, r=2, f='#FFFDF5', s='#B8862F', sw=0.7, sh=0.5)
    p.text(224, 146, 52, 10, 'ତାରିଖ: {{cert_date}}', 4.0, font='Noto Sans Oriya', wt=700, c=MAROON, al='center', va='middle', min=0.6)

    # gold seal (centre bottom)
    p.star(CX, 166, 15, 13, 32, f=GOLD, sh=1.0)
    p.circle(CX, 166, 12, f={'g': 'rad', 's': [[0, '#F9E29B'], [1, '#C08A2B']]}, s='#8C5A12', sw=0.5)
    p.circle(CX, 166, 10, s='#8C5A12', sw=0.3, dash=[0.5, 0.4])
    p.text(CX - 10, 158.6, 20, 5, 'ଗଣେଶ ପୂଜା', 2.9, font='Noto Sans Oriya', wt=700, c='#5A3A0A', al='center')
    p.text(CX - 9, 163.6, 18, 7, 'ॐ', 5.2, font='Tiro Devanagari Hindi', c=MAROON, al='center')
    p.text(CX - 10, 170.6, 20, 4, '{{session}}', 2.6, font='Montserrat', wt=700, c='#5A3A0A', al='center')
    p.img(CX - 11, 155, 22, 22, '{{seal}}', fit='contain', op=0.95)

    # four signatures
    for i, (od, en) in enumerate([('ସଭାପତି', 'President'), ('ସମ୍ପାଦକ', 'Secretary'),
                                  ('ମୁଖ୍ୟ ଅତିଥି', 'Chief Guest'), ('ସମ୍ମାନିତ ଅତିଥି', 'Honoured Guest')]):
        x = [26, 80, 177, 231][i]
        if i == 0:
            p.img(x + 2, 170, 36, 11, '{{sign}}', fit='contain')
        p.rect(x, 182, 40, 0.45, f=MAROON)
        p.text(x - 4, 183.3, 48, 5.5, od, 3.4, font='Noto Sans Oriya', wt=700, c=MAROON, al='center')
        p.text(x - 4, 188.6, 48, 4, en, 2.5, font='Montserrat', wt=600, c=BROWN, al='center', up=True, ls=0.08)

    targets = [['cert-festival', 'ganesh-puja'], ['cert-festival', 'festival'], ['cert-competition', 'winner'], ['cert-competition', 'participant']]
    return design('Ganesh Puja Odia · Merit', 'premium', targets, landscape={'front': p.side('#FFF8E6'), 'back': blank()})


if __name__ == '__main__':
    out = sys.argv[1]
    os.makedirs(out, exist_ok=True)
    d = ganesh_odia()
    json.dump(d, open(os.path.join(out, 'ganesh-odia.json'), 'w'), ensure_ascii=False, separators=(',', ':'))
    print(len(d['layout']['landscape']['front']['els']), 'elements')
