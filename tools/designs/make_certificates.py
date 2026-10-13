"""
Built-in certificate designs for Pikacart. Each function returns one design.
Run:  python3 make_certificates.py <output folder>
Every design is A4 landscape (297 x 210 mm); placeholders such as {{name}}
are filled per person, and lines whose value is empty disappear.
"""
import json, math, os, sys
from lib import Page, design, blank

GOLD = {'g': 'lin', 'a': 90, 's': [[0, '#9A7B2F'], [0.5, '#F3DE9C'], [1, '#9A7B2F']]}
GOLD_D = {'g': 'lin', 'a': 0, 's': [[0, '#B8913F'], [0.5, '#F1DFA6'], [1, '#B8913F']]}
CERT_TARGETS = {
    'merit': [['cert-school', 'merit'], ['cert-school', 'appreciation'], ['cert-competition', 'winner'], ['cert-corporate', 'award']],
    'course': [['cert-course', 'completion'], ['cert-course', 'computer'], ['cert-course', 'skill'], ['cert-course', 'workshop'], ['cert-corporate', 'training'], ['cert-corporate', 'internship']],
    'festival': [['cert-festival', 'ganesh-puja'], ['cert-festival', 'festival']],
}


def footer(p, y, ink, accent, line_c, seal=True, left_label='DATE', right_label='{{signatory_title}}'):
    """Date (left), signature (right) and certificate number + QR."""
    # date
    p.text(40, y - 9, 60, 8, '{{cert_date}}', 4.2, font='Montserrat', wt=700, c=ink, al='center', va='bottom')
    p.rect(45, y, 50, 0.45, f=line_c)
    p.text(40, y + 1.5, 60, 5, left_label, 2.8, font='Montserrat', wt=700, c=accent, al='center', ls=0.18)
    # signature
    p.img(202, y - 17, 50, 16, '{{sign}}', fit='contain')
    p.rect(202, y, 50, 0.45, f=line_c)
    p.text(192, y + 1.5, 70, 5, right_label, 2.8, font='Montserrat', wt=700, c=accent, al='center', ls=0.12, up=True)
    p.text(192, y + 6.2, 70, 4.5, '{{signatory_name}}', 2.6, font='Montserrat', c=ink, al='center')


