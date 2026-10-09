/*
 * Card renderer. Builds the front and back faces as DOM in fixed 554 x 880
 * design units (the reference card's own pixel grid), fits long text into
 * its box, and renders the real QR code and Code 128 barcode as SVG.
 * The same DOM is used for the on-screen preview and for every export.
 */
(function () {
  'use strict';

  const W = 554;
  const H = 880;
  const C = {
    dark: '#2B2B2B',
    orange: '#F27930',
    amber: '#F19D19',
    name: '#F09C1E',
    text: '#434343',
    soft: '#6B6B6B',
    qrBorder: '#CFCFCF',
  };
  const P = window.IDCARD_SHAPES;
  const SVG_NS = 'http://www.w3.org/2000/svg';
  // Exports render at 2x the design grid; QR and barcode modules are sized in
  // half units so every module lands on whole output pixels.
  const RASTER_STEP = 0.5;

  qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];

  const DEFAULT_LABELS = {
    idNo: 'ID NO',
    role: 'ROLE',
    mobile: 'MOBILE',
    terms: 'Terms & Conditions:',
    issue: 'Issue Date',
    expiry: 'Expiry Date',
    principal: 'Principal',
  };

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  function fmtDate(iso) {
    if (!iso) return '';
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : iso;
  }

  function labels(profile) {
    return Object.assign({}, DEFAULT_LABELS, profile.labels || {});
  }

  /* ---------- QR code ---------- */

  function qrText(profile, member) {
    return [
      `Name: ${member.name || ''}`,
      `Designation: ${member.designation || ''}`,
      `Role: ${member.role || ''}`,
      `Mobile: ${member.mobile || ''}`,
      `ID No: ${member.idNo || ''}`,
      `College: ${profile.name || ''}`,
      `Event: ${profile.event || ''}`,
      `Issue Date: ${fmtDate(profile.issueDate)}`,
      `Expiry Date: ${fmtDate(profile.expiryDate)}`,
    ].join('\n');
  }

  // Square SVG QR (error correction M) no larger than box. `quiet` is the
  // white margin in modules drawn inside the SVG; with 0 the card's own
  // white space around the code must provide the 4-module quiet zone.
  function qrSvg(text, box, quiet) {
    const qr = qrcode(0, 'M');
    qr.addData(text, 'Byte');
    qr.make();
    const n = qr.getModuleCount();
    const total = n + quiet * 2;
    // fill the box: bigger modules scan better than whole-pixel alignment
    const size = box;
    let d = '';
    for (let r = 0; r < n; r++) {
      for (let c = 0; c < n; c++) {
        if (qr.isDark(r, c)) d += `M${c + quiet} ${r + quiet}h1v1h-1z`;
      }
    }
    return `<svg xmlns="${SVG_NS}" width="${size}" height="${size}" viewBox="0 0 ${total} ${total}" shape-rendering="crispEdges">` +
      `<rect width="${total}" height="${total}" fill="#fff"/><path d="${d}" fill="#000"/></svg>`;
  }

  /* ---------- Code 128 barcode ---------- */

  function barcodeSvg(text, boxW, boxH) {
    if (!text) return '';
    const svg = document.createElementNS(SVG_NS, 'svg');
    try {
      JsBarcode(svg, text, {
        format: 'CODE128', width: 1, height: boxH, margin: 0,
        displayValue: false, background: '#ffffff', lineColor: '#000000',
      });
    } catch (e) {
      return '';
    }
    const modules = parseFloat(svg.getAttribute('width'));
    const quiet = 10;
    const total = modules + quiet * 2;
    const unit = Math.max(RASTER_STEP, Math.floor(boxW / total / RASTER_STEP) * RASTER_STEP);
    svg.setAttribute('viewBox', `${-quiet} 0 ${total} ${boxH}`);
    svg.setAttribute('width', unit * total);
    svg.setAttribute('height', boxH);
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.setAttribute('shape-rendering', 'crispEdges');
    svg.removeAttribute('style');
    return svg.outerHTML;
  }

  /* ---------- pieces ---------- */

  const PERSON_SVG =
    `<svg xmlns="${SVG_NS}" viewBox="0 0 100 100" width="100%" height="100%">` +
    '<rect width="100" height="100" fill="#E6E6E6"/>' +
    '<circle cx="50" cy="38" r="18" fill="#BDBDBD"/>' +
    '<path d="M14 100c2-22 17-34 36-34s34 12 36 34z" fill="#BDBDBD"/></svg>';

  function img(src, cls) {
    return src ? `<img class="${cls}" src="${src}" alt="">` : '';
  }

  function bgSvg(side) {
    const k = side === 'front' ? 'front' : 'back';
    let extra = '';
    if (side === 'front') {
      extra = `<circle cx="146.5" cy="219" r="94.5" fill="${C.amber}"/>` +
        `<circle cx="146.5" cy="219" r="86" fill="${C.orange}"/>`;
    }
    return `<svg class="idc-bg" xmlns="${SVG_NS}" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">` +
      `<path d="${P[k + '3']}" fill="${C.amber}"/>` +
      `<path d="${P[k + '2']}" fill="${C.orange}"/>` +
      `<path d="${P[k + '1']}" fill="${C.dark}"/>` +
      `<rect x="0" y="827" width="277" height="53" fill="${C.dark}"/>` +
      `<rect x="277" y="827" width="138" height="53" fill="${C.amber}"/>` +
      `<rect x="415" y="827" width="139" height="53" fill="${C.orange}"/>` +
      extra + '</svg>';
  }

  function header(profile, cx) {
    const left = cx - 116;
    return `<div class="idc-logo" style="left:${cx - 75}px;top:62px;width:150px;height:60px">${img(profile.logo, '')}</div>` +
      `<div class="idc-fit idc-head" data-wrap-at="0.8" style="left:${left}px;top:132px;width:232px;height:86px">` +
      `<div class="idc-cname" data-max="25.5" data-min="10">${esc(profile.name)}</div>` +
      `<div class="idc-event" data-max="16.8" data-min="8">${esc(profile.event)}</div></div>`;
  }

  function signature(profile, L, box) {
    // box: {x, w, sigTop, sigH, line, sealX, sealY, sealS}
    return `<div class="idc-seal" style="left:${box.sealX}px;top:${box.sealY}px;width:${box.sealS}px;height:${box.sealS}px">${img(profile.seal, '')}</div>` +
      `<div class="idc-sig" style="left:${box.x}px;top:${box.sigTop}px;width:${box.w}px;height:${box.sigH}px">${img(profile.signature, '')}</div>` +
      `<div class="idc-sigline" style="left:${box.x}px;top:${box.line}px;width:${box.w}px"></div>` +
      `<div class="idc-fit idc-principal-box" style="left:${box.x}px;top:${box.line + 6}px;width:${box.w}px;height:30px">` +
      `<div class="idc-principal" data-max="22" data-min="10">${esc(L.principal)}</div></div>`;
  }

  // framed: grey border like the reference card, with the quiet zone inside it.
  function qrBox(text, x, y, size, framed) {
    let svg;
    try {
      svg = framed ? qrSvg(text, size - 8, 4) : qrSvg(text, size, 0);
    } catch (e) {
      svg = '<div class="idc-qr-error">Text too long for QR</div>';
    }
    return `<div class="idc-qr${framed ? ' framed' : ''}" style="left:${x}px;top:${y}px;width:${size}px;height:${size}px">${svg}</div>`;
  }

  /* ---------- faces ---------- */

  function frontHTML(profile, member) {
    const L = labels(profile);
    const photo = member.photo
      ? `<img src="${member.photo}" alt="">`
      : PERSON_SVG;
    const rows = [
      [L.idNo, member.idNo],
      [L.role, member.role],
      [L.mobile, member.mobile],
    ].map(([label, value], i) => {
      const top = 484 + i * 49;
      return `<div class="idc-fit" style="left:40px;top:${top}px;width:146px;height:37px">` +
        `<div class="idc-label" data-max="29" data-min="12">${esc(label)}</div></div>` +
        `<div class="idc-colon" style="left:186px;top:${top - 2}px">:</div>` +
        `<div class="idc-fit" data-wrap-at="0.7" style="left:211px;top:${top}px;width:303px;height:44px">` +
        `<div class="idc-value" data-max="28.5" data-min="11">${esc(value)}</div></div>`;
    }).join('');

    return bgSvg('front') +
      `<div class="idc-photo" style="left:69.5px;top:142px;width:154px;height:154px">${photo}</div>` +
      header(profile, 407) +
      '<div class="idc-fit idc-namegrp" data-wrap-at="0.72" style="left:40px;top:349px;width:474px;height:91px">' +
      `<div class="idc-name" data-max="46.5" data-min="16">${esc(member.name)}</div>` +
      `<div class="idc-desig" data-max="22" data-min="10">${esc(member.designation)}</div></div>` +
      '<div class="idc-divider" style="left:40px;top:446px;width:228px"></div>' +
      rows +
      qrBox(qrText(profile, member), 40, 622, 156, false) +
      signature(profile, L, { x: 290, w: 224, sigTop: 682, sigH: 60, line: 745, sealX: 412, sealY: 632, sealS: 104 }) +
      `<div class="idc-barcode" style="left:40px;top:785px;width:474px;height:36px">${barcodeSvg(member.idNo, 474, 36)}</div>`;
  }

  function backHTML(profile, member) {
    const L = labels(profile);
    const terms = String(profile.terms || '')
      .split(/\r?\n/).map((t) => t.trim()).filter(Boolean)
      .map((t) => `<div class="idc-term" data-max="20" data-min="7"><span class="idc-term-text">${esc(t)}</span>` +
        `<svg class="idc-bullet" xmlns="${SVG_NS}" viewBox="0 0 27 27"><circle cx="13.5" cy="13.5" r="11.6" fill="none" stroke="${C.amber}" stroke-width="3.6"/>` +
        `<circle cx="13.5" cy="13.5" r="5.6" fill="${C.amber}"/></svg></div>`)
      .join('');

    return bgSvg('back') +
      header(profile, 146) +
      qrBox(qrText(profile, member), 339, 145, 181, true) +
      '<div class="idc-fit" style="left:40px;top:330px;width:460px;height:38px">' +
      `<div class="idc-tc-title" data-max="29" data-min="12">${esc(L.terms)}</div></div>` +
      `<div class="idc-fit idc-terms" data-wrap-at="1" data-gap="28" style="left:40px;top:384px;width:460px;height:242px">${terms}</div>` +
      '<div class="idc-fit" style="left:30px;top:636px;width:494px;height:34px">' +
      `<div class="idc-date" data-max="26.8" data-min="11"><b>${esc(L.issue)}</b>&nbsp; : &nbsp;${esc(fmtDate(profile.issueDate))}</div></div>` +
      '<div class="idc-fit" style="left:30px;top:681px;width:494px;height:34px">' +
      `<div class="idc-date" data-max="26.8" data-min="11"><b>${esc(L.expiry)}</b> : ${esc(fmtDate(profile.expiryDate))}</div></div>` +
      signature(profile, L, { x: 248, w: 254, sigTop: 716, sigH: 56, line: 775, sealX: 430, sealY: 707, sealS: 70 });
  }

  /* ---------- text fitting ---------- */

  // Shrinks the [data-max] children of a fixed-size box until nothing
  // overflows: first on one line (down to data-wrap-at of full size), then
  // allowing wrapping, never below each child's data-min.
  function fitBox(box) {
    const items = Array.from(box.querySelectorAll('[data-max]'));
    if (!items.length) return;
    const wrapAt = parseFloat(box.dataset.wrapAt || '0.6');
    const gap = parseFloat(box.dataset.gap || '0');
    const minScale = Math.min(...items.map((el) => +el.dataset.min / +el.dataset.max));
    const fits = () => box.scrollHeight <= box.clientHeight + 0.5 &&
      items.every((el) => el.scrollWidth <= el.clientWidth + 0.5);
    const apply = (s) => {
      for (const el of items) el.style.fontSize = Math.max(+el.dataset.min, +el.dataset.max * s) + 'px';
      if (gap) box.style.gap = gap * s + 'px';
    };
    const setWrap = (on) => {
      for (const el of items) el.style.whiteSpace = on ? 'normal' : 'nowrap';
    };
    if (wrapAt < 1) {
      setWrap(false);
      for (let s = 1; s >= wrapAt - 1e-6; s -= 0.02) {
        apply(s);
        if (fits()) return;
      }
    }
    setWrap(true);
    for (let s = Math.min(1, wrapAt); s >= minScale - 0.02; s -= 0.02) {
      apply(s);
      if (fits()) return;
    }
    apply(minScale);
  }

  /* ---------- public ---------- */

  let host = null;
  function measureHost() {
    if (!host) {
      host = document.createElement('div');
      host.id = 'idc-measure-host';
      document.body.appendChild(host);
    }
    return host;
  }

  const htmlCache = new Map();

  // Returns a fitted, detached card element. Pass a cacheKey to reuse the
  // fitted markup of an unchanged card.
  function buildFace(profile, member, side, cacheKey) {
    const el = document.createElement('div');
    el.className = 'idc idc-' + side;
    el.style.width = W + 'px';
    el.style.height = H + 'px';
    if (cacheKey && htmlCache.has(cacheKey)) {
      el.innerHTML = htmlCache.get(cacheKey);
      return el;
    }
    el.innerHTML = side === 'front' ? frontHTML(profile, member) : backHTML(profile, member);
    measureHost().appendChild(el);
    el.querySelectorAll('.idc-fit').forEach(fitBox);
    el.remove();
    if (cacheKey) {
      if (htmlCache.size > 600) htmlCache.delete(htmlCache.keys().next().value);
      htmlCache.set(cacheKey, el.innerHTML);
    }
    return el;
  }

  // Resolves once the embedded card fonts are ready for measuring.
  async function ready() {
    if (!document.getElementById('idc-font-css')) {
      const style = document.createElement('style');
      style.id = 'idc-font-css';
      style.textContent = window.IDCARD_FONT_CSS;
      document.head.appendChild(style);
    }
    const loads = [
      '400 20px Montserrat', '500 20px Montserrat', '600 20px Montserrat', '700 20px Montserrat',
      '400 20px "Open Sans"', '500 20px "Open Sans"',
    ].map((f) => document.fonts.load(f, 'Aa'));
    await Promise.all(loads);
    await document.fonts.ready;
  }

  window.IDCard = {
    W, H, COLORS: C, DEFAULT_LABELS,
    buildFace, ready, qrText, fmtDate, esc,
    clearCache: () => htmlCache.clear(),
  };
})();
