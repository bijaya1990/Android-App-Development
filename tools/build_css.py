#!/usr/bin/env python3
"""Build minified CSS bundles for the PikaCart theme from style.css.

app.min.css  = everything except the "Dashboards" section (loaded on every page)
dash.min.css = cart/checkout, auth and dashboard sections (loaded only on those routes)
Run: python3 tools/build_css.py
"""
import os, re

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'digimarket')
src = open(os.path.join(ROOT, 'style.css'), encoding='utf-8').read()
marker = '/* ==========================================================================\n'

# Drop the theme header comment.
src = src[src.index('*/') + 2:]
dash = ''
app = src
for name in ('Cart / checkout', 'Auth & onboarding', 'Dashboards'):
    key = marker + '   ' + name + '\n'
    a = app.index(key)
    b = app.index(marker, a + len(key) + 80)
    dash += app[a:b]
    app = app[:a] + app[b:]


def minify(css):
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    css = re.sub(r'\s+', ' ', css)
    css = re.sub(r'\s*([{};,>])\s*', r'\1', css)
    css = css.replace(';}', '}')
    # url(assets/...) paths are relative to the theme root; bundles live in assets/css/.
    css = css.replace('url(assets/', 'url(../')
    return css.strip() + '\n'


out = os.path.join(ROOT, 'assets', 'css')
open(os.path.join(out, 'app.min.css'), 'w', encoding='utf-8').write(minify(app))
open(os.path.join(out, 'dash.min.css'), 'w', encoding='utf-8').write(minify(dash))
print('app.min.css', len(minify(app)), 'bytes; dash.min.css', len(minify(dash)), 'bytes')
