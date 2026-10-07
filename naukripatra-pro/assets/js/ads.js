/* NaukriPatra ads loader: lazy, one at a time, so Adsterra atOptions never collide. */
(function () {
	'use strict';
	var queue = [];
	var busy = false;
	var started = false;

	function inject(tpl, box, done) {
		var frag = tpl.content.cloneNode(true);
		var last = null;
		var nodes = Array.prototype.slice.call(frag.childNodes);
		nodes.forEach(function (n) {
			if (n.nodeName === 'SCRIPT') {
				var s = document.createElement('script');
				Array.prototype.slice.call(n.attributes).forEach(function (a) { s.setAttribute(a.name, a.value); });
				s.text = n.text;
				if (n.src) { last = s; }
				box.appendChild(s);
			} else {
				box.appendChild(n);
			}
		});
		if (last) {
			var fin = function () { done(); };
			last.addEventListener('load', fin);
			last.addEventListener('error', fin);
			setTimeout(fin, 4000);
		} else { done(); }
	}

	function next() {
		if (busy || !queue.length) { return; }
		busy = true;
		var job = queue.shift();
		var called = false;
		inject(job.tpl, job.box, function () { if (!called) { called = true; busy = false; next(); } });
		// If nothing visible appears (no fill), collapse the empty slot so no blank gap stays.
		setTimeout(function () {
			var filled = Array.prototype.some.call(job.box.querySelectorAll('iframe,img,ins,video,div,a'), function (e) { return e.offsetHeight > 20; });
			var slot = job.box.closest('[data-np-ad]');
			if (!filled && slot) { slot.classList.add('np-ad--empty'); }
		}, 7000);
	}

	function enqueue(tpl, box) { queue.push({ tpl: tpl, box: box }); next(); }

	function watch() {
		if (started) { return; }
		started = true;
		document.querySelectorAll('[data-np-ad]').forEach(function (ad) {
			var tpl = ad.querySelector('template');
			var box = ad.querySelector('.np-ad__box');
			if (!tpl || !box) { return; }
			if ('IntersectionObserver' in window) {
				var io = new IntersectionObserver(function (es) {
					es.forEach(function (e) {
						if (e.isIntersecting && e.target.offsetParent !== null) { io.disconnect(); enqueue(tpl, box); }
					});
				}, { rootMargin: '300px 0px' });
				io.observe(ad);
			} else { enqueue(tpl, box); }
		});
		document.querySelectorAll('template[data-np-global]').forEach(function (t) { enqueue(t, document.body); });
		var close = document.querySelector('.np-ad__close');
		if (close) { close.addEventListener('click', function () { close.closest('.np-ad').remove(); }); }
	}

	// Start after first interaction or a configurable delay (protects LCP/TBT).
	['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (ev) {
		window.addEventListener(ev, watch, { once: true, passive: true });
	});
	setTimeout(watch, (window.npAds && npAds.delay) || 3500);
})();
