/* DigiMarket front-end behaviour. No dependencies. */
(function () {
	'use strict';

	var cfg = window.DM || {};
	var i18n = cfg.i18n || {};

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	function post(action, data) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', cfg.nonce);
		Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
		return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) { return r.json(); });
	}

	var toastTimer;
	function toast(msg, isError) {
		var t = $('.dm-toast');
		if (!t) { return; }
		t.textContent = msg;
		t.classList.toggle('is-error', !!isError);
		t.hidden = false;
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { t.hidden = true; }, 3200);
	}

	/* ---- Colour mode ---- */
	var modeBtn = $('.dm-mode-toggle');
	if (modeBtn) {
		modeBtn.addEventListener('click', function () {
			var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			document.documentElement.setAttribute('data-theme', next);
			try { localStorage.setItem('dm-mode', next); } catch (e) {}
		});
	}

	/* ---- Mobile nav ---- */
	var menuBtn = $('.dm-menu-toggle');
	var mobileNav = $('#dm-mobile-nav');
	if (menuBtn && mobileNav) {
		menuBtn.addEventListener('click', function () {
			var open = mobileNav.hidden;
			mobileNav.hidden = !open;
			menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	/* ---- Dropdowns ---- */
	$$('.dm-cat-toggle, .dm-avatar-btn').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			var wrap = btn.parentElement;
			var open = !wrap.classList.contains('is-open');
			$$('.dm-cat-menu.is-open, .dm-user-menu.is-open').forEach(function (w) { w.classList.remove('is-open'); });
			wrap.classList.toggle('is-open', open);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});
	document.addEventListener('click', function (e) {
		$$('.dm-cat-menu.is-open, .dm-user-menu.is-open').forEach(function (w) {
			if (!w.contains(e.target)) { w.classList.remove('is-open'); }
		});
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { $$('.is-open').forEach(function (w) { w.classList.remove('is-open'); }); }
	});

	/* ---- Notices ---- */
	document.addEventListener('click', function (e) {
		if (e.target.classList.contains('dm-notice-close')) { e.target.parentElement.remove(); }
	});

	/* ---- Filters (mobile) ---- */
	var ft = $('.dm-filter-toggle');
	if (ft) {
		ft.addEventListener('click', function () {
			var f = $('#dm-filters');
			var open = !f.classList.contains('is-open');
			f.classList.toggle('is-open', open);
			ft.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}
	$$('[data-autosubmit]').forEach(function (s) {
		s.addEventListener('change', function () { s.form.submit(); });
	});

	/* ---- Confirmations ---- */
	document.addEventListener('click', function (e) {
		var el = e.target.closest('[data-confirm]');
		if (!el) { return; }
		if (el.tagName === 'BUTTON' && el.form && el.form.querySelector('select[name="do"]') && !el.form.querySelector('select[name="do"]').value && el.name !== 'row') {
			e.preventDefault();
			toast(i18n.error || 'Choose an action', true);
			return;
		}
		if (!window.confirm(el.getAttribute('data-confirm') || i18n.confirm)) { e.preventDefault(); }
	});

	/* ---- Check all ---- */
	$$('[data-check-all]').forEach(function (cb) {
		cb.addEventListener('change', function () {
			$$('input[name="ids[]"]', cb.closest('form')).forEach(function (c) { c.checked = cb.checked; });
		});
	});

	/* ---- Add to cart (AJAX) ---- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-add-to-cart]');
		if (!btn || !window.fetch) { return; }
		e.preventDefault();
		btn.disabled = true;
		post('dm_cart_add', { product_id: btn.getAttribute('data-add-to-cart') }).then(function (res) {
			btn.disabled = false;
			if (res && res.success) {
				var c = $('.dm-cart-count');
				if (c) { c.textContent = res.data.count; c.hidden = false; }
				toast(res.data.message || i18n.added);
			} else {
				toast((res && res.data && res.data.message) || i18n.error, true);
			}
		}).catch(function () { btn.disabled = false; btn.form.submit(); });
	});

	/* ---- Wishlist ---- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.dm-wish, .dm-wish-inline');
		if (!btn) { return; }
		e.preventDefault();
		post('dm_wishlist', { product_id: btn.getAttribute('data-product') }).then(function (res) {
			if (res && res.success) {
				btn.classList.toggle('is-on', res.data.on);
				btn.setAttribute('aria-pressed', res.data.on ? 'true' : 'false');
				var label = btn.querySelector('span');
				if (label) { label.textContent = res.data.on ? 'Saved' : 'Save to wishlist'; }
			} else if (res && res.data && res.data.login) {
				window.location.href = cfg.loginUrl;
			}
		});
	});

	/* ---- Product gallery ---- */
	$$('[data-gallery]').forEach(function (g) {
		var main = $('.dm-gallery-main img', g);
		$$('.dm-gallery-thumbs button', g).forEach(function (b) {
			b.addEventListener('click', function () {
				if (main) { main.src = b.getAttribute('data-full'); main.removeAttribute('srcset'); }
				$$('.dm-gallery-thumbs button', g).forEach(function (x) { x.classList.remove('is-active'); });
				b.classList.add('is-active');
			});
		});
	});

	/* ---- Hero rotator ---- */
	$$('[data-rotator]').forEach(function (r) {
		var slides = $$('.dm-rotator-slide', r);
		var dots = $$('.dm-rotator-dots button', r);
		if (slides.length < 2) { return; }
		var i = 0;
		function show(n) {
			i = (n + slides.length) % slides.length;
			slides.forEach(function (s, k) { s.classList.toggle('is-active', k === i); s.setAttribute('aria-hidden', k === i ? 'false' : 'true'); });
			dots.forEach(function (d, k) { d.classList.toggle('is-active', k === i); });
		}
		dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (!reduce) { setInterval(function () { if (!document.hidden) { show(i + 1); } }, 5000); }
	});

	/* ---- Shop slug live check ---- */
	var slugInput = $('[data-slug-input]');
	if (slugInput) {
		var status = $('[data-slug-status]');
		var source = $('[data-slug-source]');
		var touched = slugInput.value !== '';
		var timer;
		function slugify(s) {
			return s.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60);
		}
		function check() {
			var v = slugify(slugInput.value);
			if (v.length < 3) { status.textContent = '✕'; status.className = 'dm-slug-status is-bad'; return; }
			status.textContent = '…';
			status.className = 'dm-slug-status';
			post('dm_check_slug', { slug: v }).then(function (res) {
				if (!res || !res.success) { return; }
				status.textContent = res.data.available ? '✓ ' + (i18n.available || '') : '✕ ' + (i18n.taken || '');
				status.className = 'dm-slug-status ' + (res.data.available ? 'is-ok' : 'is-bad');
			});
		}
		slugInput.addEventListener('input', function () {
			touched = true;
			clearTimeout(timer);
			timer = setTimeout(check, 350);
		});
		slugInput.addEventListener('blur', function () { slugInput.value = slugify(slugInput.value); });
		if (source) {
			source.addEventListener('input', function () {
				if (touched) { return; }
				slugInput.value = slugify(source.value);
				clearTimeout(timer);
				timer = setTimeout(check, 350);
			});
		}
		if (slugInput.value) { check(); }
	}

	/* ---- Delivery panels ---- */
	var radios = $$('[data-toggle-delivery]');
	function syncDelivery() {
		var cur = (radios.filter(function (r) { return r.checked; })[0] || {}).value || 'file';
		$$('.dm-delivery-panel').forEach(function (p) {
			var on = p.getAttribute('data-delivery') === cur;
			p.hidden = !on;
		});
	}
	if (radios.length) { radios.forEach(function (r) { r.addEventListener('change', syncDelivery); }); syncDelivery(); }

	/* ---- Image previews & limits ---- */
	$$('input[type="file"][data-preview]').forEach(function (inp) {
		inp.addEventListener('change', function () {
			var f = inp.files && inp.files[0];
			var box = inp.parentElement.querySelector('.dm-upload-preview');
			if (!f || !box || !/^image\//.test(f.type)) { return; }
			var url = URL.createObjectURL(f);
			if (box.classList.contains('dm-banner-preview')) {
				box.style.backgroundImage = 'url(' + url + ')';
			} else {
				box.innerHTML = '';
				var img = document.createElement('img');
				img.src = url;
				img.alt = '';
				box.appendChild(img);
			}
		});
	});
	$$('input[type="file"][data-max]').forEach(function (inp) {
		inp.addEventListener('change', function () {
			var max = parseInt(inp.getAttribute('data-max'), 10);
			if (inp.files.length > max) {
				toast('Max ' + max + ' images', true);
				inp.value = '';
			}
		});
	});

	/* ---- Character counter ---- */
	$$('[data-count]').forEach(function (inp) {
		var out = inp.parentElement.querySelector('.dm-counter');
		var max = parseInt(inp.getAttribute('data-count'), 10);
		function upd() { if (out) { out.textContent = inp.value.length + ' / ' + max; } }
		inp.addEventListener('input', upd);
		upd();
	});

	/* ---- You earn ---- */
	var comm = $('[data-commission]');
	if (comm) {
		var rate = parseFloat(comm.getAttribute('data-commission')) || 0;
		var price = $('input[name="price"]');
		var sale = $('input[name="sale_price"]');
		var out = $('[data-you-earn]', comm);
		function calc() {
			var p = parseFloat(sale && sale.value !== '' ? sale.value : price.value) || 0;
			var earn = p - Math.round(p * rate) / 100;
			out.textContent = p > 0 ? '≈ ' + earn.toFixed(2) + ' / sale' : '';
		}
		[price, sale].forEach(function (i) { if (i) { i.addEventListener('input', calc); } });
		calc();
	}

	/* ---- Copy ---- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-copy]');
		if (!b) { return; }
		e.preventDefault();
		var text = b.getAttribute('data-copy');
		if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(function () { toast('Copied'); });
		} else {
			window.prompt('Copy:', text);
		}
	});

	/* ---- Password reveal ---- */
	$$('.dm-pass-toggle').forEach(function (b) {
		b.addEventListener('click', function () {
			var i = b.parentElement.querySelector('input');
			i.type = i.type === 'password' ? 'text' : 'password';
		});
	});

	/* ---- Razorpay Checkout ---- */
	var payBtn = $('#dm-rzp-pay');
	if (payBtn) {
		var statusEl = $('#dm-pay-status');
		var conf = JSON.parse(payBtn.getAttribute('data-config'));
		var orderId = conf.dmOrder;
		delete conf.dmOrder;
		function setStatus(msg, err) { statusEl.textContent = msg; statusEl.className = 'dm-pay-status' + (err ? ' is-error' : ''); }
		conf.handler = function (resp) {
			setStatus('Verifying payment…');
			payBtn.disabled = true;
			post('dm_rzp_verify', {
				order_id: orderId,
				razorpay_payment_id: resp.razorpay_payment_id,
				razorpay_order_id: resp.razorpay_order_id,
				razorpay_signature: resp.razorpay_signature
			}).then(function (res) {
				if (res && res.success) { window.location.href = res.data.redirect; }
				else { payBtn.disabled = false; setStatus((res && res.data && res.data.message) || i18n.error, true); }
			}).catch(function () { payBtn.disabled = false; setStatus(i18n.error, true); });
		};
		conf.modal = { ondismiss: function () { setStatus(i18n.paymentFail, true); } };
		function open() {
			if (typeof window.Razorpay !== 'function') { setStatus(i18n.error, true); return; }
			var rzp = new window.Razorpay(conf);
			rzp.on('payment.failed', function (r) {
				post('dm_rzp_failed', { order_id: orderId, reason: (r && r.error && r.error.description) || 'failed' });
				setStatus(((r && r.error && r.error.description) || i18n.paymentFail) + ' — ' + (i18n.paymentFail || ''), true);
			});
			rzp.open();
		}
		payBtn.addEventListener('click', function (e) { e.preventDefault(); open(); });
		window.addEventListener('load', function () { setTimeout(open, 300); });
	}
})();