# ---------------------------------------------------------------------------
def royal_gold():
    """Navy and gold, double frame, corner flourishes, gold award seal."""
    p = Page(prefix='rg')
    NAVY, NAVY2, INK = '#0E1F45', '#1B3A73', '#1A1A2E'
    p.rect(0, 0, 297, 210, f={'g': 'rad', 's': [[0, '#FFFEF8'], [1, '#F3EAD3']]})
    p.add('pattern', 0, 0, 297, 210, kind='rings', gap=p.u(6), size=0.18, cx=0, cy=0, c='rgba(180,145,63,0.10)')
    p.add('pattern', 0, 0, 297, 210, kind='rings', gap=p.u(6), size=0.18, cx=100, cy=100, c='rgba(180,145,63,0.10)')
    p.frame(0, 0, 297, 210, 9, NAVY)
    p.frame(9, 9, 279, 192, 1.6, GOLD)
    p.rect(13, 13, 271, 184, s='#B8913F', sw=0.25)
    p.rect(14.6, 14.6, 267.8, 180.8, s='rgba(184,145,63,0.6)', sw=0.15, dash=[0.8, 0.6])
    # corner flourishes
    for (cx, cy, sx, sy) in [(13, 13, 1, 1), (284, 13, -1, 1), (13, 197, 1, -1), (284, 197, -1, -1)]:
        pts = [('M', cx, cy + sy * 28), ('C', cx, cy + sy * 10, cx + sx * 10, cy, cx + sx * 28, cy),
               ('C', cx + sx * 16, cy + sy * 4, cx + sx * 6, cy + sy * 10, cx + sx * 4, cy + sy * 20),
               ('C', cx + sx * 2, cy + sy * 10, cx + sx * 8, cy + sy * 4, cx + sx * 14, cy + sy * 3),
               ('C', cx + sx * 6, cy + sy * 6, cx + sx * 3, cy + sy * 14, cx, cy + sy * 28)]
        xs = [cx, cx + sx * 28]; ys = [cy, cy + sy * 28]
        p.path(min(xs), min(ys), 28, 28, pts, f=GOLD)
        p.circle(cx + sx * 8, cy + sy * 8, 1.6, f='#B8913F')
    # header
    p.circle(148.5, 30, 11, f='#FFFFFF', s='#B8913F', sw=0.6, sh=0.8)
    p.img(140.5, 22, 16, 16, '{{logo}}', fit='contain', shape='circle')
    p.text(40, 43, 217, 9, '{{org_name}}', 5.6, font='Cinzel', wt=700, c=NAVY, al='center', up=True, ls=0.06, min=0.5)
    p.text(40, 52, 217, 5, '{{org_tagline}}', 2.9, font='Montserrat', c='#6B5A2E', al='center', up=True, ls=0.16)
    # title
    p.text(30, 61, 237, 17, '{{card_title}}', 11.5, font='Cinzel Decorative', wt=700, c=GOLD_D, al='center', up=True, ls=0.03, min=0.45)
    p.rect(98, 80.6, 40, 0.5, f=GOLD); p.rect(159, 80.6, 40, 0.5, f=GOLD)
    p.diamond(148.5, 80.85, 2.4, 2.4, f='#B8913F')
    p.text(40, 84, 217, 7, 'This certificate is proudly presented to', 4.0, font='Cormorant Garamond', c='#4A4A5A', al='center')
    p.text(30, 91, 237, 22, '{{name}}', 15, font='Great Vibes', c=NAVY, al='center', min=0.5)
    p.rect(78, 114, 141, 0.45, f=GOLD)
    p.stack(40, 117, 217, 24, [
        {'l': 'Son / Daughter of', 'v': 'S/o, D/o {{guardian}}', 'f': 'guardian'},
        {'l': 'For', 'v': '{{course}}', 'f': 'course'},
        {'l': 'Award', 'v': '{{grade}}', 'f': 'grade'},
    ], 4.3, mode='value', al='center', font='Cormorant Garamond', vc=INK, vwt=700, lh=1.55)
    # seal
    p.star(148.5, 166, 17, 14.5, 24, f=GOLD)
    p.circle(148.5, 166, 12.6, f=NAVY)
    p.circle(148.5, 166, 11.4, s='#E8CF86', sw=0.35)
    p.poly([(140, 177), (136, 196), (141.5, 192), (146, 197), (147, 179)], f=NAVY2)
    p.poly([(157, 177), (161, 196), (155.5, 192), (151, 197), (150, 179)], f=NAVY2)
    p.star(148.5, 166, 12.6, 11.2, 24, f=NAVY, op=0)
    p.text(136, 158.5, 25, 5, 'AWARD OF', 2.3, font='Montserrat', wt=700, c='#F3DE9C', al='center', ls=0.12)
    p.text(134, 163, 29, 7, 'EXCELLENCE', 3.0, font='Cinzel', wt=700, c='#FFFFFF', al='center')
    p.star(148.5, 172.8, 1.8, 0.8, 5, f='#F3DE9C')
    footer(p, 178, INK, '#8A6D2B', '#1B3A73')
    # number + QR
    p.qr(18.5, 167, 17, c=NAVY, bg='#FFFFFF')
    p.text(18, 184.5, 60, 5, 'No. {{id_no}}', 2.6, font='Montserrat', wt=700, c='#6B5A2E')
    return design('Royal Gold Merit', 'premium', CERT_TARGETS['merit'], landscape={'front': p.side('#FFFDF6'), 'back': blank()})


