=== SU Election ID Card Generator ===
Tags: id card, election, qr code, barcode, print
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later

Students' Union Election ID card generator with college profiles, members, Excel import, real QR codes, Code 128 barcodes and A4 print sheets.

== Description ==

On activation the plugin creates a page called "ID Card Generator". Open
Settings → ID Card Generator to copy its link or add it to a menu with one click.

* College profiles: logo, name, event heading, terms, dates, signature, seal, ID prefix, card size.
* Members: manual form or Excel/CSV import, bulk photo upload matched by file name.
* Real QR code (member details as text) and Code 128 barcode on every card.
* Download a single card (JPG/PDF) or A4 sheets at real size with cut lines; print buttons.
* Card sizes: CR80, 2.25 × 3.5 in, large (3 per A4), 2.5 × 4 in, custom.
* Data is stored in each user's browser (IndexedDB), with JSON backup export/import.

Shortcodes:

* `[id_card_button text="Make ID Cards"]` – an orange button that opens the generator (`newtab="yes"` opens a new tab).
* `[id_card_generator height="1400"]` – the generator embedded inside any page.

== Installation ==

1. Plugins → Add New → Upload Plugin → choose `su-id-card-generator.zip` → Install Now → Activate.
2. Settings → ID Card Generator → "Add link to menu" (or Appearance → Menus → Pages → "ID Card Generator").

== Changelog ==

= 1.0.0 =
* First release.
