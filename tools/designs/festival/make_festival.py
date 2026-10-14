"""
English festival certificates: 7 occasions x 5 styles.

The artwork of each design is one 300 dpi image made from bg.html
(see shoot.js); this script writes the design JSON that draws everything
that changes per person on top of it.

Run:  python3 make_festival.py <output folder>
"""
import json, os, sys
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))
from lib import Page, design, blank

GOLD_T = {'g': 'lin', 'a': 0, 's': [[0, '#B8862F'], [0.45, '#F9E08B'], [0.55, '#FFF3C0'], [1, '#B8862F']]}

# key, subtype slug, palette (primary, deep) - must match OCC in bg.html
OCC = [
    ('durga', 'durga-puja', 'Durga Puja', '#B71C1C', '#3E0606'),
    ('raja', 'raja-parba', 'Raja Parba', '#AD1457', '#2A0A1C'),
    ('ganesh', 'ganesh-puja', 'Ganesh Puja', '#E65100', '#3A1400'),
    ('saraswati', 'saraswati-puja', 'Saraswati Puja', '#D98200', '#3B2A05'),
    ('vishwakarma', 'vishwakarma-puja', 'Vishwakarma Puja', '#0D47A1', '#06142E'),
    ('dhanu', 'dhanu-jatra', 'Dhanu Jatra', '#4A148C', '#1A0533'),
    ('bali', 'bali-jatra', 'Bali Jatra', '#006064', '#00262A'),
]
STYLES = {1: 'Royal Gold', 2: 'Festive Glow', 3: 'Heritage', 4: 'Modern Luxe', 5: 'Mandala Grand'}


def common_bottom(p, qr_x, qr_y, ink, accent, date_cx=55, sign_cx=110):
    p.text(date_cx - 30, 169.5, 60, 8, '{{cert_date}}', 3.7, font='Montserrat', wt=700, c=ink, al='center', va='bottom', min=0.6)
    p.img(sign_cx - 18, 164, 36, 13, '{{sign}}', fit='contain')
    p.qr(qr_x + 1.5, qr_y + 1.5, 18, c='#111111', bg='#FFFFFF')
    p.text(qr_x - 10, qr_y + 22.5, 41, 4, 'No. {{id_no}}', 2.3, font='Montserrat', wt=700, c=accent, al='center')


def style1(p, pr, dk):
    CREAM = '#F6E7C1'
    p.img(35, 27, 18, 18, '{{logo}}', fit='contain', shape='circle')
    p.text(246, 31, 14, 6, '{{grade}}', 3.0, font='Cinzel', wt=700, c='#F9E08B', al='center', va='middle', min=0.4)
    p.text(66, 49.5, 165, 8, '{{org_name}}', 5.2, font='Cinzel', wt=700, c='#F9E08B', al='center', min=0.5)
    p.text(66, 57.5, 165, 5, '{{org_address}}', 2.9, font='Montserrat', wt=500, c=CREAM, al='center', min=0.6)
    p.text(30, 94, 237, 15, '{{name}}', 12.5, font='Great Vibes', c=GOLD_T, al='center', min=0.5)
    p.text(40, 117.5, 217, 9, '{{course}}', 5.6, font='Playfair Display', wt=700, c='#FFFFFF', al='center', min=0.55)
    p.text(128.5, 143, 40, 7.5, '{{session}}', 3.8, font='Montserrat', wt=800, c=dk, al='center', va='middle', min=0.5)
    common_bottom(p, 231.5, 160, '#FFFFFF', '#F9E08B')


def style2(p, pr, dk):
    p.img(40.5, 45.5, 15, 15, '{{logo}}', fit='contain', shape='circle')
    p.text(227, 48.5, 30, 9, '{{session}}', 3.8, font='Montserrat', wt=800, c='#FFFFFF', al='center', va='middle', min=0.5)
    p.text(60, 56.5, 177, 7, '{{org_name}}', 4.8, font='Playfair Display', wt=700, c=dk, al='center', min=0.5)
    p.text(60, 63.5, 177, 5, '{{org_address}}', 2.9, font='Montserrat', wt=500, c='#666666', al='center', min=0.6)
    p.text(40, 100.5, 217, 14, '{{name}}', 10.0, font='Playfair Display', wt=700, c='#1D1D1F', al='center', min=0.5)
    p.text(50, 124, 197, 9, '{{course}}', 5.2, font='Montserrat', wt=800, c=pr, al='center', min=0.55)
    p.text(46, 113.4, 12, 5.4, '{{grade}}', 2.6, font='Montserrat', wt=800, c='#FFE9A3', al='center', va='middle', min=0.4)
    common_bottom(p, 231.5, 158, '#222222', pr, date_cx=62)


