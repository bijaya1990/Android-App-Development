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
	$$('.dm-mode-toggle').forEach(function (modeBtn) {
		modeBtn.addEventListener('click', function () {
			var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			document.documentElement.setAttribute('data-theme', next);
			try { localStorage.setItem('dm-mode', next); } catch (e) {}
		});
	});

	/* ---- Drawer (mobile menu) ---- */
	var drawer = $('#dm-drawer');
	var lastFocus = null;
	function setDrawer(open) {
		if (!drawer) { return; }
		drawer.hidden = !open;
		document.body.classList.toggle('dm-noscroll', open);
		$$('[aria-controls="dm-drawer"]').forEach(function (b) { b.setAttribute('aria-expanded', open ? 'true' : 'false'); });
		if (open) {
			lastFocus = document.activeElement;
			var first = $('.dm-drawer-close', drawer);
			if (first) { first.focus(); }
		} else if (lastFocus) {
			lastFocus.focus();
		}
	}
	$$('.dm-menu-toggle, .dm-bnav-cats').forEach(function (b) {
		b.addEventListener('click', function () { setDrawer(true); });
	});
	if (drawer) {
		drawer.addEventListener('click', function (e) {
			if (e.target === drawer || e.target.closest('.dm-drawer-close')) { setDrawer(false); }
		});
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !drawer.hidden) { setDrawer(false); } });
	}
	var bnavSearch = $('.dm-bnav-search');
	if (bnavSearch) {
		bnavSearch.addEventListener('click', function () {
			var s = $('#dm-s');
			window.scrollTo({ top: 0, behavior: 'smooth' });
			if (s) { setTimeout(function () { s.focus(); }, 250); }
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
				$$('.dm-cart-count').forEach(function (c) { c.textContent = res.data.count; c.hidden = false; c.classList.remove('dm-bump'); void c.offsetWidth; c.classList.add('dm-bump'); });
				btn.classList.add('is-added');
				toast(res.data.message || i18n.added);
			} else {
				toast((res && res.data && res.data.message) || i18n.error, true);
			}
		}).catch(function () {
			btn.disabled = false;
			if (btn.form) { btn.form.submit(); } else { window.location.href = cfg.cartUrl; }
		});
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
			navigator.clipboard.writeText(text).then(function () { toast(i18n.copied || 'Copied'); });
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
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function beacon(action, data) {
		var body = new FormData();
		body.append('action', action);
		Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
		if (navigator.sendBeacon) { navigator.sendBeacon(cfg.ajax, body); }
		else if (window.fetch) { fetch(cfg.ajax, { method: 'POST', body: body, keepalive: true }); }
	}

	/* ---- Carousels (hero cards + product rows) ---- */
	$$('[data-carousel]').forEach(function (car) {
		var track = $('.dm-car-track', car);
		if (!track) { return; }
		var slides = Array.prototype.slice.call(track.children);
		var prev = $('.dm-car-nav.is-prev', car);
		var next = $('.dm-car-nav.is-next', car);
		var dots = $$('.dm-car-dots button', car);
		var pauseBtn = $('.dm-car-pause', car);
		var delay = parseInt(car.getAttribute('data-autoplay'), 10) || 0;
		var loop = !!delay;
		var timer = null;
		var paused = false;

		function step() {
			if (slides.length < 2) { return track.clientWidth; }
			return slides[1].getBoundingClientRect().left - slides[0].getBoundingClientRect().left;
		}
		function index() { return Math.round(track.scrollLeft / Math.max(1, step())); }
		function maxScroll() { return track.scrollWidth - track.clientWidth - 2; }
		function go(i) {
			var n = slides.length;
			if (loop) { i = (i + n) % n; } else { i = Math.max(0, Math.min(n - 1, i)); }
			if (loop && i * step() > maxScroll() + step() / 2 && i !== 0) { i = 0; }
			track.scrollTo({ left: i * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
		}
		function sync() {
			var i = index();
			dots.forEach(function (d, k) {
				var on = k === Math.min(i, dots.length - 1);
				d.classList.toggle('is-active', on);
				d.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			if (!loop) {
				if (prev) { prev.disabled = track.scrollLeft < 4; }
				if (next) { next.disabled = track.scrollLeft >= maxScroll(); }
			}
		}
		function pageSize() { return Math.max(1, Math.floor(track.clientWidth / Math.max(1, step()))); }
		if (prev) { prev.addEventListener('click', function () { go(index() - (loop ? 1 : pageSize())); restart(); }); }
		if (next) { next.addEventListener('click', function () { go(index() + (loop ? 1 : pageSize())); restart(); }); }
		dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); restart(); }); });
		var ticking = false;
		track.addEventListener('scroll', function () {
			if (ticking) { return; }
			ticking = true;
			requestAnimationFrame(function () { sync(); ticking = false; });
		}, { passive: true });
		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { e.preventDefault(); go(index() + 1); }
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(index() - 1); }
		});

		function start() {
			if (!delay || reduceMotion || paused || slides.length < 2) { return; }
			stop();
			timer = setInterval(function () {
				if (document.hidden) { return; }
				var i = index() + 1;
				if (track.scrollLeft >= maxScroll()) { i = 0; }
				go(i);
			}, delay);
		}
		function stop() { if (timer) { clearInterval(timer); timer = null; } }
		function restart() { stop(); start(); }
		car.addEventListener('mouseenter', stop);
		car.addEventListener('mouseleave', start);
		car.addEventListener('focusin', stop);
		car.addEventListener('focusout', start);
		track.addEventListener('touchstart', stop, { passive: true });
		track.addEventListener('touchend', function () { setTimeout(start, 2500); }, { passive: true });
		if (pauseBtn) {
			pauseBtn.addEventListener('click', function () {
				paused = !paused;
				car.classList.toggle('is-paused', paused);
				pauseBtn.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
				if (paused) { stop(); } else { start(); }
			});
		}
		window.addEventListener('resize', sync);
		sync();
		start();
	});

	/* ---- Countdowns ---- */
	var timers = $$('[data-countdown]');
	if (timers.length) {
		var pad = function (n) { return (n < 10 ? '0' : '') + n; };
		var tick = function () {
			var now = Math.floor(Date.now() / 1000);
			timers.forEach(function (el) {
				var left = parseInt(el.getAttribute('data-countdown'), 10) - now;
				var b = el.querySelector('b');
				if (!b) { return; }
				if (left <= 0) {
					b.textContent = i18n.ended || 'Ended';
					el.classList.add('is-ended');
					return;
				}
				var d = Math.floor(left / 86400);
				var h = Math.floor((left % 86400) / 3600);
				var m = Math.floor((left % 3600) / 60);
				var s = left % 60;
				b.textContent = (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
			});
		};
		tick();
		setInterval(tick, 1000);
	}

	/* ---- Banner analytics ---- */
	var bannerEls = $$('[data-banner]');
	if (bannerEls.length) {
		var seen = {};
		var queue = [];
		var flush = function () {
			if (!queue.length) { return; }
			beacon('dm_banner_stat', { type: 'view', ids: queue.join(',') });
			queue = [];
		};
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					var id = en.target.getAttribute('data-banner');
					if (en.isIntersecting && en.intersectionRatio >= 0.5 && !seen[id]) {
						seen[id] = 1;
						queue.push(id);
					}
				});
			}, { threshold: [0.5] });
			bannerEls.forEach(function (b) { io.observe(b); });
			setInterval(flush, 4000);
			document.addEventListener('visibilitychange', function () { if (document.hidden) { flush(); } });
		}
		bannerEls.forEach(function (b) {
			b.addEventListener('click', function () { beacon('dm_banner_stat', { type: 'click', ids: b.getAttribute('data-banner') }); });
		});
	}

	/* ---- Click tracking (WhatsApp, shares) ---- */
	document.addEventListener('click', function (e) {
		var a = e.target.closest('[data-track]');
		if (!a) { return; }
		var type = a.getAttribute('data-track');
		if (type === 'affiliate') { return; }
		beacon('dm_track', { type: type, ref: a.getAttribute('data-ref') || 0, page: window.location.href.slice(0, 250) });
	});

	/* ---- Search suggestions ---- */
	$$('[data-suggest]').forEach(function (form) {
		var input = $('input[type="search"]', form);
		var box = $('.dm-suggest', form);
		if (!input || !box || !window.fetch) { return; }
		var t = null;
		var active = -1;
		var last = '';
		function close() { box.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }
		function render(data, q) {
			box.innerHTML = '';
			var items = (data && data.items) || [];
			if (!items.length) {
				var p = document.createElement('div');
				p.className = 'dm-sg-empty';
				p.textContent = i18n.noResults || 'No matches';
				box.appendChild(p);
			}
			items.forEach(function (it) {
				var a = document.createElement('a');
				a.href = it.url;
				a.setAttribute('role', 'option');
				if (it.img) {
					var img = document.createElement('img');
					img.src = it.img; img.alt = ''; img.loading = 'lazy'; img.width = 40; img.height = 40;
					a.appendChild(img);
				} else {
					var ic = document.createElement('i');
					ic.className = 'dm-sg-ico';
					ic.textContent = it.type === 'cat' ? '#' : '•';
					a.appendChild(ic);
				}
				var sp = document.createElement('span');
				var st = document.createElement('strong');
				st.textContent = it.title;
				var sm = document.createElement('small');
				sm.textContent = it.price || it.sub || '';
				sp.appendChild(st); sp.appendChild(sm);
				a.appendChild(sp);
				box.appendChild(a);
			});
			if (data && data.all) {
				var all = document.createElement('a');
				all.href = data.all;
				all.className = 'dm-sg-all';
				all.textContent = (i18n.viewAll || 'See all results') + ' “' + q + '”';
				box.appendChild(all);
			}
			box.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}
		input.addEventListener('input', function () {
			var q = input.value.trim();
			clearTimeout(t);
			if (q.length < 2) { close(); return; }
			t = setTimeout(function () {
				if (q === last && !box.hidden) { return; }
				last = q;
				fetch(cfg.ajax + '?action=dm_suggest&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) { if (input.value.trim() === q) { render(res && res.data, q); } })
					.catch(close);
			}, 220);
		});
		input.addEventListener('keydown', function (e) {
			var links = $$('a', box);
			if (box.hidden || !links.length) { return; }
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				active = e.key === 'ArrowDown' ? Math.min(links.length - 1, active + 1) : Math.max(-1, active - 1);
				links.forEach(function (l, k) { l.classList.toggle('is-active', k === active); });
			} else if (e.key === 'Enter' && active > -1) {
				e.preventDefault();
				window.location.href = links[active].href;
			} else if (e.key === 'Escape') { close(); }
		});
		document.addEventListener('click', function (e) { if (!form.contains(e.target)) { close(); } });
	});

	/* ---- Gallery zoom (desktop) ---- */
	$$('[data-zoom]').forEach(function (z) {
		if (!window.matchMedia('(hover: hover) and (min-width: 960px)').matches) { return; }
		z.addEventListener('mousemove', function (e) {
			var r = z.getBoundingClientRect();
			z.style.setProperty('--zx', ((e.clientX - r.left) / r.width * 100) + '%');
			z.style.setProperty('--zy', ((e.clientY - r.top) / r.height * 100) + '%');
		});
		z.addEventListener('mouseenter', function () { z.classList.add('is-zoom'); });
		z.addEventListener('mouseleave', function () { z.classList.remove('is-zoom'); });
	});

	/* ---- Sticky buy bar (mobile) ---- */
	var buy = $('#dm-buy');
	var bar = $('.dm-buybar');
	if (buy && bar && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (entries) {
			var off = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
			bar.classList.toggle('is-on', off);
			document.body.classList.toggle('has-buybar-on', off);
		}).observe(buy);
	}

	/* ---- Section tabs / TOC highlight ---- */
	[['.dm-ptabs a', 'is-active'], ['.dm-toc a', 'is-active']].forEach(function (conf) {
		var links = $$(conf[0]);
		if (!links.length || !('IntersectionObserver' in window)) { return; }
		var map = {};
		links.forEach(function (l) {
			var id = decodeURIComponent((l.getAttribute('href') || '').slice(1));
			var target = id && document.getElementById(id);
			if (target) { map[id] = l; }
		});
		var obs = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					links.forEach(function (l) { l.classList.remove(conf[1]); });
					var l = map[en.target.id];
					if (l) { l.classList.add(conf[1]); }
				}
			});
		}, { rootMargin: '-35% 0px -60% 0px' });
		Object.keys(map).forEach(function (id) { obs.observe(document.getElementById(id)); });
	});

	/* ---- "Read more" on long category intros ---- */
	$$('[data-clamp]').forEach(function (box) {
		var inner = box.firstElementChild;
		var btn = $('.dm-clamp-btn', box);
		if (!inner || !btn) { return; }
		var lh = parseFloat(getComputedStyle(inner).lineHeight) || 22;
		if (inner.scrollHeight > lh * 3.6) {
			box.classList.add('is-clamped');
			btn.hidden = false;
			btn.addEventListener('click', function () {
				var c = box.classList.toggle('is-clamped');
				btn.textContent = c ? 'Read more' : 'Show less';
			});
		}
	});
	/* ---- Front-end product editor: listing type switch ---- */
	$$('form .dm-card[data-listing-editor]').forEach(function (box) {
		var form = box.closest('form');
		function sync() {
			var r = $('input[name="listing_type"]:checked', box);
			var type = r ? r.value : 'digital';
			['digital', 'service', 'affiliate'].forEach(function (t) {
				$$('.dm-f-' + t, form).forEach(function (el) { el.hidden = t !== type; });
			});
		}
		$$('input[name="listing_type"]', box).forEach(function (r) { r.addEventListener('change', sync); });
		sync();
	});
	/* ---- Self-changing image stacks (landing pages + homepage cards) ---- */
	(function () {
		var stacks = $$('[data-fade]');
		if (!stacks.length || reduceMotion) { return; }
		stacks.forEach(function (box, n) {
			var imgs = $$('img', box);
			var dots = $$('.dm-fade-dots i', box);
			var i = 0, timer = null, visible = false, hover = false;
			if (dots[0]) { dots[0].classList.add('is-on'); }
			function show(k) {
				imgs[i].classList.remove('is-on');
				if (dots[i]) { dots[i].classList.remove('is-on'); }
				i = k % imgs.length;
				var im = imgs[i];
				if (im.loading === 'lazy') { im.loading = 'eager'; }
				im.classList.add('is-on');
				if (dots[i]) { dots[i].classList.add('is-on'); }
			}
			function run() {
				clearInterval(timer);
				if (visible && !hover && !document.hidden) {
					timer = setInterval(function () { show(i + 1); }, 3500 + (n % 4) * 400);
				}
			}
			var host = box.closest('a, article, header') || box;
			host.addEventListener('mouseenter', function () { hover = true; run(); });
			host.addEventListener('mouseleave', function () { hover = false; run(); });
			document.addEventListener('visibilitychange', run);
			if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (en) { visible = en[0].isIntersecting; run(); }, { threshold: 0.2 }).observe(box);
			} else { visible = true; run(); }
		});
	})();
	/* ---- Theme tag filter chips ---- */
	$$('[data-lp-filter]').forEach(function (bar) {
		var list = $('.dm-lp-themes');
		bar.addEventListener('click', function (e) {
			var b = e.target.closest('[data-tag]');
			if (!b || !list) { return; }
			var tag = b.getAttribute('data-tag');
			$$('[data-tag]', bar).forEach(function (x) { var on = x === b; x.classList.toggle('is-active', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
			$$('.dm-lp-theme', list).forEach(function (card) {
				card.hidden = !!tag && (' ' + card.getAttribute('data-tags') + ' ').indexOf(' ' + tag + ' ') < 0;
			});
		});
	});
})();
