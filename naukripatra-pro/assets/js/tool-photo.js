/* Photo Resizer: everything runs in the browser. */
(function () {
	'use strict';
	var $ = function (id) { return document.getElementById(id); };
	if (!$('ph-go')) { return; }
	var presets = [
		['Custom', 200, 230, 20, 50], ['SSC photo 100x120 (20-50 KB)', 100, 120, 20, 50], ['SSC signature 140x60 (10-20 KB)', 140, 60, 10, 20],
		['UPSC photo 350x350 (up to 300 KB)', 350, 350, 20, 300], ['IBPS photo 200x230 (20-50 KB)', 200, 230, 20, 50],
		['IBPS signature 140x60 (10-20 KB)', 140, 60, 10, 20], ['Railway RRB photo 35x45 mm (~413x531, up to 100 KB)', 413, 531, 20, 100]
	];
	presets.forEach(function (p, i) { var o = document.createElement('option'); o.value = i; o.textContent = p[0]; $('ph-preset').appendChild(o); });
	$('ph-preset').addEventListener('change', function () {
		var p = presets[this.value]; $('ph-w').value = p[1]; $('ph-h').value = p[2]; $('ph-min').value = p[3]; $('ph-max').value = p[4];
	});
	var img = null;
	$('ph-file').addEventListener('change', function () {
		var f = this.files[0]; if (!f) { return; }
		var im = new Image(); im.onload = function () { img = im; $('ph-info').textContent = 'Loaded ' + im.width + 'x' + im.height + ' px.'; };
		im.src = URL.createObjectURL(f);
	});
	function toBlob(c, type, q) { return new Promise(function (res) { c.toBlob(res, type, q); }); }
	$('ph-go').addEventListener('click', async function () {
		if (!img) { $('ph-info').textContent = 'Please choose a photo first.'; return; }
		var w = +$('ph-w').value, h = +$('ph-h').value, max = +$('ph-max').value * 1024, type = $('ph-fmt').value;
		var c = $('ph-canvas'); c.width = w; c.height = h;
		var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, w, h);
		var r = Math.max(w / img.width, h / img.height), sw = w / r, sh = h / r; // Cover-crop.
		x.drawImage(img, (img.width - sw) / 2, (img.height - sh) / 2, sw, sh, 0, 0, w, h);
		var blob;
		if (type === 'image/jpeg') {
			var lo = 0.05, hi = 0.95; blob = await toBlob(c, type, hi);
			if (blob.size > max) { for (var i = 0; i < 8; i++) { var m = (lo + hi) / 2; blob = await toBlob(c, type, m); if (blob.size > max) { hi = m; } else { lo = m; } } blob = await toBlob(c, type, lo); }
		} else { blob = await toBlob(c, type); }
		var kb = Math.round(blob.size / 1024), ok = blob.size <= max;
		$('ph-info').textContent = w + 'x' + h + ' px, ' + kb + ' KB' + (ok ? '' : ' - still above the limit; try JPG or smaller pixels.') + (kb < +$('ph-min').value ? ' (below minimum size; use a larger pixel size)' : '');
		var url = URL.createObjectURL(blob);
		$('ph-prev').src = url; $('ph-prev').hidden = false;
		$('ph-dl').href = url; $('ph-dl').download = 'photo.' + (type === 'image/png' ? 'png' : 'jpg'); $('ph-dl').hidden = false;
	});
})();