# ---------------------------------------------------------------------------
def sambalpuri():
    """Sambalpuri bandha border: maroon band with ikat diamonds, pasapali corners."""
    p = Page(prefix='sb')
    MAROON, BLACK, RED, TURMERIC, CREAM, INK = '#7A0F12', '#1B1513', '#B3121B', '#E0A100', '#FBF5E9', '#2B1A12'
    p.rect(0, 0, 297, 210, f=CREAM)
    p.add('pattern', 22, 22, 253, 166, kind='dots', gap=p.u(4.5), size=0.22, c='rgba(122,15,18,0.07)')
    # outer dhadi stripes
    p.frame(0, 0, 297, 210, 3, BLACK)
    p.frame(3, 3, 291, 204, 1.2, '#FFFFFF')
    p.frame(4.2, 4.2, 288.6, 201.6, 1.0, RED)
    p.frame(5.2, 5.2, 286.6, 199.6, 12, MAROON)
    p.frame(17.2, 17.2, 262.6, 175.6, 1.0, TURMERIC)
    p.frame(18.2, 18.2, 260.6, 173.6, 0.8, BLACK)
    # ikat diamonds along the band
    def band(x0, y0, x1, y1, n):
        for i in range(n):
            t = (i + 0.5) / n
            cx, cy = x0 + (x1 - x0) * t, y0 + (y1 - y0) * t
            if i % 2 == 0:
                p.ikat(cx, cy, 4.2, 3, f='#FFFFFF')
                p.ikat(cx, cy, 2.2, 2, f=BLACK)
                p.diamond(cx, cy, 0.8, 0.8, f=TURMERIC)
            else:
                p.ikat(cx, cy, 2.6, 2, f=TURMERIC)
                p.diamond(cx, cy, 0.9, 0.9, f=MAROON)
    band(24, 11.2, 273, 11.2, 25); band(24, 198.8, 273, 198.8, 25)
    band(11.2, 24, 11.2, 186, 16); band(285.8, 24, 285.8, 186, 16)
    # pasapali corners
    for (x, y) in [(5.2, 5.2), (279.8, 5.2), (5.2, 192.8), (279.8, 192.8)]:
        p.rect(x, y, 12, 12, f=BLACK)
        for r in range(4):
            for c in range(4):
                if (r + c) % 2 == 0:
                    p.rect(x + 0.6 + c * 2.7, y + 0.6 + r * 2.7, 2.7, 2.7, f='#FFFFFF')
        p.diamond(x + 6, y + 6, 2.2, 2.2, f=RED)
    # top shankha-like arcs (kumbha / temple motif row)
    for i in range(9):
        cx = 88 + i * 15
        if abs(cx - 148.5) < 20:
            continue  # keep the logo clear
        p.poly([(cx - 4.5, 28), (cx, 22.5), (cx + 4.5, 28)], f=MAROON, op=0.85)
        p.circle(cx, 23.2, 0.85, f=TURMERIC)
    # header
    p.circle(148.5, 40, 10, f='#FFFFFF', s=MAROON, sw=0.7)
    p.img(141.5, 33, 14, 14, '{{logo}}', fit='contain', shape='circle')
    p.text(35, 51, 227, 9, '{{org_name}}', 5.4, font='Noto Serif Oriya', wt=700, c=MAROON, al='center', up=True, min=0.5)
    p.text(35, 60, 227, 5, '{{org_tagline}}', 2.8, font='Montserrat', c='#6E4B2A', al='center', up=True, ls=0.12)
    # bilingual title
    p.text(60, 66, 177, 15, 'ପ୍ରମାଣପତ୍ର', 10.5, font='Baloo Bhaina 2', wt=700, c=RED, al='center')
    p.text(50, 81, 197, 7, '{{card_title}}', 4.4, font='Cinzel', wt=700, c=BLACK, al='center', up=True, ls=0.1)
    for x in (84, 196):
        p.rect(x, 90.2, 17, 0.6, f=TURMERIC)
    p.ikat(148.5, 90.5, 2.2, 2, f=MAROON)
    p.text(40, 94, 217, 7, 'ଏହା ପ୍ରମାଣିତ କରାଯାଉଛି ଯେ  ·  This is to certify that', 3.6, font='Noto Sans Oriya', c='#4A3A30', al='center')
    p.text(30, 101, 237, 17, '{{name}}', 10, font='Noto Serif Oriya', wt=700, c=MAROON, al='center', min=0.5)
    p.rect(85, 119, 127, 0.5, f=BLACK)
    p.stack(40, 122, 217, 30, [
        {'l': 'ପିତା / ଅଭିଭାବକ · Father / Guardian', 'v': '{{guardian}}', 'f': 'guardian'},
        {'l': 'ପାଠ୍ୟକ୍ରମ · Course', 'v': '{{course}}', 'f': 'course'},
        {'l': 'ଅବଧି · Duration', 'v': '{{duration}}', 'f': 'duration'},
        {'l': 'ଶ୍ରେଣୀ · Grade', 'v': '{{grade}}', 'f': 'grade'},
    ], 3.8, mode='inline', al='center', font='Noto Sans Oriya', vc=INK, vwt=700, lh=1.6)
    footer(p, 176, INK, MAROON, BLACK, left_label='ତାରିଖ · DATE')
    p.qr(131.5, 156, 34 * 0.5, c=BLACK, bg='#FFFFFF')
    p.text(118, 174, 61, 4.5, 'No. {{id_no}}', 2.5, font='Montserrat', wt=700, c=MAROON, al='center')
    return design('Sambalpuri Bandha · Odia', 'premium', CERT_TARGETS['course'] + [['cert-school', 'merit'], ['cert-social', 'volunteer']], landscape={'front': p.side(CREAM), 'back': blank()})


