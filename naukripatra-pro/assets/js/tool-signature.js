/* Signature Scanner: clean, contrast, crop and resize to exam limits. Browser only. */
(function () {
	'use strict';
	var $ = function (id) { return document.getElementById(id); };
	if (!$('sg-file')) { return; }
	var img = null;
	$('sg-file').addEventListener('change', function () {
		var f = this.files[0]; if (!f) { return; }
		var im = new Image(); im.onload = function () { img = im; render(); }; im.src = URL.createObjectURL(f);
	});
	['sg-th', 'sg-ct', 'sg-w', 'sg-h', 'sg-max', 'sg-crop'].forEach(function (id) { $(id).addEventListener('input', render); $(id).addEventListener('change', render); });
	function render() {
		if (!img) { return; }
		var s = document.createElement('canvas'); s.width = img.width; s.height = img.height;
		var sx = s.getContext('2d'); sx.drawImage(img, 0, 0);
		var d = sx.getImageData(0, 0, s.width, s.height), p = d.data, th = +$('sg-th').value, ct = +$('sg-ct').value / 100;
		var minX = s.width, minY = s.height, maxX = 0, maxY = 0;
		for (var y = 0; y < s.height; y++) {
			for (var x = 0; x < s.width; x++) {
				var i = (y * s.width + x) * 4, g = 0.3 * p[i] + 0.59 * p[i + 1] + 0.11 * p[i + 2], v;
				if (g > th) { v = 255; } else { v = Math.max(0, g * (1 - ct)); if (x < minX) { minX = x; } if (x > maxX) { maxX = x; } if (y < minY) { minY = y; } if (y > maxY) { maxY = y; } }
				p[i] = p[i + 1] = p[i + 2] = v; p[i + 3] = 255;
			}
		}
		sx.putImageData(d, 0, 0);
		var crop = $('sg-crop').checked && maxX > minX, cx = crop ? Math.max(0, minX - 4) : 0, cy = crop ? Math.max(0, minY - 4) : 0;
		var cw = crop ? Math.min(s.width - cx, maxX - minX + 8) : s.width, ch = crop ? Math.min(s.height - cy, maxY - minY + 8) : s.height;
		var out = $('sg-canvas'), w = +$('sg-w').value, h = +$('sg-h').value; out.width = w; out.height = h;
		var ox = out.getContext('2d'); ox.fillStyle = '#fff'; ox.fillRect(0, 0, w, h);
		var r = Math.min(w / cw, h / ch); ox.drawImage(s, cx, cy, cw, ch, (w - cw * r) / 2, (h - ch * r) / 2, cw * r, ch * r);
		var max = +$('sg-max').value * 1024, q = 0.95;
		(function step() {
			out.toBlob(function (b) {
				if (b.size > max && q > 0.1) { q -= 0.1; return step(); }
				$('sg-info').textContent = w + 'x' + h + ' px, ' + Math.round(b.size / 1024) + ' KB' + (b.size > max ? ' - above limit; reduce pixels.' : '');
				$('sg-dl').href = URL.createObjectURL(b); $('sg-dl').hidden = false;
			}, 'image/jpeg', q);
		})();
	}
})();
