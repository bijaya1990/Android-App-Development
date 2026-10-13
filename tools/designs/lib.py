"""
Helpers to write Pikacart certificate designs in millimetres.

A design is drawn on a page of W x H mm; every element is converted to the
renderer's percentages (x, y, w, h in % of the page; sizes in "u" = 1% of
the short side). Run make_certificates.py to write the JSON files.
"""
import json, math

class Page:
    def __init__(self, w=297, h=210, prefix='c'):
        self.W, self.H, self.els, self.n, self.prefix = w, h, [], 0, prefix
        self.U = min(w, h) / 100.0  # mm per "u"

    def _id(self):
        self.n += 1
        return '%s%03d' % (self.prefix, self.n)

    def box(self, x, y, w, h):
        r = lambda v: round(v, 3)
        return {'x': r(x / self.W * 100), 'y': r(y / self.H * 100), 'w': r(w / self.W * 100), 'h': r(h / self.H * 100)}

    def u(self, mm):
        return round(mm / self.U, 3)

    def add(self, t, x, y, w, h, **k):
        e = {'id': self._id(), 't': t}
        e.update(self.box(x, y, w, h))
        e.update(k)
        self.els.append(e)
        return e

    # Shapes -------------------------------------------------------------
    def rect(self, x, y, w, h, **k):
        return self.add('rect', x, y, w, h, **k)

    def circle(self, cx, cy, r, **k):
        return self.add('ellipse', cx - r, cy - r, 2 * r, 2 * r, **k)

    def frame(self, x, y, w, h, t, f, **k):
        """Four bars forming a border of thickness t (mm)."""
        self.rect(x, y, w, t, f=f, **k)
        self.rect(x, y + h - t, w, t, f=f, **k)
        self.rect(x, y + t, t, h - 2 * t, f=f, **k)
        self.rect(x + w - t, y + t, t, h - 2 * t, f=f, **k)

    def path(self, x, y, w, h, pts_mm, close=True, **k):
        """Path from absolute mm points [(px,py) or ('C',c1x,c1y,c2x,c2y,x,y)] inside box."""
        def P(px, py):
            return '%s %s' % (round((px - x) / w * 100, 3), round((py - y) / h * 100, 3))
        d, first = [], True
        for p in pts_mm:
            if p == 'Z':
                d.append('Z'); first = True; continue
            if p[0] == 'M':
                d.append('M ' + P(p[1], p[2])); first = False; continue
            if p[0] == 'C':
                d.append('C %s %s %s' % (P(p[1], p[2]), P(p[3], p[4]), P(p[5], p[6])))
            elif p[0] == 'Q':
                d.append('Q %s %s' % (P(p[1], p[2]), P(p[3], p[4])))
            else:
                d.append(('M ' if first else 'L ') + P(p[0], p[1])); first = False
        if close:
            d.append('Z')
        return self.add('path', x, y, w, h, d=' '.join(d), **k)

    def poly(self, pts, **k):
        xs = [p[0] for p in pts]; ys = [p[1] for p in pts]
        x0, y0 = min(xs), min(ys)
        w, h = max(max(xs) - x0, 0.01), max(max(ys) - y0, 0.01)
        return self.path(x0, y0, w, h, pts, **k)

    def star(self, cx, cy, r1, r2, n, rot=-90, **k):
        pts = []
        for i in range(2 * n):
            r = r1 if i % 2 == 0 else r2
            a = math.radians(rot + i * 180.0 / n)
            pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
        return self.poly(pts, **k)

    def diamond(self, cx, cy, rw, rh, **k):
        return self.poly([(cx, cy - rh), (cx + rw, cy), (cx, cy + rh), (cx - rw, cy)], **k)

    def ikat(self, cx, cy, r, steps=3, **k):
        """Stepped diamond with jagged edges, like the blurred threads of bandha."""
        s = r / steps
        pts = []
        for i in range(steps):          # top -> right
            pts += [(cx + i * s, cy - r + i * s), (cx + (i + 1) * s, cy - r + i * s)]
        for i in range(steps):          # right -> bottom
            pts += [(cx + r - i * s, cy + i * s), (cx + r - i * s, cy + (i + 1) * s)]
        for i in range(steps):          # bottom -> left
            pts += [(cx - i * s, cy + r - i * s), (cx - (i + 1) * s, cy + r - i * s)]
        for i in range(steps):          # left -> top
            pts += [(cx - r + i * s, cy - i * s), (cx - r + i * s, cy - (i + 1) * s)]
        return self.poly(pts, **k)

    def flower(self, cx, cy, r, petals=8, petal_c='#F59E0B', core_c='#B45309', **k):
        for i in range(petals):
            a = 2 * math.pi * i / petals
            px, py = cx + r * 0.55 * math.cos(a), cy + r * 0.55 * math.sin(a)
            self.circle(px, py, r * 0.42, f=petal_c, **k)
        self.circle(cx, cy, r * 0.38, f=core_c, **k)

    def leaf(self, cx, cy, l, wid, ang, **k):
        a = math.radians(ang)
        tip = (cx + l * math.cos(a), cy + l * math.sin(a))
        nx, ny = -math.sin(a) * wid, math.cos(a) * wid
        mid = (cx + l * 0.5 * math.cos(a), cy + l * 0.5 * math.sin(a))
        pts = [('M', cx, cy), ('Q', mid[0] + nx, mid[1] + ny, tip[0], tip[1]), ('Q', mid[0] - nx, mid[1] - ny, cx, cy)]
        xs = [cx, tip[0], mid[0] + nx, mid[0] - nx]; ys = [cy, tip[1], mid[1] + ny, mid[1] - ny]
        x0, y0 = min(xs), min(ys)
        return self.path(x0, y0, max(max(xs) - x0, .01), max(max(ys) - y0, .01), pts, **k)

    # Content ------------------------------------------------------------
    def text(self, x, y, w, h, text, size_mm, **k):
        return self.add('text', x, y, w, h, text=text, size=self.u(size_mm), **k)

    def img(self, x, y, w, h, src, **k):
        return self.add('img', x, y, w, h, src=src, **k)

    def qr(self, x, y, s, **k):
        return self.add('qr', x, y, s, s, v='{{verify_url}}', **k)

    def stack(self, x, y, w, h, rows, size_mm, **k):
        return self.add('stack', x, y, w, h, rows=rows, size=self.u(size_mm), **k)

    def side(self, bg):
        return {'bg': bg, 'els': self.els}


def design(name, level, targets, landscape=None, portrait=None):
    layout = {}
    if portrait:
        layout['portrait'] = portrait
    if landscape:
        layout['landscape'] = landscape
    return {'pikacart_template': 1, 'name': name, 'level': level, 'targets': targets, 'palettes': [], 'layout': layout}


def blank():
    return {'bg': '#FFFFFF', 'els': []}
