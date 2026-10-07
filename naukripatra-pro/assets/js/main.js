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
