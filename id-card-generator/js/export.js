/*
 * Exports: single card (JPG / PDF) and A4 sheets (4 members per page,
 * front left / back right, cut lines and crop marks).
 * Faces are rasterised from the same DOM as the preview at 2x the design
 * grid, which is about 520 DPI at real CR80 size and higher on the A4 sheet.
 */
(function () {
  'use strict';

  const PIXEL_RATIO = 2;
  const CR80 = { w: 54, h: 85.6 }; // portrait, mm
  const A4 = { w: 210, h: 297 };
  const ROWS = 4;
  const MIN_GAP = 7; // mm, leaves room for cut lines and crop marks
  const MARK_OFFSET = 1;
  const MARK_LEN = 2;
  const CUT_DASH = [2, 1.5];
  const CUT_COLOR = '#8a8a8a';

  /* ---------- A4 geometry (shared by preview and PDF) ---------- */

  function a4Geometry() {
    let h = CR80.h;
    let w = CR80.w;
    if (ROWS * h + (ROWS + 1) * MIN_GAP > A4.h) {
      h = (A4.h - (ROWS + 1) * MIN_GAP) / ROWS;
      w = h * CR80.w / CR80.h;
    }
    const gapY = (A4.h - ROWS * h) / (ROWS + 1);
    const gapX = (A4.w - 2 * w) / 3;
    return { w, h, gapX, gapY };
  }

  // Everything drawn on one A4 page for `count` members (1..4).
  function a4Layout(count) {
    const g = a4Geometry();
    const slots = [];
    const marks = [];
    for (let r = 0; r < count; r++) {
      const y = g.gapY + r * (g.h + g.gapY);
      ['front', 'back'].forEach((side, c) => {
        const x = g.gapX + c * (g.w + g.gapX);
        slots.push({ row: r, side, x, y });
        [[x, y, -1, -1], [x + g.w, y, 1, -1], [x, y + g.h, -1, 1], [x + g.w, y + g.h, 1, 1]].forEach(([cx, cy, dx, dy]) => {
          marks.push([cx + dx * MARK_OFFSET, cy, cx + dx * (MARK_OFFSET + MARK_LEN), cy]);
          marks.push([cx, cy + dy * MARK_OFFSET, cx, cy + dy * (MARK_OFFSET + MARK_LEN)]);
        });
      });
    }
    const usedBottom = count ? g.gapY + count * (g.h + g.gapY) - g.gapY / 2 : 0;
    const cutsH = [];
    for (let r = 1; r <= count; r++) {
      const y = g.gapY + r * (g.h + g.gapY) - g.gapY / 2;
      if (r < ROWS) cutsH.push(y);
    }
    const cutV = count ? { x: A4.w / 2, y1: 0, y2: Math.min(A4.h, usedBottom) } : null;
    return { geo: g, slots, marks, cutsH, cutV };
  }

  // Scissors in a 6 x 3.6 mm box pointing +x; (ox, oy) is the pivot side.
  function scissorsPrims(ox, oy, vertical) {
    const t = (x, y) => (vertical ? [ox - y, oy + x] : [ox + x, oy + y]);
    return {
      circles: [[...t(0.9, -0.95), 0.75], [...t(0.9, 0.95), 0.75]],
      lines: [[...t(1.55, -0.55), ...t(6, 0.95)], [...t(1.55, 0.55), ...t(6, -0.95)]],
    };
  }

  function cutPieces(layout) {
    const pieces = { dashes: [], scissors: [] };
    layout.cutsH.forEach((y) => {
      pieces.dashes.push([9, y, A4.w - 3, y]);
      pieces.scissors.push(scissorsPrims(2, y, false));
    });
    if (layout.cutV) {
      pieces.dashes.push([layout.cutV.x, 9, layout.cutV.x, layout.cutV.y2]);
      pieces.scissors.push(scissorsPrims(layout.cutV.x, 2, true));
    }
    return pieces;
  }

  // SVG overlay (mm units) for the on-screen A4 preview.
  function a4OverlaySvg(count) {
    const L = a4Layout(count);
    const pieces = cutPieces(L);
    let s = `<svg class="a4-overlay" viewBox="0 0 ${A4.w} ${A4.h}" preserveAspectRatio="none">`;
    pieces.dashes.forEach(([x1, y1, x2, y2]) => {
      s += `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${CUT_COLOR}" stroke-width="0.25" stroke-dasharray="${CUT_DASH.join(' ')}"/>`;
    });
    pieces.scissors.forEach((p) => {
      p.circles.forEach(([cx, cy, r]) => { s += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#555" stroke-width="0.3"/>`; });
      p.lines.forEach(([x1, y1, x2, y2]) => { s += `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#555" stroke-width="0.3" stroke-linecap="round"/>`; });
    });
    L.marks.forEach(([x1, y1, x2, y2]) => {
      s += `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#000" stroke-width="0.2"/>`;
    });
    return s + '</svg>';
  }

  /* ---------- rasterising ---------- */

  const rasterCache = new Map();

  async function faceCanvas(profile, member, side, key) {
    const el = IDCard.buildFace(profile, member, side, key);
    const host = document.getElementById('idc-measure-host');
    host.appendChild(el);
    try {
      const opts = {
        pixelRatio: PIXEL_RATIO, width: IDCard.W, height: IDCard.H,
        backgroundColor: '#ffffff', fontEmbedCSS: window.IDCARD_FONT_CSS,
        skipAutoScale: true, cacheBust: false,
      };
      // Safari sometimes misses images on the first pass of a fresh clone.
      if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) await htmlToImage.toCanvas(el, opts);
      return await htmlToImage.toCanvas(el, opts);
    } finally {
      el.remove();
    }
  }

  async function faceJpeg(profile, member, side, key) {
    const k = key ? `${key}|jpg` : null;
    if (k && rasterCache.has(k)) return rasterCache.get(k);
    const canvas = await faceCanvas(profile, member, side, key);
    const url = canvas.toDataURL('image/jpeg', 0.95);
    if (k) {
      if (rasterCache.size > 80) rasterCache.delete(rasterCache.keys().next().value);
      rasterCache.set(k, url);
    }
    return url;
  }

  /* ---------- file helpers ---------- */

  function safeName(s) {
    return String(s || '').trim().replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g, '_') || 'card';
  }

  function saveBlob(blob, filename) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 4000);
  }

  // Writes the real print density into the JPEG's JFIF header so the file
  // prints at true card size.
  function setJpegDpi(buf, dpi) {
    const b = new Uint8Array(buf);
    if (b[2] === 0xff && b[3] === 0xe0 && String.fromCharCode(b[6], b[7], b[8], b[9]) === 'JFIF') {
      b[13] = 1;
      b[14] = dpi >> 8; b[15] = dpi & 255;
      b[16] = dpi >> 8; b[17] = dpi & 255;
    }
    return b;
  }

  function loadImage(src) {
    return new Promise((resolve, reject) => {
      const i = new Image();
      i.onload = () => resolve(i);
      i.onerror = reject;
      i.src = src;
    });
  }

  /* ---------- single card ---------- */

  async function downloadCard(profile, member, format, keyOf) {
    const name = `${safeName(member.name)}_${safeName(member.idNo)}`;
    const front = await faceJpeg(profile, member, 'front', keyOf && keyOf('front'));
    const back = await faceJpeg(profile, member, 'back', keyOf && keyOf('back'));
    if (format === 'pdf') {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'p', unit: 'mm', format: [CR80.w, CR80.h], compress: true });
      doc.addImage(front, 'JPEG', 0, 0, CR80.w, CR80.h, undefined, 'NONE');
      doc.addPage([CR80.w, CR80.h], 'p');
      doc.addImage(back, 'JPEG', 0, 0, CR80.w, CR80.h, undefined, 'NONE');
      doc.setProperties({ title: name });
      doc.save(name + '.pdf');
      return;
    }
    const [fi, bi] = await Promise.all([loadImage(front), loadImage(back)]);
    const fw = fi.naturalWidth;
    const fh = fi.naturalHeight;
    const gap = Math.round(fw * 0.08);
    const canvas = document.createElement('canvas');
    canvas.width = fw * 2 + gap * 3;
    canvas.height = fh + gap * 2;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(fi, gap, gap);
    ctx.drawImage(bi, gap * 2 + fw, gap);
    const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', 0.95));
    const dpi = Math.round(fw / (CR80.w / 25.4));
    saveBlob(new Blob([setJpegDpi(await blob.arrayBuffer(), dpi)], { type: 'image/jpeg' }), name + '.jpg');
  }

  /* ---------- A4 sheets ---------- */

  function pageCount(n) {
    return Math.max(1, Math.ceil(n / ROWS));
  }

  async function drawA4Page(doc, profile, members, keyOf, progress) {
    const L = a4Layout(members.length);
    const g = L.geo;
    for (const slot of L.slots) {
      const m = members[slot.row];
      const url = await faceJpeg(profile, m, slot.side, keyOf && keyOf(m, slot.side));
      doc.addImage(url, 'JPEG', slot.x, slot.y, g.w, g.h, undefined, 'NONE');
      if (progress) progress();
    }
    const pieces = cutPieces(L);
    doc.setDrawColor(CUT_COLOR);
    doc.setLineWidth(0.25);
    doc.setLineDashPattern(CUT_DASH, 0);
    pieces.dashes.forEach(([x1, y1, x2, y2]) => doc.line(x1, y1, x2, y2));
    doc.setLineDashPattern([], 0);
    doc.setDrawColor('#555555');
    doc.setLineWidth(0.3);
    doc.setLineCap('round');
    pieces.scissors.forEach((p) => {
      p.circles.forEach(([cx, cy, r]) => doc.circle(cx, cy, r, 'S'));
      p.lines.forEach(([x1, y1, x2, y2]) => doc.line(x1, y1, x2, y2));
    });
    doc.setLineCap('butt');
    doc.setDrawColor('#000000');
    doc.setLineWidth(0.2);
    L.marks.forEach(([x1, y1, x2, y2]) => doc.line(x1, y1, x2, y2));
  }

  // pages: array of page indexes (0-based) to include, in order.
  async function downloadA4(profile, allMembers, pages, keyOf, onProgress) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4', compress: true });
    const total = pages.reduce((n, p) => n + allMembers.slice(p * ROWS, p * ROWS + ROWS).length * 2, 0);
    let done = 0;
    for (let i = 0; i < pages.length; i++) {
      if (i > 0) doc.addPage('a4', 'p');
      const members = allMembers.slice(pages[i] * ROWS, pages[i] * ROWS + ROWS);
      await drawA4Page(doc, profile, members, keyOf, () => onProgress && onProgress(++done, total));
    }
    const base = `IDCards_${safeName(profile.name)}`;
    const suffix = pages.length === 1 ? `_Page${pages[0] + 1}` : '_AllPages';
    doc.setProperties({ title: base + suffix });
    doc.save(base + suffix + '.pdf');
  }

  window.IDExport = {
    ROWS, CR80, A4, a4Geometry, a4Layout, a4OverlaySvg, pageCount,
    faceCanvas, downloadCard, downloadA4,
  };
})();
