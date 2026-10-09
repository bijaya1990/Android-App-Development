# SU Election ID Card Generator – WordPress plugin

Ready-to-install file: [`dist/su-id-card-generator.zip`](dist/su-id-card-generator.zip)

## Install

1. WordPress admin → **Plugins → Add New → Upload Plugin** → choose
   `su-id-card-generator.zip` → **Install Now** → **Activate**.
2. A page **ID Card Generator** is created, e.g. `https://your-site.com/id-card-generator/`.
   The link is shown right after activation and under **Settings → ID Card Generator**.
3. Put it in your menu:
   - **Settings → ID Card Generator → "Add link to menu"** (one click), or
   - **Appearance → Menus** → "Pages" → tick **ID Card Generator** → Add to Menu → Save, or
   - block themes: **Appearance → Editor → Navigation** → add a Page Link.

## Extras

- `[id_card_button text="Make ID Cards"]` shows an orange button that opens the
  generator. Add `newtab="yes"` to open it in a new tab.
- `[id_card_generator height="1400"]` embeds the generator inside a normal page.
- Settings → ID Card Generator → **Only logged-in users** limits access to
  logged-in users.

The generator page opens full-screen, outside the theme, so theme styles cannot
change the cards. Card data stays in each user's browser (IndexedDB). Use
"Export backup" / "Import backup" in the generator to move it. Deleting the
plugin removes its page and settings.

## Build

`sh wordpress-plugin/build.sh` rebuilds the zip from `su-id-card-generator/`
and the app in `../id-card-generator/` (index.html, lib/, js/fonts.js).
