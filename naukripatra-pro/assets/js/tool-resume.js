/* Resume Maker: live preview, 3 templates, print / save as PDF. Browser only. */
(function () {
	'use strict';
	var root = document.getElementById('np-resume');
	if (!root) { return; }
	var prev = document.getElementById('rs-preview'), tpl = document.getElementById('rs-tpl');
	function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
	function list(t) { var l = t.split(/\n+/).map(function (x) { return x.trim(); }).filter(Boolean); return l.length ? '<ul>' + l.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>' : ''; }
	function render() {
		var v = {};
		root.querySelectorAll('[data-k]').forEach(function (el) { v[el.getAttribute('data-k')] = el.value; });
		var html = '<h3>' + esc(v.name || 'Your Name') + '</h3><p>' + esc(v.title || '') + '</p><p>' + esc(v.contact || '') + '</p>';
		[['Objective', v.obj ? '<p>' + esc(v.obj) + '</p>' : ''], ['Education', list(v.edu || '')], ['Experience', list(v.exp || '')], ['Skills', list(v.skills || '')], ['Languages', list(v.lang || '')]].forEach(function (s) {
			if (s[1]) { html += '<h4>' + s[0] + '</h4>' + s[1]; }
		});
		prev.className = 'np-resume__preview t-' + tpl.value;
		prev.innerHTML = html;
	}
	root.addEventListener('input', render);
	tpl.addEventListener('change', render);
	document.getElementById('rs-print').addEventListener('click', function () {
		document.body.classList.add('np-printing'); window.print();
		setTimeout(function () { document.body.classList.remove('np-printing'); }, 500);
	});
	render();
})();