def style3(p, pr, dk):
    p.img(38, 28, 16, 16, '{{logo}}', fit='contain', shape='circle')
    p.text(240, 30.5, 22, 7.5, '{{session}}', 3.6, font='Cinzel', wt=700, c='#FFFFFF', al='center', va='middle', min=0.5)
    p.text(40, 60, 217, 8, '{{org_name}}', 5.0, font='Cinzel', wt=700, c=dk, al='center', min=0.5)
    p.text(40, 66.5, 217, 4.5, '{{org_address}}', 2.8, font='Montserrat', wt=500, c='#5A4030', al='center', min=0.6)
    p.text(30, 94.5, 237, 15, '{{name}}', 12.5, font='Great Vibes', c=pr, al='center', min=0.5)
    p.text(40, 118, 217, 9, '{{course}}', 5.4, font='Playfair Display', wt=700, c=dk, al='center', min=0.55)
    p.text(40, 114.3, 12, 5.4, '{{grade}}', 2.6, font='Montserrat', wt=800, c='#FFE9A3', al='center', va='middle', min=0.4)
    common_bottom(p, 231.5, 158, '#222222', dk)


def style4(p, pr, dk):
    p.img(37.5, 14.5, 17, 17, '{{logo}}', fit='contain', shape='circle')
    p.text(16, 123, 60, 9, '{{session}}', 4.4, font='Montserrat', wt=800, c='#FFFFFF', al='center', va='middle', min=0.5)
    p.text(113.5, 14.5, 170, 7, '{{org_name}}', 4.2, font='Montserrat', wt=800, c='#1D1D1F', al='left', min=0.5)
    p.text(113.5, 21.5, 170, 5, '{{org_address}}', 2.8, font='Montserrat', wt=500, c='#777777', al='left', min=0.6)
    p.text(113, 74, 140, 17, '{{name}}', 10.5, font='Playfair Display', wt=700, c='#1D1D1F', al='left', min=0.5)
    p.text(113.5, 101.5, 138, 9, '{{course}}', 5.0, font='Montserrat', wt=800, c=pr, al='left', min=0.55)
    p.text(256, 97.3, 12, 5.4, '{{grade}}', 2.6, font='Montserrat', wt=800, c='#FFE9A3', al='center', va='middle', min=0.4)
    p.text(103, 167.5, 60, 8, '{{cert_date}}', 3.7, font='Montserrat', wt=700, c='#1D1D1F', al='center', va='bottom', min=0.6)
    p.img(167, 162, 36, 13, '{{sign}}', fit='contain')
    p.qr(257.5, 157.5, 18, c='#111111', bg='#FFFFFF')
    p.text(246, 178.5, 42, 4, 'No. {{id_no}}', 2.3, font='Montserrat', wt=700, c=pr, al='center')


def style5(p, pr, dk):
    p.img(31, 23, 18, 18, '{{logo}}', fit='contain', shape='circle')
    p.text(250, 28.4, 14, 6, '{{grade}}', 3.0, font='Cinzel', wt=700, c='#FFE9A3', al='center', va='middle', min=0.4)
    p.text(66, 47.5, 165, 8, '{{org_name}}', 5.2, font='Cinzel', wt=700, c=dk, al='center', min=0.5)
    p.text(66, 55.5, 165, 5, '{{org_address}}', 2.9, font='Montserrat', wt=500, c='#5A4A3A', al='center', min=0.6)
    p.text(30, 94.5, 237, 14, '{{name}}', 9.6, font='Playfair Display', wt=700, c=pr, al='center', min=0.5)
    p.text(40, 117.5, 217, 9, '{{course}}', 5.4, font='Cinzel', wt=700, c=dk, al='center', min=0.55)
    p.text(128.5, 143, 40, 7.5, '{{session}}', 3.8, font='Montserrat', wt=800, c='#FFFFFF', al='center', va='middle', min=0.5)
    common_bottom(p, 231.5, 160, '#222222', pr)


def build(key, sub, name, pr, dk, style):
    p = Page(prefix='f%d' % style)
    p.rect(0, 0, 297, 210, f=dk)
    p.img(0, 0, 297, 210, '{{assets}}img/cert-bg/%s-%d.webp' % (key, style), fit='cover')
    [style1, style2, style3, style4, style5][style - 1](p, pr, dk)
    targets = [['cert-festival', sub], ['cert-festival', 'festival']]
    return design('%s · %s' % (name, STYLES[style]), 'premium', targets, landscape={'front': p.side(dk), 'back': blank()})


if __name__ == '__main__':
    out = sys.argv[1]
    os.makedirs(out, exist_ok=True)
    for key, sub, name, pr, dk in OCC:
        for s in STYLES:
            d = build(key, sub, name, pr, dk, s)
            json.dump(d, open(os.path.join(out, 'fest-%s-%d.json' % (key, s)), 'w'), ensure_ascii=False, separators=(',', ':'))
    print(len(OCC) * len(STYLES), 'designs')
