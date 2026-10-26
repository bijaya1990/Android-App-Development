// Render an HTML file to PDF with Chromium (Playwright).
// Usage: node tools/render.js <in.html> <out.pdf> [--no-footer] [--label "text"]
const path = require('path');
const { chromium } = require('playwright');

(async () => {
  const [, , input, output, ...rest] = process.argv;
  const noFooter = rest.includes('--no-footer');
  const li = rest.indexOf('--label');
  const label = li >= 0 ? rest[li + 1] : '';

  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto('file://' + path.resolve(input), { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);

  const footer = `
    <div style="width:100%;font-family:Inter,sans-serif;font-size:7.5px;color:#8a7a6a;
                padding:0 0.7in;display:flex;justify-content:space-between;">
      <span>${label}</span><span class="pageNumber"></span>
    </div>`;

  await page.pdf({
    path: output,
    width: '6in',
    height: '9in',
    printBackground: true,
    displayHeaderFooter: !noFooter,
    headerTemplate: '<div></div>',
    footerTemplate: noFooter ? '<div></div>' : footer,
    margin: noFooter
      ? { top: '0', bottom: '0', left: '0', right: '0' }
      : { top: '0.6in', bottom: '0.65in', left: '0.6in', right: '0.6in' },
    outline: true,
    tagged: true,
  });
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
