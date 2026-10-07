# Changelog
## 1.0.8
- One Job Overview only: when the article has its own overview table it is kept (as a label | value card) and the theme-generated overview is hidden. Key/value tables keep label and value side by side on mobile, header row supported.

## 1.0.7
- Overview, two-column tables (Important Dates) and vacancy tables are now cards on the website and in the app/REST output (neutral inline styles for the app, works in light and dark). Big tables (13+ rows, 7+ columns) stay plain tables.

## 1.0.6
- All article tables now use the same plain style as Overview; narrow screens scroll sideways instead of breaking words.

## 1.0.5
- Overview is now always a plain two-column bordered table (no colours or zebra).

## 1.0.4
- App/REST: article content.rendered now has inline colours/backgrounds removed (setting in Control > General) so the app is readable in dark mode. Keys unchanged; no ads/TOC in REST.

## 1.0.3
- Article page redesign: quick-facts tiles, full-width justified text, centred tables, styled headings, Quick Links box moved to end (setting), empty ad slots collapse, app view ?np_app=1&theme=dark|light.

## 1.0.2
- Header/footer menu items named Result, Admit Card, Latest Jobs etc. now always open their category archive (switch in Control > General).

## 1.0.1
- Fix: reader text unreadable in dark mode (inline colours on any tag now stripped + CSS safety net), duplicate overview table removed, wide tables scroll cleanly on mobile, added color-scheme meta, additive REST field content_clean for the app.

## 1.0.0
- Initial release: skeleton, design tokens, homepage, list pages, reader, auto schema, Yoast integration, REST-safe compat, front-end Post Job form with approval queue, Control panel, lazy Adsterra slots, browser tools, performance pass.
