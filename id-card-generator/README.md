# Students' Union Election ID Card Generator

A single-file web app (`index.html`) that makes ID cards in the orange/charcoal
reference design. It needs no login and no server. All data stays in the
browser (IndexedDB).

![Reference (1st, 3rd) vs generated front and back (2nd, 4th)](docs/reference-vs-generated.jpg)

## Run it

Open `index.html` in Chrome, Edge or Firefox; double-clicking the file works.
The libraries (SheetJS, qrcode-generator, JsBarcode, jsPDF) and fonts
(Montserrat, Open Sans) load from a CDN. If you are offline and the `lib/` and
`js/fonts.js` files sit next to `index.html`, the app uses those instead, so it
also works without internet.

On first open the app adds an **Example College** with 5 sample members. Remove
it with **Clear example data**.

## How to use

1. **College.** Use the selector at the top to switch colleges or to add one
   with **+ New college**. In the profile, set the logo, college name, event
   heading, issue/expiry dates, ID prefix (default `SUE26-`), card size, terms
   (one per line), principal signature and seal. Changes save automatically and
   update every card at once. A live front/back preview sits next to the form.
2. **Members.** Add members with the form (photo, name, designation, role,
   10-digit mobile). The ID is generated automatically (`SUE26-001`, …) and you
   can edit it. Each row has Preview, Edit, Delete (click twice to confirm) and
   **Download ID Card** (JPG or PDF).
3. **Excel import.** Click **Download sample Excel**, fill it in (Name,
   Designation, Role, Mobile, Photo File Name, ID No), then choose the file.
   The preview marks rows with a missing name, a mobile number that is not
   10 digits, or a duplicate ID. Only good rows are imported.
4. **Photos in bulk.** Select many photos at once. Each one is matched by Photo
   File Name, then ID number, then Name. Case and file extension are ignored.
   The app reports how many photos matched and lists the ones that did not.
5. **A4 sheets.** These appear below the list and update on every change. Each
   page has **Print** and **Download in A4** (PDF or JPG). **Print all pages**
   and **Download all pages (PDF)** cover the whole list.
6. **Backup.** **Export backup** saves everything to one JSON file. **Import
   backup** restores it, for example on another computer.

## Card sizes and covers

Cards are always printed at real size and never scaled down. The A4 sheet fits
as many front + back pairs as the size allows. Equal margins are calculated
automatically, with 8 mm between front and back and 6 mm between rows.

| Size option | Card (mm) | Per A4 | Cover / pouch |
| --- | --- | --- | --- |
| CR80 / PVC card | 54 × 85.6 | 3 | standard CR80 holder or 65 × 95 mm pouch |
| 2.25 × 3.5 inch (default) | 56 × 88.9 | 3 | 2.25 × 3.5 inch holder or 65 × 95 mm pouch |
| Large, 3 per A4 | 57.8 × 91.7 | 3 | holder/pouch about 65 × 100 mm |
| 2.5 × 4 inch badge | 63.5 × 100.8 | 2 | 2.5 × 4 inch badge holder or 75 × 105 mm pouch |
| Custom height | any (50–140 mm tall) | auto | shown in the app |

The app also shows the minimum inner size of the cover for the chosen card.

## Output

- **Single card:** front and back side by side at real size, 600 DPI. The PDF
  page is sized so each side prints at the exact card size, and crop marks are
  included.
- **A4:** 210 × 297 mm at 300 DPI. Each row holds one member, with the front on
  the left and the back on the right. Dashed cut lines with scissors and crop
  marks sit outside the cards. If the last page is not full, the remaining rows
  stay blank.
- **QR:** a real QR code (error correction M, 2-module quiet zone, sharp black
  squares). It holds plain text: Name, Designation, Role, Mobile, ID No,
  College, Event, Valid (issue–expiry). The front and back show the same QR.
- **Barcode:** Code 128 of the ID number, drawn as whole-pixel bars.

Print at **100% / "Actual size"** with margins set to **None**. Do not use "Fit
to page".

## How it is built

Every card face is drawn on a `<canvas>` by one `drawCard()` function. The
on-screen previews, the single-card files, the A4 sheets and printing all use
it, so every export matches the preview. The header waves were traced from the
reference image.
