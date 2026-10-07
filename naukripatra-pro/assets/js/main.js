/* NaukriPatra Pro - main.js. Vanilla JS, no dependencies. */
(function () {
	'use strict';
	var root = document.documentElement;

	// Dark / light toggle (initial value already set by inline head script).
	var themeBtn = document.getElementById('np-theme-toggle');
	if (themeBtn) {
		themeBtn.addEventListener('click', function () {
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			try { localStorage.setItem('np-theme', next); } catch (e) {}
		});
	}

	// Mobile menu: aria-expanded, close on outside tap and Esc.
	var nav = document.querySelector('.np-nav');
	var toggle = nav && nav.querySelector('.np-nav__toggle');
	function setMenu(open) {
		nav.classList.toggle('is-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	}
	if (toggle) {
		toggle.addEventListener('click', function () {
			setMenu(toggle.getAttribute('aria-expanded') !== 'true');
		});
		document.addEventListener('click', function (e) {
			if (!nav.contains(e.target)) { setMenu(false); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { setMenu(false); toggle.focus(); }
		});
	}
})();

/* ---- Page behaviours ---- */
(function () {
	'use strict';
	var d = document;

	// Ticker: tap toggles pause (touch devices have no hover).
	d.querySelectorAll('.np-ticker__track').forEach(function (t) {
		t.addEventListener('click', function () { t.classList.toggle('is-paused'); });
	});

	// State dropdown jumps to the state page; section page state filter auto-submits.
	d.querySelectorAll('[data-np-jump]').forEach(function (s) {
		s.addEventListener('change', function () { if (s.value) { window.location.href = s.value; } });
	});
	d.querySelectorAll('[data-np-submit]').forEach(function (s) {
		s.addEventListener('change', function () { s.form.submit(); });
	});

	// Live filter for the rows on the current page.
	var live = d.querySelector('[data-np-live]');
	if (live) {
		live.addEventListener('input', function () {
			var q = live.value.toLowerCase();
			d.querySelectorAll('.np-list tbody tr:not(.np-ad-row)').forEach(function (r) {
				r.style.display = r.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
			});
		});
	}

	// Fade-up on scroll (transform/opacity only; content stays visible without JS).
	var cards = d.querySelectorAll('.np-boxes .np-card, .np-states');
	if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		var io = new IntersectionObserver(function (es) {
			es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
		}, { rootMargin: '0px 0px -40px 0px' });
		cards.forEach(function (c) { c.classList.add('np-fade'); io.observe(c); });
	}

	// Reader toolbar: font size (remembered), print, copy link.
	var root = d.documentElement;
	var size = 17;
	try { size = parseInt(localStorage.getItem('np-font'), 10) || 17; } catch (e) {}
	function applySize() { root.style.setProperty('--np-reader', size + 'px'); }
	applySize();
	d.querySelectorAll('[data-np-font]').forEach(function (b) {
		b.addEventListener('click', function () {
			size = Math.max(14, Math.min(24, size + parseInt(b.getAttribute('data-np-font'), 10)));
			applySize();
			try { localStorage.setItem('np-font', size); } catch (e) {}
		});
	});
	d.querySelectorAll('[data-np-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });
	d.querySelectorAll('[data-np-copy]').forEach(function (b) {
		b.addEventListener('click', function () {
			var u = b.getAttribute('data-np-copy');
			if (navigator.clipboard) { navigator.clipboard.writeText(u).then(function () { b.textContent = '✓'; }); }
		});
	});

	// View counter: one tiny REST call, nothing in the cached HTML.
	if (window.npData && npData.view && navigator.sendBeacon) {
		window.addEventListener('load', function () { setTimeout(function () { navigator.sendBeacon(npData.view); }, 2000); });
	}
})();
