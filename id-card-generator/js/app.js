/* ID Card Generator - UI: college profiles, members, Excel import, previews, downloads. */
(function () {
  'use strict';

  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const esc = IDCard.esc;

  const state = {
    profiles: [],
    members: [],
    pid: null,
    selectedId: null,
    editingId: null,
    formPhoto: null,
    search: '',
    imp: { rows: [], photos: new Map(), fileName: '' },
  };

  const DEFAULT_EVENT = "Students' Union Election 2026";
  const DEFAULT_TERMS = [
    'This card is valid only for the Students\' Union Election 2026.',
    'Carry this card at all times inside the campus and show it on demand.',
    'This card is not transferable. Misuse will lead to cancellation.',
    'If found, please return it to the college office.',
  ].join('\n');
  const LABEL_NAMES = {
    idNo: 'ID number label', role: 'Role label', mobile: 'Mobile label', terms: 'Terms heading',
    issue: 'Issue date label', expiry: 'Expiry date label', principal: 'Signature label',
  };

  /* ================= helpers ================= */

  function uid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return Date.now().toString(36) + Math.random().toString(36).slice(2);
  }

  function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  }

  function rafOnce(fn) {
    let queued = false;
    return () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => { queued = false; fn(); });
    };
  }

  let toastTimer;
  function toast(msg, isError) {
    const t = $('#toast');
    t.textContent = msg;
    t.className = 'toast' + (isError ? ' error' : '');
    t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.hidden = true; }, isError ? 6000 : 3200);
  }

  function busy(text) {
    $('#busy-text').textContent = text;
    $('#busy').hidden = false;
  }
  function unbusy() { $('#busy').hidden = true; }

  async function withBusy(text, fn) {
    busy(text);
    // let the overlay paint before heavy work starts
    await new Promise((r) => requestAnimationFrame(() => setTimeout(r, 30)));
    try {
      return await fn();
    } catch (e) {
      console.error(e);
      toast('Something went wrong: ' + (e && e.message ? e.message : e), true);
    } finally {
      unbusy();
    }
  }

  // Reads an image file, downsizes it and returns a data URL.
  function fileToDataURL(file, maxDim, mime) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = () => reject(new Error('Could not read ' + file.name));
      reader.onload = () => {
        const im = new Image();
        im.onerror = () => reject(new Error('Not a valid image: ' + file.name));
        im.onload = () => {
          const k = Math.min(1, maxDim / Math.max(im.naturalWidth || maxDim, im.naturalHeight || maxDim));
          const w = Math.max(1, Math.round((im.naturalWidth || maxDim) * k));
          const h = Math.max(1, Math.round((im.naturalHeight || maxDim) * k));
          const c = document.createElement('canvas');
          c.width = w;
          c.height = h;
          const ctx = c.getContext('2d');
          if (mime === 'image/jpeg') {
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, w, h);
          }
          ctx.drawImage(im, 0, 0, w, h);
          resolve(c.toDataURL(mime, 0.92));
        };
        im.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
  }
  const photoFromFile = (f) => fileToDataURL(f, 720, 'image/jpeg');
  const artFromFile = (f) => fileToDataURL(f, 900, 'image/png');

  function profile() {
    return state.profiles.find((p) => p.id === state.pid) || null;
  }

  function faceKey(p, m, side) {
    return `${side}|${p.id}:${p.rev || 0}|${m.id}:${m.rev || 0}`;
  }

  /* ================= card frames ================= */

  const frameRO = new ResizeObserver((entries) => {
    for (const e of entries) {
      const w = e.contentRect.width;
      if (w > 0) e.target.style.setProperty('--s', (w / IDCard.W).toFixed(5));
    }
  });

  function cardFrame(p, m, side, key) {
    const frame = document.createElement('div');
    frame.className = 'card-frame';
    frame.appendChild(IDCard.buildFace(p, m, side, key));
    frameRO.observe(frame);
    return frame;
  }

  function clearFrames(container) {
    $$('.card-frame', container).forEach((f) => frameRO.unobserve(f));
    container.innerHTML = '';
  }

  /* ================= college profiles ================= */

  function derivePrefix(event) {
    const words = String(event || '').match(/[A-Za-z]+/g) || [];
    const initials = words.map((w) => w[0].toUpperCase()).join('').slice(0, 5);
    const year = (String(event || '').match(/\d{4}/) || [''])[0];
    return (initials + year) || 'ID';
  }

  function newProfile(extra) {
    return Object.assign({
      id: uid(),
      name: '',
      event: DEFAULT_EVENT,
      idPrefix: derivePrefix(DEFAULT_EVENT),
      terms: DEFAULT_TERMS,
      issueDate: new Date().toISOString().slice(0, 10),
      expiryDate: '',
      logo: '',
      signature: '',
      seal: '',
      labels: Object.assign({}, IDCard.DEFAULT_LABELS),
      createdAt: Date.now(),
      rev: 1,
    }, extra || {});
  }

  function renderCollegeList() {
    const list = $('#college-list');
    list.innerHTML = state.profiles.map((p) =>
      `<button type="button" class="college-chip${p.id === state.pid ? ' active' : ''}" data-pid="${p.id}">` +
      (p.logo ? `<img src="${p.logo}" alt="">` : '<i class="no-logo"></i>') +
      `<span>${esc(p.name || 'Untitled college')}</span></button>`).join('');
    $('#college-empty').hidden = state.profiles.length > 0;
    $('#college-form').hidden = !profile();
    $('#sec-members').hidden = !profile();
    $('#sec-a4').hidden = !profile();
  }

  function fillCollegeForm() {
    const p = profile();
    if (!p) return;
    const f = $('#college-form');
    ['name', 'event', 'idPrefix', 'terms', 'issueDate', 'expiryDate'].forEach((k) => { f.elements[k].value = p[k] || ''; });
    ['logo', 'signature', 'seal'].forEach((k) => {
      const im = $(`.image-pick[data-img=${k}] img`, f);
      if (p[k]) im.src = p[k]; else im.removeAttribute('src');
    });
    const L = Object.assign({}, IDCard.DEFAULT_LABELS, p.labels || {});
    $('#labels-grid').innerHTML = Object.keys(LABEL_NAMES).map((k) =>
      `<label class="field"><span class="label">${LABEL_NAMES[k]}</span><input type="text" data-label="${k}" value="${esc(L[k])}"></label>`).join('');
    updateIdExample();
  }

  function updateIdExample() {
    const p = profile();
    $('#id-example').textContent = `${(p && p.idPrefix) || 'ID'}-001`;
  }

  const saveProfileSoon = debounce(async (p) => {
    await DB.put('profiles', p);
    $('#college-saved').textContent = 'Saved ✓';
    setTimeout(() => { $('#college-saved').textContent = 'Changes are saved automatically'; }, 1500);
  }, 350);

  function profileChanged(p) {
    p.rev = (p.rev || 0) + 1;
    saveProfileSoon(p);
    refreshPreview();
    refreshPagesSoon();
  }

  async function selectProfile(id) {
    state.pid = id;
    state.selectedId = null;
    await DB.setMeta('currentProfile', id);
    state.members = id ? await DB.byProfile(id) : [];
    sortMembers();
    resetMemberForm();
    resetImport();
    renderCollegeList();
    fillCollegeForm();
    renderMembers();
    refreshPreview();
    renderPages();
  }

  function bindCollegeForm() {
    $('#btn-new-college').addEventListener('click', async () => {
      const p = newProfile();
      await DB.put('profiles', p);
      state.profiles.push(p);
      await selectProfile(p.id);
      $('#college-form').elements.name.focus();
    });

    $('#btn-demo').addEventListener('click', () => withBusy('Creating demo college…', addDemo));

    $('#college-list').addEventListener('click', (e) => {
      const chip = e.target.closest('[data-pid]');
      if (chip && chip.dataset.pid !== state.pid) selectProfile(chip.dataset.pid);
    });

    const f = $('#college-form');
    f.addEventListener('submit', (e) => e.preventDefault());
    f.addEventListener('input', (e) => {
      const p = profile();
      if (!p) return;
      const t = e.target;
      if (t.dataset.label) {
        p.labels = Object.assign({}, IDCard.DEFAULT_LABELS, p.labels || {}, { [t.dataset.label]: t.value });
      } else if (t.name && t.type !== 'file') {
        const before = p.event;
        p[t.name] = t.value;
        // keep the ID prefix in step with the event while the user has not customised it
        if (t.name === 'event' && (p.idPrefix === derivePrefix(before) || !p.idPrefix)) {
          p.idPrefix = derivePrefix(t.value);
          f.elements.idPrefix.value = p.idPrefix;
        }
        if (t.name === 'idPrefix' || t.name === 'event') {
          updateIdExample();
          if (!state.editingId) suggestId();
        }
        if (t.name === 'name') renderCollegeList();
      } else {
        return;
      }
      profileChanged(p);
    });

    f.addEventListener('change', async (e) => {
      const t = e.target;
      if (t.type !== 'file' || !t.dataset.file || !t.files[0]) return;
      const p = profile();
      const key = t.dataset.file;
      try {
        p[key] = await artFromFile(t.files[0]);
      } catch (err) {
        toast(err.message, true);
        return;
      } finally {
        t.value = '';
      }
      fillCollegeForm();
      renderCollegeList();
      profileChanged(p);
    });

    f.addEventListener('click', (e) => {
      const b = e.target.closest('[data-clear]');
      if (!b) return;
      const p = profile();
      p[b.dataset.clear] = '';
      fillCollegeForm();
      renderCollegeList();
      profileChanged(p);
    });

    $('#btn-delete-college').addEventListener('click', async () => {
      const p = profile();
      if (!p) return;
      const n = state.members.length;
      if (!confirm(`Delete "${p.name || 'Untitled college'}" and its ${n} member(s)? This cannot be undone.`)) return;
      await DB.deleteProfile(p.id);
      state.profiles = state.profiles.filter((x) => x.id !== p.id);
      await selectProfile(state.profiles[0] ? state.profiles[0].id : null);
      toast('College deleted');
    });
  }

  /* ================= members ================= */

  function sortMembers() {
    state.members.sort((a, b) => (a.order - b.order) || (a.createdAt - b.createdAt));
  }

  function nextId(extraTaken) {
    const p = profile();
    const prefix = (p && p.idPrefix) || 'ID';
    const re = new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '-(\\d+)$', 'i');
    let max = 0;
    const taken = state.members.map((m) => m.idNo).concat(extraTaken || []);
    taken.forEach((id) => {
      const m = re.exec(String(id || '').trim());
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return `${prefix}-${String(max + 1).padStart(3, '0')}`;
  }

  function cleanMobile(v) {
    return String(v == null ? '' : v).trim();
  }

  // 10-digit number, optionally with a country/trunk prefix (+91, 91, 0).
  function mobileError(v) {
    const s = cleanMobile(v);
    if (!s) return 'Mobile number is missing';
    const digits = s.replace(/[\s\-().]/g, '');
    if (!/^(\+?\d{1,3})?\d{10}$/.test(digits)) return `Bad mobile number "${s}" (needs 10 digits, optional country code)`;
    return '';
  }

  function idTaken(idNo, exceptId) {
    const k = String(idNo).trim().toLowerCase();
    return state.members.some((m) => m.id !== exceptId && String(m.idNo).trim().toLowerCase() === k);
  }

  function formValues() {
    const f = $('#member-form');
    return {
      name: f.elements.name.value.trim(),
      designation: f.elements.designation.value.trim(),
      role: f.elements.role.value.trim(),
      mobile: cleanMobile(f.elements.mobile.value),
      idNo: f.elements.idNo.value.trim(),
    };
  }

  function formHasContent() {
    const v = formValues();
    return !!(v.name || v.designation || v.role || v.mobile || state.formPhoto);
  }

  function setFormPhoto(src) {
    state.formPhoto = src || null;
    const im = $('#mf-photo-img');
    if (src) im.src = src; else im.removeAttribute('src');
    $('#mf-photo-empty').hidden = !!src;
  }

  function suggestId() {
    $('#member-form').elements.idNo.value = nextId();
  }

  function resetMemberForm() {
    const f = $('#member-form');
    f.reset();
    state.editingId = null;
    setFormPhoto(null);
    $('#member-form-title').textContent = 'Add member';
    $('#mf-submit').textContent = 'Add member';
    $('#mf-cancel').hidden = true;
    $('#member-error').hidden = true;
    if (profile()) suggestId();
  }

  function editMember(id) {
    const m = state.members.find((x) => x.id === id);
    if (!m) return;
    const f = $('#member-form');
    state.editingId = id;
    ['name', 'designation', 'role', 'mobile', 'idNo'].forEach((k) => { f.elements[k].value = m[k] || ''; });
    setFormPhoto(m.photo);
    $('#member-form-title').textContent = 'Edit member';
    $('#mf-submit').textContent = 'Save changes';
    $('#mf-cancel').hidden = false;
    $('#member-error').hidden = true;
    state.selectedId = id;
    renderMembers();
    refreshPreview();
    f.scrollIntoView({ behavior: 'smooth', block: 'start' });
    f.elements.name.focus({ preventScroll: true });
  }

  function showFormError(msg) {
    const el = $('#member-error');
    el.textContent = msg;
    el.hidden = !msg;
  }

  async function submitMember(e) {
    e.preventDefault();
    const v = formValues();
    const errors = [];
    if (!v.name) errors.push('Name is required');
    const me = mobileError(v.mobile);
    if (me) errors.push(me);
    if (!v.idNo) v.idNo = nextId();
    if (idTaken(v.idNo, state.editingId)) errors.push(`ID number ${v.idNo} is already used`);
    if (errors.length) {
      showFormError(errors.join(' · '));
      return;
    }
    showFormError('');
    let m;
    if (state.editingId) {
      m = state.members.find((x) => x.id === state.editingId);
      Object.assign(m, v, { photo: state.formPhoto || '', rev: (m.rev || 0) + 1 });
    } else {
      const order = state.members.reduce((mx, x) => Math.max(mx, x.order || 0), 0) + 1;
      m = Object.assign({ id: uid(), profileId: state.pid, order, createdAt: Date.now(), rev: 1 }, v, { photo: state.formPhoto || '' });
      state.members.push(m);
    }
    await DB.put('members', m);
    const wasEditing = !!state.editingId;
    state.selectedId = m.id;
    resetMemberForm();
    renderMembers();
    refreshPreview();
    renderPages();
    toast(wasEditing ? 'Member updated' : `Added ${m.name} (${m.idNo})`);
  }

  async function deleteMember(id) {
    const m = state.members.find((x) => x.id === id);
    if (!m || !confirm(`Delete ${m.name} (${m.idNo})?`)) return;
    await DB.del('members', id);
    state.members = state.members.filter((x) => x.id !== id);
    if (state.editingId === id) resetMemberForm();
    if (state.selectedId === id) state.selectedId = null;
    renderMembers();
    refreshPreview();
    renderPages();
  }

  function renderMembers() {
    const tbody = $('#member-table tbody');
    const q = state.search.trim().toLowerCase();
    const list = state.members
      .map((m, i) => ({ m, n: i + 1 }))
      .filter(({ m }) => !q || [m.name, m.designation, m.role, m.mobile, m.idNo].some((x) => String(x || '').toLowerCase().includes(q)));
    tbody.innerHTML = list.map(({ m, n }) =>
      `<tr data-id="${m.id}"${m.id === state.selectedId ? ' class="selected"' : ''}>` +
      `<td data-c="num" class="mono">${n}</td>` +
      `<td data-c="photo">${m.photo ? `<img class="avatar" src="${m.photo}" alt="">` : '<span class="avatar"></span>'}</td>` +
      `<td data-c="name">${esc(m.name)}</td>` +
      `<td data-c="designation" data-l="Designation">${esc(m.designation)}</td>` +
      `<td data-c="role" data-l="Role">${esc(m.role)}</td>` +
      `<td data-c="mobile" data-l="Mobile" class="mono">${esc(m.mobile)}</td>` +
      `<td data-c="id" data-l="ID" class="mono">${esc(m.idNo)}</td>` +
      '<td data-c="actions"><div class="actions">' +
      '<button type="button" class="btn small" data-act="preview">Preview</button>' +
      '<button type="button" class="btn small" data-act="edit">Edit</button>' +
      '<button type="button" class="btn small danger ghost" data-act="delete">Delete</button>' +
      '<span class="dl"><select aria-label="File type"><option value="jpg">JPG</option><option value="pdf">PDF</option></select>' +
      '<button type="button" class="btn small primary" data-act="download">Download ID Card</button></span>' +
      '</div></td></tr>').join('');
    $('#member-empty').hidden = state.members.length > 0;
    $('#member-empty').textContent = state.members.length ? '' : 'No members yet. Add one above or import from Excel.';
    if (state.members.length && !list.length) {
      $('#member-empty').hidden = false;
      $('#member-empty').textContent = 'No member matches your search.';
    }
    $('#member-count').textContent = state.members.length ? `(${state.members.length})` : '';
  }

  function bindMembers() {
    $('#member-form').addEventListener('submit', submitMember);
    $('#member-form').addEventListener('input', () => refreshPreview());
    $('#mf-cancel').addEventListener('click', () => { resetMemberForm(); refreshPreview(); });
    $('#mf-photo').addEventListener('change', async (e) => {
      const file = e.target.files[0];
      e.target.value = '';
      if (!file) return;
      try {
        setFormPhoto(await photoFromFile(file));
      } catch (err) {
        toast(err.message, true);
      }
      refreshPreview();
    });
    $('#mf-photo-clear').addEventListener('click', () => { setFormPhoto(null); refreshPreview(); });
    $('#member-search').addEventListener('input', (e) => { state.search = e.target.value; renderMembers(); });

    $('#member-table').addEventListener('click', (e) => {
      const b = e.target.closest('[data-act]');
      if (!b) return;
      const id = b.closest('tr').dataset.id;
      const act = b.dataset.act;
      if (act === 'edit') editMember(id);
      else if (act === 'delete') deleteMember(id);
      else if (act === 'preview') {
        state.selectedId = id;
        renderMembers();
        refreshPreview();
        if (window.innerWidth <= 960) $('.preview-panel').scrollIntoView({ behavior: 'smooth' });
      } else if (act === 'download') {
        const fmt = b.closest('.dl').querySelector('select').value;
        const m = state.members.find((x) => x.id === id);
        const p = profile();
        withBusy(`Preparing ${fmt.toUpperCase()} for ${m.name}…`, () =>
          IDExport.downloadCard(p, m, fmt, (side) => faceKey(p, m, side)));
      }
    });
  }

  /* ================= live preview ================= */

  function previewTarget() {
    if (state.editingId || formHasContent()) {
      const base = state.editingId ? state.members.find((m) => m.id === state.editingId) : null;
      return { member: Object.assign({ id: 'draft' }, base || {}, formValues(), { photo: state.formPhoto || '' }), draft: true };
    }
    const sel = state.members.find((m) => m.id === state.selectedId) || state.members[0];
    if (sel) return { member: sel, draft: false };
    return { member: Object.assign({ id: 'draft' }, formValues(), { photo: '' }), draft: true };
  }

  const refreshPreview = rafOnce(() => {
    const p = profile();
    const box = $('#preview-cards');
    clearFrames(box);
    if (!p) return;
    const { member, draft } = previewTarget();
    $('#preview-who').textContent = draft
      ? (state.editingId ? 'Editing (unsaved)' : 'From the form')
      : `${member.name} · ${member.idNo}`;
    ['front', 'back'].forEach((side) => {
      const wrap = document.createElement('div');
      wrap.appendChild(cardFrame(p, member, side, draft ? null : faceKey(p, member, side)));
      const lab = document.createElement('div');
      lab.className = 'side-label';
      lab.textContent = side === 'front' ? 'Front' : 'Back';
      wrap.appendChild(lab);
      box.appendChild(wrap);
    });
  });

  /* ================= A4 pages ================= */

  function renderPages() {
    const p = profile();
    const box = $('#pages');
    clearFrames(box);
    if (!p) return;
    const ms = state.members;
    const pages = ms.length ? IDExport.pageCount(ms.length) : 0;
    const g = IDExport.a4Geometry();
    $('#a4-info').textContent = ms.length
      ? `${ms.length} member(s) → ${pages} page(s). 4 members per A4 page, front on the left and back on the right. ` +
        `Card size on the sheet: ${g.w.toFixed(1)} × ${g.h.toFixed(1)} mm (largest that fits 4 rows; CR80 shape kept).`
      : 'Add members to see the A4 print sheets here.';
    $('#btn-all-pages').disabled = !ms.length;
    const pct = (v, total) => `${(v / total * 100).toFixed(4)}%`;
    for (let i = 0; i < pages; i++) {
      const members = ms.slice(i * IDExport.ROWS, i * IDExport.ROWS + IDExport.ROWS);
      const wrap = document.createElement('div');
      wrap.className = 'page-wrap';
      wrap.innerHTML = `<div class="page-bar"><b>Page ${i + 1}</b>` +
        `<button type="button" class="btn small primary" data-page="${i}">Download in A4</button></div>`;
      const a4 = document.createElement('div');
      a4.className = 'a4';
      const layout = IDExport.a4Layout(members.length);
      layout.slots.forEach((s) => {
        const slot = document.createElement('div');
        slot.className = 'slot';
        slot.style.left = pct(s.x, IDExport.A4.w);
        slot.style.top = pct(s.y, IDExport.A4.h);
        slot.style.width = pct(g.w, IDExport.A4.w);
        slot.style.height = pct(g.h, IDExport.A4.h);
        const m = members[s.row];
        slot.appendChild(cardFrame(p, m, s.side, faceKey(p, m, s.side)));
        a4.appendChild(slot);
      });
      a4.insertAdjacentHTML('beforeend', IDExport.a4OverlaySvg(members.length));
      wrap.appendChild(a4);
      box.appendChild(wrap);
    }
  }
  const refreshPagesSoon = debounce(renderPages, 450);

  function bindPages() {
    const run = (pages) => {
      const p = profile();
      return withBusy('Preparing A4 PDF…', () =>
        IDExport.downloadA4(p, state.members, pages, (m, side) => faceKey(p, m, side),
          (done, total) => { $('#busy-text').textContent = `Rendering cards ${done} / ${total}…`; }));
    };
    $('#pages').addEventListener('click', (e) => {
      const b = e.target.closest('[data-page]');
      if (b) run([+b.dataset.page]);
    });
    $('#btn-all-pages').addEventListener('click', () => {
      const n = IDExport.pageCount(state.members.length);
      run(Array.from({ length: n }, (_, i) => i));
    });
  }

  /* ================= Excel import ================= */

  const COLS = {
    name: ['name', 'fullname', 'membername', 'studentname'],
    designation: ['designation', 'post', 'position'],
    role: ['role'],
    mobile: ['mobile', 'mobilenumber', 'mobileno', 'phone', 'phonenumber', 'contact', 'contactnumber'],
    photo: ['photofilename', 'photofile', 'photoname', 'photo', 'image', 'imagefilename', 'filename'],
  };
  const normKey = (s) => String(s).toLowerCase().replace(/[^a-z]/g, '');

  function resetImport() {
    state.imp = { rows: [], photos: new Map(), fileName: '' };
    $('#import-file-name').textContent = '';
    $('#import-photo-count').textContent = 'Photos are matched by file name';
    $('#import-preview').innerHTML = '';
  }

  function downloadSample() {
    const rows = [
      ['Name', 'Designation', 'Role', 'Mobile', 'Photo File Name'],
      ['Rahul Kumar Sahoo', 'President', 'Candidate', '9876543210', 'rahul.jpg'],
      ['Priya Das', 'Vice President', 'Candidate', '9437012345', 'priya.jpg'],
      ['Amit Mohanty', 'Polling Agent', 'Volunteer', '+91 70080 12345', 'amit.png'],
    ];
    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 24 }, { wch: 18 }, { wch: 14 }, { wch: 18 }, { wch: 18 }];
    rows.slice(1).forEach((_, i) => { ws[XLSX.utils.encode_cell({ r: i + 1, c: 3 })].t = 's'; });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Members');
    XLSX.writeFile(wb, 'sample_members.xlsx');
  }

  function cellText(v) {
    if (v == null) return '';
    if (typeof v === 'number') return Number.isInteger(v) ? String(v) : String(v);
    return String(v).trim();
  }

  async function readSheet(file) {
    const buf = await file.arrayBuffer();
    const wb = XLSX.read(buf, { type: 'array', raw: false, cellDates: false });
    const ws = wb.Sheets[wb.SheetNames[0]];
    const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: true, blankrows: false });
    if (!raw.length) throw new Error('The file is empty');
    const head = raw[0].map(normKey);
    const idx = {};
    Object.entries(COLS).forEach(([k, names]) => {
      idx[k] = head.findIndex((h) => names.includes(h));
    });
    if (idx.name < 0) throw new Error('No "Name" column found. Use the sample Excel for the right columns.');
    return raw.slice(1)
      .map((r, i) => ({
        rowNo: i + 2,
        name: idx.name >= 0 ? cellText(r[idx.name]) : '',
        designation: idx.designation >= 0 ? cellText(r[idx.designation]) : '',
        role: idx.role >= 0 ? cellText(r[idx.role]) : '',
        mobile: idx.mobile >= 0 ? cellText(r[idx.mobile]) : '',
        photoName: idx.photo >= 0 ? cellText(r[idx.photo]) : '',
      }))
      .filter((r) => r.name || r.designation || r.role || r.mobile || r.photoName);
  }

  function findPhoto(name) {
    if (!name) return null;
    const k = name.trim().toLowerCase();
    const ph = state.imp.photos;
    if (ph.has(k)) return ph.get(k);
    const base = k.replace(/\.[a-z0-9]+$/, '');
    for (const [fname, file] of ph) {
      if (fname.replace(/\.[a-z0-9]+$/, '') === base) return file;
    }
    return null;
  }

  function validateImport() {
    state.imp.rows.forEach((r) => {
      r.errors = [];
      r.warnings = [];
      if (!r.name) r.errors.push('Name is missing');
      const me = mobileError(r.mobile);
      if (me) r.errors.push(me);
      r.file = findPhoto(r.photoName);
      if (r.photoName && !r.file) r.errors.push(`Photo "${r.photoName}" not found in uploaded photos`);
      if (!r.photoName) r.warnings.push('No photo file name');
    });
  }

  function renderImport() {
    const rows = state.imp.rows;
    const box = $('#import-preview');
    if (!rows.length) {
      box.innerHTML = state.imp.fileName ? '<p class="empty">No data rows found in this file.</p>' : '';
      return;
    }
    validateImport();
    const ok = rows.filter((r) => !r.errors.length);
    const bad = rows.length - ok.length;
    box.innerHTML =
      '<div class="import-summary">' +
      `<span class="pill ok">${ok.length} ready</span>` +
      (bad ? `<span class="pill bad">${bad} with errors (will be skipped)</span>` : '') +
      `<button type="button" class="btn primary" id="btn-do-import"${ok.length ? '' : ' disabled'}>Import ${ok.length} member(s)</button>` +
      '<button type="button" class="btn ghost" id="btn-cancel-import">Clear</button></div>' +
      '<div class="table-wrap"><table class="import-table"><thead><tr><th>Row</th><th>Photo</th><th>Name</th><th>Designation</th><th>Role</th><th>Mobile</th><th>Photo file</th><th>Status</th></tr></thead><tbody>' +
      rows.map((r) => `<tr class="${r.errors.length ? 'bad' : ''}"><td>${r.rowNo}</td>` +
        `<td>${r.thumb ? `<img class="avatar" src="${r.thumb}" alt="">` : ''}</td>` +
        `<td>${esc(r.name)}</td><td>${esc(r.designation)}</td><td>${esc(r.role)}</td><td class="mono">${esc(r.mobile)}</td><td>${esc(r.photoName)}</td>` +
        (r.errors.length
          ? `<td class="err">${r.errors.map(esc).join('<br>')}</td>`
          : `<td class="${r.warnings.length ? 'warn' : ''}">${r.warnings.length ? r.warnings.map(esc).join('<br>') : 'OK'}</td>`) +
        '</tr>').join('') +
      '</tbody></table></div>';
    loadThumbs();
  }

  let thumbRun = 0;
  async function loadThumbs() {
    const run = ++thumbRun;
    for (const r of state.imp.rows) {
      if (run !== thumbRun) return;
      if (r.file && r.thumbFile !== r.file) {
        try {
          r.thumb = await fileToDataURL(r.file, 96, 'image/jpeg');
          r.thumbFile = r.file;
        } catch (e) {
          r.thumb = '';
        }
      }
      if (!r.file) r.thumb = '';
    }
    if (run !== thumbRun) return;
    const imgs = $$('#import-preview tbody tr');
    state.imp.rows.forEach((r, i) => {
      const cell = imgs[i] && imgs[i].children[1];
      if (cell) cell.innerHTML = r.thumb ? `<img class="avatar" src="${r.thumb}" alt="">` : '';
    });
  }

  async function doImport() {
    const p = profile();
    validateImport();
    const ok = state.imp.rows.filter((r) => !r.errors.length);
    const skipped = state.imp.rows.length - ok.length;
    const created = [];
    let order = state.members.reduce((mx, x) => Math.max(mx, x.order || 0), 0);
    for (let i = 0; i < ok.length; i++) {
      const r = ok[i];
      $('#busy-text').textContent = `Importing ${i + 1} / ${ok.length}…`;
      let photo = '';
      if (r.file) {
        try {
          photo = await photoFromFile(r.file);
        } catch (e) {
          photo = '';
        }
      }
      created.push({
        id: uid(), profileId: p.id, order: ++order, createdAt: Date.now() + i, rev: 1,
        name: r.name, designation: r.designation, role: r.role, mobile: cleanMobile(r.mobile),
        idNo: nextId(created.map((c) => c.idNo)), photo,
      });
    }
    await DB.putMany('members', created);
    state.members.push(...created);
    resetImport();
    resetMemberForm();
    renderMembers();
    refreshPreview();
    renderPages();
    toast(`Imported ${created.length} member(s)` + (skipped ? `, skipped ${skipped} row(s) with errors` : ''));
  }

  function bindImport() {
    $('#btn-sample').addEventListener('click', downloadSample);
    $('#import-file').addEventListener('change', async (e) => {
      const file = e.target.files[0];
      e.target.value = '';
      if (!file) return;
      try {
        state.imp.rows = await readSheet(file);
        state.imp.fileName = file.name;
        $('#import-file-name').textContent = `${file.name} · ${state.imp.rows.length} row(s)`;
      } catch (err) {
        state.imp.rows = [];
        state.imp.fileName = '';
        $('#import-file-name').textContent = '';
        toast('Could not read the file: ' + err.message, true);
      }
      renderImport();
    });
    $('#import-photos').addEventListener('change', (e) => {
      Array.from(e.target.files).forEach((f) => state.imp.photos.set(f.name.toLowerCase(), f));
      e.target.value = '';
      $('#import-photo-count').textContent = `${state.imp.photos.size} photo(s) loaded`;
      renderImport();
    });
    $('#import-preview').addEventListener('click', (e) => {
      if (e.target.id === 'btn-do-import') withBusy('Importing…', doImport);
      if (e.target.id === 'btn-cancel-import') resetImport();
    });
  }

  /* ================= demo data ================= */

  const svgUrl = (s) => 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(s);

  function demoLogo() {
    return svgUrl('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80"><g fill="none" stroke="#fff" stroke-width="7" stroke-linejoin="round">' +
      '<path d="M12 40 26 16h28l14 24-14 24H26z"/><path d="M52 40 66 16h28l14 24-14 24H66z"/></g></svg>');
  }

  function demoSignature() {
    return svgUrl('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 80"><path d="M10 55c18-30 30-42 36-36 6 7-14 40-6 42 9 2 20-38 30-36 8 2-6 30 2 32 9 2 16-22 24-22 7 0 2 18 10 18 10 0 18-20 28-20 8 0 2 16 10 16 12 0 30-10 52-14" fill="none" stroke="#1b2a6b" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
  }

  function demoSeal() {
    return svgUrl('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><g fill="none" stroke="#2f47b5" opacity="0.85">' +
      '<circle cx="100" cy="100" r="92" stroke-width="6"/><circle cx="100" cy="100" r="62" stroke-width="3"/></g>' +
      '<defs><path id="c" d="M100 100m-77 0a77 77 0 1 1 154 0a77 77 0 1 1-154 0"/></defs>' +
      '<text font-family="Arial" font-size="19" font-weight="700" fill="#2f47b5" opacity="0.85" letter-spacing="2"><textPath href="#c">GOVT. AUTONOMOUS COLLEGE • PRINCIPAL •</textPath></text>' +
      '<text x="100" y="108" text-anchor="middle" font-family="Arial" font-size="22" font-weight="700" fill="#2f47b5" opacity="0.85">SEAL</text></svg>');
  }

  function demoAvatar(i) {
    const bg = ['#cfe3f7', '#f7e1cf', '#d9f0d6', '#efd6f0', '#f3efc8', '#d6e9ef'][i % 6];
    const skin = ['#e0ac7e', '#c68b5f', '#f1c29b', '#a8724a', '#d99d70', '#eab58c'][i % 6];
    const hair = ['#2b1d14', '#3a2a1e', '#111', '#4b3020', '#1c1c1c', '#5a3b22'][i % 6];
    const shirt = ['#2b2b2b', '#24447a', '#7a2433', '#2e6b4f', '#5b4a8a', '#8a5a24'][i % 6];
    return svgUrl(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><rect width="300" height="300" fill="${bg}"/>` +
      `<path d="M40 300c6-62 50-92 110-92s104 30 110 92z" fill="${shirt}"/><rect x="128" y="170" width="44" height="46" rx="16" fill="${skin}"/>` +
      `<ellipse cx="150" cy="122" rx="56" ry="64" fill="${skin}"/><path d="M92 118c-4-54 34-76 62-74 34 2 62 24 56 76-10-26-30-40-60-40-28 0-48 14-58 38z" fill="${hair}"/></svg>`);
  }

  async function addDemo() {
    const year = new Date().getFullYear();
    const p = newProfile({
      name: 'Government Autonomous College',
      logo: demoLogo(),
      signature: demoSignature(),
      seal: demoSeal(),
      issueDate: `${year}-10-01`,
      expiryDate: `${year + 1}-03-31`,
    });
    await DB.put('profiles', p);
    state.profiles.push(p);
    state.pid = p.id;
    const people = [
      ['Rahul Kumar Sahoo', 'President', 'Candidate', '9876543210'],
      ['Priya Das', 'Vice President', 'Candidate', '9437012345'],
      ['Amit Mohanty', 'General Secretary', 'Candidate', '+91 70080 12345'],
      ['Sneha Pattnaik', 'Polling Agent', 'Volunteer', '9861122334'],
      ['Subhashree Priyadarshini Mohapatra', 'Assistant Returning Officer', 'Election Staff', '8249012345'],
      ['Bikash Ranjan Behera', 'Observer', 'Faculty', '7978123456'],
    ];
    state.members = [];
    const created = people.map(([name, designation, role, mobile], i) => ({
      id: uid(), profileId: p.id, order: i + 1, createdAt: Date.now() + i, rev: 1,
      name, designation, role, mobile, idNo: `${p.idPrefix}-${String(i + 1).padStart(3, '0')}`, photo: demoAvatar(i),
    }));
    await DB.putMany('members', created);
    await selectProfile(p.id);
    toast('Demo college with 6 members added');
  }

  /* ================= init ================= */

  async function init() {
    await IDCard.ready();
    await DB.open();
    state.profiles = (await DB.all('profiles')).sort((a, b) => a.createdAt - b.createdAt);
    bindCollegeForm();
    bindMembers();
    bindImport();
    bindPages();
    const saved = await DB.getMeta('currentProfile');
    const start = state.profiles.find((p) => p.id === saved) || state.profiles[0];
    await selectProfile(start ? start.id : null);
  }

  init().catch((e) => {
    console.error(e);
    toast('Could not start: ' + (e && e.message ? e.message : e), true);
  });
})();