# ---------------------------------------------------------------------------
def ganesh():
    """Ganesh Puja: marigold toran, mango leaves, Om in a golden halo, diyas."""
    p = Page(prefix='gp')
    SAFFRON, MARI, MAROON, GREEN, INK = '#F26A00', '#FFB300', '#7B1010', '#2E7D32', '#3A1A0A'
    p.rect(0, 0, 297, 210, f={'g': 'rad', 's': [[0, '#FFF9EC'], [1, '#FCE7C4']]})
    p.frame(0, 0, 297, 210, 6, {'g': 'lin', 'a': 135, 's': [[0, '#B71C1C'], [0.5, SAFFRON], [1, '#B71C1C']]})
    p.frame(8, 8, 281, 194, 0.9, MAROON)
    p.rect(10, 10, 277, 190, s='rgba(123,16,16,0.5)', sw=0.2, dash=[1, 0.8])
    # toran: string of marigolds with mango leaves hanging
    for i in range(23):
        cx = 14 + i * 12.2
        cy = 12 + 5 * math.sin(i / 22 * math.pi)
        p.leaf(cx, cy + 2, 11, 2.6, 90 + (8 if i % 2 else -8), f=GREEN)
        p.flower(cx, cy, 3.4, 10, MARI if i % 2 else SAFFRON, '#C2410C')
    # om in golden halo
    p.star(148.5, 52, 21, 18, 32, f={'g': 'rad', 's': [[0, '#FFE08A'], [1, '#F2A900']]}, op=0.9)
    p.circle(148.5, 52, 16.5, f={'g': 'rad', 's': [[0, '#FFFDF5'], [1, '#FFE7B0']]}, s=MAROON, sw=0.6)
    p.text(131.5, 38, 34, 28, 'ॐ', 18, font='Tiro Devanagari Hindi', c=SAFFRON, al='center')
    p.text(60, 72, 177, 7, 'ଶ୍ରୀ ଗଣେଶାୟ ନମଃ', 4.0, font='Noto Serif Oriya', wt=700, c=MAROON, al='center')
    # org + titles
    p.text(30, 80, 237, 8, '{{org_name}}', 4.8, font='Noto Serif Oriya', wt=700, c=INK, al='center', up=True, min=0.5)
    p.text(40, 88.5, 217, 9, 'ଗଣେଶ ପୂଜା ମହୋତ୍ସବ {{session}}', 6.2, font='Baloo Bhaina 2', wt=700, c='#C62828', al='center')
    p.text(40, 98, 217, 7, '{{card_title}}', 4.2, font='Cinzel', wt=700, c=MAROON, al='center', up=True, ls=0.1)
    p.text(40, 106, 217, 6, 'ଏହି ପ୍ରମାଣପତ୍ର ପ୍ରଦାନ କରାଯାଉଛି  ·  This certificate is presented to', 3.3, font='Noto Sans Oriya', c='#5A3A20', al='center')
    p.text(30, 112, 237, 16, '{{name}}', 10.5, font='Great Vibes', c=MAROON, al='center', min=0.5)
    p.rect(88, 129, 121, 0.5, f=SAFFRON)
    p.stack(40, 131.5, 217, 20, [
        {'l': 'Event', 'v': '{{course}}', 'f': 'course'},
        {'l': 'Position', 'v': '{{grade}}', 'f': 'grade'},
    ], 4.0, mode='value', al='center', font='Noto Sans Oriya', vc=INK, vwt=700, lh=1.55)
    # diyas
    for cx in (40, 257):
        p.leaf(cx, 166, 7.5, 2.2, -90, f={'g': 'lin', 'a': 0, 's': [[0, '#FFD54F'], [1, '#FF6F00']]})
        p.path(cx - 9, 166, 18, 7, [('M', cx - 9, 166), ('Q', cx, 177, cx + 9, 166), 'Z'], f='#8D3B0A')
        p.rect(cx - 9.5, 165.2, 19, 1.2, r=0.6, f='#C2410C')
    footer(p, 182, INK, MAROON, MAROON, left_label='ତାରିଖ · DATE')
    p.qr(140, 160, 17, c=MAROON, bg='#FFFFFF')
    p.text(118, 178, 61, 4.5, 'No. {{id_no}}', 2.4, font='Montserrat', wt=700, c=MAROON, al='center')
    return design('Ganesh Puja Utsav', 'premium', CERT_TARGETS['festival'], landscape={'front': p.side('#FFF9EC'), 'back': blank()})


