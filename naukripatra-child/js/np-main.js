/**
 * NaukriPatra — Frontend JS
 * Dark mode, live table filter, back-to-top, reading progress.
 */
(function () {
	'use strict';

	/* ---------- Dark Mode ---------- */
	var KEY = 'np-theme';
	function applyTheme(t) {
		document.documentElement.classList.toggle('np-dark', t === 'dark');
		var btn = document.getElementById('npDark');
		if (btn) btn.textContent = t === 'dark' ? '☀️' : '🌙';
	}
	try { applyTheme(localStorage.getItem(KEY) || 'light'); } catch (e) {}

	document.addEventListener('DOMContentLoaded', function () {

		// Dark toggle button inject (header me)
		var header = document.querySelector('.site-header .inside-header') || document.body;
		var btn = document.createElement('button');
		btn.id = 'npDark';
		btn.className = 'np-dark-toggle';
		btn.setAttribute('aria-label', 'Toggle dark mode');
		btn.textContent = '🌙';
		header.appendChild(btn);
		try { applyTheme(localStorage.getItem(KEY) || 'light'); } catch (e) {}
		btn.addEventListener('click', function () {
			var next = document.documentElement.classList.contains('np-dark') ? 'light' : 'dark';
			try { localStorage.setItem(KEY, next); } catch (e) {}
			applyTheme(next);
		});

		/* ---------- Live Table Filter ---------- */
		var wrap = document.querySelector('.np-table-wrap');
		if (wrap) {
			var box = document.createElement('div');
			box.className = 'np-filter';
			box.innerHTML = '<input type="search" id="npFilter" placeholder="🔎 Is list me search karo... (naam, state, qualification)">' +
				'<span class="np-filter-count" id="npFilterCount"></span>';
			wrap.parentNode.insertBefore(box, wrap);

			var input = document.getElementById('npFilter');
			var count = document.getElementById('npFilterCount');
			var rows = wrap.querySelectorAll('tbody tr');

			input.addEventListener('input', function () {
				var q = this.value.trim().toLowerCase();
				var shown = 0;
				rows.forEach(function (r) {
					var hit = !q || r.textContent.toLowerCase().indexOf(q) !== -1;
					r.style.display = hit ? '' : 'none';
					if (hit) shown++;
				});
				count.textContent = q ? shown + ' results' : '';
			});
		}

		/* ---------- Back to Top ---------- */
		var top = document.getElementById('npTop');
		if (top) {
			window.addEventListener('scroll', function () {
				top.classList.toggle('np-top-show', window.scrollY > 500);
			}, { passive: true });
			top.addEventListener('click', function () {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		}

		/* ---------- Reading Progress (single post) ---------- */
		var bar = document.getElementById('npProgress');
		if (bar) {
			window.addEventListener('scroll', function () {
				var h = document.documentElement;
				var max = h.scrollHeight - h.clientHeight;
				bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
			}, { passive: true });
		}
	});
})();