# ---------------------------------------------------------------------------
def modern_tech():
    """Clean computer-centre certificate: teal/navy side band, chips, QR."""
    p = Page(prefix='mt')
    NAVY, TEAL, AQUA, INK, MUTED = '#0B2545', '#0E7C86', '#2EC4B6', '#13202E', '#5B6B7A'
    p.rect(0, 0, 297, 210, f='#FFFFFF')
    p.rect(0, 0, 74, 210, f={'g': 'lin', 'a': 180, 's': [[0, NAVY], [1, TEAL]]})
    p.add('pattern', 0, 0, 74, 210, kind='dots', gap=p.u(4), size=0.3, c='rgba(255,255,255,0.12)')
    p.poly([(74, 0), (96, 0), (74, 46)], f=AQUA, op=0.9)
    p.poly([(74, 210), (110, 210), (74, 150)], f=TEAL, op=0.25)
    p.rect(74, 0, 1.2, 210, f=GOLD)
    p.circle(37, 42, 19, f='#FFFFFF', sh=1.2)
    p.img(23, 28, 28, 28, '{{logo}}', fit='contain', shape='circle')
    p.text(8, 66, 58, 16, '{{org_name}}', 4.0, font='Montserrat', wt=700, c='#FFFFFF', al='center', lines=3, lh=1.15, up=True, min=0.5)
    p.text(8, 83, 58, 10, '{{org_tagline}}', 2.6, font='Montserrat', c='#BFEAE6', al='center', lines=2)
    p.img(22, 104, 30, 37, '{{photo}}', fit='cover', r=1.2, field='photo', ring='#FFFFFF', rw=0.6)
    p.qr(24.5, 156, 25, c=NAVY, bg='#FFFFFF')
    p.text(8, 183, 58, 5, 'SCAN TO VERIFY', 2.4, font='Montserrat', wt=700, c='#FFFFFF', al='center', ls=0.14)
    p.text(8, 189, 58, 5, 'No. {{id_no}}', 2.6, font='Montserrat', wt=700, c='#BFEAE6', al='center')
    # content
    p.text(92, 26, 190, 20, 'CERTIFICATE', 13.5, font='Montserrat', wt=700, c=NAVY, ls=0.12)
    p.text(93, 46, 190, 8, '{{title_rest}}', 4.6, font='Montserrat', wt=700, c=TEAL, up=True, ls=0.22)
    p.rect(93, 56, 26, 1.2, f=AQUA)
    p.text(93, 66, 190, 7, 'This is to certify that', 4.0, font='Montserrat', c=MUTED)
    p.text(92, 74, 195, 18, '{{name}}', 10.5, font='Playfair Display', wt=700, c=INK, min=0.5)
    p.text(93, 92, 190, 6, 'S/o, D/o {{guardian}}', 3.4, font='Montserrat', c=MUTED, field='guardian')
    p.text(93, 101, 190, 7, 'has successfully completed the course', 4.0, font='Montserrat', c=MUTED)
    p.text(93, 109, 190, 12, '{{course}}', 6.0, font='Montserrat', wt=700, c=TEAL, lines=2, lh=1.1, min=0.6)
    p.stack(93, 126, 190, 24, [
        {'l': 'Duration', 'v': '{{duration}}', 'f': 'duration'},
        {'l': 'Grade', 'v': '{{grade}}', 'f': 'grade'},
        {'l': 'Batch', 'v': '{{class}}', 'f': 'class'},
        {'l': 'Roll / Regd. No.', 'v': '{{roll_no}}', 'f': 'roll_no'},
    ], 3.6, mode='table', lw=22, font='Montserrat', lc=MUTED, lwt=700, vc=INK, vwt=700, lh=1.6)
    # date + sign
    p.text(93, 165, 70, 8, '{{cert_date}}', 4.0, font='Montserrat', wt=700, c=INK, va='bottom')
    p.rect(93, 174, 60, 0.45, f=NAVY)
    p.text(93, 175.5, 70, 5, 'DATE OF ISSUE', 2.6, font='Montserrat', wt=700, c=TEAL, ls=0.16)
    p.img(215, 156, 60, 17, '{{sign}}', fit='contain')
    p.rect(215, 174, 60, 0.45, f=NAVY)
    p.text(205, 175.5, 80, 5, '{{signatory_title}}', 2.6, font='Montserrat', wt=700, c=TEAL, al='center', ls=0.12, up=True)
    p.text(205, 180.5, 80, 4.5, '{{signatory_name}}', 2.6, font='Montserrat', c=INK, al='center')
    p.img(176, 152, 30, 30, '{{seal}}', fit='contain', op=0.9)
    p.rect(0, 205, 297, 5, f={'g': 'lin', 'a': 90, 's': [[0, NAVY], [1, AQUA]]})
    return design('Modern Tech Completion', 'professional', CERT_TARGETS['course'], landscape={'front': p.side('#FFFFFF'), 'back': blank()})


if __name__ == '__main__':
    out = sys.argv[1]
    os.makedirs(out, exist_ok=True)
    for fn in (royal_gold, sambalpuri, ganesh, modern_tech):
        d = fn()
        name = fn.__name__.replace('_', '-')
        json.dump(d, open(os.path.join(out, name + '.json'), 'w'), ensure_ascii=False, separators=(',', ':'))
        print(name, len(d['layout']['landscape']['front']['els']), 'elements')
